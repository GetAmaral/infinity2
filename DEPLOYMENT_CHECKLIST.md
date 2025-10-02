# ✅ INFINITY PRODUCTION DEPLOYMENT CHECKLIST

## Quick reference checklist for deployment - check off each item as you complete it

---

## 🎯 PRE-DEPLOYMENT (Before Creating VPS)

- [ ] **SSH Key Generated**
  - `ssh-keygen -t ed25519 -C "infinity-vps-access" -f ~/.ssh/infinity_vps -N ""`
  - Public key added to Hetzner Cloud

- [ ] **Domain Purchased**
  - Domain name: `_________________`
  - DNS management access: ✓

- [ ] **Passwords Generated**
  ```bash
  # Database password (save this securely!)
  openssl rand -base64 24

  # APP_SECRET (save this securely!)
  openssl rand -hex 32
  ```
  - Database password: `_________________` (SAVE SECURELY!)
  - APP_SECRET: `_________________` (SAVE SECURELY!)

- [ ] **Repository Access**
  - Git repository URL: `_________________`
  - Access credentials configured: ✓

---

## 🖥️ PHASE 1: VPS CREATION (5 min)

- [ ] **Create Hetzner Server**
  - Location: `_________________`
  - Type: CPX31 (8GB RAM) or higher
  - Image: Ubuntu 24.04 LTS
  - SSH key: `infinity-vps-access` selected

- [ ] **Cloud-Config Applied**
  - Paste contents of `hetzner-cloud-config.yml` into "User data"
  - Server name: `infinity-production`
  - Server created successfully

- [ ] **VPS Information Recorded**
  - VPS IPv4: `_________________`
  - VPS IPv6: `_________________`
  - Creation date: `_________________`

- [ ] **Verify Cloud-Init Complete**
  ```bash
  ssh -i ~/.ssh/infinity_vps infinity@YOUR_VPS_IP
  cat /home/infinity/DEPLOYMENT_INFO.txt
  docker --version
  docker compose version
  ```

---

## 🌐 PHASE 2: DNS CONFIGURATION (15-60 min)

- [ ] **DNS Records Created**
  ```
  Type    Name    Value              TTL
  A       @       YOUR_VPS_IP        3600
  A       *       YOUR_VPS_IP        3600
  AAAA    @       YOUR_VPS_IPv6      3600
  AAAA    *       YOUR_VPS_IPv6      3600
  ```

- [ ] **DNS Propagation Verified**
  ```bash
  dig +short your-domain.com
  dig +short test.your-domain.com
  # Both should return VPS IP
  ```

- [ ] **Wait for Full Propagation** (15-60 minutes)

---

## 🚀 PHASE 3: APPLICATION DEPLOYMENT (30 min)

- [ ] **Connect to VPS**
  ```bash
  ssh -i ~/.ssh/infinity_vps infinity@YOUR_VPS_IP
  ```

- [ ] **Clone Repository**
  ```bash
  cd /home/infinity
  git clone YOUR_REPO_URL infinity
  cd infinity
  ```

- [ ] **Create Production Environment**
  ```bash
  cp .env.production.template .env.prod
  nano .env.prod
  ```

- [ ] **Update .env.prod Values**
  - [x] `POSTGRES_PASSWORD` - Strong password set
  - [x] `DATABASE_URL` - Password updated
  - [x] `APP_SECRET` - Unique 64-char hex set
  - [x] `APP_ENV=prod`
  - [x] `APP_DEBUG=0`
  - [x] `FRANKENPHP_NUM_THREADS=8`
  - [x] `DEFAULT_URI` - Domain set
  - [x] `MAILER_DSN` - SMTP configured
  - [x] `CORS_ALLOW_ORIGIN` - Domain pattern set

- [ ] **Obtain SSL Certificate**
  ```bash
  sudo certbot certonly --manual \
    --preferred-challenges dns \
    -d your-domain.com \
    -d *.your-domain.com
  ```
  - DNS TXT record added: ✓
  - Certificate obtained: ✓
  - Certificate location: `/etc/letsencrypt/live/your-domain.com/`

- [ ] **Update Nginx Configuration**
  - File: `nginx/conf/default.conf`
  - [x] `server_name` updated to domain
  - [x] SSL certificate paths updated
  - [x] HSTS header added
  - [x] Saved changes

- [ ] **Update Docker Compose**
  - File: `docker-compose.yml`
  - [x] `restart: unless-stopped` added to all services
  - [x] Database port 5432 removed/commented
  - [x] Redis port 6379 removed/commented
  - [x] Nginx SSL volume updated to `/etc/letsencrypt:/etc/letsencrypt:ro`
  - [x] Saved changes

- [ ] **Deploy Services**
  ```bash
  # Start database and redis
  docker-compose --env-file .env.prod up -d database redis
  sleep 30

  # Build and start app
  docker-compose --env-file .env.prod build --no-cache app
  docker-compose --env-file .env.prod up -d app
  sleep 60

  # Run migrations
  docker-compose --env-file .env.prod exec app php bin/console doctrine:migrations:migrate --no-interaction

  # Optimize cache
  docker-compose --env-file .env.prod exec app php bin/console cache:clear --env=prod --no-debug
  docker-compose --env-file .env.prod exec app php bin/console cache:warmup --env=prod --no-debug

  # Start remaining services
  docker-compose --env-file .env.prod up -d nginx messenger_worker
  ```

- [ ] **Verify Deployment**
  ```bash
  docker-compose --env-file .env.prod ps
  curl https://your-domain.com/health
  ```

---

## 🔐 PHASE 4: SECURITY & MONITORING (15 min)

- [ ] **Configure Backups**
  ```bash
  # Test backup
  /home/infinity/backup-database.sh

  # Add to crontab
  crontab -e
  # Add: 0 2 * * * /home/infinity/backup-database.sh >> /home/infinity/backups/backup.log 2>&1
  ```

- [ ] **SSL Auto-Renewal Verified**
  ```bash
  sudo certbot renew --dry-run
  sudo cat /etc/cron.d/certbot
  ```

- [ ] **External Monitoring Configured**
  - Service: `_________________` (e.g., UptimeRobot)
  - Monitoring: https://your-domain.com/health
  - Alert email: `_________________`

- [ ] **Firewall Verified**
  ```bash
  sudo ufw status
  # Should show: 22/tcp, 80/tcp, 443/tcp ALLOW
  ```

- [ ] **Fail2Ban Active**
  ```bash
  sudo systemctl status fail2ban
  ```

---

## 👤 PHASE 5: CREATE ADMIN USER (10 min)

- [ ] **Create Organization**
  ```bash
  docker-compose --env-file .env.prod exec database psql -U infinity_user infinity_db

  INSERT INTO organization (id, name, slug, description, created_at, updated_at)
  VALUES (gen_random_uuid(), 'System Administration', 'admin', 'System admin organization', NOW(), NOW());

  SELECT id, name, slug FROM organization WHERE slug = 'admin';
  # Copy organization UUID: _________________
  ```

- [ ] **Create Admin User**
  ```sql
  INSERT INTO "user" (id, organization_id, name, email, roles, password, created_at, updated_at)
  VALUES (
    gen_random_uuid(),
    'ORG_UUID_FROM_ABOVE',
    'System Admin',
    'admin@your-domain.com',
    '["ROLE_SUPER_ADMIN"]',
    'temp',
    NOW(),
    NOW()
  );
  \q
  ```

- [ ] **Set Admin Password**
  ```bash
  docker-compose --env-file .env.prod exec app php bin/console security:hash-password
  # Enter password, copy hash

  docker-compose --env-file .env.prod exec database psql -U infinity_user infinity_db
  UPDATE "user" SET password = 'PASTE_HASH' WHERE email = 'admin@your-domain.com';
  \q
  ```

- [ ] **Test Admin Login**
  - URL: https://your-domain.com/login
  - Login successful: ✓

---

## ✅ PHASE 6: FINAL VERIFICATION (10 min)

- [ ] **Test All Endpoints**
  ```bash
  curl https://your-domain.com/health
  curl https://your-domain.com/health/detailed | jq .
  curl https://your-domain.com/health/metrics | jq .
  curl https://your-domain.com/api
  ```

- [ ] **Test Multi-Tenant**
  - Create test organization via admin
  - Test subdomain: https://test-org.your-domain.com
  - Verify data isolation: ✓

- [ ] **Run Security Audit**
  ```bash
  docker-compose --env-file .env.prod exec app composer audit
  docker run aquasec/trivy image infinity_app
  ```

- [ ] **Performance Test**
  ```bash
  ab -n 1000 -c 50 https://your-domain.com/health
  ```

- [ ] **Check Logs**
  ```bash
  docker-compose --env-file .env.prod logs --tail=50 app
  docker-compose --env-file .env.prod logs --tail=50 nginx
  ```

- [ ] **Resource Monitoring**
  ```bash
  docker stats --no-stream
  df -h
  free -h
  ```

---

## 📋 POST-DEPLOYMENT

- [ ] **Documentation Updated**
  - Deployment date recorded: `_________________`
  - VPS details documented: ✓
  - Admin credentials stored securely: ✓

- [ ] **Backup Strategy Verified**
  - Daily database backups: ✓
  - Backup retention: 30 days
  - Test restore: ✓

- [ ] **Monitoring Active**
  - External uptime monitoring: ✓
  - Health endpoints monitored: ✓
  - Alert system configured: ✓

- [ ] **Team Notified**
  - Production URL shared: https://your-domain.com
  - Admin access documented: ✓
  - Emergency procedures documented: ✓

---

## 🔄 ONGOING MAINTENANCE SCHEDULE

### Daily
- [ ] Check health endpoints
- [ ] Review error logs
- [ ] Verify backups completed

### Weekly
- [ ] Review security logs
- [ ] Analyze performance metrics
- [ ] Check disk space usage
- [ ] Review monitoring alerts

### Monthly
- [ ] Update dependencies: `composer update`
- [ ] Review database performance
- [ ] Test disaster recovery
- [ ] Security audit: `composer audit`

### Quarterly
- [ ] Performance optimization review
- [ ] Update Docker base images
- [ ] Security penetration test
- [ ] Review and update documentation

---

## 🚨 EMERGENCY CONTACTS

- **VPS Provider:** Hetzner Cloud Support
- **Domain Registrar:** `_________________`
- **SSL Certificates:** Let's Encrypt (auto-renewal)
- **Primary Admin:** `_________________`
- **Technical Lead:** `_________________`

---

## 📞 SUPPORT RESOURCES

- **Production Deployment Guide:** PRODUCTION_DEPLOYMENT.md
- **Application Reference:** CLAUDE.md
- **Backup Scripts:** /home/infinity/backup-database.sh
- **Health Check:** /home/infinity/docker-health-check.sh
- **Server Info:** /home/infinity/DEPLOYMENT_INFO.txt

---

## ✨ DEPLOYMENT COMPLETE!

**Deployed:** `_________________`
**By:** `_________________`
**Production URL:** https://`_________________`
**Status:** 🟢 LIVE

---

**Next Steps:**
1. Monitor closely for first 48 hours
2. Gradual user onboarding
3. Document any issues
4. Optimize based on real-world usage

**Congratulations! Your Infinity application is now in production! 🎉**
