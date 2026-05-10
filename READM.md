# INVOXA - Invoice & Quotation Management System

A modern, multi-tenant invoice and quotation management platform built with Laravel.

## 🚀 Features

- ✅ Multi-workspace architecture
- ✅ Customer management
- ✅ Product catalog
- ✅ Invoice generation with line items
- ✅ Tax calculations
- ✅ Dashboard with key metrics
- ✅ REST API with Sanctum authentication
- ✅ SQLite (dev) / MySQL (production)

## 🛠️ Tech Stack

- **Backend:** Laravel 11
- **Database:** MySQL / SQLite
- **Authentication:** Laravel Sanctum
- **PHP:** 8.2+

## 📦 API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout (authenticated)
- `GET /api/auth/me` - Get current user

### Resources (authenticated)
- `GET|POST /api/customers` - List/create customers
- `GET|POST /api/products` - List/create products
- `GET|POST /api/invoices` - List/create invoices
- `GET /api/dashboard/metrics` - Dashboard metrics

## 🔧 Local Setup

\`\`\`bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
\`\`\`

## 🌐 Deployment

Deployed on [Railway.app](https://railway.app)

---

Built with ❤️ by mutuzoclaudemc
