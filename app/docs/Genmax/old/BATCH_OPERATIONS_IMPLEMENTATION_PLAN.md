# Genmax Batch Operations Implementation Plan

**Version:** 1.0
**Date:** October 22, 2025
**Approach:** Bulk Collection Operations (Approach 2)
**Status:** Planning Phase

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Goals & Requirements](#2-goals--requirements)
3. [Architecture Overview](#3-architecture-overview)
4. [Implementation Phases](#4-implementation-phases)
5. [Detailed Implementation Steps](#5-detailed-implementation-steps)
6. [Code Examples](#6-code-examples)
7. [Testing Strategy](#7-testing-strategy)
8. [Migration & Rollout](#8-migration--rollout)
9. [Future Enhancements](#9-future-enhancements)

---

## 1. Executive Summary

### What We're Building

Add **automatic batch/bulk operation generation** to genmax that creates API endpoints for bulk CREATE, UPDATE, and DELETE operations on any entity with a single request.

### Why Approach 2 (Bulk Collection Operations)

- ✅ **Simpler** than general batch endpoint (Approach 1)
- ✅ **More structured** than array-based POST (Approach 3)
- ✅ **Clear intent** - dedicated endpoints for batch operations
- ✅ **Easier validation** - consistent entity type per request
- ✅ **Better error reporting** - per-item success/failure tracking
- ✅ **RESTful** - follows resource-oriented design

### Expected Result

```json
POST /api/contacts/batch-create
{
  "items": [
    { "name": "John Doe", "email": "john@example.com" },
    { "name": "Jane Smith", "email": "jane@example.com" }
  ],
  "transactionMode": "all_or_nothing"
}

Response 201:
{
  "successCount": 2,
  "errorCount": 0,
  "totalProcessed": 2,
  "results": [
    {
      "index": 0,
      "status": "success",
      "id": "01932f45-6789-7abc-9def-0123456789ab",
      "iri": "/api/contacts/01932f45-6789-7abc-9def-0123456789ab"
    },
    {
      "index": 1,
      "status": "success",
      "id": "01932f45-789a-7bcd-9ef0-123456789abc",
      "iri": "/api/contacts/01932f45-789a-7bcd-9ef0-123456789abc"
    }
  ]
}
```

---

## 2. Goals & Requirements

### Primary Goals

1. **Auto-generate batch endpoints** for entities with `batchOperationsEnabled = true`
2. **Support three operation types**: batch-create, batch-update, batch-delete
3. **Maintain data integrity** with configurable transaction strategies
4. **Provide detailed error reporting** for partial failures
5. **Preserve multi-tenancy** security and organization isolation
6. **Keep it optional** - feature flag controlled, backward compatible

### Non-Functional Requirements

| Requirement | Target | Notes |
|-------------|--------|-------|
| **Max batch size** | 100 items (configurable) | Prevent memory issues |
| **Response time** | < 30s for 100 items | Use chunking if needed |
| **Memory usage** | < 256MB per batch | Doctrine clear() strategy |
| **Error handling** | Per-item granularity | Return index, error details |
| **Transaction safety** | ACID compliance | All-or-nothing or partial |
| **Multi-tenancy** | 100% isolation | Validate all items belong to org |

### Business Requirements

- **Import scenarios**: Bulk user import, contact lists, product catalogs
- **Data migration**: Moving data between systems
- **Bulk updates**: Price changes, status updates across records
- **Bulk deletes**: Cleanup operations, batch archival

---

## 3. Architecture Overview

### System Components

```
┌─────────────────────────────────────────────────────────────────┐
│                     GENMAX ORCHESTRATOR                          │
│  - Coordinates all generators                                    │
│  - Controls feature flags (BATCH_OPERATIONS_ACTIVE)              │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ├── EntityGenerator (existing)
                       ├── ApiGenerator (existing)
                       ├── DtoGenerator (existing)
                       ├── StateProcessorGenerator (existing)
                       │
                       └── BatchOperationGenerator (NEW) ◄────────┐
                                    │                              │
                    ┌───────────────┼───────────────┐             │
                    ▼               ▼               ▼             │
          ┌─────────────┐  ┌──────────────┐  ┌────────────┐      │
          │ Batch DTO   │  │   Batch      │  │  API Ops   │      │
          │  Templates  │  │  Processor   │  │   YAML     │      │
          │             │  │  Templates   │  │  Templates │      │
          └─────────────┘  └──────────────┘  └────────────┘      │
                                                                  │
┌─────────────────────────────────────────────────────────────────┤
│                   GENERATOR ENTITY (Database)                    │
│  Configuration stored per entity:                                │
│  - batchOperationsEnabled: bool                                  │
│  - batchOperationTypes: ['create', 'update', 'delete']           │
│  - batchMaxItems: int (default 100)                              │
│  - batchTransactionStrategy: 'all_or_nothing' | 'partial'        │
│  - batchValidationStrategy: 'fail_fast' | 'collect_all'          │
└──────────────────────────────────────────────────────────────────┘
```

### Generated Files Per Entity

For entity `Contact` with batch operations enabled:

```
app/
├── config/api_platform/
│   └── Contact.yaml                        (MODIFIED - adds batch operations)
│
├── src/Dto/
│   ├── ContactBatchInputDto.php            (NEW - extends array of items)
│   ├── ContactBatchResultDto.php           (NEW - success/error reporting)
│   └── Generated/
│       ├── ContactBatchInputDtoGenerated.php   (NEW)
│       └── ContactBatchResultDtoGenerated.php  (NEW)
│
└── src/State/
    ├── ContactBatchCreateProcessor.php     (NEW - handles batch creation)
    ├── ContactBatchUpdateProcessor.php     (NEW - handles batch updates)
    └── ContactBatchDeleteProcessor.php     (NEW - handles batch deletion)
```

### Data Flow

```
HTTP Request
    │
    ▼
POST /api/contacts/batch-create
{
  "items": [...],
  "transactionMode": "all_or_nothing"
}
    │
    ▼
API Platform
    │
    ▼
ContactBatchInputDto (deserialization)
    │
    ▼
ContactBatchCreateProcessor::process()
    │
    ├─► Validate batch size
    ├─► Start transaction (if all_or_nothing)
    ├─► For each item:
    │   ├─► Validate item
    │   ├─► Check security (organization)
    │   ├─► Create entity via ContactProcessor
    │   ├─► Collect result (success/error)
    │   └─► Doctrine clear every 20 items (memory)
    ├─► Commit/Rollback transaction
    └─► Return ContactBatchResultDto
    │
    ▼
HTTP Response 201/207
{
  "successCount": X,
  "errorCount": Y,
  "results": [...]
}
```

---

## 4. Implementation Phases

### Phase 1: Database Schema & Configuration (Week 1)

- [ ] Add batch operation fields to GeneratorEntity
- [ ] Create migration
- [ ] Update GeneratorEntity validation
- [ ] Update API Platform config for GeneratorEntity
- [ ] Test entity configuration via API

**Deliverable:** Can configure batch operations via admin UI

---

### Phase 2: Template Creation (Week 1-2)

- [ ] Create `batch_input_dto_generated.php.twig`
- [ ] Create `batch_input_dto_extension.php.twig`
- [ ] Create `batch_result_dto_generated.php.twig`
- [ ] Create `batch_result_dto_extension.php.twig`
- [ ] Create `batch_create_processor.php.twig`
- [ ] Create `batch_update_processor.php.twig`
- [ ] Create `batch_delete_processor.php.twig`
- [ ] Update `api_platform.yaml.twig` to include batch operations

**Deliverable:** All templates ready for generation

---

### Phase 3: Generator Implementation (Week 2)

- [ ] Create `BatchOperationGenerator.php`
- [ ] Implement batch DTO generation
- [ ] Implement batch processor generation
- [ ] Implement API operation configuration
- [ ] Add to services.yaml with autowiring
- [ ] Inject into GenmaxOrchestrator

**Deliverable:** BatchOperationGenerator service working

---

### Phase 4: Orchestrator Integration (Week 2-3)

- [ ] Add `BATCH_OPERATIONS_ACTIVE` feature flag
- [ ] Add generator to orchestrator constructor
- [ ] Add generation logic to main loop
- [ ] Add backup file collection for batch files
- [ ] Update progress tracking
- [ ] Add logging for batch generation

**Deliverable:** Orchestrator can generate batch operations

---

### Phase 5: Testing & Validation (Week 3)

- [ ] Unit tests for BatchOperationGenerator
- [ ] Functional tests for batch-create endpoint
- [ ] Functional tests for batch-update endpoint
- [ ] Functional tests for batch-delete endpoint
- [ ] Test error scenarios (validation, security, partial failure)
- [ ] Test transaction strategies
- [ ] Performance test with 100 items
- [ ] Memory profiling

**Deliverable:** Full test coverage

---

### Phase 6: Documentation & Examples (Week 3-4)

- [ ] Update Generator User Guide
- [ ] Create batch operations examples
- [ ] Update API documentation
- [ ] Create migration guide
- [ ] Video tutorial (optional)

**Deliverable:** Complete documentation

---

### Phase 7: Production Rollout (Week 4)

- [ ] Enable for 1-2 pilot entities
- [ ] Monitor performance
- [ ] Gather feedback
- [ ] Fix issues
- [ ] Enable globally

**Deliverable:** Production-ready feature

---

## 5. Detailed Implementation Steps

### Step 1: Add Batch Configuration to GeneratorEntity

**File:** `app/src/Entity/Generator/GeneratorEntity.php`

**Add after existing API configuration fields (around line 148):**

```php
// ====================================
// BATCH OPERATIONS CONFIGURATION (5 fields)
// ====================================

#[ORM\Column(options: ['default' => false])]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
private bool $batchOperationsEnabled = false;

#[ORM\Column(type: 'json', nullable: true)]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
#[Assert\Choice(
    choices: ['create', 'update', 'delete'],
    multiple: true,
    message: 'Invalid batch operation type'
)]
private ?array $batchOperationTypes = null;  // ['create', 'update', 'delete']

#[ORM\Column(type: 'integer', options: ['default' => 100])]
#[Assert\Range(min: 1, max: 1000)]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
private int $batchMaxItems = 100;

#[ORM\Column(type: 'string', length: 20, options: ['default' => 'all_or_nothing'])]
#[Assert\Choice(
    choices: ['all_or_nothing', 'partial'],
    message: 'Transaction strategy must be all_or_nothing or partial'
)]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
private string $batchTransactionStrategy = 'all_or_nothing';

#[ORM\Column(type: 'string', length: 20, options: ['default' => 'collect_all'])]
#[Assert\Choice(
    choices: ['fail_fast', 'collect_all'],
    message: 'Validation strategy must be fail_fast or collect_all'
)]
#[Groups(['generator_entity:read', 'generator_entity:write'])]
private string $batchValidationStrategy = 'collect_all';
```

**Add getter/setter methods:**

```php
public function isBatchOperationsEnabled(): bool
{
    return $this->batchOperationsEnabled;
}

public function setBatchOperationsEnabled(bool $batchOperationsEnabled): self
{
    $this->batchOperationsEnabled = $batchOperationsEnabled;
    return $this;
}

public function getBatchOperationTypes(): ?array
{
    return $this->batchOperationTypes;
}

public function setBatchOperationTypes(?array $batchOperationTypes): self
{
    $this->batchOperationTypes = $batchOperationTypes;
    return $this;
}

public function getBatchMaxItems(): int
{
    return $this->batchMaxItems;
}

public function setBatchMaxItems(int $batchMaxItems): self
{
    $this->batchMaxItems = $batchMaxItems;
    return $this;
}

public function getBatchTransactionStrategy(): string
{
    return $this->batchTransactionStrategy;
}

public function setBatchTransactionStrategy(string $batchTransactionStrategy): self
{
    $this->batchTransactionStrategy = $batchTransactionStrategy;
    return $this;
}

public function getBatchValidationStrategy(): string
{
    return $this->batchValidationStrategy;
}

public function setBatchValidationStrategy(string $batchValidationStrategy): self
{
    $this->batchValidationStrategy = $batchValidationStrategy;
    return $this;
}

/**
 * Helper: Check if specific batch operation type is enabled
 */
public function isBatchOperationTypeEnabled(string $type): bool
{
    if (!$this->batchOperationsEnabled) {
        return false;
    }

    return in_array($type, $this->batchOperationTypes ?? [], true);
}
```

---

### Step 2: Create Database Migration

**Command:**
```bash
php bin/console make:migration
```

**Verify migration adds:**
- `batch_operations_enabled` BOOLEAN DEFAULT false
- `batch_operation_types` JSON NULL
- `batch_max_items` INTEGER DEFAULT 100
- `batch_transaction_strategy` VARCHAR(20) DEFAULT 'all_or_nothing'
- `batch_validation_strategy` VARCHAR(20) DEFAULT 'collect_all'

**Run migration:**
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

---

### Step 3: Update Configuration Files

**File:** `app/config/services.yaml`

**Add genmax batch templates configuration:**

```yaml
parameters:
    # ... existing parameters ...

    genmax.templates:
        # ... existing templates ...

        # Batch Operation Templates (NEW)
        batch_input_dto_generated: 'genmax/php/batch_input_dto_generated.php.twig'
        batch_input_dto_extension: 'genmax/php/batch_input_dto_extension.php.twig'
        batch_result_dto_generated: 'genmax/php/batch_result_dto_generated.php.twig'
        batch_result_dto_extension: 'genmax/php/batch_result_dto_extension.php.twig'
        batch_create_processor: 'genmax/php/batch_create_processor.php.twig'
        batch_update_processor: 'genmax/php/batch_update_processor.php.twig'
        batch_delete_processor: 'genmax/php/batch_delete_processor.php.twig'
```

---

### Step 4: Create Batch DTO Templates

**File:** `app/templates/genmax/php/batch_input_dto_generated.php.twig`

```twig
<?php

declare(strict_types=1);

namespace {{ generated_namespace }};

use Symfony\Component\Validator\Constraints as Assert;

/**
 * {{ entity.getEntityLabel() }} Batch Input DTO (Generated)
 *
 * Container for batch operations containing multiple items and configuration.
 *
 * This file is ALWAYS regenerated. DO NOT edit.
 * For customizations, edit the extension class.
 *
 * @generated by Genmax Code Generator
 */
abstract class {{ entity.getEntityName() }}BatchInputDtoGenerated
{
    /**
     * Array of items to process in batch
     *
     * @var array<{{ dto_namespace }}\{{ entity.getEntityName() }}InputDto>
     */
    #[Assert\NotBlank(message: 'Items array is required')]
    #[Assert\Count(
        min: 1,
        max: {{ entity.getBatchMaxItems() }},
        minMessage: 'At least one item is required',
        maxMessage: 'Maximum {{ entity.getBatchMaxItems() }} items allowed per batch'
    )]
    #[Assert\Valid]
    public array $items = [];

    /**
     * Transaction mode for batch operation
     *
     * - all_or_nothing: Rollback all if any item fails
     * - partial: Commit successful items, report failures
     */
    #[Assert\Choice(
        choices: ['all_or_nothing', 'partial'],
        message: 'Transaction mode must be all_or_nothing or partial'
    )]
    public string $transactionMode = '{{ entity.getBatchTransactionStrategy() }}';

    /**
     * Continue processing on validation errors
     */
    public bool $continueOnError = {{ entity.getBatchValidationStrategy() == 'collect_all' ? 'true' : 'false' }};
}
```

**File:** `app/templates/genmax/php/batch_input_dto_extension.php.twig`

```twig
<?php

declare(strict_types=1);

namespace {{ namespace }};

use {{ generated_namespace }}\{{ entity.getEntityName() }}BatchInputDtoGenerated;

/**
 * {{ entity.getEntityLabel() }} Batch Input DTO
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom validation, methods, and business logic here.
 *
 * @generated once by Genmax Code Generator
 */
class {{ entity.getEntityName() }}BatchInputDto extends {{ entity.getEntityName() }}BatchInputDtoGenerated
{
    // Add custom properties here

    // Add custom methods here
}
```

---

### Step 5: Create Batch Result DTO Templates

**File:** `app/templates/genmax/php/batch_result_dto_generated.php.twig`

```twig
<?php

declare(strict_types=1);

namespace {{ generated_namespace }};

/**
 * {{ entity.getEntityLabel() }} Batch Result DTO (Generated)
 *
 * Contains results of batch operation with per-item success/error tracking.
 *
 * This file is ALWAYS regenerated. DO NOT edit.
 *
 * @generated by Genmax Code Generator
 */
abstract class {{ entity.getEntityName() }}BatchResultDtoGenerated
{
    /**
     * Number of successfully processed items
     */
    public int $successCount = 0;

    /**
     * Number of items that failed
     */
    public int $errorCount = 0;

    /**
     * Total items processed
     */
    public int $totalProcessed = 0;

    /**
     * Detailed results for each item
     *
     * @var array<array{
     *     index: int,
     *     status: 'success'|'error',
     *     id?: string,
     *     iri?: string,
     *     errors?: array<string>
     * }>
     */
    public array $results = [];

    /**
     * Overall operation status
     */
    public string $status = 'completed';  // 'completed', 'partial', 'failed'

    /**
     * Transaction mode used
     */
    public string $transactionMode;

    public function __construct(string $transactionMode = 'all_or_nothing')
    {
        $this->transactionMode = $transactionMode;
    }

    /**
     * Add successful result
     */
    public function addSuccess(int $index, string $id, string $iri): void
    {
        $this->results[] = [
            'index' => $index,
            'status' => 'success',
            'id' => $id,
            'iri' => $iri,
        ];
        $this->successCount++;
        $this->totalProcessed++;
    }

    /**
     * Add error result
     */
    public function addError(int $index, array $errors): void
    {
        $this->results[] = [
            'index' => $index,
            'status' => 'error',
            'errors' => $errors,
        ];
        $this->errorCount++;
        $this->totalProcessed++;
    }

    /**
     * Finalize and set overall status
     */
    public function finalize(): void
    {
        if ($this->errorCount === 0) {
            $this->status = 'completed';
        } elseif ($this->successCount === 0) {
            $this->status = 'failed';
        } else {
            $this->status = 'partial';
        }
    }
}
```

**File:** `app/templates/genmax/php/batch_result_dto_extension.php.twig`

```twig
<?php

declare(strict_types=1);

namespace {{ namespace }};

use {{ generated_namespace }}\{{ entity.getEntityName() }}BatchResultDtoGenerated;

/**
 * {{ entity.getEntityLabel() }} Batch Result DTO
 *
 * This class extends the generated base and is SAFE TO EDIT.
 *
 * @generated once by Genmax Code Generator
 */
class {{ entity.getEntityName() }}BatchResultDto extends {{ entity.getEntityName() }}BatchResultDtoGenerated
{
    // Add custom properties here

    // Add custom methods here
}
```

---

### Step 6: Create Batch Create Processor Template

**File:** `app/templates/genmax/php/batch_create_processor.php.twig`

```twig
<?php

declare(strict_types=1);

namespace {{ namespace }};

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use {{ entity_namespace }}\{{ entity.getEntityName() }};
use {{ dto_namespace }}\{{ entity.getEntityName() }}BatchInputDto;
use {{ dto_namespace }}\{{ entity.getEntityName() }}BatchResultDto;
use {{ dto_namespace }}\{{ entity.getEntityName() }}InputDto;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * {{ entity.getEntityLabel() }} Batch Create Processor
 *
 * Handles bulk creation of {{ entity.getEntityLabel() }} entities.
 *
 * This file is ALWAYS regenerated. DO NOT edit.
 * For custom processing logic, use Event Subscribers.
 *
 * @generated by Genmax Code Generator
 */
class {{ entity.getEntityName() }}BatchCreateProcessor implements ProcessorInterface
{
    private const BATCH_CLEAR_THRESHOLD = 20;  // Clear Doctrine every N items

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'App\State\{{ entity.getEntityName() }}Processor')]
        private readonly ProcessorInterface $itemProcessor,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param {{ entity.getEntityName() }}BatchInputDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): {{ entity.getEntityName() }}BatchResultDto
    {
        if (!$data instanceof {{ entity.getEntityName() }}BatchInputDto) {
            throw new BadRequestHttpException('Invalid batch input data type');
        }

        $this->logger->info('[BATCH] Starting batch create for {{ entity.getEntityName() }}', [
            'item_count' => count($data->items),
            'transaction_mode' => $data->transactionMode
        ]);

        $result = new {{ entity.getEntityName() }}BatchResultDto($data->transactionMode);

        // Validate batch size
        if (count($data->items) > {{ entity.getBatchMaxItems() }}) {
            throw new BadRequestHttpException(
                'Batch size exceeds maximum of {{ entity.getBatchMaxItems() }} items'
            );
        }

        // Start transaction for all_or_nothing mode
        $useTransaction = $data->transactionMode === 'all_or_nothing';
        if ($useTransaction) {
            $this->entityManager->beginTransaction();
        }

        try {
            $processedCount = 0;

            foreach ($data->items as $index => $itemDto) {
                try {
                    // Validate item
                    if (!$itemDto instanceof {{ entity.getEntityName() }}InputDto) {
                        throw new \InvalidArgumentException('Invalid item type at index ' . $index);
                    }

                    $violations = $this->validator->validate($itemDto);
                    if (count($violations) > 0) {
                        $errors = [];
                        foreach ($violations as $violation) {
                            $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                        }
                        throw new \InvalidArgumentException(implode(', ', $errors));
                    }

                    // Process item using standard processor
                    $entity = $this->itemProcessor->process($itemDto, $operation, [], $context);

                    // Record success
                    $result->addSuccess(
                        $index,
                        $entity->getId()->toString(),
                        '/api/{{ entity.getEntityName()|lower }}s/' . $entity->getId()->toString()
                    );

                    $processedCount++;

                    // Clear Doctrine periodically to prevent memory issues
                    if ($processedCount % self::BATCH_CLEAR_THRESHOLD === 0) {
                        $this->entityManager->flush();
                        $this->entityManager->clear();
                        $this->logger->debug('[BATCH] Cleared Doctrine after {count} items', [
                            'count' => $processedCount
                        ]);
                    }

                } catch (\Throwable $e) {
                    $this->logger->warning('[BATCH] Item processing failed', [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ]);

                    // Record error
                    $result->addError($index, [$e->getMessage()]);

                    // Handle error based on strategy
                    if ($useTransaction) {
                        // all_or_nothing: rollback and fail entire batch
                        throw $e;
                    }

                    if (!$data->continueOnError) {
                        // fail_fast: stop processing
                        break;
                    }

                    // collect_all: continue to next item
                }
            }

            // Final flush for remaining items
            $this->entityManager->flush();

            // Commit transaction if all succeeded
            if ($useTransaction) {
                $this->entityManager->commit();
            }

            $result->finalize();

            $this->logger->info('[BATCH] Batch create completed', [
                'success' => $result->successCount,
                'errors' => $result->errorCount,
                'status' => $result->status
            ]);

            return $result;

        } catch (\Throwable $e) {
            if ($useTransaction && $this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
                $this->logger->error('[BATCH] Transaction rolled back', [
                    'error' => $e->getMessage()
                ]);
            }

            // If all_or_nothing and any error, return all as failed
            if ($useTransaction) {
                throw new BadRequestHttpException(
                    'Batch operation failed (all_or_nothing mode): ' . $e->getMessage()
                );
            }

            $result->finalize();
            return $result;
        }
    }
}
```

---

### Step 7: Create Batch Update Processor Template

**File:** `app/templates/genmax/php/batch_update_processor.php.twig`

Similar structure to batch create, but:
- Expects items to have `id` or `@id` field
- Uses PATCH operation context
- Loads existing entities before update

```twig
<?php

declare(strict_types=1);

namespace {{ namespace }};

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use {{ entity_namespace }}\{{ entity.getEntityName() }};
use {{ dto_namespace }}\{{ entity.getEntityName() }}BatchInputDto;
use {{ dto_namespace }}\{{ entity.getEntityName() }}BatchResultDto;
use {{ dto_namespace }}\{{ entity.getEntityName() }}InputDto;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * {{ entity.getEntityLabel() }} Batch Update Processor
 *
 * Handles bulk updates of {{ entity.getEntityLabel() }} entities.
 *
 * This file is ALWAYS regenerated. DO NOT edit.
 *
 * @generated by Genmax Code Generator
 */
class {{ entity.getEntityName() }}BatchUpdateProcessor implements ProcessorInterface
{
    private const BATCH_CLEAR_THRESHOLD = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'App\State\{{ entity.getEntityName() }}Processor')]
        private readonly ProcessorInterface $itemProcessor,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param {{ entity.getEntityName() }}BatchInputDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): {{ entity.getEntityName() }}BatchResultDto
    {
        if (!$data instanceof {{ entity.getEntityName() }}BatchInputDto) {
            throw new BadRequestHttpException('Invalid batch input data type');
        }

        $this->logger->info('[BATCH] Starting batch update for {{ entity.getEntityName() }}', [
            'item_count' => count($data->items),
            'transaction_mode' => $data->transactionMode
        ]);

        $result = new {{ entity.getEntityName() }}BatchResultDto($data->transactionMode);

        if (count($data->items) > {{ entity.getBatchMaxItems() }}) {
            throw new BadRequestHttpException(
                'Batch size exceeds maximum of {{ entity.getBatchMaxItems() }} items'
            );
        }

        $useTransaction = $data->transactionMode === 'all_or_nothing';
        if ($useTransaction) {
            $this->entityManager->beginTransaction();
        }

        try {
            $processedCount = 0;

            foreach ($data->items as $index => $itemData) {
                try {
                    // Extract ID from item
                    $itemId = $itemData['id'] ?? $itemData['@id'] ?? null;
                    if (!$itemId) {
                        throw new \InvalidArgumentException('Item at index ' . $index . ' missing id field');
                    }

                    // Handle IRI format
                    if (is_string($itemId) && str_starts_with($itemId, '/api/')) {
                        $parts = explode('/', $itemId);
                        $itemId = end($parts);
                    }

                    if (!Uuid::isValid($itemId)) {
                        throw new \InvalidArgumentException('Invalid UUID at index ' . $index);
                    }

                    $uuid = Uuid::fromString($itemId);

                    // Load existing entity
                    $entity = $this->entityManager->getRepository({{ entity.getEntityName() }}::class)->find($uuid);
                    if (!$entity) {
                        throw new \InvalidArgumentException('{{ entity.getEntityName() }} not found: ' . $itemId);
                    }

                    // Convert array to InputDto
                    $itemDto = new {{ entity.getEntityName() }}InputDto();
                    foreach ($itemData as $property => $value) {
                        if ($property !== 'id' && $property !== '@id' && property_exists($itemDto, $property)) {
                            $itemDto->$property = $value;
                        }
                    }

                    // Validate
                    $violations = $this->validator->validate($itemDto);
                    if (count($violations) > 0) {
                        $errors = [];
                        foreach ($violations as $violation) {
                            $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                        }
                        throw new \InvalidArgumentException(implode(', ', $errors));
                    }

                    // Process update
                    $updatedEntity = $this->itemProcessor->process(
                        $itemDto,
                        $operation,
                        ['id' => $uuid],
                        $context
                    );

                    $result->addSuccess(
                        $index,
                        $updatedEntity->getId()->toString(),
                        '/api/{{ entity.getEntityName()|lower }}s/' . $updatedEntity->getId()->toString()
                    );

                    $processedCount++;

                    if ($processedCount % self::BATCH_CLEAR_THRESHOLD === 0) {
                        $this->entityManager->flush();
                        $this->entityManager->clear();
                    }

                } catch (\Throwable $e) {
                    $this->logger->warning('[BATCH] Item update failed', [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ]);

                    $result->addError($index, [$e->getMessage()]);

                    if ($useTransaction) {
                        throw $e;
                    }

                    if (!$data->continueOnError) {
                        break;
                    }
                }
            }

            $this->entityManager->flush();

            if ($useTransaction) {
                $this->entityManager->commit();
            }

            $result->finalize();

            $this->logger->info('[BATCH] Batch update completed', [
                'success' => $result->successCount,
                'errors' => $result->errorCount,
                'status' => $result->status
            ]);

            return $result;

        } catch (\Throwable $e) {
            if ($useTransaction && $this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            if ($useTransaction) {
                throw new BadRequestHttpException(
                    'Batch operation failed (all_or_nothing mode): ' . $e->getMessage()
                );
            }

            $result->finalize();
            return $result;
        }
    }
}
```

---

### Step 8: Create Batch Delete Processor Template

**File:** `app/templates/genmax/php/batch_delete_processor.php.twig`

```twig
<?php

declare(strict_types=1);

namespace {{ namespace }};

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use {{ entity_namespace }}\{{ entity.getEntityName() }};
use {{ dto_namespace }}\{{ entity.getEntityName() }}BatchResultDto;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * {{ entity.getEntityLabel() }} Batch Delete Processor
 *
 * Handles bulk deletion of {{ entity.getEntityLabel() }} entities.
 *
 * This file is ALWAYS regenerated. DO NOT edit.
 *
 * @generated by Genmax Code Generator
 */
class {{ entity.getEntityName() }}BatchDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * @param array{ids: array<string>, transactionMode?: string} $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): {{ entity.getEntityName() }}BatchResultDto
    {
        if (!is_array($data) || !isset($data['ids'])) {
            throw new BadRequestHttpException('Invalid batch delete data: ids array required');
        }

        $ids = $data['ids'];
        $transactionMode = $data['transactionMode'] ?? '{{ entity.getBatchTransactionStrategy() }}';

        $this->logger->info('[BATCH] Starting batch delete for {{ entity.getEntityName() }}', [
            'id_count' => count($ids),
            'transaction_mode' => $transactionMode
        ]);

        $result = new {{ entity.getEntityName() }}BatchResultDto($transactionMode);

        if (count($ids) > {{ entity.getBatchMaxItems() }}) {
            throw new BadRequestHttpException(
                'Batch size exceeds maximum of {{ entity.getBatchMaxItems() }} items'
            );
        }

        $useTransaction = $transactionMode === 'all_or_nothing';
        if ($useTransaction) {
            $this->entityManager->beginTransaction();
        }

        try {
            foreach ($ids as $index => $id) {
                try {
                    // Handle IRI format
                    if (is_string($id) && str_starts_with($id, '/api/')) {
                        $parts = explode('/', $id);
                        $id = end($parts);
                    }

                    if (!Uuid::isValid($id)) {
                        throw new \InvalidArgumentException('Invalid UUID at index ' . $index);
                    }

                    $uuid = Uuid::fromString($id);

                    // Load and delete
                    $entity = $this->entityManager->getRepository({{ entity.getEntityName() }}::class)->find($uuid);
                    if (!$entity) {
                        throw new \InvalidArgumentException('{{ entity.getEntityName() }} not found: ' . $id);
                    }

                    $this->entityManager->remove($entity);

                    $result->addSuccess($index, $id, '');

                } catch (\Throwable $e) {
                    $this->logger->warning('[BATCH] Item deletion failed', [
                        'index' => $index,
                        'id' => $id,
                        'error' => $e->getMessage()
                    ]);

                    $result->addError($index, [$e->getMessage()]);

                    if ($useTransaction) {
                        throw $e;
                    }
                }
            }

            $this->entityManager->flush();

            if ($useTransaction) {
                $this->entityManager->commit();
            }

            $result->finalize();

            $this->logger->info('[BATCH] Batch delete completed', [
                'success' => $result->successCount,
                'errors' => $result->errorCount,
                'status' => $result->status
            ]);

            return $result;

        } catch (\Throwable $e) {
            if ($useTransaction && $this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            if ($useTransaction) {
                throw new BadRequestHttpException(
                    'Batch operation failed (all_or_nothing mode): ' . $e->getMessage()
                );
            }

            $result->finalize();
            return $result;
        }
    }
}
```

---

### Step 9: Update API Platform YAML Template

**File:** `app/templates/genmax/yaml/api_platform.yaml.twig`

**Add after existing operations (around line 97):**

```twig
{% if entity.isBatchOperationsEnabled() %}

      # =======================================
      # BATCH OPERATIONS
      # =======================================
{% if entity.isBatchOperationTypeEnabled('create') %}
      - class: ApiPlatform\Metadata\Post
        uriTemplate: /{{ entity.getEntityName()|lower }}s/batch-create
        input: App\Dto\{{ entity.getEntityName() }}BatchInputDto
        output: App\Dto\{{ entity.getEntityName() }}BatchResultDto
        processor: App\State\{{ entity.getEntityName() }}BatchCreateProcessor
        status: 201
{% if entity.getApiSecurity() %}
        security: {{ entity.getApiSecurity()|json_encode|raw }}
{% endif %}
        openapiContext:
          summary: "Batch create {{ entity.getPluralLabel()|lower }}"
          description: "Create multiple {{ entity.getPluralLabel()|lower }} in a single request"
          requestBody:
            content:
              application/json:
                schema:
                  type: object
                  properties:
                    items:
                      type: array
                      maxItems: {{ entity.getBatchMaxItems() }}
                      items:
                        $ref: '#/components/schemas/{{ entity.getEntityName() }}InputDto'
                    transactionMode:
                      type: string
                      enum: [all_or_nothing, partial]
                      default: {{ entity.getBatchTransactionStrategy() }}
                    continueOnError:
                      type: boolean
                      default: {{ entity.getBatchValidationStrategy() == 'collect_all' ? 'true' : 'false' }}
{% endif %}

{% if entity.isBatchOperationTypeEnabled('update') %}
      - class: ApiPlatform\Metadata\Patch
        uriTemplate: /{{ entity.getEntityName()|lower }}s/batch-update
        input: App\Dto\{{ entity.getEntityName() }}BatchInputDto
        output: App\Dto\{{ entity.getEntityName() }}BatchResultDto
        processor: App\State\{{ entity.getEntityName() }}BatchUpdateProcessor
{% if entity.getApiSecurity() %}
        security: {{ entity.getApiSecurity()|json_encode|raw }}
{% endif %}
        openapiContext:
          summary: "Batch update {{ entity.getPluralLabel()|lower }}"
          description: "Update multiple {{ entity.getPluralLabel()|lower }} in a single request"
{% endif %}

{% if entity.isBatchOperationTypeEnabled('delete') %}
      - class: ApiPlatform\Metadata\Delete
        uriTemplate: /{{ entity.getEntityName()|lower }}s/batch-delete
        input: false
        output: App\Dto\{{ entity.getEntityName() }}BatchResultDto
        processor: App\State\{{ entity.getEntityName() }}BatchDeleteProcessor
        deserialize: false
{% if entity.getApiSecurity() %}
        security: {{ entity.getApiSecurity()|json_encode|raw }}
{% endif %}
        openapiContext:
          summary: "Batch delete {{ entity.getPluralLabel()|lower }}"
          description: "Delete multiple {{ entity.getPluralLabel()|lower }} by IDs"
          requestBody:
            content:
              application/json:
                schema:
                  type: object
                  properties:
                    ids:
                      type: array
                      maxItems: {{ entity.getBatchMaxItems() }}
                      items:
                        type: string
                        format: uuid
                    transactionMode:
                      type: string
                      enum: [all_or_nothing, partial]
{% endif %}
{% endif %}
```

---

### Step 10: Create BatchOperationGenerator Service

**File:** `app/src/Service/Genmax/BatchOperationGenerator.php`

```php
<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;
use Psr\Log\LoggerInterface;

/**
 * Batch Operation Generator for Genmax
 *
 * Generates batch operation DTOs and processors for entities with
 * batch operations enabled.
 */
class BatchOperationGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        private readonly array $paths,
        #[Autowire(param: 'genmax.templates')]
        private readonly array $templates,
        private readonly Environment $twig,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate batch operation files for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        if (!$entity->isBatchOperationsEnabled()) {
            $this->logger->info('[GENMAX] Skipping batch operations (not enabled)', [
                'entity' => $entity->getEntityName()
            ]);
            return [];
        }

        $generatedFiles = [];

        $this->logger->info('[GENMAX] Generating batch operations', [
            'entity' => $entity->getEntityName(),
            'operations' => $entity->getBatchOperationTypes()
        ]);

        // Generate Batch Input DTO
        $generatedFiles[] = $this->generateBatchInputDtoGenerated($entity);
        $inputExtension = $this->generateBatchInputDtoExtension($entity);
        if ($inputExtension) {
            $generatedFiles[] = $inputExtension;
        }

        // Generate Batch Result DTO
        $generatedFiles[] = $this->generateBatchResultDtoGenerated($entity);
        $resultExtension = $this->generateBatchResultDtoExtension($entity);
        if ($resultExtension) {
            $generatedFiles[] = $resultExtension;
        }

        // Generate processors based on enabled operation types
        if ($entity->isBatchOperationTypeEnabled('create')) {
            $generatedFiles[] = $this->generateBatchCreateProcessor($entity);
        }

        if ($entity->isBatchOperationTypeEnabled('update')) {
            $generatedFiles[] = $this->generateBatchUpdateProcessor($entity);
        }

        if ($entity->isBatchOperationTypeEnabled('delete')) {
            $generatedFiles[] = $this->generateBatchDeleteProcessor($entity);
        }

        return array_filter($generatedFiles);
    }

    private function generateBatchInputDtoGenerated(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sBatchInputDtoGenerated.php',
            $this->projectDir,
            $this->paths['dto_generated_dir'],
            $entity->getEntityName()
        );

        $content = $this->twig->render($this->templates['batch_input_dto_generated'], [
            'entity' => $entity,
            'generated_namespace' => $this->paths['dto_generated_namespace'],
            'dto_namespace' => $this->paths['dto_namespace'],
        ]);

        $this->filesystem->dumpFile($filePath, $content);

        $this->logger->info('[GENMAX] Generated Batch Input DTO base', [
            'file' => $filePath
        ]);

        return $filePath;
    }

    private function generateBatchInputDtoExtension(GeneratorEntity $entity): ?string
    {
        $filePath = sprintf(
            '%s/%s/%sBatchInputDto.php',
            $this->projectDir,
            $this->paths['dto_dir'],
            $entity->getEntityName()
        );

        if (file_exists($filePath)) {
            $this->logger->info('[GENMAX] Skipping Batch Input DTO extension (exists)', [
                'file' => $filePath
            ]);
            return null;
        }

        $content = $this->twig->render($this->templates['batch_input_dto_extension'], [
            'entity' => $entity,
            'namespace' => $this->paths['dto_namespace'],
            'generated_namespace' => $this->paths['dto_generated_namespace'],
        ]);

        $this->filesystem->dumpFile($filePath, $content);

        $this->logger->info('[GENMAX] Generated Batch Input DTO extension', [
            'file' => $filePath
        ]);

        return $filePath;
    }

    private function generateBatchResultDtoGenerated(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sBatchResultDtoGenerated.php',
            $this->projectDir,
            $this->paths['dto_generated_dir'],
            $entity->getEntityName()
        );

        $content = $this->twig->render($this->templates['batch_result_dto_generated'], [
            'entity' => $entity,
            'generated_namespace' => $this->paths['dto_generated_namespace'],
            'dto_namespace' => $this->paths['dto_namespace'],
        ]);

        $this->filesystem->dumpFile($filePath, $content);

        return $filePath;
    }

    private function generateBatchResultDtoExtension(GeneratorEntity $entity): ?string
    {
        $filePath = sprintf(
            '%s/%s/%sBatchResultDto.php',
            $this->projectDir,
            $this->paths['dto_dir'],
            $entity->getEntityName()
        );

        if (file_exists($filePath)) {
            return null;
        }

        $content = $this->twig->render($this->templates['batch_result_dto_extension'], [
            'entity' => $entity,
            'namespace' => $this->paths['dto_namespace'],
            'generated_namespace' => $this->paths['dto_generated_namespace'],
        ]);

        $this->filesystem->dumpFile($filePath, $content);

        return $filePath;
    }

    private function generateBatchCreateProcessor(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sBatchCreateProcessor.php',
            $this->projectDir,
            $this->paths['processor_dir'],
            $entity->getEntityName()
        );

        $content = $this->twig->render($this->templates['batch_create_processor'], [
            'entity' => $entity,
            'namespace' => $this->paths['processor_namespace'],
            'dto_namespace' => $this->paths['dto_namespace'],
            'entity_namespace' => $this->paths['entity_namespace'],
        ]);

        $this->filesystem->dumpFile($filePath, $content);

        $this->logger->info('[GENMAX] Generated Batch Create Processor', [
            'file' => $filePath
        ]);

        return $filePath;
    }

    private function generateBatchUpdateProcessor(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sBatchUpdateProcessor.php',
            $this->projectDir,
            $this->paths['processor_dir'],
            $entity->getEntityName()
        );

        $content = $this->twig->render($this->templates['batch_update_processor'], [
            'entity' => $entity,
            'namespace' => $this->paths['processor_namespace'],
            'dto_namespace' => $this->paths['dto_namespace'],
            'entity_namespace' => $this->paths['entity_namespace'],
        ]);

        $this->filesystem->dumpFile($filePath, $content);

        return $filePath;
    }

    private function generateBatchDeleteProcessor(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sBatchDeleteProcessor.php',
            $this->projectDir,
            $this->paths['processor_dir'],
            $entity->getEntityName()
        );

        $content = $this->twig->render($this->templates['batch_delete_processor'], [
            'entity' => $entity,
            'namespace' => $this->paths['processor_namespace'],
            'dto_namespace' => $this->paths['dto_namespace'],
            'entity_namespace' => $this->paths['entity_namespace'],
        ]);

        $this->filesystem->dumpFile($filePath, $content);

        return $filePath;
    }
}
```

---

### Step 11: Integrate into GenmaxOrchestrator

**File:** `app/src/Service/Genmax/GenmaxOrchestrator.php`

**Add feature flag (line 34):**

```php
private const BATCH_OPERATIONS_ACTIVE = true;  // ✅ Phase 2.6 - ACTIVE
```

**Add to constructor (line 54):**

```php
private readonly BatchOperationGenerator $batchOperationGenerator,
```

**Add to generation loop (after line 181):**

```php
// Batch Operations
if (self::BATCH_OPERATIONS_ACTIVE && $entity->isBatchOperationsEnabled()) {
    try {
        $files = $this->batchOperationGenerator->generate($entity);
        $generatedFiles = array_merge($generatedFiles, $files);
        $currentStep++;
    } catch (\Throwable $e) {
        $this->logger->error("[GENMAX] Batch operations generation failed", [
            'entity' => $entity->getEntityName(),
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}
```

**Update countActiveGenerators() (line 393):**

```php
$count += self::BATCH_OPERATIONS_ACTIVE ? 1 : 0;
```

**Update getActiveGenerators() (line 415):**

```php
if (self::BATCH_OPERATIONS_ACTIVE) $active[] = 'batch_operations';
```

**Update collectFilesToBackup() (line 363):**

```php
// Batch operation files
if (self::BATCH_OPERATIONS_ACTIVE && $entity->isBatchOperationsEnabled()) {
    $files[] = sprintf('%s/%s/%sBatchInputDtoGenerated.php', $this->projectDir, $this->paths['dto_generated_dir'], $entityName);
    $files[] = sprintf('%s/%s/%sBatchInputDto.php', $this->projectDir, $this->paths['dto_dir'], $entityName);
    $files[] = sprintf('%s/%s/%sBatchResultDtoGenerated.php', $this->projectDir, $this->paths['dto_generated_dir'], $entityName);
    $files[] = sprintf('%s/%s/%sBatchResultDto.php', $this->projectDir, $this->paths['dto_dir'], $entityName);

    if ($entity->isBatchOperationTypeEnabled('create')) {
        $files[] = sprintf('%s/%s/%sBatchCreateProcessor.php', $this->projectDir, $this->paths['processor_dir'], $entityName);
    }
    if ($entity->isBatchOperationTypeEnabled('update')) {
        $files[] = sprintf('%s/%s/%sBatchUpdateProcessor.php', $this->projectDir, $this->paths['processor_dir'], $entityName);
    }
    if ($entity->isBatchOperationTypeEnabled('delete')) {
        $files[] = sprintf('%s/%s/%sBatchDeleteProcessor.php', $this->projectDir, $this->paths['processor_dir'], $entityName);
    }
}
```

---

## 6. Code Examples

### Example 1: Enable Batch Operations for Contact Entity

**Via API:**

```json
PATCH /api/generator_entities/{contact-entity-id}
{
  "batchOperationsEnabled": true,
  "batchOperationTypes": ["create", "update", "delete"],
  "batchMaxItems": 50,
  "batchTransactionStrategy": "partial",
  "batchValidationStrategy": "collect_all"
}
```

**Run Generation:**

```bash
php bin/console genmax:generate
```

**Generated Endpoints:**

```
POST   /api/contacts/batch-create
PATCH  /api/contacts/batch-update
DELETE /api/contacts/batch-delete
```

---

### Example 2: Batch Create Request

```bash
curl -X POST https://localhost/api/contacts/batch-create \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+1-555-0100"
      },
      {
        "name": "Jane Smith",
        "email": "jane@example.com",
        "phone": "+1-555-0101"
      }
    ],
    "transactionMode": "all_or_nothing",
    "continueOnError": false
  }'
```

**Success Response (201):**

```json
{
  "successCount": 2,
  "errorCount": 0,
  "totalProcessed": 2,
  "status": "completed",
  "transactionMode": "all_or_nothing",
  "results": [
    {
      "index": 0,
      "status": "success",
      "id": "01932f45-6789-7abc-9def-0123456789ab",
      "iri": "/api/contacts/01932f45-6789-7abc-9def-0123456789ab"
    },
    {
      "index": 1,
      "status": "success",
      "id": "01932f45-789a-7bcd-9ef0-123456789abc",
      "iri": "/api/contacts/01932f45-789a-7bcd-9ef0-123456789abc"
    }
  ]
}
```

---

### Example 3: Batch Update with Partial Failures

```bash
curl -X PATCH https://localhost/api/contacts/batch-update \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [
      {
        "id": "01932f45-6789-7abc-9def-0123456789ab",
        "phone": "+1-555-9999"
      },
      {
        "id": "invalid-uuid",
        "phone": "+1-555-8888"
      }
    ],
    "transactionMode": "partial",
    "continueOnError": true
  }'
```

**Partial Success Response (207 Multi-Status):**

```json
{
  "successCount": 1,
  "errorCount": 1,
  "totalProcessed": 2,
  "status": "partial",
  "transactionMode": "partial",
  "results": [
    {
      "index": 0,
      "status": "success",
      "id": "01932f45-6789-7abc-9def-0123456789ab",
      "iri": "/api/contacts/01932f45-6789-7abc-9def-0123456789ab"
    },
    {
      "index": 1,
      "status": "error",
      "errors": ["Invalid UUID at index 1"]
    }
  ]
}
```

---

### Example 4: Batch Delete

```bash
curl -X DELETE https://localhost/api/contacts/batch-delete \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ids": [
      "01932f45-6789-7abc-9def-0123456789ab",
      "01932f45-789a-7bcd-9ef0-123456789abc"
    ],
    "transactionMode": "all_or_nothing"
  }'
```

---

## 7. Testing Strategy

### Unit Tests

**File:** `app/tests/Service/Genmax/BatchOperationGeneratorTest.php`

- Test generator creates correct files
- Test skips when batch operations disabled
- Test respects batch operation types configuration

### Functional Tests

**File:** `app/tests/Api/ContactBatchOperationsTest.php`

```php
class ContactBatchOperationsTest extends ApiTestCase
{
    public function testBatchCreateSuccess(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/contacts/batch-create', [
            'json' => [
                'items' => [
                    ['name' => 'Test 1', 'email' => 'test1@example.com'],
                    ['name' => 'Test 2', 'email' => 'test2@example.com'],
                ],
                'transactionMode' => 'all_or_nothing'
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);

        $data = $client->getResponse()->toArray();
        $this->assertEquals(2, $data['successCount']);
        $this->assertEquals(0, $data['errorCount']);
    }

    public function testBatchCreateExceedsMaxItems(): void
    {
        // Test batch size validation
    }

    public function testBatchUpdateWithPartialFailure(): void
    {
        // Test partial transaction mode
    }

    public function testBatchDeleteAllOrNothing(): void
    {
        // Test all_or_nothing rollback
    }
}
```

### Performance Tests

- Test 100 item batch (max size)
- Measure memory usage
- Verify Doctrine clear() prevents memory leaks
- Test concurrent batch requests

---

## 8. Migration & Rollout

### Migration Checklist

- [ ] Run database migration
- [ ] Update existing GeneratorEntity records (optional)
- [ ] Regenerate entities with batch operations
- [ ] Update API documentation
- [ ] Train team on new feature

### Rollback Plan

If issues arise:

1. Set `BATCH_OPERATIONS_ACTIVE = false` in GenmaxOrchestrator
2. Regenerate entities (batch files won't be created)
3. Clear cache
4. Batch endpoints will return 404 (graceful degradation)

### Gradual Rollout

**Week 1:** Enable for 1 non-critical entity (e.g., Tag, Category)
**Week 2:** Enable for 3 more entities, monitor
**Week 3:** Enable for all entities needing batch operations
**Week 4:** Full production deployment

---

## 9. Future Enhancements

### Phase 2.7: Advanced Features

- [ ] **Async batch processing** - Queue large batches for background processing
- [ ] **Progress tracking** - WebSocket updates for long-running batches
- [ ] **Batch templates** - Save/reuse common batch operations
- [ ] **Dry-run mode** - Validate batch without committing
- [ ] **Batch import from CSV** - Upload CSV, auto-convert to batch

### Phase 2.8: Performance Optimizations

- [ ] **Batch size auto-tuning** - Adjust based on memory/performance
- [ ] **Parallel processing** - Process independent items concurrently
- [ ] **Caching** - Cache validation results for duplicate items
- [ ] **Streaming API** - Process extremely large batches

### Phase 2.9: Analytics & Monitoring

- [ ] **Batch operation metrics** - Track success rates, performance
- [ ] **Audit logging** - Full audit trail for batch operations
- [ ] **Error analytics** - Common error patterns
- [ ] **Usage dashboards** - Most-used batch endpoints

---

## Appendix A: Configuration Reference

### GeneratorEntity Batch Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `batchOperationsEnabled` | bool | false | Enable batch operation generation |
| `batchOperationTypes` | array | null | ['create', 'update', 'delete'] |
| `batchMaxItems` | int | 100 | Maximum items per batch (1-1000) |
| `batchTransactionStrategy` | string | all_or_nothing | 'all_or_nothing' or 'partial' |
| `batchValidationStrategy` | string | collect_all | 'fail_fast' or 'collect_all' |

---

## Appendix B: Troubleshooting

### Common Issues

**Issue:** Batch endpoint returns 404
- **Solution:** Regenerate entity with `php bin/console genmax:generate`

**Issue:** Out of memory error with large batches
- **Solution:** Reduce `batchMaxItems` or increase PHP memory_limit

**Issue:** Partial success but all items rolled back
- **Solution:** Check `transactionMode` - should be 'partial' not 'all_or_nothing'

**Issue:** Validation errors not detailed
- **Solution:** Set `batchValidationStrategy` to 'collect_all'

---

## Appendix C: Security Considerations

### Multi-Tenancy

- Each item in batch MUST belong to authenticated user's organization
- Batch processor validates organization for every item
- No cross-organization batch operations allowed

### Rate Limiting

- Apply rate limiting to batch endpoints
- Count each item in batch toward rate limit
- Example: Batch of 50 items = 50 API calls

### Authorization

- Security voters check each item individually
- Batch fails if user lacks permission for any item
- Consider batch-specific voter attributes (e.g., `BATCH_CREATE`)

---

**END OF IMPLEMENTATION PLAN**

---

## Next Steps

1. **Review this plan** with your team
2. **Estimate effort** for each phase
3. **Prioritize** which entities need batch operations first
4. **Begin Phase 1** - Database schema updates
5. **Iterate** based on feedback

**Questions? Issues? Enhancement ideas?**
Document them in this file or create GitHub issues.

**Good luck with implementation! 🚀**
