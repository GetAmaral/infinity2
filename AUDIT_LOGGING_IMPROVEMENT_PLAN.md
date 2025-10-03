# INFINITY AUDIT & LOGGING SYSTEM - COMPREHENSIVE IMPROVEMENT PLAN

## Executive Summary

Based on the audit system analysis, this plan proposes a **6-phase enhancement program** to transform the current functional but basic audit system into an enterprise-grade compliance and observability platform. The plan prioritizes immediate operational needs (log rotation, performance) before adding advanced features (historical tracking, UI, analytics).

**Timeline**: 6-8 weeks | **Phases**: 6 | **Complexity**: Medium

---

## PHASE 1: Log Management & Rotation (CRITICAL)
**Duration**: 3-5 days | **Priority**: HIGH | **Risk**: Low

### Objective
Prevent disk space issues and improve log manageability by implementing proper log rotation, compression, and retention policies.

### Current Problems
- Single 124MB `dev.log` file growing indefinitely
- No log rotation configured
- No automatic cleanup
- Difficult to search/analyze large monolithic log files

### Deliverables

**1.1 Configure Monolog Rotating File Handler**
```yaml
# config/packages/monolog.yaml (prod environment)
when@prod:
    monolog:
        handlers:
            # Replace existing audit_file handler
            audit_file:
                type: rotating_file  # Changed from 'stream'
                path: "%kernel.logs_dir%/audit.log"
                level: info
                channels: ['audit']
                formatter: monolog.formatter.json
                max_files: 90  # Keep 90 days of logs
                filename_format: '{filename}-{date}'
                date_format: 'Y-m-d'
```

**1.2 Add Separate Rotating Handlers by Entity Type**
```yaml
# Separate high-volume entities into own log files
audit_user_file:
    type: rotating_file
    path: "%kernel.logs_dir%/audit_user.log"
    level: info
    channels: ['audit']
    max_files: 30
    formatter: monolog.formatter.json

audit_student_file:
    type: rotating_file
    path: "%kernel.logs_dir%/audit_student.log"
    level: info
    channels: ['audit']
    max_files: 30
    formatter: monolog.formatter.json
```

**1.3 Implement Log Compression Service**
```php
// src/Service/LogCompressionService.php
namespace App\Service;

class LogCompressionService
{
    public function compressOldLogs(string $logDir, int $olderThanDays = 7): void
    {
        // Find log files older than X days
        // Compress with gzip
        // Delete original after compression
    }
}
```

**1.4 Create Symfony Command for Log Maintenance**
```bash
php bin/console make:command app:logs:cleanup
```

```php
// src/Command/LogsCleanupCommand.php
#[AsCommand(name: 'app:logs:cleanup')]
class LogsCleanupCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Compress logs older than 7 days
        // Delete compressed logs older than retention policy
        // Report statistics
    }
}
```

**1.5 Add Cron Job Configuration**
```bash
# crontab entry for production server
0 2 * * * cd /app && php bin/console app:logs:cleanup --env=prod
```

### Success Criteria
- ✅ Log files rotate daily
- ✅ Old logs compressed automatically
- ✅ Logs older than 90 days deleted
- ✅ Disk space usage reduced by 70%+
- ✅ Individual log files never exceed 50MB

### Testing Plan
1. Deploy to staging environment
2. Generate 100MB+ of audit logs
3. Run cleanup command manually
4. Verify rotation, compression, deletion
5. Check log accessibility

---

## PHASE 2: Performance Optimization & Async Logging
**Duration**: 5-7 days | **Priority**: HIGH | **Risk**: Medium

### Objective
Eliminate performance impact of audit logging on user-facing operations by implementing asynchronous log writing and database optimization.

### Current Problems
- Audit logging happens synchronously during entity operations
- 534 events already showing potential performance impact
- Log writes block entity persist/flush operations
- No buffering or batching

### Deliverables

**2.1 Implement Message-Based Async Logging**
```bash
composer require symfony/messenger symfony/amqp-messenger
```

**2.2 Create Audit Event Message**
```php
// src/Message/AuditEventMessage.php
namespace App\Message;

class AuditEventMessage
{
    public function __construct(
        public readonly string $action,
        public readonly string $entityClass,
        public readonly ?string $entityId,
        public readonly ?string $userId,
        public readonly ?string $userEmail,
        public readonly string $timestamp,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly array $changes = []
    ) {}
}
```

**2.3 Create Async Audit Handler**
```php
// src/MessageHandler/AuditEventHandler.php
namespace App\MessageHandler;

#[AsMessageHandler]
class AuditEventHandler
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.audit')]
        private readonly LoggerInterface $auditLogger
    ) {}

    public function __invoke(AuditEventMessage $message): void
    {
        // Write to log asynchronously
        $this->auditLogger->info('Audit event recorded', [
            'action' => $message->action,
            'entity_class' => $message->entityClass,
            // ... rest of data
        ]);
    }
}
```

**2.4 Update AuditSubscriber to Dispatch Messages**
```php
// src/EventSubscriber/AuditSubscriber.php
public function __construct(
    private readonly Security $security,
    private readonly MessageBusInterface $messageBus  // NEW
) {}

private function logAuditEvent(...): void
{
    $message = new AuditEventMessage(
        action: $action,
        entityClass: $entityClass,
        // ... rest of data
    );

    // Dispatch async instead of logging directly
    $this->messageBus->dispatch($message);
}
```

**2.5 Configure Messenger Transport**
```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async_audit: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            'App\Message\AuditEventMessage': async_audit
```

**2.6 Add Database Indexes for Audit Queries**
```php
// Create migration for composite indexes
CREATE INDEX idx_user_audit_created ON "user"(created_by_id, created_at DESC);
CREATE INDEX idx_user_audit_updated ON "user"(updated_by_id, updated_at DESC);
CREATE INDEX idx_course_audit_created ON course(created_by_id, created_at DESC);
CREATE INDEX idx_course_audit_updated ON course(updated_by_id, updated_at DESC);
// Repeat for all entities
```

**2.7 Implement Batch Log Writing**
```php
// src/Service/BatchAuditLogger.php
class BatchAuditLogger
{
    private array $buffer = [];
    private const BATCH_SIZE = 100;

    public function addEvent(AuditEventMessage $event): void
    {
        $this->buffer[] = $event;

        if (count($this->buffer) >= self::BATCH_SIZE) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        // Write all buffered events at once
        // Clear buffer
    }
}
```

### Success Criteria
- ✅ Entity operations complete 50%+ faster
- ✅ No blocking I/O during persist/flush
- ✅ Audit events processed via message queue
- ✅ Database queries using indexes for audit trails
- ✅ No lost audit events (message retry on failure)

### Testing Plan
1. Load test with 1000 concurrent entity operations
2. Measure time before/after async implementation
3. Verify all audit events eventually logged
4. Test failure scenarios (queue down, disk full)
5. Benchmark audit query performance

---

## PHASE 3: Historical Audit Table & Change Tracking
**Duration**: 7-10 days | **Priority**: MEDIUM | **Risk**: Medium

### Objective
Implement comprehensive historical tracking of all entity changes in a dedicated audit table, enabling full audit trail queries and compliance reporting.

### Current Problems
- Current system only shows CURRENT state (created_by, updated_by)
- Cannot answer: "What was the value 3 months ago?"
- Cannot track: "All changes made by user X"
- No record of deletions

### Deliverables

**3.1 Create AuditLog Entity**
```php
// src/Entity/AuditLog.php
namespace App\Entity;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['entity_class', 'entity_id'])]
#[ORM\Index(columns: ['user_id', 'created_at'])]
#[ORM\Index(columns: ['action', 'created_at'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $action; // created, updated, deleted

    #[ORM\Column(length: 255)]
    private string $entityClass;

    #[ORM\Column(type: 'uuid')]
    private Uuid $entityId;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(type: 'json')]
    private array $changes = []; // Field-level changes

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null; // IP, user agent, etc.

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    // Getters/setters...
}
```

**3.2 Create Database Migration**
```bash
php bin/console make:migration
```

```sql
CREATE TABLE audit_log (
    id UUID PRIMARY KEY,
    action VARCHAR(255) NOT NULL,
    entity_class VARCHAR(255) NOT NULL,
    entity_id UUID NOT NULL,
    user_id UUID,
    changes JSON NOT NULL,
    metadata JSON,
    created_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES "user"(id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_entity ON audit_log(entity_class, entity_id);
CREATE INDEX idx_audit_user ON audit_log(user_id, created_at DESC);
CREATE INDEX idx_audit_action ON audit_log(action, created_at DESC);
CREATE INDEX idx_audit_created ON audit_log(created_at DESC);
```

**3.3 Update AuditSubscriber to Write to Table**
```php
// src/EventSubscriber/AuditSubscriber.php
public function __construct(
    private readonly Security $security,
    private readonly MessageBusInterface $messageBus,
    private readonly EntityManagerInterface $entityManager  // NEW
) {}

private function logAuditEvent(...): void
{
    // Create AuditLog entity
    $auditLog = new AuditLog();
    $auditLog->setAction($action);
    $auditLog->setEntityClass($entityClass);
    $auditLog->setEntityId(Uuid::fromString($entityId));
    $auditLog->setUser($user);
    $auditLog->setChanges($changeSet);
    $auditLog->setMetadata([
        'ip_address' => $this->getClientIpAddress(),
        'user_agent' => $this->getUserAgent(),
    ]);

    $this->entityManager->persist($auditLog);
    $this->entityManager->flush();

    // Also dispatch message for log file
    $this->messageBus->dispatch(new AuditEventMessage(...));
}
```

**3.4 Create AuditLogRepository with Query Methods**
```php
// src/Repository/AuditLogRepository.php
class AuditLogRepository extends ServiceEntityRepository
{
    public function findByEntity(string $entityClass, Uuid $entityId): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.entityClass = :class')
            ->andWhere('a.entityId = :id')
            ->orderBy('a.createdAt', 'DESC')
            ->setParameter('class', $entityClass)
            ->setParameter('id', $entityId)
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user, ?\DateTime $since = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->orderBy('a.createdAt', 'DESC')
            ->setParameter('user', $user);

        if ($since) {
            $qb->andWhere('a.createdAt >= :since')
               ->setParameter('since', $since);
        }

        return $qb->getQuery()->getResult();
    }

    public function findChangesByField(string $entityClass, string $fieldName): array
    {
        // Query changes JSON column for specific field
        return $this->createQueryBuilder('a')
            ->where('a.entityClass = :class')
            ->andWhere("JSON_EXTRACT(a.changes, '$." . $fieldName . "') IS NOT NULL")
            ->orderBy('a.createdAt', 'DESC')
            ->setParameter('class', $entityClass)
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(\DateTime $since): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.action, a.entityClass, COUNT(a.id) as count')
            ->where('a.createdAt >= :since')
            ->groupBy('a.action', 'a.entityClass')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }
}
```

**3.5 Implement Soft Delete Support**
```php
// src/Entity/Trait/SoftDeletableTrait.php
trait SoftDeletableTrait
{
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $deletedBy = null;

    public function softDelete(User $user): void
    {
        $this->deletedAt = new \DateTimeImmutable();
        $this->deletedBy = $user;
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->deletedBy = null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
```

**3.6 Create Soft Delete Subscriber**
```php
// src/EventSubscriber/SoftDeleteSubscriber.php
#[AsDoctrineListener(event: Events::preRemove)]
class SoftDeleteSubscriber
{
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($this->hasSoftDeleteTrait($entity)) {
            // Cancel hard delete
            $em = $args->getObjectManager();
            $em->detach($entity);

            // Perform soft delete instead
            $entity->softDelete($this->getCurrentUser());
            $em->persist($entity);

            // Log deletion to audit table
            $this->logDeletion($entity);
        }
    }
}
```

### Success Criteria
- ✅ All entity changes stored in audit_log table
- ✅ Can query: "Show all changes to Organization X"
- ✅ Can query: "Show all actions by User Y in last 30 days"
- ✅ Can query: "What was Course name on date Z?"
- ✅ Soft deletes preserve audit trail
- ✅ Deletion events logged with full entity snapshot

### Testing Plan
1. Create, update, delete entities
2. Query audit_log for each operation
3. Verify all field changes captured
4. Test soft delete functionality
5. Performance test audit queries on 100k+ records

---

## PHASE 4: Audit Viewing UI & Admin Interface
**Duration**: 10-14 days | **Priority**: MEDIUM | **Risk**: Low

### Objective
Provide administrators with a powerful web-based interface to search, filter, and analyze audit logs without requiring database or log file access.

### Current Problems
- No UI to view audit logs
- Requires SSH access + SQL queries to investigate
- No search/filter capabilities
- Not accessible to non-technical staff

### Deliverables

**4.1 Create AuditController**
```php
// src/Controller/Admin/AuditController.php
namespace App\Controller\Admin;

#[Route('/admin/audit')]
#[IsGranted('ROLE_ADMIN')]
class AuditController extends AbstractController
{
    #[Route('/', name: 'admin_audit_index')]
    public function index(Request $request, AuditLogRepository $repo): Response
    {
        // Display audit log with filters
    }

    #[Route('/entity/{class}/{id}', name: 'admin_audit_entity')]
    public function viewEntity(string $class, Uuid $id): Response
    {
        // Show full audit trail for specific entity
    }

    #[Route('/user/{id}', name: 'admin_audit_user')]
    public function viewUser(Uuid $id): Response
    {
        // Show all actions by specific user
    }

    #[Route('/export', name: 'admin_audit_export')]
    public function export(Request $request): Response
    {
        // Export audit logs to CSV/JSON
    }
}
```

**4.2 Create Audit Search Form**
```php
// src/Form/AuditSearchType.php
class AuditSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('entityClass', ChoiceType::class, [
                'choices' => [
                    'User' => User::class,
                    'Organization' => Organization::class,
                    'Course' => Course::class,
                    // ...
                ],
                'required' => false,
            ])
            ->add('action', ChoiceType::class, [
                'choices' => [
                    'Created' => 'entity_created',
                    'Updated' => 'entity_updated',
                    'Deleted' => 'entity_deleted',
                ],
                'required' => false,
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'required' => false,
            ])
            ->add('dateFrom', DateType::class, ['required' => false])
            ->add('dateTo', DateType::class, ['required' => false])
            ->add('search', SearchType::class, ['required' => false]);
    }
}
```

**4.3 Create Audit Index Template**
```twig
{# templates/admin/audit/index.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
<div class="infinity-card p-4">
    <h1 class="text-gradient mb-4">
        <i class="bi bi-clipboard-data me-2"></i>Audit Log
    </h1>

    {# Search/Filter Form #}
    {{ form_start(searchForm) }}
    <div class="row">
        <div class="col-md-3">{{ form_row(searchForm.entityClass) }}</div>
        <div class="col-md-3">{{ form_row(searchForm.action) }}</div>
        <div class="col-md-3">{{ form_row(searchForm.user) }}</div>
        <div class="col-md-3">{{ form_row(searchForm.dateFrom) }}</div>
    </div>
    <button type="submit" class="btn infinity-btn-primary">
        <i class="bi bi-search me-1"></i>Search
    </button>
    {{ form_end(searchForm) }}

    {# Results Table #}
    <table class="table table-hover mt-4">
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Action</th>
                <th>Entity</th>
                <th>User</th>
                <th>Changes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {% for log in auditLogs %}
            <tr>
                <td>{{ log.createdAt|date('Y-m-d H:i:s') }}</td>
                <td>
                    <span class="badge bg-{{ log.action == 'entity_created' ? 'success' : 'primary' }}">
                        {{ log.action }}
                    </span>
                </td>
                <td>
                    <a href="{{ path('admin_audit_entity', {
                        class: log.entityClass,
                        id: log.entityId
                    }) }}">
                        {{ log.entityClass|split('\\')|last }} #{{ log.entityId|slice(0, 8) }}
                    </a>
                </td>
                <td>
                    {% if log.user %}
                        <a href="{{ path('admin_audit_user', {id: log.user.id}) }}">
                            {{ log.user.email }}
                        </a>
                    {% else %}
                        <em class="text-muted">System</em>
                    {% endif %}
                </td>
                <td>
                    <button class="btn btn-sm infinity-btn-ai"
                            data-bs-toggle="modal"
                            data-bs-target="#changesModal{{ log.id }}">
                        View Changes
                    </button>
                </td>
                <td>
                    <a href="{{ path('admin_audit_entity', {
                        class: log.entityClass,
                        id: log.entityId
                    }) }}" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-clock-history"></i> Timeline
                    </a>
                </td>
            </tr>
            {% endfor %}
        </tbody>
    </table>
</div>
{% endblock %}
```

**4.4 Create Entity Timeline Template**
```twig
{# templates/admin/audit/entity_timeline.html.twig #}
<div class="timeline">
    {% for log in auditLogs %}
    <div class="timeline-item">
        <div class="timeline-marker bg-{{ log.action == 'entity_created' ? 'success' : 'primary' }}">
            <i class="bi bi-{{ log.action == 'entity_created' ? 'plus' : 'pencil' }}"></i>
        </div>
        <div class="timeline-content infinity-card p-3">
            <div class="d-flex justify-content-between">
                <h6 class="text-white">{{ log.action|replace({'_': ' '})|title }}</h6>
                <small class="text-muted">{{ log.createdAt|date('Y-m-d H:i:s') }}</small>
            </div>
            <p class="mb-2">
                by {% if log.user %}{{ log.user.email }}{% else %}<em>System</em>{% endif %}
            </p>
            {% if log.changes %}
            <div class="changes-detail">
                {% for field, values in log.changes %}
                <div class="change-row">
                    <strong>{{ field }}:</strong>
                    <span class="text-danger">{{ values[0] }}</span>
                    <i class="bi bi-arrow-right mx-2"></i>
                    <span class="text-success">{{ values[1] }}</span>
                </div>
                {% endfor %}
            </div>
            {% endif %}
        </div>
    </div>
    {% endfor %}
</div>
```

**4.5 Create Audit Export Service**
```php
// src/Service/AuditExportService.php
class AuditExportService
{
    public function exportToCsv(array $auditLogs): StreamedResponse
    {
        return new StreamedResponse(function() use ($auditLogs) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, ['Timestamp', 'Action', 'Entity', 'User', 'Changes', 'IP']);

            foreach ($auditLogs as $log) {
                fputcsv($handle, [
                    $log->getCreatedAt()->format('Y-m-d H:i:s'),
                    $log->getAction(),
                    $log->getEntityClass() . '#' . $log->getEntityId(),
                    $log->getUser()?->getEmail() ?? 'System',
                    json_encode($log->getChanges()),
                    $log->getMetadata()['ip_address'] ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit_export.csv"',
        ]);
    }

    public function exportToJson(array $auditLogs): JsonResponse
    {
        $data = array_map(fn($log) => [
            'timestamp' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'action' => $log->getAction(),
            'entity_class' => $log->getEntityClass(),
            'entity_id' => $log->getEntityId()->toString(),
            'user_email' => $log->getUser()?->getEmail(),
            'changes' => $log->getChanges(),
            'metadata' => $log->getMetadata(),
        ], $auditLogs);

        return new JsonResponse($data);
    }
}
```

**4.6 Add Real-Time Audit Dashboard**
```javascript
// assets/controllers/audit_dashboard_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { refreshUrl: String }

    connect() {
        this.refresh();
        setInterval(() => this.refresh(), 30000); // Refresh every 30s
    }

    async refresh() {
        const response = await fetch(this.refreshUrlValue);
        const html = await response.text();
        this.element.innerHTML = html;
    }
}
```

### Success Criteria
- ✅ Admins can search audit logs via web interface
- ✅ Filter by entity type, action, user, date range
- ✅ View complete timeline for any entity
- ✅ View all actions by any user
- ✅ Export audit data to CSV/JSON
- ✅ Real-time dashboard updates every 30 seconds
- ✅ Responsive design works on mobile

### Testing Plan
1. Test all filter combinations
2. Verify timeline accuracy
3. Test export with 10k+ records
4. Performance test with concurrent users
5. Mobile responsiveness check

---

## PHASE 5: Compliance & Retention Policies
**Duration**: 5-7 days | **Priority**: MEDIUM | **Risk**: Low

### Objective
Implement automated retention policies, compliance reporting, and data protection measures to meet regulatory requirements (GDPR, SOC2, etc.).

### Current Problems
- No automatic log cleanup
- No retention policy enforcement
- No compliance reporting
- No audit log encryption
- No tamper detection

### Deliverables

**5.1 Create Retention Policy Configuration**
```yaml
# config/packages/audit.yaml
audit:
    retention:
        default_days: 90
        policies:
            user: 365           # Keep user audit logs for 1 year
            organization: 1825  # Keep org logs for 5 years
            course: 730         # Keep course logs for 2 years
            student_course: 365
            student_lecture: 90

    encryption:
        enabled: true
        algorithm: 'aes-256-gcm'

    compliance:
        gdpr_enabled: true
        anonymize_after_days: 730
```

**5.2 Create Retention Policy Service**
```php
// src/Service/AuditRetentionService.php
class AuditRetentionService
{
    public function __construct(
        private readonly AuditLogRepository $auditRepo,
        private readonly ParameterBagInterface $params
    ) {}

    public function enforceRetentionPolicies(): array
    {
        $stats = [];
        $policies = $this->params->get('audit.retention.policies');

        foreach ($policies as $entityClass => $retentionDays) {
            $cutoffDate = new \DateTime("-{$retentionDays} days");

            $deleted = $this->auditRepo->deleteOlderThan(
                $entityClass,
                $cutoffDate
            );

            $stats[$entityClass] = $deleted;
        }

        return $stats;
    }

    public function anonymizeOldData(): int
    {
        $anonymizeAfter = $this->params->get('audit.compliance.anonymize_after_days');
        $cutoffDate = new \DateTime("-{$anonymizeAfter} days");

        return $this->auditRepo->anonymizeUserData($cutoffDate);
    }
}
```

**5.3 Implement GDPR Data Anonymization**
```php
// src/Repository/AuditLogRepository.php
public function anonymizeUserData(\DateTime $before): int
{
    return $this->createQueryBuilder('a')
        ->update()
        ->set('a.user', 'NULL')
        ->set('a.metadata', "JSON_SET(a.metadata, '$.user_email', 'anonymized@gdpr.local')")
        ->set('a.metadata', "JSON_SET(a.metadata, '$.ip_address', '0.0.0.0')")
        ->where('a.createdAt < :before')
        ->setParameter('before', $before)
        ->getQuery()
        ->execute();
}
```

**5.4 Create Compliance Report Generator**
```php
// src/Service/ComplianceReportService.php
class ComplianceReportService
{
    public function generateGDPRReport(User $user): array
    {
        return [
            'data_subject' => [
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ],
            'audit_trail' => $this->auditRepo->findByUser($user),
            'data_created' => $this->findEntitiesCreatedByUser($user),
            'data_modified' => $this->findEntitiesModifiedByUser($user),
            'retention_status' => $this->getRetentionStatus($user),
            'generated_at' => new \DateTimeImmutable(),
        ];
    }

    public function generateSOC2AuditReport(\DateTime $from, \DateTime $to): array
    {
        return [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'statistics' => [
                'total_events' => $this->auditRepo->countInPeriod($from, $to),
                'by_action' => $this->auditRepo->countByAction($from, $to),
                'by_entity' => $this->auditRepo->countByEntity($from, $to),
                'by_user' => $this->auditRepo->countByUser($from, $to),
            ],
            'security_events' => [
                'failed_logins' => $this->getFailedLoginCount($from, $to),
                'unauthorized_access' => $this->getUnauthorizedAccessCount($from, $to),
                'data_deletions' => $this->getDeletionCount($from, $to),
            ],
            'compliance_checks' => [
                'all_events_logged' => $this->verifyCompleteness(),
                'no_tampering_detected' => $this->verifyIntegrity(),
                'retention_policies_enforced' => $this->verifyRetention(),
            ],
        ];
    }
}
```

**5.5 Implement Audit Log Encryption**
```php
// src/Service/AuditEncryptionService.php
class AuditEncryptionService
{
    private const CIPHER = 'aes-256-gcm';

    public function __construct(
        #[Autowire(env: 'AUDIT_ENCRYPTION_KEY')]
        private readonly string $encryptionKey
    ) {}

    public function encryptChanges(array $changes): string
    {
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt(
            json_encode($changes),
            self::CIPHER,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return base64_encode($iv . $tag . $encrypted);
    }

    public function decryptChanges(string $encrypted): array
    {
        $data = base64_decode($encrypted);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);

        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $ciphertext = substr($data, $ivLength + 16);

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return json_decode($decrypted, true);
    }
}
```

**5.6 Add Tamper Detection**
```php
// src/Entity/AuditLog.php (add field)
#[ORM\Column(length: 64)]
private string $checksum;

// Generate on creation
public function generateChecksum(): void
{
    $data = json_encode([
        $this->action,
        $this->entityClass,
        $this->entityId->toString(),
        $this->changes,
        $this->createdAt->format(\DateTimeInterface::ATOM),
    ]);

    $this->checksum = hash('sha256', $data . $_ENV['AUDIT_SALT']);
}

// Verify integrity
public function verifyIntegrity(): bool
{
    $data = json_encode([
        $this->action,
        $this->entityClass,
        $this->entityId->toString(),
        $this->changes,
        $this->createdAt->format(\DateTimeInterface::ATOM),
    ]);

    $expectedChecksum = hash('sha256', $data . $_ENV['AUDIT_SALT']);

    return hash_equals($expectedChecksum, $this->checksum);
}
```

**5.7 Create Symfony Commands**
```php
// src/Command/AuditRetentionCommand.php
#[AsCommand(name: 'app:audit:retention')]
class AuditRetentionCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stats = $this->retentionService->enforceRetentionPolicies();

        foreach ($stats as $entity => $count) {
            $output->writeln("Deleted {$count} old {$entity} audit records");
        }

        return Command::SUCCESS;
    }
}

// src/Command/AuditVerifyCommand.php
#[AsCommand(name: 'app:audit:verify')]
class AuditVerifyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logs = $this->auditRepo->findAll();
        $tampered = 0;

        foreach ($logs as $log) {
            if (!$log->verifyIntegrity()) {
                $output->writeln("⚠️  TAMPERED: {$log->getId()}");
                $tampered++;
            }
        }

        $output->writeln($tampered === 0 ? '✅ All audit logs verified' : "❌ {$tampered} tampered records");

        return $tampered === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
```

**5.8 Add Cron Jobs**
```bash
# crontab
0 3 * * 0 cd /app && php bin/console app:audit:retention --env=prod
0 4 * * * cd /app && php bin/console app:audit:verify --env=prod
```

### Success Criteria
- ✅ Audit logs automatically deleted per retention policy
- ✅ User data anonymized after 2 years (GDPR compliance)
- ✅ SOC2 compliance reports generated automatically
- ✅ Sensitive audit data encrypted at rest
- ✅ Tamper detection alerts on integrity violations
- ✅ GDPR data export for any user in <1 second

### Testing Plan
1. Test retention policy enforcement
2. Verify anonymization doesn't break queries
3. Generate compliance reports for audit
4. Test encryption/decryption performance
5. Attempt to tamper with audit log and verify detection

---

## PHASE 6: Advanced Analytics & Monitoring
**Duration**: 10-14 days | **Priority**: LOW | **Risk**: Low

### Objective
Provide actionable insights from audit data through dashboards, anomaly detection, and predictive analytics for security and compliance teams.

### Deliverables

**6.1 Create Analytics Dashboard**
```php
// src/Controller/Admin/AuditAnalyticsController.php
#[Route('/admin/audit/analytics')]
class AuditAnalyticsController extends AbstractController
{
    #[Route('/', name: 'admin_audit_analytics')]
    public function index(AuditAnalyticsService $analytics): Response
    {
        return $this->render('admin/audit/analytics.html.twig', [
            'metrics' => [
                'events_today' => $analytics->getEventsToday(),
                'events_week' => $analytics->getEventsThisWeek(),
                'top_users' => $analytics->getTopActiveUsers(10),
                'top_entities' => $analytics->getMostModifiedEntities(10),
                'hourly_distribution' => $analytics->getHourlyDistribution(),
                'action_breakdown' => $analytics->getActionBreakdown(),
            ],
            'anomalies' => $analytics->detectAnomalies(),
        ]);
    }
}
```

**6.2 Implement Anomaly Detection**
```php
// src/Service/AuditAnalyticsService.php
class AuditAnalyticsService
{
    public function detectAnomalies(): array
    {
        $anomalies = [];

        // Detect unusual activity patterns
        $anomalies[] = $this->detectBulkOperations();
        $anomalies[] = $this->detectOffHoursActivity();
        $anomalies[] = $this->detectRapidChanges();
        $anomalies[] = $this->detectSuspiciousPatterns();

        return array_filter($anomalies);
    }

    private function detectBulkOperations(): ?array
    {
        // Find users performing >100 operations in 1 hour
        $threshold = 100;
        $window = new \DateTime('-1 hour');

        $results = $this->auditRepo->findHighVolumeUsers($window, $threshold);

        if (!empty($results)) {
            return [
                'type' => 'bulk_operations',
                'severity' => 'medium',
                'message' => 'Bulk operations detected',
                'users' => $results,
            ];
        }

        return null;
    }

    private function detectOffHoursActivity(): ?array
    {
        // Activity outside business hours (9am-6pm)
        $now = new \DateTime();
        $hour = (int) $now->format('H');

        if ($hour < 9 || $hour > 18) {
            $count = $this->auditRepo->countInLastHour();

            if ($count > 20) {
                return [
                    'type' => 'off_hours_activity',
                    'severity' => 'low',
                    'message' => "{$count} operations outside business hours",
                ];
            }
        }

        return null;
    }

    private function detectRapidChanges(): ?array
    {
        // Same entity modified >10 times in 5 minutes
        $window = new \DateTime('-5 minutes');

        $results = $this->auditRepo->findRapidlyChangingEntities($window, 10);

        if (!empty($results)) {
            return [
                'type' => 'rapid_changes',
                'severity' => 'high',
                'message' => 'Entity changed rapidly',
                'entities' => $results,
            ];
        }

        return null;
    }
}
```

**6.3 Create Charts with Chart.js**
```twig
{# templates/admin/audit/analytics.html.twig #}
<div class="row">
    <div class="col-md-6">
        <div class="infinity-card p-4">
            <h5>Activity by Hour</h5>
            <canvas id="hourlyChart"></canvas>
        </div>
    </div>
    <div class="col-md-6">
        <div class="infinity-card p-4">
            <h5>Action Breakdown</h5>
            <canvas id="actionChart"></canvas>
        </div>
    </div>
</div>

<script>
// Hourly distribution chart
new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: {{ metrics.hourly_distribution.hours|json_encode|raw }},
        datasets: [{
            label: 'Events',
            data: {{ metrics.hourly_distribution.counts|json_encode|raw }},
            backgroundColor: 'rgba(59, 130, 246, 0.5)',
        }]
    }
});

// Action breakdown pie chart
new Chart(document.getElementById('actionChart'), {
    type: 'pie',
    data: {
        labels: {{ metrics.action_breakdown.labels|json_encode|raw }},
        datasets: [{
            data: {{ metrics.action_breakdown.data|json_encode|raw }},
            backgroundColor: [
                'rgba(34, 197, 94, 0.5)',
                'rgba(59, 130, 246, 0.5)',
                'rgba(239, 68, 68, 0.5)',
            ]
        }]
    }
});
</script>
```

**6.4 Implement Alert System**
```php
// src/Service/AuditAlertService.php
class AuditAlertService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly SlackNotifier $slack,
        #[Autowire(service: 'monolog.logger.security')]
        private readonly LoggerInterface $securityLogger
    ) {}

    public function sendAnomalyAlert(array $anomaly): void
    {
        $severity = $anomaly['severity'];

        // Always log
        $this->securityLogger->warning('Anomaly detected', $anomaly);

        // Email for high severity
        if ($severity === 'high') {
            $this->sendEmail($anomaly);
        }

        // Slack for medium/high
        if (in_array($severity, ['medium', 'high'])) {
            $this->sendSlack($anomaly);
        }
    }

    private function sendEmail(array $anomaly): void
    {
        $email = (new Email())
            ->to($_ENV['SECURITY_EMAIL'])
            ->subject('🚨 Audit Anomaly Detected')
            ->html($this->renderTemplate($anomaly));

        $this->mailer->send($email);
    }

    private function sendSlack(array $anomaly): void
    {
        $message = new ChatMessage("🚨 Audit Anomaly: {$anomaly['message']}");

        $this->slack->send($message);
    }
}
```

**6.5 Create Scheduled Analytics Report**
```php
// src/Command/AuditAnalyticsReportCommand.php
#[AsCommand(name: 'app:audit:analytics:report')]
class AuditAnalyticsReportCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $report = $this->analyticsService->generateWeeklyReport();

        // Email to admins
        $this->reportService->emailReport($report, $this->getAdminEmails());

        // Save to database
        $this->reportService->saveReport($report);

        return Command::SUCCESS;
    }
}
```

**6.6 Add Predictive Analytics**
```php
// src/Service/PredictiveAnalyticsService.php
class PredictiveAnalyticsService
{
    public function predictNextWeekActivity(): array
    {
        // Analyze last 4 weeks
        $weeks = [];
        for ($i = 4; $i >= 1; $i--) {
            $from = new \DateTime("-{$i} weeks");
            $to = new \DateTime("-" . ($i - 1) . " weeks");
            $weeks[] = $this->auditRepo->countInPeriod($from, $to);
        }

        // Simple linear regression
        $prediction = $this->linearRegression($weeks);

        return [
            'predicted_events' => round($prediction),
            'trend' => $this->calculateTrend($weeks),
            'confidence' => $this->calculateConfidence($weeks),
        ];
    }

    private function linearRegression(array $data): float
    {
        $n = count($data);
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data as $i => $y) {
            $x = $i + 1;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        return $slope * ($n + 1) + $intercept;
    }
}
```

### Success Criteria
- ✅ Real-time analytics dashboard
- ✅ Anomaly detection with alerts
- ✅ Weekly automated reports
- ✅ Predictive analytics for capacity planning
- ✅ Visual charts and graphs
- ✅ Export analytics data

### Testing Plan
1. Generate synthetic audit data
2. Verify anomaly detection accuracy
3. Test alert delivery (email, Slack)
4. Validate prediction accuracy
5. Performance test with large datasets

---

## IMPLEMENTATION ROADMAP

### Week 1-2: Foundation (Phases 1 & 2)
- Days 1-3: Log rotation and compression
- Days 4-7: Async logging implementation
- Days 8-10: Database optimization and indexes
- Buffer: 2-4 days

### Week 3-4: Historical Tracking (Phase 3)
- Days 11-14: Audit table creation
- Days 15-17: Soft delete implementation
- Days 18-20: Repository query methods
- Buffer: 1-3 days

### Week 5-6: User Interface (Phase 4)
- Days 21-25: Controller and forms
- Days 26-30: Templates and UI
- Days 31-34: Export functionality
- Buffer: 2-4 days

### Week 7: Compliance (Phase 5)
- Days 35-38: Retention policies
- Days 39-41: Encryption and integrity
- Buffer: 1-2 days

### Week 8: Analytics (Phase 6 - Optional)
- Days 42-46: Dashboard and charts
- Days 47-50: Anomaly detection
- Days 51-56: Reports and alerts
- Buffer: 2-4 days

---

## METRICS & SUCCESS CRITERIA

### Performance Metrics
- Log write time: <10ms (async)
- Audit query time: <500ms (for 100k records)
- UI response time: <2s
- Export time: <30s (for 50k records)

### Reliability Metrics
- Log loss rate: <0.01%
- Uptime: 99.9%
- Tamper detection: 100%

### Compliance Metrics
- Retention enforcement: 100%
- GDPR request fulfillment: <24 hours
- Audit completeness: >99.9%

---

## RISK MITIGATION

### High Risks
1. **Performance Impact**: Mitigate with async logging (Phase 2)
2. **Data Loss**: Mitigate with message queue persistence
3. **Disk Space**: Mitigate with rotation and compression (Phase 1)

### Medium Risks
1. **Migration Complexity**: Mitigate with thorough testing in staging
2. **Query Performance**: Mitigate with proper indexes (Phase 2)
3. **UI Complexity**: Mitigate with iterative development

### Low Risks
1. **Encryption Overhead**: Acceptable for compliance
2. **Report Generation Time**: Run asynchronously
3. **Analytics Accuracy**: Improve over time with ML

---

## BUDGET & RESOURCES

### Development Time
- **Phase 1**: 24-40 hours
- **Phase 2**: 40-56 hours
- **Phase 3**: 56-80 hours
- **Phase 4**: 80-112 hours
- **Phase 5**: 40-56 hours
- **Phase 6**: 80-112 hours
- **Total**: 320-456 hours (8-11 weeks with 1 developer)

### Infrastructure
- Message Queue (RabbitMQ/Redis): $0 (existing)
- Additional disk space: ~100GB for 1 year (compressed)
- Database storage: ~10GB for audit_log table

---

## MAINTENANCE & MONITORING

### Daily
- Monitor queue depth
- Check for anomalies
- Verify disk space

### Weekly
- Review analytics reports
- Check retention enforcement
- Verify backup success

### Monthly
- Compliance audit
- Performance review
- Cost optimization

---

**END OF PLAN**

This comprehensive plan provides a clear roadmap from current state to enterprise-grade audit system with full compliance, analytics, and monitoring capabilities.

**Generated**: October 3, 2025
**Author**: Claude Code AI Assistant
**Repository**: https://github.com/GetAmaral/infinity2
