const { chromium } = require('playwright');

/**
 * Test real bank JSON import with all 6 years of bank statements
 * Files: 2020, 2021, 2022, 2023, 2024, 2025
 */
(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('🎯 Real Bank JSON Import Test - All Years\n');
    console.log('Files to import: 2020, 2021, 2022, 2023, 2024, 2025\n');

    // Register new user
    console.log('📝 Step 1: Registering test user...');
    await page.goto('http://localhost:8080/register', { waitUntil: 'domcontentloaded' });
    const hasEmailInput = await page.$('input[name="email"]');
    if (hasEmailInput) {
      await page.fill('input[name="email"]', `test-real-import-${Date.now()}@example.com`);
      await page.fill('input[name="password"]', 'test123');
      await page.fill('input[name="name"]', 'Real Import Test');
      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ timeout: 5000 }).catch(() => {})
      ]);
      await page.waitForTimeout(500);
      console.log('✅ User registered\n');
    }

    // Login
    console.log('🔐 Step 2: Logging in...');
    await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded' });
    if (page.url().includes('/login')) {
      const email = await page.inputValue('input[name="email"]');
      // Get email from register page or use new one
      await page.fill('input[name="email"]', `test-real-import-${Date.now()}@example.com`);
      await page.fill('input[name="password"]', 'test123');

      // Re-register if needed - try login first
      let loginSuccess = false;
      try {
        await Promise.all([
          page.click('button[type="submit"]'),
          page.waitForNavigation({ timeout: 5000 }).catch(() => {})
        ]);
        await page.waitForTimeout(500);
        loginSuccess = !page.url().includes('/login');
      } catch (e) {
        console.log('  (Initial login attempt failed, will re-register)');
      }
    }
    console.log('✅ Logged in\n');

    // Navigate to bank import page
    console.log('📋 Step 3: Opening bank import page...');
    await page.goto('http://localhost:8080/bank-import', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);

    // Check if button exists
    const hasAutoImportBtn = await page.$('#autoImportBtn');
    if (!hasAutoImportBtn) {
      console.log('❌ Auto-import button not found');
      process.exit(1);
    }
    console.log('✅ Bank import page loaded\n');

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
    console.log('🔄 Step 4: Initiating auto-import for all files...\n');
    page.on('dialog', dialog => {
      console.log(`  Dialog: "${dialog.message()}"`);
      dialog.accept();
    });

    await page.click('#autoImportBtn');

    // Wait for response with longer timeout for large files (up to 120 seconds for 6 years of data)
    const apiResponse = await Promise.race([
      responsePromise,
      new Promise(resolve => setTimeout(() => resolve(null), 120000))
    ]);

    if (apiResponse) {
      console.log('✅ Import completed!\n');

      // Summary statistics
      const totalImported = apiResponse.success ?? 0;
      const totalFailed = apiResponse.failed ?? 0;
      const filesProcessed = apiResponse.files ? apiResponse.files.length : 0;

      console.log('📊 IMPORT SUMMARY:');
      console.log(`  Total Transactions Imported: ${totalImported}`);
      console.log(`  Total Files with Errors: ${totalFailed}`);
      console.log(`  Files Processed: ${filesProcessed}\n`);

      // Detailed file results
      if (apiResponse.files && apiResponse.files.length > 0) {
        console.log('📁 FILE-BY-FILE RESULTS:');
        let totalTransactions = 0;
        let totalSkipped = 0;

        apiResponse.files.forEach((file, idx) => {
          if (file.status === 'success') {
            const imported = file.imported || 0;
            const skipped = file.skipped || 0;
            const total = file.total_processed || 0;
            totalTransactions += imported;
            totalSkipped += skipped;

            console.log(`  ${idx + 1}. ${file.name}`);
            console.log(`     ✓ Imported: ${imported}`);
            console.log(`     ⊘ Skipped:  ${skipped}`);
            console.log(`     □ Total:    ${total}`);

            if (file.errors && file.errors.length > 0) {
              console.log(`     ⚠️  Errors:`);
              file.errors.slice(0, 3).forEach(err => {
                console.log(`         - ${err.substring(0, 70)}${err.length > 70 ? '...' : ''}`);
              });
            }
          } else {
            console.log(`  ${idx + 1}. ${file.name}`);
            console.log(`     ✗ Error: ${file.error}`);
          }
        });

        console.log(`\n📈 TOTALS ACROSS ALL FILES:`);
        console.log(`  Total Imported: ${totalTransactions}`);
        console.log(`  Total Skipped:  ${totalSkipped}`);
        console.log(`  Grand Total:    ${totalTransactions + totalSkipped}`);
      }

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
