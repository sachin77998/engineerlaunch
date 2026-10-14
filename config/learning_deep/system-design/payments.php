<?php

// System Design › Payments, broking and banking case studies.
return [

'Design a Payment Gateway (Razorpay / Paytm / PayU)' => <<<'MD'
## What it does
A payment gateway lets a merchant (an e-commerce site, an app) accept money by card, UPI, net banking or wallet. The merchant creates an order, the customer pays on the gateway's checkout, the gateway talks to banks and card networks, and the merchant is told the result and later receives the money (settlement).

## Functional requirements
- Create order, show checkout, authorise and capture payments (card, UPI, net banking, wallets).
- Webhooks/callbacks to merchants with the final status.
- Refunds (full and partial), settlement to merchant bank accounts (T+1 / T+2), reconciliation with banks.
- Dashboard, reports, disputes/chargebacks.

## Non-functional requirements
- Correctness above all: never charge twice, never lose a payment.
- High availability (99.99%+); every minute down is lost sales for thousands of merchants.
- Latency: checkout page < 300 ms; the bank leg is outside our control (2–30 s).
- Security and compliance: PCI-DSS for card data, RBI data-localisation, encryption at rest and in transit.

## Scale estimate
- 10 crore payments/day ≈ 1,150/s on average, peaks of 10–20× during sales (≈ 20,000/s).
- Each payment ≈ 2 KB of core data plus events → ~200 GB/day before indexes.

## High-level architecture
```text
Merchant server ──▶ API Gateway (auth, rate limit) ──▶ Order Service ──▶ Payments DB
Customer ──▶ Checkout (web/SDK) ──▶ Payment Service ──▶ Router ──▶ Bank/Network adapters
                                                     │              (Visa/Mastercard/RuPay, NPCI UPI, net-banking)
                                                     ├──▶ Ledger Service (double-entry)
                                                     ├──▶ Event bus (Kafka) ──▶ Webhook Service ──▶ Merchant
                                                     ├──▶ Risk/Fraud Service
                                                     └──▶ Tokenisation Vault (card numbers, PCI zone)
Settlement & Reconciliation jobs ◀── bank settlement files (daily)
```

## Core data model
| Table | Key columns |
|---|---|
| orders | order_id, merchant_id, amount, currency, status (created/attempted/paid), receipt, created_at |
| payments | payment_id, order_id, method, amount, status (created → authorised → captured / failed / refunded), gateway_ref, bank_ref, idempotency_key |
| refunds | refund_id, payment_id, amount, status |
| ledger_entries | entry_id, txn_id, account, debit, credit, created_at (append-only) |
| webhook_deliveries | event_id, merchant_id, url, attempts, next_retry_at, status |

## Payment flow (card)
1. Merchant server calls `POST /orders` with amount and an `Idempotency-Key`.
2. Customer opens checkout; card number goes straight to the PCI vault and is replaced by a token.
3. Payment Service creates a `payment` (status created) and asks the Router which acquiring bank to use (success rate, cost, downtime).
4. Adapter sends authorisation to the card network → issuer bank → 3-D Secure OTP → authorised.
5. Payment marked `authorised`, then `captured` (immediately or when the merchant ships).
6. Ledger records double entries (customer receivable ↔ merchant payable, gateway fee).
7. `payment.captured` event → Kafka → Webhook Service delivers to the merchant with retries.
8. Next day: bank settlement file arrives; reconciliation matches every captured payment; money is settled to the merchant minus fees.

## Key design decisions
- **Idempotency:** every create/capture/refund API takes an idempotency key stored with a unique index. A retried request returns the original result instead of charging again.
- **State machine:** payments move only through allowed transitions (created → authorised → captured → refunded). Updates use `UPDATE … WHERE status = 'authorised'` (compare-and-set) so two workers cannot capture twice.
- **Double-entry ledger:** money is never "edited"; each movement is two immutable rows that sum to zero. Balances are derived, which makes audits and reconciliation possible.
- **Unknown outcomes:** a bank timeout does not mean failure. Mark the payment `pending`, poll the bank's status API and reconcile with the settlement file; only then mark failed and auto-refund if money was debited.
- **Outbox pattern:** the DB update and the "payment captured" event are written in one transaction (outbox table), then a relay publishes to Kafka. No event is lost if the service crashes in between.
- **Webhooks:** at-least-once delivery with exponential backoff (1 min, 5 min, 30 min … up to 24 h), signed with HMAC so merchants can verify them; merchants must handle duplicates by event_id.
- **Smart routing:** track success rate per bank/card type/minute; route away from a degrading acquirer automatically.

## Scaling and availability
- Shard payments by merchant_id or payment_id; read replicas for dashboards; OLAP store (ClickHouse/BigQuery) for reports.
- Multi-AZ deployment, active-passive across regions for the vault; circuit breakers around every bank adapter.
- Rate limits per merchant to protect shared bank connections.

## Interview follow-ups
- *How do you prevent double charges when the user clicks Pay twice?* Idempotency key on order + one active attempt per order + compare-and-set state transitions.
- *What if the webhook endpoint is down?* Retries with backoff, a dashboard of failed deliveries, and a fetch-status API so merchants can poll.
- *Why not store card numbers in the main DB?* PCI scope: only the isolated vault sees PANs; everything else uses tokens.
MD,

'Design a UPI Payments App (PhonePe / Google Pay / Paytm)' => <<<'MD'
## How UPI works (the part the app does not own)
UPI is run by NPCI. A UPI app (a TPAP such as PhonePe, Google Pay) is the front end; a sponsor PSP bank connects it to NPCI; NPCI routes the request between the payer's bank and the payee's bank, which debit and credit the actual accounts. The app never holds the money.

```text
User ─▶ UPI App ─▶ PSP bank (e.g. Yes/Axis/ICICI) ─▶ NPCI UPI switch ─▶ Payer bank (debit)
                                                                  └──▶ Payee bank (credit)
```

## Functional requirements
- Onboarding: verify mobile via SMS from the registered SIM, discover bank accounts linked to that number, set the UPI PIN (with debit-card details).
- Pay to UPI ID / mobile / QR code; collect requests; transaction history; split bills; merchant payments; UPI Lite / AutoPay mandates.
- Notifications and dispute raising.

## Non-functional requirements
- Peak load: UPI handles 15+ billion transactions a month; a large app sees 5,000–10,000 TPS at peak (salary day, festivals).
- Latency target: response in a few seconds end-to-end; app-side processing < 200 ms.
- Strong consistency for the transaction record; never show "success" before NPCI confirms.
- Security: device binding, UPI PIN entered in the NPCI common library (the app never sees the PIN), fraud checks.

## Architecture
```text
Mobile app ──▶ Edge/API gateway ──▶ User & Device Service (device binding, SIM check)
                                 ──▶ VPA/Contacts Service (UPI ID lookup, QR parsing)
                                 ──▶ Transaction Service ──▶ PSP adapter ──▶ NPCI
                                 ──▶ Risk Engine (velocity rules, ML scores)
Transaction events ──▶ Kafka ──▶ Notifications · History (read model) · Rewards · Analytics
```

## Payment flow
1. User scans a QR → app parses the UPI intent (`upi://pay?pa=merchant@bank&am=250`).
2. Transaction Service creates a txn (status INITIATED) with a unique txn id; Risk Engine checks device, amount and velocity.
3. User enters the PIN inside the NPCI library; the encrypted PIN block goes to the PSP bank.
4. PSP sends the request to NPCI; NPCI asks the payer bank to debit and the payee bank to credit.
5. Response (SUCCESS / FAILURE / DEEMED / PENDING) flows back; the txn status is updated with a compare-and-set.
6. Event published → push notification, history updated, cashback evaluated asynchronously.

## Hard problems
- **Pending / deemed transactions:** a timeout is common at peak. Keep the txn PENDING, run a status-check job with backoff against the PSP/NPCI, and show "processing" in the app. Never retry the debit blindly; that could debit twice.
- **Idempotency:** the txn id is generated once on the client/server and reused on retries; NPCI deduplicates by it.
- **Hot spots:** salary-day spikes; auto-scale stateless services, pre-warm connections to PSP banks, queue non-critical work (rewards, analytics).
- **History at scale:** write path in a sharded OLTP store (shard by user id); read path in a denormalised store (Cassandra/HBase) keyed by user and time for fast "my transactions".
- **Multiple PSP banks:** route across sponsor banks; if one PSP has downtime, new requests go to another (users may hold handles on several PSPs).
- **Fraud:** device fingerprint, SIM change detection, unusual amount/time/location, first-time payee warnings; collect-request spam limits.

## Data model (simplified)
| Table | Key columns |
|---|---|
| users | user_id, mobile, device_id, status |
| vpas | vpa (name@psp), user_id, linked_account_ref, is_primary |
| transactions | txn_id, payer_vpa, payee_vpa, amount, status, npci_rrn, created_at, updated_at |
| mandates | mandate_id, user_id, merchant, max_amount, frequency, status |

## Interview follow-ups
- *Where is the money?* In the bank accounts; the app stores only references and transaction records.
- *How does UPI Lite differ?* A small on-device balance (debited from the bank once) allows PIN-less, offline-tolerant small payments with fewer calls to the bank's core system.
- *How do you show history fast for 40 crore users?* A per-user time-ordered read model, cached recent pages, and archival of old data.
MD,

'Design a Stock Broking Platform (Zerodha / Groww)' => <<<'MD'
## What it does
Lets investors place buy/sell orders on NSE/BSE, see live prices, hold stocks in a demat account and invest in mutual funds (Groww). The exchange matches orders; the broker validates, routes and records them and manages risk and funds.

## Functional requirements
- Live market data (quotes, depth, charts) for thousands of instruments.
- Place / modify / cancel orders (market, limit, stop-loss), see order book, positions, holdings, P&L.
- Funds: add money (UPI/net banking), withdraw, margin calculation.
- Mutual funds: SIPs and lump-sum orders via the exchange MF platforms / RTAs (CAMS, KFintech).

## Non-functional requirements
- Market open (09:15 IST) is a thundering herd: millions of users log in and place orders in the first minutes.
- Order placement latency: tens of milliseconds inside the broker before sending to the exchange.
- Correctness: never sell shares the user does not hold, never exceed available margin, never lose an order.
- Regulatory: SEBI audit trails, order logs kept for years, two-factor login.

## Architecture
```text
Exchange feeds (multicast) ─▶ Market Data Ingest ─▶ Tick store ─▶ WebSocket fan-out ─▶ Apps (millions)
App ─▶ API gateway ─▶ Order Management System (OMS)
                         ├─▶ Risk Management System (RMS): margin, holdings, limits
                         ├─▶ Exchange gateway (FIX/NNF) ─▶ NSE/BSE ─▶ trade confirmations
                         └─▶ Order & trade DB ─▶ positions / holdings / P&L
Back office (end of day): contract notes, settlement with clearing corporation, ledger
```

## Order flow
1. User places "Buy 10 INFY limit ₹1,500".
2. OMS assigns an order id and persists it (status OPEN_PENDING).
3. RMS checks: available funds/margin ≥ 10 × 1,500 + charges; for sells, holdings or position available. Funds are blocked atomically.
4. Exchange gateway sends the order; the exchange acknowledges with its order id.
5. Partial or full fills arrive as trade messages; positions and P&L update; funds blocked are released or consumed.
6. Status pushed to the app over WebSocket; contract note generated after market hours.

## Market data at scale
- One ingest process per exchange feed normalises ticks; the latest quote per instrument lives in memory (and Redis).
- Fan-out: users subscribe to instrument tokens over WebSocket; servers send only subscribed tokens, batched every 100–250 ms to cap bandwidth.
- Charts: candles (1-min, 5-min…) built from ticks and stored in a time-series DB.

## Hard problems
- **9:15 spike:** pre-scale before market open, keep login/session tokens in a fast store, separate the order path from heavy read paths (portfolio, charts), and protect the OMS with per-user rate limits.
- **Consistency of funds and holdings:** RMS keeps balances in memory per user with a write-ahead log, or uses row-level locks / compare-and-set in the DB, so two simultaneous orders cannot both use the same money.
- **Exchange disconnects:** the gateway resends unacknowledged orders using sequence numbers; the exchange deduplicates; at startup orders are reconciled with the exchange order book.
- **Audit trail:** every state change is an append-only event (event sourcing works well here).

## Interview follow-ups
- *Why not match orders yourself?* Matching happens only on the exchange; the broker routes orders (except internal mutual-fund/ledger transfers).
- *How do you show live P&L for 1 crore users?* Compute on the client from holdings plus streamed prices; the server streams prices, not per-user P&L.
- *What makes Groww's MF flow different?* Orders are batched and sent to the exchange MF platform/RTA before the daily cut-off; NAV is applied later, so status is asynchronous (order placed → units allotted next day).
MD,

'Design a Core Banking System (HDFC Bank / Indian Bank)' => <<<'MD'
## What it does
Core banking (CBS) is the system of record for accounts and balances: savings/current accounts, deposits, loans, interest, and every debit and credit coming from branches, ATMs, UPI, IMPS/NEFT/RTGS, cards and net banking. Large Indian banks run platforms such as Finacle, Flexcube or BaNCS, surrounded by channel and middleware layers.

## Functional requirements
- Customer and account management (KYC, CIF), account opening, nominee, freezes/liens.
- Posting transactions: transfers, cash deposits/withdrawals, standing instructions, charges.
- Interest accrual and application (daily accrual, monthly/quarterly credit), loan EMIs and schedules.
- Payment rails: UPI, IMPS (instant), NEFT (batches every 30 min), RTGS (high value, real time), ATM/POS switch.
- Statements, regulatory reports to RBI, AML monitoring.

## Non-functional requirements
- Absolute correctness and durability: a balance is never wrong and a committed transaction is never lost.
- Very high availability (24×7 UPI/IMPS); planned downtime minimised.
- Throughput: a large bank posts thousands of transactions per second at peak (salary days, month end).
- Strict audit, data residency in India, encryption, maker-checker approvals.

## Architecture
```text
Channels: Branch · ATM · Mobile/Net banking · UPI (via NPCI) · Cards
        ─▶ API / ESB layer (authentication, routing, limits)
        ─▶ Core Banking: Accounts · Ledger · Products · Interest · Loans
        ─▶ Payment hubs: NEFT/RTGS (RBI), IMPS/UPI (NPCI), Card switch
        ─▶ Data: OLTP (Oracle/DB2) ─▶ CDC ─▶ Data warehouse (reporting, AML, RBI returns)
End of Day (EOD) batch: interest, charges, statements, GL reconciliation
```

## Posting a transfer (A → B, same bank)
1. Channel sends the request with a unique reference; duplicates are rejected by the reference.
2. In one database transaction: lock account A (and B) in a fixed order to avoid deadlocks, check available balance (balance − lien − holds), insert the debit and credit ledger rows, update both balances.
3. Commit → publish an event for SMS/notifications and fraud monitoring.

## Inter-bank transfer (IMPS / UPI)
1. Debit the customer and credit an intermediary "IMPS/UPI outward suspense" account.
2. Send to NPCI. On success, the suspense account is cleared during settlement; on failure or timeout the debit is reversed after the status check.
3. Daily settlement with NPCI/RBI and reconciliation of every transaction.

## Key ideas
- **Double-entry general ledger:** every posting is balanced; account balances and the bank's GL must reconcile daily.
- **Available vs ledger balance:** holds (card authorisations, liens, cheques in clearing) reduce what can be withdrawn without changing the ledger balance.
- **Concurrency:** row locks or optimistic versioning on the account row; hot accounts (a merchant receiving thousands of credits per second) use sub-ledgers/aggregated postings to avoid lock contention.
- **EOD/BOD batch:** interest accrual for crores of accounts runs in parallel partitions; modern CBS keep the bank online during EOD by using a "next business date" for postings.
- **Resilience:** synchronous replication to a near DR site, asynchronous to a far DR site; regular DR drills.

## Interview follow-ups
- *How do you avoid two ATM withdrawals overdrawing the account?* Lock the account row (or compare-and-set on a version) while checking and debiting in one transaction.
- *What happens if the UPI debit succeeds but NPCI times out?* The amount sits in the outward suspense account; the status check or settlement decides whether to complete or reverse; the customer sees "pending" and auto-reversal within the timeline set by RBI.
- *Why NEFT is batch and RTGS real time?* NEFT settles in half-hourly net batches at RBI; RTGS settles each transaction individually and immediately (gross settlement).
MD,

];
