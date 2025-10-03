# Log Rotation - Quick Setup

## Automated Setup (Recommended)

Run this single command to set up everything:

```bash
cd /home/user/inf/app && chmod +x scripts/setup-log-rotation.sh && ./scripts/setup-log-rotation.sh
```

This configures:
- ✅ Monolog daily rotation (already configured)
- ✅ Systemd timer for automatic compression & cleanup
- ✅ Runs daily at 2:00 AM
- ✅ Logs older than 7 days → compressed
- ✅ Compressed logs older than 90 days → deleted

## Manual Setup

If you prefer manual setup or need to customize:

### 1. Create Systemd Service

```bash
sudo tee /etc/systemd/system/infinity-logs-cleanup.service << 'EOF'
[Unit]
Description=Infinity Log Cleanup and Compression
After=network.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/home/user/inf/app
ExecStart=/usr/bin/php /home/user/inf/app/bin/console app:logs:cleanup --env=prod
StandardOutput=journal
StandardError=journal
EOF
```

### 2. Create Systemd Timer

```bash
sudo tee /etc/systemd/system/infinity-logs-cleanup.timer << 'EOF'
[Unit]
Description=Daily Infinity Log Cleanup
Requires=infinity-logs-cleanup.service

[Timer]
OnCalendar=daily
OnCalendar=02:00
Persistent=true

[Install]
WantedBy=timers.target
EOF
```

### 3. Enable and Start

```bash
sudo systemctl daemon-reload
sudo systemctl enable infinity-logs-cleanup.timer
sudo systemctl start infinity-logs-cleanup.timer
```

## Verification

```bash
# Check timer status
systemctl status infinity-logs-cleanup.timer

# See next scheduled run
systemctl list-timers infinity-logs-cleanup.timer

# Test manually
php bin/console app:logs:cleanup --dry-run

# View logs
journalctl -u infinity-logs-cleanup.service -n 50
```

## Configuration

### Retention Periods

Configured in `config/packages/monolog.yaml`:

| Log Type  | Rotation | Compression | Deletion |
|-----------|----------|-------------|----------|
| Audit     | Daily    | 7 days      | 90 days  |
| Security  | Daily    | 7 days      | 365 days |
| Others    | Daily    | 7 days      | 30 days  |

### Customization

Edit retention periods:

```bash
# Compress after 14 days, delete after 180 days
sudo nano /etc/systemd/system/infinity-logs-cleanup.service

# Change ExecStart line to:
ExecStart=/usr/bin/php /home/user/inf/app/bin/console app:logs:cleanup --compress-after=14 --delete-after=180 --env=prod

# Reload
sudo systemctl daemon-reload
```

## Monitoring

```bash
# Disk usage
du -sh /home/user/inf/app/var/log/

# Recent compressed files
ls -lht /home/user/inf/app/var/log/*.gz | head -10

# Service logs
journalctl -u infinity-logs-cleanup.service --since today
```

## Troubleshooting

**Timer not running:**
```bash
sudo systemctl status infinity-logs-cleanup.timer
sudo journalctl -u infinity-logs-cleanup.service
```

**Permission errors:**
```bash
sudo chown -R www-data:www-data /home/user/inf/app/var/log
sudo chmod -R 755 /home/user/inf/app/var/log
```

**Manual cleanup:**
```bash
cd /home/user/inf/app
php bin/console app:logs:cleanup --env=prod
```

## Uninstall

```bash
sudo systemctl stop infinity-logs-cleanup.timer
sudo systemctl disable infinity-logs-cleanup.timer
sudo rm /etc/systemd/system/infinity-logs-cleanup.{service,timer}
sudo systemctl daemon-reload
```

---

**Phase**: 1 - Log Management & Rotation
**Status**: Complete
**Updated**: 2025-10-03
