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

    console.log('\n=== FULL PAGE HTML ===');
    console.log(html.substring(0, 3000));
    console.log('\n...\n');
    console.log(html.substring(html.length - 1000));

    if (html.includes('Error') || html.includes('Exception')) {
      console.log('\n❌ Page contains error');
    } else {
      console.log('\n✅ Page appears to render without errors');
    }

  } catch (error) {
    console.error('❌ Error:', error.message);
  } finally {
    await browser.close();
  }
})();
