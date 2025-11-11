/**
 * Global setup for Playwright tests
 * Runs once before all test suites
 */

const { chromium } = require('@playwright/test');
const fs = require('fs').promises;
const path = require('path');

module.exports = async (config) => {
  console.log('🚀 Starting global test setup...');

  // Ensure test results directory exists
  const testResultsDir = path.join(process.cwd(), 'test-results');
  try {
    await fs.mkdir(testResultsDir, { recursive: true });
    console.log('✅ Test results directory created');
  } catch (error) {
    console.log('ℹ️ Test results directory already exists');
  }

  // Ensure screenshots directory exists
  const screenshotsDir = path.join(process.cwd(), 'tests', 'screenshots');
  try {
    await fs.mkdir(screenshotsDir, { recursive: true });
    console.log('✅ Screenshots directory created');
  } catch (error) {
    console.log('ℹ️ Screenshots directory already exists');
  }

  // Pre-warm browsers (optional)
  if (process.env.PREWARM_BROWSERS) {
    console.log('🔥 Pre-warming browsers...');
    const browser = await chromium.launch();
    const page = await browser.newPage();
    await page.goto('http://localhost:8080');
    await page.waitForLoadState('networkidle');
    await browser.close();
    console.log('✅ Browsers pre-warmed');
  }

  // Setup test database state if needed
  if (process.env.SETUP_TEST_DATA) {
    console.log('🗄️ Setting up test data...');
    // Add test data setup logic here
    console.log('✅ Test data setup complete');
  }

  console.log('🎯 Global setup complete!');
};
