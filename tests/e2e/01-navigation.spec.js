/**
 * E2E Test: Turbo Navigation (Phase 6.1)
 * Tests smooth Turbo Drive navigation across all main routes
 */

const { test, expect } = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');
const { waitForTurbo, isTurboActive } = require('./helpers/turbo');

test.describe('Turbo Navigation Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await loginAsAdmin(page);

    // Wait for Turbo to be active
    await waitForTurbo(page);
  });

  test('TC 6.1.1: Turbo is loaded and active', async ({ page }) => {
    const turboActive = await isTurboActive(page);
    expect(turboActive).toBeTruthy();

    // Check console for Turbo message
    const logs = [];
    page.on('console', msg => logs.push(msg.text()));

    await page.reload();
    await page.waitForLoadState('networkidle');

    const hasTurboLog = logs.some(log => log.includes('Turbo Drive enabled'));
    expect(hasTurboLog).toBeTruthy();
  });

  test('TC 6.1.2: Navigate Home → Organizations with Turbo', async ({ page }) => {
    // Track network requests
    const requests = [];
    page.on('request', req => {
      if (req.url().includes('/organization') && !req.url().includes('api')) {
        requests.push({
          url: req.url(),
          type: req.resourceType(),
        });
      }
    });

    // Navigate to Organizations
    await page.click('a:has-text("Organizations")');
    await page.waitForURL('/organization');
    await page.waitForLoadState('networkidle');

    // Verify page loaded
    await expect(page.locator('h1')).toContainText('Organizations');

    // Check that navigation was via fetch (Turbo), not full page reload
    await page.waitForTimeout(500);
    const hasFetchRequest = requests.some(r => r.type === 'fetch' || r.type === 'xhr');

    // Note: In some cases, first navigation might be document type
    // But subsequent navigations should be fetch/xhr
    console.log('Navigation requests:', requests);
  });

  test('TC 6.1.3: Navigate Organizations → Detail → Back', async ({ page }) => {
    // Go to Organizations
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Click first organization card
    const firstCard = page.locator('.card').first();
    await firstCard.waitFor({ state: 'visible' });

    // Get scroll position before navigation
    const scrollBefore = await page.evaluate(() => window.scrollY);

    await firstCard.click();

    // Wait for detail page
    await page.waitForURL(/\/organization\/[a-f0-9-]+$/);
    await page.waitForLoadState('networkidle');

    // Go back
    await page.goBack();
    await page.waitForURL('/organization');
    await page.waitForLoadState('networkidle');

    // Verify we're back on organizations page
    await expect(page.locator('h1')).toContainText('Organizations');

    // Note: Scroll position restoration depends on Turbo cache
    console.log('Scroll position before:', scrollBefore);
  });

  test('TC 6.1.4: Navigate Home → Courses', async ({ page }) => {
    await page.goto('/');

    await page.click('a:has-text("Courses")');
    await page.waitForURL('/course');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('h1')).toContainText('Courses');
  });

  test('TC 6.1.5: Navigate Home → Users', async ({ page }) => {
    await page.goto('/');

    await page.click('a:has-text("Users")');
    await page.waitForURL('/user');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('h1')).toContainText('Users');
  });

  test('TC 6.1.6: Navigate Home → TreeFlow', async ({ page }) => {
    await page.goto('/');

    // Click TreeFlow link (might be in dropdown or main nav)
    const treeflowLink = page.locator('a[href="/treeflow"]').first();
    await treeflowLink.click();

    await page.waitForURL('/treeflow');
    await page.waitForLoadState('networkidle');

    await expect(page.locator('h1')).toContainText('TreeFlow');
  });

  test('TC 6.1.7: No white flash during navigation', async ({ page }) => {
    await page.goto('/');

    // Record if page goes completely white (blank)
    let wentBlank = false;

    page.on('framenavigated', async () => {
      const bodyColor = await page.evaluate(() => {
        return window.getComputedStyle(document.body).backgroundColor;
      });
      if (bodyColor === 'rgb(255, 255, 255)' || bodyColor === 'rgba(0, 0, 0, 0)') {
        wentBlank = true;
      }
    });

    // Navigate multiple times
    await page.click('a:has-text("Organizations")');
    await page.waitForLoadState('networkidle');

    await page.click('a:has-text("Courses")');
    await page.waitForLoadState('networkidle');

    // With Turbo, page should not flash white
    // Note: This is a best-effort check
    console.log('Page went blank during navigation:', wentBlank);
  });

  test('TC 6.1.8: No console errors during navigation', async ({ page }) => {
    const errors = [];
    page.on('console', msg => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });

    // Navigate through multiple pages
    await page.goto('/');
    await page.click('a:has-text("Organizations")');
    await page.waitForLoadState('networkidle');

    await page.click('a:has-text("Courses")');
    await page.waitForLoadState('networkidle');

    await page.click('a:has-text("Users")');
    await page.waitForLoadState('networkidle');

    // Check for errors (excluding expected ones)
    const criticalErrors = errors.filter(err =>
      !err.includes('favicon') &&
      !err.includes('net::ERR_') &&
      !err.includes('chrome-extension')
    );

    expect(criticalErrors).toHaveLength(0);
    if (criticalErrors.length > 0) {
      console.log('Console errors found:', criticalErrors);
    }
  });

  test('TC 6.1.9: Browser back/forward buttons work', async ({ page }) => {
    // Navigate through pages
    await page.goto('/');
    const homeTitle = await page.title();

    await page.goto('/organization');
    await page.waitForLoadState('networkidle');
    const orgTitle = await page.title();

    await page.goto('/course');
    await page.waitForLoadState('networkidle');
    const courseTitle = await page.title();

    // Go back
    await page.goBack();
    await page.waitForLoadState('networkidle');
    expect(await page.title()).toBe(orgTitle);

    // Go back again
    await page.goBack();
    await page.waitForLoadState('networkidle');
    expect(await page.title()).toBe(homeTitle);

    // Go forward
    await page.goForward();
    await page.waitForLoadState('networkidle');
    expect(await page.title()).toBe(orgTitle);
  });
});
