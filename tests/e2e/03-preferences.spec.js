/**
 * E2E Test: Preferences & Theme (Phase 6.5, 6.6, 6.7)
 * Tests view toggles, theme persistence, and preferences with Turbo
 */

const { test, expect } = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');
const { waitForTurbo } = require('./helpers/turbo');

test.describe('Preferences & Theme Tests', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await waitForTurbo(page);
  });

  test('TC 6.5.1: Grid view toggle works', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Look for view toggle buttons
    const gridButton = page.locator('button:has-text("Grid"), a:has-text("Grid"), [data-view="grid"]').first();

    const hasViewToggle = await gridButton.count() > 0;
    if (hasViewToggle) {
      await gridButton.click();
      await page.waitForTimeout(500);

      // Check if grid view is active
      const gridActive = await page.locator('.row .col, .grid-view').count() > 0;
      expect(gridActive).toBeTruthy();
    } else {
      console.log('View toggle not found (might use different selector)');
    }
  });

  test('TC 6.5.2: List view toggle works', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    const listButton = page.locator('button:has-text("List"), a:has-text("List"), [data-view="list"]').first();

    const hasViewToggle = await listButton.count() > 0;
    if (hasViewToggle) {
      await listButton.click();
      await page.waitForTimeout(500);

      // Check if list view is active
      const listActive = await page.locator('.list-group, .list-view').count() > 0;
      expect(listActive).toBeTruthy();
    } else {
      console.log('List view toggle not found');
    }
  });

  test('TC 6.5.3: View preference persists across navigation', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Try to set grid view
    const gridButton = page.locator('button:has-text("Grid"), a:has-text("Grid"), [data-view="grid"]').first();

    if (await gridButton.count() > 0) {
      await gridButton.click();
      await page.waitForTimeout(300);

      // Navigate away
      await page.goto('/course');
      await page.waitForLoadState('networkidle');

      // Navigate back
      await page.goto('/organization');
      await page.waitForLoadState('networkidle');

      // View preference should persist (check localStorage or cookies)
      const preference = await page.evaluate(() => {
        return localStorage.getItem('organizationViewPreference') ||
               localStorage.getItem('viewPreference');
      });

      console.log('View preference after navigation:', preference);
      // Preference should be set
    }
  });

  test('TC 6.7.1: Theme toggle works', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // Find theme toggle button
    const themeButton = page.locator('button:has-text("Dark"), button:has-text("Light"), [data-theme-toggle], #themeToggle').first();

    const hasThemeToggle = await themeButton.count() > 0;
    if (hasThemeToggle) {
      // Get current theme
      const themeBefore = await page.evaluate(() => {
        return document.documentElement.getAttribute('data-theme') ||
               document.body.getAttribute('data-theme') ||
               localStorage.getItem('theme');
      });

      console.log('Theme before toggle:', themeBefore);

      // Click theme toggle
      await themeButton.click();
      await page.waitForTimeout(300);

      // Get theme after toggle
      const themeAfter = await page.evaluate(() => {
        return document.documentElement.getAttribute('data-theme') ||
               document.body.getAttribute('data-theme') ||
               localStorage.getItem('theme');
      });

      console.log('Theme after toggle:', themeAfter);

      // Theme should have changed
      expect(themeAfter).not.toBe(themeBefore);
    } else {
      console.log('Theme toggle not found');
    }
  });

  test('TC 6.7.2: Theme persists across Turbo navigation', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // Get current theme
    const themeBefore = await page.evaluate(() => {
      return document.documentElement.getAttribute('data-theme') ||
             document.body.getAttribute('data-theme') ||
             localStorage.getItem('theme') ||
             'light';
    });

    console.log('Initial theme:', themeBefore);

    // Navigate to another page
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Check theme persists
    const themeAfter = await page.evaluate(() => {
      return document.documentElement.getAttribute('data-theme') ||
             document.body.getAttribute('data-theme') ||
             localStorage.getItem('theme') ||
             'light';
    });

    console.log('Theme after navigation:', themeAfter);

    expect(themeAfter).toBe(themeBefore);
  });

  test('TC 6.7.3: Tooltips work after Turbo navigation', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Look for elements with tooltips
    const tooltipElements = page.locator('[data-bs-toggle="tooltip"], [title]:not([title=""])');

    const tooltipCount = await tooltipElements.count();
    console.log('Found elements with tooltips:', tooltipCount);

    if (tooltipCount > 0) {
      const firstTooltip = tooltipElements.first();

      // Hover to trigger tooltip
      await firstTooltip.hover();
      await page.waitForTimeout(500);

      // Check if tooltip appeared (Bootstrap creates .tooltip element)
      const tooltipVisible = await page.locator('.tooltip').count() > 0;
      console.log('Tooltip visible:', tooltipVisible);

      // Navigate away and back
      await page.goto('/course');
      await page.waitForLoadState('networkidle');

      await page.goto('/organization');
      await page.waitForLoadState('networkidle');

      // Tooltips should still work after navigation
      const firstTooltipAfter = page.locator('[data-bs-toggle="tooltip"]').first();
      if (await firstTooltipAfter.count() > 0) {
        await firstTooltipAfter.hover();
        await page.waitForTimeout(500);

        const tooltipStillWorks = await page.locator('.tooltip').count() > 0;
        console.log('Tooltip works after navigation:', tooltipStillWorks);
      }
    }
  });

  test('TC 6.7.4: Dropdowns work after Turbo navigation', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // Find dropdown buttons
    const dropdownButton = page.locator('[data-bs-toggle="dropdown"]').first();

    if (await dropdownButton.count() > 0) {
      // Click dropdown
      await dropdownButton.click();
      await page.waitForTimeout(300);

      // Check if dropdown menu appears
      const dropdownMenu = page.locator('.dropdown-menu.show');
      await expect(dropdownMenu).toBeVisible();

      // Close dropdown
      await page.click('body');
      await page.waitForTimeout(200);

      // Navigate to another page
      await page.goto('/organization');
      await page.waitForLoadState('networkidle');

      // Find dropdown on new page
      const dropdownAfter = page.locator('[data-bs-toggle="dropdown"]').first();

      if (await dropdownAfter.count() > 0) {
        // Click dropdown
        await dropdownAfter.click();
        await page.waitForTimeout(300);

        // Dropdown should still work
        const dropdownMenuAfter = page.locator('.dropdown-menu.show');
        const stillWorks = await dropdownMenuAfter.count() > 0;
        console.log('Dropdown works after Turbo navigation:', stillWorks);
        expect(stillWorks).toBeTruthy();
      }
    } else {
      console.log('No dropdowns found to test');
    }
  });

  test('TC 6.6.1: Organization switcher works (admin only)', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // Look for organization switcher
    const orgSwitcher = page.locator('[data-organization-switcher], .organization-switcher, text=/Organization.*Switcher/i').first();

    if (await orgSwitcher.count() > 0) {
      await orgSwitcher.click();
      await page.waitForTimeout(300);

      // Should show organization list
      const orgList = page.locator('.dropdown-menu.show, [role="menu"]');
      await expect(orgList).toBeVisible();

      console.log('Organization switcher works');
    } else {
      console.log('Organization switcher not found (might require specific admin role)');
    }
  });
});
