<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\User;
use App\Entity\Organization;
use App\EventSubscriber\AuditSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Test suite for AuditSubscriber functionality
 *
 * Tests automatic population of audit fields and logging behavior
 * for both authenticated and unauthenticated contexts.
 */
class AuditSubscriberTest extends TestCase
{
    private AuditSubscriber $auditSubscriber;
    private Security|MockObject $security;
    private LoggerInterface|MockObject $logger;
    private EntityManagerInterface|MockObject $entityManager;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->auditSubscriber = new AuditSubscriber($this->security, $this->logger);
    }

    public function testPrePersistWithAuthenticatedUser(): void
    {
        // Create test user and organization
        $currentUser = new User();
        $organization = new Organization();

        // Mock security to return the authenticated user
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser);

        // Mock the event args
        $eventArgs = $this->createMock(PrePersistEventArgs::class);
        $eventArgs
            ->expects($this->once())
            ->method('getObject')
            ->willReturn($organization);

        // Expect audit logging
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Audit event recorded', $this->arrayHasKey('action'));

        // Execute the event handler
        $this->auditSubscriber->prePersist($eventArgs);

        // Assert audit fields are set
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getUpdatedAt());
        $this->assertSame($currentUser, $organization->getCreatedBy());
        $this->assertSame($currentUser, $organization->getUpdatedBy());
    }

    public function testPrePersistWithoutAuthenticatedUser(): void
    {
        // Create test organization
        $organization = new Organization();

        // Mock security to return no user (unauthenticated context)
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        // Mock the event args
        $eventArgs = $this->createMock(PrePersistEventArgs::class);
        $eventArgs
            ->expects($this->once())
            ->method('getObject')
            ->willReturn($organization);

        // Expect audit logging with null user
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Audit event recorded', $this->logicalAnd(
                $this->arrayHasKey('action'),
                $this->arrayHasKey('user_id'),
                $this->callback(function ($data) {
                    return $data['user_id'] === null;
                })
            ));

        // Execute the event handler
        $this->auditSubscriber->prePersist($eventArgs);

        // Assert audit fields are set correctly
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getUpdatedAt());
        $this->assertNull($organization->getCreatedBy());
        $this->assertNull($organization->getUpdatedBy());
    }

    public function testPreUpdateWithAuthenticatedUser(): void
    {
        // Create test user and organization
        $currentUser = new User();
        $organization = new Organization();

        // Set initial audit values
        $initialTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $organization->setCreatedAt($initialTime);
        $organization->setUpdatedAt($initialTime);

        // Mock security to return the authenticated user
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser);

        // Mock change set
        $changeSet = [
            'name' => ['Old Name', 'New Name'],
            'description' => ['Old Description', 'New Description']
        ];

        // Mock the event args
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);
        $eventArgs
            ->expects($this->once())
            ->method('getObject')
            ->willReturn($organization);
        $eventArgs
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn($changeSet);

        // Expect audit logging with change set
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Audit event recorded', $this->logicalAnd(
                $this->arrayHasKey('action'),
                $this->arrayHasKey('changes'),
                $this->callback(function ($data) {
                    return $data['action'] === 'entity_updated' &&
                           isset($data['changes']['name']);
                })
            ));

        // Execute the event handler
        $this->auditSubscriber->preUpdate($eventArgs);

        // Assert updatedAt is changed and updatedBy is set
        $this->assertGreaterThan($initialTime, $organization->getUpdatedAt());
        $this->assertSame($currentUser, $organization->getUpdatedBy());

        // createdAt and createdBy should remain unchanged
        $this->assertEquals($initialTime, $organization->getCreatedAt());
    }

    public function testPreUpdateDoesNotAffectNonAuditableEntity(): void
    {
        // Create a non-auditable entity (plain object)
        $nonAuditableEntity = new \stdClass();

        // Mock the event args
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);
        $eventArgs
            ->expects($this->once())
            ->method('getObject')
            ->willReturn($nonAuditableEntity);

        // Security and logger should not be called
        $this->security
            ->expects($this->never())
            ->method('getUser');

        $this->logger
            ->expects($this->never())
            ->method('info');

        // Execute the event handler - should do nothing
        $this->auditSubscriber->preUpdate($eventArgs);

        // No assertions needed - test passes if no exceptions are thrown
        $this->assertTrue(true);
    }

    public function testSensitiveFieldsAreRedactedInLogs(): void
    {
        // Create test user
        $currentUser = new User();
        $user = new User();

        // Mock security
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser);

        // Mock change set with sensitive fields
        $changeSet = [
            'name' => ['Old Name', 'New Name'],
            'password' => ['old_hash', 'new_hash'],
            'apiToken' => ['old_token', 'new_token']
        ];

        // Mock the event args
        $eventArgs = $this->createMock(PreUpdateEventArgs::class);
        $eventArgs
            ->expects($this->once())
            ->method('getObject')
            ->willReturn($user);
        $eventArgs
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->willReturn($changeSet);

        // Expect audit logging with redacted sensitive fields
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Audit event recorded', $this->callback(function ($data) {
                // Check that sensitive fields are redacted
                return isset($data['changes']['password']) &&
                       $data['changes']['password'] === ['[REDACTED]', '[REDACTED]'] &&
                       isset($data['changes']['apiToken']) &&
                       $data['changes']['apiToken'] === ['[REDACTED]', '[REDACTED]'] &&
                       isset($data['changes']['name']) &&
                       $data['changes']['name'] === ['Old Name', 'New Name'];
            }));

        // Execute the event handler
        $this->auditSubscriber->preUpdate($eventArgs);
    }

    public function testAuditLogContainsExpectedFields(): void
    {
        // Create test data
        $currentUser = new User();
        $organization = new Organization();

        // Mock security
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($currentUser);

        // Mock the event args
        $eventArgs = $this->createMock(PrePersistEventArgs::class);
        $eventArgs
            ->expects($this->once())
            ->method('getObject')
            ->willReturn($organization);

        // Expect audit log with all required fields
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('Audit event recorded', $this->callback(function ($data) {
                $requiredFields = [
                    'action', 'entity_class', 'entity_id', 'user_id',
                    'user_email', 'timestamp', 'ip_address', 'user_agent'
                ];

                foreach ($requiredFields as $field) {
                    if (!array_key_exists($field, $data)) {
                        return false;
                    }
                }

                return $data['action'] === 'entity_created' &&
                       $data['entity_class'] === Organization::class;
            }));

        // Execute the event handler
        $this->auditSubscriber->prePersist($eventArgs);
    }
}