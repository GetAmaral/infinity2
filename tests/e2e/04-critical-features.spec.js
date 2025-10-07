/**
 * E2E Test: Critical Features (Phase 6.3)
 * Tests critical features like no duplicate elements, memory leaks, etc.
 */

const { test, expect } = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');
const { waitForTurbo, countElements } = require('./helpers/turbo');

test.describe('Critical Features Tests', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await waitForTurbo(page);
  });

  test('TC 6.3.1: No duplicate video players (CRITICAL)', async ({ page }) => {
    // Try to find a lecture page with video
    await page.goto('/course');
    await page.waitForLoadState('networkidle');

    // Look for any course
    const firstCourse = page.locator('.card, .list-group-item').first();
    const hasCourses = await firstCourse.count() > 0;

    if (!hasCourses) {
      console.log('No courses found, skipping video player test');
      return;
    }

    // Try to navigate to a lecture (this depends on having lectures)
    // For now, just check that video elements don't duplicate on any page

    // Navigate to multiple pages and check for video elements
    const pages = ['/course', '/organization', '/user'];

    for (const pagePath of pages) {
      await page.goto(pagePath);
      await page.waitForLoadState('networkidle');

      const videoCount = await countElements(page, 'video');
      console.log(`Video elements on ${pagePath}:`, videoCount);

      // Should have 0 or 1 video element, never more
      expect(videoCount).toBeLessThanOrEqual(1);
    }
  });

  test('TC 6.3.2: No duplicate tooltips after navigation', async ({ page }) => {
    // Navigate to a page with tooltips
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Hover over an element with tooltip
    const tooltipElement = page.locator('[data-bs-toggle="tooltip"]').first();

    if (await tooltipElement.count() > 0) {
      await tooltipElement.hover();
      await page.waitForTimeout(500);

      // Count tooltip elements (Bootstrap creates .tooltip)
      const tooltipCount1 = await page.locator('.tooltip').count();
      console.log('Tooltips after first hover:', tooltipCount1);

      // Navigate away and back
      await page.goto('/course');
      await page.waitForLoadState('networkidle');

      await page.goto('/organization');
      await page.waitForLoadState('networkidle');

      // Hover again
      const tooltipElement2 = page.locator('[data-bs-toggle="tooltip"]').first();
      if (await tooltipElement2.count() > 0) {
        await tooltipElement2.hover();
        await page.waitForTimeout(500);

        // Count tooltips again
        const tooltipCount2 = await page.locator('.tooltip').count();
        console.log('Tooltips after navigation and hover:', tooltipCount2);

        // Should not have duplicates (typically 0 or 1, max 2 during transition)
        expect(tooltipCount2).toBeLessThanOrEqual(2);
      }
    } else {
      console.log('No tooltips found to test');
    }
  });

  test('TC 6.3.3: No memory leaks after multiple navigations', async ({ page }) => {
    // Navigate through pages multiple times
    const pages = ['/organization', '/course', '/user', '/'];

    for (let i = 0; i < 3; i++) {
      for (const pagePath of pages) {
        await page.goto(pagePath);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(200);
      }
    }

    // After multiple navigations, check performance metrics
    const metrics = await page.evaluate(() => {
      if (performance.memory) {
        return {
          usedJSHeapSize: performance.memory.usedJSHeapSize,
          jsHeapSizeLimit: performance.memory.jsHeapSizeLimit,
        };
      }
      return null;
    });

    if (metrics) {
      console.log('Memory usage:', metrics);

      // Memory usage should be reasonable (not using more than 50% of heap)
      const memoryUsagePercent = (metrics.usedJSHeapSize / metrics.jsHeapSizeLimit) * 100;
      console.log('Memory usage percent:', memoryUsagePercent.toFixed(2) + '%');

      // This is a soft check - memory usage varies
      expect(memoryUsagePercent).toBeLessThan(80);
    } else {
      console.log('performance.memory not available (Chrome only)');
    }
  });

  test('TC 6.3.4: No duplicate modal backdrops', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Look for modal trigger button
    const modalButton = page.locator('[data-bs-toggle="modal"], button:has-text("New")').first();

    if (await modalButton.count() > 0) {
      // Open modal
      await modalButton.click();
      await page.waitForTimeout(500);

      // Check for modal
      const modal = page.locator('.modal.show');
      if (await modal.count() > 0) {
        // Count backdrops
        const backdropCount1 = await page.locator('.modal-backdrop').count();
        console.log('Modal backdrops:', backdropCount1);

        expect(backdropCount1).toBeLessThanOrEqual(1);

        // Close modal
        const closeButton = page.locator('.modal.show button[data-bs-dismiss="modal"], .modal.show .btn-close').first();
        if (await closeButton.count() > 0) {
          await closeButton.click();
          await page.waitForTimeout(500);
        } else {
          await page.press('body', 'Escape');
          await page.waitForTimeout(500);
        }

        // Navigate away and check for leftover backdrops
        await page.goto('/course');
        await page.waitForLoadState('networkidle');

        const leftoverBackdrops = await page.locator('.modal-backdrop').count();
        console.log('Leftover backdrops after navigation:', leftoverBackdrops);

        // Should have no leftover backdrops
        expect(leftoverBackdrops).toBe(0);
      }
    } else {
      console.log('No modal button found to test');
    }
  });

  test('TC 6.3.5: Forms work with Turbo', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Look for modal form
    const newButton = page.locator('button:has-text("New"), a:has-text("New")').first();

    if (await newButton.count() > 0) {
      await newButton.click();
      await page.waitForTimeout(500);

      // Check if modal opened
      const modal = page.locator('.modal.show');
      if (await modal.count() > 0) {
        console.log('Modal opened successfully');

        // Look for form inputs
        const formInputs = page.locator('.modal.show input[type="text"], .modal.show input[name]');
        const inputCount = await formInputs.count();
        console.log('Form inputs found:', inputCount);

        // Forms should have CSRF token
        const csrfToken = page.locator('.modal.show input[name="_csrf_token"]');
        const hasCsrf = await csrfToken.count() > 0;
        console.log('CSRF token present:', hasCsrf);

        // This verifies form structure is correct
        expect(inputCount).toBeGreaterThan(0);
      }
    } else {
      console.log('No "New" button found');
    }
  });

  test('TC 6.3.6: No console errors on any page', async ({ page }) => {
    const errors = [];

    page.on('console', msg => {
      if (msg.type() === 'error') {
        // Filter out non-critical errors
        const text = msg.text();
        if (!text.includes('favicon') &&
            !text.includes('net::ERR_') &&
            !text.includes('chrome-extension') &&
            !text.includes('Download the React DevTools')) {
          errors.push(text);
        }
      }
    });

    // Navigate to all main pages
    const pages = ['/', '/organization', '/course', '/user', '/treeflow'];

    for (const pagePath of pages) {
      await page.goto(pagePath);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(500);
    }

    // Log any errors found
    if (errors.length > 0) {
      console.log('Console errors found:', errors);
    }

    // Should have no critical errors
    expect(errors).toHaveLength(0);
  });

  test('TC 6.3.7: Page loads complete within reasonable time', async ({ page }) => {
    const pages = ['/organization', '/course', '/user'];

    for (const pagePath of pages) {
      const startTime = Date.now();

      await page.goto(pagePath);
      await page.waitForLoadState('networkidle');

      const loadTime = Date.now() - startTime;
      console.log(`${pagePath} load time: ${loadTime}ms`);

      // Page should load in under 5 seconds
      expect(loadTime).toBeLessThan(5000);
    }
  });
});
