# Database Connection Guide

This guide explains how to connect to the PostgreSQL database from your local machine.

## Connection Details

The PostgreSQL database is exposed on port **5433** to avoid conflicts with local PostgreSQL installations.

### Connection Parameters

| Parameter | Value |
|-----------|-------|
| **Host** | `localhost` |
| **Port** | `5433` |
| **Database** | `teamvault` |
| **Username** | `teamvault_user` |
| **Password** | `secret` (or value from `.env`) |

## Using psql (Command Line)

If you have PostgreSQL client tools installed:

```bash
psql -h localhost -p 5433 -U teamvault_user -d teamvault
```

When prompted, enter the password: `secret`

## Using GUI Tools

### TablePlus

1. Click **New Connection**
2. Select **PostgreSQL**
3. Enter connection details:
   - Name: `TeamVault Local`
   - Host: `localhost`
   - Port: `5433`
   - User: `teamvault_user`
   - Password: `secret`
   - Database: `teamvault`
4. Click **Test** then **Connect**

### pgAdmin

1. Right-click **Servers** ’ **Register** ’ **Server**
2. **General** tab:
   - Name: `TeamVault Local`
3. **Connection** tab:
   - Host: `localhost`
   - Port: `5433`
   - Maintenance database: `teamvault`
   - Username: `teamvault_user`
   - Password: `secret`
4. Click **Save**

### DBeaver

1. Click **New Database Connection**
2. Select **PostgreSQL**
3. Enter connection details:
   - Server Host: `localhost`
   - Port: `5433`
   - Database: `teamvault`
   - Username: `teamvault_user`
   - Password: `secret`
4. Click **Test Connection** then **Finish**

### DataGrip (JetBrains)

1. Click **+** ’ **Data Source** ’ **PostgreSQL**
2. Enter connection details:
   - Host: `localhost`
   - Port: `5433`
   - Database: `teamvault`
   - User: `teamvault_user`
   - Password: `secret`
3. Click **Test Connection** then **OK**

## Using Docker Exec (Alternative)

You can also connect directly to the database container:

```bash
docker exec -it teamvault_db psql -U teamvault_user -d teamvault
```

This doesn't require any local PostgreSQL client installation.

## Changing the External Port

If you need to use a different external port, update the `DB_EXTERNAL_PORT` in your `.env` file:

```env
DB_EXTERNAL_PORT=5434  # or any other port
```

Then restart the containers:

```bash
docker compose down
docker compose up -d
```

## Security Notes

- The database is only accessible from `localhost` (127.0.0.1)
- It is NOT exposed to external networks
- Change the default password in production deployments
- The container network keeps the database isolated from the internet

## Troubleshooting

### Port Already in Use

If port 5433 is already in use on your system, you'll see an error when starting containers. To fix:

1. Choose a different port (e.g., 5434)
2. Update `.env`: `DB_EXTERNAL_PORT=5434`
3. Restart: `docker compose down && docker compose up -d`

### Connection Refused

If you get "connection refused":

1. Check containers are running: `docker compose ps`
2. Verify port is exposed: `docker compose ps | grep db`
3. Check container logs: `docker compose logs db`

### Authentication Failed

If password is incorrect:

1. Check `.env` file for `DB_PASSWORD`
2. Default password is `secret`
3. The password must match between `.env` and your connection tool
