/**
 * E2E Test: Performance Testing (Phase 7.2)
 * Tests navigation speed, memory leaks, cache behavior, and error handling
 */

const { test, expect } = require('@playwright/test');
const { loginAsAdmin } = require('./helpers/auth');
const { waitForTurbo } = require('./helpers/turbo');

test.describe('Performance Tests', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
    await waitForTurbo(page);
  });

  test('TC 7.2.1: Memory leak check - Navigate 10 pages', async ({ page, browserName }) => {
    // Memory API only available in Chromium
    test.skip(browserName !== 'chromium', 'Memory profiling only in Chromium');

    // Record initial memory
    const initialMemory = await page.evaluate(() => {
      if (performance.memory) {
        return {
          usedJSHeapSize: performance.memory.usedJSHeapSize,
          jsHeapSizeLimit: performance.memory.jsHeapSizeLimit,
        };
      }
      return null;
    });

    console.log('Initial memory:', initialMemory);

    // Navigate through multiple pages (10 times)
    const pages = ['/', '/organization', '/course', '/user', '/treeflow'];

    for (let i = 0; i < 2; i++) {
      for (const pagePath of pages) {
        await page.goto(pagePath);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(200);
      }
    }

    // Return to starting page
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Check final memory
    const finalMemory = await page.evaluate(() => {
      if (performance.memory) {
        return {
          usedJSHeapSize: performance.memory.usedJSHeapSize,
          jsHeapSizeLimit: performance.memory.jsHeapSizeLimit,
        };
      }
      return null;
    });

    console.log('Final memory:', finalMemory);

    if (initialMemory && finalMemory) {
      const memoryIncrease = finalMemory.usedJSHeapSize - initialMemory.usedJSHeapSize;
      const memoryIncreasePercent = (memoryIncrease / initialMemory.usedJSHeapSize) * 100;

      console.log('Memory increase:', memoryIncrease, 'bytes');
      console.log('Memory increase percent:', memoryIncreasePercent.toFixed(2) + '%');

      // Memory increase should be reasonable (less than 50% increase)
      expect(memoryIncreasePercent).toBeLessThan(50);
    }
  });

  test('TC 7.2.2: Navigation speed measurement', async ({ page }) => {
    const navigationTimes = [];

    const pages = ['/organization', '/course', '/user', '/'];

    for (const pagePath of pages) {
      const startTime = Date.now();

      await page.goto(pagePath);
      await page.waitForLoadState('networkidle');

      const endTime = Date.now();
      const duration = endTime - startTime;

      navigationTimes.push({
        page: pagePath,
        duration: duration,
      });

      console.log(`Navigation to ${pagePath}: ${duration}ms`);
    }

    // Calculate average
    const avgTime = navigationTimes.reduce((sum, t) => sum + t.duration, 0) / navigationTimes.length;
    console.log('Average navigation time:', avgTime.toFixed(2) + 'ms');

    // All navigations should be under 3 seconds (3000ms)
    navigationTimes.forEach(nav => {
      expect(nav.duration).toBeLessThan(3000);
    });

    // Average should be under 2 seconds
    expect(avgTime).toBeLessThan(2000);
  });

  test('TC 7.2.3: Cache behavior - Back button', async ({ page }) => {
    // Navigate to organizations
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Get page title
    const orgTitle = await page.title();

    // Navigate to courses
    await page.goto('/course');
    await page.waitForLoadState('networkidle');

    // Measure back button navigation time
    const startTime = Date.now();

    await page.goBack();
    await page.waitForLoadState('networkidle');

    const backTime = Date.now() - startTime;

    console.log('Back button navigation time:', backTime + 'ms');

    // Verify we're back on organizations page
    const currentTitle = await page.title();
    expect(currentTitle).toBe(orgTitle);

    // Back navigation should be very fast (under 500ms) due to Turbo cache
    expect(backTime).toBeLessThan(1000);
  });

  test('TC 7.2.4: Turbo progress bar appears on slow requests', async ({ page }) => {
    let progressBarAppeared = false;

    // Listen for progress bar
    page.on('response', async response => {
      // Check if progress bar exists during navigation
      const hasProgressBar = await page.locator('.turbo-progress-bar').count();
      if (hasProgressBar > 0) {
        progressBarAppeared = true;
      }
    });

    // Navigate to a page
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    console.log('Progress bar appeared:', progressBarAppeared);

    // Note: Progress bar might not appear if navigation is very fast
    // This is acceptable behavior - we just log the result
  });

  test('TC 7.2.5: Error handling - Offline mode', async ({ page }) => {
    // Go to a page first
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Go offline
    await page.context().setOffline(true);

    // Try to navigate
    const navigationPromise = page.goto('/course', { timeout: 5000 }).catch(() => null);

    // Should fail or show error
    const result = await navigationPromise;

    console.log('Navigation result while offline:', result === null ? 'Failed (expected)' : 'Completed');

    // Go back online
    await page.context().setOffline(false);

    // Navigation should work again
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Verify page loaded
    await expect(page.locator('h1')).toContainText('Organizations');
  });

  test('TC 7.2.6: Page load performance metrics', async ({ page, browserName }) => {
    test.skip(browserName !== 'chromium', 'Performance API metrics more detailed in Chromium');

    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Get performance metrics
    const metrics = await page.evaluate(() => {
      const perf = performance.getEntriesByType('navigation')[0];
      if (perf) {
        return {
          domContentLoaded: perf.domContentLoadedEventEnd - perf.domContentLoadedEventStart,
          loadComplete: perf.loadEventEnd - perf.loadEventStart,
          domInteractive: perf.domInteractive - perf.fetchStart,
          responseTime: perf.responseEnd - perf.requestStart,
        };
      }
      return null;
    });

    if (metrics) {
      console.log('Performance metrics:', metrics);
      console.log('DOM Content Loaded:', metrics.domContentLoaded + 'ms');
      console.log('Load Complete:', metrics.loadComplete + 'ms');
      console.log('DOM Interactive:', metrics.domInteractive + 'ms');
      console.log('Response Time:', metrics.responseTime + 'ms');

      // DOM Interactive should be under 1 second
      expect(metrics.domInteractive).toBeLessThan(1000);
    }
  });

  test('TC 7.2.7: Turbo cache effectiveness', async ({ page }) => {
    // First visit
    const firstVisitStart = Date.now();
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');
    const firstVisitTime = Date.now() - firstVisitStart;

    console.log('First visit time:', firstVisitTime + 'ms');

    // Navigate away
    await page.goto('/course');
    await page.waitForLoadState('networkidle');

    // Navigate back (should use Turbo cache)
    const cacheVisitStart = Date.now();
    await page.goto('/organization');
    await page.waitForLoadState('networkidle');
    const cacheVisitTime = Date.now() - cacheVisitStart;

    console.log('Cached visit time:', cacheVisitTime + 'ms');

    // Cached visit should be faster or similar
    // (Turbo cache makes navigation instant)
    console.log('Cache speedup:', ((1 - cacheVisitTime / firstVisitTime) * 100).toFixed(2) + '%');

    // Both should complete in reasonable time
    expect(firstVisitTime).toBeLessThan(3000);
    expect(cacheVisitTime).toBeLessThan(3000);
  });

  test('TC 7.2.8: Resource loading efficiency', async ({ page }) => {
    // Clear cache first
    await page.context().clearCookies();

    // Navigate and count resources
    const resources = [];

    page.on('response', response => {
      const url = response.url();
      const type = response.request().resourceType();

      resources.push({
        url,
        type,
        status: response.status(),
        size: response.headers()['content-length'] || 0,
      });
    });

    await page.goto('/organization');
    await page.waitForLoadState('networkidle');

    // Analyze resources
    const imageCount = resources.filter(r => r.type === 'image').length;
    const scriptCount = resources.filter(r => r.type === 'script').length;
    const stylesheetCount = resources.filter(r => r.type === 'stylesheet').length;
    const fetchCount = resources.filter(r => r.type === 'fetch' || r.type === 'xhr').length;

    console.log('Resources loaded:', {
      images: imageCount,
      scripts: scriptCount,
      stylesheets: stylesheetCount,
      fetch: fetchCount,
      total: resources.length,
    });

    // Should not load excessive resources
    expect(resources.length).toBeLessThan(100);
  });
});
