<?php

declare(strict_types=1);

namespace App\Tests\Doctrine\Filter;

use App\Entity\Organization;
use App\Entity\User;
use App\Service\OrganizationContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class OrganizationFilterTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private OrganizationContext $organizationContext;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->organizationContext = $container->get(OrganizationContext::class);
    }

    public function testFilterIsRegistered(): void
    {
        $filters = $this->entityManager->getFilters();
        $this->assertTrue($filters->has('organization_filter'));
    }

    public function testFilterCanBeEnabled(): void
    {
        $filters = $this->entityManager->getFilters();

        if ($filters->isEnabled('organization_filter')) {
            $filters->disable('organization_filter');
        }

        $this->assertFalse($filters->isEnabled('organization_filter'));

        $filter = $filters->enable('organization_filter');
        $this->assertTrue($filters->isEnabled('organization_filter'));
        $this->assertNotNull($filter);
    }

    public function testFilterAcceptsParameter(): void
    {
        $filters = $this->entityManager->getFilters();
        $filter = $filters->enable('organization_filter');

        // Should not throw exception
        $filter->setParameter('organization_id', '019296b7-55be-72db-8cfd-f152c3ab0bd7', 'string');

        $this->assertTrue(true); // If we get here, test passed
    }
}