# PingOS 🚀

**PingOS** is a powerful multi-tenant communication platform built with the latest Laravel stack. It enables businesses to manage WhatsApp and Telegram campaigns, interact with customers in real-time, and automate messaging workflows efficiently.

---

## ✨ Features

- **Multi-Tenant Architecture**: Manage multiple independent workspaces with isolated data.
- **WhatsApp & Telegram Integration**: Connect your Meta Business accounts and Telegram bots for seamless communication.
- **Campaign Management**: Create, schedule, and track bulk messaging campaigns.
- **Unified Real-time Inbox**: Centralized dashboard to handle incoming messages across all channels with **Conversation Persistence**.
- **Smart Notifications**: Priority-based notification system with importance color-coding and real-time alerts.
- **Canned Responses**: Save time with pre-defined message templates for common queries.
- **Contact Management**: Import contacts via CSV, organize them, and manage custom metadata.
- **Analytics Dashboard**: Track message delivery, engagement metrics, and campaign performance.
- **Billing & Subscriptions**: Integrated payment gateways (Razorpay, Stripe) for plan management.
- **Admin Control Panel**: Comprehensive dashboard to manage users, plans, and system-wide transactions.

---

## 🛠️ Tech Stack

- **Backend**: [Laravel 12](https://laravel.com), PHP 8.2+
- **Frontend**: [Vue 3](https://vuejs.org) (Composition API), [Inertia.js 2.0](https://inertiajs.com), [Tailwind CSS 4](https://tailwindcss.com)
- **Real-time**: [Laravel Reverb](https://reverb.laravel.com) (WebSockets), Laravel Echo
- **Build Tool**: [Vite 7](https://vitejs.dev)
- **Search**: Laravel Scout (with Meilisearch support)
- **Background Jobs**: Laravel Horizon, Redis
- **Database**: PostgreSQL (Postgres)
- **Payments**: Razorpay, Stripe

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- Docker (optional, but recommended for Redis/Postgres/Meilisearch)

### Installation

1.  **Clone the Repository**:
    ```bash
    git clone https://github.com/your-repo/pingos.git
    cd pingos
    ```

2.  **Install Dependencies**:
    ```bash
    composer install
    npm install
    ```

3.  **Environment Setup**:
    Copy the example environment file and configure your database and API credentials.
    ```bash
    cp .env.example .env
    ```

4.  **Generate Application Key**:
    ```bash
    php artisan key:generate
    ```

5.  **Run Migrations & Seeders**:
    ```bash
    php artisan migrate
    ```

6.  **Build Assets**:
    ```bash
    npm run dev
    ```

7.  **Serve the Application**:
    ```bash
    php artisan serve
    ```

---

## 🔧 Environment Configuration

Key environment variables to configure in your `.env`:

- `DB_CONNECTION`: Database driver (default: `pgsql`)
- `REVERB_APP_ID`, `REVERB_APP_KEY`: WebSocket credentials
- `WHATSAPP_API_TOKEN`: Meta WhatsApp Cloud API token
- `TELEGRAM_BOT_TOKEN`: Your Telegram Bot token
- `RAZORPAY_KEY_ID`, `RAZORPAY_KEY_SECRET`: Razorpay credentials

---

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
