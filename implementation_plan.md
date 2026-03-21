# PingOS — TRD Implementation Plan

Implement the remaining features from the [TRD_PingOS.txt](file:///c:/Users/jhasa/Desktop/whats-App/pingos/eve/TRD_PingOS.txt) across 12 phases. All changes are additive — no existing tables/columns are dropped.

> [!IMPORTANT]
> The TRD specifies a full PostgreSQL migration, but the **existing phpunit.xml uses SQLite in-memory**. Some PostgreSQL-specific syntax (`@>`, `jsonb`, `GIN` indexes, `timestampTz`) cannot be tested against SQLite. I will write migrations that work on pgsql and ensure the code changes compile cleanly. Unit tests will continue to use SQLite where possible, with PostgreSQL-specific queries skipped or stubbed in tests.

> [!WARNING]
> The TRD calls for removing `->after()` from 6 existing migration files. These are **in-place edits to committed migrations** — they should only be applied if the database has not yet been deployed to production, or a fresh migration is planned.

## Proposed Changes

### Phase 1 — PostgreSQL Migration Compatibility

#### [MODIFY] [database.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/config/database.php)
- Change `'default'` to `env('DB_CONNECTION', 'pgsql')`

#### [MODIFY] 6 existing migration files
- Remove all `->after('column')` calls (not supported on Postgres)
- Change [json](file:///c:/Users/jhasa/Desktop/whats-App/pingos/package.json) columns → `jsonb`
- Change `unsignedTinyInteger` / `unsignedSmallInteger` → `smallInteger`
- Change `timestamp` → `timestampTz` where specified in TRD §2.8

#### [NEW] Migration: convert contacts tags/custom_attributes to jsonb + GIN index (TRD §2.1)

---

### Phase 2 — Tags Normalization

#### [NEW] Migration: create `tags` table (TRD §2.2)
#### [NEW] Migration: create `contact_tag` pivot table (TRD §2.3)
#### [NEW] [Tag.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/Tag.php) — Tag model with `scopeForWorkspace()`
#### [MODIFY] [Contact.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/Contact.php) — Add `belongsToMany(Tag::class)` relation
#### [NEW] [TagController.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Http/Controllers/TagController.php) — CRUD endpoints (GET/POST/PUT/DELETE `/tags`)
#### [MODIFY] [web.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/routes/web.php) — Register `/tags` routes
#### [NEW] [TagManager.vue](file:///c:/Users/jhasa/Desktop/whats-App/pingos/resources/js/Components/Tags/TagManager.vue) — Tag create/rename/delete UI

---

### Phase 3 — Message Status History & Funnel Tracking

#### [NEW] Migration: create `message_status_history` table (TRD §2.4)
#### [NEW] Migration: add `failure_reason` + `attempt_count` to `message_logs` (TRD §2.5)
#### [NEW] [MessageStatusHistory.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/MessageStatusHistory.php)
#### [MODIFY] [MessageLog.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/MessageLog.php) — Add `hasMany(MessageStatusHistory)`, add new columns to fillable
#### [MODIFY] [WhatsAppWebhookController.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Http/Controllers/Api/WhatsAppWebhookController.php) — Write to `message_status_history` on status callbacks (TRD §3.6)

---

### Phase 4 — Rate Limiting & Workspace Config

#### [NEW] Migration: add `msg_per_minute` to [workspaces](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/User.php#33-37) (TRD §2.6)
#### [MODIFY] [SendMessageJob.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Jobs/SendMessageJob.php) — Use `$workspace->msg_per_minute ?? 80` for rate limit, update `$backoff` to `[10, 60, 300]`
#### [MODIFY] [Workspace.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/Workspace.php) — Add `msg_per_minute` to fillable

---

### Phase 5 — DispatchCampaignJob Segment Fix & PostgreSQL Query Updates

#### [MODIFY] [DispatchCampaignJob.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Jobs/DispatchCampaignJob.php) — Add `contact_segment_id` support, use `whereRaw("tags @> ?::jsonb")` (TRD §3.3)
#### [MODIFY] [CampaignController.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Http/Controllers/CampaignController.php) — Replace `whereJsonContains` with `whereRaw` (TRD §2.8)
#### [MODIFY] [Contact.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/Contact.php) — Update [scopeFilter()](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/Contact.php#30-44) to use PostgreSQL `@>` operator
#### [MODIFY] [WhatsAppWebhookController.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Http/Controllers/Api/WhatsAppWebhookController.php) — Use `->>>` operator for settings lookup (TRD §2.8)

---

### Phase 6 — Automation Workflow New Actions

#### [MODIFY] [AutomationWorkflowService.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Services/AutomationWorkflowService.php) — Add 5 new action handlers: `assign_conversation`, `change_status`, `delay`, `send_template`, `webhook` (TRD §3.5)
#### [NEW] [ResumeWorkflowJob.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Jobs/ResumeWorkflowJob.php) — Handles delayed workflow resumption

---

### Phase 7 — Horizon Queue Restructure

#### [MODIFY] [horizon.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/config/horizon.php) — Replace 6 supervisors with 3 (`high`, `medium`, [low](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/Workflow.php#8-37)). Campaign-send merges into medium. (TRD §3.4)
#### [MODIFY] [CampaignSendChunkJob.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Jobs/CampaignSendChunkJob.php) — Change `$queue` from `'campaign-send'` to `'medium'`
#### [MODIFY] [SendMessageJob.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Jobs/SendMessageJob.php) — Dispatch to `'medium'` queue

---

### Phase 8 — Frontend: Analytics & Campaign Funnel

#### [NEW] [AnalyticsController.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Http/Controllers/AnalyticsController.php) — `campaignFunnel()` and `workspaceFunnel()` endpoints
#### [NEW] [FunnelChart.vue](file:///c:/Users/jhasa/Desktop/whats-App/pingos/resources/js/Components/Analytics/FunnelChart.vue) — Campaign funnel visualization (ApexCharts)
#### [MODIFY] [Show.vue](file:///c:/Users/jhasa/Desktop/whats-App/pingos/resources/js/Pages/Campaigns/Show.vue) — Integrate FunnelChart component
#### [MODIFY] Routes — Register analytics endpoints

---

### Phase 9 — Chat Inbox Frontend
#### [NEW] [inbox.js](file:///c:/Users/jhasa/Desktop/pingos/resources/js/stores/inbox.js) — Pinia store with Reverb operations
#### [NEW] [ConversationList.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Inbox/ConversationList.vue) — Virtual scroll left panel
#### [NEW] [ConversationItem.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Inbox/ConversationItem.vue) — Single row
#### [NEW] [ChatWindow.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Inbox/ChatWindow.vue) — Right panel with Echo subscription
#### [NEW] [MessageBubble.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Inbox/MessageBubble.vue)
#### [NEW] [MessageInput.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Inbox/MessageInput.vue)
#### [NEW] [ConversationHeader.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Inbox/ConversationHeader.vue)
#### [NEW] [Index.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Pages/Inbox/Index.vue) — Replacement full layout

---

### Phase 10 — Automation Trigger Wiring + Workflow UI
#### [MODIFY] [InboxController.php](file:///c:/Users/jhasa/Desktop/pingos/app/Http/Controllers/InboxController.php) — Fire `conversation_closed`
#### [MODIFY] [AutomationWorkflowService.php](file:///c:/Users/jhasa/Desktop/pingos/app/Services/AutomationWorkflowService.php) — Fire `tag_added`
#### [MODIFY] [WhatsAppWebhookController.php](file:///c:/Users/jhasa/Desktop/pingos/app/Http/Controllers/Api/WhatsAppWebhookController.php) — Detect reply and fire `campaign_replied`
#### [NEW] [ConditionBuilder.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Workflows/ConditionBuilder.vue)
#### [NEW] [ActionBuilder.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Workflows/ActionBuilder.vue)
#### [MODIFY] [Index.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Pages/Workflows/Index.vue) — Update with builders

---

### Phase 11 — Analytics Dashboard + Contact Timeline
#### [NEW] [TrendChart.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Analytics/TrendChart.vue)
#### [MODIFY] [Index.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Pages/Analytics/Index.vue) — Add charts
#### [MODIFY] [CampaignAnalyticsService.php](file:///c:/Users/jhasa/Desktop/pingos/app/Services/CampaignAnalyticsService.php) — Add `getDailyTrend()`
#### [NEW] [ActivityTimeline.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Contacts/ActivityTimeline.vue)
#### [MODIFY] [ContactController.php](file:///c:/Users/jhasa/Desktop/pingos/app/Http/Controllers/ContactController.php) — Add `timeline()`
#### [MODIFY] [web.php](file:///c:/Users/jhasa/Desktop/pingos/routes/web.php) — Timeline routes

---

### Phase 12 — Remaining Small Items
#### [NEW] [TagChip.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Components/Tags/TagChip.vue)
#### [MODIFY] [Index.vue](file:///c:/Users/jhasa/Desktop/pingos/resources/js/Pages/Contacts/Index.vue) — Tag filter and TagChip
#### [MODIFY] [ContactController.php](file:///c:/Users/jhasa/Desktop/pingos/app/Http/Controllers/ContactController.php) — Add `syncTags` endpoint
#### [MODIFY] [channels.php](file:///c:/Users/jhasa/Desktop/pingos/routes/channels.php) — Authorize private-chat channel for Reverb
#### [MODIFY] [.env.example](file:///c:/Users/jhasa/Desktop/pingos/.env.example) — DB_CONNECTION=pgsql
#### [NEW] Migration: contacts.tags Legacy Data Migration
#### [MODIFY] [SendConversationMessageJob.php](file:///c:/Users/jhasa/Desktop/pingos/app/Jobs/SendConversationMessageJob.php) — Set queue to `high`
#### [MODIFY] [web.php](file:///c:/Users/jhasa/Desktop/pingos/routes/web.php) — Explicit analytics funnel routes

---

## Verification Plan

### Automated Tests

1. **Existing unit tests** — Run to ensure no regressions:
   ```
   php artisan test --testsuite=Unit
   ```

2. **New test: `CampaignStatusServiceTest`** — (already exists at [tests/Unit/AutomationWorkflowServiceTest.php](file:///c:/Users/jhasa/Desktop/whats-App/pingos/tests/Unit/AutomationWorkflowServiceTest.php), will add new test alongside):
   ```
   php artisan test --filter=CampaignStatusServiceTest
   ```

3. **New test: `TagControllerTest`** — Feature test for CRUD tag endpoints:
   ```
   php artisan test --filter=TagControllerTest
   ```

4. **Syntax/compilation check** — All modified PHP files compile:
   ```
   php -l app/Jobs/SendMessageJob.php
   php -l app/Jobs/DispatchCampaignJob.php
   php -l app/Http/Controllers/CampaignController.php
   ```

5. **Migration dry-run** (requires PostgreSQL connection):
   ```
   php artisan migrate --pretend
   ```

### Manual Verification

> [!NOTE]
> Many TRD features (PostgreSQL migration, webhook funnel tracking, real-time inbox) require a running PostgreSQL database and WhatsApp/Telegram sandbox. These are best verified in a staging environment.

1. **Migration verification** — After connecting to a PostgreSQL database, run `php artisan migrate` and confirm all tables are created without errors.
2. **Tag CRUD** — Navigate to the Tags management UI component and verify create/rename/delete operations.
3. **Campaign funnel** — Send a test campaign, then check the campaign Show page for the funnel chart rendering sent → delivered → read counts.
4. **Horizon dashboard** — Visit `/horizon` and confirm the 3 supervisors (`high`, `medium`, [low](file:///c:/Users/jhasa/Desktop/whats-App/pingos/app/Models/Workflow.php#8-37)) appear with correct queue assignments.
