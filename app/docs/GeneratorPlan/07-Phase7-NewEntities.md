# Phase 7: Bulk Generation (Weeks 9-10)

## Overview

Phase 7 generates all 50+ new entities in the system, performing comprehensive testing and validation.

**Duration:** Weeks 9-10 (10 working days)

**Deliverables:**
- ✅ All 66 entities generated
- ✅ 1,100+ files generated
- ✅ Full test suite passing
- ✅ Performance validated
- ✅ Database migrations created

---

## Week 9: Batch Generation

### Day 1: Pre-Generation Checklist

**Verification Steps:**
1. ✅ CSV files validated
2. ✅ Backup system tested
3. ✅ All generators working
4. ✅ Test environment prepared
5. ✅ Git repository clean

**Environment Setup:**
```bash
# Clear cache
php bin/console cache:clear

# Drop and recreate database
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create

# Verify disk space
df -h /home/user/inf
```

---

### Day 2-3: Generate Core Entities (20 entities)

**Core Entities:**
- Organization
- User
- Role
- Module

**CRM Entities:**
- Contact
- Company
- Deal
- Lead
- Campaign
- Task
- Activity
- Note
- Email
- Phone
- Address

**Generate:**
```bash
php bin/console app:generate-from-csv
```

**Expected Output:**
- 20 entities × 17 files = **340 files**
- Generation time: ~5 minutes

**Validation:**
```bash
# Run migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Run tests
php bin/phpunit

# Check generated files
find src/Entity -name "*Generated.php" | wc -l  # Should be 20
```

---

### Day 4-5: Generate Remaining Entities (46 entities)

**Remaining Entities:**
- Product, Service, Invoice, Payment, Subscription
- Document, Attachment, Comment, Tag
- Pipeline, Stage, Workflow, Automation
- Report, Dashboard, Chart, Metric
- Notification, Alert, Reminder
- Calendar, Event, Meeting, Appointment
- Project, Milestone, Deliverable
- Team, Department, Territory
- Goal, Target, KPI
- Integration, Webhook, API Token
- Audit Log, Change History

**Generate in Batches:**
```bash
# Batch 1: Products & Services (10 entities)
php bin/console app:generate-from-csv

# Verify batch
php bin/phpunit --testsuite=entity

# Batch 2: Documents & Communication (10 entities)
php bin/console app:generate-from-csv

# Verify batch
php bin/phpunit --testsuite=entity

# Batch 3: Workflows & Reporting (10 entities)
php bin/console app:generate-from-csv

# Verify batch
php bin/phpunit --testsuite=entity

# Batch 4: Projects & Teams (8 entities)
php bin/console app:generate-from-csv

# Verify batch
php bin/phpunit --testsuite=entity

# Batch 5: Integrations & Audit (8 entities)
php bin/console app:generate-from-csv

# Final verification
php bin/phpunit
```

**Total Generated:**
- 66 entities × 17 files = **1,122 files**
- Generation time: ~20 minutes

---

## Week 10: Testing & Validation

### Day 1-2: Comprehensive Testing

**Test Execution:**
```bash
# Unit tests
php bin/phpunit tests/Entity/
php bin/phpunit tests/Repository/

# Functional tests
php bin/phpunit tests/Controller/

# Security tests
php bin/phpunit tests/Security/Voter/

# Integration tests
php bin/phpunit tests/Integration/

# Generate coverage report
php bin/phpunit --coverage-html coverage/
```

**Success Criteria:**
- [ ] All unit tests pass (80%+ coverage)
- [ ] All functional tests pass
- [ ] All security tests pass
- [ ] No PHPStan errors
- [ ] No deprecation warnings

---

### Day 3: Performance Testing

**Performance Validation:**

1. **Generation Performance**
   ```bash
   time php bin/console app:generate-from-csv
   ```
   - **Target:** < 2 minutes for all 66 entities

2. **Database Performance**
   ```bash
   php bin/console doctrine:query:sql "ANALYZE"
   php bin/console doctrine:query:sql "
     SELECT schemaname, tablename, n_live_tup
     FROM pg_stat_user_tables
     ORDER BY n_live_tup DESC
   "
   ```
   - **Target:** All tables created correctly

3. **Repository Performance**
   ```bash
   php bin/phpunit tests/Repository/ --testdox
   ```
   - **Target:** All searches complete in < 100ms

4. **API Performance**
   ```bash
   ab -n 1000 -c 10 https://localhost/api/contacts
   ```
   - **Target:** > 100 requests/second

---

### Day 4: Manual Testing

**Manual Test Checklist:**

For each entity type (sample 5 entities):

**CRUD Operations:**
- [ ] Create entity via form
- [ ] View entity in list
- [ ] View entity detail
- [ ] Edit entity
- [ ] Delete entity
- [ ] Verify flash messages
- [ ] Check Turbo Drive behavior

**Search & Filter:**
- [ ] Search by text
- [ ] Filter by field
- [ ] Sort by column
- [ ] Pagination works

**Security:**
- [ ] Voter prevents unauthorized access
- [ ] Organization isolation works
- [ ] ROLE_USER can view
- [ ] ROLE_MANAGER can create/edit
- [ ] ROLE_ADMIN can delete

**API:**
- [ ] GET /api/{entities} returns list
- [ ] GET /api/{entities}/{id} returns single
- [ ] POST /api/{entities} creates
- [ ] PUT /api/{entities}/{id} updates
- [ ] DELETE /api/{entities}/{id} deletes

---

### Day 5: Issue Resolution

**Common Issues & Fixes:**

**Issue 1: Migration Conflicts**
```bash
# Resolution
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:migrate --no-interaction
```

**Issue 2: Circular References**
```bash
# Check entity relationships
grep -r "targetEntity" src/Entity/Generated/

# Fix in Property.csv: Set proper inversedBy/mappedBy
```

**Issue 3: Test Failures**
```bash
# Debug specific test
php bin/phpunit --filter ContactControllerTest --debug

# Fix in test Generator template
```

**Issue 4: Performance Issues**
```bash
# Add indexes to slow queries
# Update indexes column in Entity.csv
```

---

## Statistics Summary

**Generated Files:**
| Category | Count per Entity | Total (66 entities) |
|----------|------------------|---------------------|
| Entity (Generated + Extension) | 2 | 132 |
| Repository (Generated + Extension) | 2 | 132 |
| Controller (Generated + Extension) | 2 | 132 |
| Voter (Generated + Extension) | 2 | 132 |
| Form (Generated + Extension) | 2 | 132 |
| Templates | 6 | 396 |
| Tests | 4 | 264 |
| **TOTAL** | **17** | **1,122** |

**Plus:**
- 1 OrganizationTrait
- 66 API Platform YAML configs
- 1 base.html.twig (updated navigation)
- 1 messages.en.yaml (updated translations)

**Grand Total:** **1,191 files**

---

## Performance Metrics

**Generation Performance:**
- CSV parsing: ~5 seconds
- Code generation: ~90 seconds
- Total: < 2 minutes ✅

**Runtime Performance:**
- Entity hydration: < 5ms average
- Repository search: < 50ms average
- Controller response: < 200ms average
- API response: < 150ms average

**Test Performance:**
- Unit tests: ~2 minutes
- Functional tests: ~5 minutes
- Total test suite: < 10 minutes ✅

---

## Phase 7 Deliverables Checklist

- [ ] All 66 entities generated
- [ ] 1,191 files created
- [ ] All migrations executed
- [ ] All tests passing (80%+ coverage)
- [ ] PHPStan level 8 passes
- [ ] Performance targets met
- [ ] Manual testing completed
- [ ] All issues resolved
- [ ] Documentation updated

---

## Next Phase

**Phase 8: Polish & Documentation** (Week 11)
- Code quality review
- Documentation finalization
- Optimization
- Deployment preparation
