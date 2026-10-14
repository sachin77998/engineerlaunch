<?php

// Apache Kafka interview questions 1–25, in depth.
return [

'What is Apache Kafka?' => <<<'MD'
## What it is
Apache Kafka is a **distributed event-streaming platform**. Applications write events (records) to it, Kafka stores them durably on disk in an ordered, replicated log, and any number of applications read them, at their own pace, now or later. It was built at LinkedIn in 2011 and is now used by banks, Uber, Swiggy, Zerodha, Netflix and most large backends.

Kafka combines three capabilities:
- **Publish and subscribe** to streams of records (like a message system).
- **Store** streams durably and fault-tolerantly for as long as configured (days, or forever).
- **Process** streams as they arrive (Kafka Streams, ksqlDB, Flink).

## Core vocabulary
| Term | Meaning |
|---|---|
| Record/event | key, value, timestamp, headers |
| Topic | named stream, e.g. `payments.completed` |
| Partition | ordered, append-only log; a topic has many |
| Offset | position of a record in a partition |
| Broker | a Kafka server storing partitions |
| Producer / Consumer | writes / reads records |
| Consumer group | consumers sharing the work of reading a topic |

## Why it is fast
- Sequential disk writes and reads (append-only log) instead of random I/O.
- Relies on the OS page cache and **zero-copy** (`sendfile`) to send data from disk to network.
- Batching and compression of records.
- Horizontal scaling through partitions spread over brokers.

## Example
```java
producer.send(new ProducerRecord<>("orders.placed", orderId, orderJson));
```
The inventory, payment, notification and analytics services each read `orders.placed` independently.

## Summary
Kafka is a durable, replicated, partitioned commit log that lets many systems publish and consume event streams at very high throughput with ordering per partition.
MD,

'What are Kafka’s main use cases?' => <<<'MD'
## Main use cases
1. **Event-driven microservices:** services publish domain events (`OrderPlaced`, `PaymentCaptured`) instead of calling each other synchronously. Producers do not need to know who consumes.
2. **Messaging/decoupling:** absorb traffic spikes (a sale on Flipkart) so downstream services process at their own rate.
3. **Activity tracking and clickstreams:** page views, searches, job applications sent to analytics in real time (the original LinkedIn use case).
4. **Log and metrics aggregation:** collect logs from thousands of servers into one pipeline (to Elasticsearch, S3).
5. **Change data capture (CDC):** Debezium reads MySQL/Postgres binlogs and publishes every row change, keeping search indexes, caches and data warehouses in sync.
6. **Stream processing:** fraud detection on card transactions, surge pricing in ride hailing, real-time dashboards (Kafka Streams, Flink).
7. **Data integration pipelines:** Kafka Connect moves data between databases, S3, Elasticsearch, Snowflake.
8. **Event sourcing and audit logs:** the log is the system of record of what happened; state can be rebuilt by replaying.
9. **IoT and telemetry:** vehicle GPS updates every few seconds (Uber/Ola driver locations).

## Example: a job portal
| Event | Consumers |
|---|---|
| `job.posted` | search indexer, alert emailer, recommendation engine |
| `application.submitted` | employer notifications, ATS scoring, analytics |
| `profile.updated` | search index, matching service |

## When Kafka is not the right tool
- Simple request/response where the caller needs an answer now (use REST/gRPC).
- Small apps with low volume (a database table plus a job queue is simpler).
- Complex per-message routing and priorities (RabbitMQ fits better).

## Summary
Kafka is used for decoupling services, real-time analytics, log aggregation, CDC, stream processing and data integration: anywhere many systems need the same stream of events reliably.
MD,

'What is a Kafka broker?' => <<<'MD'
## What it is
A broker is a single **Kafka server process**. It receives records from producers, writes them to partition log files on disk, serves reads to consumers, and replicates partitions to and from other brokers. Each broker has a unique `node.id` (`broker.id` in older versions).

## Responsibilities
- Hosting partitions: each partition replica is a directory of segment files (`00000000000000000000.log`, `.index`, `.timeindex`).
- Acting as **leader** for some partitions (handles all reads and writes for them) and **follower** for others (copies data from leaders).
- Handling client requests: produce, fetch, metadata, offset commits (the `__consumer_offsets` topic lives on brokers too).
- Enforcing retention: deleting or compacting old segments.

## Clients find brokers through bootstrap servers
```properties
bootstrap.servers=broker1:9092,broker2:9092,broker3:9092
```
The client connects to any one, fetches cluster **metadata** (which broker leads which partition), then talks directly to the right leaders. Listing a few brokers is enough; it does not need all of them.

## Sizing
A broker can handle thousands of partitions and hundreds of MB/s, limited by disk throughput, network and the page cache. Production clusters usually start with **at least three brokers** so replication factor 3 is possible and one broker can fail.

## Key broker settings
```properties
log.dirs=/data/kafka
num.partitions=6
default.replication.factor=3
min.insync.replicas=2
log.retention.hours=168
```

## Summary
A broker is one Kafka server that stores partition replicas, leads some partitions, follows others, and serves producers and consumers; a cluster is several brokers working together.
MD,

'What is a Kafka cluster?' => <<<'MD'
## What it is
A Kafka cluster is a group of brokers that together store and serve topics. Partitions of each topic are spread across brokers, and each partition is replicated to several brokers, so the cluster provides **scalability** (more brokers, more throughput and storage) and **fault tolerance** (a broker can fail without data loss).

## Components
- **Brokers:** store data and serve clients.
- **Controller:** one elected component that manages cluster metadata: which broker leads which partition, broker membership, topic creation and leader elections.
  - Older Kafka: metadata in **ZooKeeper**, one broker acts as controller.
  - Kafka 3.3+ / 4.0: **KRaft** mode, a quorum of controller nodes stores metadata in an internal Raft log; ZooKeeper is removed in 4.0.

## Example layout
Topic `payments` with 3 partitions and replication factor 3 on 3 brokers:
| Partition | Leader | Followers |
|---|---|---|
| P0 | Broker 1 | 2, 3 |
| P1 | Broker 2 | 3, 1 |
| P2 | Broker 3 | 1, 2 |
Leaders are spread so every broker shares the load. If Broker 2 dies, the controller makes Broker 3 or 1 the leader of P1.

## Scaling a cluster
Adding brokers does not move existing partitions automatically; use `kafka-reassign-partitions.sh` (or Cruise Control) to rebalance replicas onto new brokers.

## Multi-cluster setups
- One cluster per region or environment (dev, staging, prod).
- **MirrorMaker 2** or Confluent Cluster Linking copy topics between clusters for disaster recovery or geo-distribution.

## Summary
A cluster is a set of brokers plus a controller (ZooKeeper-based or KRaft) that spreads and replicates partitions for throughput and fault tolerance.
MD,

'What is a topic?' => <<<'MD'
## What it is
A topic is a **named, logical stream of records** of one kind, like a table name for events: `orders.placed`, `payments.completed`, `user.signup`. Producers write to a topic, consumers subscribe to it. Physically a topic is split into **partitions** stored on brokers.

## Properties
- **Append-only:** records are added at the end and never modified.
- **Multi-subscriber:** any number of consumer groups can read the same topic independently.
- **Durable:** records stay until retention removes them (time, size) or compaction keeps only the latest per key, regardless of whether they were consumed.

## Creating a topic
```bash
kafka-topics.sh --bootstrap-server localhost:9092 --create \
  --topic payments.completed --partitions 6 --replication-factor 3 \
  --config retention.ms=604800000 --config min.insync.replicas=2
```
```java
// Spring Kafka
@Bean
NewTopic paymentsTopic() {
    return TopicBuilder.name("payments.completed").partitions(6).replicas(3).build();
}
```

## Naming and design
- Use a convention: `<domain>.<entity>.<event>` such as `banking.transfer.completed`.
- One topic per event type (or per entity with a type header); avoid one giant topic for everything.
- Set **partitions** for parallelism, **replication factor** for durability, **retention** for how long data is needed.
- Disable `auto.create.topics.enable` in production so typos do not create topics.
- Define a schema (Avro, Protobuf, JSON Schema with a Schema Registry) so producers and consumers agree on the format.

## Summary
A topic is a named, durable, append-only stream of related events, split into partitions and readable by many independent consumer groups.
MD,

'What is a partition?' => <<<'MD'
## What it is
A partition is an **ordered, immutable, append-only log** that holds part of a topic's records. A topic with 6 partitions has six independent logs. Each record in a partition gets a sequential **offset** (0, 1, 2, …).

```text
Topic orders, partition 0:  [0][1][2][3][4][5] ← new records appended here
Topic orders, partition 1:  [0][1][2][3]
Topic orders, partition 2:  [0][1][2][3][4]
```

## How records choose a partition
- **With a key:** `partition = murmur2(key) % numPartitions`. Same key, same partition, so events for one customer or account stay in order.
- **Without a key:** the default partitioner uses a **sticky** strategy: it fills a batch for one partition, then moves to another, spreading load while keeping batches large.
- **Explicit partition** or a custom `Partitioner` can also be used.

## On disk
Each partition replica is a directory of **segments** (files of e.g. 1 GB). Only the newest segment is written; older segments are deleted or compacted according to retention. Index files map offsets and timestamps to file positions for fast lookups.

## Guarantees
- **Ordering** is guaranteed only within a partition, not across partitions.
- One partition is read by **at most one consumer in a group** at a time.

## Partitions and replicas
Each partition has a leader replica and follower replicas on other brokers. Clients read and write the leader (followers can serve reads with rack-aware fetching).

## Summary
A partition is the unit of ordering, storage and parallelism in Kafka: an append-only log of records with sequential offsets, chosen by hashing the record key.
MD,

'Why are partitions important?' => <<<'MD'
## Partitions are how Kafka scales
1. **Parallel consumption:** within a consumer group, each partition is assigned to one consumer. 12 partitions allow up to 12 consumers to work in parallel; a 13th would sit idle.
2. **Parallel writes and storage:** partitions are spread across brokers, so writes, disk space and network load are shared. A topic is not limited by one machine.
3. **Ordering by key:** records with the same key always go to the same partition, giving per-key order (all events of account `ACC-42` in sequence) while different keys proceed in parallel.
4. **Fault tolerance unit:** each partition is replicated independently and has its own leader, so leadership is spread over the cluster.
5. **Retention and compaction** happen per partition segment.

## Example
A payments topic receives 50,000 events per second. One consumer handles about 5,000 per second. You need at least 10 consumers, so at least 10 partitions; you might choose 24 to allow for growth.

## Trade-offs of too many partitions
- More open files and memory on brokers, more metadata.
- Longer leader elections and recovery after a broker failure (less of an issue with KRaft).
- Producers spread data thinner: smaller batches, more requests, higher latency.
- **Increasing** partitions later changes `hash(key) % n`, so keys move to different partitions and ordering for a key can break during the change. Partitions can never be decreased.

## Rule of thumb
`partitions ≥ max(target throughput / per-consumer throughput, target throughput / per-producer throughput)`, plus headroom. Many teams start with 6–12 for normal topics and measure.

## Summary
Partitions give Kafka horizontal scalability, parallelism and per-key ordering; choose the count from throughput needs and plan ahead because increasing it remaps keys.
MD,

'What is an offset?' => <<<'MD'
## What it is
An offset is a **64-bit sequential number** that identifies the position of a record within a partition. The first record in a partition has offset 0, the next 1, and so on. Offsets never change and are never reused, even after old records are deleted by retention.

```text
Partition 3:  offset 0 | 1 | 2 | 3 | 4 | 5 | 6 ...
                                  ↑ committed offset of group "billing" = 4
                                          ↑ log end offset (next write) = 7
```

## Two kinds of offsets that matter
- **Record offset:** where a record sits in the log.
- **Consumer (committed) offset:** for each consumer group and partition, the offset of the **next record to read**. Stored in the internal topic `__consumer_offsets`. If a consumer restarts, it resumes from the committed offset.

Other positions: **log-start offset** (oldest record still kept) and **log-end offset / high watermark** (the latest offset replicated to all in-sync replicas; consumers can read only up to the high watermark).

## Using offsets
```java
consumer.seek(new TopicPartition("payments", 3), 1500);   // replay from offset 1500
consumer.seekToBeginning(partitions);                     // reprocess everything kept
```
```bash
kafka-consumer-groups.sh --bootstrap-server localhost:9092 --group billing \
  --reset-offsets --to-datetime 2026-10-01T00:00:00.000 --topic payments --execute
```
Resetting offsets lets you **replay** events after fixing a bug, which a traditional queue cannot do.

## auto.offset.reset
When a group has no committed offset (new group) or the offset was deleted: `earliest` reads from the beginning, `latest` (default) reads only new records, `none` throws an error.

## Summary
An offset is a record's sequential position in a partition; consumer groups commit offsets to remember progress, which enables restarts and replay.
MD,

'Is an offset unique across a topic?' => <<<'MD'
## Answer: no
An offset is unique **only within a partition**. Every partition starts counting from 0 independently, so offset 100 exists in partition 0, partition 1, partition 2, and so on.

```text
orders-0: 0 1 2 3 ... 100 ...
orders-1: 0 1 2 3 ... 100 ...
orders-2: 0 1 2 3 ... 100 ...
```

## Uniquely identifying a record
A record is identified by the triple **(topic, partition, offset)**:
```java
for (ConsumerRecord<String, String> r : records) {
    String id = r.topic() + "-" + r.partition() + "-" + r.offset();   // e.g. orders-2-100
}
```
This triple is a good idempotency key for consumers that must not process the same record twice, as long as the topic is never recreated. For business-level deduplication prefer a **business id** in the payload (order id, transaction reference), because the same business event could be produced twice and land at two different offsets.

## Consequences
- Consumer progress is tracked **per partition**: a group commits one offset for each partition it reads.
- There is no single "position in the topic"; comparing offsets across partitions is meaningless. Ordering across partitions is not defined either.
- Lag is calculated per partition (`log-end offset − committed offset`) and summed for the topic.
- If a topic is deleted and recreated, offsets start again at 0.

## Summary
Offsets are sequential per partition, not per topic; use (topic, partition, offset) to identify a record, and a business id for deduplication.
MD,

'What do producers and consumers do?' => <<<'MD'
## Producers
A producer **writes records** to topics. It serialises key and value, chooses the partition (by key hash or the sticky partitioner), batches records per partition, optionally compresses them and sends them to the partition leader. It can wait for acknowledgements (`acks`) and retries on transient errors.

```java
Properties p = new Properties();
p.put("bootstrap.servers", "localhost:9092");
p.put("key.serializer", StringSerializer.class.getName());
p.put("value.serializer", StringSerializer.class.getName());
p.put("acks", "all");
p.put("enable.idempotence", "true");

try (KafkaProducer<String, String> producer = new KafkaProducer<>(p)) {
    producer.send(new ProducerRecord<>("payments", "ACC-42", "{\"amount\":500}"),
        (meta, ex) -> {
            if (ex != null) log.error("send failed", ex);
            else log.info("written to {}-{}@{}", meta.topic(), meta.partition(), meta.offset());
        });
}
```
`send` is asynchronous; the callback reports success or failure.

## Consumers
A consumer **reads records** by polling partitions it is assigned, deserialises them, processes them and commits offsets to record progress. Consumers **pull** data (Kafka does not push), so each reads at its own speed.

```java
p.put("group.id", "billing-service");
p.put("enable.auto.commit", "false");
try (KafkaConsumer<String, String> consumer = new KafkaConsumer<>(p)) {
    consumer.subscribe(List.of("payments"));
    while (true) {
        ConsumerRecords<String, String> records = consumer.poll(Duration.ofMillis(500));
        for (ConsumerRecord<String, String> r : records) process(r);
        consumer.commitSync();
    }
}
```

## Spring Kafka
```java
kafkaTemplate.send("payments", accountId, event);

@KafkaListener(topics = "payments", groupId = "billing-service")
public void onPayment(PaymentEvent event) { billing.record(event); }
```

## Decoupling
Producers do not know who consumes; consumers do not know who produced. New consumers can be added later and even replay history.

## Summary
Producers serialise, partition, batch and send records with acknowledgements; consumers poll assigned partitions, process records and commit offsets, each at their own pace.
MD,

'What is a consumer group?' => <<<'MD'
## What it is
A consumer group is a set of consumers sharing the same `group.id` that **cooperate to read a topic**. Kafka assigns each partition to exactly one consumer in the group, so the work is divided and each record is processed once by the group.

```text
Topic payments: P0 P1 P2 P3 P4 P5
Group billing-service (3 consumers):
  consumer A ← P0, P1
  consumer B ← P2, P3
  consumer C ← P4, P5
```

## Scaling rules
- Adding consumers (up to the number of partitions) increases parallelism.
- **More consumers than partitions:** extra consumers stay idle (useful only as hot standby).
- If a consumer dies or a new one joins, the group **rebalances** and partitions are reassigned.

## Group coordination
One broker acts as the **group coordinator**. Consumers send heartbeats to it; if heartbeats stop (`session.timeout.ms`) or a consumer does not call `poll` in time (`max.poll.interval.ms`), it is removed and its partitions go to others. The group's committed offsets are stored in `__consumer_offsets`.

## Groups as "subscribers"
Each group gets the full stream independently. A queue-style service uses one group with many members; pub/sub is achieved by having several groups.

## Inspecting a group
```bash
kafka-consumer-groups.sh --bootstrap-server localhost:9092 --describe --group billing-service
# TOPIC     PARTITION  CURRENT-OFFSET  LOG-END-OFFSET  LAG  CONSUMER-ID
# payments  0          15020           15030           10   consumer-A
```

## Summary
A consumer group is a team of consumers that split a topic's partitions between them, giving parallel, load-balanced processing with progress tracked per group.
MD,

'Can multiple consumer groups read one topic?' => <<<'MD'
## Answer: yes, and this is a core strength
Any number of consumer groups can read the same topic **independently**. Each group has its own committed offsets, so one group being slow, stopped or replaying history does not affect the others. Records are not removed when read.

```text
Topic orders.placed
  ├─ group inventory-service     (reserves stock)          offset 98,200
  ├─ group payment-service       (charges the customer)    offset 98,215
  ├─ group notification-service  (sends SMS/email)         offset 97,900
  └─ group analytics-loader      (loads to warehouse)      offset 40,000 (catching up)
```

## Why this matters
- **Fan-out without coupling:** the order service publishes once; adding a new consumer (e.g. fraud detection) needs no change in the producer.
- **Independent speed:** analytics can process in bulk every hour while payments react in milliseconds.
- **Replay per group:** a new team can start with `auto.offset.reset=earliest` and process the full retained history.
- **Different technologies:** a Java service, a Python ML job and a Kafka Connect sink can all read the same topic.

## Cost
Each group reads the data separately, so network egress from brokers grows with the number of groups. Popular data is usually served from the OS page cache, so disk reads are not repeated.

## Contrast with traditional queues
In RabbitMQ or SQS, a message is removed after one consumer acknowledges it. Delivering one message to several services needs a fan-out exchange or several queues. Kafka gets this naturally with groups.

## Summary
Yes: every consumer group gets the entire topic and tracks its own offsets, enabling fan-out, independent pacing and replay without affecting other groups.
MD,

'Can two consumers in one group read the same partition?' => <<<'MD'
## Answer: no (at the same time)
Within one consumer group, each partition is assigned to **exactly one** consumer at a time. Two consumers in the same group never read the same partition simultaneously.

## Why Kafka enforces this
1. **Ordering:** a partition is ordered; if two consumers processed it in parallel, record 11 could finish before record 10.
2. **Simple progress tracking:** one committed offset per partition per group. Two readers would need complex coordination of which records each processed.
3. **No duplicate work** within the group.

## What happens with more consumers than partitions
```text
Topic with 3 partitions, group of 5 consumers:
  C1 ← P0   C2 ← P1   C3 ← P2   C4 idle   C5 idle
```
Idle consumers take over during a rebalance if an active one fails.

## Getting more parallelism than partitions
- **Add partitions** (planned carefully because key mapping changes).
- **Process records concurrently inside one consumer** while keeping per-key order, e.g. Confluent's **Parallel Consumer** library, or hand records to a thread pool keyed by record key, and commit only offsets whose records are all finished.
```java
// Spring Kafka: concurrency creates several consumers, still max one per partition
@KafkaListener(topics = "payments", groupId = "billing", concurrency = "6")
```
- Kafka 4.0 adds **share groups** (KIP-932, "queues for Kafka"), where several consumers can share a partition with per-record acknowledgement, giving queue semantics without ordering guarantees.

## Different groups
Consumers in **different** groups can of course read the same partition, each with its own offset.

## Summary
In a classic consumer group a partition has one owner at a time, preserving order and simple offset tracking; scale by adding partitions or by in-consumer parallelism.
MD,

'Can one consumer read several partitions?' => <<<'MD'
## Answer: yes
A consumer can be assigned many partitions, from one topic or from several topics. When a group has fewer consumers than partitions, partitions are divided among them.

```text
Topic orders with 6 partitions, group of 2 consumers:
  consumer A ← P0, P1, P2
  consumer B ← P3, P4, P5
```
A single consumer in a group reads all 6 partitions.

## How it works
`poll()` fetches from all assigned partitions and returns a batch that can contain records from several partitions. Order is preserved **within each partition** but records from different partitions are interleaved.

```java
ConsumerRecords<String, String> records = consumer.poll(Duration.ofMillis(500));
for (TopicPartition tp : records.partitions()) {
    List<ConsumerRecord<String, String>> partRecords = records.records(tp);
    for (ConsumerRecord<String, String> r : partRecords) process(r);
    long last = partRecords.get(partRecords.size() - 1).offset();
    consumer.commitSync(Map.of(tp, new OffsetAndMetadata(last + 1)));   // commit per partition
}
```

## Assignment strategies
Configured with `partition.assignment.strategy`:
- **RangeAssignor:** contiguous ranges per topic (can be uneven across topics).
- **RoundRobinAssignor:** spreads partitions evenly.
- **StickyAssignor:** even and keeps previous assignments where possible.
- **CooperativeStickyAssignor:** sticky plus incremental (cooperative) rebalancing, so consumers keep most partitions during a rebalance. Recommended default for new apps. Kafka 4.0's new consumer protocol (KIP-848) moves assignment to the broker.

## Manual assignment
`consumer.assign(List.of(new TopicPartition("orders", 2)))` reads specific partitions without group management (no rebalancing); useful for tools and replays.

## Summary
Yes: a consumer can own multiple partitions; it receives interleaved batches while order is kept per partition, and assignors decide how partitions are spread.
MD,

'What is replication?' => <<<'MD'
## What it is
Replication keeps **copies of each partition on several brokers** so data survives broker failures. The number of copies is the **replication factor** (commonly 3). One copy is the **leader**; the others are **followers** that continuously fetch new records from the leader.

```text
Partition payments-0, replication factor 3
  Broker 1: leader     [0..1050]
  Broker 2: follower   [0..1050]   in sync
  Broker 3: follower   [0..1048]   catching up
```

## How it works
1. The producer writes to the leader.
2. Followers send fetch requests to the leader and append the same records to their logs.
3. Followers that are up to date are in the **ISR** (in-sync replica set).
4. The **high watermark** advances when all ISR members have a record; consumers only read up to the high watermark, so they never see data that could disappear after a failover.
5. With `acks=all`, the producer gets success only after all ISR replicas have the record.

## Durability settings that work together
```properties
replication.factor=3          # three copies
min.insync.replicas=2         # at least two copies must confirm a write
acks=all                      # producer waits for the ISR
unclean.leader.election.enable=false   # never elect a stale replica
```
This tolerates one broker failure with no data loss and continued writes.

## Placement
Replicas are placed on different brokers, and with `broker.rack` set, on different racks or availability zones, so a whole rack or AZ failure is survivable.

## Summary
Replication copies every partition to several brokers (leader plus followers); with `acks=all` and `min.insync.replicas`, committed data survives broker failures.
MD,

'What is a partition leader?' => <<<'MD'
## What it is
For every partition, one replica is the **leader**. The leader handles all **writes** from producers and, by default, all **reads** from consumers. Followers only copy data from it.

## Why one leader
A single writer per partition gives a strict order of records and simple consistency: followers replicate exactly the leader's log.

## Leader distribution
Leaders of different partitions are spread across brokers, so each broker is the leader for some partitions and a follower for others. This shares the load. The first replica in a partition's replica list is its **preferred leader**; Kafka periodically moves leadership back to preferred leaders (`auto.leader.rebalance.enable=true`) so load stays balanced after failures.

```bash
kafka-topics.sh --bootstrap-server localhost:9092 --describe --topic payments
# Topic: payments  Partition: 0  Leader: 1  Replicas: 1,2,3  Isr: 1,2,3
# Topic: payments  Partition: 1  Leader: 2  Replicas: 2,3,1  Isr: 2,3,1
```

## Leader election
If the leader's broker fails, the **controller** picks a new leader from the ISR and notifies brokers; clients refresh metadata and switch. With `unclean.leader.election.enable=false`, only in-sync replicas can become leader, preventing data loss.

## Reading from followers
Since Kafka 2.4, consumers can **fetch from the closest replica** (KIP-392) using `client.rack` and `replica.selector.class=RackAwareReplicaSelector`, which reduces cross-AZ network cost in the cloud.

## Summary
The leader is the replica that serves writes (and normally reads) for a partition; leaders are spread over brokers and re-elected from the ISR by the controller on failure.
MD,

'What is ISR?' => <<<'MD'
## What it is
**ISR (In-Sync Replicas)** is the set of replicas of a partition, including the leader, that are fully caught up with the leader's log. Only ISR members count when a write needs acknowledgement (`acks=all`), and only they can become leader (with clean leader election).

## Staying in the ISR
A follower stays in the ISR as long as it has fetched up to the leader's log end within `replica.lag.time.max.ms` (default 30 seconds). If it falls behind longer (slow disk, network trouble, GC pause, broker down), the leader removes it from the ISR. When it catches up again, it is added back.

```text
Replicas: 1,2,3   ISR: 1,2,3   healthy
Replicas: 1,2,3   ISR: 1,3     broker 2 lagging → partition is under-replicated
```

## Why it matters
- **Durability:** with `acks=all`, a write is committed once all ISR members have it. Combined with `min.insync.replicas=2`, at least two copies exist before the producer is told "success".
- **Availability trade-off:** if ISR shrinks below `min.insync.replicas`, producers using `acks=all` get `NotEnoughReplicasException` (writes are rejected rather than accepted with too few copies). Reads continue.
- **Safe failover:** a new leader is chosen from ISR, so no committed data is lost.

## Monitoring
- `UnderReplicatedPartitions` > 0: some partitions have ISR smaller than the replica list.
- `UnderMinIsrPartitionCount` > 0: writes with `acks=all` are failing for those partitions.
- Frequent ISR shrink/expand events point to overloaded brokers or network problems.

## Summary
The ISR is the set of replicas fully caught up with the leader; acknowledgements and leader elections rely on it, and its size versus `min.insync.replicas` decides whether writes are accepted.
MD,

'What is replication factor?' => <<<'MD'
## What it is
The replication factor (RF) is the **number of copies** of each partition in the cluster, including the leader. RF=3 means one leader and two followers on three different brokers.

```bash
kafka-topics.sh --create --topic payments --partitions 12 --replication-factor 3 \
  --bootstrap-server localhost:9092
```
Default for auto-created topics: `default.replication.factor` (broker setting).

## Choosing a value
| RF | Survives | Typical use |
|---|---|---|
| 1 | nothing: losing the broker loses data | local development only |
| 2 | one failure, but with `min.insync.replicas=2` any failure stops writes | rarely recommended |
| 3 | one failure with writes continuing (`min.insync.replicas=2`), two failures without data loss | **standard for production** |
| 4–5 | more failures | critical data, multi-AZ stretch clusters |

## Constraints and costs
- RF cannot exceed the number of brokers.
- Storage and replication network traffic multiply by RF: 1 TB of data at RF=3 uses 3 TB of disk.
- Write latency with `acks=all` depends on the slowest ISR member.

## The usual production trio
```properties
replication.factor=3
min.insync.replicas=2
acks=all
```
Survives one broker outage with no data loss and writes still accepted; with two brokers down, writes stop (safety first) but data is preserved.

## Changing RF later
Use partition reassignment with a JSON plan that lists the new replicas (`kafka-reassign-partitions.sh --execute`). Internal topics (`__consumer_offsets`, `__transaction_state`) should also use RF=3 (`offsets.topic.replication.factor=3`).

## Summary
Replication factor is the number of copies of each partition; RF=3 with `min.insync.replicas=2` and `acks=all` is the standard balance of durability, availability and cost.
MD,

'What happens when a leader broker fails?' => <<<'MD'
## Sequence of events
1. **Detection:** the controller notices the broker is gone (it stops sending heartbeats in KRaft, or its ZooKeeper session expires).
2. **Leader election:** for every partition that broker led, the controller picks a new leader from the remaining **ISR** members.
3. **Metadata update:** the new leadership and ISR are written to the metadata log and pushed to all brokers.
4. **Clients recover:** producers and consumers get `NOT_LEADER_OR_FOLLOWER` or connection errors, refresh metadata and reconnect to the new leaders. Producers retry automatically (`retries`, `delivery.timeout.ms`).
5. **Followers re-point:** remaining followers fetch from the new leader; the old broker's replicas are removed from the ISR.
6. **Return:** when the failed broker restarts, it truncates any uncommitted records beyond the high watermark, catches up as a follower, rejoins the ISR, and later may become leader again through preferred-leader rebalancing.

## Data safety
- With `acks=all`, `min.insync.replicas=2` and clean leader election, every acknowledged record exists on the new leader: **no data loss**.
- With `acks=1`, a record acknowledged by the old leader but not yet copied is **lost**.
- With `unclean.leader.election.enable=true`, an out-of-sync replica can become leader if no ISR member is alive: availability is restored but data can be lost.

## Impact
Failover usually takes from under a second to a few seconds (faster with KRaft). During that time, writes to affected partitions are retried or delayed, and consumers pause for those partitions. Idempotent producers prevent duplicates caused by these retries.

## Summary
The controller elects a new leader from the ISR, clients refresh metadata and retry, and with `acks=all` plus `min.insync.replicas` no acknowledged data is lost.
MD,

'What is retention?' => <<<'MD'
## What it is
Retention defines **how long Kafka keeps records**, independent of whether anyone consumed them. Kafka deletes old data per **segment** (a file of records) once the whole segment is older than the retention limit or the partition exceeds its size limit.

## Settings
| Setting | Scope | Meaning |
|---|---|---|
| `retention.ms` (topic) / `log.retention.hours` (broker) | time | default 7 days |
| `retention.bytes` | size per partition | default unlimited (-1) |
| `segment.bytes` / `segment.ms` | segment roll size or age | 1 GB / 7 days |
| `cleanup.policy` | `delete`, `compact` or `compact,delete` | default `delete` |

```bash
kafka-configs.sh --bootstrap-server localhost:9092 --alter --entity-type topics \
  --entity-name clickstream --add-config retention.ms=86400000     # 1 day
```

## How deletion actually works
Only **closed** segments are deleted. If `segment.ms` is 7 days and `retention.ms` is 1 day, data may stay much longer than a day because the active segment has not rolled yet. Low-volume topics with short retention need a smaller `segment.ms`.

## Choosing retention
- Clickstream or logs: hours to a few days.
- Business events used for replay or onboarding new consumers: 7–30 days or more.
- Event sourcing/audit: unlimited (`retention.ms=-1`), ideally with **tiered storage** (Kafka 3.6+), which moves old segments to S3-like object storage cheaply.
- Latest-state topics (customer profile): **compaction** instead of time retention.

## Consumer impact
If a consumer is offline longer than retention, its committed offset points to deleted data; it then follows `auto.offset.reset` (and may skip records). Monitor lag against retention.

## Summary
Retention removes old segments based on time or size, regardless of consumption; choose it from replay needs and storage cost, and use compaction or tiered storage for long-lived data.
MD,

'Does Kafka delete messages after consumption?' => <<<'MD'
## Answer: no
Kafka **does not delete a record when it is consumed**. Records stay in the log until **retention** (time or size) or **compaction** removes them. Consuming only moves the consumer group's committed offset forward.

## Why Kafka works this way
- **Multiple independent readers:** many consumer groups read the same records at different speeds. Deleting on read would make that impossible.
- **Replay:** after a bug fix you can reset offsets and reprocess days of events. A new service can be bootstrapped from history.
- **Performance:** no per-message delete bookkeeping; deleting whole segment files is cheap.

```text
Topic orders (retention 7 days)
   [day1][day2][day3][day4][day5][day6][day7] ← new
     ↑ group analytics at day1     ↑ group billing at day6
Records older than 7 days are removed regardless of these positions.
```

## Compare with traditional queues
| | Kafka | RabbitMQ / SQS / JMS queue |
|---|---|---|
| After consumption | record stays | message removed after ack |
| Replay | yes, by resetting offsets | no (unless re-published) |
| Multiple consumers of same data | each group | separate queues needed |
| Progress tracking | consumer offset | broker tracks each message |

## Deleting data deliberately
- Lower `retention.ms` for the topic.
- Compacted topics: send a **tombstone** (key with null value) to delete a key.
- Admin API `deleteRecords` truncates a partition up to an offset.
- For personal data (GDPR/DPDP Act), design for deletion: use compaction with tombstones, or crypto-shredding (encrypt per user and delete the key).

## Summary
No. Consumption only advances offsets; records are removed by retention or compaction, which enables multiple consumer groups and replay.
MD,

'Kafka versus a traditional queue?' => <<<'MD'
## Model
- **Traditional queue (JMS, ActiveMQ, SQS, RabbitMQ classic queues):** the broker pushes or hands out messages, tracks each message's state, and removes it after a consumer acknowledges it. Each message goes to one consumer.
- **Kafka:** a durable, partitioned **log**. The broker stores records in order; consumers pull and track their own position (offset). Records remain until retention.

## Comparison
| | Kafka | Traditional queue |
|---|---|---|
| Storage | append-only log, retained | messages removed after ack |
| Consumers | many groups read same data | one consumer per message (fan-out needs extra setup) |
| Replay | yes | no |
| Ordering | per partition | per queue (lost with competing consumers) |
| Scaling consumers | up to the number of partitions | add consumers freely |
| Per-message ack/redelivery | offset commit (batch), no per-message ack in classic groups | per message, with redelivery and delays |
| Priorities, TTL per message, delayed delivery | not built in | commonly supported |
| Throughput | very high (millions/s) | lower, but plenty for most apps |
| Typical use | event streams, analytics, CDC, event sourcing | task/job queues, RPC-style work distribution |

## Example
- **Sending 10,000 PDF resume-parsing jobs to workers:** a task queue (RabbitMQ, SQS, Laravel queue) fits: each job processed once, retried individually, no need to keep it.
- **Publishing every `application.submitted` event for notifications, analytics, ATS scoring and search:** Kafka fits: many consumers, history, ordering per candidate.

## Kafka 4.0 share groups
KIP-932 ("Queues for Kafka") adds share groups with per-record acknowledgement and multiple consumers per partition, narrowing the gap for queue-style workloads.

## Summary
A queue distributes individual messages to workers and deletes them; Kafka is a retained, replayable log read by many independent groups, built for high-throughput event streams.
MD,

'Kafka versus RabbitMQ?' => <<<'MD'
## Different designs
- **RabbitMQ** is a **smart broker, simple consumer** message broker (AMQP). Producers publish to **exchanges**; routing rules (direct, topic, fanout, headers) deliver copies to **queues**; consumers receive pushed messages and acknowledge each one.
- **Kafka** is a **simple broker, smart consumer** distributed log. Brokers append and store; consumers pull and manage offsets.

## Comparison
| | Kafka | RabbitMQ |
|---|---|---|
| Throughput | very high (hundreds of MB/s per broker) | high (tens of thousands msg/s per queue) |
| Retention/replay | yes, days or forever | messages gone after ack (Streams plugin adds log semantics) |
| Routing | by topic and key; logic in consumers | rich routing with exchanges and bindings |
| Ordering | per partition | per queue, single consumer |
| Per-message features | limited | priorities, TTL, dead-letter exchanges, delayed messages |
| Delivery | pull, batch offset commits | push with prefetch, per-message ack/nack |
| Scaling | partitions across brokers | queues per node, quorum queues |
| Ecosystem | Connect, Streams, Schema Registry, ksqlDB | plugins, many protocols (AMQP, MQTT, STOMP) |

## When to choose which
Choose **Kafka** when:
- Many services need the same events (fan-out) and may need replay.
- Volume is high (clickstream, telemetry, CDC, logs).
- You do stream processing or event sourcing.

Choose **RabbitMQ** when:
- You need a task queue with per-message retries, priorities, delays and complex routing.
- Request/reply messaging or low-latency work distribution at moderate volume.
- The team wants simpler operations for a small system.

## Banking example
Card transactions streamed to fraud detection, ledger projection and analytics: Kafka. Sending a "generate statement PDF" job to a pool of workers with retries: RabbitMQ (or a job queue).

## Summary
RabbitMQ excels at flexible routing and per-message task queues; Kafka excels at high-throughput, retained, replayable event streams read by many consumers.
MD,

'What does producer acks control?' => <<<'MD'
## What it controls
`acks` decides **how many replicas must confirm a write before the leader tells the producer it succeeded**. It is the main producer setting for the trade-off between durability and latency.

| Value | Producer gets success when | Risk |
|---|---|---|
| `acks=0` | immediately after sending, no response waited | records lost silently on any failure |
| `acks=1` | the leader has written to its log | lost if the leader fails before followers copy |
| `acks=all` (`-1`) | all **in-sync replicas** have the record | none for committed records (with `min.insync.replicas ≥ 2`) |

Since Kafka 3.0, the default is **`acks=all`** with **idempotence enabled**.

```java
props.put(ProducerConfig.ACKS_CONFIG, "all");
props.put(ProducerConfig.ENABLE_IDEMPOTENCE_CONFIG, true);
props.put(ProducerConfig.DELIVERY_TIMEOUT_MS_CONFIG, 120000);
```

## Interaction with min.insync.replicas
`acks=all` alone waits for the *current* ISR, which could be only the leader. Setting topic `min.insync.replicas=2` makes the broker reject writes when fewer than two replicas are in sync, guaranteeing at least two copies.

## Latency
`acks=all` adds the time for followers to fetch the record (typically a few milliseconds in one data centre). Batching (`linger.ms`, `batch.size`) amortises this, so throughput with `acks=all` is still very high.

## Choosing
- Payments, orders, ledger events: `acks=all`.
- Metrics or logs where small loss is acceptable and latency matters most: `acks=1` (or even `0` for fire-and-forget telemetry).

## Summary
`acks` sets how many replicas must have a record before the producer is told it is written: 0 (none), 1 (leader), all (every in-sync replica, the durable default).
MD,

'What does acks=0 mean?' => <<<'MD'
## Meaning
With `acks=0` the producer **does not wait for any acknowledgement**. It writes the request to the network socket and immediately considers the record sent. The broker sends no response.

## Consequences
- **Lowest latency and highest throughput** from the producer's point of view.
- **No delivery guarantee:** if the leader is down, the network drops the packet, the broker rejects the record (too large, wrong partition) or crashes before writing, the producer never knows.
- **Retries do nothing useful**, because no error is ever received.
- The returned `RecordMetadata` offset is `-1` (unknown).
- **Idempotence cannot be enabled** (it requires `acks=all`).

```java
props.put(ProducerConfig.ACKS_CONFIG, "0");
props.put(ProducerConfig.ENABLE_IDEMPOTENCE_CONFIG, false);
```

## When it is acceptable
Only for data where losing some records does not matter and volume is huge:
- High-frequency IoT sensor readings where the next reading arrives in a second.
- Application metrics or debug logs sampled anyway.
- Real-time location pings where only the latest matters.

## When it is wrong
Anything with business value: orders, payments, job applications, audit events, notifications. A silent loss there causes missing money or angry users with no trace.

## Summary
`acks=0` is fire-and-forget: fastest, but the producer never learns about failures, so records can be lost silently; use it only for disposable telemetry.
MD,

];
