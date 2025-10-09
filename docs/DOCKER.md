# Docker Architecture Guide

Complete Docker setup and management for Luminai.

---

## Architecture Overview

Luminai uses a **4-service Docker architecture**:

```
┌─────────────┐
│   nginx     │  → SSL termination, reverse proxy
│   (443)     │     Wildcard *.localhost support
└──────┬──────┘
       │
┌──────▼──────┐
│     app     │  → FrankenPHP 1.9 + PHP 8.4 + Symfony 7.3
│   (8000)    │     Worker mode, OPcache enabled
└──────┬──────┘
       │
       ├────────────────┬────────────────┐
       │                │                │
┌──────▼──────┐  ┌─────▼──────┐  ┌─────▼──────┐
│  database   │  │   redis    │  │   nginx    │
│   (5432)    │  │   (6379)   │  │   (443)    │
│ PostgreSQL  │  │  Redis 7   │  │  Reverse   │
│    18       │  │  Cache     │  │   Proxy    │
└─────────────┘  └────────────┘  └────────────┘
```

---

## Services

### 1. App Service (FrankenPHP)

**Purpose**: Symfony application server

**Technology Stack**:
- FrankenPHP 1.9 (based on Caddy server)
- PHP 8.4 with extensions: pdo_pgsql, opcache, intl, gd, redis
- Symfony 7.3
- API Platform 4.1

**Configuration**:
```yaml
# docker-compose.yml
app:
  build:
    context: ./app
    dockerfile: docker/frankenphp/Dockerfile
  environment:
    - DATABASE_URL=postgresql://luminai_user:LuminaiSecure2025!@database:5432/luminai_db
    - REDIS_URL=redis://redis:6379/0
    - FRANKENPHP_NUM_THREADS=4
    - OPCACHE_ENABLED=true
  volumes:
    - ./app:/app
  ports:
    - "8000:8000"
```

**Health Check**:
```bash
docker-compose exec app wget --spider http://localhost:8000/health
```

### 2. Database Service (PostgreSQL 18)

**Purpose**: Primary database with UUIDv7 support

**Features**:
- PostgreSQL 18
- Native UUID functions (`uuidv7()`)
- Extensions: `uuid-ossp`, `unaccent`
- Persistent volume for data

**Configuration**:
```yaml
database:
  image: postgres:18-alpine
  environment:
    - POSTGRES_DB=luminai_db
    - POSTGRES_USER=luminai_user
    - POSTGRES_PASSWORD=LuminaiSecure2025!
  volumes:
    - postgres_data:/var/lib/postgresql/data
    - ./database/init:/docker-entrypoint-initdb.d
  ports:
    - "5432:5432"
```

**Health Check**:
```bash
docker-compose exec database pg_isready -U luminai_user -d luminai_db
```

**Test UUIDv7**:
```bash
docker-compose exec database psql -U luminai_user -d luminai_db -c "SELECT uuidv7();"
```

### 3. Redis Service (Redis 7)

**Purpose**: Caching and session storage

**Features**:
- Redis 7 with LRU eviction
- Memory limit: 256MB
- Persistent volume

**Configuration**:
```yaml
redis:
  image: redis:7-alpine
  command: redis-server --maxmemory 256mb --maxmemory-policy allkeys-lru
  volumes:
    - redis_data:/data
  ports:
    - "6379:6379"
```

**Health Check**:
```bash
docker-compose exec redis redis-cli ping
# Expected: PONG
```

**Monitor Memory**:
```bash
docker-compose exec redis redis-cli info memory
```

### 4. Nginx Service

**Purpose**: SSL termination and reverse proxy

**Features**:
- SSL/TLS 1.2 & 1.3
- Wildcard subdomain support (`*.localhost`)
- HTTP to HTTPS redirect
- Security headers

**Configuration**:
```nginx
# nginx/conf/default.conf
server {
    listen 443 ssl http2;
    server_name localhost *.localhost;

    ssl_certificate /etc/nginx/ssl/localhost.crt;
    ssl_certificate_key /etc/nginx/ssl/localhost.key;

    location / {
        proxy_pass http://app:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

**Health Check**:
```bash
curl -k https://localhost/health
```

---

## Common Operations

### Starting Services

```bash
# Start all services
docker-compose up -d

# Start specific service
docker-compose up -d app

# View startup logs
docker-compose logs -f
```

### Stopping Services

```bash
# Stop all services
docker-compose down

# Stop but keep volumes
docker-compose stop

# Stop and remove volumes (⚠️ deletes data)
docker-compose down -v
```

### Restarting Services

```bash
# Restart all
docker-compose restart

# Restart specific service
docker-compose restart app
docker-compose restart nginx
docker-compose restart database
docker-compose restart redis
```

### Rebuilding Containers

```bash
# Rebuild app container
docker-compose build app

# Rebuild without cache
docker-compose build --no-cache app

# Rebuild and restart
docker-compose build app && docker-compose up -d app
```

### Viewing Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f database
docker-compose logs -f redis
docker-compose logs -f nginx

# Last 100 lines
docker-compose logs --tail=100 app
```

### Executing Commands

```bash
# Execute command in app container
docker-compose exec app php bin/console cache:clear

# Execute as root
docker-compose exec -u root app chmod -R 777 var/

# Execute without TTY (for scripts)
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
```

### Service Status

```bash
# View running services
docker-compose ps

# View resource usage
docker stats --no-stream

# View detailed service info
docker-compose inspect app
```

---

## Health Monitoring

### Application Health

```bash
# Simple health check
curl -k https://localhost/health

# Detailed health with JSON
curl -k https://localhost/health/detailed | jq .

# Metrics endpoint
curl -k https://localhost/health/metrics | jq .
```

### Database Health

```bash
# Connection test
docker-compose exec database pg_isready -U luminai_user -d luminai_db

# Database size
docker-compose exec database psql -U luminai_user -d luminai_db -c "
  SELECT pg_size_pretty(pg_database_size('luminai_db'));"

# Active connections
docker-compose exec database psql -U luminai_user -d luminai_db -c "
  SELECT count(*) FROM pg_stat_activity;"
```

### Redis Health

```bash
# Connection test
docker-compose exec redis redis-cli ping

# Memory usage
docker-compose exec redis redis-cli info memory

# Key count
docker-compose exec redis redis-cli dbsize

# Cache hit rate
docker-compose exec redis redis-cli info stats | grep hits
```

### Nginx Health

```bash
# Test configuration
docker-compose exec nginx nginx -t

# Reload configuration
docker-compose exec nginx nginx -s reload

# View connections
docker-compose exec nginx netstat -an | grep :443
```

---

## Performance Monitoring

### Resource Usage

```bash
# CPU and memory for all services
docker stats

# Continuous monitoring
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}"
```

### FrankenPHP Worker Mode

```bash
# Check worker processes
docker-compose exec app ps aux | grep frankenphp

# Monitor worker performance
docker-compose exec app tail -f var/log/performance.log
```

### Database Performance

```bash
# Slow query log
docker-compose logs database | grep "duration:"

# Table sizes
docker-compose exec database psql -U luminai_user -d luminai_db -c "
  SELECT schemaname, tablename,
         pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
  FROM pg_tables
  WHERE schemaname = 'public'
  ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC;"
```

### Redis Performance

```bash
# Cache statistics
docker-compose exec redis redis-cli info stats

# Memory analysis
docker-compose exec redis redis-cli --bigkeys

# Monitor commands in real-time
docker-compose exec redis redis-cli monitor
```

---

## Troubleshooting

### Port Conflicts

```bash
# Check what's using Docker ports
sudo lsof -i :80 -i :443 -i :5432 -i :6379

# Stop conflicting services
sudo systemctl stop apache2 nginx postgresql redis-server
```

### Nginx 502 Bad Gateway

**Cause**: Nginx cached old app container IP after rebuild

```bash
# Solution: Restart nginx to refresh DNS
docker-compose restart nginx

# Verify nginx can reach app
docker-compose exec nginx ping -c 2 app
```

### Database Connection Refused

```bash
# Check database is running
docker-compose exec database pg_isready -U luminai_user -d luminai_db

# View database logs
docker-compose logs database

# Restart database
docker-compose restart database
```

### Redis Connection Refused

```bash
# Check Redis is running
docker-compose exec redis redis-cli ping

# View Redis logs
docker-compose logs redis

# Restart Redis
docker-compose restart redis
```

### SSL Certificate Issues

```bash
# Regenerate SSL certificates
rm -rf nginx/ssl/*
./scripts/generate-ssl.sh

# Restart nginx
docker-compose restart nginx

# Verify certificate
openssl x509 -in nginx/ssl/localhost.crt -text -noout | grep DNS
```

### Performance Issues

```bash
# Check OPcache status
docker-compose exec app php -r "print_r(opcache_get_status());"

# Check Redis memory
docker-compose exec redis redis-cli info memory

# Check database connections
docker-compose exec database psql -U luminai_user -d luminai_db -c "
  SELECT count(*) FROM pg_stat_activity WHERE state = 'active';"
```

### Disk Space Issues

```bash
# Check Docker disk usage
docker system df

# Clean up unused resources
docker system prune -a

# Remove old images
docker image prune -a
```

---

## Emergency Recovery

### Complete Reset

```bash
# ⚠️ WARNING: This deletes ALL data

# Stop all services
docker-compose down -v

# Clean up Docker resources
docker system prune -a -f

# Rebuild from scratch
docker-compose build --no-cache
chmod +x scripts/setup.sh && ./scripts/setup.sh
```

### Database Backup & Restore

**Backup**:
```bash
# Backup to file
docker-compose exec -T database pg_dump -U luminai_user luminai_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

**Restore**:
```bash
# Restore from file
docker-compose exec -T database psql -U luminai_user luminai_db < backup.sql
```

---

## Best Practices

### 1. Regular Health Checks

```bash
# Add to crontab for automated monitoring
*/5 * * * * docker-compose exec -T app wget --spider http://localhost:8000/health || echo "Health check failed"
```

### 2. Log Rotation

```yaml
# docker-compose.yml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

### 3. Resource Limits

```yaml
# docker-compose.yml
app:
  deploy:
    resources:
      limits:
        cpus: '2'
        memory: 2G
      reservations:
        memory: 512M
```

### 4. Automated Backups

```bash
# scripts/backup-db.sh
#!/bin/bash
docker-compose exec -T database pg_dump -U luminai_user luminai_db | \
  gzip > /backups/luminai_$(date +%Y%m%d_%H%M%S).sql.gz
```

### 5. Monitor Logs

```bash
# Monitor for errors
docker-compose logs -f | grep -i error

# Monitor for critical issues
docker-compose logs -f | grep -i critical
```

---

## Quick Reference

### Essential Commands

```bash
# Start
docker-compose up -d

# Stop
docker-compose down

# Restart
docker-compose restart app

# Logs
docker-compose logs -f app

# Health
curl -k https://localhost/health/detailed | jq .

# Rebuild
docker-compose build app && docker-compose up -d app
```

### Service Ports

- **App (FrankenPHP)**: 8000 (internal), 443 (via nginx)
- **Database (PostgreSQL)**: 5432
- **Redis**: 6379
- **Nginx**: 80 (redirect), 443 (SSL)

---

For more information:
- [Quick Start Guide](QUICK_START.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
- [Production Deployment](ProductionDeployment.md)
