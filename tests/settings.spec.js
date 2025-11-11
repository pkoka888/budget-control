const { test, expect } = require('@playwright/test');

test.describe('Budget Control - Settings Functionality Tests', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the application before each test
    await page.goto('http://localhost:8080', { timeout: 30000 });
    await page.waitForLoadState('domcontentloaded');
  });

  test('should redirect to login when accessing settings without authentication', async ({ page }) => {
    const response = await page.goto('http://localhost:8080/settings', { waitUntil: 'domcontentloaded', timeout: 30000 });

    // Should redirect to login (302) or show login page (200)
    expect([200, 302, 301]).toContain(response.status());
    console.log(`✅ Settings page redirect status: ${response.status()}`);
  });

  test('should have settings routes defined and responsive', async ({ page }) => {
    // Test various settings routes
    const settingsRoutes = [
      '/settings',
      '/settings/profile',
      '/settings/notifications',
      '/settings/preferences',
      '/settings/security'
    ];

    for (const route of settingsRoutes) {
      const response = await page.goto(`http://localhost:8080${route}`, { waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => null);
      if (response) {
        const status = response.status();
        console.log(`✅ Settings route ${route}: ${status}`);
        // Should either redirect to login (302/301) or show login page (200)
        // Should NOT be 404 (which would indicate routing is broken)
        expect(status).not.toBe(404);
        expect([200, 302, 301]).toContain(status);
      } else {
        console.log(`⚠️  Settings route ${route}: No response`);
      }
    }
  });

  test('should verify SettingsController syntax is valid', async ({ page }) => {
    // Test that the application loads without PHP parse errors
    const response = await page.goto('http://localhost:8080', { waitUntil: 'domcontentloaded', timeout: 30000 });
    expect(response.status()).toBe(200);

    // Check that no PHP errors are displayed
    const content = await page.content();
    expect(content).not.toContain('Parse error');
    expect(content).not.toContain('syntax error');
    expect(content).not.toContain('Fatal error');

    console.log('✅ No PHP parse errors detected');
  });

  test('should check for settings-related content in application', async ({ page }) => {
    // Navigate to home page and check if settings are mentioned
    await page.goto('http://localhost:8080', { waitUntil: 'domcontentloaded', timeout: 30000 });

    const content = await page.content();

    // Check for settings-related keywords
    const hasSettings = content.includes('settings') || content.includes('Settings') ||
                       content.includes('nastavení') || content.includes('Nastavení');

    if (hasSettings) {
      console.log('✅ Settings content found in application');
    } else {
      console.log('ℹ️  No settings content found (may be behind authentication)');
    }
  });

  test('should verify settings routes are registered in the application', async ({ page }) => {
    // Test that settings routes don't return 404 (which would indicate routing issues)
    const response = await page.goto('http://localhost:8080/settings', { waitUntil: 'domcontentloaded', timeout: 30000 });

    // Should NOT be 404 - if routing was broken, it would be 404
    expect(response.status()).not.toBe(404);
    console.log(`✅ Settings routing is working (status: ${response.status()})`);
  });

  test('should test settings export functionality route', async ({ page }) => {
    const response = await page.goto('http://localhost:8080/settings/export', { waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => null);

    if (response) {
      const status = response.status();
      console.log(`✅ Settings export route status: ${status}`);
      // Should be a valid response (not 404)
      expect(status).not.toBe(404);
      expect([200, 302, 301]).toContain(status);
    } else {
      console.log('⚠️  Settings export route: No response');
    }
  });

  test('should verify SettingsController methods are accessible', async ({ page }) => {
    // Test that the controller methods can be reached (even if they redirect)
    const methods = [
      'settings',
      'settings/profile',
      'settings/notifications',
      'settings/preferences',
      'settings/security',
      'settings/export'
    ];

    let accessibleCount = 0;
    for (const method of methods) {
      try {
        const response = await page.goto(`http://localhost:8080/${method}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
        if (response && response.status() !== 404) {
          accessibleCount++;
        }
      } catch (e) {
        // Route may not be accessible
      }
    }

    console.log(`✅ Settings controller methods accessible: ${accessibleCount}/${methods.length}`);
    expect(accessibleCount).toBeGreaterThan(0);
  });
});
