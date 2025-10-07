/**
 * Authentication Helper for E2E Tests
 * Provides reusable login functionality
 */

/**
 * Login as admin user
 * @param {import('@playwright/test').Page} page
 */
async function loginAsAdmin(page) {
  await page.goto('/login', { waitUntil: 'networkidle', timeout: 30000 });

  // Wait for form to be visible
  await page.waitForSelector('#email', { state: 'visible', timeout: 10000 });

  // Fill login form
  await page.fill('#email', 'admin@infinity.ai');
  await page.fill('#password', '1');

  // Submit form and wait for navigation
  await Promise.all([
    page.waitForNavigation({ timeout: 30000 }),
    page.click('button[type="submit"]')
  ]);

  // Verify we're logged in by checking URL or page content
  await page.waitForLoadState('networkidle', { timeout: 30000 });
}

/**
 * Check if user is logged in
 * @param {import('@playwright/test').Page} page
 * @returns {Promise<boolean>}
 */
async function isLoggedIn(page) {
  try {
    await page.waitForSelector('text=Welcome', { timeout: 2000 });
    return true;
  } catch {
    return false;
  }
}

/**
 * Logout current user
 * @param {import('@playwright/test').Page} page
 */
async function logout(page) {
  await page.goto('/logout');
  await page.waitForURL('/login');
}

module.exports = {
  loginAsAdmin,
  isLoggedIn,
  logout,
};
