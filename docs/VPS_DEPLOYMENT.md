# VPS Deployment Guide

Complete guide to deploying Luminai on a production VPS server.

---

## VPS Server Details

- **IP Address**: `91.98.137.175`
- **SSH User**: `root`
- **SSH Key**: `/home/user/.ssh/luminai_vps`
- **Application Path**: `/opt/luminai`
- **Access Method**: SSH key-based authentication only

---

## File Modification Rules

⚠️ **CRITICAL: NEVER modify ANY files directly on the VPS server**

### Allowed Operations on VPS

- ✅ Git pull to update code
- ✅ Docker operations (build, restart containers)
- ✅ Run migrations inside Docker containers
- ✅ Cache operations inside Docker containers
- ✅ View logs and monitoring
- ✅ **ONLY EXCEPTION**: Create migration files (migrations folder in `.gitignore`)

### Forbidden Operations on VPS

- ❌ Edit ANY files directly on VPS
- ❌ Create new files (except migrations)
- ❌ Delete files manually
- ❌ Modify Dockerfile or docker-compose.yml on VPS
- ❌ Edit configuration files on VPS
- ❌ Modify entity files on VPS

### Why This Rule Exists

- VPS is **production environment**
- ALL changes must come through Git for traceability
- Ensures local and production code stay in sync
- Prevents configuration drift
- Maintains deployment consistency

---

## Development & Deployment Workflow

### Local Development (Your Machine)

**Step 1: Make Changes Locally**

```bash
# Work on your local machine at /home/user/inf
cd /home/user/inf

# Make entity changes
# Edit src/Entity/User.php (add new field)

# Make configuration changes if needed
# Edit config files, Dockerfile, docker-compose.yml, etc.

# Test locally
docker-compose build app
docker-compose up -d app
php bin/console doctrine:schema:validate
```

**Step 2: Commit Changes to Git**

```bash
# Stage entity changes (NOT migrations)
git add src/Entity/
git add config/
git add docker-compose.yml  # if changed
git add app/docker/frankenphp/Dockerfile  # if changed

# Commit with descriptive message
git commit -m "Add email verification field to User entity"

# Push to repository
git push origin main
```

**Step 3: Deploy to VPS**

Use the simplified deployment command:

```bash
# Option 1: Just type "vps" or "vps deploy" or "deploy to vps"
# Claude will execute the full deployment automatically

# Option 2: Manual deployment via SSH
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  git pull origin main && \
  docker-compose build app && \
  docker-compose up -d app && \
  docker-compose exec -T app php bin/console make:migration --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console asset-map:compile --env=prod && \
  docker-compose exec -T app php bin/console cache:clear --env=prod && \
  docker-compose exec -T app php bin/console cache:warmup --env=prod'
```

---

## Deployment Process

### Automated Deployment Flow

When you run the deployment command (or ask Claude to "deploy to vps"):

**1. VPS Connection**
```bash
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175
cd /opt/luminai
```

**2. Pull Latest Code**
```bash
git pull origin main
```

**3. Rebuild Docker Containers (if Dockerfile changed)**
```bash
docker-compose build app
docker-compose up -d app
```

**4. Create Migration (on VPS)**
```bash
# This is the ONLY exception to the "no file creation" rule
docker-compose exec -T app php bin/console make:migration --no-interaction --env=prod
```

Why migrations are created on VPS:
- Migrations folder is in `.gitignore`
- Migration files are environment-specific
- Generated based on actual production database state

**5. Execute Migration**
```bash
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod
```

**6. Compile Assets**
```bash
docker-compose exec -T app php bin/console asset-map:compile --env=prod
```

This compiles all JavaScript, CSS, and other assets (including Tom Select and Stimulus controllers) for production use.

**7. Clear and Warm Cache**
```bash
docker-compose exec -T app php bin/console cache:clear --env=prod
docker-compose exec -T app php bin/console cache:warmup --env=prod
```

**8. Verify Deployment**
```bash
curl -k https://91.98.137.175/health/detailed | jq .
```

---

## Docker Configuration Changes

When you need to update Docker configuration (Dockerfile, docker-compose.yml):

### Step 1: Modify Locally

```bash
cd /home/user/inf

# Edit Docker configuration
nano docker-compose.yml
# or
nano app/docker/frankenphp/Dockerfile
```

### Step 2: Test Locally

```bash
# Rebuild containers
docker-compose build app

# Test changes
docker-compose up -d app

# Verify application works
curl -k https://localhost/health/detailed
```

### Step 3: Commit Changes

```bash
git add docker-compose.yml
git add app/docker/frankenphp/Dockerfile
git commit -m "Update Docker configuration: increase PHP memory limit"
git push origin main
```

### Step 4: Deploy to VPS

```bash
# Deployment includes automatic rebuild
# The build step detects changes and rebuilds containers
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  git pull origin main && \
  docker-compose build app && \
  docker-compose up -d app'
```

---

## Common Deployment Scenarios

### Scenario 1: Add New Entity Field

**Local**:
```bash
# Edit entity
nano app/src/Entity/User.php
# Add: private ?string $phoneNumber = null;

# Test
docker-compose exec app php bin/console doctrine:schema:validate

# Commit
git add src/Entity/User.php
git commit -m "Add phoneNumber field to User entity"
git push origin main
```

**VPS**:
```bash
# Deploy (creates migration on VPS)
vps deploy
```

### Scenario 2: Update Configuration

**Local**:
```bash
# Edit configuration
nano app/config/packages/doctrine.yaml

# Test
docker-compose restart app

# Commit
git add config/packages/doctrine.yaml
git commit -m "Update Doctrine configuration"
git push origin main
```

**VPS**:
```bash
# Deploy
vps deploy
```

### Scenario 3: Update Dependencies

**Local**:
```bash
# Update composer.json
composer require new/package

# Test
composer install
php bin/phpunit

# Commit
git add composer.json composer.lock
git commit -m "Add new package"
git push origin main
```

**VPS**:
```bash
# Deploy (includes composer install inside container rebuild)
vps deploy
```

---

## Deployment Checklist

Before deploying to VPS:

- [ ] ✅ All changes tested locally
- [ ] ✅ All tests passing (`php bin/phpunit`)
- [ ] ✅ Entity changes committed (NOT migrations)
- [ ] ✅ Configuration changes committed
- [ ] ✅ Docker changes committed (if any)
- [ ] ✅ Changes pushed to Git (`git push origin main`)
- [ ] ✅ No local migrations created (VPS will create them)

After deploying to VPS:

- [ ] ✅ Verify deployment via health endpoint
- [ ] ✅ Check logs for errors
- [ ] ✅ Test critical functionality
- [ ] ✅ Monitor application for 10-15 minutes

---

## Troubleshooting Deployment

### Git Pull Failed

```bash
# Check Git status on VPS
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && git status'

# Check for uncommitted changes
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && git diff'

# Force reset to main (⚠️ destructive, loses local changes)
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && git reset --hard origin/main'
```

### Docker Build Failed

```bash
# Check Docker logs
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose logs app'

# Rebuild without cache
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose build --no-cache app'

# Check Docker disk space
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'docker system df'
```

### Migration Failed

```bash
# Check migration status
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  docker-compose exec -T app php bin/console doctrine:migrations:status'

# View last migration
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  docker-compose exec -T app php bin/console doctrine:migrations:list'

# Rollback migration
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  docker-compose exec -T app php bin/console doctrine:migrations:migrate prev --no-interaction'
```

### Cache Issues

```bash
# Clear cache on VPS
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  docker-compose exec -T app php bin/console cache:clear --env=prod'

# Rebuild cache
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  docker-compose exec -T app php bin/console cache:warmup --env=prod'
```

### Application Not Responding

```bash
# Check containers status
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose ps'

# View application logs
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose logs -f app'

# Restart containers
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose restart app'
```

---

## Monitoring VPS

### Check Application Health

```bash
# From local machine
curl -k https://91.98.137.175/health/detailed | jq .

# From VPS
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 \
  'curl -k https://localhost/health/detailed | jq .'
```

### Check Docker Status

```bash
# Container status
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose ps'

# Resource usage
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'docker stats --no-stream'

# Disk usage
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'docker system df'
```

### Check Logs

```bash
# Application logs
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose logs --tail=100 app'

# Database logs
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose logs --tail=100 database'

# Redis logs
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose logs --tail=100 redis'
```

### Check Redis Extension

```bash
# Verify Redis PHP extension is loaded
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  docker-compose exec -T app php -m | grep redis'
```

---

## Rollback Procedure

If deployment fails and you need to rollback:

### Step 1: Revert Git Commit

```bash
# On VPS: Go back to previous commit
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  git reset --hard HEAD^ && \
  docker-compose build app && \
  docker-compose up -d app'
```

### Step 2: Rollback Migration (if needed)

```bash
# Rollback last migration
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  docker-compose exec -T app php bin/console doctrine:migrations:migrate prev --no-interaction'
```

### Step 3: Verify Rollback

```bash
# Check health
curl -k https://91.98.137.175/health/detailed | jq .

# Check Git status
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && git log -1'
```

---

## Production Environment

### Environment Variables

Ensure these are set on VPS:

```bash
# .env.local on VPS
APP_ENV=prod
APP_SECRET=ProductionSecretHere
DATABASE_URL="postgresql://user:password@database:5432/luminai_db"
REDIS_URL=redis://redis:6379/0
FRANKENPHP_NUM_THREADS=8
OPCACHE_ENABLED=true
```

### Performance Settings

**Production Optimizations**:

1. **OPcache**: Enabled with optimized settings
2. **Redis**: Memory limit 256MB
3. **FrankenPHP**: 8 worker threads
4. **Doctrine**: Result cache enabled
5. **Symfony**: Production mode with compiled container

---

## Security Considerations

### SSH Access

- ✅ Key-based authentication only
- ❌ No password authentication
- ✅ Root access (for Docker operations)
- ✅ Key location: `/home/user/.ssh/luminai_vps`

### File Permissions

VPS file permissions are managed by Docker:
- Container runs as appropriate user
- Files owned by container user
- No manual permission changes needed

### Secrets Management

- ✅ Secrets in `.env.local` (not in Git)
- ✅ Database passwords in environment variables
- ✅ API keys in environment variables
- ❌ No secrets in codebase

---

## Quick Reference

### Quick Deployment

```bash
# Deploy everything
vps deploy
# or
deploy to vps
# or
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && \
  git pull origin main && \
  docker-compose build app && \
  docker-compose up -d app && \
  docker-compose exec -T app php bin/console make:migration --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod && \
  docker-compose exec -T app php bin/console cache:clear --env=prod && \
  docker-compose exec -T app php bin/console cache:warmup --env=prod'
```

### Quick Health Check

```bash
curl -k https://91.98.137.175/health/detailed | jq .
```

### Quick Logs

```bash
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose logs --tail=50 app'
```

### Quick Restart

```bash
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && docker-compose restart app'
```

---

For more information:
- [Production Deployment Guide](ProductionDeployment.md)
- [Monitoring Guide](MONITORING.md)
- [Troubleshooting Guide](TROUBLESHOOTING.md)
- [Docker Guide](DOCKER.md)
