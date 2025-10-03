# Phase 5: Compliance & Retention Policies - Setup Guide

## Overview

Phase 5 implements automated retention policies, compliance reporting, and data protection measures to meet regulatory requirements (GDPR, SOC2, etc.). This guide covers setup, configuration, and operational procedures.

---

## Components Implemented

### 1. Configuration Files
- **`config/packages/audit.yaml`** - Retention policies, encryption settings, GDPR compliance

### 2. Services
- **`AuditRetentionService`** - Enforces retention policies and GDPR anonymization
- **`ComplianceReportService`** - Generates GDPR and SOC2 compliance reports
- **`AuditEncryptionService`** - Encrypts/decrypts sensitive audit data

### 3. Entity Updates
- **`AuditLog`** entity - Added checksum field for tamper detection

### 4. Commands
- **`app:audit:retention`** - Enforce retention policies (delete old logs, anonymize data)
- **`app:audit:verify`** - Verify audit log integrity (detect tampering)

---

## Configuration

### Retention Policies

Edit `config/packages/audit.yaml` to configure retention periods:

```yaml
parameters:
    audit.retention.policies:
        'App\Entity\User': 365                  # 1 year
        'App\Entity\Organization': 1825         # 5 years
        'App\Entity\Course': 730                # 2 years
        'App\Entity\StudentCourse': 365         # 1 year
        'App\Entity\StudentLecture': 90         # 90 days
```

### Encryption Configuration

**Step 1: Generate Encryption Key**

```bash
# Generate a secure 256-bit encryption key
docker-compose exec app php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

**Step 2: Add to Environment**

Add to `.env.local`:

```bash
# Audit encryption key (256-bit hex)
AUDIT_ENCRYPTION_KEY=your_generated_key_here
```

### Integrity Salt Configuration

**Step 1: Generate Salt**

```bash
# Generate a secure salt for checksums
docker-compose exec app php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"
```

**Step 2: Add to Environment**

Add to `.env.local`:

```bash
# Audit integrity salt for tamper detection
AUDIT_INTEGRITY_SALT=your_generated_salt_here
```

---

## Database Migration

Run the migration to add the checksum field:

```bash
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

This adds the `checksum` column to the `audit_log` table for tamper detection.

---

## Cron Job Setup

### Option 1: System Crontab (Recommended for Production)

**Step 1: Open Crontab**

```bash
crontab -e
```

**Step 2: Add Cron Jobs**

```bash
# Audit Log Retention - Run weekly on Sunday at 3:00 AM
0 3 * * 0 cd /home/user/inf/app && docker-compose exec -T app php bin/console app:audit:retention --env=prod

# Audit Integrity Verification - Run daily at 4:00 AM
0 4 * * * cd /home/user/inf/app && docker-compose exec -T app php bin/console app:audit:verify --env=prod || mail -s "Audit Tampering Detected" admin@infinity.local

# Alternative: Log verification failures to file
0 4 * * * cd /home/user/inf/app && docker-compose exec -T app php bin/console app:audit:verify --env=prod >> /var/log/audit-verify.log 2>&1
```

### Option 2: Systemd Timers (Modern Linux)

**Step 1: Create Service File**

Create `/etc/systemd/system/audit-retention.service`:

```ini
[Unit]
Description=Audit Log Retention Policy Enforcement
After=docker.service
Requires=docker.service

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/home/user/inf/app
ExecStart=/usr/bin/docker-compose exec -T app php bin/console app:audit:retention --env=prod
StandardOutput=journal
StandardError=journal
```

**Step 2: Create Timer File**

Create `/etc/systemd/system/audit-retention.timer`:

```ini
[Unit]
Description=Run Audit Retention Weekly
Requires=audit-retention.service

[Timer]
OnCalendar=Sun *-*-* 03:00:00
Persistent=true

[Install]
WantedBy=timers.target
```

**Step 3: Create Verification Service**

Create `/etc/systemd/system/audit-verify.service`:

```ini
[Unit]
Description=Audit Log Integrity Verification
After=docker.service
Requires=docker.service

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/home/user/inf/app
ExecStart=/usr/bin/docker-compose exec -T app php bin/console app:audit:verify --env=prod
StandardOutput=journal
StandardError=journal
```

**Step 4: Create Verification Timer**

Create `/etc/systemd/system/audit-verify.timer`:

```ini
[Unit]
Description=Run Audit Verification Daily
Requires=audit-verify.service

[Timer]
OnCalendar=*-*-* 04:00:00
Persistent=true

[Install]
WantedBy=timers.target
```

**Step 5: Enable and Start Timers**

```bash
sudo systemctl daemon-reload
sudo systemctl enable audit-retention.timer
sudo systemctl enable audit-verify.timer
sudo systemctl start audit-retention.timer
sudo systemctl start audit-verify.timer
```

**Step 6: Verify Timers**

```bash
# List all timers
sudo systemctl list-timers

# Check specific timer status
sudo systemctl status audit-retention.timer
sudo systemctl status audit-verify.timer
```

---

## Manual Command Usage

### Retention Policy Enforcement

```bash
# Dry run (show what would be deleted)
docker-compose exec app php bin/console app:audit:retention --dry-run

# Execute retention policies
docker-compose exec app php bin/console app:audit:retention

# Execute without anonymization
docker-compose exec app php bin/console app:audit:retention --skip-anonymize
```

### Integrity Verification

```bash
# Verify all audit logs
docker-compose exec app php bin/console app:audit:verify

# Verify only recent 1000 logs
docker-compose exec app php bin/console app:audit:verify --limit=1000

# Stop on first tampered record
docker-compose exec app php bin/console app:audit:verify --fail-fast

# Show details of tampered records
docker-compose exec app php bin/console app:audit:verify --verbose-failures
```

---

## Compliance Reports

### GDPR Data Subject Access Request (DSAR)

Generate a GDPR report for a user:

```php
use App\Service\ComplianceReportService;

// In a controller or command
$report = $complianceReportService->generateGDPRReport($user);

// Returns array with:
// - Data subject information
// - Complete audit trail
// - Entities created by user
// - Entities modified by user
// - Retention status
// - User rights information
```

### SOC2 Audit Report

Generate SOC2 compliance report for a period:

```php
use App\Service\ComplianceReportService;

$from = new \DateTime('-30 days');
$to = new \DateTime();

$report = $complianceReportService->generateSOC2AuditReport($from, $to);

// Returns array with:
// - Total events in period
// - Events by action type
// - Events by entity type
// - Security events (deletions, admin actions)
// - Compliance checks (encryption, retention, integrity)
```

---

## Security Monitoring

### Tamper Detection Alerts

If tampering is detected, the `app:audit:verify` command returns exit code 1.

**Email Alert Setup:**

```bash
# Add to crontab
0 4 * * * cd /home/user/inf/app && docker-compose exec -T app php bin/console app:audit:verify --env=prod || echo "Audit tampering detected at $(date)" | mail -s "🚨 SECURITY ALERT: Audit Tampering" admin@infinity.local
```

**Slack/Discord Webhook Alert:**

```bash
# Add to crontab
0 4 * * * cd /home/user/inf/app && docker-compose exec -T app php bin/console app:audit:verify --env=prod || curl -X POST -H 'Content-type: application/json' --data '{"text":"🚨 Audit tampering detected"}' YOUR_WEBHOOK_URL
```

### Monitoring Logs

```bash
# View verification results
docker-compose exec app tail -f var/log/prod.log | grep audit

# Check systemd journal
sudo journalctl -u audit-verify.service -f

# Check cron execution
grep CRON /var/log/syslog
```

---

## Troubleshooting

### Issue: Retention command fails with "Entity not found"

**Solution:** Ensure all entity classes in `audit.yaml` exist:

```bash
docker-compose exec app php bin/console debug:container --parameters | grep audit.retention
```

### Issue: Verify command fails with "AUDIT_INTEGRITY_SALT not set"

**Solution:** Add the salt to your environment:

```bash
# Generate salt
docker-compose exec app php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# Add to .env.local
echo "AUDIT_INTEGRITY_SALT=your_salt_here" >> .env.local

# Restart containers
docker-compose restart app
```

### Issue: All logs show "Missing Checksum"

**Cause:** Existing logs created before checksum implementation don't have checksums.

**Solution:** This is expected behavior. Only new logs will have checksums. Old logs can't be verified but won't cause failures.

### Issue: Cron job not executing

**Check:**

```bash
# Verify cron service is running
sudo systemctl status cron

# Check cron logs
grep CRON /var/log/syslog | tail -20

# Test command manually
docker-compose exec app php bin/console app:audit:retention --dry-run
```

---

## Performance Considerations

### Large Audit Tables

For databases with millions of audit logs:

1. **Run retention more frequently** (daily instead of weekly)
2. **Use --limit option** for verification:
   ```bash
   docker-compose exec app php bin/console app:audit:verify --limit=10000
   ```
3. **Batch deletion** in retention policy service

### Database Optimization

After retention cleanup, reclaim space:

```bash
docker-compose exec database psql -U infinity_user -d infinity_db -c "VACUUM FULL audit_log;"
```

---

## Compliance Checklist

- ✅ Retention policies configured for all entity types
- ✅ GDPR anonymization enabled and configured
- ✅ Encryption key generated and secured
- ✅ Integrity salt generated and secured
- ✅ Database migration executed
- ✅ Cron jobs configured and enabled
- ✅ Tamper detection alerts configured
- ✅ Monitoring in place
- ✅ Tested retention command in dry-run mode
- ✅ Tested verification command
- ✅ Documentation reviewed by security team

---

## Next Steps

With Phase 5 complete, the audit system now provides:

- ✅ Automated retention policy enforcement
- ✅ GDPR-compliant data anonymization
- ✅ SOC2 audit reporting
- ✅ Encryption for sensitive data
- ✅ Tamper detection with checksums
- ✅ Automated compliance monitoring

**Optional Phase 6**: Advanced Analytics & Monitoring (anomaly detection, dashboards, predictive analytics)

---

## Support

For issues or questions:
- Check troubleshooting section above
- Review command help: `php bin/console app:audit:retention --help`
- Review logs: `docker-compose logs -f app`
- Contact: admin@infinity.local

---

**Generated**: October 3, 2025
**Phase**: 5 - Compliance & Retention Policies
**Status**: Complete
