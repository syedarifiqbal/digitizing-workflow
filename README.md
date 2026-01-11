# Digitizing Workflow SaaS

A multi-tenant SaaS application for digitizing/embroidery businesses to manage orders from intake to delivery.

## Features

- **Multi-tenant Architecture** - Each company has isolated data with `tenant_id` scoping
- **Order Management** - Track orders through complete lifecycle
- **Designer Assignment** - Assign and reassign orders to designers
- **Versioned Submissions** - Track design submissions with version history
- **Revision Loop** - Handle revision requests with attachments
- **File Delivery** - Secure file delivery to clients
- **Commission Tracking** - Flexible commission rules (fixed, percentage, hybrid)
- **Client Portal** - Self-service portal for clients
- **API Integration** - REST API for external order intake
- **Webhooks** - Real-time notifications for order events

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** Vue 3 + Inertia.js
- **Styling:** Tailwind CSS v4
- **Database:** MySQL
- **Queue:** Redis
- **Auth:** Laravel + Spatie Permission

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+
- Redis

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/digitizing-workflow.git
   cd digitizing-workflow
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database**

   Update `.env` with your database credentials:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=digitizing_workflow
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

5. **Configure Redis** (for queues)
   ```
   QUEUE_CONNECTION=redis
   CACHE_STORE=redis
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Run the application**
   ```bash
   # Terminal 1 - Laravel server
   php artisan serve

   # Terminal 2 - Vite dev server
   npm run dev
   ```

8. Visit `http://localhost:8000`

## Development

### Running Queue Worker
```bash
php artisan queue:work
```

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
```

## Project Structure

```
app/
├── Actions/          # Single-purpose action classes
├── Http/
│   ├── Controllers/  # Route controllers
│   ├── Middleware/   # Custom middleware
│   └── Requests/     # Form requests
├── Models/           # Eloquent models
│   └── Traits/       # Model traits (BelongsToTenant)
├── Notifications/    # Email notifications
├── Policies/         # Authorization policies
├── Services/         # Business logic services
└── Jobs/             # Queue jobs

resources/js/
├── Components/       # Reusable Vue components
├── Layouts/          # Page layouts
│   ├── AppLayout.vue     # Authenticated layout
│   └── PublicLayout.vue  # Marketing pages layout
└── Pages/            # Inertia pages
```

## User Roles

| Role | Description |
|------|-------------|
| Admin | Full access within tenant |
| Manager | Same as Admin, limited settings access |
| Designer | View assigned orders, submit work |
| Client | Create orders, view deliveries |

## Order Statuses

```
RECEIVED → ASSIGNED → IN_PROGRESS → SUBMITTED → IN_REVIEW
                                                    ↓
                         REVISION_REQUESTED ←───────┤
                                ↓                   ↓
                         IN_PROGRESS            APPROVED → DELIVERED → CLOSED
```

## License

MIT License - see [LICENSE](LICENSE) file for details.
