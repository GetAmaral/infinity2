<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Test Audit Controller functionality
 */
class AuditControllerTest extends WebTestCase
{
    public function testAuditIndexRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/audit/');

        // Should redirect to login
        $this->assertResponseRedirects('/login');
    }

    public function testAuditIndexWithAdminUser(): void
    {
        $client = static::createClient();

        // Find admin user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@infinity.local']);

        if (!$adminUser) {
            $this->markTestSkipped('Admin user not found');
        }

        // Login as admin
        $client->loginUser($adminUser);

        // Access audit index
        $client->request('GET', '/admin/audit/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Audit Log');
    }

    public function testAuditIndexShowsAuditLogs(): void
    {
        $client = static::createClient();

        // Find admin user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@infinity.local']);

        if (!$adminUser) {
            $this->markTestSkipped('Admin user not found');
        }

        // Login as admin
        $client->loginUser($adminUser);

        // Check if there are audit logs
        $auditLogRepository = static::getContainer()->get(AuditLogRepository::class);
        $auditLogs = $auditLogRepository->findRecent(10);

        if (empty($auditLogs)) {
            $this->markTestSkipped('No audit logs found');
        }

        // Access audit index with default (recent) logs
        $client->request('GET', '/admin/audit/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
        $this->assertSelectorTextContains('h5', 'Audit Events');
    }

    public function testEntityTimelineAccess(): void
    {
        $client = static::createClient();

        // Find admin user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@infinity.local']);

        if (!$adminUser) {
            $this->markTestSkipped('Admin user not found');
        }

        // Get an audit log to test with
        $auditLogRepository = static::getContainer()->get(AuditLogRepository::class);
        $auditLogs = $auditLogRepository->findRecent(1);

        if (empty($auditLogs)) {
            $this->markTestSkipped('No audit logs found');
        }

        $auditLog = $auditLogs[0];

        // Login as admin
        $client->loginUser($adminUser);

        // Access entity timeline
        $url = sprintf(
            '/admin/audit/entity/%s/%s',
            urlencode($auditLog->getEntityClass()),
            $auditLog->getEntityId()->toString()
        );

        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Entity Audit Timeline');
    }

    public function testUserActionsAccess(): void
    {
        $client = static::createClient();

        // Find admin user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@infinity.local']);

        if (!$adminUser) {
            $this->markTestSkipped('Admin user not found');
        }

        // Login as admin
        $client->loginUser($adminUser);

        // Access user actions
        $url = sprintf('/admin/audit/user/%s', $adminUser->getId()->toString());

        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'User Audit Actions');
    }

    public function testExportCsvAccess(): void
    {
        $client = static::createClient();

        // Find admin user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@infinity.local']);

        if (!$adminUser) {
            $this->markTestSkipped('Admin user not found');
        }

        // Login as admin
        $client->loginUser($adminUser);

        // Access CSV export
        $client->request('GET', '/admin/audit/export?format=csv');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertResponseHeaderMatches('Content-Disposition', '/attachment; filename="audit_export_/');
    }

    public function testExportJsonAccess(): void
    {
        $client = static::createClient();

        // Find admin user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@infinity.local']);

        if (!$adminUser) {
            $this->markTestSkipped('Admin user not found');
        }

        // Login as admin
        $client->loginUser($adminUser);

        // Access JSON export
        $client->request('GET', '/admin/audit/export?format=json');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertResponseHeaderMatches('Content-Disposition', '/attachment; filename="audit_export_/');

        // Verify JSON structure
        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('export_date', $data);
        $this->assertArrayHasKey('total_records', $data);
        $this->assertArrayHasKey('data', $data);
    }

    public function testSearchFormSubmission(): void
    {
        $client = static::createClient();

        // Find admin user
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneBy(['email' => 'admin@infinity.local']);

        if (!$adminUser) {
            $this->markTestSkipped('Admin user not found');
        }

        // Login as admin
        $client->loginUser($adminUser);

        // Submit search form
        $crawler = $client->request('GET', '/admin/audit/');

        // Find and submit the search form
        $form = $crawler->filter('form')->form([
            'audit_search[action]' => 'entity_updated',
        ]);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
    }
}
