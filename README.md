# ESMSPASS - Team Password Manager

A secure, team-based password management system built with Laravel 11 and Vue 3, featuring Google OAuth authentication, encrypted storage, and comprehensive change tracking.

## Features

- **Google OAuth Authentication** - Secure login with Google accounts
- **Encrypted Storage** - All sensitive data encrypted in PostgreSQL
- **Record Types** - Support for passwords, contacts, codes, and notes
- **Change Tracking** - Complete audit log of all record modifications
- **Team Collaboration** - Share and manage credentials across your team
- **Admin Controls** - Admin-only settings for import/export functionality
- **Archive System** - Soft-delete records with restore capability
- **Search & Filter** - Quick search across all records with type and status filters
- **Responsive UI** - Modern, mobile-friendly interface built with Tailwind CSS

## Tech Stack

- **Backend**: Laravel 11 (PHP 8.3)
- **Frontend**: Vue 3 (Composition API)
- **Database**: PostgreSQL 16
- **Authentication**: Laravel Socialite (Google OAuth)
- **Styling**: Tailwind CSS
- **Build Tool**: Vite
- **Container**: Docker & Docker Compose

## Prerequisites

### For Docker Deployment
- Docker Engine 20.10+
- Docker Compose 2.0+

### For Local Development
- PHP 8.3+
- Composer 2.x
- Node.js 20+
- PostgreSQL 16+
- NPM or Yarn

## Quick Start with Docker

### 1. Clone the Repository

```bash
git clone https://github.com/hosquiat/esmspass.git
cd esmspass
```

### 2. Configure Environment

Copy the example environment file and update it with your values:

```bash
cp .env.example .env
```

**Required environment variables:**

```env
APP_NAME=ESMSPASS
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain.com
APP_KEY=base64:your_generated_app_key_here

# Database
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=esmspass
DB_USERNAME=esmspass_user
DB_PASSWORD=your_secure_database_password

# Google OAuth (required)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://your-domain.com/auth/google/callback
```

### 3. Generate Application Key

If you haven't generated an `APP_KEY`, run:

```bash
docker run --rm -v $(pwd):/app -w /app php:8.3-cli php artisan key:generate --show
```

Copy the output and add it to your `.env` file.

### 4. Set Up Google OAuth

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Navigate to **APIs & Services > Credentials**
4. Click **Create Credentials > OAuth 2.0 Client ID**
5. Configure OAuth consent screen if prompted
6. Set application type to **Web application**
7. Add authorized redirect URI: `http://your-domain.com/auth/google/callback`
8. Copy the **Client ID** and **Client Secret** to your `.env` file

### 5. Deploy with Docker Compose

```bash
# Pull and start the containers
docker-compose up -d

# Check container status
docker-compose ps

# View logs
docker-compose logs -f app
```

The application will be available at `http://localhost:8000`

### 6. Create First Admin User

After your first login via Google OAuth, you'll need to manually set yourself as an admin in the database:

```bash
# Access the database container
docker-compose exec db psql -U esmspass_user -d esmspass

# Update your user to admin
UPDATE users SET is_admin = true WHERE email = 'your-email@example.com';

# Exit
\q
```

## Docker Hub Deployment

Pull the pre-built image from Docker Hub:

```bash
docker pull hosquiat/esmspass:latest
```

Then update your `docker-compose.yml` to use the image instead of building:

```yaml
services:
  app:
    image: hosquiat/esmspass:latest
    # Remove the 'build' section
```

## Local Development Setup

### 1. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Set Up Database

Create a PostgreSQL database:

```bash
createdb esmspass
```

Update your `.env` file with local database credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=esmspass
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Run Migrations

```bash
php artisan migrate
```

### 4. Build Frontend Assets

```bash
# Development build with hot reload
npm run dev

# Production build
npm run build
```

### 5. Start Development Server

```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Application Structure

```
esmspass/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/RecordController.php
│   │   └── Middleware/
│   │       └── EnsureUserIsAdmin.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Record.php
│   │   └── RecordChange.php
│   └── Observers/
│       └── RecordObserver.php
├── database/
│   └── migrations/
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   ├── RecordsDashboard.vue
│   │   │   ├── RecordDetail.vue
│   │   │   ├── RecordForm.vue
│   │   │   └── SettingsPage.vue
│   │   └── app.js
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php
│       └── records/
├── docker/
│   ├── nginx.conf
│   ├── default.conf
│   ├── supervisord.conf
│   └── entrypoint.sh
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## API Endpoints

### Records
- `GET /api/records` - List all records (with pagination and filters)
- `POST /api/records` - Create a new record
- `GET /api/records/{id}` - Get record details
- `PUT /api/records/{id}` - Update a record
- `DELETE /api/records/{id}` - Delete a record
- `PATCH /api/records/{id}/archive` - Archive a record
- `PATCH /api/records/{id}/restore` - Restore an archived record
- `GET /api/records/{id}/changes` - Get record change history

### Admin-Only Endpoints
- `POST /api/records/export` - Export all records as JSON
- `POST /api/records/import` - Import records from JSON

## Environment Variables Reference

| Variable | Description | Default | Required |
|----------|-------------|---------|----------|
| `APP_NAME` | Application name | ESMSPASS | No |
| `APP_ENV` | Environment (production/local) | production | Yes |
| `APP_DEBUG` | Enable debug mode | false | No |
| `APP_URL` | Application URL | - | Yes |
| `APP_KEY` | Encryption key | - | Yes |
| `DB_CONNECTION` | Database type | pgsql | Yes |
| `DB_HOST` | Database host | db | Yes |
| `DB_PORT` | Database port | 5432 | No |
| `DB_DATABASE` | Database name | esmspass | Yes |
| `DB_USERNAME` | Database user | esmspass_user | Yes |
| `DB_PASSWORD` | Database password | - | Yes |
| `GOOGLE_CLIENT_ID` | Google OAuth Client ID | - | Yes |
| `GOOGLE_CLIENT_SECRET` | Google OAuth Secret | - | Yes |
| `GOOGLE_REDIRECT_URI` | OAuth redirect URL | - | Yes |

## Security Considerations

1. **APP_KEY** - Must be a secure, random 32-character string. Use `php artisan key:generate`
2. **Database Password** - Use a strong, unique password for production
3. **HTTPS** - Always use HTTPS in production. Configure your reverse proxy (nginx, Traefik, etc.)
4. **Google OAuth** - Keep your Client Secret secure and never commit it to version control
5. **Admin Access** - Carefully control who has admin privileges
6. **Backups** - Regularly backup your PostgreSQL database
7. **Updates** - Keep dependencies updated for security patches

## Backup and Restore

### Backup Database

```bash
docker-compose exec db pg_dump -U esmspass_user esmspass > backup_$(date +%Y%m%d).sql
```

### Restore Database

```bash
docker-compose exec -T db psql -U esmspass_user esmspass < backup_20241120.sql
```

### Export Records via UI

Admin users can export all records:
1. Log in as an admin
2. Navigate to **Settings**
3. Click **Export Records**
4. Save the JSON file

### Import Records via UI

Admin users can import records:
1. Navigate to **Settings**
2. Click **Import Records**
3. Select your JSON backup file

## Troubleshooting

### Container won't start
```bash
# Check logs
docker-compose logs app

# Restart containers
docker-compose restart
```

### Database connection failed
```bash
# Verify database is running
docker-compose ps db

# Check database logs
docker-compose logs db

# Test database connection
docker-compose exec app php artisan migrate:status
```

### Permission errors
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Google OAuth not working
- Verify `GOOGLE_REDIRECT_URI` matches exactly what's configured in Google Cloud Console
- Ensure your domain is added to authorized origins
- Check that your Google OAuth consent screen is published (not in testing mode) if using production

## Production Deployment

### Using Docker Compose (Recommended)

1. **Set up server with Docker**
```bash
# Install Docker and Docker Compose on your server
curl -fsSL https://get.docker.com | sh
```

2. **Configure reverse proxy** (nginx, Traefik, Caddy)
```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

3. **Set up SSL with Let's Encrypt**
```bash
sudo certbot --nginx -d your-domain.com
```

4. **Deploy application**
```bash
git clone https://github.com/hosquiat/esmspass.git
cd esmspass
cp .env.example .env
# Edit .env with production values
docker-compose up -d
```

### Kubernetes Deployment

Helm charts and Kubernetes manifests can be created for production k8s deployments. Contact the maintainer for enterprise deployment support.

## Maintenance

### Update Application

```bash
# Pull latest changes
git pull origin main

# Rebuild and restart containers
docker-compose build --no-cache
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --force
```

### Monitor Application

```bash
# View real-time logs
docker-compose logs -f app

# Check resource usage
docker stats
```

## Contributing

This is a private repository. For bug reports or feature requests, please contact the repository owner.

## License

Proprietary - All rights reserved

## Support

For support, please contact:
- GitHub: [@hosquiat](https://github.com/hosquiat)
- Email: support@elitestartms.com

---

**Built with ❤️ by Elite Start TMS**
