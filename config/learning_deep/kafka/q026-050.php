<?php

// Apache Kafka interview questions 26–50, in depth.
return [

'What does acks=1 mean?' => <<<'MD'
## Meaning
With `acks=1` the producer receives success as soon as the **partition leader has written the record to its local log**. The leader does not wait for followers to copy it.

## The risk window
```text
1. Producer → Leader (broker 1) writes offset 500 → replies "OK"
2. Broker 1 crashes before followers fetched offset 500
3. Broker 2 (follower, has up to 499) becomes leader
4. Offset 500 is gone, although the producer was told it succeeded
```
When broker 1 comes back it truncates its log to match the new leader, so the record is permanently lost. No exception is ever raised to the producer.

## Characteristics
| | acks=1 |
|---|---|
| Latency | low: one disk write (page cache) on the leader |
| Throughput | high |
| Data loss | possible on leader failure |
| Duplicates | possible with retries unless idempotence |
| Idempotent producer | not allowed (requires `acks=all`) |

```java
props.put(ProducerConfig.ACKS_CONFIG, "1");
props.put(ProducerConfig.ENABLE_IDEMPOTENCE_CONFIG, false);   // must be disabled explicitly in Kafka 3.x
```

## When it was the default
Before Kafka 3.0, `acks=1` was the default, which is why many older systems lost data silently during broker failures.

## When it is reasonable
Logs, metrics, clickstream or analytics events where losing a few records during a rare broker crash is acceptable and lower latency matters.

## Summary
`acks=1` means only the leader confirmed the write: fast, but a leader crash before replication loses acknowledged records.
MD,

'What does acks=all mean?' => <<<'MD'
## Meaning
With `acks=all` (equivalent to `acks=-1`) the leader replies success only after **every replica currently in the ISR** has the record. It is the most durable setting and the default since Kafka 3.0.

## Why it is not enough on its own
"All in-sync replicas" can shrink to just the leader if followers fall behind. Then `acks=all` behaves like `acks=1`. The topic or broker setting **`min.insync.replicas`** fixes this by requiring a minimum ISR size:
```properties
# topic
replication.factor=3
min.insync.replicas=2
# producer
acks=all
enable.idempotence=true
```
Now a write succeeds only if at least two replicas have it. If the ISR drops to one, the producer gets `NotEnoughReplicasException` and retries until `delivery.timeout.ms` expires.

## What you gain
- No loss of acknowledged records when one broker fails.
- Enables the **idempotent producer** and **transactions**.

## What it costs
- Slightly higher latency: the leader waits for followers' next fetch (usually a few ms in one region; more across availability zones).
- Lower availability for writes when replicas are down.
- Throughput stays high with batching (`linger.ms`, `batch.size`, compression).

## Example: payment events in Spring Boot
```yaml
spring:
  kafka:
    producer:
      acks: all
      retries: 2147483647
      properties:
        enable.idempotence: true
        delivery.timeout.ms: 120000
        max.in.flight.requests.per.connection: 5
```

## Summary
`acks=all` waits for all in-sync replicas; combined with `min.insync.replicas=2` and replication factor 3 it guarantees acknowledged records survive a broker failure.
MD,

'What is min.insync.replicas?' => <<<'MD'
## What it is
`min.insync.replicas` (topic or broker setting) is the **minimum number of in-sync replicas that must acknowledge a write** when the producer uses `acks=all`. If the ISR is smaller, the broker rejects the write with `NotEnoughReplicasException` (or `NotEnoughReplicasAfterAppendException`).

It does nothing for `acks=0` or `acks=1` producers.

## Example with replication factor 3
| ISR size | min.insync.replicas=2, acks=all |
|---|---|
| 3 | writes succeed |
| 2 (one broker down) | writes succeed |
| 1 (two brokers down) | writes rejected; reads still work |

So the cluster tolerates one failure without stopping writes and never accepts a write that exists on only one machine.

## Common mistakes
- `min.insync.replicas = replication.factor` (e.g. 3 and 3): any single broker restart or rolling upgrade blocks all writes.
- `min.insync.replicas=1` with `acks=all`: a write can be acknowledged by the leader alone, then lost.
- Forgetting internal topics: set `transaction.state.log.min.isr=2` and `offsets.topic.replication.factor=3` too.

## Setting it
```bash
kafka-configs.sh --bootstrap-server localhost:9092 --alter \
  --entity-type topics --entity-name payments --add-config min.insync.replicas=2
```
Broker default: `min.insync.replicas=2` in `server.properties`.

## Monitoring
`UnderMinIsrPartitionCount` > 0 means some partitions are rejecting `acks=all` writes. Alert on it immediately.

## Summary
`min.insync.replicas` sets the minimum number of replicas that must confirm an `acks=all` write; RF=3 with min ISR 2 is the standard choice, trading a little availability for guaranteed durability.
MD,

'What is producer batching?' => <<<'MD'
## What it is
The producer does not send each record in its own network request. It groups records going to the **same partition** into a **batch** in memory (the `RecordAccumulator`), and a background **sender thread** ships batches to brokers. One request can carry batches for many partitions on that broker.

## Why it matters
- Far fewer network round trips and disk writes.
- Compression works on whole batches, so the ratio is much better.
- Brokers write a batch as one unit.
Batching is the main reason Kafka reaches hundreds of thousands to millions of records per second per producer.

## Settings
| Setting | Default | Effect |
|---|---|---|
| `batch.size` | 16384 bytes | maximum size of one partition's batch |
| `linger.ms` | 5 ms (Kafka 4.0; 0 before) | how long to wait for more records before sending a non-full batch |
| `buffer.memory` | 32 MB | total memory for unsent records |
| `max.block.ms` | 60 s | how long `send()` blocks when the buffer is full |
| `compression.type` | none | compress each batch |
| `max.request.size` | 1 MB | maximum request size |

A batch is sent when it is full **or** `linger.ms` has passed, whichever comes first.

## Tuning for throughput
```properties
batch.size=65536          # 64 KB
linger.ms=10
compression.type=lz4
buffer.memory=67108864
```
Latency rises by a few milliseconds while throughput and compression improve greatly.

## Sticky partitioner
For records without a key, the producer sticks to one partition until the batch is sent, then switches. This produces fuller batches than strict round-robin.

## Back-pressure
If brokers are slow and the buffer fills, `send()` blocks up to `max.block.ms`, then throws `TimeoutException`. This protects the application from running out of memory.

## Summary
Producer batching groups records per partition and sends them together, controlled mainly by `batch.size` and `linger.ms`; bigger batches mean higher throughput and better compression at a small latency cost.
MD,

'What does linger.ms do?' => <<<'MD'
## What it does
`linger.ms` is how long the producer **waits for more records to fill a batch** before sending it, even if the batch is not full. It trades a little latency for larger batches.

- `linger.ms=0`: send as soon as the sender thread is free (still batches records that arrived meanwhile).
- `linger.ms=5` (default in Kafka 4.0): wait up to 5 ms.
- `linger.ms=20–100`: very high throughput pipelines.

A batch is sent at whichever comes first: `batch.size` reached or `linger.ms` expired.

## Example effect
Producer sending 50,000 small records per second to one partition:
| linger.ms | Records per batch | Requests per second |
|---|---|---|
| 0 | ~10 | ~5,000 |
| 10 | ~500 | ~100 |
Fewer requests reduce CPU on producer and broker, improve compression and increase throughput.

## Choosing
- Latency-sensitive (an OTP request event): `linger.ms` 0–5.
- Bulk events (clickstream, logs, CDC): 10–50 with `batch.size` 64–256 KB and compression.
- Low traffic: linger adds up to `linger.ms` of latency per record but gives little benefit; keep it small.

```java
props.put(ProducerConfig.LINGER_MS_CONFIG, 10);
props.put(ProducerConfig.BATCH_SIZE_CONFIG, 64 * 1024);
props.put(ProducerConfig.COMPRESSION_TYPE_CONFIG, "lz4");
```

## Related
`flush()` sends all buffered records immediately (useful at shutdown or before committing work), and `close()` flushes and releases resources.

## Summary
`linger.ms` deliberately delays sending to build bigger batches; a few milliseconds usually give large throughput gains with negligible latency impact.
MD,

'Which compression codecs does Kafka support?' => <<<'MD'
## Supported codecs
Set with `compression.type` on the producer (and optionally on the topic or broker):
| Codec | Compression ratio | CPU cost | Notes |
|---|---|---|---|
| `none` | 1× | none | default on the producer |
| `gzip` | high | high | good for low-volume, bandwidth-limited links |
| `snappy` | moderate | low | fast, from Google |
| `lz4` | moderate | very low | usually the best default for throughput |
| `zstd` | high (close to gzip) | moderate | Kafka 2.1+; excellent balance, levels configurable since 3.8 |

## How it works
The producer compresses a **whole batch**. The broker stores it compressed (no recompression if the topic's `compression.type=producer`, the default), and the consumer decompresses. Compression therefore saves network bandwidth, broker disk and page cache, and replication traffic.

JSON event payloads with repeated field names often compress 3–10×.

```properties
compression.type=zstd       # producer
linger.ms=10                # bigger batches compress better
batch.size=131072
```

## Topic-level compression
If a topic sets `compression.type=gzip` and producers send `lz4`, the broker must **recompress**, costing CPU. Keep topics at `producer` unless you need to enforce a codec.

## Choosing
- General purpose, high throughput: **lz4** or **zstd**.
- Storage cost matters most (long retention): **zstd**.
- Very CPU-constrained producers: **snappy** or **lz4**.
- Already compressed payloads (images, encrypted data): **none**.

## Summary
Kafka supports gzip, snappy, lz4 and zstd, applied per batch by the producer; lz4 and zstd are the usual choices, and larger batches improve the ratio.
MD,

'What is an idempotent producer?' => <<<'MD'
## The problem
A producer sends a record, the broker writes it, but the acknowledgement is lost (network glitch, timeout). The producer retries and the record is written **twice**.

## The solution
With `enable.idempotence=true` (default since Kafka 3.0), the broker **deduplicates retries**:
- Each producer gets a **Producer ID (PID)** and an epoch.
- Each batch sent to a partition carries a **sequence number**.
- The broker remembers the last sequence numbers per PID and partition. A retried batch with a sequence it already has is acknowledged but not written again; a gap in sequence numbers is rejected.

Result: **exactly-once writes per partition within one producer session**, and ordering is preserved even with up to 5 in-flight requests.

## Required settings (set automatically)
```properties
enable.idempotence=true
acks=all
retries=2147483647
max.in.flight.requests.per.connection=5   # must be ≤ 5
```
If you explicitly set conflicting values (e.g. `acks=1`), the producer throws a configuration error in Kafka 3.x.

## Limits
- Covers **retries of the same send** within a producer session. If the application crashes and resends the same business event with a new producer, it gets a new PID, so the duplicate is not detected.
- Does not cover duplicates created by your own code calling `send()` twice.
- Per partition, not across partitions or topics. For atomic writes across partitions, use **transactions** (`transactional.id`).

## Consumers still need idempotency
Because duplicates can still arise from application-level resends or consumer reprocessing, consumers should deduplicate with a business key (e.g. payment id with a unique constraint).

## Summary
An idempotent producer uses a producer ID and per-partition sequence numbers so brokers discard duplicate retries, giving exactly-once, in-order writes per partition; it is on by default in modern Kafka.
MD,

'How can retries create duplicates?' => <<<'MD'
## The scenario
```text
1. Producer sends record R (orderId=42) to the leader
2. Leader writes R at offset 100 and replicates it
3. The acknowledgement is lost or the request times out (request.timeout.ms)
4. Producer assumes failure and retries
5. Leader writes R again at offset 101
→ Two identical records; consumers process order 42 twice
```
The producer cannot distinguish "the request never arrived" from "it arrived but the answer was lost".

## Other sources of duplicates
- **Leader failover:** the old leader acknowledged nothing, the producer retries on the new leader, but the old leader had in fact replicated it.
- **Application retries:** the service catches an exception and calls `send()` again with a new producer instance.
- **Consumer side:** a consumer processes records, crashes before committing offsets, and reprocesses them after restart (at-least-once delivery).
- **Upstream retries:** an HTTP client retries a "place order" call, and your service publishes the event twice.

## Ordering problems too
Without idempotence, with `max.in.flight.requests.per.connection > 1`, batch 1 can fail while batch 2 succeeds; batch 1's retry is then written after batch 2, reversing order.

## Prevention
1. **Idempotent producer** (`enable.idempotence=true`, default in 3.x) removes broker-level duplicates from retries and keeps order.
2. **Transactions** for read-process-write pipelines.
3. **Idempotent consumers:** store processed event ids or use unique constraints:
```sql
INSERT INTO processed_events (event_id) VALUES (:id)   -- PRIMARY KEY; duplicate → skip
```
4. **Idempotency keys at the API layer** so a retried HTTP call does not create a second event.

## Summary
Retries duplicate records when a write succeeded but its acknowledgement was lost; idempotent producers fix this at the broker, and idempotent consumers handle the remaining cases.
MD,

'Why use a message key?' => <<<'MD'
## What the key does
Each record has an optional key. The default partitioner hashes it (`murmur2(key) % partitions`), so **all records with the same key go to the same partition**.

## Reasons to use a key
1. **Ordering per entity:** events for account `ACC-42` (debit, credit, freeze) are consumed in the order they were produced, because one partition is ordered and read by one consumer.
2. **Locality of processing and state:** a consumer handling a customer's partition sees all of that customer's events, which is essential for stateful stream processing (running balance, session windows, joins).
3. **Log compaction:** compacted topics keep the latest record per key; without a key compaction is impossible.
4. **Even distribution:** a high-cardinality key (user id, order id) spreads load well.

```java
producer.send(new ProducerRecord<>("account-events", accountId, eventJson));
```

## Choosing a key
| Need | Key |
|---|---|
| order of a customer's actions | customerId |
| order of an account's transactions | accountNumber |
| latest state of a product | productId |
| no ordering need | null (sticky partitioning) |

## Pitfalls
- **Hot keys:** one huge customer (or a default value like `"unknown"`) sends most traffic to one partition, overloading one consumer.
- **Changing the partition count** remaps keys, breaking ordering during the transition.
- Keys should be stable identifiers, not timestamps or random values (random keys spread load but give no ordering benefit).
- Keys are visible in plain form; avoid sensitive data such as PAN or Aadhaar numbers.

## Summary
A key routes all related records to one partition, giving per-key ordering, consistent stateful processing and enabling compaction; choose a stable, high-cardinality business id.
MD,

'How do you preserve per-customer ordering?' => <<<'MD'
## The recipe
1. **Use the customer id as the record key.** Same key, same partition, and a partition is strictly ordered.
2. **Keep producer ordering safe on retries:** `enable.idempotence=true` (default) with `max.in.flight.requests.per.connection ≤ 5`. Without idempotence, set in-flight to 1.
3. **Process each partition sequentially** in the consumer (the default for one consumer thread per partition). If you parallelise inside a consumer, parallelise by key, not randomly.
4. **Do not change the partition count** of the topic without a migration plan.
5. **Produce from a single place in order:** if two services emit events for the same customer, their relative order is not guaranteed.

```java
producer.send(new ProducerRecord<>("customer-events", customerId, event));
```
```java
// Parallel processing that still keeps per-key order (Confluent Parallel Consumer)
ParallelConsumerOptions<String, String> options = ParallelConsumerOptions.<String, String>builder()
    .ordering(ProcessingOrder.KEY)
    .maxConcurrency(50)
    .consumer(kafkaConsumer)
    .build();
```

## Retries and dead letters
If processing a customer's event fails and you send it to a retry topic while continuing with later events, the order breaks. Options:
- Retry in place (blocking) for transient errors.
- When parking an event, also park subsequent events of that customer until it is resolved.
- Use version numbers so consumers can ignore stale updates (`if event.version <= stored.version skip`).

## Across topics
Ordering is per partition, so events for one customer in `orders` and `payments` topics have no guaranteed relative order. Put related events in one topic with a type field when strict cross-type order matters.

## Summary
Key by customer id, keep the idempotent producer, process each partition (or key) sequentially, avoid repartitioning, and handle retries without letting later events overtake earlier ones.
MD,

'Does Kafka guarantee global topic ordering?' => <<<'MD'
## Answer: no
Kafka guarantees order **only within a partition**. A topic with several partitions has no total order: partitions are written and read in parallel, possibly by different consumers on different machines.

```text
Produced:  A(p0)  B(p1)  C(p0)  D(p1)
Consumer may process: A, B, D, C  or  B, A, C, D ...
Guaranteed only: A before C, and B before D
```

## Getting global order (and its cost)
- **Single-partition topic:** total order, but throughput is limited to one partition and only **one consumer** in a group can work. Acceptable for low-volume streams (configuration changes, a small audit trail).
- **Sequence numbers:** add a global sequence or timestamp and reorder downstream in a buffer, which adds latency and complexity.

## What most systems actually need
Usually you need order **per entity** (per account, per order, per user), not globally. Use the entity id as the key: you get correct order where it matters plus parallelism across entities.

## Timestamps are not an ordering guarantee
Record timestamps come from producers' clocks (`CreateTime`) or brokers (`LogAppendTime`); clocks differ across machines, so sorting by timestamp across partitions is approximate.

## Summary
Kafka orders records only within each partition; global order requires one partition (no parallelism), so design for per-key ordering instead.
MD,

'What is an offset commit?' => <<<'MD'
## What it is
An offset commit records, for a consumer group, **the position of the next record to read** in each partition. Kafka stores commits in the internal compacted topic `__consumer_offsets`. After a restart or rebalance, the consumer that gets the partition resumes from the committed offset.

```text
Partition 0: ... 97 98 99 [100] 101 ...
Processed up to 99 → commit offset 100 (the NEXT record)
```

## How commits are made
- **Automatic:** `enable.auto.commit=true` commits the latest polled offsets every `auto.commit.interval.ms` (5 s), during `poll()`.
- **Manual synchronous:** `commitSync()` blocks until the broker confirms, and retries.
- **Manual asynchronous:** `commitAsync(callback)` does not block; no automatic retry (a later commit supersedes it).
- **Specific offsets:** commit per partition with `Map<TopicPartition, OffsetAndMetadata>`.
- **In a transaction:** `producer.sendOffsetsToTransaction(...)` commits offsets atomically with produced records (exactly-once).

## Commit timing defines the delivery guarantee
| Commit | If the consumer crashes | Guarantee |
|---|---|---|
| after processing | reprocess some records | at-least-once |
| before processing | skip unprocessed records | at-most-once |
| atomically with outputs | neither | exactly-once (within Kafka) |

## Practical pattern
```java
while (running) {
    ConsumerRecords<String, Payment> records = consumer.poll(Duration.ofMillis(500));
    for (ConsumerRecord<String, Payment> r : records) handle(r.value());
    consumer.commitAsync();                 // fast path
}
consumer.commitSync();                      // final, reliable commit on shutdown
```

## Retention of commits
Committed offsets of an inactive group expire after `offsets.retention.minutes` (7 days); then `auto.offset.reset` decides where it starts.

## Summary
An offset commit saves a group's progress (next offset to read) per partition in `__consumer_offsets`; when you commit relative to processing determines at-least-once or at-most-once behaviour.
MD,

'What is auto commit?' => <<<'MD'
## What it is
With `enable.auto.commit=true` (the default for the plain Java consumer), the consumer **commits offsets periodically** in the background of `poll()`: every `auto.commit.interval.ms` (default 5000 ms) it commits the offsets of the records returned by the **previous** poll.

```properties
enable.auto.commit=true
auto.commit.interval.ms=5000
```

## Behaviour
Because commits happen inside `poll()` for records already returned, a typical loop that fully processes each batch before polling again gives **at-least-once** delivery:
```java
while (true) {
    ConsumerRecords<String, String> recs = consumer.poll(Duration.ofMillis(500));   // commits previous batch
    recs.forEach(this::process);
}
```

## Where it goes wrong
1. **Asynchronous processing:** if you hand records to another thread and poll again, the offsets are committed even though processing has not finished. A crash then **loses** those records.
2. **Crash between commits:** up to 5 seconds of processed records are reprocessed after a restart (duplicates).
3. **Rebalances** in the middle of an interval also cause reprocessing.
4. Little control: you cannot commit only after a database transaction succeeds.

## When it is fine
- Simple, idempotent processing in the polling thread (writing to a store with upserts).
- Analytics or logging where occasional duplicates are acceptable.

## Spring Kafka note
Spring Kafka sets `enable.auto.commit=false` by default and commits itself according to `AckMode` (`BATCH` by default, after the listener returns), which is safer.

## Summary
Auto commit periodically commits polled offsets during `poll()`; it is convenient and at-least-once for synchronous processing, but can lose or duplicate records with async processing or crashes.
MD,

'What is manual commit?' => <<<'MD'
## What it is
With `enable.auto.commit=false`, **your code decides when offsets are committed**, typically after records are fully processed (written to the database, API called). This gives precise control over delivery guarantees.

## Ways to commit
```java
consumer.commitSync();                       // all offsets returned by the last poll, blocking, retries
consumer.commitAsync((offsets, ex) -> {      // non-blocking
    if (ex != null) log.warn("commit failed", ex);
});

// Commit exactly one partition/offset after processing that record
consumer.commitSync(Map.of(
    new TopicPartition(r.topic(), r.partition()),
    new OffsetAndMetadata(r.offset() + 1)));  // +1: next record to read
```

## Typical at-least-once pattern
```java
try {
    while (running) {
        ConsumerRecords<String, Order> records = consumer.poll(Duration.ofMillis(500));
        for (ConsumerRecord<String, Order> r : records) {
            orderService.save(r.value());        // idempotent upsert
        }
        consumer.commitAsync();
    }
} finally {
    try { consumer.commitSync(); } finally { consumer.close(); }
}
```

## Spring Kafka
```yaml
spring.kafka.listener.ack-mode: manual_immediate
```
```java
@KafkaListener(topics = "orders", groupId = "fulfilment")
public void listen(Order order, Acknowledgment ack) {
    fulfilment.process(order);
    ack.acknowledge();     // commit after success
}
```
Other modes: `RECORD` (after each record), `BATCH` (after each poll, default), `MANUAL`.

## Trade-offs
- Committing after every record is safest but slower (one request per record); committing per batch is faster but reprocesses up to a batch after a crash.
- Commit in a `ConsumerRebalanceListener.onPartitionsRevoked` to avoid reprocessing on rebalances.

## Summary
Manual commit means committing offsets yourself after successful processing, typically per batch with `commitAsync` plus a final `commitSync`, giving reliable at-least-once delivery.
MD,

'What does enable.auto.commit=false do?' => <<<'MD'
## Effect
It **turns off periodic automatic offset commits**. The consumer then commits only when your code calls `commitSync()`/`commitAsync()` (or the framework commits for you, as Spring Kafka does), so offsets move forward only after you decide processing succeeded.

## Why use it
- Commit only after the database write or downstream call completes, preventing lost records.
- Commit specific offsets per partition, e.g. after each record in critical flows.
- Combine with transactions: offsets are committed through the producer (`sendOffsetsToTransaction`) for exactly-once read-process-write.

## What happens if you never commit
- The group's offset stays where it was. After a restart or rebalance, the consumer restarts from the last committed position and **reprocesses** everything since.
- With no commit at all, a new group starts from `auto.offset.reset` (latest by default) every time, so it may skip records entirely.

## Example
```java
props.put(ConsumerConfig.ENABLE_AUTO_COMMIT_CONFIG, false);
props.put(ConsumerConfig.AUTO_OFFSET_RESET_CONFIG, "earliest");

while (running) {
    var records = consumer.poll(Duration.ofSeconds(1));
    try {
        db.inTransaction(() -> records.forEach(this::apply));
        consumer.commitSync();
    } catch (Exception e) {
        // do not commit: seek back so the batch is retried
        records.partitions().forEach(tp -> consumer.seek(tp, records.records(tp).get(0).offset()));
    }
}
```

## Storing offsets yourself
Some systems store the offset in the same database transaction as the results (e.g. a `consumer_offsets` table) and `seek()` to it on startup, which gives effectively exactly-once processing into that database.

## Summary
`enable.auto.commit=false` hands offset commits to your code so progress is recorded only after successful processing; you must then commit explicitly or records will be reprocessed.
MD,

'What if a consumer crashes before committing?' => <<<'MD'
## What happens
1. The consumer stops sending heartbeats; after `session.timeout.ms` (45 s default) the group coordinator removes it (or immediately if it closed cleanly).
2. A **rebalance** assigns its partitions to other consumers.
3. The new owner starts from the **last committed offset**.
4. Records that were processed after that commit are **processed again**.

```text
Committed offset: 500
Processed 500–549, crashed before committing 550
New consumer reads from 500 → 500–549 processed twice
```
No records are lost (they are still in the log), but there are **duplicates**: this is at-least-once delivery.

## Consequences
- Duplicate emails or SMS, double inventory reservations, or double payment attempts if the consumer is not idempotent.
- The processing time of the reprocessed range adds lag.

## Making reprocessing safe
1. **Idempotent processing:** upserts, unique constraints, "processed event id" tables:
```sql
INSERT INTO payments (payment_id, amount, status) VALUES (?, ?, 'CAPTURED')
ON DUPLICATE KEY UPDATE status = status;
```
2. **Commit more often** (per record or small batches) to shrink the reprocessing window, at a performance cost.
3. **Transactions** for Kafka-to-Kafka pipelines: output records and offset commit are atomic, so a crash rolls both back.
4. **Store offsets with results** in the same database transaction.
5. **Idempotency keys** when calling external APIs (payment gateways accept them).

## Summary
After a crash, the partition's new owner resumes from the last committed offset, so records processed after that commit are redelivered; design consumers to be idempotent.
MD,

'What is at-least-once delivery?' => <<<'MD'
## Definition
Every record is processed **one or more times**: none are lost, but duplicates are possible. It is Kafka's most common and practical guarantee.

## How it is achieved
- **Producer:** `acks=all` with retries, so records are not lost (retries can duplicate, unless idempotence is on).
- **Consumer:** commit offsets **after** processing. A crash after processing but before commit causes reprocessing.

```java
for (ConsumerRecord<String, Event> r : consumer.poll(timeout)) {
    process(r);            // 1. do the work
}
consumer.commitSync();     // 2. then record progress
```

## Where duplicates come from
- Consumer crash or rebalance before commit.
- Producer retries without idempotence.
- Commit failures.

## Handling duplicates
- **Idempotent writes:** `INSERT ... ON CONFLICT DO NOTHING`, upsert by business id.
- **Deduplication table** keyed by event id with a retention period.
- **Natural idempotency:** "set status to SHIPPED" is safe to repeat; "add ₹100 to balance" is not, so model it as "apply transaction T123 once".
- **Version checks:** ignore events with a version older than the stored one.

## Why it is preferred
Losing a payment or order event is usually far worse than processing it twice and ignoring the duplicate. At-least-once plus idempotent consumers achieves "effectively exactly once" outcomes in databases and external systems, where Kafka transactions cannot reach.

## Summary
At-least-once means no loss but possible duplicates, achieved by committing after processing; pair it with idempotent consumers for correct results.
MD,

'What is at-most-once delivery?' => <<<'MD'
## Definition
Every record is processed **zero or one time**: never duplicated, but some may be lost.

## How it happens
- **Consumer:** commit offsets **before** processing. If it crashes after committing but before finishing, those records are skipped.
```java
ConsumerRecords<String, Metric> recs = consumer.poll(timeout);
consumer.commitSync();        // progress recorded first
recs.forEach(this::process);  // crash here → records never processed
```
- **Producer:** `acks=0`, or no retries, so failed sends are not repeated.
- **Auto commit with asynchronous processing:** offsets get committed while work is still pending.

## When it is acceptable
- High-volume telemetry, metrics or debug logs where a gap is harmless and duplicates would distort counts.
- Real-time location or price ticks where only the latest value matters and stale data is useless.
- Notifications where sending twice is worse than not sending (rare; usually you want at-least-once with dedupe).

## When it is wrong
Payments, orders, ledger entries, job applications, audit trails: any loss is a business or compliance problem.

## Comparison
| Guarantee | Loss | Duplicates | Commit timing |
|---|---|---|---|
| at-most-once | possible | no | before processing |
| at-least-once | no | possible | after processing |
| exactly-once | no | no | atomic with output (transactions) |

## Summary
At-most-once commits before processing (or never retries), so records may be lost but never duplicated; use it only for disposable data.
MD,

'What is exactly-once processing?' => <<<'MD'
## Definition
Each record's effect is applied **exactly once**, even with failures and retries. In Kafka this is called **EOS (exactly-once semantics)** and applies to **read-process-write pipelines inside Kafka**: consume from topics, process, produce to topics.

## Building blocks
1. **Idempotent producer:** no duplicates from retries (PID + sequence numbers).
2. **Transactions:** a producer with a `transactional.id` writes to several partitions and commits consumer offsets **atomically**:
```java
producer.initTransactions();
while (running) {
    ConsumerRecords<String, Order> records = consumer.poll(Duration.ofMillis(500));
    producer.beginTransaction();
    try {
        for (var r : records) producer.send(new ProducerRecord<>("invoices", r.key(), toInvoice(r.value())));
        producer.sendOffsetsToTransaction(currentOffsets(records), consumer.groupMetadata());
        producer.commitTransaction();
    } catch (ProducerFencedException e) {
        producer.close(); throw e;               // another instance took over
    } catch (KafkaException e) {
        producer.abortTransaction();             // outputs and offsets both rolled back
    }
}
```
3. **`isolation.level=read_committed`** on downstream consumers so they never see aborted records.

Kafka Streams enables all of this with one setting: `processing.guarantee=exactly_once_v2`.

## The boundary
EOS covers Kafka-to-Kafka. When the output is a database, email or payment API, Kafka transactions cannot roll those back. Then use:
- at-least-once delivery + **idempotent sinks** (upsert by key, unique event id),
- storing offsets in the same database transaction as the results,
- the **outbox pattern** for producing events from database changes,
- idempotency keys for external APIs.

## Cost
Transactions add latency (commit markers, coordinator round trips) and some throughput overhead; consumers with `read_committed` wait until transactions complete.

## Summary
Exactly-once in Kafka combines idempotent producers, transactions that commit outputs and offsets atomically, and `read_committed` consumers; outside Kafka you achieve it with idempotent sinks.
MD,

'What is consumer lag?' => <<<'MD'
## Definition
Consumer lag is **how far behind a consumer group is** in a partition:
```text
lag = log end offset (latest record) − committed offset of the group
```
Total lag for a topic is the sum over its partitions. Lag can also be measured in time ("the oldest unprocessed record is 4 minutes old"), which is often more meaningful to the business.

## Checking lag
```bash
kafka-consumer-groups.sh --bootstrap-server localhost:9092 --describe --group payment-service
# TOPIC      PARTITION  CURRENT-OFFSET  LOG-END-OFFSET  LAG
# payments   0          1_204_550       1_204_600       50
# payments   1          1_198_020       1_260_020       62000   ← problem
```
Tools: Burrow, Kafka Exporter + Prometheus/Grafana, Confluent Control Center, Kafka UI, consumer metrics `records-lag-max`.

## Why it matters
- Business latency: an order event lagging 10 minutes means a confirmation email 10 minutes late, or fraud checks happening after the money moved.
- Data loss risk: if lag exceeds retention, unconsumed records are deleted.
- Growing lag means consumers cannot keep up with producers.

## Reading lag correctly
- Small, stable lag is normal (records arrive continuously).
- Lag concentrated in one partition indicates a hot key or a stuck consumer.
- Steadily increasing lag across all partitions means throughput is insufficient.

## Alerting
Alert on **sustained growth** or on time lag above an SLO (e.g. > 60 s for payments), not on a fixed number of messages, because volumes vary.

## Summary
Consumer lag is the gap between the newest offset and the group's committed offset; monitor it per partition and in time to catch slow or stuck consumers.
MD,

'What causes high lag?' => <<<'MD'
## Common causes
1. **Slow processing per record:** synchronous calls to slow databases or external APIs (payment gateway, SMS provider), heavy JSON parsing, no batching of database writes.
2. **Not enough parallelism:** fewer consumers than partitions, or too few partitions for the load.
3. **Traffic spikes:** a sale, a batch job or a replay suddenly produces far more records.
4. **Hot partitions:** one key (or a bad partitioner) concentrates most records in one partition handled by one consumer.
5. **Frequent rebalances:** consumers exceeding `max.poll.interval.ms` are kicked out, partitions move, work restarts; repeated rebalances can stop progress.
6. **Errors and retries:** a poison message retried forever blocks the partition.
7. **Downstream outages:** the database is down or throttling, so the consumer waits.
8. **Resource limits:** CPU throttling in containers, long GC pauses, network bandwidth, too small `fetch.max.bytes`.
9. **Broker problems:** under-replicated partitions, slow disks, or leaders concentrated on one broker.

## Diagnosing
- Is lag on **all** partitions (capacity issue) or **one** (hot key or stuck consumer)?
- Is the group stable? Check rebalance logs and `max.poll.interval.ms` errors.
- Measure processing time per record and per external call (tracing, metrics).
- Compare producer rate with consumer rate.
- Check the consumer's error logs for repeated failures on the same offset.

## Summary
High lag comes from slow processing, too little parallelism, spikes, hot partitions, rebalances, poison messages, downstream outages or resource limits; find out whether it is global or per-partition first.
MD,

'How do you reduce lag?' => <<<'MD'
## Scale out
- **Add consumers** to the group, up to the number of partitions.
- **Add partitions** if consumers are already equal to partitions (plan for key remapping).
- **Process in parallel inside a consumer** while keeping per-key order (Confluent Parallel Consumer, or a thread per key hash).

## Make each record cheaper
- **Batch downstream writes:** insert 500 rows in one statement instead of 500 statements.
```java
@KafkaListener(topics = "clicks", batch = "true")
public void onBatch(List<ClickEvent> events) { clickRepository.saveAllBatch(events); }
```
- Cache reference data instead of querying the database per record.
- Use async, non-blocking clients for external APIs, with bounded concurrency.
- Use efficient serialisation (Avro/Protobuf) instead of large JSON.

## Tune the consumer
```properties
max.poll.records=500          # more records per poll if processing is fast
fetch.min.bytes=65536         # fewer, larger fetches
fetch.max.wait.ms=100
max.partition.fetch.bytes=4194304
```
Keep `max.poll.interval.ms` above the worst-case batch processing time to avoid rebalances, and use the cooperative sticky assignor so rebalances do not stop everyone.

## Handle failures without blocking
Send poison messages to a **retry or dead-letter topic** after a few attempts instead of retrying forever.

## Fix hot partitions
Better keys (add a sub-key or salt), or separate topics for very large tenants.

## Prioritise
Separate urgent and bulk traffic into different topics with different consumer groups so a bulk backlog does not delay critical events.

## Summary
Reduce lag by adding parallelism (consumers, partitions, in-consumer concurrency), making processing cheaper (batching, caching, async I/O), tuning fetch and poll settings, and moving failures out of the main path.
MD,

'What is a rebalance?' => <<<'MD'
## What it is
A rebalance is the process of **redistributing partitions among the consumers of a group**. It is coordinated by the group coordinator (a broker) and the group leader (one consumer, in the classic protocol).

## Triggers
- A consumer joins the group (scale-out, deployment).
- A consumer leaves (shutdown, crash, or missing heartbeats for `session.timeout.ms`).
- A consumer exceeds `max.poll.interval.ms` without calling `poll()` and is considered failed.
- Partitions are added to a subscribed topic, or a new topic matches a subscription pattern.

## Eager (classic) rebalance
1. All consumers stop and **revoke all** partitions ("stop the world").
2. They rejoin; the leader computes a new assignment.
3. Everyone gets new partitions and resumes from committed offsets.
During this time the whole group processes nothing; with many consumers this can take seconds to minutes.

## Problems caused by rebalances
- Processing pauses and growing lag.
- Duplicates: work done after the last commit is redone by the new owner.
- **Rebalance storms** during rolling deployments, or when processing is slow and consumers keep timing out.

## Reducing their impact
- **Cooperative sticky assignor** (incremental rebalancing): only moved partitions are revoked.
- **Static membership:** `group.instance.id` per consumer, so a restart within `session.timeout.ms` does not trigger a rebalance (great for Kubernetes pods).
- Commit offsets in `onPartitionsRevoked`.
- Keep processing per poll short (`max.poll.records`, `max.poll.interval.ms`).
- Kafka 4.0's new consumer group protocol (KIP-848) moves assignment to the broker and removes the global synchronisation barrier.

```java
consumer.subscribe(List.of("orders"), new ConsumerRebalanceListener() {
    public void onPartitionsRevoked(Collection<TopicPartition> parts) { consumer.commitSync(currentOffsets); }
    public void onPartitionsAssigned(Collection<TopicPartition> parts) { /* load state if needed */ }
});
```

## Summary
A rebalance reassigns partitions when group membership or subscriptions change; it pauses processing, so use cooperative assignment, static membership and quick polling to minimise it.
MD,

'What is cooperative rebalancing?' => <<<'MD'
## What it is
Cooperative (incremental) rebalancing, introduced in Kafka 2.4 (KIP-429), lets consumers **keep the partitions they already own** during a rebalance. Only partitions that actually need to move are revoked, and they are moved in a second round.

## Eager versus cooperative
| | Eager | Cooperative |
|---|---|---|
| On rebalance | every consumer revokes all partitions | only moved partitions are revoked |
| Processing during rebalance | stops for the whole group | continues for unaffected partitions |
| Rounds | one | usually two (revoke, then assign) |
| Assignor | Range, RoundRobin, Sticky | `CooperativeStickyAssignor` |

## Example
Group with consumers A and B, 6 partitions; consumer C joins.
- **Eager:** A and B drop all 6; everyone pauses; partitions are reassigned 2/2/2.
- **Cooperative:** A gives up one partition, B gives up one; they keep processing their other partitions; C then receives the two freed partitions.

## Enabling it
```properties
partition.assignment.strategy=org.apache.kafka.clients.consumer.CooperativeStickyAssignor
```
To migrate a running group from eager without downtime, do two rolling restarts: first list both `CooperativeStickyAssignor` and the old assignor, then remove the old one.

Spring Boot:
```yaml
spring.kafka.consumer.properties.partition.assignment.strategy: org.apache.kafka.clients.consumer.CooperativeStickyAssignor
```

## Kafka Streams and Kafka 4.0
Kafka Streams uses cooperative rebalancing by default. Kafka 4.0's new consumer rebalance protocol (KIP-848, `group.protocol=consumer`) goes further: the broker computes assignments incrementally and consumers do not need to synchronise at all.

## Summary
Cooperative rebalancing revokes only the partitions that move, so most of the group keeps processing during scaling and deployments; enable it with the CooperativeStickyAssignor.
MD,

'What does max.poll.interval.ms control?' => <<<'MD'
## What it controls
`max.poll.interval.ms` (default **300000 ms = 5 minutes**) is the **maximum time allowed between two `poll()` calls**. If the consumer does not call `poll()` within this time, it is considered stuck: it leaves the group, a rebalance happens and its partitions go to other consumers.

This is separate from heartbeats: a background thread sends heartbeats (so the consumer looks alive), but `max.poll.interval.ms` detects a consumer whose processing loop is stuck or too slow.

## Typical failure
```text
poll() returns 500 records
Each record calls a slow API: 1 s each → 500 s > 300 s
→ consumer kicked out, rebalance, another consumer reprocesses the same records
→ it also takes 500 s → endless rebalance loop, lag grows
```
Log message: "consumer poll timeout has expired... the poll loop is spending too much time processing messages".

## Fixes
- Lower `max.poll.records` so each batch finishes in time:
```properties
max.poll.records=50
max.poll.interval.ms=300000
```
- Increase `max.poll.interval.ms` if processing legitimately takes longer (e.g. 15 minutes for heavy batch jobs), knowing that a truly stuck consumer will be detected later.
- Speed up processing (batch writes, async calls with bounded concurrency).
- For very long tasks, `pause()` the partitions, process in another thread while continuing to call `poll()` (which returns nothing for paused partitions), then `resume()`.

## Related settings
- `session.timeout.ms`: liveness via heartbeats (process crash, network).
- `heartbeat.interval.ms`: how often heartbeats are sent.

## Summary
`max.poll.interval.ms` is the maximum gap between polls before a consumer is treated as failed; keep batch processing time well below it by tuning `max.poll.records` or processing speed.
MD,

];
