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

## Viewing Container Logs

### View logs for all containers

To see logs from all containers:

```bash
docker compose logs -f
```

### View logs for a specific container

To see logs from a specific container:

```bash
# PHP container (most interesting - shows DB setup, migrations, etc.)
docker compose logs -f php

# Database container
docker compose logs -f db

# Nginx container
docker compose logs -f nginx
```

### View logs without following

To see logs without following (one-time output):

```bash
docker compose logs php
```

**Tip:** The PHP container logs are particularly useful as they show:
- Composer dependency installation
- Laravel application key generation
- Database connection attempts
- Migration execution
- Any errors during startup

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

### Check container status

```bash
docker compose ps
```

### Access PHP container shell

```bash
docker compose exec php bash
```

### Access database

```bash
docker compose exec db psql -U orders -d orders
```

### View PHP container logs during startup

The PHP container performs several initialization steps. To see them in real-time:

```bash
docker compose logs -f php
```

You should see output like:
- `vendor/ missing -> running composer install...` or `vendor/ exists -> skipping composer install`
- `.env missing -> copying from .env.example...` (on first run)
- `APP_KEY missing -> generating...` (on first run)
- `Waiting for DB to be ready (pgsql)...`
- `DB is ready.`
- `Running migrations...`
