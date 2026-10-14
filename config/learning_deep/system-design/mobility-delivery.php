<?php

// System Design › Ride hailing, food delivery and quick commerce case studies.
return [

'Design a Ride-Hailing App (Uber / Ola)' => <<<'MD'
## Functional requirements
- Rider: enter pickup and drop, see fare estimate and ETA, request a ride, track the driver live, pay, rate.
- Driver: go online/offline, stream location, receive ride offers, accept, navigate, complete trip.
- Pricing: base fare + distance + time, surge pricing by area and demand.
- Payments, trip history, safety features (SOS, share trip).

## Non-functional requirements
- Location updates: every active driver sends GPS every 4–5 s. 10 lakh online drivers → ~2.5 lakh writes/s.
- Matching must finish in seconds; a ride is assigned to exactly one driver.
- High availability in each city; a city outage should not affect others.

## Architecture
```text
Driver app ──GPS every 4 s──▶ Location Service ──▶ Geo index (Redis GEO / H3 cells, in memory)
Rider app ──▶ API gateway ──▶ Trip Service ──▶ Matching/Dispatch Service ──▶ offer to drivers (push/WebSocket)
                           ──▶ Pricing Service (fare estimate, surge per H3 cell)
                           ──▶ ETA/Routing Service (map graph, traffic)
Trip events ──▶ Kafka ──▶ Payments · Notifications · Analytics · Fraud
```

## Location indexing
- Divide the map into cells (Uber's H3 hexagons or geohash). Each driver's latest position is stored in its cell in memory (Redis GEO or a custom in-memory index), with a TTL so stale drivers disappear.
- Finding nearby drivers = look up the rider's cell plus neighbouring rings, filter by status (available) and vehicle type.
- Writes are high volume but only the latest point matters, so they go to memory first; the full trail is streamed to Kafka and stored for trip replay and billing.

## Matching flow
1. Rider requests a ride; Trip Service creates the trip (REQUESTED) and asks Dispatch for candidates.
2. Dispatch finds available drivers in nearby cells and ranks them by ETA (road distance, not straight line), rating and acceptance rate.
3. Offer sent to the best driver with a 10–15 s timeout. Acceptance uses an atomic update: `driver.status: AVAILABLE → RESERVED` and `trip.driver_id IS NULL → driver_id`. This guarantees one driver per trip and one trip per driver.
4. On timeout or decline, offer the next driver; widen the search radius over time.
5. Trip moves through ACCEPTED → ARRIVED → STARTED → COMPLETED; fare is calculated from the actual GPS trail.

## Surge pricing
- Every minute, per cell: demand (open requests) ÷ supply (available drivers) → multiplier, smoothed so prices do not jump wildly, capped by policy.
- The quoted fare is locked for the rider for a few minutes (stored with the estimate id).

## Scaling
- Partition by city/region: each region has its own dispatch and location shards (geo-sharding).
- Live tracking: riders subscribe to their driver's location channel over WebSocket; only one subscriber per trip, so fan-out is small.
- Stateless services behind load balancers; in-memory geo index replicated for failover.

## Interview follow-ups
- *Why not store locations in SQL?* 2.5 lakh writes/s of short-lived data; an in-memory geo index is far cheaper, and history goes to a log/stream store.
- *How do you stop two riders getting the same driver?* Atomic compare-and-set on the driver's status (Redis Lua script or DB row lock).
- *How is ETA computed?* Road-graph shortest path (contraction hierarchies) with live traffic speeds, cached per cell pair.
MD,

'Design a Food Delivery App (Zomato / Swiggy)' => <<<'MD'
## Functional requirements
- Discover restaurants by location, cuisine, rating and delivery time; menus with prices and availability.
- Cart, checkout, payment, order placement.
- Restaurant app: accept order, mark preparing/ready; delivery partner assignment and live tracking.
- Ratings, reviews, offers, order history, refunds.

## Non-functional requirements
- Lunch and dinner peaks (5–10× normal), big cricket-match nights.
- Search and listing latency < 200 ms; order placement must be reliable even under peak.
- Delivery estimates should be accurate (customer trust).

## Architecture
```text
App ─▶ API gateway ─▶ Search/Listing Service ─▶ Elasticsearch (geo + filters) + Redis cache
                    ─▶ Menu Service ─▶ menu DB (+ cache)
                    ─▶ Cart & Order Service ─▶ Orders DB (sharded) ─▶ Payments
                    ─▶ Restaurant Service (order acceptance, prep time)
                    ─▶ Delivery/Dispatch Service ─▶ partner geo index (like ride-hailing)
                    ─▶ ETA Service (prep time model + travel time)
Order events ─▶ Kafka ─▶ Notifications · Tracking · Analytics · Offers
```

## Order lifecycle
`CREATED → PAYMENT_PENDING → PLACED → ACCEPTED (restaurant) → PREPARING → READY → PICKED_UP → DELIVERED` (or CANCELLED/REFUNDED). Each transition is an event; the state machine rejects invalid transitions.

## Placing an order
1. Validate the cart against the latest menu and prices (items can go out of stock).
2. Create the order (PAYMENT_PENDING) with an idempotency key.
3. Payment via gateway; on success the order becomes PLACED and is pushed to the restaurant tablet.
4. If the restaurant does not accept within N minutes, auto-cancel and refund (a saga with compensation steps).

## Dispatch (assigning a delivery partner)
- Start assignment so the partner arrives when the food is ready: dispatch time = ready time − partner travel time.
- Batch orders: one partner can carry two orders from the same restaurant or same route if delivery promises still hold.
- Matching uses the same geo-index ideas as ride-hailing, optimising total delay rather than the nearest partner only.

## Search and listing
- Restaurants indexed with geo-point, serviceable radius/polygon, cuisines, ratings, cost for two, open hours.
- Results ranked by relevance, rating, distance, delivery time and personalisation; the first page of results per area is cached for a minute at peak.

## Interview follow-ups
- *How do you keep delivery-time estimates accurate?* ML model on restaurant prep history, current load, time of day and live traffic; re-estimate at each stage.
- *What if payment succeeds but the order service crashes?* Payment webhooks plus reconciliation create or refund the order; idempotency keys prevent duplicates.
- *How do you handle menu changes during peak?* Menu cache with short TTL and versioning; price is re-validated at checkout.
MD,

'Design a Quick Commerce App (Blinkit / Zepto / Instamart)' => <<<'MD'
## What is different from food delivery
Delivery in 10–15 minutes is possible only because items come from small dark stores (micro-warehouses) within 2–3 km, and the hard problem is real-time inventory: the app must never sell an item that is not on the shelf of the store that will deliver it.

## Functional requirements
- Map the customer's location to the serving dark store; show only that store's in-stock items.
- Cart, checkout, payment; picking and packing in the store; rider assignment; live tracking.
- Inventory: receiving stock, cycle counts, replenishment from mother warehouses, expiry tracking.

## Non-functional requirements
- End-to-end 10 minutes: ~2 min picking/packing, ~1 min assignment, ~5–7 min riding.
- Inventory accuracy: overselling causes cancellations; underselling loses revenue.
- Peaks: evenings, weekends, festivals, rain (demand jumps and riders are fewer).

## Architecture
```text
App ─▶ Serviceability Service (location ─▶ dark store polygon)
    ─▶ Catalog Service (store-specific assortment + price) ─▶ cache
    ─▶ Inventory Service (per store, per SKU: on_hand, reserved) ─▶ Redis + DB
    ─▶ Order Service ─▶ Payments
    ─▶ Store Ops (picker app: optimised pick path, packing)
    ─▶ Rider Dispatch (riders waiting at the store, FIFO / batching)
Events ─▶ Kafka ─▶ Replenishment & demand forecasting · Analytics
```

## Inventory reservation (the core)
1. Available to sell = on_hand − reserved − safety buffer.
2. When the customer places the order, reserve each item atomically: `UPDATE inventory SET reserved = reserved + qty WHERE store_id = ? AND sku = ? AND on_hand - reserved >= qty` (or a Redis Lua script). If any item fails, release the others and tell the user.
3. Reservation has a timeout (e.g. 10 min) in case payment is abandoned.
4. When picked and dispatched, decrease on_hand and reserved together.

## Fast fulfilment
- Picking: SKUs stored by velocity (fast movers near the packing desk); the picker app gives an optimal route.
- Riders wait at the store, so assignment is a queue rather than a city-wide search; orders going the same direction can be batched.
- Demand forecasting per store per hour decides replenishment from the mother warehouse (often several times a day).

## Interview follow-ups
- *How do you show stock instantly to lakhs of users?* Cache availability per store/SKU, updated by inventory events; final truth is checked at order time by the reservation.
- *What if the shelf count is wrong?* Picker marks the item missing → partial refund or substitute; frequent cycle counts correct the count.
- *How is a location mapped to a store?* Point-in-polygon lookup on store service areas, with fallback to another store when one is overloaded or closed.
MD,

];
