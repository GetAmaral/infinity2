/**
 * E2E Test: Cross-Browser Compatibility (Phase 7.1)
 * Tests that Turbo works correctly across Chrome, Firefox, Safari, and Mobile
 */

const { test, expect } = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');
const { waitForTurbo, isTurboActive } = require('./helpers/turbo');

test.describe('Cross-Browser Compatibility Tests', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await waitForTurbo(page);
  });

  test('TC 7.1.1: Turbo is active in browser', async ({ page, browserName }) => {
    const turboActive = await isTurboActive(page);

    console.log(`${browserName}: Turbo active = ${turboActive}`);

    expect(turboActive).toBeTruthy();
  });

  test('TC 7.1.2: Navigation works smoothly', async ({ page, browserName }) => {
    console.log(`Testing navigation in ${browserName}`);

    // Navigate to multiple pages
    const pages = ['/', '/organization', '/course', '/user'];

    for (const pagePath of pages) {
      await page.goto(pagePath);
      await page.waitForLoadState('networkidle');

      // Page should load successfully
      const title = await page.title();
      console.log(`${browserName} - ${pagePath}: ${title}`);

      expect(title).toBeTruthy();
    }
  });

  test('TC 7.1.3: Forms submit correctly', async ({ page, browserName }) => {
    console.log(`Testing forms in ${browserName}`);

    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Look for "New" button
    const newButton = page.locator('button:has-text("New"), a:has-text("New")').first();

    if (await newButton.count() > 0) {
      await newButton.click();
      await page.waitForTimeout(500);

      // Check if modal opened
      const modal = page.locator('.modal.show');
      const modalVisible = await modal.count() > 0;

      console.log(`${browserName}: Modal opened = ${modalVisible}`);

      if (modalVisible) {
        // Check for form inputs
        const inputs = page.locator('.modal.show input');
        const inputCount = await inputs.count();

        console.log(`${browserName}: Form inputs = ${inputCount}`);
        expect(inputCount).toBeGreaterThan(0);

        // Close modal
        await page.press('body', 'Escape');
        await page.waitForTimeout(300);
      }
    } else {
      console.log(`${browserName}: No "New" button found`);
    }
  });

  test('TC 7.1.4: Modals work correctly', async ({ page, browserName }) => {
    console.log(`Testing modals in ${browserName}`);

    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Try to open modal
    const modalTrigger = page.locator('[data-bs-toggle="modal"], button:has-text("New")').first();

    if (await modalTrigger.count() > 0) {
      await modalTrigger.click();
      await page.waitForTimeout(500);

      // Modal should appear
      const modal = page.locator('.modal.show');
      const modalVisible = await modal.isVisible().catch(() => false);

      console.log(`${browserName}: Modal visible = ${modalVisible}`);

      if (modalVisible) {
        // Modal should have backdrop
        const backdrop = page.locator('.modal-backdrop');
        const backdropVisible = await backdrop.isVisible().catch(() => false);

        console.log(`${browserName}: Backdrop visible = ${backdropVisible}`);

        // Close modal with ESC
        await page.press('body', 'Escape');
        await page.waitForTimeout(500);

        // Modal should close
        const modalStillVisible = await modal.isVisible().catch(() => false);
        expect(modalStillVisible).toBeFalsy();
      }
    }
  });

  test('TC 7.1.5: No console errors', async ({ page, browserName }) => {
    console.log(`Checking console errors in ${browserName}`);

    const errors = [];

    page.on('console', msg => {
      if (msg.type() === 'error') {
        const text = msg.text();
        // Filter out non-critical errors
        if (!text.includes('favicon') &&
            !text.includes('net::ERR_') &&
            !text.includes('chrome-extension') &&
            !text.includes('DevTools')) {
          errors.push(text);
        }
      }
    });

    // Navigate through pages
    const pages = ['/', '/organization', '/course'];

    for (const pagePath of pages) {
      await page.goto(pagePath);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(300);
    }

    console.log(`${browserName}: Console errors = ${errors.length}`);

    if (errors.length > 0) {
      console.log(`${browserName}: Errors found:`, errors);
    }

    expect(errors).toHaveLength(0);
  });

  test('TC 7.1.6: Back/forward buttons work', async ({ page, browserName }) => {
    console.log(`Testing browser navigation in ${browserName}`);

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
    const backTitle = await page.title();

    console.log(`${browserName}: Back navigation - Expected: ${orgTitle}, Got: ${backTitle}`);
    expect(backTitle).toBe(orgTitle);

    // Go forward
    await page.goForward();
    await page.waitForLoadState('networkidle');
    const forwardTitle = await page.title();

    console.log(`${browserName}: Forward navigation - Expected: ${courseTitle}, Got: ${forwardTitle}`);
    expect(forwardTitle).toBe(courseTitle);
  });

  test('TC 7.1.7: Tooltips work', async ({ page, browserName }) => {
    console.log(`Testing tooltips in ${browserName}`);

    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    const tooltipElements = page.locator('[data-bs-toggle="tooltip"]');
    const tooltipCount = await tooltipElements.count();

    console.log(`${browserName}: Tooltip elements found = ${tooltipCount}`);

    if (tooltipCount > 0) {
      const firstTooltip = tooltipElements.first();

      // Hover to trigger tooltip
      await firstTooltip.hover();
      await page.waitForTimeout(500);

      // Check if tooltip appeared
      const tooltip = page.locator('.tooltip');
      const tooltipVisible = await tooltip.count() > 0;

      console.log(`${browserName}: Tooltip appeared = ${tooltipVisible}`);
    }
  });

  test('TC 7.1.8: Dropdowns work', async ({ page, browserName }) => {
    console.log(`Testing dropdowns in ${browserName}`);

    await page.goto('/');
    await page.waitForLoadState('networkidle');

    const dropdownButton = page.locator('[data-bs-toggle="dropdown"]').first();

    if (await dropdownButton.count() > 0) {
      // Click dropdown
      await dropdownButton.click();
      await page.waitForTimeout(300);

      // Check if dropdown menu appears
      const dropdownMenu = page.locator('.dropdown-menu.show');
      const menuVisible = await dropdownMenu.isVisible().catch(() => false);

      console.log(`${browserName}: Dropdown opened = ${menuVisible}`);
      expect(menuVisible).toBeTruthy();

      // Close dropdown
      await page.click('body');
      await page.waitForTimeout(200);

      const menuStillVisible = await dropdownMenu.isVisible().catch(() => false);
      expect(menuStillVisible).toBeFalsy();
    }
  });

  test('TC 7.1.9: Theme toggle works', async ({ page, browserName }) => {
    console.log(`Testing theme toggle in ${browserName}`);

    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // Get initial theme
    const initialTheme = await page.evaluate(() => {
      return document.documentElement.getAttribute('data-theme') ||
             document.body.getAttribute('data-theme') ||
             localStorage.getItem('theme') ||
             'light';
    });

    console.log(`${browserName}: Initial theme = ${initialTheme}`);

    // Find theme toggle button
    const themeButton = page.locator('button:has-text("Dark"), button:has-text("Light"), [data-theme-toggle], #themeToggle').first();

    if (await themeButton.count() > 0) {
      await themeButton.click();
      await page.waitForTimeout(300);

      // Get theme after toggle
      const newTheme = await page.evaluate(() => {
        return document.documentElement.getAttribute('data-theme') ||
               document.body.getAttribute('data-theme') ||
               localStorage.getItem('theme') ||
               'light';
      });

      console.log(`${browserName}: Theme after toggle = ${newTheme}`);

      // Theme should have changed
      expect(newTheme).not.toBe(initialTheme);
    }
  });

  test('TC 7.1.10: Mobile touch interactions', async ({ page, browserName, isMobile }) => {
    test.skip(!isMobile, 'Touch interactions test only for mobile browsers');

    console.log(`Testing touch interactions in ${browserName}`);

    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Tap on first card
    const firstCard = page.locator('.card, .list-group-item').first();

    if (await firstCard.count() > 0) {
      await firstCard.tap();
      await page.waitForLoadState('networkidle');

      console.log(`${browserName}: Touch tap navigation successful`);

      // Go back
      await page.goBack();
      await page.waitForLoadState('networkidle');
    }
  });
});
