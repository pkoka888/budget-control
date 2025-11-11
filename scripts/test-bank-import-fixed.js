const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('🔐 Logging in...');
    await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'test123');

    // Click submit and wait for navigation
    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ timeout: 10000 }).catch(() => {})
    ]);

    await page.waitForTimeout(1000);
    console.log('✅ Logged in, current URL:', page.url());

    // Navigate to bank import page
    console.log('📋 Navigating to /bank-import...');
    await page.goto('http://localhost:8080/bank-import', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);

    const title = await page.title();
    console.log('Page title:', title);

    // Get page HTML to check content
    const html = await page.content();

    if (html.includes('Error') && html.includes('Exception')) {
      console.log('❌ Page returned error');
      const errorMatch = html.match(/<p>(.*?)<\/p>/);
      if (errorMatch) console.log('Error:', errorMatch[1]);
    } else if (html.includes('Import Bank Data') || html.includes('Imported Transactions')) {
      console.log('✅ Bank import page loaded successfully!');

      // Check specific elements
      const hasHeader = html.includes('Import Bank Data');
      const hasStats = html.includes('Imported Transactions');
      const hasFileList = html.includes('Available JSON Files');
      const hasAutoBtn = html.includes('autoImportBtn');

      console.log('  - Header found:', hasHeader);
      console.log('  - Statistics found:', hasStats);
      console.log('  - File list found:', hasFileList);
      console.log('  - Auto-import button found:', hasAutoBtn);
    } else {
      console.log('⚠️  Unexpected page content');
      console.log('First 500 chars:', html.substring(0, 500));
    }

  } catch (error) {
    console.error('❌ Error:', error.message);
  } finally {
    await browser.close();
  }
})();
