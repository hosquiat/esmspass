# TeamVault - Setup and Usage Guide

## Overview

TeamVault is a secure internal web application for storing and managing company secrets including passwords, contact information, codes/IDs, and notes. It uses Google OAuth for authentication and Laravel + Vue for a robust, modern architecture.

## Quick Start

### Prerequisites

- Docker and Docker Compose
- Google OAuth 2.0 credentials
- Your company email for admin access

### Installation Steps

1. **Clone and Setup Environment**
   ```bash
   cp .env.example .env
   ```

2. **Configure Google OAuth**
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create OAuth 2.0 credentials
   - Set authorized redirect URI: `http://localhost:8080/auth/google/callback`
   - Add credentials to `.env`:
     ```
     GOOGLE_CLIENT_ID=your_client_id
     GOOGLE_CLIENT_SECRET=your_client_secret
     ```

3. **Set Admin Users**
   ```bash
   # In .env file
   ADMIN_EMAILS=admin@yourcompany.com,manager@yourcompany.com
   ```

4. **Start Application**
   ```bash
   docker-compose up -d --build
   docker-compose exec app php artisan migrate --seed
   ```

5. **Access Application**
   - Visit: http://localhost:8080
   - Sign in with Google using an email from `ADMIN_EMAILS`

## Features

### Record Types

1. **Passwords**
   - Store username, password, and URL
   - Passwords are encrypted at rest
   - Show/hide and copy functionality

2. **Contacts**
   - Store name, company, email, phone
   - Quick mailto: and tel: links

3. **Codes**
   - Store license keys, account IDs, etc.
   - Copy functionality for easy access

4. **Notes**
   - General purpose notes and references
   - Supports long-form text

### Features

- **Search**: Full-text search across titles, descriptions, tags, and groups
- **Filters**: Filter by type, archived status, tags, or groups
- **Tags**: Organize records with multiple tags
- **Groups**: Categorize by client, project, or system
- **Archiving**: Soft-delete records (can be restored)
- **Permissions**: Role-based access (admin/user)
- **Encryption**: Sensitive data encrypted with Laravel's encryption

### User Roles

**Admin**
- Full access to all records
- Can hard delete records
- Manage user roles (via database)

**User**
- View and edit all records
- Archive and restore records
- Cannot hard delete

## API Endpoints

All API endpoints require authentication via Sanctum (session-based).

### Records API

```
GET    /api/records              List records with filters
POST   /api/records              Create record
GET    /api/records/{id}         Show single record
PUT    /api/records/{id}         Update record
DELETE /api/records/{id}         Delete record (admin only)
PATCH  /api/records/{id}/archive Archive record
PATCH  /api/records/{id}/restore Restore archived record
```

### Query Parameters

- `search` - Search text
- `type` - password, contact, code, note
- `archived` - 0 (active), 1 (archived), all (both)
- `tag` - Filter by tag
- `group` - Filter by group
- `sort` - title, type, created_at, updated_at, group
- `direction` - asc, desc
- `per_page` - Results per page (max 100)

### Example Request

```bash
curl -X GET "http://localhost:8080/api/records?type=password&search=office&archived=0" \
  -H "Accept: application/json" \
  -H "X-CSRF-TOKEN: your_csrf_token" \
  --cookie "your_session_cookie"
```

## Development

### Local Development (Without Docker)

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database (use PostgreSQL or MySQL)
# Update .env with your database credentials

# Run migrations
php artisan migrate --seed

# Build assets
npm run dev

# Start server
php artisan serve
```

### Running Tests

```bash
# Inside Docker
docker-compose exec app php artisan test

# Local
php artisan test
```

### Asset Development

```bash
# Watch for changes
npm run dev

# Build for production
npm run build
```

## Security

### Encryption

- **APP_KEY**: Laravel uses this to encrypt sensitive data
- **Data Column**: The entire `data` JSON column is encrypted
- **HTTPS**: Use HTTPS in production
- **Google OAuth**: No passwords stored locally

### Best Practices

1. **Rotate APP_KEY**: Keep it secret and rotate periodically
2. **HTTPS Only**: Always use HTTPS in production
3. **VPN/IP Restriction**: Consider restricting access to company network
4. **Audit Logs**: Future enhancement - track who accessed what
5. **2FA**: Can be added on top of Google OAuth

## Production Deployment

### Environment Changes

```bash
# .env for production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://vault.yourcompany.com
GOOGLE_REDIRECT_URI=https://vault.yourcompany.com/auth/google/callback
```

### Optimization

```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app composer install --optimize-autoloader --no-dev
```

### SSL/HTTPS

Use a reverse proxy (nginx-proxy, Traefik) or cloud load balancer with Let's Encrypt for SSL certificates.

### Backup

```bash
# Backup database
docker-compose exec db pg_dump -U teamvault_user teamvault > backup_$(date +%Y%m%d).sql

# Backup .env (contains encryption key)
cp .env .env.backup.$(date +%Y%m%d)
```

## Troubleshooting

### Can't Log In

- Check `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env`
- Verify redirect URI matches in Google Console
- Ensure your email is in `ADMIN_EMAILS`

### Database Connection Failed

- Check `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in `.env`
- Ensure database container is running: `docker-compose ps`
- Restart containers: `docker-compose restart`

### Assets Not Loading

- Run `npm run build`
- Clear Laravel cache: `php artisan optimize:clear`
- Check Vite manifest exists: `public/build/manifest.json`

### Permission Denied

- Fix storage permissions:
  ```bash
  docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
  docker-compose exec app chmod -R 775 storage bootstrap/cache
  ```

## Architecture Notes

### Hybrid Blade + Vue

This application uses a hybrid approach:
- Blade templates render the page shell and layout
- Vue components mount into specific elements for dynamic functionality
- This provides SEO benefits and simpler deployment vs. full SPA

### Why PostgreSQL?

- Better JSON support for the `data` column
- ACID compliance for sensitive data
- Easy to switch to MySQL if needed (see CLAUDE.md)

### Why Encrypted Array Cast?

The `data` column uses `encrypted:array` cast, which:
- Encrypts the entire JSON before storing
- Automatically decrypts when accessed
- Uses Laravel's `APP_KEY` for encryption
- Protects passwords, license keys, and other secrets

## Future Enhancements

- [ ] 2FA via Google Authenticator
- [ ] Audit log (track who viewed/changed records)
- [ ] Export to CSV/JSON
- [ ] Bulk operations
- [ ] Advanced search with filters
- [ ] Record sharing with specific users
- [ ] Password strength indicator
- [ ] Password generator
- [ ] Record attachments

## Support

For issues or questions:
1. Check this documentation
2. Review `CLAUDE.md` for development details
3. Check `README-DOCKER.md` for Docker-specific help
4. Review application logs: `docker-compose logs -f app`

## License

Internal company use only.
