# Monitoring & Health Checks Guide

Complete guide to monitoring Luminai application health, performance, and logs.

---

## Health Check Endpoints

### Simple Health Check

**URL**: `https://localhost/health`

**Purpose**: Quick availability check

**Response**:
```json
{
    "status": "OK"
}
```

**Usage**:
```bash
curl -k https://localhost/health
```

### Detailed Health Check

**URL**: `https://localhost/health/detailed`

**Purpose**: Comprehensive system monitoring

**Response Structure**:
```json
{
    "status": "OK",
    "timestamp": "2025-01-08T12:00:00+00:00",
    "checks": {
        "database": {
            "status": "OK",
            "connection": true,
            "response_time_ms": 2,
            "uuidv7_support": true
        },
        "redis": {
            "status": "OK",
            "connection": true,
            "version": "7.0.15",
            "memory_used_mb": 45.2,
            "memory_peak_mb": 52.1,
            "connected_clients": 3,
            "uptime_days": 15
        },
        "messenger": {
            "status": "OK",
            "queued_messages": 5,
            "failed_messages": 0
        },
        "disk_space": {
            "status": "OK",
            "total_gb": 256.0,
            "used_gb": 90.1,
            "free_gb": 165.9,
            "used_percent": 35.2
        },
        "storage_directories": {
            "status": "OK",
            "directories": {
                "var/videos/originals": {
                    "exists": true,
                    "writable": true,
                    "size_mb": 1250.5
                },
                "var/videos/hls": {
                    "exists": true,
                    "writable": true,
                    "size_mb": 890.3
                },
                "public/uploads": {
                    "exists": true,
                    "writable": true,
                    "size_mb": 45.2
                },
                "var/cache": {
                    "exists": true,
                    "writable": true,
                    "size_mb": 125.8
                },
                "var/log": {
                    "exists": true,
                    "writable": true,
                    "size_mb": 78.3
                }
            }
        },
        "php_extensions": {
            "status": "OK",
            "required": ["pdo_pgsql", "opcache", "intl", "gd", "redis"],
            "loaded": ["pdo_pgsql", "opcache", "intl", "gd", "redis"],
            "missing": []
        },
        "environment": {
            "status": "OK",
            "app_env": "dev",
            "database_configured": true,
            "redis_configured": true
        },
        "system": {
            "status": "OK",
            "php_version": "8.4.0",
            "memory_limit": "256M",
            "memory_usage_mb": 128.5,
            "memory_usage_percent": 50.2,
            "load_average": [0.5, 0.6, 0.7]
        }
    }
}
```

**Status Levels**:
- `OK`: All systems operational
- `WARNING`: Non-critical issues (e.g., >10 failed messages, >80% disk usage)
- `ERROR`: Critical issues requiring immediate attention (e.g., missing extensions, >90% disk, unwritable directories)

**Usage**:
```bash
# Pretty print
curl -k https://localhost/health/detailed | jq .

# Check status only
curl -k https://localhost/health/detailed | jq -r .status

# Check specific component
curl -k https://localhost/health/detailed | jq .checks.database
```

### Metrics Endpoint

**URL**: `https://localhost/health/metrics`

**Purpose**: Performance and database metrics

**Response**:
```json
{
    "timestamp": "2025-01-08T12:00:00+00:00",
    "database": {
        "total_size_mb": 512.5,
        "table_count": 28,
        "total_rows": 15420,
        "tables": [
            {
                "name": "user",
                "rows": 1250,
                "size_mb": 45.2
            },
            {
                "name": "organization",
                "rows": 25,
                "size_mb": 0.5
            }
        ]
    },
    "cache": {
        "redis_memory_mb": 45.2,
        "redis_keys": 1250,
        "opcache_enabled": true,
        "opcache_hit_rate": 98.5
    }
}
```

**Usage**:
```bash
curl -k https://localhost/health/metrics | jq .
```

---

## Log Monitoring

### Log Files

**Location**: `app/var/log/`

**Main Log Files**:
- `app.log` - Application logs (all environments)
- `dev.log` - Development environment logs
- `prod.log` - Production environment logs
- `security.log` - Security events (authentication, authorization)
- `performance.log` - Performance metrics
- `business.log` - Business logic events
- `audit.log` - Audit trail events

### Log Format

All logs use **structured JSON format**:

```json
{
    "message": "User logged in successfully",
    "context": {
        "user_id": "019296b7-55be-72db-8cfd-1234567890ab",
        "ip_address": "192.168.1.100"
    },
    "level": "INFO",
    "level_name": "INFO",
    "channel": "security",
    "datetime": "2025-01-08T12:00:00+00:00",
    "extra": {
        "file": "src/Security/Authenticator.php",
        "line": 125
    }
}
```

### Viewing Logs

**Real-time monitoring**:
```bash
# All logs
docker-compose exec app tail -f var/log/app.log | jq .

# Specific log file
docker-compose exec app tail -f var/log/security.log | jq .
docker-compose exec app tail -f var/log/performance.log | jq .
docker-compose exec app tail -f var/log/business.log | jq .

# Pretty print with color
docker-compose exec app tail -f var/log/app.log | jq -C .
```

**Filter by level**:
```bash
# Errors only
docker-compose exec app tail -f var/log/app.log | jq 'select(.level=="ERROR")'

# Warnings and errors
docker-compose exec app tail -f var/log/app.log | jq 'select(.level=="ERROR" or .level=="WARNING")'
```

**Filter by channel**:
```bash
# Security events
grep "security" var/log/app.log | jq .

# Business events
grep "business" var/log/app.log | jq .
```

**Count errors**:
```bash
# Total errors
grep -c '"level":"ERROR"' var/log/app.log

# Errors in last 100 lines
tail -100 var/log/app.log | grep -c '"level":"ERROR"'
```

**Top errors**:
```bash
grep '"level":"ERROR"' var/log/app.log | \
    jq -r .message | \
    sort | uniq -c | sort -rn | head -10
```

---

## Performance Monitoring

### Application Performance

**Performance Log Analysis**:
```bash
# View performance logs
docker-compose exec app tail -f var/log/performance.log | jq .

# Slow operations (>1s)
docker-compose exec app grep "duration" var/log/performance.log | \
    jq 'select(.context.duration_ms > 1000)'

# Average operation time
docker-compose exec app grep "operation_completed" var/log/performance.log | \
    jq -r .context.duration_ms | \
    awk '{sum+=$1; n++} END {print "Avg: " sum/n "ms"}'
```

### OPcache Monitoring

**Check OPcache status**:
```bash
docker-compose exec app php -r "print_r(opcache_get_status());"

# Hit rate
docker-compose exec app php -r "
\$stats = opcache_get_status();
\$hits = \$stats['opcache_statistics']['hits'];
\$misses = \$stats['opcache_statistics']['misses'];
\$rate = \$hits / (\$hits + \$misses) * 100;
echo 'OPcache hit rate: ' . round(\$rate, 2) . '%' . PHP_EOL;
"
```

### Redis Performance

**Memory usage**:
```bash
docker-compose exec redis redis-cli info memory

# Key metrics
docker-compose exec redis redis-cli info memory | grep -E "used_memory_human|used_memory_peak_human"
```

**Cache hit rate**:
```bash
docker-compose exec redis redis-cli info stats | grep -E "keyspace_hits|keyspace_misses"

# Calculate hit rate
docker-compose exec redis redis-cli --raw info stats | awk '
/keyspace_hits/ {hits=$2}
/keyspace_misses/ {misses=$2}
END {print "Hit rate: " (hits/(hits+misses)*100) "%"}'
```

**Monitor commands**:
```bash
# Watch commands in real-time
docker-compose exec redis redis-cli monitor

# Count command types
docker-compose exec redis redis-cli info commandstats
```

### Database Performance

**Connection pool**:
```bash
docker-compose exec database psql -U luminai_user -d luminai_db -c "
SELECT count(*) as total_connections,
       count(*) FILTER (WHERE state = 'active') as active_connections,
       count(*) FILTER (WHERE state = 'idle') as idle_connections
FROM pg_stat_activity;"
```

**Slow queries**:
```bash
docker-compose exec database psql -U luminai_user -d luminai_db -c "
SELECT query, mean_exec_time, calls
FROM pg_stat_statements
WHERE mean_exec_time > 100
ORDER BY mean_exec_time DESC
LIMIT 10;"
```

**Table sizes**:
```bash
docker-compose exec database psql -U luminai_user -d luminai_db -c "
SELECT schemaname, tablename,
       pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size
FROM pg_tables
WHERE schemaname = 'public'
ORDER BY pg_total_relation_size(schemaname||'.'||tablename) DESC
LIMIT 10;"
```

**Index usage**:
```bash
docker-compose exec database psql -U luminai_user -d luminai_db -c "
SELECT schemaname, tablename, indexname,
       idx_scan as index_scans,
       idx_tup_read as tuples_read
FROM pg_stat_user_indexes
ORDER BY idx_scan DESC
LIMIT 10;"
```

---

## Resource Monitoring

### Docker Container Stats

**Real-time stats**:
```bash
# All containers
docker stats

# Specific container
docker stats app

# Format output
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}"
```

### Disk Usage

**Container disk usage**:
```bash
# All Docker resources
docker system df

# Detailed breakdown
docker system df -v
```

**Application disk usage**:
```bash
# Check var/ directory
docker-compose exec app du -sh var/*

# Check public/ directory
docker-compose exec app du -sh public/*

# Find large files
docker-compose exec app find var/ -type f -size +100M -exec ls -lh {} \;
```

### Memory Usage

**Container memory**:
```bash
docker stats --no-stream --format "table {{.Name}}\t{{.MemUsage}}\t{{.MemPerc}}"
```

**Application memory**:
```bash
docker-compose exec app php -r "
echo 'Memory limit: ' . ini_get('memory_limit') . PHP_EOL;
echo 'Memory usage: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB' . PHP_EOL;
echo 'Peak usage: ' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB' . PHP_EOL;
"
```

---

## Alerting & Monitoring Setup

### External Monitoring (UptimeRobot, Pingdom)

**Configuration**:
- **URL**: `https://yourdomain.com/health`
- **Interval**: 5 minutes
- **Timeout**: 30 seconds
- **Alert on**: Non-200 response or response time > 5s

### Prometheus Integration

**Scrape configuration** (`prometheus.yml`):
```yaml
scrape_configs:
  - job_name: 'luminai'
    scrape_interval: 30s
    metrics_path: '/health/metrics'
    static_configs:
      - targets: ['localhost:443']
    scheme: https
    tls_config:
      insecure_skip_verify: true
```

### Grafana Dashboard

**Key Metrics to Track**:

1. **Application Health**
   - Health check status (OK/WARNING/ERROR)
   - Response time
   - Error rate

2. **Database**
   - Connection pool utilization
   - Query execution time
   - Database size growth

3. **Cache**
   - Redis memory usage
   - Cache hit rate
   - OPcache hit rate

4. **System Resources**
   - CPU usage
   - Memory usage
   - Disk space

5. **Performance**
   - Average response time
   - 95th percentile response time
   - Requests per second

### Log Aggregation (ELK Stack)

**Filebeat configuration**:
```yaml
filebeat.inputs:
  - type: log
    enabled: true
    paths:
      - /var/www/luminai/var/log/*.log
    json.keys_under_root: true
    json.add_error_key: true

output.elasticsearch:
  hosts: ["elasticsearch:9200"]
```

---

## Common Monitoring Queries

### Check System Health

```bash
# Overall health
curl -k https://localhost/health/detailed | jq -r .status

# Database status
curl -k https://localhost/health/detailed | jq .checks.database

# Redis status
curl -k https://localhost/health/detailed | jq .checks.redis

# Disk space
curl -k https://localhost/health/detailed | jq .checks.disk_space
```

### Check Performance

```bash
# Response times (from logs)
grep "request_completed" var/log/app.log | \
    jq -r .context.duration_ms | \
    awk '{sum+=$1; n++} END {print "Avg: " sum/n "ms"}'

# Slow requests (>500ms)
grep "request_completed" var/log/app.log | \
    jq 'select(.context.duration_ms > 500)'

# Error rate
total=$(grep -c "request_completed" var/log/app.log)
errors=$(grep -c '"level":"ERROR"' var/log/app.log)
echo "Error rate: $(echo "scale=2; $errors / $total * 100" | bc)%"
```

### Check Security

```bash
# Failed logins
grep "authentication failure" var/log/security.log | jq .

# Account lockouts
grep "account locked" var/log/security.log | jq .

# Suspicious activity
grep -E "sql.*injection|xss|script.*alert" var/log/security.log | jq .
```

---

## Automated Health Checks

### Cron Job for Health Monitoring

```bash
# Add to crontab
*/5 * * * * curl -k https://localhost/health/detailed > /tmp/health.json && \
    if [ $(cat /tmp/health.json | jq -r .status) != "OK" ]; then \
        echo "Health check failed" | mail -s "Luminai Health Alert" admin@example.com; \
    fi
```

### Monitoring Script

```bash
#!/bin/bash
# scripts/monitor-health.sh

HEALTH_URL="https://localhost/health/detailed"
ALERT_EMAIL="admin@example.com"

# Fetch health status
HEALTH=$(curl -k -s $HEALTH_URL)
STATUS=$(echo $HEALTH | jq -r .status)

if [ "$STATUS" != "OK" ]; then
    echo "ALERT: Health status is $STATUS" | mail -s "Luminai Health Alert" $ALERT_EMAIL
    echo $HEALTH | jq . | mail -s "Luminai Health Details" $ALERT_EMAIL
fi

# Check disk space
DISK_USAGE=$(echo $HEALTH | jq -r .checks.disk_space.used_percent)
if (( $(echo "$DISK_USAGE > 80" | bc -l) )); then
    echo "WARNING: Disk usage is ${DISK_USAGE}%" | mail -s "Luminai Disk Alert" $ALERT_EMAIL
fi

# Check error rate
ERROR_COUNT=$(grep -c '"level":"ERROR"' var/log/app.log)
if [ $ERROR_COUNT -gt 10 ]; then
    echo "WARNING: ${ERROR_COUNT} errors in log" | mail -s "Luminai Error Alert" $ALERT_EMAIL
fi
```

---

## Best Practices

### 1. Monitor Continuously

- Set up automated health checks every 5 minutes
- Monitor critical metrics in real-time
- Set up alerts for critical issues

### 2. Review Logs Daily

```bash
# Daily log review script
grep '"level":"ERROR"' var/log/app.log | tail -20 | jq .
grep '"level":"WARNING"' var/log/app.log | tail -20 | jq .
```

### 3. Track Key Metrics

- Response time < 200ms
- Error rate < 1%
- Cache hit rate > 90%
- Disk usage < 80%
- Memory usage < 80%

### 4. Investigate Anomalies

```bash
# Sudden spike in errors
grep '"level":"ERROR"' var/log/app.log | jq -r .datetime | sort | uniq -c

# Unusual traffic patterns
grep "request_completed" var/log/app.log | jq -r .datetime | cut -d: -f1-2 | sort | uniq -c
```

### 5. Archive Old Logs

```bash
# Rotate logs older than 7 days
find var/log/ -name "*.log" -mtime +7 -exec gzip {} \;

# Archive old compressed logs
find var/log/ -name "*.log.gz" -mtime +30 -exec mv {} /archive/ \;
```

---

For more information:
- [Troubleshooting Guide](TROUBLESHOOTING.md)
- [Docker Guide](DOCKER.md)
- [Production Deployment](ProductionDeployment.md)
