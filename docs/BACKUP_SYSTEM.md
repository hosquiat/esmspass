# Database Backup System

## Overview

TeamVault includes a comprehensive backup system with two storage options:
1. **Filesystem Backups** - Automated backups stored in a Docker volume (configured by default)
2. **Google Drive Backups** - Optional cloud backups for off-site redundancy

## Features

- **Automated Daily Backups** - Scheduled at 2:00 AM via Laravel scheduler
- **Manual Backups** - Create backups on-demand from the Settings page
- **Retention Management** - Automatically delete old backups based on retention policy
- **Download Backups** - Download backup files directly from the Settings UI
- **Google Drive Integration** - Optionally upload backups to Google Drive for cloud storage
- **PostgreSQL & MySQL Support** - Works with both database types

## Filesystem Backups

### Setup (Included by Default)

Filesystem backups are enabled by default when you set up TeamVault.

1. **Docker Volume** - A dedicated `backup_data` volume is configured in `docker-compose.yml`
2. **Storage Location** - Backups are stored in `/var/www/html/storage/app/backups` inside the container
3. **Persistence** - The Docker volume ensures backups persist across container restarts

### Configuration

Configure filesystem backups via the Settings page:

- **Enable/Disable** - Toggle filesystem backups on/off
- **Retention Period** - Set how many days to keep backups (default: 30 days)

Or via environment variables in `.env`:

```env
BACKUP_FILESYSTEM_ENABLED=true
BACKUP_RETENTION_DAYS=30
BACKUP_SCHEDULE=daily
```

### Manual Backup

Create a backup immediately:

```bash
docker exec teamvault_app php artisan backup:run
```

Or use the "Run Backup Now" button in Settings > Database Backups.

### Accessing Backup Files

**Via Docker Volume:**
```bash
# List backups
docker exec teamvault_app ls -lh storage/app/backups

# Copy backup to host
docker cp teamvault_app:/var/www/html/storage/app/backups/teamvault_backup_2025-11-21_120000.sql ./
```

**Via Settings UI:**
Navigate to Settings > Database Backups and click "Download" next to any backup.

## Google Drive Backups

### Setup

Google Drive backups require a Google Service Account with Google Drive API access.

#### Step 1: Create a Google Service Account

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the **Google Drive API**:
   - Navigate to "APIs & Services" > "Library"
   - Search for "Google Drive API"
   - Click "Enable"
4. Create a Service Account:
   - Navigate to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "Service Account"
   - Name it "TeamVault Backups" and create
5. Generate a JSON key:
   - Click on the service account you just created
   - Go to "Keys" tab
   - Click "Add Key" > "Create new key"
   - Choose "JSON" format
   - Save the downloaded JSON file

#### Step 2: Configure in TeamVault

1. Navigate to **Settings > Database Backups**
2. Scroll to the **Google Drive Backup** section
3. Paste the entire contents of your service account JSON file into the "Service Account JSON" textarea
4. Click "Configure Google Drive"

This will:
- Validate your credentials
- Create a "TeamVault Backups" folder in Google Drive
- Store the configuration securely (encrypted)

#### Step 3: Enable Google Drive Backups

After configuration, toggle the "Google Drive Backup" switch to enable automatic uploads.

### Sharing the Backup Folder

To access backups from your Google account:

1. Find the service account email in your JSON credentials (looks like `teamvault-backups@project-id.iam.gserviceaccount.com`)
2. In Google Drive, locate the "TeamVault Backups" folder
3. Share the folder with your personal Google account

## Automated Backups

### Scheduler Setup

The backup scheduler is configured in `routes/console.php`:

```php
Schedule::command('backup:run')->dailyAt('02:00')->name('database-backup');
```

### Running the Scheduler

The Laravel scheduler must be running for automated backups:

**In Docker (Production):**
Add to your `docker-compose.yml`:

```yaml
services:
  scheduler:
    image: sogeniusio/teamvault:latest
    command: sh -c "while true; do php artisan schedule:run --verbose --no-interaction; sleep 60; done"
    depends_on:
      - db
    environment:
      # Same environment variables as app service
```

**Or use cron (if running on a server):**
```bash
* * * * * cd /path/to/teamvault && docker exec teamvault_app php artisan schedule:run >> /dev/null 2>&1
```

## Restore from Backup

### Filesystem Restore

```bash
# 1. Stop the application
docker compose down

# 2. Copy backup to container (if restoring from local file)
docker compose up -d db
docker cp backup.sql teamvault_db:/tmp/backup.sql

# 3. Restore database
docker exec -i teamvault_db psql -U teamvault_user -d postgres -c "DROP DATABASE IF EXISTS teamvault; CREATE DATABASE teamvault;"
docker exec -i teamvault_db pg_restore -U teamvault_user -d teamvault -v /tmp/backup.sql

# 4. Restart application
docker compose up -d
```

### Google Drive Restore

1. Download the backup from Google Drive
2. Follow the filesystem restore steps above

## Backup File Format

- **Filename**: `teamvault_backup_YYYY-MM-DD_HHMMSS.sql`
- **Format**: PostgreSQL custom format (`pg_dump -F c`) or MySQL dump
- **Encryption**: Not encrypted (ensure Google Drive folder is private)

## Security Considerations

1. **Google Service Account Credentials** - Stored encrypted in the database using Laravel's encryption
2. **Backup Files** - Not encrypted by default; consider encrypting sensitive backups
3. **Google Drive Folder** - Make sure the folder is not publicly accessible
4. **Retention Policy** - Old backups are automatically deleted based on retention settings

## Troubleshooting

### "Backup failed" Error

Check container logs:
```bash
docker compose logs app
```

Common issues:
- PostgreSQL/MySQL client not installed (should be in Docker image)
- Database connection issues
- Insufficient disk space

### Google Drive Upload Fails

1. Test the connection: Click "Test Connection" in Settings
2. Check service account permissions
3. Verify the Google Drive API is enabled
4. Check container logs for detailed error messages

### Scheduler Not Running

Verify the scheduler is active:
```bash
docker compose ps
# Should see a 'scheduler' service if you added it

# Or check manually
docker exec teamvault_app php artisan schedule:list
```

## Monitoring

### Check Last Backup

In Settings > Database Backups, you'll see:
- "Last backup: X hours ago"
- List of available backups with sizes and dates

### Manual Verification

```bash
# List local backups
docker exec teamvault_app ls -lh storage/app/backups

# Verify backup file is not empty
docker exec teamvault_app du -h storage/app/backups/teamvault_backup_*.sql
```

## Best Practices

1. **Enable Both Backup Types** - Use filesystem for quick restores, Google Drive for off-site redundancy
2. **Test Restores Regularly** - Verify backups can actually be restored
3. **Monitor Backup Sizes** - Sudden size changes may indicate issues
4. **Set Appropriate Retention** - Balance storage space with recovery needs (30 days is recommended)
5. **Secure Google Drive Folder** - Don't share the backup folder publicly
6. **Document Service Account** - Keep the service account JSON in a secure location

## API Endpoints

The backup system exposes these API endpoints (admin-only):

- `GET /api/admin/backups/settings` - Get current backup settings
- `PUT /api/admin/backups/settings` - Update backup settings
- `POST /api/admin/backups/run` - Run a manual backup
- `GET /api/admin/backups/list` - List available backups
- `GET /api/admin/backups/download/{filename}` - Download a backup file
- `POST /api/admin/backups/google-drive/configure` - Configure Google Drive credentials
- `GET /api/admin/backups/google-drive/test` - Test Google Drive connection

## Implementation Details

### Files Created

- `app/Models/BackupSetting.php` - Model for storing backup configuration
- `app/Services/BackupService.php` - Core backup logic (create, list, download, restore)
- `app/Services/GoogleDriveBackupService.php` - Google Drive integration
- `app/Http/Controllers/Admin/BackupController.php` - API endpoints
- `app/Console/Commands/BackupDatabase.php` - Artisan command
- `database/migrations/2025_11_21_220000_create_backup_settings_table.php` - Settings storage

### Dependencies

- `google/apiclient: ^2.18` - Google Drive API client library
