# Luminai — Rebuild VPS from Scratch

This guide documents how to reconstruct the Luminai production VPS from **(a)** this git repository and **(b)** the local backup taken before decommission. It is the single source of truth for resurrecting the service.

> The original VPS was decommissioned on **2026-05-07** after a backup at `~/inf/backups/vps-prod/2026-05-07_001604/`. Anything in this guide is what you will need that is **not** in the git repo.

---

## 0. What was on the VPS

| Layer | Detail |
|-------|--------|
| Provider | **Hetzner Cloud** (Ubuntu image, instance-id 110091964) |
| Hostname | `infinity-prod` |
| Public IP | `91.98.137.175` |
| OS | Ubuntu 24.04.3 LTS (Noble Numbat), kernel 6.8 |
| Specs | 4 vCPU, 7.6 GB RAM, 150 GB disk |
| Domains | `avelum.com.br` (primary, `DEFAULT_URI`), `luminai.ia.br` (legacy) — both with wildcard subdomains |
| Stack | Docker 28 + Compose v2 — 5 services (database, app, messenger_worker, redis, nginx) |
| Project root | `/opt/luminai` |
| TLS | Let's Encrypt via host `certbot` (system package) — bind-mounted into nginx container |
| Backup | Custom script at `/opt/luminai_backup/` — daily 22:00 UTC pg_dump → GPG-encrypt → push to private GitHub repo `GetAmaral/LuminaiBackup` |

---

## 1. Backup inventory (local)

Path: `/home/user/inf/backups/vps-prod/2026-05-07_001604/`

| File | Size | Purpose | Sensitive |
|------|-----:|---------|:---------:|
| `luminai_db.dump` | 2.0 MB | PostgreSQL 18 custom dump (304 TOC entries) — schema + data | — |
| `uploads.tar.gz` | 4.3 MB | `app/public/uploads/` | — |
| `var.tar.gz` | 6.2 GB | Full `app/var/` — **videos**, cache, logs, sessions | — |
| `migrations_not_in_git.tar.gz` | 5.5 KB | 8 Doctrine migrations that existed only on the VPS (gitignored). Already applied to the dump, but kept for traceability. | — |
| `critical-secrets/config_secrets.tar.gz` | 9.5 KB | `.env`, `app/.env`, `nginx/conf/*`, `nginx/ssl/*`, `docker-compose.override.yml` | 🔒 |
| `critical-secrets/letsencrypt.tar.gz` | 12 KB | Full `/etc/letsencrypt/` (certs, account, renewal config) — certs expired, but private key + account ID let you reuse Let's Encrypt account | 🔒 |
| `critical-secrets/root_gnupg.tar.gz` | 9.9 KB | **GPG keyring of root** (key id `7033806CB718ED40ACBAB94B4B0BFF94CCCFB556`, uid `Luminai Backup <backup@luminai.local>`). **Required to decrypt the encrypted historical backups in `LuminaiBackup` GitHub repo.** | 🔒🔒 |
| `critical-secrets/luminai_backup_repo.tar.gz` | 344 MB | Full clone of `/opt/luminai_backup/` — backup script, GPG public key, README, **30 historical encrypted backups (Mar–Apr 2026)** + git history | 🔒 |
| `SHA256SUMS` | — | Integrity checksums | — |

> **🔒 Anything inside `critical-secrets/` must NEVER be committed to git.** It contains real production passwords (DB, Redis), Symfony `APP_SECRET`, GPG private key, and TLS private keys.

---

## 2. Prerequisites for rebuild

- Hetzner Cloud account (or any cloud provider — Ubuntu 24.04 image, ≥4 vCPU, ≥8 GB RAM, ≥150 GB disk recommended)
- DNS control for `avelum.com.br` and (optionally) `luminai.ia.br` — see [DNS_HETZNER.md](../DNS_HETZNER.md)
- SSH keypair (the original was `~/.ssh/luminai_vps`)
- GitHub access to repos `GetAmaral/inf` (this repo) and `GetAmaral/LuminaiBackup` (encrypted backups)
- This local backup folder accessible

---

## 3. Step-by-step rebuild

### 3.1 Provision a new server

1. Create a Hetzner Cloud server: **Ubuntu 24.04**, CX22 (4 vCPU / 8 GB RAM / 80 GB) or larger; pick a Brazil-friendly region (`fsn1` / `nbg1` worked acceptably).
2. Add your SSH public key during creation; root login by key only.
3. Note the public IPv4. Update DNS A records:
   - `avelum.com.br` → `<NEW_IP>`
   - `*.avelum.com.br` → `<NEW_IP>`
   - `luminai.ia.br` → `<NEW_IP>` (if keeping legacy)
   - `*.luminai.ia.br` → `<NEW_IP>`

### 3.2 Initial server hardening

```bash
ssh root@<NEW_IP>

# Timezone
timedatectl set-timezone America/Sao_Paulo

# Update + base packages
apt-get update && apt-get -y upgrade
apt-get install -y curl ca-certificates gnupg ufw certbot

# Firewall (the old VPS had ufw INACTIVE — fix this)
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# Disable system nginx if Ubuntu image ships it (the old VPS had it enabled AND active,
# which conflicts with the docker nginx on port 80 — known footgun)
systemctl disable --now nginx 2>/dev/null || true
apt-get remove -y nginx nginx-common 2>/dev/null || true
```

### 3.3 Install Docker

```bash
install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu noble stable" \
  > /etc/apt/sources.list.d/docker.list
apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
systemctl enable --now docker
```

### 3.4 Clone the application repo

```bash
mkdir -p /opt && cd /opt
git clone git@github.com:GetAmaral/inf.git luminai
cd luminai
```

### 3.5 Restore secrets

From your laptop (where the backup folder lives):

```bash
# .env files + nginx configs + local self-signed certs
scp ~/inf/backups/vps-prod/2026-05-07_001604/critical-secrets/config_secrets.tar.gz \
    root@<NEW_IP>:/opt/luminai/

# On the VPS:
ssh root@<NEW_IP>
cd /opt/luminai
tar xzf config_secrets.tar.gz
rm config_secrets.tar.gz
# This restores: .env, app/.env, nginx/conf/{default,production}.conf, nginx/ssl/*, docker-compose.override.yml
```

> ⚠️ **Fix `APP_DEBUG=1` in `app/.env`** — the original prod `.env` had this misconfigured. Set `APP_DEBUG=0` for production.

### 3.6 Restore TLS certificates

Two options:

**(a) Reuse old Let's Encrypt account, get fresh certs (recommended — old certs expired):**

```bash
# Restore the LE account/config (gives you the same account ID — avoids rate limits)
scp ~/inf/backups/vps-prod/2026-05-07_001604/critical-secrets/letsencrypt.tar.gz \
    root@<NEW_IP>:/etc/
ssh root@<NEW_IP> 'cd /etc && tar xzf letsencrypt.tar.gz && rm letsencrypt.tar.gz'

# Get fresh certs (HTTP-01 challenge — make sure DNS points to NEW_IP first!)
certbot certonly --standalone -d avelum.com.br -d www.avelum.com.br --agree-tos -m <YOUR_EMAIL>
# For wildcard, use DNS-01 (requires Hetzner DNS API plugin or manual):
# certbot certonly --manual --preferred-challenges dns -d "*.avelum.com.br"

certbot certonly --standalone -d luminai.ia.br -d www.luminai.ia.br --agree-tos -m <YOUR_EMAIL>

# Sync local-bind copies expected by nginx config
cp /etc/letsencrypt/live/luminai.ia.br/fullchain.pem /opt/luminai/nginx/ssl/luminai.ia.br.crt
cp /etc/letsencrypt/live/luminai.ia.br/privkey.pem  /opt/luminai/nginx/ssl/luminai.ia.br.key
chmod 644 /opt/luminai/nginx/ssl/*.crt
chmod 600 /opt/luminai/nginx/ssl/*.key
```

The avelum certs are referenced directly from `/etc/letsencrypt/live/avelum.com.br/` (see `nginx/conf/production.conf`).

**(b) Reuse the expired certs** (will produce browser warnings until renewed):

```bash
ssh root@<NEW_IP> 'cd /etc && tar xzf letsencrypt.tar.gz && rm letsencrypt.tar.gz'
# Then on first nginx start, immediately run `certbot renew --force-renewal`
```

### 3.7 Configure auto-renewal hook

Recreate `/etc/letsencrypt/renewal-hooks/deploy/01-reload-nginx.sh` (it's already inside the restored letsencrypt.tar.gz, but verify):

```bash
#!/bin/bash
cd /opt/luminai && docker compose exec -T nginx nginx -s reload
```

```bash
chmod +x /etc/letsencrypt/renewal-hooks/deploy/01-reload-nginx.sh
```

### 3.8 Start services

```bash
cd /opt/luminai
docker compose build app
docker compose up -d database
# wait for healthy
until docker exec luminai_database pg_isready -U luminai_user -d luminai_db; do sleep 2; done
```

### 3.9 Restore database

From your laptop:

```bash
scp ~/inf/backups/vps-prod/2026-05-07_001604/luminai_db.dump root@<NEW_IP>:/tmp/
```

On the VPS:

```bash
# Wipe the auto-created empty DB and restore from dump
docker exec -i luminai_database pg_restore \
    -U luminai_user -d luminai_db \
    --clean --if-exists --no-owner --no-privileges \
    < /tmp/luminai_db.dump
rm /tmp/luminai_db.dump

# Sanity check
docker exec luminai_database psql -U luminai_user -d luminai_db -c "SELECT count(*) FROM organization;"
```

### 3.10 Restore uploads + var/

From your laptop (uploads is small; var is 6.2 GB — use rsync over SSH for resumable transfer):

```bash
# Uploads
scp ~/inf/backups/vps-prod/2026-05-07_001604/uploads.tar.gz root@<NEW_IP>:/tmp/
ssh root@<NEW_IP> 'cd /opt/luminai/app/public && tar xzf /tmp/uploads.tar.gz && rm /tmp/uploads.tar.gz'

# var/ (big — 6.2 GB)
rsync -avh --progress \
    ~/inf/backups/vps-prod/2026-05-07_001604/var.tar.gz \
    root@<NEW_IP>:/tmp/
ssh root@<NEW_IP> 'cd /opt/luminai/app && tar xzf /tmp/var.tar.gz && rm /tmp/var.tar.gz && chown -R 82:82 var/'
# UID 82 = www-data inside the FrankenPHP container (PHP Alpine image convention)
```

### 3.11 Start the rest of the stack

```bash
cd /opt/luminai
docker compose up -d
docker compose ps
docker compose logs -f --tail 100 app nginx
```

Smoke tests:

```bash
curl -k https://avelum.com.br/health
curl -k https://luminai.ia.br/health
```

### 3.12 Restore the backup automation

```bash
# Pull the backup repo into /opt
scp ~/inf/backups/vps-prod/2026-05-07_001604/critical-secrets/luminai_backup_repo.tar.gz \
    root@<NEW_IP>:/opt/
ssh root@<NEW_IP> 'cd /opt && tar xzf luminai_backup_repo.tar.gz && rm luminai_backup_repo.tar.gz'

# Restore the GPG keyring (needed to encrypt new backups + decrypt old ones)
scp ~/inf/backups/vps-prod/2026-05-07_001604/critical-secrets/root_gnupg.tar.gz \
    root@<NEW_IP>:/root/
ssh root@<NEW_IP> 'cd /root && tar xzf root_gnupg.tar.gz && rm root_gnupg.tar.gz && chmod 700 /root/.gnupg'

# Re-add cron job
ssh root@<NEW_IP> '(crontab -l 2>/dev/null; echo "0 22 * * * /bin/bash /opt/luminai_backup/backup.sh >> /opt/luminai_backup/logs/cron.log 2>&1") | crontab -'

# Test once
ssh root@<NEW_IP> 'bash /opt/luminai_backup/backup.sh'
```

---

## 4. Critical knowledge / footguns

### 4.1 Two distinct nginx setups — don't run both

The old VPS had **both** the system `nginx.service` (apt package) AND the `luminai_nginx` container running. The system one was bound to `:80` and silently broke the docker nginx. **Always**: `systemctl disable --now nginx && apt-get remove -y nginx*` before starting Docker.

### 4.2 `APP_DEBUG=1` shipped in production

The old `app/.env` had `APP_DEBUG=1`. The Symfony profiler being exposed in prod is a security and performance issue. Set to `0` on rebuild and confirm in the running container with:

```bash
docker compose exec app php bin/console debug:config framework
```

### 4.3 Two domains, two cert strategies

| Domain | Cert source | Mounted as |
|--------|-------------|------------|
| `avelum.com.br` | `/etc/letsencrypt/live/avelum.com.br/` (wildcard) | Bind mount via `docker-compose.override.yml` |
| `luminai.ia.br` | `/opt/luminai/nginx/ssl/luminai.ia.br.{crt,key}` (copied from `/etc/letsencrypt`) | Static file in `nginx/ssl/` |
| `localhost` (dev) | self-signed in `nginx/ssl/localhost.{crt,key}` | Same |

The certbot deploy hook at `/etc/letsencrypt/renewal-hooks/deploy/` reloads the nginx container.

### 4.4 8 migrations that lived only on the VPS

`app/migrations/` had 8 files matching `Version2025*` that were gitignored on the VPS but never pushed to git. They're **already baked into the DB dump** (the dump is the schema source of truth), but a copy is preserved in `migrations_not_in_git.tar.gz` for code archaeology. After the dump is restored, Doctrine will see them as "already executed" if you copy them back, or you can leave them out — the schema will already be correct.

### 4.5 Backup encryption key

The encrypted historical backups in `github.com:GetAmaral/LuminaiBackup` are **useless without the GPG private key** in `critical-secrets/root_gnupg.tar.gz`. If that file is lost, every backup older than the May 2026 plain dump becomes unrecoverable. **Make off-site copies of `root_gnupg.tar.gz`.**

To decrypt one of the historical backups:

```bash
gpg --import < (tar xzOf root_gnupg.tar.gz)  # or after restoring to ~/.gnupg
gpg -d luminai_backup_20260416_220001.backup.gpg > restored.dump
pg_restore -U luminai_user -d luminai_db --clean --if-exists < restored.dump
```

### 4.6 Hardening that was missing

The old VPS had:
- `ufw` **inactive** (no firewall)
- `fail2ban` **not installed**
- Root login by SSH key (OK), but no other user
- Single SSH key in `/root/.ssh/authorized_keys`

The rebuild guide enables `ufw` in 3.2. Consider also installing `fail2ban` and creating a non-root deploy user.

### 4.7 Subdomain-based multi-tenant routing

The app uses subdomains for organization isolation (`{org-slug}.avelum.com.br`). DNS must wildcard `*.avelum.com.br` to the server, and `OrganizationContext.php` recognizes both `avelum.com.br` and `luminai.ia.br` as valid base domains. See [MULTI_TENANT.md](MULTI_TENANT.md).

---

## 5. Verification checklist

- [ ] `docker compose ps` — all 5 containers up and healthy
- [ ] `curl -I https://avelum.com.br/` — 200 OK, valid TLS
- [ ] `curl -I https://luminai.ia.br/` — 200 OK, valid TLS
- [ ] Login works with a known organization slug
- [ ] Uploaded videos play (HLS streaming through nginx → app)
- [ ] `docker compose exec app php bin/console doctrine:schema:validate` — passes
- [ ] `bash /opt/luminai_backup/backup.sh` — completes, pushes to LuminaiBackup repo
- [ ] `certbot certificates` — both certs valid, not expiring soon
- [ ] `ufw status` — active, with 22/80/443

---

## 6. Related docs

- [VPS_DEPLOYMENT.md](VPS_DEPLOYMENT.md) — day-to-day deploy workflow once running
- [DOCKER.md](DOCKER.md) — service architecture
- [DATABASE.md](DATABASE.md) — schema patterns
- [MULTI_TENANT.md](MULTI_TENANT.md) — subdomain isolation
- [SECURITY.md](SECURITY.md) — auth & RBAC
- [ProductionDeployment.md](ProductionDeployment.md) — full production setup reference (older, more general)

If anything in section 3 fails, the dump is portable: `pg_restore` from `luminai_db.dump` into any PostgreSQL 18+ instance and you have the full data, regardless of how/where the rest is rebuilt.
