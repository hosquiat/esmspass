# Troubleshooting Guide

## Page Flashes and Doesn't Load After Login

### Fixed Issues
1. ✅ Changed API routes from `auth:sanctum` to `auth` middleware (session-based)
2. ✅ Fixed Vue mounting conflict (was trying to mount to both `#app` and `#records-dashboard`)
3. ✅ Rebuilt assets with proper component mounting

### If Issue Persists

**Check Browser Console for Errors:**
```javascript
// Open Developer Tools (F12)
// Check Console tab for any errors
```

**Common Issues and Solutions:**

#### 1. CSRF Token Mismatch
**Symptoms:** 419 errors in console, requests failing

**Solution:**
```bash
# Clear Laravel cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Or in Docker
docker-compose exec app php artisan optimize:clear
```

#### 2. Session Not Persisting
**Symptoms:** Redirected back to login immediately

**Check:**
- `.env` file has `SESSION_DRIVER=database`
- Session table exists (run migrations)
- Clear browser cookies for localhost:8000

**Solution:**
```bash
# Recreate session table
docker-compose exec app php artisan migrate:fresh --seed
```

#### 3. API Routes Not Working
**Symptoms:** 404 errors when calling `/api/records`

**Solution:**
```bash
# Clear route cache
docker-compose exec app php artisan route:clear

# Verify routes exist
docker-compose exec app php artisan route:list | grep records
```

#### 4. Vue Component Not Mounting
**Symptoms:** Empty page, no errors in console

**Check:**
1. View page source - confirm `<div id="records-dashboard">` exists
2. Confirm Vite manifest exists: `public/build/manifest.json`
3. Check Network tab - confirm JS/CSS files load

**Solution:**
```bash
# Rebuild assets
npm run build

# Or in Docker
docker-compose exec app npm run build
```

#### 5. Database Connection Errors
**Symptoms:** 500 errors, "could not connect to database"

**Solution:**
```bash
# Check database is running
docker-compose ps

# Restart database
docker-compose restart db

# Check database credentials in .env match docker-compose.yml
```

## Debugging Steps

### 1. Check Laravel Logs
```bash
docker-compose exec app tail -f storage/logs/laravel.log
```

### 2. Check Web Server Logs
```bash
docker-compose logs -f web
docker-compose logs -f app
```

### 3. Test API Directly
```bash
# Get CSRF token from login page, then:
curl -X GET "http://localhost:8000/api/records" \
  -H "Accept: application/json" \
  -H "Cookie: your_session_cookie" \
  -H "X-CSRF-TOKEN: your_csrf_token"
```

### 4. Check Authentication
Visit `http://localhost:8000/api/user` after logging in - should return your user JSON.

### 5. Verify Google OAuth
- Check `.env` has correct `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`
- Verify redirect URI in Google Console matches `.env`
- Check your email is in `ADMIN_EMAILS`

## Quick Fixes

### Reset Everything
```bash
# Stop all containers
docker-compose down

# Remove volumes (WARNING: deletes all data)
docker-compose down -v

# Rebuild and start fresh
docker-compose up -d --build
docker-compose exec app php artisan migrate:fresh --seed
```

### Clear All Caches
```bash
docker-compose exec app php artisan optimize:clear
docker-compose exec app composer dump-autoload
npm run build
```

### Restart Containers
```bash
docker-compose restart
```

## Development Mode

For easier debugging, run in development mode:

```bash
# Start containers
docker-compose up -d

# Watch assets for changes
npm run dev

# In another terminal, watch logs
docker-compose logs -f app
```

## Common Error Messages

### "419 Page Expired"
- CSRF token issue
- Clear cache and refresh page
- Check CSRF meta tag exists in HTML

### "401 Unauthorized"
- Not logged in or session expired
- Log out and log back in
- Clear browser cookies

### "403 Forbidden"
- Authorization policy blocking action
- Check user role (admin vs user)
- Review RecordPolicy permissions

### "500 Internal Server Error"
- Check Laravel logs
- Often database or encryption key issue
- Verify `APP_KEY` is set in `.env`

### "SQLSTATE Connection Refused"
- Database not running
- Wrong database credentials
- Check `docker-compose ps`

## Getting Help

If you're still stuck:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Review this file: `CLAUDE.md` for architecture details
4. Check `SETUP.md` for configuration details

## Testing Without Docker

```bash
# Use SQLite for quick testing
cp .env.example .env
# Change DB_CONNECTION=sqlite
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Visit `http://localhost:8000`
