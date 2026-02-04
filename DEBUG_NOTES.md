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
