# Quick Start Guide

Fast-track guide to get Luminai running locally.

---

## Prerequisites

- **Docker** & **Docker Compose** installed
- **Git** installed
- **WSL2** (if on Windows)

---

## Development Setup

### 1. Quick Setup (Automated)

```bash
cd /home/user/inf
chmod +x scripts/setup.sh && ./scripts/setup.sh
```

This script will:
- Start all Docker services
- Install dependencies
- Run database migrations
- Load fixtures
- Clear and warm cache
- Verify setup

### 2. Manual Setup

```bash
# Start Docker services
docker-compose up -d

# Install PHP dependencies
docker-compose exec app composer install

# Run migrations
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction

# Install frontend assets
docker-compose exec app php bin/console importmap:install

# Clear cache
docker-compose exec app php bin/console cache:clear
docker-compose exec app php bin/console cache:warmup
```

---

## Testing

### Quick Test Execution

```bash
# Run all tests
./scripts/run-tests.sh

# Manual testing
cd /home/user/inf/app
php bin/phpunit                     # All tests
php bin/phpunit tests/Entity/       # Unit tests
php bin/phpunit tests/Controller/   # Functional tests
php bin/phpunit tests/Api/          # API tests
composer audit                      # Security audit
```

### Test Coverage

```bash
php bin/phpunit --coverage-html coverage/
# Open coverage/index.html in browser
```

---

## Docker Operations

### Starting Services

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f app
docker-compose logs -f database
docker-compose logs -f redis
docker-compose logs -f nginx
```

### Health Checks

```bash
# Application health
docker-compose exec app wget --spider http://localhost:8000/health

# Database health
docker-compose exec database pg_isready -U luminai_user -d luminai_db

# Redis health
docker-compose exec redis redis-cli ping
```

### Service Management

```bash
# Stop services
docker-compose down

# Restart specific service
docker-compose restart app
docker-compose restart nginx

# Rebuild containers
docker-compose build app
docker-compose up -d app

# View running services
docker-compose ps

# View resource usage
docker stats --no-stream
```

---

## Key Commands

### Database

```bash
# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Create new migration
php bin/console make:migration --no-interaction

# Load fixtures
php bin/console doctrine:fixtures:load --no-interaction

# Validate schema
php bin/console doctrine:schema:validate

# Execute raw SQL
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM organization"
```

### Cache

```bash
# Clear cache
php bin/console cache:clear

# Warm cache
php bin/console cache:warmup

# Clear Redis cache
docker-compose exec redis redis-cli FLUSHALL
```

### Assets

```bash
# Install assets
php bin/console importmap:install

# Add new dependency
php bin/console importmap:require package-name

# Remove dependency
php bin/console importmap:remove package-name
```

### Development

```bash
# Create entity
php bin/console make:entity EntityName --no-interaction

# Create controller
php bin/console make:controller ControllerName --no-interaction

# Create voter
php bin/console make:voter EntityVoter

# Create form
php bin/console make:form EntityType
```

---

## Access URLs

Once services are running:

- **Frontend**: https://localhost (root domain, admin access)
- **Organization Subdomain**: https://{slug}.localhost (e.g., https://acme-corporation.localhost)
- **API Documentation**: https://localhost/api
- **Health Check**: https://localhost/health
- **Detailed Health**: https://localhost/health/detailed
- **Metrics**: https://localhost/health/metrics

---

## Default Credentials

After loading fixtures:

**Super Admin:**
- Email: `admin@luminai.local`
- Password: `password`

**Organization Admin:**
- Email: `org.admin@acme.local`
- Password: `password`

**Regular User:**
- Email: `user@acme.local`
- Password: `password`

---

## Troubleshooting

### Services Not Starting

```bash
# Check Docker status
docker-compose ps

# View logs
docker-compose logs app

# Restart all services
docker-compose down && docker-compose up -d
```

### Port Conflicts

```bash
# Check what's using ports
sudo lsof -i :80 -i :443 -i :5432 -i :6379

# Stop conflicting services
sudo systemctl stop apache2 nginx redis-server postgresql
```

### Database Connection Issues

```bash
# Check database is running
docker-compose exec database pg_isready -U luminai_user -d luminai_db

# View database logs
docker-compose logs database
```

### Cache Issues

```bash
# Clear all caches
docker-compose exec app php bin/console cache:clear
docker-compose exec redis redis-cli FLUSHALL
docker-compose exec app rm -rf var/cache/*
```

### Permission Issues

```bash
# Fix permissions
docker-compose exec app chmod -R 777 var/
```

---

## Next Steps

After successful setup:

1. **Explore the application**: Visit https://localhost
2. **Check health status**: https://localhost/health/detailed
3. **Browse API**: https://localhost/api
4. **Read documentation**: See [Documentation Index](README.md)

For detailed information, see:
- [Docker Guide](DOCKER.md)
- [Development Workflow](DEVELOPMENT_WORKFLOW.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
