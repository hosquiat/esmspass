You are a senior Laravel + Vue full-stack engineer and a security-minded architect.

I want you to design and build a small internal web app for my company to securely store and manage shared information like:

- Passwords and login details
- Contact information (vendors, partners, key customers)
- Codes and ID numbers (account IDs, customer IDs, license keys, etc.)
- General “secret notes” or internal references

Right now we send these around in email and chat, and we want to stop doing that.

We want to use:

- **Laravel** as the backend framework.
- **Google authentication** (OAuth2) for login, using **Laravel Socialite** (or another robust integration you recommend).
- A **hybrid frontend**:
  - Laravel + Blade for layout, auth pages, and simple views.
  - Vue 3 components for richer “app-like” screens (records dashboard, forms, etc.), built with Vite.
- Tailwind CSS for styling.
- Docker for running the app (locally first, with the ability to move to a VPS/cloud later).

If something is ambiguous, choose a sensible default and briefly explain the choice.

---

## High-level goals

- Centralize company secrets and reference info in one place.
- Support multiple record types: passwords, contacts, codes/IDs, generic notes.
- Basic CRUD for all records with **archiving** instead of hard delete.
- Use **Google Sign-In** (OAuth2) as the authentication method.
- Role-based access control (`admin`, `user`).
- Clean, minimal UI that’s friendly for non-technical teammates.
- Hybrid Blade + Vue architecture.
- Docker-first deployment: easy to run on a local machine or VPS.

---

## Tech stack (required / preferred)

### Backend / Framework

- **Laravel** (latest stable – e.g. 11.x)
- **PHP 8.2+**
- **Laravel Socialite** (or similar) for **Google OAuth2**.
- **Laravel Sanctum** for API authentication between Vue and backend.
- Database: **PostgreSQL** (default). Mention how to swap to MySQL if needed.

### Frontend

- Blade templates for main layout and shell.
- Vue 3 (Composition API, SFCs) mounted in Blade views.
- Tailwind CSS for styling.
- Vite as bundler (default in new Laravel apps).

### Structure

Use and document a clear folder structure:

- `app/Models`, `app/Http/Controllers`, `app/Http/Resources`, `app/Policies`
- `routes/web.php` and `routes/api.php`
- `resources/views` for Blade layouts and pages
- `resources/js` and `resources/js/components` for Vue SFCs
- `database/migrations` and `database/seeders`
- Docker-related files (Dockerfile, docker-compose.yml, nginx config) in a logical structure (e.g. `docker/`).

---

## Authentication via Google OAuth

### Requirements

We will **not** use username/password login. All authentication must go through **Google Login**.

Use **Laravel Socialite** (or similar) to implement Google OAuth2 authentication:

- Configure Google OAuth credentials via environment variables:
  - `GOOGLE_CLIENT_ID`
  - `GOOGLE_CLIENT_SECRET`
  - `GOOGLE_REDIRECT_URI`

### User model

Define the `users` table and `User` model with at least:

- `id`
- `name`
- `email` (unique)
- `google_id` (string, nullable but used for OAuth identification)
- `avatar` (nullable string, can store Google profile picture URL)
- `role` (`admin` | `user`) – string or enum
- timestamps

We do **not** need a local password for login, but you may still include a nullable `password` field for future flexibility. Clarify in comments that login is via Google only.

### Login flow

Implement this flow:

1. **Login page** (Blade):
   - Simple page with a “Sign in with Google” button.
   - Clicking the button redirects to a `web` route like `/auth/google/redirect`.

2. **Google redirect route**:
   - Use Socialite to redirect to Google’s OAuth consent screen.

3. **Google callback route**:
   - Receive the callback from Google.
   - Retrieve the user’s info from Google (id, name, email, avatar).
   - If a `User` with this email already exists:
     - Update `google_id` and avatar if needed.
   - Else:
     - Optionally check allowed domains (e.g. only `@company.com`) if you want.
     - Create a new user record with:
       - `name`
       - `email`
       - `google_id`
       - default `role` (e.g. `user`)
   - Log the user in using Laravel’s auth system (session).
   - Optionally create a Sanctum token/session for API access if necessary.

4. **Logout route**:
   - Standard `POST /logout` route that logs user out and invalidates session.

### Roles and authorization

- Seed at least **one admin user**:
  - You can:
    - Use a seeder that sets a specific email as admin.
    - Or allow a configurable list of admin emails from `.env` (e.g. `ADMIN_EMAILS=...`).
- `admin` role:
  - Can access user management, hard deletes, and other admin-only features.
- `user` role:
  - Can create/view/update records but not manage users.

Use **Laravel policies** (e.g. `RecordPolicy`) or gates to enforce who can view/update/archive/delete records and access admin features.

---

## Data model for stored information

We need to store various record types (passwords, contacts, codes, notes).

Create a `records` table (Eloquent model: `Record`) with at least:

- `id`
- `type` (string: `password`, `contact`, `code`, `note`)
- `title` (e.g. “Office 365 Admin Login”)
- `description` / `notes` (nullable text)
- `tags` (JSON array or comma-separated string; choose one and explain why)
- `group` or `client` (string, optional: system name, project, or company)
- `is_archived` (boolean, default `false`)
- `created_by` (FK to `users.id`)
- `updated_by` (FK to `users.id`, nullable)
- timestamps

### Type-specific fields

Use **one of these approaches** (JSON is recommended for simplicity):

#### Option A – JSON column

Add a `data` JSON column to store type-specific fields:

For `type = "password"`:

- `data.username`
- `data.password` (encrypted; see security section)
- `data.url` or `data.system_name`

For `type = "contact"`:

- `data.name`
- `data.company`
- `data.email`
- `data.phone`
- `data.role`
- `data.address`

For `type = "code"`:

- `data.code_value`
- `data.code_type` (e.g. “Account ID”, “License key”)
- `data.related_system`

For `type = "note"`:

- additional rich text or structured info

#### Option B – Separate detail tables

If you prefer, you can define per-type tables, but JSON is fine for this small internal tool. If you choose JSON, show the migration and how you cast `data` on the model.

---

## CRUD + archiving behavior

For `Record`:

- **Create**:
  - Endpoint (API) and Vue form to create a new record.
  - Fields:
    - `type`
    - `title`
    - `description/notes`
    - `tags`
    - `group/client`
    - type-specific data.

- **Read**:
  - Index/list view displaying:
    - Title, type, group/client, updated_at, archived status.
  - Detail view showing everything.

- **Update**:
  - Endpoint + form for editing all the above.
  - Update `updated_by`.

- **Archive**:
  - Main destructive action:
    - Set `is_archived = true`.
  - Provide API routes and UI for:
    - Archive record.
    - Restore record (`is_archived = false`).

- **Hard delete**:
  - Optional, admin-only.
  - Implement as a separate action/route with confirmation.

Use Form Request classes for validation and policies to ensure only authorized users can modify records.

---

## Search, filters, and organization

Implement server-side query capabilities (in the API):

- **Search** (`search` query param):
  - Match on:
    - `title`
    - `description/notes`
    - `tags`
    - `group/client`
    - (optionally) some fields in `data` like contact name or username.

- **Filters**:
  - By `type` (`password`, `contact`, `code`, `note`).
  - By tag.
  - By archived status:
    - `archived=0` → active only.
    - `archived=1` → archived only.
    - `archived=all` → both.

- **Sorting**:
  - Default sort: `updated_at` DESC.
  - Option to sort by title.

Use Laravel pagination and return paginated JSON via `RecordResource`.

Example API endpoints:

- `GET /api/records?type=password&search=office&archived=0`
- `GET /api/records?tag=vendor&sort=updated_at_desc`

---

## Security for sensitive data

We are storing secrets (passwords, license keys, etc.), so we need basic but solid security.

### User accounts

- Auth via **Google OAuth** only.
- No plain-text passwords.

### Record secrets

For fields like stored passwords or license keys inside `data`:

- Use Laravel’s encrypted casts, or `encrypt()` / `decrypt()` manually.
- Example:
  - Cast a `data->password` field as encrypted, or encrypt before saving and decrypt in accessors.
- Ensure encryption uses `APP_KEY` via Laravel’s standard encryption system.

### Other security measures

- Don’t log decrypted secrets.
- Protect all web pages with auth middleware:
  - Only logged-in Google users can see anything beyond login.
- Protect API routes with Sanctum or session-based auth.
- Use CSRF protection for Blade forms.
- Consider domain restriction:
  - Optionally allow only emails from a specific domain (e.g. `@company.com`) to access the app, configurable via `.env`.

Also provide a short note on future hardening:

- 2FA on top of Google (if desired).
- Audit logging (who viewed/changed what).
- VPN-only or IP-restricted access.
- Strong policies about how secrets are used/stored elsewhere.

---

## Frontend UX – Blade + Vue hybrid

Use Blade for main layout and pages, Vue 3 for dynamic parts.

### Layout

Create a main Blade layout, e.g. `resources/views/layouts/app.blade.php`:

- Top nav:
  - App name (you can suggest one like “TeamVault”, “KeyRing”, etc.).
  - Current user name and avatar (from Google).
  - Logout link.
- Content `@yield('content')`.
- Include Vite JS/CSS.

### Views / Pages

1. **Login page**

- Blade view at e.g. `/login`.
- Show:
  - App branding.
  - “Sign in with Google” button that points to `/auth/google/redirect`.

2. **Records dashboard (Blade + Vue)**

- Route: `/records`.
- Blade view `resources/views/records/index.blade.php`:
  - Extends layout.
  - Contains a `div` where Vue will mount, e.g. `<div id="records-dashboard-root"></div>`.

- Vue component: `RecordsDashboard.vue`:
  - Calls `GET /api/records` with filters and pagination.
  - Shows:
    - Search bar.
    - Type filter controls.
    - Tag filter.
    - Archived toggle.
    - Table or grid of records.
  - Has button(s) for:
    - “Add new record” (opens `RecordForm`).
    - Archive/restore actions.

3. **Record create/edit (Vue)**

- Component `RecordForm.vue`:
  - Fields for:
    - Type.
    - Title.
    - Description/notes.
    - Tags.
    - Group/client.
    - Type-specific fields (conditionally shown).
  - For create: `POST /api/records`.
  - For edit: `PUT /api/records/{id}`.

- This form can be:
  - Used in a modal on the dashboard.
  - Or in dedicated routes (`/records/create`, `/records/{id}/edit`) rendered via Blade + Vue.

4. **Record detail view (Blade + Vue)**

- Route: `/records/{record}`.
- Blade view `resources/views/records/show.blade.php`:
  - Extends layout.
  - Mounts `RecordDetail` component onto a root div, passing ID via data-attribute or props.

- Vue component: `RecordDetail.vue`:
  - Fetches `GET /api/records/{id}`.
  - Displays all record info:
    - Type, title, tags, group, description.
    - Type-specific data.
  - For sensitive fields (like passwords):
    - Show masked value with “show/hide” toggle.
  - Buttons:
    - Archive/restore.
    - Edit.
    - (If admin) delete.

5. **Optional: Admin user management**

- Simple admin-only page at `/admin/users`:
  - Lists users.
  - Allows toggling roles or marking deactivated (optional).

---

## API design (routes)

### Web routes (`routes/web.php`)

- Google auth routes:
  - `/login` → login page view.
  - `/auth/google/redirect` → redirect to Google.
  - `/auth/google/callback` → handle callback and sign in user.
  - `/logout` → log user out.

- Protected routes for views:
  - `/` → redirect to `/records` for logged-in users.
  - `/records` → records dashboard view (Blade + Vue).
  - `/records/{record}` → record detail view.

Use middleware like `auth` to protect everything except login and auth routes.

### API routes (`routes/api.php`)

Protect with auth middleware (Sanctum/session):

- `GET /api/records` — list with search/filter/pagination.
- `POST /api/records` — create.
- `GET /api/records/{record}` — show.
- `PUT /api/records/{record}` — update.
- `PATCH /api/records/{record}/archive` — archive.
- `PATCH /api/records/{record}/restore` — restore.
- `DELETE /api/records/{record}` — hard delete (admin only).

Use Form Requests for validation and `RecordResource` for JSON formatting.

Include example request/response payloads in the explanation.

---

## Containerization & Deployment (Docker-first)

I will host this app via Docker (locally and possibly on a VPS later). Design it Docker-first.

### Docker artifacts

Create:

1. **Dockerfile** for the Laravel app:

   - Base image: `php:fpm` (PHP 8.2+).
   - Install needed extensions: `pdo_pgsql`, `mbstring`, `openssl`, `xml`, `bcmath`, `zip`, etc.
   - Use a **multi-stage build** if appropriate:
     - Build stage:
       - Install Composer.
       - `composer install` (no dev deps in production build).
       - Install Node + npm (or use a node image) to run `npm install` and `npm run build`.
     - Runtime stage:
       - New `php:fpm` base.
       - Copy Laravel app code.
       - Copy `vendor/` and built assets from build stage.
   - Set working dir (e.g. `/var/www/html`).
   - Configure `php-fpm` as entrypoint.
   - Expose port `9000`.

2. **Nginx config** (e.g. `docker/nginx/default.conf`):

   - Serve the app from `/var/www/html/public`.
   - Forward `php` requests to `app:9000`.
   - Handle reasonable defaults for:
     - `index.php`
     - static assets
     - 404/500 basics.

3. **docker-compose.yml** with services:

   - `app`:
     - Build from `Dockerfile`.
     - env vars from `.env` or `env_file`.
     - depends on `db`.

   - `web` (nginx):
     - Image: official `nginx`.
     - Mount:
       - App code (or built artifacts) from `app`.
       - Nginx config.
     - Ports:
       - `8000:80` (host:container).
     - Depends on `app`.

   - `db` (Postgres):
     - Image: `postgres:latest`.
     - Env:
       - `POSTGRES_DB`
       - `POSTGRES_USER`
       - `POSTGRES_PASSWORD`
     - Volume:
       - `db_data` for persistent storage.
     - Expose internal port `5432`.

Use named volume `db_data`.

### Environment & commands

Provide an example `.env` (or `.env.docker`) tailored to Docker with:

- `APP_NAME=TeamVault` (or similar)
- `APP_URL=http://localhost:8000`
- `DB_CONNECTION=pgsql`
- `DB_HOST=db`
- `DB_PORT=5432`
- `DB_DATABASE=teamvault`
- `DB_USERNAME=teamvault_user`
- `DB_PASSWORD=secret`
- Google auth keys:
  - `GOOGLE_CLIENT_ID=...`
  - `GOOGLE_CLIENT_SECRET=...`
  - `GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback`
- Any additional Sanctum / session config needed.

Explain commands for:

**First-time setup with Docker:**

- `cp .env.example .env`
- Edit `.env` with DB + Google credentials.
- `docker-compose up -d --build`
- `docker-compose exec app php artisan key:generate`
- `docker-compose exec app php artisan migrate --seed`

**Typical dev workflow:**

- `docker-compose up -d`
- Code editing locally.
- Commands:
  - `docker-compose exec app php artisan migrate`
  - `docker-compose exec app php artisan test`

**More production-like run:**

- `docker-compose up -d --build`
- Inside `app` container:
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`

### README / docs

Add a “Deployment with Docker” section that explains:

- Prereqs: Docker + docker-compose.
- Steps:
  1. Copy `.env.example` → `.env`.
  2. Set DB and Google OAuth values.
  3. `docker-compose up -d --build`.
  4. `docker-compose exec app php artisan migrate --seed`.
  5. Visit `http://localhost:8000`.

- How DB persistence works via `db_data` volume.
- How to back up the DB:
  - Example using `pg_dump` via `docker-compose exec db ...`.

Also include a short “Future deployment options” note:

- Same Docker setup on a VPS (just different domain + SSL).
- Potential to separate the Vue frontend later as a standalone SPA (e.g. on Vercel) that talks to the Laravel API if needed.

---

## Implementation plan (phases)

Proceed in phases and show code in manageable chunks.

### Phase 1 – Planning & setup

- Summarize architecture and auth strategy (Google OAuth via Socialite).
- Initialize Laravel project.
- Set up Tailwind + Vue via Vite (standard Laravel setup).
- Create basic Dockerfile and docker-compose.yml.
- Outline folder structure.

### Phase 2 – Auth via Google + User model

- Install and configure Socialite (or chosen Google auth integration).
- Implement:
  - `/login` view with “Sign in with Google” button.
  - `/auth/google/redirect` and `/auth/google/callback` routes/controllers.
  - `User` model + migration (with `google_id`, `avatar`, `role`).
- Seed an initial admin user based on an email in `.env` or via seeder.
- Protect main routes with `auth` middleware.

### Phase 3 – Records model, migrations, CRUD API

- Create `Record` model + migration (with `data` JSON if chosen).
- Implement:
  - `RecordController` (API) with index/store/show/update/destroy.
  - Archive / restore routes + actions.
  - `RecordPolicy` for authorization.
  - Request validation classes.
  - `RecordResource` for shaping JSON.
- Implement search/filter/sort in the `index` method.

### Phase 4 – Blade + Vue integration

- Create Blade layout + login view.
- Create records:
  - `index.blade.php` (dashboard).
  - `show.blade.php` (detail).
- Create Vue components:
  - `RecordsDashboard.vue`.
  - `RecordForm.vue`.
  - `RecordDetail.vue`.
- Set up `resources/js/app.js` to register and mount components.
- Implement API calls with proper auth (Sanctum or session-based).

### Phase 5 – Security, polish, documentation

- Add encryption for sensitive data inside `records`.
- Ensure Google OAuth + logout flows are clean and secure.
- Improve UX (loading states, error messages, confirmations).
- Add Docker-specific docs:
  - Setup steps.
  - Admin seeding.
  - DB backup tips.
- Add a section on future hardening (2FA, audit logs, IP restriction, etc.).

At each phase:

- Explain what you’re doing and why.
- Provide concrete code snippets (migrations, controllers, policies, Blade views, Vue components, Docker files) in reasonably sized chunks that I can copy into my project.

