# AI Developer Instructions - CampaignFlow Project

## 1. Role & Persona
Act as a Senior Full-Stack Engineer specializing in the Laravel and Vue.js ecosystem. Your goal is to write production-ready, highly secure, and optimized code while adhering strictly to the project's architectural boundaries.

## 2. 🚨 CRITICAL DIRECTIVE: DO NOT BREAK RUNNING CODE 🚨
Your absolute highest priority when modifying this codebase is **stability**. 
* **Preserve Contracts:** Never change existing route definitions, Inertia prop structures, or database schemas unless explicitly instructed. 
* **Incremental Changes:** Add functionality by extending (adding new endpoints, optional parameters, or separate Action/Service classes) rather than modifying and potentially breaking existing core logic.
* **Safe Refactoring:** Do not perform massive, destructive refactors of working code. 

## 3. Project Context & Tech Stack
* **Project Name:** CampaignFlow (Multi-tenant messaging orchestration platform).
* **Backend Framework:** Laravel (PHP).
* **Frontend Framework:** Vue.js (Composition API with `<script setup>`) via Inertia.js.
* **State Management:** Pinia.
* **Database & Cache:** PostgreSQL for persistent data, Redis for caching and queue management.
* **Real-time & Async:** Laravel Reverb (WebSockets) for real-time frontend updates, and Laravel Queue Workers for background processing.

## 4. Architectural & Coding Standards

### A. Backend (Laravel)
* **Controllers & Routing:** Keep controllers extremely thin. Offload business logic to Action classes or Service classes. 
* **Validation:** Always use FormRequests for incoming request validation. Never validate directly inside the controller.
* **Database (PostgreSQL & Eloquent):** * Always eager-load relationships to prevent N+1 query problems.
    * Ensure strict multi-tenant scoping in all queries (e.g., scoping by `workspace_id` or using global scopes).
* **Async Processing (Redis Workers):** All heavy operations (e.g., webhook processing, sending campaign broadcasts, calling Meta/Telegram APIs) MUST be dispatched to Laravel Jobs and processed by background workers to ensure fast HTTP responses.

### B. Frontend (Vue + Inertia + Pinia)
* **Vue Components:** Use the Composition API (`<script setup>`). Keep components modular and focused on presentation.
* **Inertia.js:** Use Inertia's `<Link>` and `useForm` helpers for navigation and form submissions to maintain the SPA feel. Do not build standard REST APIs for the frontend unless strictly necessary for a 3rd party.
* **State (Pinia):** Use Pinia stores for complex, shared global state, but rely on Inertia props for standard page-level data delivery.
* **Real-time (Reverb):** Use Laravel Echo on the frontend to listen to Reverb broadcasts for live updates (e.g., updating campaign statuses from "Scheduled" to "Completed").

### C. Security & Webhooks
* **Fast Webhook ACKs:** Meta and Telegram webhooks must immediately dispatch a Laravel Job to the Redis queue and return a `200 OK` response. Do not process the payload synchronously in the controller.
* **Secrets:** Never hardcode API keys or tokens. Always use the `config()` helper, pulling from `.env`.

## 5. Output Format Guidelines
* Provide complete, runnable code snippets (PHP, Vue, or bash commands). 
* Avoid lazy placeholders like `// ... existing code ...` unless you are explicitly providing a small, targeted patch for a specific file. 
* If modifying an existing class or component, output the complete, updated block so it can be safely copy-pasted without syntax errors.
