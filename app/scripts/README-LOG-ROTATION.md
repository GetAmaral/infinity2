# Log Rotation Scripts

## Quick Start

### Install (One Command)

```bash
sudo ./scripts/setup-log-rotation.sh
```

This will:
1. ✅ Create systemd service and timer
2. ✅ Enable automatic daily log cleanup at 2:00 AM
3. ✅ Test the configuration
4. ✅ Show you the status

### Uninstall

```bash
sudo ./scripts/uninstall-log-rotation.sh
```

## What It Does

**Daily at 2:00 AM:**
- Compress log files older than 7 days
- Delete compressed logs older than 90 days (security: 365 days)
- Free up disk space automatically

**Expected Results:**
- ~70% space savings from compression
- Automatic cleanup prevents disk space issues
- Logs organized by date

## Manual Commands

```bash
# Test without making changes
php bin/console app:logs:cleanup --dry-run

# Run manually
php bin/console app:logs:cleanup

# Check timer status
systemctl status infinity-logs-cleanup.timer

# See next scheduled run
systemctl list-timers infinity-logs-cleanup.timer

# View execution logs
journalctl -u infinity-logs-cleanup.service -n 50

# Manual trigger
sudo systemctl start infinity-logs-cleanup.service
```

## Files Created

- `/etc/systemd/system/infinity-logs-cleanup.service` - The service definition
- `/etc/systemd/system/infinity-logs-cleanup.timer` - The daily schedule

## Requirements

- Modern Linux with systemd (Ubuntu 16.04+, Debian 8+, CentOS 7+, etc.)
- PHP CLI
- sudo access

## Customization

Edit `/etc/systemd/system/infinity-logs-cleanup.service` to change retention:

```bash
# Change this line
ExecStart=/usr/bin/php /home/user/inf/app/bin/console app:logs:cleanup --env=prod

# To this (compress after 14 days, delete after 180 days)
ExecStart=/usr/bin/php /home/user/inf/app/bin/console app:logs:cleanup --compress-after=14 --delete-after=180 --env=prod

# Then reload
sudo systemctl daemon-reload
```
