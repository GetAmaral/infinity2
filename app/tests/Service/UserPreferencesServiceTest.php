<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserPreferencesService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserPreferencesServiceTest extends TestCase
{
    private UserPreferencesService $preferencesService;
    private EntityManagerInterface $entityManager;
    private Security $security;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->preferencesService = new UserPreferencesService(
            $this->entityManager,
            $this->security,
            $this->logger
        );
    }

    public function testGetUserPreferencesWithNoUser(): void
    {
        $this->security->method('getUser')->willReturn(null);

        $preferences = $this->preferencesService->getUserPreferences();

        // Should return default preferences when no user
        $this->assertIsArray($preferences);
        $this->assertEquals('dark', $preferences['theme']);
        $this->assertEquals('en', $preferences['locale']);
        $this->assertEquals(25, $preferences['items_per_page']);
    }

    public function testGetUserPreferencesWithUser(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setUiSetting('theme', 'light');
        $user->setUiSetting('locale', 'pt_BR');

        $this->security->method('getUser')->willReturn($user);

        $preferences = $this->preferencesService->getUserPreferences();

        // Should merge user preferences with defaults
        $this->assertEquals('light', $preferences['theme']);
        $this->assertEquals('pt_BR', $preferences['locale']);
        $this->assertEquals(25, $preferences['items_per_page']); // Default value
    }

    public function testSaveUserPreferencesWithoutUser(): void
    {
        $this->security->method('getUser')->willReturn(null);

        $success = $this->preferencesService->saveUserPreferences(null, ['theme' => 'light']);

        $this->assertFalse($success);
    }

    public function testSaveUserPreferencesWithUser(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');

        $this->security->method('getUser')->willReturn($user);
        $this->entityManager->expects($this->once())->method('flush');

        $success = $this->preferencesService->saveUserPreferences(null, ['theme' => 'light']);

        $this->assertTrue($success);
        $this->assertEquals('light', $user->getUiSetting('theme'));
    }

    public function testSavePreference(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');

        $this->security->method('getUser')->willReturn($user);
        $this->entityManager->expects($this->once())->method('flush');

        $success = $this->preferencesService->savePreference('theme', 'light');

        $this->assertTrue($success);
        $this->assertEquals('light', $user->getUiSetting('theme'));
    }

    public function testGetPreference(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setUiSetting('theme', 'light');

        $this->security->method('getUser')->willReturn($user);

        $theme = $this->preferencesService->getPreference('theme');
        $unknownPref = $this->preferencesService->getPreference('unknown', 'default');

        $this->assertEquals('light', $theme);
        $this->assertEquals('default', $unknownPref);
    }

    public function testResetUserPreferences(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setUiSetting('theme', 'light');

        $this->security->method('getUser')->willReturn($user);
        $this->entityManager->expects($this->once())->method('flush');

        $success = $this->preferencesService->resetUserPreferences();

        $this->assertTrue($success);

        // Should have default preferences
        $preferences = $user->getUiSettings();
        $this->assertEquals('dark', $preferences['theme']);
        $this->assertEquals('en', $preferences['locale']);
    }

    public function testExportUserPreferences(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setUiSetting('theme', 'light');

        $this->security->method('getUser')->willReturn($user);

        $json = $this->preferencesService->exportUserPreferences();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('light', $decoded['theme']);
    }

    public function testImportUserPreferences(): void
    {
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');

        $this->security->method('getUser')->willReturn($user);
        $this->entityManager->expects($this->once())->method('flush');

        $json = json_encode(['theme' => 'light', 'locale' => 'pt_BR']);
        $success = $this->preferencesService->importUserPreferences($json);

        $this->assertTrue($success);
        $this->assertEquals('light', $user->getUiSetting('theme'));
        $this->assertEquals('pt_BR', $user->getUiSetting('locale'));
    }

    public function testImportInvalidJson(): void
    {
        $user = new User();
        $this->security->method('getUser')->willReturn($user);

        $success = $this->preferencesService->importUserPreferences('invalid json');

        $this->assertFalse($success);
    }
}