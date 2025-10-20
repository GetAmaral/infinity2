# Luminai Database Backups

This directory contains PostgreSQL database backups for the Luminai CRM system.

---

## Backup Files

Backups are created in two formats:

1. **`.dump` files** - PostgreSQL custom format (compressed, production-ready)
2. **`.sql` files** - Plain SQL text (human-readable, version control friendly)
3. **`_INFO.md` files** - Backup metadata and restore instructions

---

## Quick Backup Command

```bash
# Create full backup (both formats)
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
docker-compose exec -T database pg_dump -U luminai_user -d luminai_db --format=custom --file=/tmp/backup_${TIMESTAMP}.dump
docker-compose exec -T database pg_dump -U luminai_user -d luminai_db --format=plain --file=/tmp/backup_${TIMESTAMP}.sql
docker cp luminai_database:/tmp/backup_${TIMESTAMP}.dump backups/
docker cp luminai_database:/tmp/backup_${TIMESTAMP}.sql backups/
```

---

## Quick Restore Command

### From Custom Format (.dump)
```bash
# Restore entire database
docker cp backups/backup_YYYYMMDD_HHMMSS.dump luminai_database:/tmp/
docker-compose exec -T database pg_restore -U luminai_user -d luminai_db --clean --if-exists /tmp/backup_YYYYMMDD_HHMMSS.dump

# Clear application cache after restore
docker-compose exec -T app php bin/console cache:clear
```

### From SQL Format (.sql)
```bash
# Restore entire database
docker cp backups/backup_YYYYMMDD_HHMMSS.sql luminai_database:/tmp/
docker-compose exec -T database psql -U luminai_user -d luminai_db -f /tmp/backup_YYYYMMDD_HHMMSS.sql

# Clear application cache after restore
docker-compose exec -T app php bin/console cache:clear
```

---

## Backup Strategy

### When to Create Backups

✅ **Always backup before:**
- Database schema changes
- Major entity modifications
- Bulk data operations
- Production deployments
- Genmax regeneration of all entities

✅ **Recommended schedule:**
- Daily: Automated backups (production)
- Manual: Before any risky operation (development)

---

## Backup Contents

Each backup includes:
- All 75 generator entities definitions
- All 1,245+ generator properties
- Organization data
- User accounts and profiles
- CRM data (contacts, companies, deals, pipelines, tasks)
- System configuration tables
- Audit logs

---

## Restore Scenarios

### 1. Full Database Restore
Use when you need to restore the entire database to a previous state.

### 2. Selective Table Restore
```bash
# Restore only specific tables
pg_restore -U luminai_user -d luminai_db -t generator_entity -t generator_property backup.dump
```

### 3. Inspection Only
```bash
# View backup contents without restoring
pg_restore --list backup.dump

# Extract specific table data
pg_restore -U luminai_user --data-only -t generator_entity backup.dump | less
```

---

## Backup Verification

Always verify backups after creation:

```bash
# Check backup file integrity
pg_restore --list backup.dump > /dev/null && echo "✅ Backup is valid"

# Compare table counts
docker-compose exec -T database psql -U luminai_user -d luminai_db -c "SELECT 'generator_entity', COUNT(*) FROM generator_entity"
```

---

## Storage Recommendations

### Local Development
- Keep last 5-10 backups
- Store in `/home/user/inf/backups/`
- Add to `.gitignore` (already configured)

### Production
- Keep daily backups for 30 days
- Keep weekly backups for 3 months
- Keep monthly backups for 1 year
- Store offsite (S3, Google Cloud, etc.)

---

## Cleanup Old Backups

```bash
# Remove backups older than 30 days
find /home/user/inf/backups -name "backup_*.dump" -mtime +30 -delete
find /home/user/inf/backups -name "backup_*.sql" -mtime +30 -delete
```

---

## Emergency Recovery

If you need to recover from a corrupted database:

1. **Stop the application**:
   ```bash
   docker-compose down
   ```

2. **Drop and recreate database**:
   ```bash
   docker-compose up -d database
   docker-compose exec -T database psql -U luminai_user -d postgres -c "DROP DATABASE luminai_db;"
   docker-compose exec -T database psql -U luminai_user -d postgres -c "CREATE DATABASE luminai_db;"
   ```

3. **Restore from backup**:
   ```bash
   docker cp backups/backup_LATEST.dump luminai_database:/tmp/
   docker-compose exec -T database pg_restore -U luminai_user -d luminai_db /tmp/backup_LATEST.dump
   ```

4. **Start application**:
   ```bash
   docker-compose up -d
   docker-compose exec -T app php bin/console cache:clear
   ```

---

## Troubleshooting

### "role does not exist"
Use correct credentials: `-U luminai_user` (not `-U luminai`)

### "database does not exist"
Use correct database name: `-d luminai_db` (not `-d luminai_crm`)

### "permission denied"
Ensure backup files are readable:
```bash
chmod 644 backups/*.dump backups/*.sql
```

### "disk full"
Clean old backups or increase disk space

---

## Related Documentation

- Main Docs: `/home/user/inf/CLAUDE.md`
- Database Guide: `/home/user/inf/docs/DATABASE.md`
- Quick Start: `/home/user/inf/docs/QUICK_START.md`

---

**Last Updated**: 2025-10-20
**Database**: luminai_db (PostgreSQL 18)
**System**: Luminai CRM v1.0
