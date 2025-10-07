/**
 * Turbo Helper Functions
 * Utilities for testing Turbo Drive navigation
 */

/**
 * Wait for Turbo to be loaded
 * @param {import('@playwright/test').Page} page
 */
async function waitForTurbo(page) {
  await page.waitForFunction(() => {
    return typeof window.Turbo !== 'undefined';
  }, { timeout: 5000 });
}

/**
 * Check if Turbo is active
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<boolean>}
 */
async function isTurboActive(page) {
  return await page.evaluate(() => {
    return typeof window.Turbo !== 'undefined';
  });
}

/**
 * Wait for Turbo navigation to complete
 * @param {import('@playwright/test').Page} page
 */
async function waitForTurboNavigation(page) {
  await page.waitForLoadState('networkidle');

  // Wait for turbo:load event
  await page.evaluate(() => {
    return new Promise(resolve => {
      document.addEventListener('turbo:load', () => resolve(), { once: true });
    });
  });
}

/**
 * Get last navigation type (fetch or document)
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<string|null>}
 */
async function getLastNavigationType(page) {
  const requests = [];

  page.on('request', request => {
    requests.push({
      url: request.url(),
      resourceType: request.resourceType(),
    });
  });

  // Return resource type of HTML requests
  const htmlRequest = requests.find(r => r.resourceType === 'document' || r.resourceType === 'fetch');
  return htmlRequest ? htmlRequest.resourceType : null;
}

/**
 * Check for console errors
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<Array>}
 */
async function getConsoleErrors(page) {
  const errors = [];

  page.on('console', msg => {
    if (msg.type() === 'error') {
      errors.push(msg.text());
    }
  });

  return errors;
}

/**
 * Check if Turbo progress bar appeared
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<boolean>}
 */
async function didProgressBarAppear(page) {
  try {
    await page.waitForSelector('.turbo-progress-bar', { timeout: 2000 });
    return true;
  } catch {
    return false;
  }
}

/**
 * Count elements on page
 * @param {import('@playwright/test').Page} page
 * @param {string} selector
 * @returns {Promise<number>}
 */
async function countElements(page, selector) {
  return await page.locator(selector).count();
}

module.exports = {
  waitForTurbo,
  isTurboActive,
  waitForTurboNavigation,
  getLastNavigationType,
  getConsoleErrors,
  didProgressBarAppear,
  countElements,
};
