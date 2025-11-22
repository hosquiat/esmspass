# Context Dump for Claude Code

> **Last Updated**: 2025-11-21 21:58:46
> **Purpose**: This file contains essential context for continuing work after a context reset

## Current Session Summary

### Recent Work Completed
- Implemented dual authentication system (email/password + Google OAuth)
- Created admin user seeding (admin@example.com / changeme)
- Built user management UI in Settings page
- Enabled local database connectivity (PostgreSQL on port 5433)
- Fixed Tailwind CSS v4 opacity syntax in modal overlays

### Active Features
1. **Authentication**
   - Dual auth: Email/password and Google OAuth
   - Auto-linking Google accounts to existing email accounts
   - Password change enforcement for "changeme" default password
   - Strong password requirements (min 8 chars, uppercase, lowercase, number, special char)

2. **User Management**
   - Admin can view all users in Settings page
   - Role assignment (admin/user) with real-time updates
   - User deletion with safeguards (can't delete self, can't delete last admin)
   - Shows login type (Email/Google/Both) with visual badges

3. **Records Management**
   - CRUD operations for password/contact/code/note records
   - Encryption for sensitive data
   - Change tracking with audit logs
   - Archive/restore functionality
   - Search and filtering by type

## Pending Tasks

None - all recent tasks completed successfully.

## Recent File Changes

### Authentication System
- `app/Http/Controllers/Auth/BasicAuthController.php` - Email/password login
- `app/Http/Controllers/Auth/GoogleAuthController.php` - Google OAuth with auto-linking
- `app/Http/Middleware/EnsurePasswordIsChanged.php` - Password change enforcement
- `resources/views/auth/login.blade.php` - Dual auth login form
- `resources/views/auth/change-password.blade.php` - Password change form

### User Management
- `app/Http/Controllers/Admin/UserController.php` - User management API
- `resources/js/components/SettingsPage.vue` - User management UI
- `database/seeders/AdminUserSeeder.php` - Default admin user seeding

### Database & Infrastructure
- `docker-compose.yml` - PostgreSQL exposed on port 5433
- `docker/entrypoint.sh` - Runs AdminUserSeeder after migrations
- `DATABASE_CONNECTION.md` - Local database connection guide

### Frontend Fixes
- `resources/js/components/RecordForm.vue` - Tailwind v4 opacity syntax
- `resources/js/components/RecordsDashboard.vue` - Tailwind v4 opacity syntax
- `resources/js/components/SettingsPage.vue` - Tailwind v4 opacity syntax

## Key Configurations

### Environment Variables (.env)
```env
# App
APP_NAME=TeamVault
APP_URL=http://localhost:8000
APP_PORT=8000

# Database
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=teamvault
DB_USERNAME=teamvault_user
DB_PASSWORD=secret
DB_EXTERNAL_PORT=5433  # For local connections

# Default Admin User
DEFAULT_ADMIN_EMAIL=admin@example.com
DEFAULT_ADMIN_PASSWORD=changeme

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id_here
GOOGLE_CLIENT_SECRET=your_google_client_secret_here
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Session & Auth
SESSION_DRIVER=database
SESSION_LIFETIME=120
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000
```

### Database Schema

**Users Table:**
- `id`, `name`, `email`, `password` (nullable for Google-only accounts)
- `google_id` (nullable), `email_verified_at`
- `role` (enum: 'user', 'admin')
- `created_at`, `updated_at`

**Records Table:**
- `id`, `user_id`, `type` (enum: 'password', 'contact', 'code', 'note')
- `title`, `description`, `group`, `tags` (JSON)
- `data` (JSON, encrypted), `is_archived`
- `created_at`, `updated_at`

**Record Changes Table:**
- `id`, `record_id`, `user_id`, `field_name`, `old_value`, `new_value`
- `created_at`

### Docker Setup

**Containers:**
- `teamvault_app` - Laravel 11 + Vue 3 (Alpine Linux, PHP 8.3)
- `teamvault_db` - PostgreSQL 16

**Ports:**
- App: `8000` (HTTP)
- Database: `5433` (external) -> `5432` (internal)

**Volumes:**
- `postgres_data` - Database persistence

### Tech Stack

**Backend:**
- Laravel 11
- PHP 8.3
- PostgreSQL 16
- Laravel Socialite (Google OAuth)
- bcrypt password hashing

**Frontend:**
- Vue 3 (Composition API)
- Tailwind CSS v4 (uses `@import 'tailwindcss'` syntax)
- Vite build tool

**Infrastructure:**
- Docker & Docker Compose
- Multi-stage Docker builds
- Alpine Linux base images

## Important Technical Notes

### Tailwind CSS v4 Syntax
Project uses Tailwind v4 which has different opacity syntax:
- ❌ Old (v3): `bg-gray-500 bg-opacity-75`
- ✅ New (v4): `bg-gray-500/75`

### Password Security
- Strong password validation regex: `/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/`
- bcrypt hashing with 12 rounds
- "changeme" password triggers change enforcement via session flag

### Google OAuth Auto-Linking
When user logs in with Google:
1. Check for existing user by email OR google_id
2. If found, update google_id (preserve existing role)
3. If not found, create new user with 'user' role

### User Management Safeguards
- Can't delete yourself
- Can't delete last admin user
- Can't change your own role
- Real-time validation on all operations

### Database Connectivity
- PostgreSQL accessible from local machine on port 5433
- Use any database client (psql, TablePlus, pgAdmin, DBeaver, DataGrip)
- Connection string: `postgresql://teamvault_user:secret@localhost:5433/teamvault`

## Git Repository

**Remote:** `https://github.com/hosquiat/esmspass.git`
**Branch:** `main`
**Latest Commit:** Tailwind v4 opacity syntax fixes

## Next Steps After Context Reset

1. Verify Docker containers are running: `docker compose ps`
2. Check application status: `curl -I http://localhost:8000`
3. Verify database connectivity: `psql -h localhost -p 5433 -U teamvault_user -d teamvault`
4. Review any new user requests or bug reports
5. Continue with feature development or maintenance tasks

## Common Commands

```bash
# Start application
docker compose up -d

# View logs
docker compose logs -f app

# Run migrations
docker exec teamvault_app php artisan migrate

# Seed admin user
docker exec teamvault_app php artisan db:seed --class=AdminUserSeeder

# Rebuild frontend assets
docker compose down && docker compose up -d --build

# Connect to database
psql -h localhost -p 5433 -U teamvault_user -d teamvault

# Access application shell
docker exec -it teamvault_app sh
```

---

**Note**: This context dump was automatically generated by the context compact script.
