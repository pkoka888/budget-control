const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // First, register the user if needed
    console.log('📝 Ensuring test user exists...');
    await page.goto('http://localhost:8080/register', { waitUntil: 'domcontentloaded' });

    // Try to register
    const hasEmailInput = await page.$('input[name="email"]');
    if (hasEmailInput) {
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'test123');
      await page.fill('input[name="name"]', 'Test User');

      // Try to submit - if user exists it will show error, if not it will redirect
      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ timeout: 5000 }).catch(() => {})
      ]);

      await page.waitForTimeout(500);
      console.log('✅ User registration/verification attempt completed');
    }

    // Now navigate to login
    console.log('🔐 Logging in...');
    await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded' });

    // Check if already logged in
    if (page.url().includes('/login')) {
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'test123');

      // Submit login form
      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ timeout: 10000 }).catch(() => {})
      ]);

      await page.waitForTimeout(1000);
    }

    const currentUrl = page.url();
    console.log('✅ After login, URL:', currentUrl);

    // Navigate to bank import page
    console.log('📋 Navigating to /bank-import...');
    await page.goto('http://localhost:8080/bank-import', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);

    const html = await page.content();

    if (html.includes('Import Bank Data')) {
      console.log('✅ Bank import page loaded successfully!');
      console.log('  - Contains "Import Bank Data" header');

      if (html.includes('Imported Transactions')) {
        console.log('  - Contains statistics section');
      }

      if (html.includes('Available JSON Files')) {
        console.log('  - Contains file list section');
      }

      if (html.includes('autoImportBtn')) {
        console.log('  - Contains auto-import button');
      }

      // Try to find JSON files
      const fileListMatch = html.match(/Available JSON Files<\/p>\s*<p[^>]*>(\d+)<\/p>/);
      if (fileListMatch) {
        console.log(`  - Found ${fileListMatch[1]} JSON files available`);
      }

    } else if (html.includes('Přihlášení')) {
      console.log('❌ Still on login page - authentication failed');
    } else if (html.includes('Error')) {
      const errorMatch = html.match(/<p>(.*?)<\/p>/);
      if (errorMatch) {
        console.log('❌ Page error:', errorMatch[1]);
      }
    } else {
      console.log('⚠️  Unexpected page content');
      console.log('First 800 chars:', html.substring(0, 800));
    }

  } catch (error) {
    console.error('❌ Error:', error.message);
  } finally {
    await browser.close();
  }
})();
