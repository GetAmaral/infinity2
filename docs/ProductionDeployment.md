# Production Deployment Guide

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Deployment Steps](#deployment-steps)
3. [Post-Deployment Verification](#post-deployment-verification)
4. [Rollback Plan](#rollback-plan)
5. [Monitoring](#monitoring)
6. [CI/CD Integration](#cicd-integration)

---

## Pre-Deployment Checklist

### Code Quality

- [ ] **All tests pass**
```bash
php bin/phpunit
# Expected: 100% pass rate, 80%+ coverage
```

- [ ] **PHPStan level 8 passes**
```bash
vendor/bin/phpstan analyse src --level=8
# Expected: 0 errors
```

- [ ] **Code style compliant**
```bash
vendor/bin/php-cs-fixer fix --dry-run
# Expected: 0 files need fixing
```

- [ ] **Security audit clean**
```bash
composer audit
# Expected: 0 vulnerabilities
```

### Generator Validation

- [ ] **CSV validation passes**
```bash
php scripts/verify-csv-migration.php
# Expected: All checks pass
```

- [ ] **Pre-generation check passes**
```bash
php scripts/pre-generation-check.php
# Expected: 0 errors, 0 warnings
```

- [ ] **Dry run succeeds**
```bash
php bin/console genmax:generate --dry-run
# Expected: Preview of all files, no errors
```

- [ ] **Performance tests pass**
```bash
php scripts/performance-test.php
# Expected: All targets met
```

### Database

- [ ] **Backup current database**
```bash
# PostgreSQL
pg_dump -U luminai_user luminai_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Or via Docker
docker-compose exec -T database pg_dump -U luminai_user luminai_db > backup.sql
```

- [ ] **Migrations are ready**
```bash
php bin/console doctrine:migrations:status
# Expected: All migrations up-to-date
```

- [ ] **Database schema is valid**
```bash
php bin/console doctrine:schema:validate
# Expected: Schema and mapping files are in sync
```

### Application

- [ ] **Dependencies updated**
```bash
composer install --no-dev --optimize-autoloader
```

- [ ] **Assets compiled**
```bash
php bin/console importmap:install
```

- [ ] **Cache cleared**
```bash
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### Environment

- [ ] **Environment variables set**
```bash
# Check .env.prod or environment
echo $APP_ENV                  # Should be: prod
echo $DATABASE_URL             # Correct production database
echo $REDIS_URL                # Correct Redis server
```

- [ ] **Permissions correct**
```bash
# On server
chmod -R 755 var/
chown -R www-data:www-data var/ public/
```

- [ ] **Disk space sufficient**
```bash
df -h
# Minimum 5GB free recommended
```

---

## Deployment Steps

### Step 1: Code Deployment

**Option A: Git Deployment (Recommended)**

```bash
# On production server
cd /var/www/luminai

# Backup current code
tar -czf backup_code_$(date +%Y%m%d_%H%M%S).tar.gz .

# Pull latest code
git fetch origin
git checkout main
git pull origin main

# Verify git status
git status
git log -1
```

**Option B: Manual Upload**

```bash
# From local machine
rsync -avz --exclude='var/' --exclude='vendor/' \
    ./ user@production:/var/www/luminai/

# On production server
cd /var/www/luminai
```

### Step 2: Install Dependencies

```bash
# Production optimized install
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Verify critical packages
composer show symfony/framework-bundle
composer show api-platform/core
```

### Step 3: Run Migrations

```bash
# Check migration status
php bin/console doctrine:migrations:status

# Run migrations with backup
php bin/console doctrine:migrations:migrate --no-interaction

# Verify migration
php bin/console doctrine:migrations:status
```

### Step 4: Generate Code (if CSV changed)

**Only if CSV files were updated:**

```bash
# Verify CSV first
php scripts/verify-csv-migration.php

# Generate code
php bin/console genmax:generate

# Run new migrations
php bin/console make:migration
php bin/console doctrine:migrations:migrate --no-interaction
```

### Step 5: Clear & Warm Cache

```bash
# Clear all caches
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug

# Verify cache
ls -lah var/cache/prod/
```

### Step 6: Restart Services

**Option A: FrankenPHP (via Docker)**

```bash
docker-compose restart app
docker-compose ps
```

**Option B: PHP-FPM + Nginx**

```bash
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

**Option C: Apache**

```bash
sudo systemctl restart apache2
```

### Step 7: Verify Deployment

Run immediate health checks:

```bash
# Health endpoint
curl https://yourdomain.com/health/detailed | jq .

# Check critical endpoints
curl -I https://yourdomain.com/
curl -I https://yourdomain.com/api

# Check database connectivity
php bin/console doctrine:query:sql "SELECT 1"
```

---

## Post-Deployment Verification

### Automated Verification

Run comprehensive checks:

```bash
# System health
curl https://yourdomain.com/health/detailed

# Performance test
php scripts/performance-test.php --report=deployment.json

# Quick smoke tests
php bin/phpunit tests/Smoke/
```

### Manual Verification

**Test Critical User Paths:**

1. **Authentication**
   - [ ] Login works
   - [ ] Logout works
   - [ ] Password reset works

2. **Entity CRUD Operations**
   - [ ] List view loads
   - [ ] Create new entity
   - [ ] Edit entity
   - [ ] Delete entity
   - [ ] Search/filter works

3. **API Endpoints**
   - [ ] GET /api (API documentation)
   - [ ] GET /api/contacts (collection)
   - [ ] GET /api/contacts/{id} (item)
   - [ ] POST /api/contacts (create)
   - [ ] PUT /api/contacts/{id} (update)

4. **Multi-tenant Isolation**
   - [ ] Access organization subdomain
   - [ ] Verify data isolation
   - [ ] Switch organizations (admin)

### Performance Verification

```bash
# Response times
ab -n 100 -c 10 https://yourdomain.com/

# Database query performance
php bin/console doctrine:query:sql "
  SELECT COUNT(*) FROM organization;
  SELECT COUNT(*) FROM \"user\";
"

# Cache hit rates
docker-compose exec redis redis-cli info stats | grep hits
```

### Log Monitoring

```bash
# Check recent logs
tail -f var/log/prod.log

# Check for errors
grep -i error var/log/prod.log | tail -20

# Check for critical issues
grep -i critical var/log/prod.log
```

---

## Rollback Plan

### Quick Rollback

If deployment fails, rollback immediately:

**Step 1: Revert Code**

```bash
# Git rollback
git log --oneline -10
git reset --hard PREVIOUS_COMMIT_HASH
git status

# Clear cache
php bin/console cache:clear --env=prod
```

**Step 2: Revert Database**

```bash
# Rollback migrations
php bin/console doctrine:migrations:migrate prev --no-interaction

# Or restore from backup
psql -U luminai_user luminai_db < backup_TIMESTAMP.sql
```

**Step 3: Restore Generated Files**

```bash
# List backups
ls -la var/generatorBackup/

# Restore from latest backup
cd var/generatorBackup/LATEST_TIMESTAMP/
cp *.bak ../../src/Entity/
# ... restore other files
```

**Step 4: Restart Services**

```bash
docker-compose restart app
# Or
sudo systemctl restart php8.4-fpm nginx
```

**Step 5: Verify Rollback**

```bash
curl https://yourdomain.com/health/detailed
php bin/phpunit tests/Smoke/
```

### Full Disaster Recovery

If system is completely broken:

**1. Stop Services**
```bash
docker-compose down
```

**2. Restore Full Backup**
```bash
# Code
tar -xzf backup_code_TIMESTAMP.tar.gz -C /var/www/luminai/

# Database
psql -U luminai_user luminai_db < backup_TIMESTAMP.sql
```

**3. Verify Restoration**
```bash
cd /var/www/luminai
git status
php bin/console doctrine:schema:validate
```

**4. Restart Services**
```bash
docker-compose up -d
docker-compose ps
```

**5. Full Verification**
```bash
curl https://yourdomain.com/health/detailed
php scripts/performance-test.php
```

---

## Monitoring

### Health Monitoring

**Endpoint**: `https://yourdomain.com/health/detailed`

**Response Structure**:
```json
{
  "status": "OK",
  "timestamp": "2025-01-07T12:00:00+00:00",
  "checks": {
    "database": {
      "status": "OK",
      "response_time": "2ms"
    },
    "redis": {
      "status": "OK",
      "memory_used": "45MB",
      "uptime_days": 15
    },
    "disk_space": {
      "status": "OK",
      "free_gb": 125.4,
      "used_percent": 35.2
    }
  }
}
```

**Monitoring Setup**:

1. **External monitoring** (e.g., UptimeRobot, Pingdom):
   - Monitor `/health` endpoint every 5 minutes
   - Alert on non-200 response
   - Alert on response time > 5s

2. **Internal monitoring** (e.g., Prometheus + Grafana):
```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'luminai'
    scrape_interval: 30s
    metrics_path: '/health/metrics'
    static_configs:
      - targets: ['yourdomain.com:443']
```

### Log Monitoring

**Application Logs**:
```bash
# Monitor in real-time
tail -f var/log/prod.log | jq .

# Check error rate
grep -c "level\":\"error" var/log/prod.log

# Top errors
grep "level\":\"error" var/log/prod.log | \
    jq -r .message | sort | uniq -c | sort -rn | head -10
```

**Web Server Logs**:
```bash
# Nginx access log
tail -f /var/log/nginx/access.log

# Check 5xx errors
awk '$9 >= 500' /var/log/nginx/access.log | tail -20

# Response time analysis
awk '{print $NF}' /var/log/nginx/access.log | \
    awk '{sum+=$1; n++} END {print "Avg: " sum/n "ms"}'
```

**Database Logs**:
```bash
# PostgreSQL logs
tail -f /var/log/postgresql/postgresql-*.log

# Slow query log
grep "duration:" /var/log/postgresql/postgresql-*.log | \
    grep -v "duration: 0\."
```

### Performance Metrics

**Track Key Metrics**:

1. **Response Times**
   - Target: < 200ms for pages, < 150ms for API
   - Alert threshold: > 500ms

2. **Database Performance**
   - Query time: < 50ms average
   - Connection pool: < 80% utilization

3. **Cache Hit Rate**
   - Redis: > 90%
   - OPcache: > 95%

4. **Error Rate**
   - Target: < 0.1% of requests
   - Alert threshold: > 1%

5. **Disk Space**
   - Warning: < 20% free
   - Critical: < 10% free

### Alerting Rules

**Critical Alerts** (immediate action):
- Service down (health check fails)
- Database connection errors
- Disk space < 5%
- Error rate > 5%

**Warning Alerts** (review within 1 hour):
- Response time > 500ms
- Error rate > 1%
- Disk space < 20%
- Cache hit rate < 80%

**Info Alerts** (review daily):
- Slow queries (> 1s)
- High memory usage (> 80%)
- Deprecation warnings

---

## CI/CD Integration

### GitHub Actions Workflow

Create `.github/workflows/generator-deploy.yml`:

```yaml
name: Generator Production Deploy

on:
  push:
    branches: [main]
    paths:
      - 'config/Entity*.csv'
      - 'src/Service/Generator/**'
      - 'templates/Generator/**'

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo_pgsql, intl, opcache

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Validate CSV
        run: php scripts/verify-csv-migration.php

      - name: Run pre-generation checks
        run: php scripts/pre-generation-check.php

      - name: Generate code (dry-run)
        run: php bin/console genmax:generate --dry-run

      - name: Run tests
        run: php bin/phpunit

      - name: PHPStan
        run: vendor/bin/phpstan analyse src --level=8

      - name: Code style
        run: vendor/bin/php-cs-fixer fix --dry-run

      - name: Security audit
        run: composer audit

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
      - name: Deploy to production
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.PROD_HOST }}
          username: ${{ secrets.PROD_USER }}
          key: ${{ secrets.PROD_SSH_KEY }}
          script: |
            cd /var/www/luminai
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php bin/console doctrine:migrations:migrate --no-interaction
            php bin/console cache:clear --env=prod
            php bin/console cache:warmup --env=prod
            docker-compose restart app

      - name: Verify deployment
        run: |
          curl -f https://yourdomain.com/health/detailed || exit 1

      - name: Notify on failure
        if: failure()
        uses: 8398a7/action-slack@v3
        with:
          status: ${{ job.status }}
          text: 'Production deployment failed!'
          webhook_url: ${{ secrets.SLACK_WEBHOOK }}
```

### Manual Deployment Script

Create `scripts/deploy-production.sh`:

```bash
#!/bin/bash
set -e

echo "🚀 Starting production deployment..."

# Configuration
PRODUCTION_HOST="yourdomain.com"
PRODUCTION_USER="deploy"
PRODUCTION_PATH="/var/www/luminai"

# Pre-deployment checks
echo "1️⃣ Running pre-deployment checks..."
php bin/phpunit || exit 1
php scripts/pre-generation-check.php || exit 1
vendor/bin/phpstan analyse src --level=8 || exit 1

# Deploy code
echo "2️⃣ Deploying code to production..."
ssh ${PRODUCTION_USER}@${PRODUCTION_HOST} << 'ENDSSH'
    cd /var/www/luminai
    git pull origin main
    composer install --no-dev --optimize-autoloader
    php bin/console doctrine:migrations:migrate --no-interaction
    php bin/console cache:clear --env=prod
    php bin/console cache:warmup --env=prod
    docker-compose restart app
ENDSSH

# Verify deployment
echo "3️⃣ Verifying deployment..."
sleep 5
curl -f https://${PRODUCTION_HOST}/health/detailed || {
    echo "❌ Deployment verification failed!"
    exit 1
}

echo "✅ Deployment completed successfully!"
```

Make executable:
```bash
chmod +x scripts/deploy-production.sh
```

---

## Security Considerations

### SSL/TLS

- [ ] Valid SSL certificate installed
- [ ] HTTPS redirect configured
- [ ] HSTS header enabled
- [ ] Certificate auto-renewal configured

### Access Control

- [ ] Production database: restricted access
- [ ] Redis: password protected
- [ ] Admin routes: IP whitelisted
- [ ] SSH: key-based auth only

### Secrets Management

- [ ] No secrets in git repository
- [ ] Environment variables properly set
- [ ] Database passwords rotated
- [ ] API keys properly secured

### Rate Limiting

- [ ] API rate limits configured
- [ ] Login attempt limits active
- [ ] DDoS protection enabled

---

## Troubleshooting

### Deployment Failed

**Check git status**:
```bash
git status
git log -1
```

**Check permissions**:
```bash
ls -la var/
ls -la public/
```

**Check logs**:
```bash
tail -50 var/log/prod.log
```

### Migrations Failed

**Check migration status**:
```bash
php bin/console doctrine:migrations:status
```

**Rollback migration**:
```bash
php bin/console doctrine:migrations:migrate prev
```

**Manual fix**:
```bash
php bin/console doctrine:query:sql "SELECT version FROM doctrine_migration_versions"
```

### Performance Issues

**Check OPcache**:
```bash
php -i | grep opcache
```

**Check Redis**:
```bash
docker-compose exec redis redis-cli info memory
```

**Analyze slow queries**:
```bash
grep "duration:" var/log/prod.log | sort -rn | head -10
```

---

## Next Steps

- Review [User Guide](../app/docs/Generator/GeneratorUserGuide.md) for daily operations
- Check [Developer Guide](../app/docs/Generator/GeneratorDeveloperGuide.md) for development
- Explore [Cheat Sheets](../app/docs/Generator/CheatSheets.md) for quick commands
