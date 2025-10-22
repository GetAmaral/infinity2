# Batch Operations Implementation - Quick Checklist

**For detailed information, see:** [BATCH_OPERATIONS_IMPLEMENTATION_PLAN.md](./BATCH_OPERATIONS_IMPLEMENTATION_PLAN.md)

---

## Phase 1: Database Schema (Week 1)

- [ ] Add 5 batch fields to `GeneratorEntity.php`
  - `batchOperationsEnabled` (bool)
  - `batchOperationTypes` (array)
  - `batchMaxItems` (int)
  - `batchTransactionStrategy` (string)
  - `batchValidationStrategy` (string)
- [ ] Add getter/setter methods + `isBatchOperationTypeEnabled()`
- [ ] Create migration: `php bin/console make:migration`
- [ ] Run migration: `php bin/console doctrine:migrations:migrate`

---

## Phase 2: Templates (Week 1-2)

Create in `app/templates/genmax/php/`:

- [ ] `batch_input_dto_generated.php.twig`
- [ ] `batch_input_dto_extension.php.twig`
- [ ] `batch_result_dto_generated.php.twig`
- [ ] `batch_result_dto_extension.php.twig`
- [ ] `batch_create_processor.php.twig`
- [ ] `batch_update_processor.php.twig`
- [ ] `batch_delete_processor.php.twig`

Update:
- [ ] `api_platform.yaml.twig` - Add batch operations section

---

## Phase 3: Configuration (Week 2)

Update `app/config/services.yaml`:

```yaml
genmax.templates:
  batch_input_dto_generated: 'genmax/php/batch_input_dto_generated.php.twig'
  batch_input_dto_extension: 'genmax/php/batch_input_dto_extension.php.twig'
  batch_result_dto_generated: 'genmax/php/batch_result_dto_generated.php.twig'
  batch_result_dto_extension: 'genmax/php/batch_result_dto_extension.php.twig'
  batch_create_processor: 'genmax/php/batch_create_processor.php.twig'
  batch_update_processor: 'genmax/php/batch_update_processor.php.twig'
  batch_delete_processor: 'genmax/php/batch_delete_processor.php.twig'
```

---

## Phase 4: Generator (Week 2)

- [ ] Create `app/src/Service/Genmax/BatchOperationGenerator.php`
- [ ] Implement all 7 generation methods
- [ ] Add to `services.yaml` (auto-wired)

---

## Phase 5: Orchestrator Integration (Week 2-3)

Update `GenmaxOrchestrator.php`:

- [ ] Add feature flag: `BATCH_OPERATIONS_ACTIVE = true`
- [ ] Inject `BatchOperationGenerator` in constructor
- [ ] Add batch generation in main loop (after State Processors)
- [ ] Update `countActiveGenerators()`
- [ ] Update `getActiveGenerators()`
- [ ] Update `collectFilesToBackup()`

---

## Phase 6: Testing (Week 3)

- [ ] Unit test: `BatchOperationGeneratorTest.php`
- [ ] Functional test: `ContactBatchOperationsTest.php`
  - [ ] testBatchCreateSuccess
  - [ ] testBatchCreateExceedsMaxItems
  - [ ] testBatchUpdatePartialFailure
  - [ ] testBatchDeleteAllOrNothing
- [ ] Performance test: 100 items
- [ ] Memory profiling

---

## Phase 7: Documentation (Week 3-4)

- [ ] Update Generator User Guide
- [ ] Create usage examples
- [ ] Update API documentation
- [ ] Create migration guide

---

## Phase 8: Rollout (Week 4)

- [ ] Enable for 1 pilot entity
- [ ] Test in production
- [ ] Monitor performance
- [ ] Fix any issues
- [ ] Enable globally

---

## Quick Test Commands

```bash
# 1. Enable batch operations for Contact entity (via API or database)
# 2. Generate code
php bin/console genmax:generate

# 3. Clear cache
php bin/console cache:clear

# 4. Test batch create
curl -X POST https://localhost/api/contacts/batch-create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {"name": "Test 1", "email": "test1@example.com"},
      {"name": "Test 2", "email": "test2@example.com"}
    ],
    "transactionMode": "all_or_nothing"
  }'

# 5. Verify response
# Expected: 201 with successCount: 2
```

---

## Expected Generated Files (per entity)

For `Contact` entity with batch enabled:

```
app/
├── config/api_platform/
│   └── Contact.yaml (UPDATED - adds 3 batch operations)
├── src/Dto/
│   ├── ContactBatchInputDto.php (NEW)
│   ├── ContactBatchResultDto.php (NEW)
│   └── Generated/
│       ├── ContactBatchInputDtoGenerated.php (NEW)
│       └── ContactBatchResultDtoGenerated.php (NEW)
└── src/State/
    ├── ContactBatchCreateProcessor.php (NEW)
    ├── ContactBatchUpdateProcessor.php (NEW)
    └── ContactBatchDeleteProcessor.php (NEW)
```

---

## Key Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| **Approach** | Bulk Collection Operations (Approach 2) | Simpler, clearer intent, easier validation |
| **Transaction Default** | all_or_nothing | Safer, prevents partial data corruption |
| **Max Batch Size** | 100 items | Balance between performance and safety |
| **Memory Management** | Clear Doctrine every 20 items | Prevent memory exhaustion |
| **Error Strategy** | collect_all | Better user experience, see all errors |
| **Feature Flag** | Optional per entity | Backward compatible, gradual rollout |

---

## Common Pitfalls

❌ **DON'T:**
- Forget to add batch files to backup collection
- Skip Doctrine clear() in processors (memory leak!)
- Allow unlimited batch size
- Mix entities in single batch request

✅ **DO:**
- Validate organization for every batch item
- Use transactions wisely (all_or_nothing vs partial)
- Clear Doctrine periodically
- Log batch operations for audit
- Test with max batch size

---

## Performance Targets

| Metric | Target | Notes |
|--------|--------|-------|
| 100 items batch | < 30 seconds | With validation |
| Memory usage | < 256 MB | With Doctrine clear() |
| Success rate | > 99% | For valid data |
| Error reporting | 100% accuracy | Per-item granularity |

---

## Security Checklist

- [ ] Multi-tenancy: All items validated for organization
- [ ] Authorization: Security voters check each item
- [ ] Rate limiting: Count each batch item
- [ ] Input validation: Symfony validation constraints
- [ ] SQL injection: Using Doctrine ORM (protected)
- [ ] CSRF: Not applicable (stateless API)

---

## Rollback Plan

If issues occur:

1. Set `BATCH_OPERATIONS_ACTIVE = false`
2. Run `php bin/console genmax:generate`
3. Clear cache
4. Batch endpoints return 404 (graceful)

---

## Next Steps After Implementation

1. **Monitor** batch operation usage and performance
2. **Gather feedback** from API consumers
3. **Optimize** based on real-world usage patterns
4. **Consider** async processing for large batches (Phase 2.7)
5. **Document** best practices and common patterns

---

## Support & Resources

- **Full Plan:** `/app/docs/Genmax/BATCH_OPERATIONS_IMPLEMENTATION_PLAN.md`
- **API Platform Docs:** https://api-platform.com/docs/
- **Symfony Docs:** https://symfony.com/doc/7.3/
- **Project Docs:** `/docs/`

---

**Implementation Time Estimate: 3-4 weeks**

**Complexity: Medium** ⭐⭐⭐☆☆

**Business Value: High** 💰💰💰💰💰

---

_Last Updated: October 22, 2025_
