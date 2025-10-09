<?php

namespace App\Tests\Turbo;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Turbo Compatibility Tests
 *
 * Tests to verify JavaScript controllers and handlers are Turbo-compatible.
 * Verifies that window.location usage has been replaced with Turbo.visit()
 */
class TurboCompatibilityTest extends WebTestCase
{
    /**
     * Test that delete-handler.js uses Turbo-compatible navigation
     */
    public function testDeleteHandlerUsesTurboNavigation(): void
    {
        $deleteHandlerPath = __DIR__ . '/../../assets/delete-handler.js';

        $this->assertFileExists($deleteHandlerPath, 'delete-handler.js should exist');

        $content = file_get_contents($deleteHandlerPath);

        // Check for Turbo-compatible code pattern
        $this->assertStringContainsString('typeof Turbo !== \'undefined\'', $content, 'delete-handler.js should check for Turbo');
        $this->assertStringContainsString('Turbo.visit', $content, 'delete-handler.js should use Turbo.visit()');
    }

    /**
     * Test that session monitor controller uses Turbo-compatible navigation
     */
    public function testSessionMonitorUsesTurboNavigation(): void
    {
        $controllerPath = __DIR__ . '/../../assets/controllers/session_monitor_controller.js';

        $this->assertFileExists($controllerPath, 'session_monitor_controller.js should exist');

        $content = file_get_contents($controllerPath);

        // Check for Turbo-compatible code pattern
        $this->assertStringContainsString('typeof Turbo !== \'undefined\'', $content, 'session_monitor_controller.js should check for Turbo');
        $this->assertStringContainsString('Turbo.visit', $content, 'session_monitor_controller.js should use Turbo.visit()');
    }

    /**
     * Test that TreeFlow canvas controller uses Turbo-compatible navigation
     */
    public function testTreeflowCanvasUsesTurboNavigation(): void
    {
        $controllerPath = __DIR__ . '/../../assets/controllers/treeflow_canvas_controller.js';

        $this->assertFileExists($controllerPath, 'treeflow_canvas_controller.js should exist');

        $content = file_get_contents($controllerPath);

        // Check for Turbo-compatible code pattern
        $this->assertStringContainsString('typeof Turbo !== \'undefined\'', $content, 'treeflow_canvas_controller.js should check for Turbo');
        $this->assertStringContainsString('Turbo.visit', $content, 'treeflow_canvas_controller.js should use Turbo.visit()');
    }

    /**
     * Test that module lecture reorder controller uses Turbo-compatible navigation
     */
    public function testModuleLectureReorderUsesTurboNavigation(): void
    {
        $controllerPath = __DIR__ . '/../../assets/controllers/module_lecture_reorder_controller.js';

        $this->assertFileExists($controllerPath, 'module_lecture_reorder_controller.js should exist');

        $content = file_get_contents($controllerPath);

        // Check for Turbo-compatible code pattern
        $this->assertStringContainsString('typeof Turbo !== \'undefined\'', $content, 'module_lecture_reorder_controller.js should check for Turbo');
        $this->assertStringContainsString('Turbo.visit', $content, 'module_lecture_reorder_controller.js should use Turbo.visit()');
    }

    /**
     * Test that lecture processing controller uses Turbo-compatible navigation
     */
    public function testLectureProcessingUsesTurboNavigation(): void
    {
        $controllerPath = __DIR__ . '/../../assets/controllers/lecture_processing_controller.js';

        $this->assertFileExists($controllerPath, 'lecture_processing_controller.js should exist');

        $content = file_get_contents($controllerPath);

        // Check for Turbo-compatible code pattern
        $this->assertStringContainsString('typeof Turbo !== \'undefined\'', $content, 'lecture_processing_controller.js should check for Turbo');
        $this->assertStringContainsString('Turbo.visit', $content, 'lecture_processing_controller.js should use Turbo.visit()');
    }

    /**
     * Test that enrollment switch controller uses Turbo-compatible navigation
     */
    public function testEnrollmentSwitchUsesTurboNavigation(): void
    {
        $controllerPath = __DIR__ . '/../../assets/controllers/enrollment_switch_controller.js';

        $this->assertFileExists($controllerPath, 'enrollment_switch_controller.js should exist');

        $content = file_get_contents($controllerPath);

        // Check for Turbo-compatible code pattern
        $this->assertStringContainsString('typeof Turbo !== \'undefined\'', $content, 'enrollment_switch_controller.js should check for Turbo');
        $this->assertStringContainsString('Turbo.visit', $content, 'enrollment_switch_controller.js should use Turbo.visit()');
    }

    /**
     * Test that course enrollment controller uses Turbo-compatible navigation
     */
    public function testCourseEnrollmentUsesTurboNavigation(): void
    {
        $controllerPath = __DIR__ . '/../../assets/controllers/course_enrollment_controller.js';

        $this->assertFileExists($controllerPath, 'course_enrollment_controller.js should exist');

        $content = file_get_contents($controllerPath);

        // Check for Turbo-compatible code pattern
        $this->assertStringContainsString('typeof Turbo !== \'undefined\'', $content, 'course_enrollment_controller.js should check for Turbo');
        $this->assertStringContainsString('Turbo.visit', $content, 'course_enrollment_controller.js should use Turbo.visit()');
    }

    /**
     * Test that CRUD modal controller uses Turbo-compatible navigation
     */
    public function testCrudModalUsesTurboNavigation(): void
    {
        $controllerPath = __DIR__ . '/../../assets/controllers/crud_modal_controller.js';

        $this->assertFileExists($controllerPath, 'crud_modal_controller.js should exist');

        $content = file_get_contents($controllerPath);

        // Check for Turbo-compatible code pattern
        $this->assertStringContainsString('typeof Turbo !== \'undefined\'', $content, 'crud_modal_controller.js should check for Turbo');
        $this->assertStringContainsString('Turbo.visit', $content, 'crud_modal_controller.js should use Turbo.visit()');
    }

    /**
     * Test that live search controller uses Turbo-compatible navigation
     */
    public function testLiveSearchUsesTurboNavigation(): void
    {
        $controllerPath = __DIR__ . '/../../assets/controllers/live_search_controller.js';

        $this->assertFileExists($controllerPath, 'live_search_controller.js should exist');

        $content = file_get_contents($controllerPath);

        // Check for Turbo-compatible code pattern
        $this->assertStringContainsString('typeof Turbo !== \'undefined\'', $content, 'live_search_controller.js should check for Turbo');
        $this->assertStringContainsString('Turbo.visit', $content, 'live_search_controller.js should use Turbo.visit()');
    }

    /**
     * Test that student lecture template has Turbo cleanup handlers
     */
    public function testStudentLectureTemplateHasCleanup(): void
    {
        $templatePath = __DIR__ . '/../../templates/student/lecture.html.twig';

        $this->assertFileExists($templatePath, 'student/lecture.html.twig should exist');

        $content = file_get_contents($templatePath);

        // Check for Turbo event listeners
        $this->assertStringContainsString('turbo:load', $content, 'Lecture template should listen to turbo:load');
        $this->assertStringContainsString('turbo:before-visit', $content, 'Lecture template should have cleanup on turbo:before-visit');

        // Check for video player cleanup
        $this->assertStringContainsString('Destroying video player', $content, 'Should destroy video player before navigation');
    }

    /**
     * Test that audit page template has Turbo cleanup handlers
     */
    public function testAuditTemplateHasCleanup(): void
    {
        $templatePath = __DIR__ . '/../../templates/admin/audit/index.html.twig';

        $this->assertFileExists($templatePath, 'admin/audit/index.html.twig should exist');

        $content = file_get_contents($templatePath);

        // Check for Turbo event listeners
        $this->assertStringContainsString('turbo:load', $content, 'Audit template should listen to turbo:load');
        $this->assertStringContainsString('turbo:before-visit', $content, 'Audit template should have cleanup on turbo:before-visit');

        // Check for interval cleanup
        $this->assertStringContainsString('clearInterval', $content, 'Should clear intervals before navigation');
    }

    /**
     * Test that organization users template has Turbo support
     */
    public function testOrganizationUsersTemplateHasTurboSupport(): void
    {
        $templatePath = __DIR__ . '/../../templates/organization/users.html.twig';

        $this->assertFileExists($templatePath, 'organization/users.html.twig should exist');

        $content = file_get_contents($templatePath);

        // Check for Turbo event listeners
        $this->assertStringContainsString('turbo:load', $content, 'Organization users template should listen to turbo:load');
    }

    /**
     * Test that login template has Turbo support
     */
    public function testLoginTemplateHasTurboSupport(): void
    {
        $templatePath = __DIR__ . '/../../templates/security/login.html.twig';

        $this->assertFileExists($templatePath, 'security/login.html.twig should exist');

        $content = file_get_contents($templatePath);

        // Check for Turbo event listeners
        $this->assertStringContainsString('turbo:load', $content, 'Login template should listen to turbo:load');
    }

    /**
     * Test that base entity list template has Turbo support
     */
    public function testBaseEntityListTemplateHasTurboSupport(): void
    {
        $templatePath = __DIR__ . '/../../templates/_base_entity_list.html.twig';

        $this->assertFileExists($templatePath, '_base_entity_list.html.twig should exist');

        $content = file_get_contents($templatePath);

        // Check for Turbo event listeners
        $this->assertStringContainsString('turbo:load', $content, 'Base entity list template should listen to turbo:load');
        $this->assertStringContainsString('turbo:before-cache', $content, 'Base entity list template should have cleanup handler');
        $this->assertStringContainsString('initializeEntityList', $content, 'Should have initialization function');
    }

    /**
     * Test that app.js imports Turbo
     */
    public function testAppJsImportsTurbo(): void
    {
        $appJsPath = __DIR__ . '/../../assets/app.js';

        $this->assertFileExists($appJsPath, 'app.js should exist');

        $content = file_get_contents($appJsPath);

        // Check that Turbo is imported
        $this->assertStringContainsString('import * as Turbo from \'@hotwired/turbo\'', $content, 'app.js should import Turbo');
        $this->assertStringContainsString('Turbo Drive enabled', $content, 'Should log that Turbo is enabled');
        $this->assertStringContainsString('Turbo.setProgressBarDelay', $content, 'Should configure Turbo progress bar');
    }

    /**
     * Test that app.css has Turbo progress bar styles
     */
    public function testAppCssHasTurboProgressBarStyles(): void
    {
        $appCssPath = __DIR__ . '/../../assets/styles/app.css';

        $this->assertFileExists($appCssPath, 'app.css should exist');

        $content = file_get_contents($appCssPath);

        // Check for Turbo progress bar styles
        $this->assertStringContainsString('.turbo-progress-bar', $content, 'app.css should have turbo-progress-bar styles');
        $this->assertStringContainsString('turbo-progress-glow', $content, 'Should have progress bar glow animation');
    }

    /**
     * Test that base template has Turbo cleanup handlers
     */
    public function testBaseTemplateHasTurboCleanup(): void
    {
        $templatePath = __DIR__ . '/../../templates/base.html.twig';

        $this->assertFileExists($templatePath, 'base.html.twig should exist');

        $content = file_get_contents($templatePath);

        // Check for Turbo cleanup handlers
        $this->assertStringContainsString('turbo:before-cache', $content, 'Base template should have turbo:before-cache handler');
        $this->assertStringContainsString('Cleaning up page before cache', $content, 'Should log cleanup');
        $this->assertStringContainsString('tooltip.dispose()', $content, 'Should dispose tooltips');
        $this->assertStringContainsString('.modal-backdrop', $content, 'Should remove modal backdrops');
        $this->assertStringContainsString('.dropdown-menu.show', $content, 'Should close open dropdowns');
    }

    /**
     * Test that base template has audit page exclusion
     */
    public function testBaseTemplateHasAuditExclusion(): void
    {
        $templatePath = __DIR__ . '/../../templates/base.html.twig';

        $this->assertFileExists($templatePath, 'base.html.twig should exist');

        $content = file_get_contents($templatePath);

        // Check for audit page exclusion
        $this->assertStringContainsString('admin_audit', $content, 'Base template should check for admin_audit route');
        $this->assertStringContainsString('turbo-visit-control', $content, 'Should have turbo-visit-control meta tag');
        $this->assertStringContainsString('turbo-cache-control', $content, 'Should have turbo-cache-control meta tag');
    }
}
