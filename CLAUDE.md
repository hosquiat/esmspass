# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is an internal web application for securely storing and managing shared company information (passwords, contact information, codes/IDs, and secret notes). The application uses Google OAuth as the exclusive authentication method - no username/password login is supported.

**Tech Stack:**
- Backend: Laravel 11.x with PHP 8.2+
- Frontend: Hybrid Blade templates + Vue 3 (Composition API)
- Database: PostgreSQL (primary), MySQL supported as alternative
- Styling: Tailwind CSS
- Build Tool: Vite
- Authentication: Laravel Socialite (Google OAuth2) + Laravel Sanctum for API
- Deployment: Docker-first approach

## Architecture

### Hybrid Blade + Vue Approach

The application uses a hybrid architecture where:
- **Blade templates** provide the main layout, auth pages, and page shells
- **Vue 3 components** are mounted within Blade views for dynamic app-like functionality
- Vue components communicate with the backend via API routes (`/api/*`)
- Web routes (`routes/web.php`) serve Blade views
- API routes (`routes/api.php`) handle Vue component requests

This differs from a pure SPA approach - each page is initially rendered by Blade, then Vue components take over for interactivity.

### Authentication Flow

Google OAuth is the **only** authentication method:
1. Login page (`/login`) displays "Sign in with Google" button
2. Button redirects to `/auth/google/redirect` (Socialite redirect)
3. Google returns to `/auth/google/callback` with user info
4. Backend creates/updates user record with `google_id`, `email`, `name`, `avatar`
5. User is logged in via Laravel session
6. Sanctum token/session created for API access

**Critical**: The `users` table includes `google_id` field for OAuth identification. The `password` field is nullable and unused (included only for future flexibility).

### Data Model - Records System

The core entity is `Record` (table: `records`) which stores all types of information:
- **Type field** determines record category: `password`, `contact`, `code`, or `note`
- **JSON data column** stores type-specific fields (recommended approach for this small internal tool)
- **Soft archiving**: Uses `is_archived` boolean instead of soft deletes
- **Audit fields**: `created_by` and `updated_by` track user actions

Type-specific data structure (in JSON `data` column):
- **password**: `username`, `password` (encrypted), `url`/`system_name`
- **contact**: `name`, `company`, `email`, `phone`, `role`, `address`
- **code**: `code_value`, `code_type`, `related_system`
- **note**: additional rich text or structured info

### Security Considerations

**Encryption**: Sensitive fields in the `data` JSON column (like passwords, license keys) must be encrypted using Laravel's encrypted casts or `encrypt()`/`decrypt()` functions. This uses `APP_KEY` from `.env`.

**Authorization**:
- Two roles: `admin` and `user`
- Laravel policies (e.g. `RecordPolicy`) enforce record-level permissions
- Admin users can manage users and perform hard deletes
- Regular users can only CRUD their own records

**Domain Restriction**: Optionally restrict to specific email domains (e.g. `@company.com`) via `.env` configuration during Google OAuth callback.

## Docker Setup

The application is Docker-first with these services:
- **app**: Laravel application (PHP-FPM)
- **web**: Nginx web server (proxies to app:9000)
- **db**: PostgreSQL database

### Key Docker Files
- `Dockerfile`: Multi-stage build (build stage: Composer + npm, runtime stage: PHP-FPM)
- `docker-compose.yml`: Orchestrates app, web, db services
- `docker/nginx/default.conf`: Nginx configuration for Laravel

### Environment Configuration
Database connection uses service name `db` as host:
```
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
```

Google OAuth redirect URI must match the exposed port:
```
GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback
```

## Development Commands

### Initial Setup
```bash
# Copy environment file
cp .env.example .env

# Edit .env with DB credentials and Google OAuth keys
# Then build and start containers
docker-compose up -d --build

# Generate application key
docker-compose exec app php artisan key:generate

# Run migrations and seed admin user
docker-compose exec app php artisan migrate --seed
```

### Daily Development
```bash
# Start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Run tests
docker-compose exec app php artisan test

# Access Laravel tinker
docker-compose exec app php artisan tinker

# View logs
docker-compose logs -f app
```

### Frontend Development
```bash
# Install npm dependencies (if not using Docker build)
docker-compose exec app npm install

# Build assets for development
docker-compose exec app npm run dev

# Build assets for production
docker-compose exec app npm run build

# Watch for changes (development)
docker-compose exec app npm run dev
```

### Production-Like Optimization
```bash
# Cache configuration, routes, and views
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Clear all caches
docker-compose exec app php artisan optimize:clear
```

### Database Operations
```bash
# Create new migration
docker-compose exec app php artisan make:migration create_example_table

# Run specific migration
docker-compose exec app php artisan migrate --path=/database/migrations/2024_01_01_000000_create_example_table.php

# Rollback last migration
docker-compose exec app php artisan migrate:rollback

# Fresh migration (drop all tables and re-migrate)
docker-compose exec app php artisan migrate:fresh --seed

# Backup database
docker-compose exec db pg_dump -U teamvault_user teamvault > backup.sql
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/     # API and web controllers
│   ├── Resources/       # JSON API resources (e.g. RecordResource)
│   └── Requests/        # Form request validation classes
├── Models/              # Eloquent models (User, Record)
└── Policies/            # Authorization policies (RecordPolicy)

resources/
├── views/
│   ├── layouts/         # Main Blade layouts (app.blade.php)
│   ├── auth/            # Login page
│   └── records/         # Records views (index, show)
└── js/
    ├── app.js           # Vue app initialization
    └── components/      # Vue SFCs (RecordsDashboard, RecordForm, RecordDetail)

routes/
├── web.php              # Blade view routes + Google OAuth routes
└── api.php              # API routes for Vue components

database/
├── migrations/          # Database schema migrations
└── seeders/             # Database seeders (admin user seeder)

docker/
└── nginx/
    └── default.conf     # Nginx configuration
```

## API Routes

### Authentication (Web Routes)
- `GET /login` - Login page (Blade)
- `GET /auth/google/redirect` - Redirect to Google OAuth
- `GET /auth/google/callback` - Handle Google OAuth callback
- `POST /logout` - Logout user

### Records API
- `GET /api/records` - List with search/filter/pagination (`?type=password&search=office&archived=0`)
- `POST /api/records` - Create new record
- `GET /api/records/{record}` - Show single record
- `PUT /api/records/{record}` - Update record
- `PATCH /api/records/{record}/archive` - Archive record
- `PATCH /api/records/{record}/restore` - Restore archived record
- `DELETE /api/records/{record}` - Hard delete (admin only)

### Search & Filter Parameters
- `search` - Full-text search across title, description, tags, group/client
- `type` - Filter by record type (password, contact, code, note)
- `tag` - Filter by specific tag
- `archived` - Filter by archived status (0=active, 1=archived, all=both)
- `sort` - Sort order (default: updated_at DESC)

## Key Implementation Details

### Admin User Seeding
The seeder should check for admin emails in `.env`:
```php
// In DatabaseSeeder or dedicated AdminUserSeeder
$adminEmails = explode(',', env('ADMIN_EMAILS', ''));
```

### Record Data Casting
Use Laravel's `casts` property for JSON and encryption:
```php
protected $casts = [
    'data' => 'encrypted:array', // or handle encryption manually
    'is_archived' => 'boolean',
];
```

### Vue Component Mounting
In Blade views, pass data to Vue via data attributes:
```blade
<div id="records-dashboard" data-user="{{ auth()->user()->id }}"></div>
```

### Sanctum Configuration
Since this uses a hybrid approach (not pure SPA), configure Sanctum for session-based API authentication. The Vue components should send requests with CSRF tokens.

## Testing Approach

- Use Laravel's built-in testing (PHPUnit/Pest)
- Test Google OAuth flow with mocked Socialite responses
- Test record CRUD operations with different user roles
- Test encryption/decryption of sensitive fields
- Test authorization policies (ensure users can't access others' records if that's a requirement)

## Future Enhancements Noted in Spec

- 2FA on top of Google OAuth
- Audit logging (who viewed/changed what)
- VPN-only or IP-restricted access
- Potential to split into separate SPA frontend (Vue on Vercel) + Laravel API backend
- Switching from PostgreSQL to MySQL (just change `DB_CONNECTION` and update Dockerfile PHP extensions)
