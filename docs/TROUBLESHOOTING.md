# Troubleshooting Guide

Common issues and their solutions for Luminai.

---

## Table of Contents

- [Docker Issues](#docker-issues)
- [Database Issues](#database-issues)
- [Redis Issues](#redis-issues)
- [Nginx Issues](#nginx-issues)
- [SSL/TLS Issues](#ssl-tls-issues)
- [Performance Issues](#performance-issues)
- [Cache Issues](#cache-issues)
- [Multi-Tenant Issues](#multi-tenant-issues)
- [Migration Issues](#migration-issues)
- [Permission Issues](#permission-issues)

---

## Docker Issues

### Port Conflicts

**Problem**: Can't start Docker services due to port already in use

**Symptoms**:
```
Error: bind: address already in use
```

**Solution**:
```bash
# Check what's using the ports
sudo lsof -i :80 -i :443 -i :5432 -i :6379

# Stop conflicting services
sudo systemctl stop apache2
sudo systemctl stop nginx
sudo systemctl stop postgresql
sudo systemctl stop redis-server

# Start Docker services
docker-compose up -d
```

### Services Not Starting

**Problem**: Docker containers fail to start

**Diagnosis**:
```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs app
docker-compose logs database
docker-compose logs redis
docker-compose logs nginx
```

**Solution**:
```bash
# Restart all services
docker-compose down
docker-compose up -d

# Rebuild if needed
docker-compose build --no-cache app
docker-compose up -d app
```

### Out of Disk Space

**Problem**: Docker running out of disk space

**Diagnosis**:
```bash
# Check Docker disk usage
docker system df

# Check system disk usage
df -h
```

**Solution**:
```bash
# Clean up Docker resources
docker system prune -a

# Remove unused volumes
docker volume prune

# Remove unused images
docker image prune -a
```

---

## Database Issues

### Connection Refused

**Problem**: Application can't connect to database

**Symptoms**:
```
SQLSTATE[08006] Connection refused
```

**Diagnosis**:
```bash
# Check if database is running
docker-compose ps database

# Test database connection
docker-compose exec database pg_isready -U luminai_user -d luminai_db
```

**Solution**:
```bash
# Restart database
docker-compose restart database

# Check logs
docker-compose logs database

# Verify connection from app container
docker-compose exec app psql -h database -U luminai_user -d luminai_db
```

### Schema Not in Sync

**Problem**: Database schema doesn't match entity definitions

**Symptoms**:
```
The database schema is not in sync with the current mapping file
```

**Diagnosis**:
```bash
# Validate schema
php bin/console doctrine:schema:validate
```

**Solution**:
```bash
# Create migration
php bin/console make:migration --no-interaction

# Review migration file
cat migrations/VersionXXXXXXXXXXXXXX.php

# Execute migration
php bin/console doctrine:migrations:migrate --no-interaction
```

### UUIDv7 Not Working

**Problem**: UUIDv7 generation failing

**Diagnosis**:
```bash
# Test UUIDv7 function
docker-compose exec database psql -U luminai_user -d luminai_db -c "SELECT uuidv7();"
```

**Solution**:
```bash
# Check if uuid-ossp extension exists
docker-compose exec database psql -U luminai_user -d luminai_db -c "
SELECT * FROM pg_extension WHERE extname = 'uuid-ossp';"

# If not installed, check database init script
cat database/init/01-uuid.sql

# Rebuild database (⚠️ destroys data)
docker-compose down -v
docker-compose up -d database
```

---

## Redis Issues

### Connection Refused

**Problem**: Application can't connect to Redis

**Diagnosis**:
```bash
# Check if Redis is running
docker-compose ps redis

# Test Redis connection
docker-compose exec redis redis-cli ping
```

**Solution**:
```bash
# Restart Redis
docker-compose restart redis

# Check logs
docker-compose logs redis

# Verify from app container
docker-compose exec app php -r "
\$redis = new Redis();
\$redis->connect('redis', 6379);
echo 'Connected: ' . \$redis->ping() . PHP_EOL;
"
```

### Redis PHP Extension Not Loaded

**Problem**: Redis PHP extension not available

**Symptoms**:
```
Class "Redis" not found
```

**Diagnosis**:
```bash
# Check if Redis extension is loaded
docker-compose exec app php -m | grep redis
```

**Solution**:
```bash
# Rebuild app container (should include Redis extension)
docker-compose build --no-cache app
docker-compose up -d app

# Verify
docker-compose exec app php -m | grep redis
```

### Out of Memory

**Problem**: Redis running out of memory

**Diagnosis**:
```bash
# Check Redis memory usage
docker-compose exec redis redis-cli info memory
```

**Solution**:
```bash
# Increase memory limit in docker-compose.yml
# command: redis-server --maxmemory 512mb --maxmemory-policy allkeys-lru

# Restart Redis
docker-compose down redis
docker-compose up -d redis

# Or clear Redis cache
docker-compose exec redis redis-cli FLUSHALL
```

---

## Nginx Issues

### 502 Bad Gateway

**Problem**: Nginx shows 502 Bad Gateway error

**Symptoms**:
```
502 Bad Gateway
```

**Diagnosis**:
```bash
# Check if app container is running
docker-compose ps app

# Check nginx logs
docker-compose logs nginx

# Check app logs
docker-compose logs app
```

**Solution**:
```bash
# Restart nginx (refreshes DNS cache)
docker-compose restart nginx

# Verify nginx can reach app
docker-compose exec nginx ping -c 2 app

# Restart app if needed
docker-compose restart app
```

### Configuration Errors

**Problem**: Nginx fails to start due to configuration errors

**Diagnosis**:
```bash
# Test nginx configuration
docker-compose exec nginx nginx -t

# Check logs
docker-compose logs nginx
```

**Solution**:
```bash
# Fix configuration in nginx/conf/default.conf
# Then reload configuration
docker-compose exec nginx nginx -s reload

# Or restart nginx
docker-compose restart nginx
```

---

## SSL/TLS Issues

### Certificate Not Valid

**Problem**: SSL certificate errors or warnings

**Symptoms**:
```
NET::ERR_CERT_AUTHORITY_INVALID
```

**Solution**:
```bash
# Regenerate SSL certificates
rm -rf nginx/ssl/*
./scripts/generate-ssl.sh

# Restart nginx
docker-compose restart nginx

# Verify certificate
openssl x509 -in nginx/ssl/localhost.crt -text -noout | grep DNS
```

### Wildcard Subdomain Not Working

**Problem**: Organization subdomains not accessible

**Diagnosis**:
```bash
# Check certificate SAN (Subject Alternative Names)
openssl x509 -in nginx/ssl/localhost.crt -text -noout | grep DNS

# Should show:
# DNS:localhost, DNS:*.localhost
```

**Solution**:
```bash
# Ensure generate-ssl.sh includes wildcard
# Check [alt_names] section:
# DNS.1 = localhost
# DNS.2 = *.localhost

# Regenerate certificates
./scripts/generate-ssl.sh
docker-compose restart nginx
```

---

## Performance Issues

### Slow Response Times

**Problem**: Application responding slowly

**Diagnosis**:
```bash
# Check performance logs
docker-compose exec app tail -f var/log/performance.log | jq .

# Check OPcache status
docker-compose exec app php -r "print_r(opcache_get_status());"

# Check Redis memory
docker-compose exec redis redis-cli info memory

# Check database connections
docker-compose exec database psql -U luminai_user -d luminai_db -c "
SELECT count(*) FROM pg_stat_activity WHERE state = 'active';"
```

**Solution**:
```bash
# Clear caches
docker-compose exec app php bin/console cache:clear
docker-compose exec redis redis-cli FLUSHALL

# Restart services
docker-compose restart app redis

# Check resource usage
docker stats
```

### High Memory Usage

**Problem**: Containers using too much memory

**Diagnosis**:
```bash
# Check container memory usage
docker stats --no-stream

# Check PHP memory
docker-compose exec app php -r "
echo 'Memory limit: ' . ini_get('memory_limit') . PHP_EOL;
echo 'Peak usage: ' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB' . PHP_EOL;
"
```

**Solution**:
```bash
# Increase PHP memory limit in docker/frankenphp/php.ini
# memory_limit = 512M

# Rebuild container
docker-compose build app
docker-compose up -d app

# Set Docker resource limits in docker-compose.yml
# deploy:
#   resources:
#     limits:
#       memory: 2G
```

### OPcache Not Working

**Problem**: OPcache disabled or not optimized

**Diagnosis**:
```bash
# Check OPcache status
docker-compose exec app php -r "var_dump(opcache_get_status());"
```

**Solution**:
```bash
# Ensure OPCACHE_ENABLED=true in docker-compose.yml
# Rebuild container
docker-compose build app
docker-compose up -d app

# Verify
docker-compose exec app php -r "
\$status = opcache_get_status();
echo 'OPcache enabled: ' . (\$status ? 'Yes' : 'No') . PHP_EOL;
"
```

---

## Cache Issues

### Cache Not Clearing

**Problem**: Changes not reflecting after cache clear

**Solution**:
```bash
# Nuclear option - remove cache directory
docker-compose exec app rm -rf var/cache/*

# Clear Symfony cache
docker-compose exec app php bin/console cache:clear

# Warm cache
docker-compose exec app php bin/console cache:warmup

# Clear Redis cache
docker-compose exec redis redis-cli FLUSHALL

# Restart app
docker-compose restart app
```

### Session Issues

**Problem**: Users getting logged out or session not persisting

**Diagnosis**:
```bash
# Check Redis connection
docker-compose exec redis redis-cli ping

# Check session keys
docker-compose exec redis redis-cli keys "session:*"
```

**Solution**:
```bash
# Verify REDIS_URL in .env
# REDIS_URL=redis://redis:6379/0

# Restart Redis
docker-compose restart redis

# Clear all sessions
docker-compose exec redis redis-cli FLUSHDB
```

---

## Multi-Tenant Issues

### Organization Filter Not Working

**Problem**: Users seeing data from other organizations

**Diagnosis**:
```bash
# Check if filter is registered
php bin/console debug:config doctrine orm filters

# Check logs for filter status
docker-compose logs app | grep "Organization filter"
```

**Solution**:
```bash
# Verify OrganizationFilterConfigurator is registered
php bin/console debug:event-dispatcher kernel.request

# Ensure entities have organization field
# Check src/Doctrine/Filter/OrganizationFilter.php

# Clear cache
php bin/console cache:clear
```

### Subdomain Not Detected

**Problem**: Organization not loaded from subdomain

**Diagnosis**:
```bash
# Check nginx configuration
cat nginx/conf/default.conf | grep server_name

# Should include: server_name localhost *.localhost;

# Check SSL certificate
openssl x509 -in nginx/ssl/localhost.crt -text -noout | grep DNS

# Should include: DNS:localhost, DNS:*.localhost
```

**Solution**:
```bash
# Regenerate SSL certificate with wildcard
./scripts/generate-ssl.sh

# Verify nginx config
docker-compose exec nginx nginx -t

# Restart nginx
docker-compose restart nginx
```

### User Can't Login to Organization Subdomain

**Problem**: User gets authentication error on organization subdomain

**Diagnosis**:
```bash
# Check organization slug
docker-compose exec app php bin/console doctrine:query:sql "
SELECT id, name, slug FROM organization"

# Check user's organization
docker-compose exec app php bin/console doctrine:query:sql "
SELECT u.email, o.slug
FROM \"user\" u
JOIN organization o ON u.organization_id = o.id"
```

**Solution**:
```bash
# Ensure user belongs to organization with matching slug
# Ensure OrganizationAwareAuthenticator is configured

# Check security logs
docker-compose exec app tail -f var/log/security.log | jq .
```

---

## Migration Issues

### Migration Failed

**Problem**: Migration throws error during execution

**Diagnosis**:
```bash
# Check migration status
php bin/console doctrine:migrations:status

# View executed migrations
php bin/console doctrine:migrations:list
```

**Solution**:
```bash
# Rollback last migration
php bin/console doctrine:migrations:migrate prev

# Review migration file
cat migrations/VersionXXXXXXXXXXXXXX.php

# Fix migration and re-run
php bin/console doctrine:migrations:migrate --no-interaction
```

### Migration Out of Sync

**Problem**: Migration version table out of sync

**Diagnosis**:
```bash
# Check database migration versions
docker-compose exec database psql -U luminai_user -d luminai_db -c "
SELECT version, executed_at FROM doctrine_migration_versions ORDER BY executed_at DESC LIMIT 10;"
```

**Solution**:
```bash
# Mark migration as executed (if already applied)
php bin/console doctrine:migrations:version --add VersionXXXXXXXXXXXXXX

# Or mark as not executed
php bin/console doctrine:migrations:version --delete VersionXXXXXXXXXXXXXX
```

---

## Permission Issues

### File Permission Denied

**Problem**: Can't write to var/ or public/ directories

**Solution**:
```bash
# Fix permissions inside container
docker-compose exec app chmod -R 777 var/
docker-compose exec app chmod -R 777 public/uploads/

# Or as root
docker-compose exec -u root app chmod -R 777 var/ public/uploads/
```

### Cache Permission Issues

**Problem**: Can't clear cache due to permissions

**Solution**:
```bash
# Remove cache directory as root
docker-compose exec -u root app rm -rf var/cache/*

# Clear cache normally
docker-compose exec app php bin/console cache:clear

# Fix permissions
docker-compose exec app chmod -R 777 var/
```

---

## Emergency Recovery

### Complete Reset

If everything is broken, nuclear option:

```bash
# ⚠️ WARNING: This destroys ALL data

# Stop all services
docker-compose down -v

# Clean up Docker
docker system prune -a -f

# Remove var/ directory
rm -rf app/var/cache/* app/var/log/*

# Rebuild from scratch
docker-compose build --no-cache
docker-compose up -d

# Setup application
chmod +x scripts/setup.sh && ./scripts/setup.sh
```

### Database Restore

If database is corrupted:

```bash
# Backup current state
docker-compose exec -T database pg_dump -U luminai_user luminai_db > backup_broken.sql

# Restore from known good backup
docker-compose exec -T database psql -U luminai_user luminai_db < backup_good.sql

# Or reset database completely
docker-compose down -v database
docker-compose up -d database

# Run migrations
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Load fixtures
docker-compose exec app php bin/console doctrine:fixtures:load --no-interaction
```

---

## Getting Help

### Check Logs First

```bash
# Application logs
docker-compose logs --tail=100 app

# Database logs
docker-compose logs --tail=100 database

# Redis logs
docker-compose logs --tail=100 redis

# Nginx logs
docker-compose logs --tail=100 nginx

# All logs
docker-compose logs --tail=100
```

### Health Check

```bash
# Comprehensive health status
curl -k https://localhost/health/detailed | jq .
```

### System Status

```bash
# Docker containers
docker-compose ps

# Resource usage
docker stats --no-stream

# Disk usage
docker system df
df -h
```

---

For more information:
- [Docker Guide](DOCKER.md)
- [Monitoring Guide](MONITORING.md)
- [Quick Start Guide](QUICK_START.md)
- [VPS Deployment](VPS_DEPLOYMENT.md)
