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
      await page.fill('input[name="email"]', 'test-response@example.com');
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
      await page.fill('input[name="email"]', 'test-response@example.com');
      await page.fill('input[name="password"]', 'test123');
      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ timeout: 10000 }).catch(() => {})
      ]);
      await page.waitForTimeout(1000);
    }

    // Capture raw response
    let rawResponse = null;
    page.on('response', async response => {
      if (response.url().includes('/bank-import/auto-import')) {
        console.log('Response Status:', response.status());
        console.log('Response Headers:', response.headers());
        try {
          const text = await response.text();
          console.log('Response Body (first 2000 chars):');
          console.log(text.substring(0, 2000));
          rawResponse = text;
        } catch (e) {
          console.log('Error reading response:', e.message);
        }
      }
    });

    await page.goto('http://localhost:8080/bank-import', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(500);

    page.on('dialog', dialog => {
      dialog.accept();
    });

    console.log('\nClicking auto-import button...\n');
    await page.click('#autoImportBtn');
    
    await page.waitForTimeout(3000);

  } catch (error) {
    console.error('Error:', error.message);
  } finally {
    await browser.close();
  }
})();
