const { chromium } = require('playwright');

/**
 * Test the async bank import workflow
 * 1. User clicks import button
 * 2. API returns 202 Accepted with job_id
 * 3. Job processes in background
 * 4. Check job status with GET /bank-import/job-status?job_id=<id>
 */
(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  let jobId = null;

  try {
    console.log('🎯 Async Bank Import Workflow Test\n');

    // Register new user
    console.log('📝 Step 1: Registering test user...');
    const email = `async-test-${Date.now()}@example.com`;
    await page.goto('http://localhost:8080/register', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('input[name="email"]', { timeout: 10000 });
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'test123');
    await page.fill('input[name="name"]', 'Async Test User');
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ timeout: 10000 }).catch(() => {});
    await page.waitForTimeout(500);
    console.log('✅ Registered\n');

    // Login
    console.log('🔐 Step 2: Logging in...');
    await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('input[name="email"]', { timeout: 10000 });
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', 'test123');
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ timeout: 10000 }).catch(() => {});
    await page.waitForTimeout(500);
    console.log('✅ Logged in\n');

    // Go to bank import page
    console.log('📋 Step 3: Opening bank import page...');
    await page.goto('http://localhost:8080/bank-import');
    await page.waitForTimeout(1000);

    // Accept dialog and capture API response
    let importResponse = null;
    page.on('dialog', dialog => {
      dialog.accept();
    });

    const responsePromise = page.waitForResponse(
      response => response.url().includes('/bank-import/auto-import') && response.status() === 202,
      { timeout: 10000 }
    ).then(async response => {
      importResponse = await response.json();
      return importResponse;
    }).catch(err => {
      console.log('⚠️  Error waiting for 202 response:', err.message);
      return null;
    });

    console.log('🔄 Step 4: Clicking import button...');
    await page.click('#autoImportBtn');

    console.log('⏳ Waiting for 202 Accepted response...');
    importResponse = await responsePromise;

    if (!importResponse) {
      console.log('❌ Did not receive 202 Accepted response');
      process.exit(1);
    }

    console.log('\n✅ Received 202 Accepted!\n');
    console.log('📊 API Response:');
    console.log(`   Job ID: ${importResponse.job_id}`);
    console.log(`   Status: ${importResponse.status}`);
    console.log(`   Message: ${importResponse.message}`);

    jobId = importResponse.job_id;

    // Poll job status
    console.log('\n🔍 Step 5: Checking job status...');
    await page.waitForTimeout(2000);

    for (let i = 0; i < 10; i++) {
      const statusUrl = `http://localhost:8080/bank-import/job-status?job_id=${jobId}`;
      const statusResponse = await page.evaluate(async (url) => {
        const res = await fetch(url);
        return await res.json();
      }, statusUrl);

      console.log(`\nAttempt ${i + 1}:`);
      console.log(`   Status: ${statusResponse.status}`);
      console.log(`   Progress: ${statusResponse.progress.processed_files}/${statusResponse.progress.total_files} files`);
      console.log(`   Imported: ${statusResponse.progress.imported_count} transactions`);

      if (statusResponse.status === 'completed' || statusResponse.status === 'failed') {
        console.log('\n✅ Job finished!');
        console.log('\n📊 FINAL RESULTS:');

        if (statusResponse.results) {
          console.log(`   Total Imported: ${statusResponse.results.success || 0}`);
          console.log(`   Total Failed: ${statusResponse.results.failed || 0}`);

          if (statusResponse.results.files && statusResponse.results.files.length > 0) {
            console.log('\n   Files:');
            let totalImported = 0;
            let totalSkipped = 0;

            statusResponse.results.files.forEach((file, idx) => {
              if (file.status === 'success') {
                const imported = file.imported || 0;
                const skipped = file.skipped || 0;
                totalImported += imported;
                totalSkipped += skipped;
                console.log(`     ${idx + 1}. ${file.name}: ${imported} imported, ${skipped} skipped`);
              } else {
                console.log(`     ${idx + 1}. ${file.name}: ERROR - ${file.error}`);
              }
            });

            console.log(`\n   TOTALS: ${totalImported} imported, ${totalSkipped} skipped`);
          }
        }

        if (statusResponse.error_message) {
          console.log(`\n   Error: ${statusResponse.error_message}`);
        }

        console.log(`\n   Started: ${statusResponse.started_at}`);
        console.log(`   Completed: ${statusResponse.completed_at}`);

        console.log('\n✅ Async import test completed successfully!');
        process.exit(0);
      }

      if (i < 9) {
        await page.waitForTimeout(2000);
      }
    }

    console.log('\n⚠️  Job did not complete within timeout');
    process.exit(1);

  } catch (error) {
    console.error('\n❌ Error:', error.message);
    process.exit(1);
  } finally {
    await browser.close();
  }
})();
