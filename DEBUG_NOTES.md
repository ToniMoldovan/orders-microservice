# Debug Notes

## Issue: Missing APP_KEY on first container startup

**Problem:**
When I first tried to access the application at `localhost:8080`, Laravel threw an error saying the application key wasn't set. This makes sense - the `.env` file gets copied from `.env.example` during container startup, but the example file doesn't have an `APP_KEY` value (and shouldn't, since it's sensitive).

**Diagnosis:**
Pretty straightforward - I just opened `localhost:8080` in my browser and Laravel's error page told me exactly what was wrong. The error message was clear about the missing `APP_KEY`.

**Solution:**
I added a check in `docker/scripts/entrypoint.sh` that automatically generates the `APP_KEY` if it's missing from the `.env` file. The script checks if `APP_KEY` exists and has a value, and if not, it runs `php artisan key:generate --force` to create one.

Here's the code I added:

```bash
# generate APP_KEY only if missing
if ! grep -qE '^APP_KEY=.+$' .env; then
  echo "APP_KEY missing -> generating..."
  php artisan key:generate --force
else
  echo "APP_KEY exists -> skipping key:generate"
fi
```

This way, the key gets generated automatically on first startup, but won't regenerate if it already exists (which is important to avoid breaking sessions and encrypted data).

## Issue: Nginx routing for /api prefixed vs non-prefixed requests

**Problem:**
Endpoints like `/orders` and `/health` were returning 404 errors, even though the routes were defined in `api.php` (which Laravel prefixes with `/api` by default). The nginx config needed to rewrite requests without the `/api` prefix to include it, so Laravel's router could match them.

**Diagnosis:**
After rebuilding containers, requests to `/orders` and `/health` were hitting nginx but returning 404. The routes exist at `/api/orders` and `/api/health` in Laravel, but the API spec requires them without the prefix. Using `rewrite ... last` wasn't working because it wasn't properly passing the rewritten URI to Laravel's front controller.

**Solution:**
Updated the nginx location block to use `break` instead of `last` and directly pass the rewritten request to PHP-FPM. The location block now rewrites the URI and immediately routes to `index.php` with the correct `REQUEST_URI` parameter:

```nginx
location ~ ^/(orders|health)(/.*)?$ {
    rewrite ^/(.*)$ /api/$1 break;
    fastcgi_pass php:9000;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    fastcgi_param REQUEST_URI $uri$is_args$args;
}
```

This ensures that requests to `/orders` or `/health` get rewritten to `/api/orders` and `/api/health` respectively, and Laravel receives the correct request URI to match against the routes.
