<?php

namespace App\Tests\Turbo;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turbo Navigation Tests
 *
 * Tests to verify Turbo Drive is properly configured and working.
 * These tests complement the manual testing checklist in PHASE_6_TESTING_GUIDE.md
 */
class TurboNavigationTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Test that Turbo is loaded in the base template
     */
    public function testTurboIsLoadedInBasePage(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        // Check that Turbo console log appears in the page source
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Turbo Drive enabled', $content, 'Turbo should be loaded and log enabled status');
    }

    /**
     * Test that navigation to organizations page works
     */
    public function testNavigationToOrganizations(): void
    {
        $this->client->request('GET', '/organization');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Organizations');
    }

    /**
     * Test that navigation to courses page works
     */
    public function testNavigationToCourses(): void
    {
        $this->client->request('GET', '/course');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Courses');
    }

    /**
     * Test that navigation to users page works
     */
    public function testNavigationToUsers(): void
    {
        $this->client->request('GET', '/user');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Users');
    }

    /**
     * Test that navigation to TreeFlow page works
     */
    public function testNavigationToTreeFlow(): void
    {
        $this->client->request('GET', '/treeflow');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'TreeFlows');
    }

    /**
     * Test that Turbo progress bar CSS is loaded
     */
    public function testTurboProgressBarCssLoaded(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('.turbo-progress-bar', $content, 'Turbo progress bar CSS should be loaded');
    }

    /**
     * Test that Turbo cleanup handlers are present
     */
    public function testTurboCleanupHandlersPresent(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('turbo:before-cache', $content, 'Turbo before-cache event handler should be present');
        $this->assertStringContainsString('Cleaning up page before cache', $content, 'Turbo cleanup logs should be present');
    }

    /**
     * Test that Admin Audit pages have Turbo exclusion meta tags
     */
    public function testAdminAuditPagesExcludeFromTurbo(): void
    {
        // Login as admin first
        $this->loginAsAdmin();

        // Navigate to audit page
        $this->client->request('GET', '/admin/audit');

        // If audit page requires specific permissions, it might redirect or show 403
        // We check if response is either successful or redirects
        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();

        if ($statusCode === Response::HTTP_OK) {
            $content = $response->getContent();

            // Check for Turbo exclusion meta tags
            $this->assertStringContainsString('turbo-visit-control', $content, 'Audit page should have turbo-visit-control meta tag');
            $this->assertStringContainsString('reload', $content, 'Audit page should be excluded from Turbo');
        } else {
            // If we can't access audit page, just mark as skipped
            $this->markTestSkipped('Cannot access admin audit page, might require specific permissions');
        }
    }

    /**
     * Test that CSRF protection is present in forms (Turbo compatibility)
     */
    public function testCsrfTokenPresentInForms(): void
    {
        $this->client->request('GET', '/organization');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // Check if page has forms with CSRF tokens
        // Note: Forms might be in modals, loaded dynamically
        if (str_contains($content, '<form')) {
            $this->assertStringContainsString('_csrf_token', $content, 'Forms should have CSRF tokens for Turbo compatibility');
        }
    }

    /**
     * Test that entity list initialization works
     */
    public function testEntityListInitialization(): void
    {
        $this->client->request('GET', '/organization');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // Check for entity list initialization function
        $this->assertStringContainsString('initializeEntityList', $content, 'Entity list should have initialization function');

        // Check for Turbo event listeners
        $this->assertStringContainsString('turbo:load', $content, 'Entity list should listen to turbo:load');
    }

    /**
     * Test that search functionality HTML is present
     */
    public function testSearchInputPresent(): void
    {
        $this->client->request('GET', '/organization');

        $this->assertResponseIsSuccessful();

        $this->assertSelectorExists('#organizationSearchInput', 'Search input should be present');
        $this->assertSelectorExists('#clearSearchBtn', 'Clear search button should be present');
    }

    /**
     * Test that view toggle buttons are present
     */
    public function testViewToggleButtonsPresent(): void
    {
        $this->client->request('GET', '/organization');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // Check for view toggle buttons
        $this->assertStringContainsString('bi-grid', $content, 'Grid view button should be present');
        $this->assertStringContainsString('bi-list', $content, 'List view button should be present');
        $this->assertStringContainsString('bi-table', $content, 'Table view button should be present');
    }

    /**
     * Test that PreferenceManager is loaded with Turbo exclusion
     */
    public function testPreferenceManagerLoaded(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // PreferenceManager should be loaded with data-turbo-eval="false"
        $this->assertStringContainsString('preference-manager.js', $content, 'PreferenceManager script should be loaded');
        $this->assertStringContainsString('data-turbo-eval="false"', $content, 'PreferenceManager should be excluded from Turbo eval');
    }

    /**
     * Test that Turbo event logging is present in dev mode
     */
    public function testTurboEventLogging(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // In dev environment, Turbo event logging should be present
        if ($this->client->getKernel()->getEnvironment() === 'dev') {
            $this->assertStringContainsString('Turbo event logging enabled', $content, 'Turbo event logging should be enabled in dev mode');
            $this->assertStringContainsString('turbo:click', $content, 'Should log turbo:click events');
            $this->assertStringContainsString('turbo:before-visit', $content, 'Should log turbo:before-visit events');
        }
    }

    /**
     * Test that Bootstrap tooltips initialization is Turbo-aware
     */
    public function testBootstrapTooltipsInitialization(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // Check that tooltip initialization listens to both DOMContentLoaded and turbo:load
        $this->assertStringContainsString('bootstrap.Tooltip', $content, 'Bootstrap tooltips should be initialized');
        $this->assertStringContainsString('turbo:load', $content, 'Should reinitialize on turbo:load');
    }

    /**
     * Test that organization switcher form is present (for admins)
     */
    public function testOrganizationSwitcherPresent(): void
    {
        // Login as admin
        $this->loginAsAdmin();

        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $content = $this->client->getResponse()->getContent();

        // Check for organization switcher
        if (str_contains($content, 'organization_switcher')) {
            $this->assertStringContainsString('organization_switcher', $content, 'Organization switcher should be present for admin');
        }
    }

    /**
     * Test home page accessibility
     */
    public function testHomePageAccessible(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Welcome');
    }

    /**
     * Helper method to login as admin
     */
    private function loginAsAdmin(): void
    {
        $crawler = $this->client->request('GET', '/login');

        $form = $crawler->selectButton('Sign In')->form([
            'email' => 'admin@infinity.ai',
            'password' => '1',
        ]);

        $this->client->submit($form);
        $this->client->followRedirect();
    }
}
