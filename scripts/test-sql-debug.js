const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    // Register and login
    await page.goto('http://localhost:8080/register', { waitUntil: 'domcontentloaded' });
    const hasEmailInput = await page.$('input[name="email"]');
    if (hasEmailInput) {
      await page.fill('input[name="email"]', 'test-sql@example.com');
      await page.fill('input[name="password"]', 'test123');
      await page.fill('input[name="name"]', 'Test');
      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ timeout: 5000 }).catch(() => {})
      ]);
      await page.waitForTimeout(500);
    }

    await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded' });
    if (page.url().includes('/login')) {
      await page.fill('input[name="email"]', 'test-sql@example.com');
      await page.fill('input[name="password"]', 'test123');
      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ timeout: 10000 }).catch(() => {})
      ]);
      await page.waitForTimeout(1000);
    }

    // Capture the import response
    let importData = null;
    page.on('response', async response => {
      if (response.url().includes('/bank-import/auto-import')) {
        importData = await response.json();
      }
    });

    await page.goto('http://localhost:8080/bank-import', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    page.on('dialog', dialog => {
      dialog.accept();
    });

    await page.click('#autoImportBtn');
    await page.waitForTimeout(3000);

    if (importData) {
      console.log('Import Response:');
      console.log(JSON.stringify(importData, null, 2));
    }

  } catch (error) {
    console.error('Error:', error.message);
  } finally {
    await browser.close();
  }
})();
