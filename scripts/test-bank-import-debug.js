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
      process.exit(1);
    }

    console.log('✅ Found auto-import button');

    // Set up a listener for the API response before clicking
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

    // Handle the confirmation dialog
    console.log('🔄 Initiating auto-import...');
    page.on('dialog', dialog => {
      console.log('Dialog:', dialog.message());
      dialog.accept();
    });

    // Click the button
    await page.click('#autoImportBtn');

    // Wait for the API response with timeout
    const apiResponse = await Promise.race([
      responsePromise,
      new Promise(resolve => setTimeout(() => resolve(null), 5000))
    ]);

    if (apiResponse) {
      console.log('\n✅ Import completed!');
      console.log('\nFull API Response:');
      console.log(JSON.stringify(apiResponse, null, 2));

      console.log(`\n   - Success: ${apiResponse.success ?? apiResponse.imported_count ?? 0} transactions`);
      console.log(`   - Failed: ${apiResponse.failed ?? 0} files`);
      console.log(`   - Skipped: ${apiResponse.skipped_count ?? 0} transactions`);
      console.log(`   - Total processed: ${apiResponse.total_processed ?? 'unknown'}`);

      if (apiResponse.errors && apiResponse.errors.length > 0) {
        console.log('\n⚠️  Errors/Issues:');
        apiResponse.errors.forEach(error => {
          console.log(`   - ${error}`);
        });
      }

      if (apiResponse.files && apiResponse.files.length > 0) {
        console.log(`\n   Files processed: ${apiResponse.files.length}`);
        apiResponse.files.forEach(file => {
          if (file.status === 'success') {
            console.log(`     ✓ ${file.name}: ${file.imported} imported, ${file.skipped} skipped (total: ${file.total_processed})`);
            if (file.errors && file.errors.length > 0) {
              console.log(`       Errors:`);
              file.errors.forEach(error => {
                console.log(`         - ${error}`);
              });
            }
          } else {
            console.log(`     ✗ ${file.name}: ${file.error}`);
          }
        });
      }

      // Verify results are displayed on page
      await page.waitForTimeout(1000);
      const resultsVisible = await page.evaluate(() => {
        const results = document.getElementById('importResults');
        return results && !results.classList.contains('hidden');
      });

      if (resultsVisible) {
        const imported = await page.evaluate(() => {
          return document.getElementById('resultImported')?.textContent;
        });
        const skipped = await page.evaluate(() => {
          return document.getElementById('resultSkipped')?.textContent;
        });
        console.log(`\n✅ Results displayed on page: ${imported} imported, ${skipped} skipped`);
      }
    } else {
      console.log('⚠️  API response not received (timeout)');
    }

    console.log('\n✅ Bank import test completed!');

  } catch (error) {
    console.error('❌ Error:', error.message);
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
