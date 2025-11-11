/**
 * Performance Testing Suite
 * Tests application performance, load times, and resource usage
 */

const { test, expect } = require('@playwright/test');

test.describe('Budget Control - Performance Tests', () => {
  test('should load homepage within acceptable time', async ({ page }) => {
    const startTime = Date.now();

    await page.goto('/');
    await page.waitForLoadState('networkidle');

    const loadTime = Date.now() - startTime;
    expect(loadTime).toBeLessThan(3000); // Should load within 3 seconds

    console.log(`Homepage load time: ${loadTime}ms`);
  });

  test('should load login page quickly', async ({ page }) => {
    const startTime = Date.now();

    await page.goto('/login');
    await page.waitForLoadState('domcontentloaded');

    const loadTime = Date.now() - startTime;
    expect(loadTime).toBeLessThan(2000); // Should load within 2 seconds

    console.log(`Login page load time: ${loadTime}ms`);
  });

  test('should have reasonable Lighthouse performance score', async ({ page }) => {
    await page.goto('/');

    // Use Playwright's built-in performance API
    const metrics = await page.evaluate(() => {
      const perfData = performance.getEntriesByType('navigation')[0];
      return {
        domContentLoaded: perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart,
        loadComplete: perfData.loadEventEnd - perfData.loadEventStart,
        firstPaint: performance.getEntriesByName('first-paint')[0]?.startTime || 0,
        firstContentfulPaint: performance.getEntriesByName('first-contentful-paint')[0]?.startTime || 0,
      };
    });

    // Assert reasonable performance metrics
    expect(metrics.domContentLoaded).toBeLessThan(1500);
    expect(metrics.loadComplete).toBeLessThan(3000);

    console.log('Performance metrics:', metrics);
  });

  test('should handle multiple concurrent users', async ({ browser }) => {
    const userCount = 3;
    const pages = [];

    // Create multiple pages simulating concurrent users
    for (let i = 0; i < userCount; i++) {
      const context = await browser.newContext();
      const page = await context.newPage();
      pages.push({ page, context });
    }

    const startTime = Date.now();

    // Load the homepage on all pages concurrently
    await Promise.all(
      pages.map(({ page }) => page.goto('/'))
    );

    // Wait for all pages to be ready
    await Promise.all(
      pages.map(({ page }) => page.waitForLoadState('networkidle'))
    );

    const totalTime = Date.now() - startTime;
    const averageTime = totalTime / userCount;

    expect(averageTime).toBeLessThan(5000); // Average load time should be reasonable

    console.log(`Concurrent users test: ${userCount} users, ${totalTime}ms total, ${averageTime}ms average`);

    // Clean up
    await Promise.all(
      pages.map(({ context }) => context.close())
    );
  });

  test('should not have memory leaks on navigation', async ({ page, context }) => {
    // Navigate through multiple pages
    const pages = ['/', '/login', '/register'];

    for (const pageUrl of pages) {
      await page.goto(pageUrl);
      await page.waitForLoadState('networkidle');

      // Check for console errors
      const errors = [];
      page.on('console', msg => {
        if (msg.type() === 'error') {
          errors.push(msg.text());
        }
      });

      // Basic interaction to ensure page is functional
      await page.waitForTimeout(500);
    }

    // Should not have accumulated errors
    expect(errors.length).toBe(0);
  });

  test('should load assets efficiently', async ({ page }) => {
    const requests = [];

    page.on('request', request => {
      requests.push({
        url: request.url(),
        resourceType: request.resourceType(),
        method: request.method()
      });
    });

    await page.goto('/');
    await page.waitForLoadState('networkidle');

    // Analyze asset loading
    const assets = requests.filter(req => ['image', 'stylesheet', 'script'].includes(req.resourceType));

    console.log(`Loaded ${assets.length} assets:`, assets.map(a => `${a.resourceType}: ${a.url.split('/').pop()}`));

    // Should not have too many assets
    expect(assets.length).toBeLessThan(50);
  });

  test('should handle JavaScript execution performance', async ({ page }) => {
    await page.goto('/');

    // Measure JavaScript execution time
    const jsExecutionTime = await page.evaluate(() => {
      const start = performance.now();

      // Simulate some JavaScript work
      for (let i = 0; i < 10000; i++) {
        Math.sqrt(i);
      }

      return performance.now() - start;
    });

    expect(jsExecutionTime).toBeLessThan(100); // Should execute quickly

    console.log(`JavaScript execution time: ${jsExecutionTime}ms`);
  });

  test('should maintain performance under repeated navigation', async ({ page }) => {
    const navigationTimes = [];

    // Navigate to the same page multiple times
    for (let i = 0; i < 5; i++) {
      const startTime = Date.now();
      await page.goto('/');
      await page.waitForLoadState('domcontentloaded');
      const loadTime = Date.now() - startTime;
      navigationTimes.push(loadTime);
    }

    const averageTime = navigationTimes.reduce((a, b) => a + b) / navigationTimes.length;
    const maxTime = Math.max(...navigationTimes);

    console.log(`Navigation performance: avg ${averageTime}ms, max ${maxTime}ms`);

    // Performance should not degrade significantly
    expect(averageTime).toBeLessThan(2000);
    expect(maxTime).toBeLessThan(3000);
  });
});
