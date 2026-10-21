# OTP Rocket — Full-Stack OTP Services Platform

A complete, production-grade virtual-number and SMS verification marketplace built strictly using **Core PHP 8.2+**, **MySQL / MariaDB via PDO**, **Tailwind CSS**, and **Vanilla JavaScript** (Zero `.ts` / `.tsx` files).

## Features

### 1. User Panel
- **Registration & Authentication**: Secure session-based login, password hashing (`bcrypt`), CSRF protection, and lockout policies.
- **Service Browsing**: Live search and filtering across virtual-number services (Google, WhatsApp, Telegram, Facebook, Instagram, Twitter/X, etc.).
- **Order Placement & Tracking**: Instant number allocation, live countdown timer (20 minutes), real-time OTP polling, copy-to-clipboard, and authorized cancellation with instant wallet refunds.
- **Wallet & Transactions**: Real-time balance, Razorpay online gateway integration, manual UPI QR deposit requests, and immutable audit logs.
- **Support & Profile**: Support ticket submission, password updates, and notification feeds.

### 2. Super Admin Panel
- **Single Super Admin**: Strict single Super Admin model (`admin` / `Admin@123456`) with comprehensive security logging.
- **Service Importer**: Automated sync and batch import tool with duplicate detection and custom margin adjustments.
- **Service & Country Management**: Control active services, categories, country flags, provider mappings, and custom selling prices.
- **Live Order & Transaction Monitoring**: Monitor active verifications, manually trigger OTP checks, cancel stale requests, and inspect payments.
- **Reports & Analytics**: 7-day revenue trend chart via Chart.js, KPI breakdown, top-performing services.
- **Settings**: Complete configuration for platform details, Razorpay credentials, brute-force defenses, session lifetimes, and IP restrictions.

## Default Credentials

- **Super Admin Panel**: `/admin/login.php`
  - Username: `admin`
  - Password: `Admin@123456`
- **User Panel**: `/login.php`
  - Username / Email: `azlen` (or `user@example.com`)
  - Password: `User@123456`

## Background Jobs & Automation (Cron)

- `cron/sync-services.php`: Synchronizes services and real-time stock from providers.
- `cron/check-orders.php`: Polls active pending orders for incoming OTP verification codes.
- `cron/cleanup-expired.php`: Automatically cancels orders older than the timeout period and issues instant wallet refunds.
- `cron/reconcile-payments.php`: Expires abandoned payment checkout sessions.

## Technology Stack

- **Backend**: Core PHP 8.2+ with PDO & prepared statements
- **Database**: MariaDB / MySQL 8+ with InnoDB transactions and row-level locking
- **Styling**: Tailwind CSS compiled to static CSS (`assets/css/output.css`)
- **Frontend**: Vanilla JavaScript (ES6+), Alpine.js, SweetAlert2, Chart.js
- **Payment Gateway**: Razorpay (API & Webhooks) + Direct UPI
