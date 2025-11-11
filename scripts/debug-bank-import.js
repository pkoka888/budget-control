const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  try {
    console.log('🔐 Logging in...');
    await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'test123');

    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ timeout: 10000 }).catch(() => {})
    ]);

    await page.waitForTimeout(2000);
    console.log('✅ Logged in');

    // Navigate to bank import page
    console.log('📋 Navigating to /bank-import...');
    await page.goto('http://localhost:8080/bank-import', { waitUntil: 'domcontentloaded' });

    // Get page HTML
    const html = await page.content();

    // Check for error
    if (html.includes('Error') || html.includes('404')) {
      console.log('❌ Page returned error');
      // Print relevant section
      const errorMatch = html.match(/<h1>.*?<\/h1>/);
      if (errorMatch) console.log('Error:', errorMatch[0]);
    } else {
      console.log('✅ Page loaded successfully');

      // Check what content is there
      const hasHeader = html.includes('Import Bank Data');
      const hasStats = html.includes('Imported Transactions');
      const hasFileList = html.includes('Available files');

      console.log('Has header:', hasHeader);
      console.log('Has statistics:', hasStats);
      console.log('Has file list:', hasFileList);

      // Print first 2000 chars to debug
      console.log('\nPage content (first 2000 chars):');
      console.log(html.substring(0, 2000));
    }

  } catch (error) {
    console.error('❌ Error:', error.message);
  } finally {
    await browser.close();
  }
})();
