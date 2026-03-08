# LFS Deployment Runbook

This document covers deployment steps and validation for the Logistics Financial System (LFS) in line with the enterprise technical specification.

## 1. Server requirements

- **OS:** Linux (e.g. Ubuntu 22.04 LTS)
- **Web server:** Nginx (or Apache)
- **PHP:** 8.4+ with extensions: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo_mysql (or pdo_pgsql), tokenizer, xml
- **Database:** MySQL 8.x or PostgreSQL 15+
- **Redis:** 6+ (recommended for cache and queue)
- **SSL:** TLS certificate (e.g. Let’s Encrypt)

## 2. Environment configuration

1. Copy `.env.example` to `.env` and set:

   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL` = public URL of the application
   - `DB_*` (connection, database, username, password)
   - `CACHE_DRIVER=redis` (recommended)
   - `QUEUE_CONNECTION=redis` (recommended)
   - `SESSION_DRIVER=database` or `redis`
   - `REDIS_HOST`, `REDIS_PASSWORD` if using Redis

2. Generate application key:

   ```bash
   php artisan key:generate
   ```

3. Run migrations:

   ```bash
   php artisan migrate --force
   ```

4. Seed permissions (and optional demo data):

   ```bash
   php artisan db:seed --class=ModulePermissionsSeeder --force
   # Optional: php artisan db:seed --force
   ```

5. Cache config and routes:

   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## 3. Queue worker (Supervisor)

If the app uses queues, run a worker via Supervisor.

Example `/etc/supervisor/conf.d/lfs-worker.conf`:

```ini
[program:lfs-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lfs/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/lfs/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor after changes:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start lfs-worker:*
```

## 4. Scheduler (cron)

Laravel scheduler for scheduled tasks (e.g. daily jobs):

```bash
* * * * * cd /var/www/lfs && php artisan schedule:run >> /dev/null 2>&1
```

## 5. Nginx example

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-fms.example.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-fms.example.com;
    root /var/www/lfs/public;

    ssl_certificate     /etc/ssl/certs/your-fms.example.com.crt;
    ssl_certificate_key /etc/ssl/private/your-fms.example.com.key;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60;
    }
}
```

## 6. Health and monitoring

- **Laravel health route:** `GET /up` (default Laravel health endpoint).
- **API health (JSON):** `GET /api/health`  
  Returns `200` with `{"status":"ok","database":"ok",...}` or `503` if database is unavailable. Use this for load balancers and monitoring.

## 7. Integration and API

- **Financial events API:** `POST /api/financial-events/{event_type}`  
  Requires `auth:sanctum` and permission `integration.financial-events`. Use idempotency keys to prevent duplicate posting.
- **WMS billing feed:** `POST /api/wms-billing/feed`  
  Requires `auth:sanctum` and permission `integration.wms-billing`.
- Ensure API consumers use HTTPS and that tokens/users have the correct permissions (e.g. via LFS Administration → Role & Permission Management).

## 8. Pre-release validation checklist

Before going live, confirm:

- [ ] Double-entry enforcement verified (journal posting balanced).
- [ ] Period locking functional (no posting in closed periods).
- [ ] Audit logs active (LFS Administration → Audit Logs).
- [ ] API idempotency working (duplicate idempotency key returns `duplicate` and does not double-post).
- [ ] Event duplication prevented (Integration Center → Financial Events Monitor / Sync Logs).
- [ ] Reports balanced with GL (trial balance, P&L).
- [ ] Trial balance zero-difference validated.
- [ ] `GET /api/health` returns 200 in production.
- [ ] Queue worker and scheduler running (if used).
- [ ] Backups and SSL in place per enterprise spec.

## 9. Post-deployment

- Monitor **LFS Administration → Financial Events Monitor** and **Sync Logs** for integration traffic.
- Retain application and financial audit logs according to policy.
- Apply security and dependency updates via a controlled release process.
