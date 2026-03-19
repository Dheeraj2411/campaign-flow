## PRD: Campaign Flow Scalability, Queue, and Architecture Improvements

Problem statement:
- Bulk campaign dispatch and per-message jobs create huge queue pressure.
- Campaign state finished before all recipients complete, causing false "success".
- Single queue channel (`default`), no priority, no worker separation.
- Potential tenant data leak via static `TenantContext`.
- API rate-limits (WhatsApp/Telegram) aren’t enforced.
- DB hotspots on `message_logs` and realtime writes in high-throughput flows.

Goals:
- Reliable campaign completion status (`running` → `completed/failed/partial`).
- Scalable messaging throughput for 100k+ contact campaign volume.
- Prioritized worker topology with multiple queues/supervisors.
- Rate-limit protection for each provider per tenant.
- Operational observability: queue age, throughput, failures.
- Maintain tenancy isolation and state cleanliness.

Target architecture:
- Redis default production queue.
- Named queues: `campaign-dispatch`, `campaign-send`, `conversation-send`, `notifications`, `imports`.
- Horizon multi supervisors, auto scaling strategy.
- `DispatchCampaignJob` uses chunk/`CampaignSendChunkJob`, batch or counters, and correct status transitions.
- Per-job queue via `.onQueue('...')` and explicit `timeout`, `tries`, `backoff`.
- Reset `TenantContext::setWorkspace(null)` in job finally.

Data model and DB ops:
- Add indexes: `message_logs(campaign_id, status)`, `message_logs(contact_id)`, `conversations(workspace_id, contact_id, platform)`.
- Partition/cleanup message logs after 90 days, archive history.

Rate limiting & throttling:
- `RateLimiter::for('whatsapp', ...)` and `telegram`. 30 req/min per tenant.
- `SendMessageJob` uses `ShouldBeThrottled` or release + delay.
- Handle 429 with retry/backoff/circuit break.

Observability and health:
- `failed_jobs` with `database-uuids`; hook `LongWaitDetected`, `JobFailed`.
- Metrics: campaign processed, send latency, failure rate.
- Dashboards + alerts on queue depth, worker restarts, API 429 spikes.

Acceptance criteria:
- `QUEUE_CONNECTION=redis` in env.
- Horizon has multiple queues + supervisors.
- `DispatchCampaignJob` waits child job completion (batch or counter).
- campaign status reflects actual message results.
- 100k contacts : queue backlog stays bounded.
- per-tenant rate limit avoids 429 storm.
- no tenant context leak.
- DB indexes in place and no full scan for message lookup.

Roadmap:
1. Quick: redis queue + horizon config, queue names, job timeouts, lifecycle fix.
2. Hardening: batch/Chunk job, indexing/pruning, rate limit.
3. Scale: tenant queue slicing, optional message-dispatch microservice, event bus.

Non-functional:
- multi-tenant safe.
- idempotent jobs.
- consistent eventual state.
- HA workers.
- support 10x volume without 2x task-time drift.

Optional long-term:
- event sourcing of message state.
- k8s HPA by backlog.
- SQS FIFO for ordered tenant throughput.
- droppable low-priority message history.
