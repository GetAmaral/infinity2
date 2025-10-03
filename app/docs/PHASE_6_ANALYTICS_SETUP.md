# Phase 6: Advanced Analytics & Monitoring - Setup Guide

## Overview

Phase 6 provides actionable insights from audit data through dashboards, anomaly detection, and predictive analytics for security and compliance teams. This is an **optional enhancement** that adds advanced monitoring capabilities on top of the core audit system.

---

## Components Implemented

### 1. Services
- **`AuditAnalyticsService`** - Metrics, trend analysis, and anomaly detection
- **`PredictiveAnalyticsService`** - Forecasting and capacity planning using linear regression
- **`AuditAlertService`** - Alert notifications for detected anomalies

### 2. Controller
- **`AuditAnalyticsController`** - Analytics dashboard with real-time metrics and charts

### 3. Repository Enhancements
- Added 7 analytics query methods to `AuditLogRepository`:
  - `countInLastHour()` - Recent activity monitoring
  - `findHighVolumeUsers()` - Bulk operation detection
  - `findRapidlyChangingEntities()` - Rapid change detection
  - `getHourlyDistribution()` - 24-hour activity patterns
  - `getTopActiveUsers()` - Most active user ranking
  - `getMostModifiedEntities()` - Hot entity tracking
  - `getActionBreakdown()` - Action type distribution

### 4. Command
- **`app:audit:analytics:report`** - Weekly analytics report generation

### 5. Frontend
- Analytics dashboard template with Chart.js visualizations
- Bar chart for hourly distribution
- Pie chart for action breakdown
- Responsive Bootstrap 5 design

---

## Features

### Real-Time Analytics
- ✅ Total events counter
- ✅ Today's activity
- ✅ Weekly activity trends
- ✅ Hourly distribution (last 24 hours)
- ✅ Action breakdown (created/updated/deleted)
- ✅ Top 10 active users
- ✅ Most modified entities

### Anomaly Detection
Automatically detects 3 types of suspicious patterns:

**1. Bulk Operations (Medium Severity)**
- Trigger: User performs >100 operations in 1 hour
- Use case: Detect automated scripts or data exports

**2. Off-Hours Activity (Low Severity)**
- Trigger: >20 operations outside business hours (9am-6pm)
- Use case: Detect unauthorized access or scheduled jobs

**3. Rapid Changes (High Severity)**
- Trigger: Same entity modified >10 times in 5 minutes
- Use case: Detect potential data corruption or attacks

### Predictive Analytics
- ✅ Next week activity prediction
- ✅ Trend analysis (increasing/decreasing/stable)
- ✅ Prediction confidence score
- ✅ Capacity recommendations
- ✅ 7-day daily forecasts

### Alerting
- ✅ Anomalies logged to security channel
- ✅ Capacity warnings for significant increases
- 🔜 Email notifications (ready for extension)
- 🔜 Slack notifications (ready for extension)

---

## Accessing the Analytics Dashboard

### Web Interface

**URL**: `https://localhost/admin/audit/analytics`

**Access**:
1. Login as admin user
2. Click user dropdown in navigation
3. Select "Audit Analytics" under Admin section

**Requirements**:
- `ROLE_ADMIN` permission
- Phase 1-4 implemented (audit system functional)

---

## Using the Analytics Report Command

### Generate Report

```bash
# Basic report
docker-compose exec app php bin/console app:audit:analytics:report

# With alert notifications
docker-compose exec app php bin/console app:audit:analytics:report --send-alerts
```

### Sample Output

```
Weekly Audit Analytics Report
=============================

Generated: 2025-10-03 15:00:00

Summary Statistics
------------------
Metric                Value
Total Events          14
Events Today          5
Events This Week      14
Anomalies Detected    0

Top Active Users (Last 7 Days)
-------------------------------
User                  Actions
admin@infinity.local  14

Most Modified Entities (Last 7 Days)
-------------------------------------
Entity Type           Modifications
StudentCourse         8
StudentLecture        6

Detected Anomalies
------------------
✓ No anomalies detected

Predictions
-----------
Metric                          Value
Predicted Events Next Week      15
Trend                           INCREASING
Confidence                      75.0%

Capacity Recommendation
-----------------------
✓ Stable activity predicted
Recommendation: Current capacity is appropriate

[OK] Weekly analytics report completed
```

---

## Scheduling Automated Reports

### Option 1: Cron Job

```bash
# Edit crontab
crontab -e

# Add weekly report (Monday 9:00 AM)
0 9 * * 1 cd /home/user/inf/app && docker-compose exec -T app php bin/console app:audit:analytics:report --send-alerts --env=prod >> /var/log/audit-analytics.log 2>&1
```

### Option 2: Systemd Timer

**Create Service**: `/etc/systemd/system/audit-analytics-report.service`

```ini
[Unit]
Description=Audit Analytics Weekly Report
After=docker.service
Requires=docker.service

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/home/user/inf/app
ExecStart=/usr/bin/docker-compose exec -T app php bin/console app:audit:analytics:report --send-alerts --env=prod
StandardOutput=journal
StandardError=journal
```

**Create Timer**: `/etc/systemd/system/audit-analytics-report.timer`

```ini
[Unit]
Description=Run Audit Analytics Report Weekly
Requires=audit-analytics-report.service

[Timer]
OnCalendar=Mon *-*-* 09:00:00
Persistent=true

[Install]
WantedBy=timers.target
```

**Enable Timer**:

```bash
sudo systemctl daemon-reload
sudo systemctl enable audit-analytics-report.timer
sudo systemctl start audit-analytics-report.timer

# Verify
sudo systemctl status audit-analytics-report.timer
```

---

## Understanding the Metrics

### Summary Statistics

| Metric | Description |
|--------|-------------|
| Total Events | All audit log entries since inception |
| Events Today | Events logged today (00:00 to now) |
| Events This Week | Events since Monday of current week |
| Anomalies Detected | Currently active anomalies |

### Hourly Distribution Chart
- Bar chart showing events per hour (last 24 hours)
- X-axis: Hour (0-23)
- Y-axis: Event count
- Helps identify peak activity times

### Action Breakdown Chart
- Pie chart showing distribution of actions
- Green: Created
- Blue: Updated
- Red: Deleted
- Helps understand operation types

### Top Active Users
- Users ranked by action count (last 7 days)
- Useful for identifying power users
- Can detect compromised accounts

### Most Modified Entities
- Entity types ranked by modification count
- Useful for identifying hot spots
- Can detect targeted attacks

---

## Anomaly Detection Configuration

### Thresholds

Current detection thresholds (defined in `AuditAnalyticsService`):

```php
// Bulk Operations
private const BULK_OPS_THRESHOLD = 100;      // ops per hour
private const BULK_OPS_WINDOW = '-1 hour';

// Off-Hours Activity
private const OFF_HOURS_START = 9;  // 9 AM
private const OFF_HOURS_END = 18;   // 6 PM
private const OFF_HOURS_THRESHOLD = 20;      // ops per hour

// Rapid Changes
private const RAPID_CHANGE_THRESHOLD = 10;   // changes per entity
private const RAPID_CHANGE_WINDOW = '-5 minutes';
```

### Customizing Thresholds

To adjust thresholds, edit `src/Service/AuditAnalyticsService.php`:

```php
// Example: Lower bulk operations threshold
private function detectBulkOperations(): ?array
{
    $threshold = 50; // Changed from 100
    // ... rest of method
}
```

---

## Predictive Analytics

### How It Works

1. **Data Collection**: Analyzes last 4 weeks of audit logs
2. **Linear Regression**: Calculates trend line: `y = mx + b`
3. **Prediction**: Forecasts next week using trend
4. **Confidence**: Calculated from data consistency (coefficient of variation)

### Interpreting Predictions

**Predicted Events**: Expected number of events next week

**Trend**:
- **Increasing**: Activity growing by >10%
- **Decreasing**: Activity declining by >10%
- **Stable**: Activity change within ±10%

**Confidence**:
- **High (>70%)**: Data shows consistent pattern
- **Medium (40-70%)**: Some variation in data
- **Low (<40%)**: High variation, less reliable

**Capacity Recommendations**:
- **Warning**: >50% increase predicted, review capacity
- **Info**: 25-50% change, monitor closely
- **Success**: Stable or <25% change, capacity OK

---

## Performance Considerations

### Query Performance

All analytics queries use indexed fields:
- `created_at` for time-based queries
- `user_id` for user-based queries
- `entity_class` for entity-type queries

**Technical Note**: The hourly distribution query (`getHourlyDistribution()`) uses native SQL with PostgreSQL's `EXTRACT()` function instead of DQL for optimal performance and compatibility.

For databases with >100k audit logs:
- Consider materialized views for hourly distribution
- Add database-level caching for frequent queries
- Limit dashboard to recent data (last 30 days)

### Chart.js Performance

Charts render client-side. For large datasets:
- Hourly chart limited to 24 data points (optimal)
- Action breakdown limited to 3-5 categories (optimal)
- Top users/entities limited to 10 entries (optimal)

---

## Extending the System

### Adding Email Alerts

1. Install Symfony Mailer:
```bash
docker-compose exec app composer require symfony/mailer
```

2. Configure mailer in `.env`:
```bash
MAILER_DSN=smtp://user:pass@smtp.example.com:587
SECURITY_EMAIL=security@infinity.local
```

3. Uncomment email methods in `AuditAlertService.php`

### Adding Slack Notifications

1. Install Notifier component:
```bash
docker-compose exec app composer require symfony/slack-notifier
```

2. Configure Slack webhook in `.env`:
```bash
SLACK_DSN=slack://TOKEN@default?channel=security
```

3. Uncomment Slack methods in `AuditAlertService.php`

### Adding Custom Anomaly Detection

Add a new detection method in `AuditAnalyticsService.php`:

```php
private function detectCustomAnomaly(): ?array
{
    // Your detection logic
    $results = $this->auditLogRepository->customQuery();

    if (!empty($results)) {
        return [
            'type' => 'custom_anomaly',
            'severity' => 'medium',
            'message' => 'Custom anomaly detected',
            'data' => $results,
        ];
    }

    return null;
}

// Add to detectAnomalies() method
public function detectAnomalies(): array
{
    $anomalies = [];

    if ($custom = $this->detectCustomAnomaly()) {
        $anomalies[] = $custom;
    }

    // ... existing detections

    return $anomalies;
}
```

---

## Troubleshooting

### Issue: Charts not displaying

**Cause**: Chart.js not loaded or JavaScript error

**Solution**:
```bash
# Clear cache
docker-compose exec app php bin/console cache:clear

# Verify Chart.js in importmap
grep chart.js /home/user/inf/app/importmap.php
```

### Issue: No data in analytics

**Cause**: Insufficient audit log data

**Solution**:
- Generate test activity by creating/updating entities
- Wait for async queue to process (Phase 2)
- Check audit_log table has records:
```bash
docker-compose exec database psql -U infinity_user -d infinity_db -c "SELECT COUNT(*) FROM audit_log;"
```

### Issue: Prediction confidence is 0%

**Cause**: Not enough historical data (need 4+ weeks)

**Solution**: Wait for more data to accumulate, or prediction will show low confidence.

### Issue: Anomalies not being detected

**Check thresholds**: Current activity may not exceed thresholds

**Test anomaly detection**:
```bash
# Generate bulk operations (simulate)
for i in {1..150}; do
    docker-compose exec app php bin/console app:some-operation
done

# Check analytics
docker-compose exec app php bin/console app:audit:analytics:report
```

---

## API for Custom Integrations

### Accessing Metrics Programmatically

```php
use App\Service\AuditAnalyticsService;
use App\Service\PredictiveAnalyticsService;

class MyController extends AbstractController
{
    public function getMetrics(
        AuditAnalyticsService $analytics,
        PredictiveAnalyticsService $predictive
    ): JsonResponse {
        return $this->json([
            'summary' => $analytics->getSummaryStatistics(),
            'anomalies' => $analytics->detectAnomalies(),
            'prediction' => $predictive->predictNextWeekActivity(),
        ]);
    }
}
```

---

## Security Considerations

### Access Control
- ✅ Dashboard requires `ROLE_ADMIN`
- ✅ Analytics data includes all organizations (admin view)
- ✅ Anomaly alerts logged to security channel

### Data Privacy
- Analytics aggregate data (no PII exposure)
- User emails shown only to admins
- Anomaly detection doesn't expose sensitive field values

---

## Success Criteria

All Phase 6 goals achieved:

- ✅ Real-time analytics dashboard
- ✅ Anomaly detection with 3 detection types
- ✅ Weekly automated reports
- ✅ Predictive analytics for capacity planning
- ✅ Visual charts and graphs (Chart.js)
- ✅ Export analytics data (via report command)
- ✅ Admin-only access control
- ✅ Performance-optimized queries

---

## Maintenance

### Daily
- Review anomaly alerts in security logs
- Check dashboard for unusual patterns

### Weekly
- Review automated analytics report
- Verify predictions accuracy
- Adjust thresholds if needed

### Monthly
- Analyze trend accuracy
- Review capacity recommendations
- Update detection algorithms based on false positives

---

## Next Steps

Phase 6 is complete! The audit system now provides:

**Phases 1-5** (Core):
- ✅ Log rotation and compression
- ✅ Async logging performance
- ✅ Historical audit trail
- ✅ Admin UI with search/export
- ✅ Compliance (GDPR, SOC2)
- ✅ Retention policies
- ✅ Encryption & tamper detection

**Phase 6** (Analytics):
- ✅ Real-time metrics dashboard
- ✅ Anomaly detection
- ✅ Predictive forecasting
- ✅ Automated reporting

**Optional Enhancements**:
- Email/Slack notifications
- Custom anomaly detectors
- Machine learning models
- Real-time streaming dashboard

---

## Support

For issues or questions:
- Review this guide
- Check command help: `php bin/console app:audit:analytics:report --help`
- Review security logs: `docker-compose logs -f app | grep anomaly`
- Contact: admin@infinity.local

---

**Generated**: October 3, 2025
**Phase**: 6 - Advanced Analytics & Monitoring
**Status**: Complete
