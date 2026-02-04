#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ ! -f composer.json ]; then
  echo "ERROR: composer.json not found in /var/www/html (did you mount ./src?)"
  exit 1
fi

# install dependencies only when vendor/ is missing
if [ ! -d vendor ]; then
  echo "vendor/ missing -> running composer install..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
else
  echo "vendor/ exists -> skipping composer install"
fi

# ensure we have an .env in the container
if [ ! -f .env ]; then
  echo ".env missing -> copying from .env.example..."
  cp .env.example .env
fi

# ensure .env has the DB config from container env
set_env () {
  local key="$1"
  local value="$2"

  if grep -qE "^${key}=" .env; then
    sed -i "s#^${key}=.*#${key}=${value}#g" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

# inject runtime config into .env for Laravel
set_env "APP_ENV" "${APP_ENV:-local}"
set_env "APP_DEBUG" "${APP_DEBUG:-true}"
set_env "APP_URL" "${APP_URL:-http://localhost:8080}"

set_env "DB_CONNECTION" "${DB_CONNECTION:-pgsql}"
set_env "DB_HOST" "${DB_HOST:-db}"
set_env "DB_PORT" "${DB_PORT:-5432}"
set_env "DB_DATABASE" "${DB_DATABASE:-orders}"
set_env "DB_USERNAME" "${DB_USERNAME:-orders}"
set_env "DB_PASSWORD" "${DB_PASSWORD:-orders}"

set_env "SESSION_DRIVER" "${SESSION_DRIVER:-file}"

# generate APP_KEY only if missing
if ! grep -qE '^APP_KEY=.+$' .env; then
  echo "APP_KEY missing -> generating..."
  php artisan key:generate --force
else
  echo "APP_KEY exists -> skipping key:generate"
fi

echo "Waiting for DB to be ready (pgsql)..."

php -r '
$host = getenv("DB_HOST") ?: "db";
$port = getenv("DB_PORT") ?: "5432";
$db   = getenv("DB_DATABASE") ?: "orders";
$user = getenv("DB_USERNAME") ?: "orders";
$pass = getenv("DB_PASSWORD") ?: "orders";

$dsn = "pgsql:host={$host};port={$port};dbname={$db}";

// This is a simple health check to ensure the DB is ready.
// It will try to connect to the DB 30 times with a 5 second delay between attempts.
// If the DB is not ready after 30 attempts, it will exit with an error.
for ($i = 1; $i <= 30; $i++) {
  try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->query("SELECT 1");
    echo "DB is ready.\n";
    exit(0);
  } catch (Throwable $e) {
    fwrite(STDERR, "DB not ready ({$i}/30): " . $e->getMessage() . "\n");
    sleep(min(5, $i)); // small backoff, not a blind fixed sleep
  }
}
fwrite(STDERR, "DB never became ready. Exiting.\n");
exit(1);
';

echo "Running migrations..."
php artisan migrate --force

# ensure Laravel writable dirs exist
mkdir -p storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

exec "$@"
