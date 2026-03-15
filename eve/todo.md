# 📋 CampaignFlow — Project Todo & Work Chunks

> **Platform:** Multi-Channel Messaging Campaign SaaS (WhatsApp + Telegram)
> **Stack:** Laravel 12 · Vue 3 · Inertia.js · TailwindCSS · PostgreSQL · Redis · Laravel Horizon · WebSockets · Meilisearch

---

## 🗺️ Overview

| Chunk | Area | Est. Complexity |
|-------|------|----------------|
| [Chunk 1](#chunk-1-project-setup--foundation) | Project Setup & Foundation | 🟡 Medium |
| [Chunk 2](#chunk-2-auth--workspace-management) | Auth & Workspace Management | 🟡 Medium |
| [Chunk 3](#chunk-3-contact-management) | Contact Management | 🟡 Medium |
| [Chunk 4](#chunk-4-campaign-management--templates) | Campaign Management & Templates | 🔴 High |
| [Chunk 5](#chunk-5-messaging-integrations) | Messaging Integrations (WhatsApp + Telegram) | 🔴 High |
| [Chunk 6](#chunk-6-real-time-inbox--two-way-messaging) | Real-Time Inbox & Two-Way Messaging | 🔴 High |
| [Chunk 7](#chunk-7-analytics--billing) | Analytics & Billing | 🟡 Medium |
| [Chunk 8](#chunk-8-deployment--devops) | Deployment & DevOps | 🟡 Medium |

---

## Chunk 1 — Project Setup & Foundation

> **Goal:** A working skeleton with all services wired up and design system in place.

### Backend
- [ ] Initialize Laravel 12 project
- [ ] Configure PostgreSQL database connection
- [ ] Install & configure Redis (queue + cache + session)
- [ ] Install & configure Laravel Horizon
- [ ] Install & configure Laravel WebSockets
- [ ] Install Meilisearch & Laravel Scout
- [ ] Set up AWS S3 (or compatible) file storage driver
- [ ] Set up `.env` with all service credentials

### Frontend
- [ ] Install Vue 3 + Inertia.js + `@inertiajs/vue3`
- [ ] Install & configure TailwindCSS
- [ ] Extend `tailwind.config.js` with dual-palette tokens:
  - `store-primary` → `#F67E15` (orange)
  - `store-secondary` → `#06402B` (forest green)
  - `admin-primary` → `#4020C9` (indigo)
  - `admin-highlight` → `#9477ED` (violet)
  - `admin-alert` → `#AF5134` (terracotta)
- [ ] Set up Google Material Symbols Outlined icon library
- [ ] Create `AdminLayout.vue` — collapsible sidebar, top app bar
- [ ] Create `StoreLayout.vue` — sticky glassmorphism header, footer
- [ ] Create base UI component library:
  - [ ] `<BaseButton>` (accepts `variant="admin|store"`)
  - [ ] `<BaseModal>`
  - [ ] `<DataTable>`
  - [ ] `<BaseCard>`
  - [ ] `<BaseBadge>`
- [ ] Configure dark mode support (`dark:` classes throughout)

### Dev Tooling
- [ ] Configure `vite.config.js` for Vue + Inertia
- [ ] Set up ESLint + Prettier
- [ ] Set up Laravel Pint for PHP formatting

---

## Chunk 2 — Auth & Workspace Management

> **Goal:** Users can register, verify email, log in, reset password, and operate inside isolated workspaces.

### Backend
- [ ] Auth scaffolding (Laravel Breeze or Fortify)
  - [ ] Register
  - [ ] Email verification
  - [ ] Login / Logout
  - [ ] Password reset
- [ ] `workspaces` table migration (id, name, owner_id, plan, settings)
- [ ] `workspace_user` pivot table (workspace_id, user_id, role)
- [ ] Workspace middleware — scope all queries to active workspace
- [ ] Workspace invitation system (invite by email)
- [ ] Platform Admin role — separate guard or role flag

### Frontend
- [ ] Register page (Vue + Inertia)
- [ ] Login page
- [ ] Email verification screen
- [ ] Password reset flow
- [ ] Workspace selector / switcher component
- [ ] Workspace Settings page (name, logo, timezone)
- [ ] User profile page

---

## Chunk 3 — Contact Management

> **Goal:** Businesses can build, import, and segment their customer contact lists.

### Backend
- [ ] `contacts` table migration (id, workspace_id, name, phone, telegram_username, tags[], custom_attributes jsonb)
- [ ] Contacts CRUD API (List, Create, Update, Delete)
- [ ] CSV import — queue job for bulk import
- [ ] API endpoint for programmatic contact import
- [ ] Contact segmentation logic (filter by tags, type, location)
- [ ] Meilisearch indexing for contacts (fast search)
- [ ] Duplicate detection on phone number within workspace

### Frontend
- [ ] Contacts list page — searchable, filterable `<DataTable>`
- [ ] Add contact form / modal
- [ ] Bulk CSV upload UI with progress indicator
- [ ] Segment builder UI (tag-based filter, type, location)
- [ ] Contact detail page (history of conversations + campaigns)
- [ ] Tag management UI

---

## Chunk 4 — Campaign Management & Templates

> **Goal:** Full campaign creation flow — create, draft, schedule, send, and manage templates.

### Backend
- [ ] `campaigns` table migration (id, workspace_id, name, type[whatsapp|telegram|both], status, scheduled_at, contact_list_ids[])
- [ ] `message_templates` table migration (id, workspace_id, name, body, variables[], platform)
- [ ] Campaign CRUD API
- [ ] Template CRUD API
- [ ] Variable interpolation engine (`{name}` → actual contact name)
- [ ] Campaign Scheduler — queue job dispatched at `scheduled_at`
- [ ] Campaign status state machine: `draft → scheduled → running → completed | failed`
- [ ] Rate limiting per workspace plan

### Frontend
- [ ] Campaign list page (status badges, filter by platform)
- [ ] Campaign creation wizard (multi-step):
  - [ ] Step 1: Name + platform selection (WhatsApp / Telegram / Both)
  - [ ] Step 2: Select contact list / segment
  - [ ] Step 3: Compose message or pick template
  - [ ] Step 4: Send now or schedule (datetime picker)
  - [ ] Step 5: Review & confirm
- [ ] Template library page (create, edit, preview, reuse)
- [ ] Campaign detail page (live status, send log)
- [ ] Campaign scheduler UI (calendar / time picker)

---

## Chunk 5 — Messaging Integrations

> **Goal:** Actual messages are dispatched through WhatsApp Cloud API and Telegram Bot API via reliable queues.

### Backend — WhatsApp (Meta Cloud API)
- [ ] Store `whatsapp_phone_number_id` + `access_token` per workspace
- [ ] `WhatsAppMessage` service class (send text, image, document, interactive buttons)
- [ ] WhatsApp webhook endpoint (receive incoming messages + delivery status)
- [ ] Webhook signature validation (X-Hub-Signature-256)
- [ ] Queue job: `SendWhatsAppMessageJob` — dispatched per contact
- [ ] Message delivery tracking (sent → delivered → read / failed)
- [ ] Retry logic for failed messages

### Backend — Telegram (Bot API)
- [ ] Store `bot_token` per workspace
- [ ] `TelegramMessage` service class (send text, image, document, link)
- [ ] Telegram webhook endpoint (`/api/telegram/webhook/{workspace}`)
- [ ] Queue job: `SendTelegramMessageJob`
- [ ] Retry logic for failed messages

### General Messaging
- [ ] `message_logs` table (campaign_id, contact_id, platform, status, sent_at, delivered_at, failed_at)
- [ ] Unified message dispatcher — routes to WhatsApp or Telegram based on campaign type
- [ ] Laravel Horizon dashboard access for queue monitoring

### Frontend
- [ ] Workspace settings: WhatsApp integration setup (token, phone number ID)
- [ ] Workspace settings: Telegram integration setup (bot token)
- [ ] Connection status indicator (connected / not connected)
- [ ] Queue health widget on admin dashboard

---

## Chunk 6 — Real-Time Inbox & Two-Way Messaging

> **Goal:** Businesses see incoming customer replies in real time and can respond — 3-column CRM inbox layout.

### Backend
- [ ] `conversations` table (id, workspace_id, contact_id, platform, last_message_at, status[open|closed])
- [ ] `messages` table (id, conversation_id, direction[inbound|outbound], body, media_url, sent_at)
- [ ] Webhook handlers (WhatsApp + Telegram) write inbound messages to DB + fire WebSocket event
- [ ] `ConversationReply` API endpoint (POST a reply → dispatches message job)
- [ ] Conversation search via Meilisearch
- [ ] Conversation filter (platform, status, date)
- [ ] Mark conversation as read / closed

### Frontend (3-Column Admin Layout)
- [ ] **Column 1:** Admin sidebar (already from Chunk 1)
- [ ] **Column 2:** Inbox feed — scrollable list of conversation cards
  - Avatar, contact name, last message snippet, timestamp, unread badge
- [ ] **Column 3:** Active chat workspace
  - Sticky header (contact name, platform icon, status)
  - Chat bubble area (inbound left, outbound right, distinct colors)
  - Fixed bottom reply input with send button + attachment option
- [ ] WebSocket integration (Laravel Echo) — new messages appear live without refresh
- [ ] Mobile "Drill-Down" flow (inbox → chat, back button to return)
- [ ] Conversation search bar (Meilisearch powered)
- [ ] Filter drawer (by platform, status)

---

## Chunk 7 — Analytics & Billing

> **Goal:** Businesses can measure campaign performance; platform runs on subscription plans.

### Analytics Backend
- [ ] Analytics query service (rollup from `message_logs`)
  - Messages sent / delivered / failed per campaign
  - Customer reply rate
  - Platform breakdown (WhatsApp vs Telegram)
- [ ] Campaign analytics API endpoints
- [ ] Dashboard summary endpoint (total contacts, campaigns this month, messages sent)

### Analytics Frontend
- [ ] Dashboard overview with KPI cards (indigo-to-violet color scale charts)
- [ ] Campaign analytics page (per-campaign breakdown table + chart)
- [ ] Chart component (use Chart.js or ApexCharts, bound to admin palette)

### Billing Backend
- [ ] `subscription_plans` table (basic, pro, enterprise — contact/campaign/message limits)
- [ ] `subscriptions` table (workspace_id, plan_id, status, renews_at)
- [ ] Plan enforcement middleware (block over-limit campaigns)
- [ ] Stripe integration (or local payment gateway)
  - Checkout flow
  - Webhook for payment events (paid, failed, cancelled)
- [ ] Subscription management API

### Billing Frontend
- [ ] Pricing / Plan selection page (3-column plan cards)
- [ ] Current plan indicator in workspace settings
- [ ] Usage meter (contacts used / limit, messages used / limit)
- [ ] Billing history page

---

## Chunk 8 — Deployment & DevOps

> **Goal:** The platform is containerized, deployed, and production-ready.

### Infrastructure
- [ ] `Dockerfile` for Laravel app
- [ ] `docker-compose.yml` — app, queue worker, PostgreSQL, Redis, Meilisearch, WebSockets server
- [ ] Nginx config (reverse proxy, SSL termination)
- [ ] Supervisor config for queue workers (`php artisan queue:work`)
- [ ] Laravel Horizon in production
- [ ] Environment variable management (`.env.production`)

### CI/CD
- [ ] GitHub Actions workflow:
  - Run tests (`php artisan test`)
  - Build Vue assets (`npm run build`)
  - Deploy to VPS on merge to `main`

### Security & Performance
- [ ] HTTPS / SSL (Let's Encrypt via Certbot)
- [ ] API rate limiting (Laravel throttle middleware)
- [ ] Webhook signature validation (WhatsApp + Telegram)
- [ ] Database backups (automated daily)
- [ ] Redis persistence config
- [ ] Content Security Policy headers

### Notification System
- [ ] In-app notifications (campaign complete, failed messages, customer replies, plan updates)
- [ ] Email notifications via Laravel Mail + queue
- [ ] Notification bell UI in admin header (live via WebSockets)

---

## 📌 Key Design Principles (from Design Doc)

| Principle | Implementation |
|-----------|---------------|
| Mobile-First | All layouts: stacked → `md:` → `lg:` breakpoints |
| Soft Geometry | `rounded-xl` on all cards, modals, buttons |
| Icons | Google Material Symbols Outlined (filled = active, outlined = rest) |
| Dark Mode | `dark:bg-slate-900`, `dark:text-white` across all components |
| Admin palette | Indigo `#4020C9` → Violet `#9477ED` for data visualizations |
| Store palette | Orange `#F67E15` for CTAs, Forest Green `#06402B` for navigation |

---

## 🚀 Suggested Build Order

```
Chunk 1 → Chunk 2 → Chunk 3 → Chunk 4 → Chunk 5 → Chunk 6 → Chunk 7 → Chunk 8
Foundation  Auth      Contacts   Campaigns  Messaging  Inbox     Analytics  Deploy
```

> Each chunk is functional enough to demo independently before moving to the next.
