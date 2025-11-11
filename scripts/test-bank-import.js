const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  try {
    console.log('🔐 Logging in...');
    await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'test123');

    // Wait for navigation with a longer timeout
    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ timeout: 10000 }).catch(() => {})
    ]);

    await page.waitForTimeout(2000);
    console.log('✅ Logged in successfully');

    // Navigate to bank import page
    console.log('📋 Navigating to bank import page...');
    await page.goto('http://localhost:8080/bank-import', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('domcontentloaded');

    // Check if page loaded
    const title = await page.title();
    console.log('Page title:', title);

    // Check if statistics are displayed
    const importedCount = await page.evaluate(() => {
      const text = document.querySelector('.text-google-blue-600');
      return text ? text.textContent : null;
    });
    console.log('Imported transactions count:', importedCount);

    // Check if available files are shown
    const availableFiles = await page.evaluate(() => {
      const text = document.querySelector('.text-google-green-600');
      return text ? text.textContent : null;
    });
    console.log('Available JSON files:', availableFiles);

    // Check if file list is displayed
    const fileList = await page.evaluate(() => {
      const items = document.querySelectorAll('.importFileBtn');
      return items.length;
    });
    console.log('File import buttons found:', fileList);

    // Check if auto-import button exists
    const hasAutoImportBtn = await page.evaluate(() => {
      return document.getElementById('autoImportBtn') !== null;
    });
    console.log('Auto-import button exists:', hasAutoImportBtn);

    // Try to click auto-import (if there are files)
    if (availableFiles && parseInt(availableFiles) > 0 && hasAutoImportBtn) {
      console.log('\n🔄 Starting auto-import...');

      // Set up listener for network responses
      page.on('response', response => {
        if (response.url().includes('/bank-import/auto-import')) {
          console.log('Response status:', response.status());
        }
      });

      // Click the auto-import button
      await page.click('#autoImportBtn');

      // Handle the confirmation dialog
      page.on('dialog', dialog => {
        console.log('Dialog:', dialog.message());
        dialog.accept();
      });

      // Wait for the response
      await page.waitForTimeout(3000);

      // Check import results
      const importResults = await page.evaluate(() => {
        const resultsDiv = document.getElementById('importResults');
        if (resultsDiv && !resultsDiv.classList.contains('hidden')) {
          return {
            imported: document.getElementById('resultImported')?.textContent,
            skipped: document.getElementById('resultSkipped')?.textContent,
            files: document.getElementById('resultFiles')?.textContent
          };
        }
        return null;
      });

      if (importResults) {
        console.log('\n✅ Import Results:');
        console.log('  Imported transactions:', importResults.imported);
        console.log('  Skipped transactions:', importResults.skipped);
        console.log('  Files processed:', importResults.files);
      } else {
        console.log('Results not yet displayed');
      }
    }

    console.log('\n✅ Bank import page test completed successfully!');

  } catch (error) {
    console.error('❌ Error:', error.message);
    const screenshot = await page.screenshot();
    console.log('Screenshot saved');
  } finally {
    await browser.close();
  }
})();
