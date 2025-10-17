# VPS Dual-Domain Setup Guide (avelum.com.br + luminai.ia.br)

## Overview
This guide explains how to configure the VPS to support both `avelum.com.br` and `luminai.ia.br` domains simultaneously.

## Code Changes Completed ✅
The following code changes have been made to support both domains:

1. **OrganizationContext.php** - Updated subdomain extraction to recognize both domains
2. **nginx/conf/default.conf** - Added server blocks for avelum.com.br

## VPS Configuration Required

### 1. SSL Certificates (Let's Encrypt)

Generate SSL certificates for `avelum.com.br` on the VPS:

```bash
# SSH into VPS
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175

# Stop nginx temporarily
docker-compose stop nginx

# Generate certificate for avelum.com.br (replace with your email)
certbot certonly --standalone -d avelum.com.br -d *.avelum.com.br --email your-email@example.com --agree-tos

# Copy certificates to the correct location
cp /etc/letsencrypt/live/avelum.com.br/fullchain.pem /opt/luminai/nginx/ssl/avelum.com.br.crt
cp /etc/letsencrypt/live/avelum.com.br/privkey.pem /opt/luminai/nginx/ssl/avelum.com.br.key

# Set proper permissions
chmod 644 /opt/luminai/nginx/ssl/avelum.com.br.crt
chmod 600 /opt/luminai/nginx/ssl/avelum.com.br.key

# Restart nginx
docker-compose start nginx
```

### 2. Environment Variables (.env on VPS)

Update `/opt/luminai/app/.env` on the VPS with the following settings:

```bash
# Session Configuration - CRITICAL for login to work
# Use .avelum.com.br to share sessions across all subdomains
SESSION_COOKIE_DOMAIN=.avelum.com.br

# Or leave empty to use the exact domain (less flexible)
# SESSION_COOKIE_DOMAIN=

# Base domain for routing
APP_BASE_DOMAIN=avelum.com.br

# CORS - Update to allow both domains
CORS_ALLOW_ORIGIN="^https?://(localhost|127\.0\.0\.1|[a-z0-9\-]+\.avelum\.com\.br|[a-z0-9\-]+\.luminai\.ia\.br)(:[0-9]+)?$"
```

### 3. DNS Configuration

Ensure DNS records are properly configured:

#### For avelum.com.br:
- **A Record**: `avelum.com.br` → `91.98.137.175`
- **A Record**: `*.avelum.com.br` → `91.98.137.175` (wildcard for subdomains)

#### For luminai.ia.br (legacy):
- **A Record**: `luminai.ia.br` → `91.98.137.175`
- **A Record**: `*.luminai.ia.br` → `91.98.137.175`

### 4. Deployment Steps

After pushing code changes to Git:

```bash
# SSH into VPS
ssh -i /home/user/.ssh/luminai_vps root@91.98.137.175

# Navigate to project
cd /opt/luminai

# Pull latest code
git pull origin main

# Update environment variables (edit .env file)
nano app/.env
# Add the SESSION_COOKIE_DOMAIN and other settings from step 2

# Rebuild and restart
docker-compose build app
docker-compose up -d app nginx

# Run migrations if needed
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Clear cache
docker-compose exec -T app php bin/console cache:clear --env=prod
docker-compose exec -T app php bin/console cache:warmup --env=prod

# Restart to ensure all changes take effect
docker-compose restart app nginx
```

## How Multi-Domain Works

### Subdomain Extraction
The system extracts organization slugs from subdomains:

- `acme.avelum.com.br` → organization slug: `acme`
- `acme.luminai.ia.br` → organization slug: `acme`
- `avelum.com.br` → no organization (root domain)
- `luminai.ia.br` → no organization (root domain)

### Session Cookies
When `SESSION_COOKIE_DOMAIN=.avelum.com.br`:
- Cookies are shared across all `*.avelum.com.br` subdomains
- Login at `org1.avelum.com.br` works for that specific organization
- Each organization gets isolated data via Doctrine filtering

## Troubleshooting

### Login Redirect Loop
**Cause**: Session cookie domain mismatch

**Solution**: Ensure `SESSION_COOKIE_DOMAIN` is set correctly:
- For avelum.com.br: `SESSION_COOKIE_DOMAIN=.avelum.com.br`
- For luminai.ia.br: `SESSION_COOKIE_DOMAIN=.luminai.ia.br`
- For both (not recommended): Leave empty `SESSION_COOKIE_DOMAIN=`

### SSL Certificate Errors
**Cause**: Missing or expired certificates

**Solution**: Generate certificates as shown in step 1

### Organization Not Recognized
**Cause**: DNS not configured or OrganizationContext not updated

**Solution**:
1. Verify DNS is pointing to VPS
2. Ensure OrganizationContext.php includes both domains (already done)
3. Clear cache: `docker-compose exec app php bin/console cache:clear --env=prod`

## Testing

### Test Both Domains
```bash
# Test avelum.com.br
curl -k https://avelum.com.br/health/detailed

# Test with organization subdomain
curl -k https://org1.avelum.com.br/health/detailed

# Test legacy domain
curl -k https://luminai.ia.br/health/detailed
```

### Verify Session Cookies
1. Open browser DevTools (F12)
2. Go to Application/Storage → Cookies
3. Login to an organization subdomain
4. Check cookie domain matches `SESSION_COOKIE_DOMAIN` setting

## Quick Reference

### Files Modified
- ✅ `/home/user/inf/app/src/Service/OrganizationContext.php` - Lines 93, 98
- ✅ `/home/user/inf/nginx/conf/default.conf` - Added avelum.com.br server block

### VPS Files to Update
- ⚠️ `/opt/luminai/app/.env` - Add SESSION_COOKIE_DOMAIN
- ⚠️ `/opt/luminai/nginx/ssl/avelum.com.br.crt` - SSL certificate
- ⚠️ `/opt/luminai/nginx/ssl/avelum.com.br.key` - SSL private key

### Critical Environment Variables
```bash
SESSION_COOKIE_DOMAIN=.avelum.com.br
APP_BASE_DOMAIN=avelum.com.br
CORS_ALLOW_ORIGIN="^https?://(localhost|127\.0\.0\.1|[a-z0-9\-]+\.avelum\.com\.br|[a-z0-9\-]+\.luminai\.ia\.br)(:[0-9]+)?$"
```
