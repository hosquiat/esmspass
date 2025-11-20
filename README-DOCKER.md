# ESMSPass - Docker Deployment Guide

This document explains how to run the ESMSPass application using Docker.

## Prerequisites

- Docker Desktop (or Docker + docker-compose)
- Google OAuth 2.0 credentials

## First-Time Setup

### 1. Get Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (or select existing)
3. Enable the Google+ API
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client ID"
5. Configure OAuth consent screen
6. Create OAuth client ID (Web application type)
7. Add authorized redirect URI: `http://localhost:8080/auth/google/callback`
8. Copy the Client ID and Client Secret

### 2. Configure Environment

```bash
# Copy the example environment file
cp .env.example .env

# Edit .env and set your Google OAuth credentials
# Required values:
# - GOOGLE_CLIENT_ID=your_google_client_id_here
# - GOOGLE_CLIENT_SECRET=your_google_client_secret_here
# - ADMIN_EMAILS=your@email.com (use the Google account email you'll log in with)
```

### 3. Build and Start Containers

```bash
# Build and start all services
docker-compose up -d --build

# Wait for containers to be healthy (check with docker-compose ps)
# The database needs to be ready before running migrations
```

### 4. Run Database Migrations

```bash
# Run migrations to create database tables
docker-compose exec app php artisan migrate --seed

# This will:
# - Create users, records, and other necessary tables
# - Seed an admin user (based on ADMIN_EMAILS in .env)
```

### 5. Access the Application

Open your browser and navigate to: **http://localhost:8080**

Click "Sign in with Google" and authenticate with the email address you set in `ADMIN_EMAILS`.

## Daily Development Workflow

```bash
# Start containers
docker-compose up -d

# View logs
docker-compose logs -f app

# Stop containers
docker-compose down

# Stop and remove all data (including database)
docker-compose down -v
```

## Common Commands

### Laravel Artisan Commands

```bash
# Run migrations
docker-compose exec app php artisan migrate

# Rollback last migration
docker-compose exec app php artisan migrate:rollback

# Fresh migration (drop all tables and re-migrate)
docker-compose exec app php artisan migrate:fresh --seed

# Clear caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Run tests
docker-compose exec app php artisan test

# Access Laravel tinker
docker-compose exec app php artisan tinker
```

### Frontend Development

```bash
# Build assets for production
docker-compose exec app npm run build

# Development build with watch (requires running the container with npm run dev)
# Note: For development, you may want to run Vite outside Docker for hot reloading
npm install
npm run dev
```

### Database Operations

```bash
# Access PostgreSQL CLI
docker-compose exec db psql -U ESMSPass_user -d ESMSPass

# Backup database
docker-compose exec db pg_dump -U ESMSPass_user ESMSPass > backup_$(date +%Y%m%d).sql

# Restore database
docker-compose exec -T db psql -U ESMSPass_user ESMSPass < backup_20240101.sql
```

## Production Deployment

### Environment Changes

For production deployment on a VPS:

1. Update `.env`:
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   ```

2. Update `GOOGLE_REDIRECT_URI` to match your production domain:
   ```
   GOOGLE_REDIRECT_URI=https://yourdomain.com/auth/google/callback
   ```

3. Update authorized redirect URIs in Google Cloud Console

### Optimization Commands

```bash
# Cache configuration
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Optimize Composer autoloader
docker-compose exec app composer install --optimize-autoloader --no-dev
```

### SSL/HTTPS Setup

For production, you'll want to add SSL certificates. You can use:
- Nginx proxy with Let's Encrypt
- Cloudflare
- AWS Certificate Manager (if deploying to AWS)

Example with nginx-proxy and letsencrypt companion (not included in this setup).

## Database Persistence

The PostgreSQL data is stored in a Docker volume named `db_data`. This persists even when containers are stopped or removed (unless you use `docker-compose down -v`).

## Switching to MySQL

If you prefer MySQL over PostgreSQL:

1. Update `docker-compose.yml` - replace the `db` service with MySQL
2. Update `.env`:
   ```
   DB_CONNECTION=mysql
   DB_PORT=3306
   ```
3. Update `Dockerfile` to install `pdo_mysql` instead of `pdo_pgsql`

## Troubleshooting

### Permission Issues

```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Database Connection Errors

- Ensure the `db` container is healthy: `docker-compose ps`
- Check database credentials in `.env` match `docker-compose.yml`
- Try restarting: `docker-compose restart app`

### Google OAuth Errors

- Verify redirect URI matches exactly in Google Console and `.env`
- Check that Google+ API is enabled
- Ensure your Google account email matches one in `ADMIN_EMAILS`

## Security Notes

- Change `APP_KEY` in production (run `php artisan key:generate`)
- Use strong database passwords
- Never commit `.env` to version control
- Consider domain restriction (only allow `@yourcompany.com` emails)
- Run behind a VPN or IP whitelist for additional security
- Enable HTTPS in production
