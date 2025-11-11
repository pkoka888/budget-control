// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * Enhanced Playwright configuration with comprehensive testing capabilities
 * See https://playwright.dev/docs/test-configuration.
 */
module.exports = defineConfig({
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 3 : 1,
  workers: process.env.CI ? 2 : undefined,

  // Enhanced reporting
  reporter: [
    ['html', { open: 'never' }],
    ['json', { outputFile: 'test-results/results.json' }],
    ['junit', { outputFile: 'test-results/junit.xml' }],
    process.env.CI ? ['github'] : ['list']
  ],

  timeout: 60000, // Increased timeout for complex tests
  expect: {
    timeout: 10000,
  },

  use: {
    baseURL: 'http://localhost:8080',
    trace: process.env.CI ? 'retain-on-failure' : 'on-first-retry',
    screenshot: 'only-on-failure',
    video: process.env.CI ? 'retain-on-failure' : 'off',
    actionTimeout: 15000,
    navigationTimeout: 30000,

    // Enhanced browser context
    viewport: { width: 1280, height: 720 },
    ignoreHTTPSErrors: true,

    // Permissions for testing
    permissions: ['geolocation', 'notifications'],

    // Enhanced launch options
    launchOptions: {
      slowMo: process.env.SLOW_MO ? 500 : 0,
    },
  },

  // Multiple projects for comprehensive testing
  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        contextOptions: {
          recordVideo: process.env.RECORD_VIDEO ? { dir: 'test-results/videos/' } : undefined,
        },
      },
    },

    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },

    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },

    // Mobile testing
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 5'] },
    },

    {
      name: 'mobile-safari',
      use: { ...devices['iPhone 12'] },
    },

    // Accessibility testing
    {
      name: 'accessibility',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '**/accessibility.spec.js',
    },

    // API testing
    {
      name: 'api',
      use: {
        baseURL: 'http://localhost:8080',
        extraHTTPHeaders: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      },
      testMatch: '**/api.spec.js',
    },

    // Performance testing
    {
      name: 'performance',
      use: { ...devices['Desktop Chrome'] },
      testMatch: '**/performance.spec.js',
    },
  ],

  // Global setup and teardown
  globalSetup: require.resolve('./tests/global-setup.js'),
  globalTeardown: require.resolve('./tests/global-teardown.js'),

  // Test output and artifacts
  outputDir: 'test-results/',

  // Web server configuration for automatic startup
  webServer: {
    command: 'docker-compose up -d',
    port: 8080,
    timeout: 120000,
    reuseExistingServer: !process.env.CI,
  },

  // Test metadata
  metadata: {
    version: process.env.npm_package_version,
    environment: process.env.NODE_ENV || 'development',
    timestamp: new Date().toISOString(),
  },
});
