# .env Files Analysis and Configuration Fix

## Summary

The .env files have been added to .gitignore to prevent sensitive production credentials from being committed to the repository. This document explains the differences between environments and provides the correct configuration.

---

## Key Differences Found

### Root `.env` File

| Setting | Local (Dev) | VPS (Production) |
|---------|-------------|------------------|
| `APP_ENV` | `dev` | `prod` ✅ |
| `DEFAULT_URI` | `http://localhost` | `https://luminai.ia.br` ✅ |
| `SESSION_COOKIE_DOMAIN` | ` ` (empty) | `luminai.ia.br` ✅ |
| `CORS_ALLOW_ORIGIN` | localhost only | includes production domains ✅ |
| `REDIS_PASSWORD` | Set with auth | Set with auth ✅ |

### App `app/.env` File

| Setting | Local (Dev) | VPS (Production) | Correct Value |
|---------|-------------|------------------|---------------|
| `APP_ENV` | `dev` | `dev` | ❌ Should be `prod` |
| `DEFAULT_URI` | `http://localhost` | `http://localhost` | ❌ Should be `https://avelum.com.br` |
| `APP_BASE_DOMAIN` | `localhost` | `avelum.com.br` | ✅ |
| `SESSION_COOKIE_DOMAIN` | ` ` (empty) | `.avelum.com.br` | ✅ |
| `CORS_ALLOW_ORIGIN` | localhost only | includes prod domains | ✅ |
| `REDIS_URL` | no password | no password | ⚠️ Should have password |

---

## Critical Issues to Fix on VPS

### 1. **app/.env** needs corrections:

```bash
# WRONG (current VPS value):
APP_ENV=dev

# CORRECT:
APP_ENV=prod
```

```bash
# WRONG (current VPS value):
DEFAULT_URI=http://localhost

# CORRECT:
DEFAULT_URI=https://avelum.com.br
```

```bash
# MISSING (current VPS):
REDIS_URL=redis://redis:6379/0

# CORRECT (add password authentication):
REDIS_PASSWORD=RedisSecure2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9xY2vZ8
REDIS_URL=redis://:${REDIS_PASSWORD}@redis:6379/0
```

---

## Actions Taken

1. ✅ Added `.env` to both `.gitignore` files (root and app)
2. ✅ Removed `.env` files from git tracking (`git rm --cached`)
3. ✅ Created `.env.example` template files for documentation
4. ⏳ VPS `.env` files need manual correction (see below)

---

## Next Steps: Fix VPS Configuration

### Option 1: SSH into VPS and edit manually

```bash
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175

# Edit app/.env
nano /opt/luminai/app/.env

# Change these lines:
# APP_ENV=dev          → APP_ENV=prod
# DEFAULT_URI=http://localhost → DEFAULT_URI=https://avelum.com.br

# Add after CORS_ALLOW_ORIGIN line:
# Redis Configuration
REDIS_PASSWORD=RedisSecure2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9xY2vZ8
REDIS_URL=redis://:${REDIS_PASSWORD}@redis:6379/0

# Save and restart services
cd /opt/luminai
docker-compose restart app redis
```

### Option 2: Create production .env files locally and upload

Create proper production .env files locally (without committing) and use scp to upload:

```bash
# After creating correct production .env files:
scp -i /home/user/.ssh/luminai_vps /tmp/prod.env root@91.98.137.175:/opt/luminai/.env
scp -i /home/user/.ssh/luminai_vps /tmp/app-prod.env root@91.98.137.175:/opt/luminai/app/.env
```

---

## Correct Production Configuration

### `/opt/luminai/.env` (Root - **Currently Correct**)

```bash
# Infrastructure Configuration (Docker Compose)
POSTGRES_DB=luminai_db
POSTGRES_USER=luminai_user
POSTGRES_PASSWORD=LuminaiProd2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9

# Application Configuration (Symfony)
DATABASE_URL="postgresql://luminai_user:LuminaiProd2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9@database:5432/luminai_db?serverVersion=18&charset=utf8"
APP_ENV=prod
APP_SECRET=b8f2c9e4a1d6f3e7c2b9a4d8e1f5a2c6b9e3f7a0d4c8b2e6f1a5d9c3f8e2a7b4
APP_TIMEZONE=America/Sao_Paulo
FRANKENPHP_NUM_THREADS=4

# Routing
DEFAULT_URI=https://luminai.ia.br

# Messenger
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0

# Mailer
MAILER_DSN=null://null

# CORS
CORS_ALLOW_ORIGIN="^https?://(luminai\.ia\.br|.*\.luminai\.ia\.br)(:[0-9]+)?$"

# Redis Configuration - SECURITY: Password authentication required
REDIS_PASSWORD=RedisSecure2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9xY2vZ8
REDIS_URL=redis://:${REDIS_PASSWORD}@redis:6379/0

# Security Configuration
SECURITY_RATE_LIMIT_ENABLED=true
SECURITY_MONITORING_ENABLED=true

# Performance Configuration
CACHE_ENABLED=true
OPCACHE_ENABLED=true

# Session Configuration
SESSION_COOKIE_DOMAIN=luminai.ia.br
```

### `/opt/luminai/app/.env` (App - **NEEDS FIXES**)

```bash
# Infrastructure Configuration (Docker Compose)
POSTGRES_DB=luminai_db
POSTGRES_USER=luminai_user
POSTGRES_PASSWORD=LuminaiProd2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9

# Application Configuration (Symfony)
DATABASE_URL="postgresql://luminai_user:LuminaiProd2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9@database:5432/luminai_db?serverVersion=18&charset=utf8"
APP_ENV=prod                    # ← CHANGED FROM dev
APP_SECRET=b8f2c9e4a1d6f3e7c2b9a4d8e1f5a2c6b9e3f7a0d4c8b2e6f1a5d9c3f8e2a7b4
FRANKENPHP_NUM_THREADS=4

# Routing
DEFAULT_URI=https://avelum.com.br    # ← CHANGED FROM http://localhost
APP_BASE_DOMAIN=avelum.com.br

# Session Configuration (empty for localhost, set domain for production)
SESSION_COOKIE_DOMAIN=.avelum.com.br

# Messenger
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0

# Mailer
MAILER_DSN=null://null

# CORS
CORS_ALLOW_ORIGIN="^https?://(localhost|127\.0\.0\.1|[a-z0-9\-]+\.avelum\.com\.br|[a-z0-9\-]+\.luminai\.ia\.br)(:[0-9]+)?$"

# Redis Configuration - ADD PASSWORD
REDIS_PASSWORD=RedisSecure2025_xK9mN7qL2vR8jT4pW6fY3hZ5nM1gB4cD2eF9xY2vZ8
REDIS_URL=redis://:${REDIS_PASSWORD}@redis:6379/0

# Security Configuration
SECURITY_RATE_LIMIT_ENABLED=true
SECURITY_MONITORING_ENABLED=true

# Performance Configuration
CACHE_ENABLED=true
OPCACHE_ENABLED=true
```

---

## Security Notes

⚠️ **IMPORTANT**: Never commit `.env` files with production credentials to git!

- ✅ `.env` files are now in `.gitignore`
- ✅ `.env.example` files provide templates without sensitive data
- ✅ Production passwords should be unique and strong
- ✅ Each environment should have its own `.env` file

---

## After VPS Configuration is Fixed

Once the VPS `.env` files are corrected, deploy the latest code:

```bash
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175 'cd /opt/luminai && git pull origin main && docker-compose build app && docker-compose up -d && docker-compose exec -T app php bin/console cache:clear --env=prod && docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod'
```
