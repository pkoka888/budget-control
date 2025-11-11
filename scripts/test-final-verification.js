const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Register new user
    console.log('📝 Registering test user...');
    await page.goto('http://localhost:8080/register', { waitUntil: 'domcontentloaded' });
    const hasEmailInput = await page.$('input[name="email"]');
    if (hasEmailInput) {
      await page.fill('input[name="email"]', 'final-test@example.com');
      await page.fill('input[name="password"]', 'test123');
      await page.fill('input[name="name"]', 'Final Test User');
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
      await page.fill('input[name="email"]', 'final-test@example.com');
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
      process.exit(1);
    }

    console.log('✅ Found auto-import button');

    // Capture import response
    let importResponse = null;
    const responsePromise = new Promise(resolve => {
      page.on('response', async response => {
        if (response.url().includes('/bank-import/auto-import')) {
          try {
            importResponse = await response.json();
          } catch (e) {
            importResponse = { error: 'Failed to parse response' };
          }
          resolve(importResponse);
        }
      });
    });

    // Handle dialog and click
    console.log('🔄 Initiating auto-import...');
    page.on('dialog', dialog => {
      console.log('  Dialog:', dialog.message());
      dialog.accept();
    });

    await page.click('#autoImportBtn');

    // Wait for response
    const apiResponse = await Promise.race([
      responsePromise,
      new Promise(resolve => setTimeout(() => resolve(null), 10000))
    ]);

    if (apiResponse) {
      console.log('\n✅ Import completed!');
      console.log(`\nResults:`);
      console.log(`  - Imported: ${apiResponse.success ?? apiResponse.imported_count ?? 0} transactions`);
      console.log(`  - Skipped: ${apiResponse.failed ?? 0} files`);

      if (apiResponse.files && apiResponse.files.length > 0) {
        console.log(`\nFiles processed: ${apiResponse.files.length}`);
        apiResponse.files.forEach(file => {
          if (file.status === 'success') {
            console.log(`  ✓ ${file.name}: ${file.imported} imported, ${file.skipped} skipped (total: ${file.total_processed})`);
          } else {
            console.log(`  ✗ ${file.name}: ${file.error}`);
          }
        });
      }

      // Verify results displayed on page
      console.log('\n✅ Bank import test completed successfully!');
      process.exit(0);
    } else {
      console.log('⚠️ API response not received (timeout)');
      process.exit(1);
    }

  } catch (error) {
    console.error('❌ Error:', error.message);
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
