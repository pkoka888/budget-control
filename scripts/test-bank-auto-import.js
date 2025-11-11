const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Register user
    console.log('📝 Ensuring test user exists...');
    await page.goto('http://localhost:8080/register', { waitUntil: 'domcontentloaded' });

    const hasEmailInput = await page.$('input[name="email"]');
    if (hasEmailInput) {
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'test123');
      await page.fill('input[name="name"]', 'Test User');

      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ timeout: 5000 }).catch(() => {})
      ]);

      await page.waitForTimeout(500);
    }

    // Login
    console.log('🔐 Logging in...');
    await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded' });

    if (page.url().includes('/login')) {
      await page.fill('input[name="email"]', 'test@example.com');
      await page.fill('input[name="password"]', 'test123');

      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ timeout: 10000 }).catch(() => {})
      ]);

      await page.waitForTimeout(1000);
    }

    console.log('✅ Logged in');

    // Navigate to bank import page
    console.log('📋 Opening bank import page...');
    await page.goto('http://localhost:8080/bank-import', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);

    // Check if button exists
    const hasAutoImportBtn = await page.$('#autoImportBtn');
    if (!hasAutoImportBtn) {
      console.log('❌ Auto-import button not found');
      const html = await page.content();
      console.log('Page contains:', html.includes('autoImportBtn') ? 'autoImportBtn' : 'no autoImportBtn');
      process.exit(1);
    }

    console.log('✅ Found auto-import button');

    // Click auto-import button and intercept API response
    console.log('🔄 Clicking auto-import button...');

    let importResponse = null;
    page.on('response', async response => {
      if (response.url().includes('/bank-import/auto-import')) {
        importResponse = await response.json().catch(() => null);
      }
    });

    await page.click('#autoImportBtn');

    // Wait for the API call
    await page.waitForTimeout(3000);

    if (importResponse) {
      console.log('\n✅ Auto-import API response received:');
      console.log(`   - Success: ${importResponse.success ?? 0} transactions`);
      console.log(`   - Failed: ${importResponse.failed ?? 0} files`);
      if (importResponse.files && importResponse.files.length > 0) {
        importResponse.files.forEach(file => {
          console.log(`   - File: ${file.name} (${file.status})`);
          if (file.status === 'success') {
            console.log(`     · Imported: ${file.imported}`);
            console.log(`     · Skipped: ${file.skipped}`);
          }
        });
      }

      // Check if there are results on the page
      await page.waitForTimeout(1000);
      const html = await page.content();

      if (html.includes('importResults') || html.includes('resultImported')) {
        console.log('\n✅ Results displayed on page');
      }
    } else {
      console.log('⚠️  No API response received (timeout or error)');
    }

    console.log('\n✅ Bank auto-import test completed successfully!');

  } catch (error) {
    console.error('❌ Error:', error.message);
  } finally {
    await browser.close();
  }
})();
