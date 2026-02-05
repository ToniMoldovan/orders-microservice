# Orders Microservice

A Laravel-based microservice for managing orders, containerized with Docker Compose.

## Prerequisites

- Docker and Docker Compose installed on your system
- Git

## Cloning the Repository

### Clone into a specific folder

If you want to clone the repository into a specific directory:

```bash
git clone <repository-url> orders-microservice
cd orders-microservice
```

This will create a new folder called `orders-microservice` in your current directory.

### Clone into the current folder

If you want to clone directly into your current directory:

```bash
git clone <repository-url> .
```

**Note:** Make sure the current directory is empty before using this command, otherwise Git will refuse to clone.

## Environment Variables

The project uses environment variables for configuration. You can create a `.env` file at the root level to override default values.

### Root-level Environment Variables

These variables control Docker Compose behavior and can be set in a root-level `.env` file:

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_PORT` | `8080` | Port on which the application will be accessible (mapped to nginx) |
| `POSTGRES_DB` | `orders` | PostgreSQL database name |
| `POSTGRES_USER` | `orders` | PostgreSQL database user |
| `POSTGRES_PASSWORD` | `orders` | PostgreSQL database password |
| `DB_PORT` | `5432` | PostgreSQL port (mapped to host) |

### Laravel Application Environment Variables

The Laravel application environment variables are automatically configured by the entrypoint script based on Docker Compose environment variables. These are set in `docker-compose.yml` and can be overridden:

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_ENV` | `local` | Application environment |
| `APP_DEBUG` | `true` | Enable/disable debug mode |
| `APP_URL` | `http://localhost:8080` | Application URL |
| `DB_CONNECTION` | `pgsql` | Database connection type |
| `DB_HOST` | `db` | Database host (container name) |
| `DB_PORT` | `5432` | Database port |
| `DB_DATABASE` | `orders` | Database name |
| `DB_USERNAME` | `orders` | Database username |
| `DB_PASSWORD` | `orders` | Database password |
| `SESSION_DRIVER` | `file` | Session storage driver |

**Note:** The `APP_KEY` is automatically generated on first startup if it doesn't exist.

## Getting Started

### 1. Clone the repository

```bash
git clone <repository-url> orders-microservice
cd orders-microservice
```

### 2. (Optional) Configure environment variables

If you need to customize the default configuration, create a `.env` file at the root level:

```bash
cp .env.example .env
# Edit .env with your preferred values
```

### 3. Start the services

Build and start all containers:

```bash
docker compose up --build
```

This command will:
- Build the PHP container with all dependencies
- Start PostgreSQL database
- Start PHP-FPM service
- Start Nginx web server
- Automatically run Composer install (if needed)
- Generate Laravel application key (if missing)
- Wait for database to be ready
- Run database migrations

### 4. Access the application

Once all containers are running, access the application at:

```
http://localhost:8080
```

## API Endpoints

### POST /api/orders

Create a new order. Supports idempotency - submitting the same payload multiple times returns the existing order.

**Request:**

```json
{
  "order_id": "ORD-12345",
  "customer_email": "customer@example.com",
  "total_amount": 99.99,
  "currency": "EUR",
  "created_at": "2026-02-05T12:00:00Z"
}
```

**Request Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `order_id` | string | Yes | Unique order identifier (max 255 characters) |
| `customer_email` | string | Yes | Valid email address |
| `total_amount` | number | Yes | Order total amount (≥ 0) |
| `currency` | string | Yes | 3-letter currency code (e.g., EUR, USD) |
| `created_at` | string | Yes | ISO 8601 formatted date |

**Response (201 Created):**

```json
{
  "order_id": "ORD-12345",
  "customer_email": "customer@example.com",
  "total_amount": "99.99",
  "currency": "EUR",
  "created_at": "2026-02-05T12:00:00+00:00"
}
```

**Response (200 OK):** Same as above - returned when the same payload is submitted again (idempotent).

**Response (409 Conflict):** Returned when an order with the same `order_id` but different payload already exists.

### GET /api/orders/{order_id}

Retrieve an order by its order ID.

**Response (200 OK):**

```json
{
  "order_id": "ORD-12345",
  "customer_email": "customer@example.com",
  "total_amount": "99.99",
  "currency": "EUR",
  "created_at": "2026-02-05T12:00:00+00:00"
}
```

**Response (404 Not Found):**

```json
{
  "message": "Order not found"
}
```

### GET /api/health

Check the health status of the API, database connection, and schema.

**Response (200 OK):**

```json
{
  "status": "ok",
  "api": "ok",
  "db": "ok",
  "schema": "ok"
}
```

**Response (503 Service Unavailable):** Returned when database connection fails or schema is not applied.

## Testing

### Running Tests

Run all tests from the project root:

```bash
docker compose exec php php artisan test
```

Run only feature tests:

```bash
docker compose exec php php artisan test --testsuite=Feature
```

### Test Database Strategy

Tests use **SQLite in-memory** database (configured in `phpunit.xml`). This approach:

- **Isolates test data** - Each test run starts with a fresh database via Laravel's `RefreshDatabase` trait
- **Runs faster** - In-memory database eliminates disk I/O and network overhead
- **Requires no setup** - No separate test database container needed
- **Is standard practice** - Laravel's default testing configuration

The production PostgreSQL database is never touched during testing. All migrations run automatically before each test and roll back afterward, ensuring complete isolation.

## Container Information

| Container | Name | Port | Description |
|-----------|------|------|-------------|
| PHP | `orders-php` | - | Laravel application with PHP-FPM |
| Nginx | `orders-nginx` | `8080` | Web server (reverse proxy) |
| PostgreSQL | `orders-db` | `5432` | Database server |

## Stopping the Services

To stop all containers:

```bash
docker compose down
```

To stop and remove volumes (this will delete the database data):

```bash
docker compose down -v
```

## Troubleshooting

### Access database

```bash
docker compose exec db psql -U orders -d orders
```