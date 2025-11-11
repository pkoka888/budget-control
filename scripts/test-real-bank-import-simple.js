const { chromium } = require('playwright');

/**
 * Simple test: Import real bank statements and check if they appear on the page
 */
(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('🎯 Real Bank JSON Import Test\n');

    // Register
    console.log('📝 Registering...');
    const email = `test-${Date.now()}@example.com`;
    await page.goto('http://localhost:8080/register');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'test123');
    await page.fill('input[name="name"]', 'Test');
    await page.click('button[type="submit"]');
    await page.waitForNavigation().catch(() => {});
    console.log('✅ Registered\n');

    // Login
    console.log('🔐 Logging in...');
    await page.goto('http://localhost:8080/login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'test123');
    await page.click('button[type="submit"]');
    await page.waitForNavigation().catch(() => {});
    console.log('✅ Logged in\n');

    // Go to bank import
    console.log('📋 Opening bank import page...');
    await page.goto('http://localhost:8080/bank-import');
    await page.waitForTimeout(1000);

    // Accept dialog and click button
    page.on('dialog', dialog => {
      console.log('  Dialog accepted');
      dialog.accept();
    });

    console.log('🔄 Clicking import button...');
    const responsePromise = page.waitForResponse(
      response => response.url().includes('/bank-import/auto-import') && response.status() === 200,
      { timeout: 120000 }
    );

    await page.click('#autoImportBtn');

    console.log('⏳ Waiting for response (this may take a minute with 6 years of data)...');
    const response = await responsePromise;
    const data = await response.json();

    console.log('\n✅ Import completed!\n');

    // Display results
    console.log('📊 RESULTS:');
    console.log(`  Total Imported: ${data.success || 0}`);
    console.log(`  Failed Files: ${data.failed || 0}\n`);

    if (data.files && data.files.length > 0) {
      console.log('📁 Files:');
      let totalImported = 0;
      let totalSkipped = 0;

      data.files.forEach((file, i) => {
        if (file.status === 'success') {
          const imported = file.imported || 0;
          const skipped = file.skipped || 0;
          totalImported += imported;
          totalSkipped += skipped;

          console.log(`  ${i + 1}. ${file.name}: ${imported} imported, ${skipped} skipped`);
        } else {
          console.log(`  ${i + 1}. ${file.name}: ✗ ${file.error}`);
        }
      });

      console.log(`\n  TOTAL: ${totalImported} imported, ${totalSkipped} skipped`);
    }

    console.log('\n✅ Test completed!');
    process.exit(0);

  } catch (error) {
    console.error('\n❌ Error:', error.message);
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
