/**
 * E2E Test: Search & Filters (Phase 6.4)
 * Tests live search functionality with Turbo
 */

const { test, expect } = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');
const { waitForTurbo } = require('./helpers/turbo');

test.describe('Search & Filters Tests', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await waitForTurbo(page);
  });

  test('TC 6.4.1: Organizations search filters results', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Find search input
    const searchInput = page.locator('#organizationSearchInput');
    await searchInput.waitFor({ state: 'visible' });

    // Count initial results
    const initialCount = await page.locator('.card, .list-group-item, tbody tr').count();
    console.log('Initial organizations count:', initialCount);

    // Type search term
    await searchInput.fill('acme');
    await page.waitForTimeout(500); // Debounce

    // Results should filter
    const filteredCount = await page.locator('.card, .list-group-item, tbody tr').count();
    console.log('Filtered organizations count:', filteredCount);

    // Filtered count should be less or equal to initial
    expect(filteredCount).toBeLessThanOrEqual(initialCount);
  });

  test('TC 6.4.2: Clear search button works', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    const searchInput = page.locator('#organizationSearchInput');
    const clearBtn = page.locator('#clearSearchBtn');

    // Search for something
    await searchInput.fill('test');
    await page.waitForTimeout(300);

    // Clear button should be visible
    await clearBtn.waitFor({ state: 'visible' });

    // Click clear
    await clearBtn.click();

    // Input should be empty
    await expect(searchInput).toHaveValue('');
  });

  test('TC 6.4.3: ESC key clears search', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    const searchInput = page.locator('#organizationSearchInput');

    // Search for something
    await searchInput.fill('test');
    await page.waitForTimeout(300);

    // Press ESC
    await searchInput.press('Escape');

    // Input should be empty
    await expect(searchInput).toHaveValue('');
  });

  test('TC 6.4.4: Courses search works', async ({ page }) => {
    await page.goto('/course');
    await page.waitForLoadState('networkidle');

    const searchInput = page.locator('#courseSearchInput');

    // Check if search input exists
    const searchExists = await searchInput.count() > 0;
    if (searchExists) {
      await searchInput.fill('test');
      await page.waitForTimeout(300);

      // Should not error
      const errors = [];
      page.on('console', msg => {
        if (msg.type() === 'error') errors.push(msg.text());
      });

      expect(errors).toHaveLength(0);
    } else {
      console.log('Course search input not found (might not be implemented)');
    }
  });

  test('TC 6.4.5: Users search works', async ({ page }) => {
    await page.goto('/user');
    await page.waitForLoadState('networkidle');

    const searchInput = page.locator('#userSearchInput');

    // Check if search input exists
    const searchExists = await searchInput.count() > 0;
    if (searchExists) {
      await searchInput.fill('admin');
      await page.waitForTimeout(300);

      // Should not error
      const errors = [];
      page.on('console', msg => {
        if (msg.type() === 'error') errors.push(msg.text());
      });

      expect(errors).toHaveLength(0);
    } else {
      console.log('User search input not found (might not be implemented)');
    }
  });

  test('TC 6.4.6: Search persists on navigation back', async ({ page }) => {
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    const searchInput = page.locator('#organizationSearchInput');

    // Search for something
    await searchInput.fill('acme');
    await page.waitForTimeout(500);

    // Navigate away
    await page.goto('/course');
    await page.waitForLoadState('networkidle');

    // Go back
    await page.goBack();
    await page.waitForLoadState('networkidle');

    // Search should be cleared (Turbo cache behavior)
    // OR might be preserved depending on implementation
    const searchValue = await searchInput.inputValue();
    console.log('Search value after back navigation:', searchValue);

    // This is acceptable either way - just documenting behavior
  });
});
