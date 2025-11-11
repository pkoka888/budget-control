/**
 * Verify CSS is loading by checking computed styles in real browser context
 */

const { chromium } = require('playwright');

async function verifyCSSLoading() {
  const browser = await chromium.launch({ headless: false }); // Non-headless for real rendering
  const page = await browser.newPage();

  try {
    console.log('📸 Opening login page in browser...\n');
    await page.goto('http://localhost:8080/login', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Wait for CSS to fully render
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Check computed styles of the white box
    const boxStyles = await page.evaluate(() => {
      const box = document.querySelector('.bg-white');
      if (!box) return { error: 'Box not found' };
      
      const styles = window.getComputedStyle(box);
      return {
        backgroundColor: styles.backgroundColor,
        borderRadius: styles.borderRadius,
        boxShadow: styles.boxShadow,
        padding: styles.padding,
        display: 'Found box with styling'
      };
    });

    console.log('✅ Computed Styles of Login Box:');
    console.log(JSON.stringify(boxStyles, null, 2));

    // Take screenshot
    await page.screenshot({
      path: 'screenshots/login-with-css.png',
      fullPage: true
    });
    console.log('\n✅ Screenshot saved: screenshots/login-with-css.png');

    await page.pause(); // Pause so you can see the page

  } catch (error) {
    console.error('❌ Error:', error.message);
  } finally {
    await browser.close();
  }
}

verifyCSSLoading();
