<?php

// Apache Kafka interview questions 51–75, in depth.
return [

'What does session.timeout.ms control?' => <<<'MD'
## What it controls
`session.timeout.ms` is how long the group coordinator waits **without receiving a heartbeat** from a consumer before declaring it dead, removing it from the group and triggering a rebalance. Default: **45 seconds** (Kafka 3.0+; 10 s before).

Heartbeats are sent by a background thread, so this detects **process crashes, network partitions and frozen JVMs**, not slow processing (that is `max.poll.interval.ms`).

## Trade-off
| Value | Pros | Cons |
|---|---|---|
| short (10 s) | failures detected quickly, partitions reassigned fast | GC pauses or brief network blips cause unnecessary rebalances |
| long (60+ s) | tolerant of pauses | a crashed consumer's partitions sit unprocessed longer |

The value must lie between the broker's `group.min.session.timeout.ms` (6 s) and `group.max.session.timeout.ms` (30 min).

## Static membership
With `group.instance.id` set, a consumer that restarts (e.g. a Kubernetes pod during a rolling deploy) and rejoins **within** `session.timeout.ms` gets its old partitions back **without a rebalance**. Teams often raise the session timeout to 1–5 minutes with static membership.
```properties
group.instance.id=payment-consumer-0     # stable per pod, e.g. StatefulSet name
session.timeout.ms=120000
heartbeat.interval.ms=10000
```

## Clean shutdown
`consumer.close()` sends a LeaveGroup request so the rebalance starts immediately rather than after the timeout (static members do not send it, by design).

## Summary
`session.timeout.ms` is the heartbeat silence after which a consumer is considered dead; balance fast failure detection against false alarms, and use static membership to avoid rebalances on restarts.
MD,

'What does heartbeat.interval.ms control?' => <<<'MD'
## What it controls
`heartbeat.interval.ms` is **how often the consumer's background thread sends heartbeats** to the group coordinator. Default: **3 seconds**.

Heartbeats serve two purposes:
1. Prove the consumer is alive (checked against `session.timeout.ms`).
2. Learn quickly that a **rebalance** has started: the coordinator answers a heartbeat with "rebalance in progress", so the consumer rejoins.

## Rule of thumb
Set it to no more than **one third** of `session.timeout.ms`, so several heartbeats can be missed before the session expires:
```properties
session.timeout.ms=45000
heartbeat.interval.ms=3000       # up to 15000 is valid
```

## Effects of the value
- **Lower:** faster reaction to rebalances, slightly more network traffic to the coordinator (negligible for most clusters).
- **Higher:** fewer requests but slower rebalance participation; if close to the session timeout, a couple of lost packets can expel the consumer.

## Heartbeats do not prove progress
Because heartbeats come from a separate thread, a consumer stuck in an infinite loop or a blocked database call keeps heartbeating. That case is caught by `max.poll.interval.ms`, which requires the main loop to call `poll()`.

| Setting | Detects |
|---|---|
| `heartbeat.interval.ms` | how often liveness is reported |
| `session.timeout.ms` | crashed process or lost network |
| `max.poll.interval.ms` | stuck or too slow processing loop |

## Kafka 4.0 note
With the new consumer group protocol (KIP-848), the heartbeat interval and session timeout are controlled by the broker (`group.consumer.heartbeat.interval.ms`, `group.consumer.session.timeout.ms`).

## Summary
`heartbeat.interval.ms` sets how often a consumer reports liveness and checks for rebalances; keep it at about a third of the session timeout or less.
MD,

'What does max.poll.records control?' => <<<'MD'
## What it controls
`max.poll.records` is the **maximum number of records a single `poll()` returns**. Default: **500**. It does not change how much data is fetched from brokers (that is `fetch.max.bytes`, `max.partition.fetch.bytes`); extra fetched records wait in the consumer's buffer for the next poll.

## Why it matters
Processing time per poll ≈ `max.poll.records` × time per record. That total must stay below `max.poll.interval.ms`, otherwise the consumer is kicked out of the group.

| Processing per record | Safe max.poll.records (5 min interval) |
|---|---|
| 1 ms (in-memory, batched DB writes) | 500–5000 |
| 50 ms (one DB call) | ~500 |
| 1 s (external API) | ~100 or less |

## Tuning
- **Raise it** when processing is fast and you batch work (bulk inserts) to cut per-poll overhead.
- **Lower it** when each record is expensive or variable (calls to payment gateways, PDF generation) to avoid poll timeouts and to commit progress more often (smaller reprocessing window after a crash).

```properties
max.poll.records=100
max.poll.interval.ms=300000
fetch.min.bytes=1
```

## Spring Kafka
```yaml
spring.kafka.consumer.max-poll-records: 100
spring.kafka.listener.type: batch     # receive the whole poll as a List
```

## Summary
`max.poll.records` caps records per poll; size it so a batch is processed well within `max.poll.interval.ms`, larger for cheap batched work and smaller for slow per-record work.
MD,

'What is a dead-letter topic?' => <<<'MD'
## What it is
A dead-letter topic (DLT, or DLQ) is a separate topic where a consumer sends records it **cannot process** after a limited number of attempts, so they do not block the partition or get lost. The main consumer commits past the bad record and continues.

## Typical reasons records go to a DLT
- Deserialisation errors (corrupt JSON, schema mismatch): **poison pills**.
- Validation failures (missing customer id, negative amount).
- Business rule violations that will never succeed on retry.
- Persistent downstream errors after all retries.

## What to store
The original key and value unchanged, plus headers describing the failure:
```text
headers: original-topic=payments, original-partition=3, original-offset=884213,
         exception-class=..., exception-message=..., failed-at=2026-10-15T10:20:00Z, attempts=4
```

## Spring Kafka example
```java
@Bean
DefaultErrorHandler errorHandler(KafkaTemplate<Object, Object> template) {
    var recoverer = new DeadLetterPublishingRecoverer(template,
        (record, ex) -> new TopicPartition(record.topic() + ".DLT", record.partition()));
    var handler = new DefaultErrorHandler(recoverer, new ExponentialBackOff(1000, 2.0) {{ setMaxElapsedTime(30_000); }});
    handler.addNotRetryableExceptions(DeserializationException.class, ValidationException.class);
    return handler;
}
```
Kafka Connect has the same idea: `errors.tolerance=all`, `errors.deadletterqueue.topic.name=...`.

## Operating a DLT
- **Alert** when records arrive; a DLT that nobody watches is silent data loss.
- Provide tooling to inspect, fix and **replay** records back to the original topic.
- Give the DLT long retention.
- Remember ordering: a record parked in the DLT means later events for the same key were processed before it.

## Summary
A dead-letter topic isolates records that repeatedly fail, with failure metadata in headers, so the main flow keeps moving; monitor it and provide replay.
MD,

'How do retry topics work?' => <<<'MD'
## The problem with retrying in place
Retrying a failed record inside the consumer blocks the whole partition: if the SMS provider is down for 10 minutes, every later record waits. Retrying immediately also hammers the failing system.

## Non-blocking retries with retry topics
Failed records are published to **delay topics**, each consumed after a waiting period, and finally to a DLT:
```text
orders  ──fail──▶ orders-retry-1m ──fail──▶ orders-retry-10m ──fail──▶ orders-retry-1h ──fail──▶ orders-dlt
   ▲ main consumer keeps going        (each retry consumer waits until the record's due time)
```
A retry consumer reads a record, checks a header like `retry-at`, and if the time has not come, pauses that partition (`consumer.pause`) or sleeps until due, then processes it. Success at any level ends the chain.

## Spring Kafka support
```java
@RetryableTopic(
    attempts = "4",
    backoff = @Backoff(delay = 60_000, multiplier = 10.0, maxDelay = 3_600_000),
    exclude = { ValidationException.class },
    dltTopicSuffix = ".dlt")
@KafkaListener(topics = "orders", groupId = "notifier")
public void onOrder(OrderEvent e) { notifier.send(e); }

@DltHandler
public void onDlt(OrderEvent e, @Header(KafkaHeaders.EXCEPTION_MESSAGE) String error) { alerts.raise(e, error); }
```
Spring creates the retry topics, headers and consumers automatically.

## Design points
- Retry only **transient** errors (timeouts, 503s); send permanent errors (bad data) straight to the DLT.
- **Ordering is lost** for retried records: later events for the same key are processed first. If order matters, use blocking retries for that flow or park all events of the key.
- Make processing idempotent: a retried record may have partly succeeded.
- Monitor every retry topic's lag and the DLT.

## Summary
Retry topics move failed records into delayed topics with increasing back-off and finally a DLT, keeping the main partition flowing at the cost of per-key ordering.
MD,

'What is Kafka Connect?' => <<<'MD'
## What it is
Kafka Connect is Kafka's framework for **moving data between Kafka and other systems without writing custom producer or consumer code**. You configure ready-made **connectors** with JSON; Connect workers run them, scale them and track their progress.

## Architecture
- **Workers:** JVM processes running connectors, in **standalone** (one process, dev) or **distributed** mode (a cluster sharing work, storing configs, offsets and status in Kafka topics).
- **Connectors:** plugins for a specific system (MySQL, S3, Elasticsearch, Salesforce...).
- **Tasks:** units of parallel work a connector is split into.
- **Converters:** turn data into bytes (JSON, Avro, Protobuf with Schema Registry).
- **SMTs (Single Message Transforms):** light per-record changes: rename or drop fields, mask values, route by field, add timestamps.

## Example: MySQL changes to Kafka with Debezium
```json
{
  "name": "jobs-db-cdc",
  "config": {
    "connector.class": "io.debezium.connector.mysql.MySqlConnector",
    "database.hostname": "mysql", "database.port": "3306",
    "database.user": "debezium", "database.password": "${file:/secrets/db.properties:password}",
    "database.server.id": "5501", "topic.prefix": "portal",
    "table.include.list": "portal.jobs,portal.applications",
    "schema.history.internal.kafka.topic": "schema-history.portal",
    "schema.history.internal.kafka.bootstrap.servers": "kafka:9092"
  }
}
```
```bash
curl -X POST -H "Content-Type: application/json" --data @jobs-db-cdc.json http://connect:8083/connectors
```

## Benefits
- No custom code for common integrations; fault tolerance and offset tracking built in.
- Scales horizontally by adding workers and tasks.
- REST API for management; error handling with dead-letter queues.

## Summary
Kafka Connect runs configurable source and sink connectors in a scalable worker cluster to stream data in and out of Kafka without writing integration code.
MD,

'What is a source connector?' => <<<'MD'
## What it is
A source connector **reads data from an external system and writes it into Kafka topics**. Connect tracks the source position (e.g. database binlog position, file offset, last timestamp) so it resumes correctly after restarts.

## Common source connectors
| Connector | Reads from | How |
|---|---|---|
| Debezium MySQL/PostgreSQL/SQL Server/Oracle | databases | change data capture from transaction logs (binlog, WAL) |
| JDBC Source | databases | polling queries using an incrementing id or timestamp column |
| MongoDB source | MongoDB | change streams |
| S3 / file source | object storage, files | reads new objects or lines |
| MQTT, JMS, RabbitMQ source | message brokers | subscribes and forwards |
| Salesforce, HTTP sources | SaaS APIs | polling or streaming APIs |

## CDC versus polling
- **CDC (Debezium):** captures every insert, update and delete in commit order with low latency and little database load; needs log access permissions.
- **JDBC polling:** simple, but misses deletes, can miss updates between polls, and adds query load.

```json
{
  "name": "orders-jdbc-source",
  "config": {
    "connector.class": "io.confluent.connect.jdbc.JdbcSourceConnector",
    "connection.url": "jdbc:mysql://db:3306/shop",
    "mode": "timestamp+incrementing",
    "timestamp.column.name": "updated_at",
    "incrementing.column.name": "id",
    "table.whitelist": "orders",
    "topic.prefix": "shop.",
    "poll.interval.ms": "5000"
  }
}
```

## Use cases
- Stream database changes to a search index or cache.
- Feed a data lake or warehouse in near real time.
- Build the **outbox pattern**: Debezium reads an `outbox` table and publishes events reliably.

## Summary
A source connector pulls data from an external system into Kafka, ideally via change data capture, with Connect managing source offsets and fault tolerance.
MD,

'What is a sink connector?' => <<<'MD'
## What it is
A sink connector **reads records from Kafka topics and writes them to an external system**. Internally it is a consumer group: it commits Kafka offsets after the data is written, and scales by running several tasks (up to the number of partitions).

## Common sink connectors
| Connector | Writes to |
|---|---|
| Elasticsearch / OpenSearch sink | search indexes (job search, product search) |
| JDBC sink | MySQL, PostgreSQL, Oracle (insert or upsert) |
| S3 / GCS / Azure Blob sink | data lake files (Parquet, JSON, Avro) partitioned by time |
| Snowflake, BigQuery, Redshift sinks | data warehouses |
| MongoDB, Cassandra, Redis sinks | NoSQL stores |
| HTTP sink | REST endpoints |

## Example: index job postings in Elasticsearch
```json
{
  "name": "jobs-es-sink",
  "config": {
    "connector.class": "io.confluent.connect.elasticsearch.ElasticsearchSinkConnector",
    "topics": "portal.jobs",
    "connection.url": "http://elasticsearch:9200",
    "key.ignore": "false",
    "behavior.on.null.values": "delete",
    "tasks.max": "4",
    "errors.tolerance": "all",
    "errors.deadletterqueue.topic.name": "dlq.jobs-es-sink",
    "errors.deadletterqueue.context.headers.enable": "true"
  }
}
```
Using the record key as the document id makes writes **idempotent** (a redelivered record overwrites the same document), and tombstones delete documents.

## Delivery semantics
Sinks are usually at-least-once; idempotent writes (upsert by key, deterministic file names) give effectively exactly-once results.

## Summary
A sink connector consumes Kafka topics and writes to external systems such as search engines, databases and data lakes, with offset tracking, scaling and dead-letter handling built in.
MD,

'What is Kafka Streams?' => <<<'MD'
## What it is
Kafka Streams is a **Java library for building stream-processing applications** that read from Kafka, transform, aggregate or join data, and write results back to Kafka. It is not a separate cluster: you embed it in a normal application (e.g. a Spring Boot service) and scale by running more instances.

## Capabilities
- Stateless operations: `filter`, `map`, `flatMap`, `branch`.
- Stateful operations: `groupByKey`, `count`, `aggregate`, `reduce`, with local **state stores** (RocksDB) backed by changelog topics.
- **Windowing:** tumbling, hopping, sliding and session windows on event time.
- **Joins:** stream-stream, stream-table, table-table.
- **Exactly-once processing:** `processing.guarantee=exactly_once_v2`.
- Interactive queries of state stores.

## Example: count job applications per company per hour
```java
StreamsBuilder builder = new StreamsBuilder();
builder.stream("applications", Consumed.with(Serdes.String(), applicationSerde))
       .selectKey((k, app) -> app.getCompanyId())
       .groupByKey(Grouped.with(Serdes.String(), applicationSerde))
       .windowedBy(TimeWindows.ofSizeWithNoGrace(Duration.ofHours(1)))
       .count(Materialized.as("applications-per-company-hourly"))
       .toStream()
       .map((window, count) -> KeyValue.pair(window.key(), count))
       .to("applications-per-company", Produced.with(Serdes.String(), Serdes.Long()));

KafkaStreams streams = new KafkaStreams(builder.build(), props);   // application.id, bootstrap.servers
streams.start();
```

## How it scales and recovers
Work is split into **tasks**, one per input partition. Instances with the same `application.id` share tasks like a consumer group. If an instance dies, its tasks move to others, which rebuild state from changelog topics (standby replicas make this faster).

## Alternatives
ksqlDB (SQL over Kafka Streams), Apache Flink (separate cluster, very powerful for large stateful jobs), Spark Structured Streaming.

## Summary
Kafka Streams is an embeddable Java library for stateful, fault-tolerant stream processing with windows, joins and exactly-once guarantees, scaled simply by running more instances.
MD,

'Kafka Connect versus Kafka Streams?' => <<<'MD'
## Different jobs
- **Kafka Connect** moves data **between Kafka and external systems** (databases, S3, Elasticsearch) using configured connectors.
- **Kafka Streams** **processes data inside Kafka**: reads topics, computes, writes topics, using code you write.

## Comparison
| | Kafka Connect | Kafka Streams |
|---|---|---|
| Purpose | integration (import/export) | transformation and analytics |
| How you use it | JSON configuration of connectors | Java/Kotlin code (DSL or Processor API) |
| Runs as | Connect worker cluster | library inside your application |
| Data flow | external system ↔ Kafka | Kafka → Kafka |
| Transformations | simple per-record SMTs | full logic: joins, aggregations, windows, state |
| State | source/sink offsets | local state stores with changelogs |
| Scaling | workers and tasks | app instances, one task per partition |
| Exactly-once | depends on connector | built in (`exactly_once_v2`) |

## Using them together
```text
MySQL ──(Debezium source)──▶ portal.applications
                                   │
                           Kafka Streams app: enrich with job data,
                           count per company, detect spam
                                   │
                         applications.enriched ──(Elasticsearch sink)──▶ search index
                                                └─(S3 sink)──▶ data lake
```
Connect brings data in and out; Streams does the business logic in between.

## When to choose which
- "Copy table X into Kafka" or "write topic Y into Snowflake": Connect.
- "Compute a running total", "join orders with customers", "flag 5 failed logins in a minute": Streams (or ksqlDB/Flink).

## Summary
Connect is for moving data in and out of Kafka with configuration; Streams is for processing data within Kafka with code; real pipelines often combine both.
MD,

'What is a KStream?' => <<<'MD'
## What it is
A `KStream` is Kafka Streams' abstraction of a **record stream**: an unbounded sequence of independent events. Each record is a new fact; a later record with the same key does **not** replace an earlier one.

```text
KStream of payments (key = account):
ACC-1 → ₹500
ACC-2 → ₹200
ACC-1 → ₹300      both ACC-1 events stay; they are separate payments
```

## Typical operations
```java
KStream<String, Payment> payments = builder.stream("payments");

KStream<String, Payment> large = payments.filter((acc, p) -> p.amount() > 100_000);
KStream<String, String> alerts = large.mapValues(p -> "Large payment " + p.id());
alerts.to("payment-alerts");

// Branch by type
Map<String, KStream<String, Payment>> branches = payments.split(Named.as("pay-"))
    .branch((k, p) -> p.type() == UPI, Branched.as("upi"))
    .branch((k, p) -> p.type() == CARD, Branched.as("card"))
    .defaultBranch(Branched.as("other"));
```
- Stateless: `filter`, `map`, `mapValues`, `flatMap`, `peek`, `merge`, `split`.
- Re-keying: `selectKey` (triggers repartitioning before grouping).
- Stateful: `groupByKey().count()/aggregate()` turns a stream into a **KTable**.
- Joins: stream-stream (within a time window) and stream-table (enrichment).

## Examples of things that are streams
Clicks, payments, application submissions, sensor readings, log lines: every event matters on its own.

## Summary
A KStream is an append-only stream of independent events where every record counts; you filter, transform, join and aggregate it.
MD,

'What is a KTable?' => <<<'MD'
## What it is
A `KTable` is Kafka Streams' abstraction of a **changelog stream**: it represents the **latest value per key**, like a database table continuously updated. A new record for a key is an **update** (upsert); a record with a null value is a **delete** (tombstone).

```text
Topic customer-profiles (key = customerId):
C1 → {city: Pune}
C2 → {city: Delhi}
C1 → {city: Mumbai}     ← replaces C1's previous value

KTable state: C1 → Mumbai, C2 → Delhi
```

## Creating and using
```java
KTable<String, Customer> customers = builder.table("customer-profiles",
        Materialized.as("customers-store"));          // backed by a local state store

// Aggregations produce KTables
KTable<String, Long> ordersPerCustomer = orders.groupByKey().count();

// Enrich a stream with the current table value
KStream<String, EnrichedOrder> enriched = orders.join(customers,
        (order, customer) -> new EnrichedOrder(order, customer.city()));
```
The table is materialised in a **state store** (RocksDB) with a compacted changelog topic for recovery. Each instance holds only the partitions it owns.

## GlobalKTable
A `GlobalKTable` replicates **all partitions to every instance**, so lookups need no co-partitioning; good for small reference data (currencies, branch codes, job categories).

## Typical uses
Current customer profile, account balance, product price, inventory per SKU, latest job status: any "current state per key".

## Summary
A KTable is a continuously updated table holding the latest value per key, built from a compacted topic or an aggregation, and used for lookups, joins and current-state views.
MD,

'KStream versus KTable?' => <<<'MD'
## Core difference
- **KStream:** every record is an independent **event** (insert semantics). "Asha paid ₹500", "Asha paid ₹300" are two payments.
- **KTable:** every record is an **update** to the current value of its key (upsert semantics). "Asha's balance is ₹500", then "Asha's balance is ₹800": only ₹800 matters now.

## Comparison
| | KStream | KTable |
|---|---|---|
| Meaning of a record | a fact that happened | latest state for a key |
| Same key again | another event | replaces previous value |
| Null value | just a record | delete (tombstone) |
| Backing topic | normal topic | often compacted topic |
| State | none (stateless ops) | state store |
| Example | clicks, payments, logins | user profile, balance, stock level |

## Duality
They convert into each other:
- **Stream → table:** aggregate a stream (`groupByKey().count()`, `reduce`), or treat the latest record per key as the value.
- **Table → stream:** `table.toStream()` emits every update as an event (a changelog).

```java
KStream<String, Payment> payments = builder.stream("payments");
KTable<String, Long> totalPaid = payments
        .groupByKey()
        .aggregate(() -> 0L, (acc, p, total) -> total + p.amountPaise(),
                   Materialized.with(Serdes.String(), Serdes.Long()));
totalPaid.toStream().to("total-paid-per-account");
```

## Joins
- stream-table join: enrich each event with the current state (order + customer profile).
- stream-stream join: match events within a time window (payment request + payment confirmation within 10 minutes).
- table-table join: maintain a combined view.

## Summary
A KStream treats records as independent events, a KTable treats them as updates to the latest value per key; aggregations turn streams into tables and `toStream()` turns tables back into streams.
MD,

'What is a state store?' => <<<'MD'
## What it is
A state store is **local storage inside a Kafka Streams instance** used by stateful operations: counts, aggregations, windowed results, joins, deduplication and custom processors. By default it is **RocksDB** on local disk (in-memory stores are also available).

## Fault tolerance through changelog topics
Every update to a store is also written to a compacted **changelog topic** in Kafka (`<application.id>-<store>-changelog`). If an instance dies, the task moves to another instance, which **restores the store by replaying the changelog**. Options:
- `num.standby.replicas=1`: keep warm copies on other instances for fast failover.
- Changelogs are compacted, so restoration reads only the latest value per key.

## Using a store in a custom processor
```java
StoreBuilder<KeyValueStore<String, Long>> seenStore = Stores.keyValueStoreBuilder(
        Stores.persistentKeyValueStore("seen-payment-ids"), Serdes.String(), Serdes.Long());
builder.addStateStore(seenStore);

payments.process(() -> new Processor<String, Payment, String, Payment>() {
    private KeyValueStore<String, Long> seen;
    private ProcessorContext<String, Payment> ctx;
    public void init(ProcessorContext<String, Payment> c) { ctx = c; seen = c.getStateStore("seen-payment-ids"); }
    public void process(Record<String, Payment> r) {
        if (seen.get(r.value().id()) != null) return;          // duplicate: drop
        seen.put(r.value().id(), r.timestamp());
        ctx.forward(r);
    }
}, "seen-payment-ids");
```

## Interactive queries
Stores can be queried directly from the application, e.g. a REST endpoint returning the current count for a company, without a separate database. For keys on other instances, the app forwards the request using the streams metadata.

## Sizing and operations
- Local disk must hold the state of the partitions the instance owns.
- Restoration time grows with state size; use standbys and compaction.
- Windowed stores need retention settings so old windows are removed.

## Summary
State stores are local, RocksDB-backed key-value stores that power stateful stream processing, made fault-tolerant by compacted changelog topics and optional standby replicas.
MD,

'What is a Kafka transaction?' => <<<'MD'
## What it is
A Kafka transaction lets a producer **write to multiple partitions and topics, and commit consumer offsets, as one atomic unit**: either everything becomes visible to `read_committed` consumers or nothing does. It is the foundation of exactly-once processing in Kafka.

## Components
- **Transactional producer** with a unique `transactional.id`.
- **Transaction coordinator** (a broker) that records transaction state in the internal topic `__transaction_state`.
- **Commit/abort markers** written into each involved partition.
- **Consumers with `isolation.level=read_committed`**, which skip records from aborted transactions and wait for open ones.

## Flow
```java
props.put("transactional.id", "invoice-builder-" + instanceId);
KafkaProducer<String, Invoice> producer = new KafkaProducer<>(props);
producer.initTransactions();                       // registers, fences older instances

producer.beginTransaction();
try {
    producer.send(new ProducerRecord<>("invoices", orderId, invoice));
    producer.send(new ProducerRecord<>("ledger-entries", accountId, entry));
    producer.sendOffsetsToTransaction(offsets, consumer.groupMetadata());   // input progress
    producer.commitTransaction();
} catch (ProducerFencedException | OutOfOrderSequenceException e) {
    producer.close();                              // fatal: another instance owns this id
} catch (KafkaException e) {
    producer.abortTransaction();                   // nothing becomes visible
}
```

## What it guarantees and what it does not
- Atomic writes across partitions plus offsets: yes.
- No zombie writes: an old instance with the same `transactional.id` is **fenced**.
- External side effects (database writes, emails, HTTP calls) are **not** part of the transaction.

## Costs
Extra round trips to the coordinator and markers in the log; consumers using `read_committed` see data only after commit, adding latency equal to the transaction duration. Commit in batches (e.g. every 100 ms) rather than per record.

## Summary
Kafka transactions make writes to several partitions plus consumer offset commits atomic, enabling exactly-once read-process-write pipelines inside Kafka.
MD,

'What is transactional.id?' => <<<'MD'
## What it is
`transactional.id` is a **stable, unique identifier for a transactional producer**. Setting it turns on transactions (and idempotence). The transaction coordinator maps it to a producer id and an **epoch**.

## Why it must be stable
Its main job is **fencing zombies**. Suppose instance A is processing partition 0 and hangs (long GC pause). The group moves the work to instance B, which starts with the same `transactional.id`. When B calls `initTransactions()`:
1. The coordinator **aborts** any open transaction of the previous holder.
2. It **increments the epoch**.
3. If A wakes up and tries to send or commit, its old epoch is rejected with `ProducerFencedException`.

So a frozen old instance can never write duplicate or conflicting output.

## Choosing the value
- It must be the **same across restarts** of the same logical producer and **unique** among concurrently running producers.
- Classic approach: one id per input partition, e.g. `orders-processor-<topic>-<partition>`.
- Since Kafka 2.5 (KIP-447), fencing also uses consumer group metadata (`sendOffsetsToTransaction(offsets, groupMetadata)`), so one id per application instance (e.g. pod ordinal) is sufficient.

```properties
transactional.id=payment-processor-${HOSTNAME}     # StatefulSet: payment-processor-0, -1, -2
```
Spring Kafka: `spring.kafka.producer.transaction-id-prefix=tx-` generates ids per producer.

## Expiry
If a transactional id is unused for `transactional.id.expiration.ms` (7 days), the coordinator forgets it.

## Summary
`transactional.id` identifies a transactional producer across restarts so the coordinator can abort its unfinished transactions and fence older instances via epochs; keep it stable and unique.
MD,

'What does read_committed do?' => <<<'MD'
## What it does
`isolation.level=read_committed` makes a consumer **return only records from committed transactions** (plus non-transactional records). Records from aborted transactions are filtered out, and records from transactions still open are held back.

The default is `read_uncommitted`, which returns everything, including records that are later aborted.

```properties
isolation.level=read_committed
```

## How it works
- The broker tracks the **Last Stable Offset (LSO)**: the offset before the earliest still-open transaction.
- A `read_committed` consumer can read only up to the LSO.
- Aborted records are skipped using the aborted-transaction index the broker returns with fetches.

## Example
```text
Partition: [A][B][T1:x][T1:y][C][T1 commit marker]
read_uncommitted: A B x y C
read_committed:   A B (waits until T1 commits) x y C
If T1 aborts:     A B C
```

## Consequences
- **Correctness:** downstream services never act on output from failed transactions, essential for exactly-once pipelines (e.g. ledger entries).
- **Latency:** a long-running transaction blocks readers of that partition until it ends (lag grows). Keep transactions short and set `transaction.timeout.ms` sensibly (default 60 s).
- A hung producer with an open transaction stalls `read_committed` consumers until the timeout aborts it.

## When to use it
Any consumer of topics written transactionally, including Kafka Streams with exactly-once (it sets this automatically). For topics with no transactional producers, it makes no difference.

## Summary
`read_committed` hides aborted and in-progress transactional records by reading only up to the last stable offset, which is required for exactly-once but adds latency while transactions are open.
MD,

'What is log compaction?' => <<<'MD'
## What it is
Log compaction is a retention mode (`cleanup.policy=compact`) where Kafka keeps **at least the latest record for every key** and removes older records for keys that have newer values. Instead of deleting by age, the topic becomes a durable, replayable **snapshot of the current state per key**.

```text
Before: [K1:a][K2:b][K1:c][K3:d][K2:null][K1:e]
After:  [K3:d][K1:e]          (K2 deleted by tombstone after delete.retention.ms)
```
Offsets of surviving records do not change; compaction only removes records.

## How it works
- The log is split into a **head** (recent, uncompacted, includes the active segment) and a **tail** (cleaned).
- A background **log cleaner** thread rewrites older segments, keeping only the newest record per key.
- Settings:
```properties
cleanup.policy=compact
min.cleanable.dirty.ratio=0.5      # clean when 50% of the log is "dirty"
min.compaction.lag.ms=0            # minimum age before a record can be compacted
max.compaction.lag.ms=...          # force compaction within this time
delete.retention.ms=86400000       # how long tombstones stay visible
segment.ms=604800000
```
- `cleanup.policy=compact,delete` combines compaction with time-based deletion.

## Guarantees
- A consumer reading from the beginning sees at least the final value of every key.
- Ordering of records is preserved.
- Consumers that keep up see every update before it is compacted (if they are faster than the compaction lag).

## Built-in users
`__consumer_offsets`, `__transaction_state`, Kafka Streams changelogs and Kafka Connect config/offset/status topics are compacted.

## Summary
Log compaction keeps the latest value per key (and removes deleted keys after tombstones), turning a topic into a durable, replayable current-state table.
MD,

'When should compacted topics be used?' => <<<'MD'
## Use compacted topics when the topic represents **current state per key**
1. **Entity state / reference data:** customer profiles, product catalogue, job postings, branch IFSC codes, exchange rates. New services can bootstrap the full current state by reading the topic from the start.
2. **Kafka Streams state stores:** changelogs for tables and aggregations (automatic).
3. **Caches and materialised views:** a service rebuilds its in-memory cache or search index from the topic after a restart.
4. **Configuration distribution:** feature flags or settings per key.
5. **CDC "latest row" topics:** database tables mirrored as one record per primary key.
6. **Offsets and checkpoints:** like `__consumer_offsets`.

## Do not use compaction when
- Every event matters (payments, clicks, ledger entries, audit logs): compaction would delete history. Use time retention or infinite retention instead.
- Records have no key (compaction requires keys; produce fails for null keys on compacted topics).
- You need data to disappear after a fixed time (compaction alone keeps the latest value forever; use `compact,delete`).

## Design tips
- Choose the key to be the entity id; the value is the full latest state (not a partial delta), so the latest record alone is enough.
- Use tombstones to delete entities (including for privacy requests).
- Tune `min.compaction.lag.ms` if consumers must see every intermediate update.

## Example
Topic `jobs.current` (key = jobId, compacted): the search indexer and recommendation service each rebuild their state by reading from offset 0, getting exactly one latest record per live job.

## Summary
Use compacted topics for latest-state data keyed by entity (profiles, catalogues, changelogs, caches), not for event histories where every record matters.
MD,

'What is a tombstone?' => <<<'MD'
## What it is
A tombstone is a record with a **key and a null value**. On a compacted topic it means "**delete this key**": the log cleaner removes older records for that key, and later removes the tombstone itself after `delete.retention.ms`.

```java
producer.send(new ProducerRecord<>("customer-profiles", "C42", null));   // delete customer C42
```

## Lifecycle
1. The tombstone is appended like any record.
2. During compaction, all earlier records for `C42` are removed.
3. The tombstone stays for `delete.retention.ms` (default 24 h) so consumers that read the topic can see the deletion and remove `C42` from their own stores.
4. After that, the tombstone is removed too, so the key leaves no trace.

A consumer offline for longer than `delete.retention.ms` may miss the deletion; size this setting to your consumers' maximum downtime.

## Where tombstones appear
- **Kafka Streams:** a KTable treats null values as deletes and emits tombstones to changelogs.
- **Debezium CDC:** a database `DELETE` produces a delete event followed by a tombstone so the compacted topic drops the row.
- **Sink connectors:** e.g. Elasticsearch with `behavior.on.null.values=delete` deletes the document.
- **Privacy:** removing a user's data from compacted topics on request (GDPR, India's DPDP Act).

## Consumer handling
Code must handle null values: deserialisers and listeners should not crash on them.
```java
@KafkaListener(topics = "customer-profiles")
public void on(ConsumerRecord<String, Customer> r) {
    if (r.value() == null) cache.remove(r.key()); else cache.put(r.key(), r.value());
}
```

## Summary
A tombstone is a key with a null value that deletes the key from a compacted topic, kept for `delete.retention.ms` so consumers can apply the deletion.
MD,

'What if a replica falls behind?' => <<<'MD'
## What happens
A follower that has not caught up with the leader's log end for longer than `replica.lag.time.max.ms` (default 30 s) is **removed from the ISR**. It keeps fetching and replicating; once it catches up, the leader adds it back.

## Causes
- Broker overloaded (CPU, disk I/O saturation, too many partitions).
- Slow or failing disk, network congestion between brokers or availability zones.
- Long garbage collection pauses.
- Broker restarting and catching up after downtime.
- Under-sized `num.replica.fetchers` for a high partition count.

## Effects
- The partition is **under-replicated**: fewer copies of new data.
- With `acks=all`, the producer now needs only the remaining ISR members, so writes continue as long as ISR ≥ `min.insync.replicas`.
- If ISR drops below `min.insync.replicas`, `acks=all` writes fail with `NotEnoughReplicasException`.
- The lagging replica **cannot become leader** (with clean leader election), so if the leader also fails and no other ISR member exists, the partition goes offline.

## Detection
- Metric `UnderReplicatedPartitions` > 0 and `IsrShrinksPerSec` / `IsrExpandsPerSec`.
- `kafka-topics.sh --describe --under-replicated-partitions`.
- Replica fetcher lag metrics (`ReplicaFetcherManager MaxLag`).

## Fixes
- Find the slow broker (disk latency, CPU, network) and fix or replace it.
- Rebalance partition leadership and replicas across brokers (Cruise Control).
- Increase `num.replica.fetchers`, add brokers, reduce partitions per broker.
- Throttle reassignments so recovery does not overload the cluster.

## Summary
A lagging follower is removed from the ISR (reducing redundancy and possibly blocking `acks=all` writes), keeps replicating, and rejoins once caught up; monitor under-replicated partitions and fix the slow broker.
MD,

'What is unclean leader election?' => <<<'MD'
## What it is
Unclean leader election allows an **out-of-sync replica** (not in the ISR) to become leader when **no in-sync replica is available**. It is controlled by `unclean.leader.election.enable` (default **false** since Kafka 0.11).

## The trade-off
| Setting | When all ISR replicas are down |
|---|---|
| `false` (default) | partition stays **offline** until an ISR replica returns: no data loss, no availability |
| `true` | a lagging replica becomes leader: partition is **available**, but records it never received are **lost**, and the old leader truncates them when it returns |

```text
Leader B1 has offsets 0..1000 (ISR = {B1})
Follower B2 has 0..950, out of ISR
B1 dies permanently
unclean=true  → B2 leads; offsets 951..1000 are lost forever; new writes reuse those offsets
unclean=false → partition unavailable until B1 is back
```

## Consistency issues beyond loss
Consumers that already read offsets 951–1000 from the old leader saw data that no longer exists; their committed offsets may point past the new log end, causing resets or skipped data.

## When enabling might be considered
Only for data where availability matters more than completeness (metrics, logs) and the business accepts loss. Never for payments, orders or ledgers.

## Manual last resort
An operator can trigger an unclean election for a specific partition during an emergency:
```bash
kafka-leader-election.sh --bootstrap-server localhost:9092 --election-type unclean --topic metrics --partition 3
```

## Summary
Unclean leader election promotes an out-of-sync replica when no ISR member is alive, restoring availability at the cost of losing acknowledged data; keep it disabled for important topics.
MD,

'What is the Kafka controller?' => <<<'MD'
## What it is
The controller is the component that **manages cluster metadata and coordinates cluster-wide changes**. Only one controller is active at a time.

## Responsibilities
- Tracking which brokers are alive (registrations, heartbeats).
- **Electing partition leaders** when a broker fails or shuts down, and updating ISR changes.
- Creating and deleting topics, adding partitions, partition reassignments.
- Propagating metadata to all brokers so clients get correct routing.
- Managing configuration changes, ACLs, quotas and feature versions.

## Two generations
### ZooKeeper mode (legacy, removed in Kafka 4.0)
- Metadata lived in ZooKeeper.
- One broker was elected controller (via an ephemeral ZooKeeper node).
- On controller failover, the new controller had to **load all metadata from ZooKeeper**, which took a long time with hundreds of thousands of partitions.

### KRaft mode (default since 3.3 for new clusters, only mode in 4.0)
- A small quorum of **controller nodes** (usually 3 or 5) store metadata in an internal Raft-replicated log (`__cluster_metadata`).
- One is the active controller (Raft leader); the others are hot standbys with the full metadata in memory, so **failover is near-instant**.
- Brokers fetch metadata changes from the log like consumers.

```properties
process.roles=controller
node.id=1
controller.quorum.voters=1@ctrl1:9093,2@ctrl2:9093,3@ctrl3:9093
```

## Why it matters operationally
Controller health determines how fast the cluster recovers from broker failures. Metrics: `ActiveControllerCount` must be exactly 1 across the cluster; watch leader election rates and metadata lag.

## Summary
The controller manages metadata and leader elections; in KRaft mode a Raft quorum of controller nodes replaces ZooKeeper, giving faster failover and simpler operations.
MD,

'What is KRaft?' => <<<'MD'
## What it is
**KRaft (Kafka Raft)** is Kafka's built-in metadata management system, introduced by KIP-500. It **removes the dependency on Apache ZooKeeper**: cluster metadata is stored in an internal topic (`__cluster_metadata`) replicated with a Kafka-specific implementation of the **Raft consensus protocol** among controller nodes.

## Timeline
- Kafka 2.8 (2021): early access.
- Kafka 3.3 (2022): production-ready for new clusters.
- Kafka 3.5–3.9: migration path from ZooKeeper, ZooKeeper deprecated.
- **Kafka 4.0 (2025): ZooKeeper removed; KRaft is the only mode.**

## Why it was built
| ZooKeeper mode | KRaft |
|---|---|
| two distributed systems to deploy, secure and monitor | one system |
| controller failover reloads all metadata from ZooKeeper (slow) | standby controllers already have metadata: fast failover |
| practical limit ~200k partitions per cluster | millions of partitions |
| metadata pushed by RPC, could diverge | brokers replay one ordered metadata log |

## Roles
```properties
process.roles=broker              # data node
process.roles=controller          # metadata quorum member
process.roles=broker,controller   # combined mode (small or dev clusters)
controller.quorum.bootstrap.servers=ctrl1:9093,ctrl2:9093,ctrl3:9093
```
Production clusters usually run **3 dedicated controllers** (tolerates one failure) or 5 (tolerates two).

## Setting up a new cluster
```bash
KAFKA_CLUSTER_ID=$(kafka-storage.sh random-uuid)
kafka-storage.sh format -t $KAFKA_CLUSTER_ID -c config/server.properties
kafka-server-start.sh config/server.properties
```

## Summary
KRaft is Kafka's Raft-based internal metadata quorum that replaced ZooKeeper, giving one system to operate, faster controller failover and much larger cluster scale.
MD,

'Is KRaft an alternative to Kafka?' => <<<'MD'
## Answer: no
KRaft is **not** a separate product or a competitor to Kafka. It is **part of Kafka itself**: the mode in which Kafka manages its own metadata using the Raft protocol instead of relying on ZooKeeper.

The confusion comes from the phrase "Kafka without ZooKeeper". What changed is only **where cluster metadata lives and how the controller is elected**:
| | What KRaft replaces | What stays the same |
|---|---|---|
| Metadata store | ZooKeeper ensemble | topics, partitions, replication |
| Controller election | ZooKeeper ephemeral node | producers, consumers, consumer groups |
| | | client APIs, Kafka Streams, Connect |

Applications do not change: producers and consumers connect to brokers exactly as before (`bootstrap.servers`). Only cluster operators see the difference (no ZooKeeper to run; controller nodes configured with `process.roles=controller`).

## What KRaft is an alternative to
**ZooKeeper** as Kafka's metadata system. Since Kafka 4.0, it is the only option; older clusters must migrate from ZooKeeper to KRaft (supported in-place through Kafka 3.9) before upgrading to 4.x.

## Summary
KRaft is Kafka's own Raft-based metadata mode that replaced ZooKeeper; it is Kafka, not an alternative to it, and client applications work unchanged.
MD,

];
