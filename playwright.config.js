// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Playwright Configuration for Infinity Application
 * Testing Turbo Drive integration and all Phase 6 requirements
 */
module.exports = defineConfig({
  testDir: './tests/e2e',

  /* Run tests in files in parallel */
  fullyParallel: false, // Sequential for better stability

  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,

  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,

  /* Opt out of parallel tests on CI. */
  workers: process.env.CI ? 1 : 1,

  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['list'],
    ['json', { outputFile: 'playwright-results.json' }]
  ],

  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL: process.env.CI ? 'http://localhost:8000' : 'https://localhost',

    /* Collect trace when retrying the failed test. See https://playwright.dev/docs/trace-viewer */
    trace: 'on-first-retry',

    /* Screenshot on failure */
    screenshot: 'only-on-failure',

    /* Video on failure */
    video: 'retain-on-failure',

    /* Ignore HTTPS errors (self-signed cert) */
    ignoreHTTPSErrors: true,

    /* Timeout for each action */
    actionTimeout: 10000,

    /* Navigation timeout */
    navigationTimeout: 30000,
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1920, height: 1080 },
      },
    },
  ],

  /* Run your local dev server before starting the tests */
  webServer: {
    command: 'docker-compose ps app | grep -q "Up"',
    url: 'https://localhost',
    reuseExistingServer: true,
    ignoreHTTPSErrors: true,
    timeout: 10000,
  },
});
