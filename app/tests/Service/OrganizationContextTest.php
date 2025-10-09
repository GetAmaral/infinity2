<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Organization;
use App\Service\OrganizationContext;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Request;

final class OrganizationContextTest extends KernelTestCase
{
    private OrganizationContext $organizationContext;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $session = new Session(new MockArraySessionStorage());

        $request = new Request();
        $request->setSession($session);
        $this->requestStack->push($request);

        $this->organizationContext = new OrganizationContext($this->requestStack);
    }

    public function testExtractSlugFromSubdomain(): void
    {
        $slug = $this->organizationContext->extractSlugFromHost('acme-corporation.localhost');
        $this->assertEquals('acme-corporation', $slug);

        $slug = $this->organizationContext->extractSlugFromHost('stark-industries.localhost:8000');
        $this->assertEquals('stark-industries', $slug);
    }

    public function testExtractSlugFromRootDomain(): void
    {
        $slug = $this->organizationContext->extractSlugFromHost('localhost');
        $this->assertNull($slug);

        $slug = $this->organizationContext->extractSlugFromHost('localhost:8000');
        $this->assertNull($slug);
    }

    public function testSetAndGetOrganization(): void
    {
        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setSlug('test-org');

        $this->organizationContext->setOrganization($organization);
        $this->assertEquals('test-org', $this->organizationContext->getOrganizationSlug());
        $this->assertTrue($this->organizationContext->hasActiveOrganization());
    }

    public function testClearOrganization(): void
    {
        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setSlug('test-org');

        $this->organizationContext->setOrganization($organization);
        $this->assertTrue($this->organizationContext->hasActiveOrganization());

        $this->organizationContext->clearOrganization();
        $this->assertFalse($this->organizationContext->hasActiveOrganization());
        $this->assertNull($this->organizationContext->getOrganizationSlug());
    }
}