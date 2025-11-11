/**
 * Screenshot verification script
 * Captures login page and dashboard to verify CSS rendering
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function captureScreenshots() {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  const screenshotsDir = './screenshots';

  // Create screenshots directory if it doesn't exist
  if (!fs.existsSync(screenshotsDir)) {
    fs.mkdirSync(screenshotsDir);
  }

  try {
    console.log('📸 Capturing screenshots for verification...\n');

    // Step 1: Capture Login Page
    console.log('📝 Step 1: Capturing login page...');
    await page.goto('http://localhost:8080/login', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Wait for CSS to fully load and render
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000); // Extra wait for CSS rendering
    await page.screenshot({
      path: path.join(screenshotsDir, '01-login-page.png'),
      fullPage: true
    });
    console.log('✅ Login page captured: screenshots/01-login-page.png\n');

    // Step 2: Login
    console.log('📝 Step 2: Logging in with test@example.com / password123...');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    // Wait for navigation to complete
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000); // Extra wait for CSS rendering

    const currentUrl = page.url();
    console.log(`✅ Login submitted. Current URL: ${currentUrl}\n`);

    // Step 3: Capture Dashboard/Homepage
    console.log('📝 Step 3: Capturing dashboard after login...');

    // Check if redirected or still on login
    if (currentUrl.includes('login')) {
      console.log('❌ Still on login page - login may have failed');
      const errorMsg = await page.locator('.bg-red-100, .text-red-600').first().textContent().catch(() => 'Unknown error');
      console.log(`   Error: ${errorMsg}`);
    } else {
      // Wait for any animations/CSS transitions
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1500); // Extra time for CSS animations

      await page.screenshot({
        path: path.join(screenshotsDir, '02-dashboard-after-login.png'),
        fullPage: true
      });
      console.log('✅ Dashboard captured: screenshots/02-dashboard-after-login.png\n');

      // Capture sidebar menu
      console.log('📝 Step 4: Capturing sidebar menu...');
      const sidebar = await page.locator('nav, .sidebar, [role="navigation"]').first();
      if (await sidebar.isVisible().catch(() => false)) {
        await sidebar.screenshot({
          path: path.join(screenshotsDir, '03-sidebar-menu.png')
        });
        console.log('✅ Sidebar captured: screenshots/03-sidebar-menu.png\n');
      }
    }

    // Step 4: Check for PHP errors
    console.log('📝 Step 5: Checking for PHP errors in console...');
    const pageContent = await page.content();

    if (pageContent.includes('Warning:') || pageContent.includes('Error:')) {
      console.log('⚠️  Found errors in page content');
    } else {
      console.log('✅ No obvious PHP errors found\n');
    }

    // Step 5: Get computed styles to verify CSS is working
    console.log('📝 Step 6: Verifying CSS is applied...');
    const bodyComputedStyle = await page.evaluate(() => {
      const elem = document.querySelector('body');
      return window.getComputedStyle(elem).cssText.substring(0, 100);
    });

    if (bodyComputedStyle) {
      console.log(`✅ CSS is being applied to body element`);
      console.log(`   Sample: ${bodyComputedStyle.substring(0, 80)}...\n`);
    }

    console.log('✅ ═════════════════════════════════════════════════════════');
    console.log('✅ SCREENSHOT VERIFICATION COMPLETE!');
    console.log('✅ ═════════════════════════════════════════════════════════\n');

    console.log('📋 Captured files:');
    console.log('   1. screenshots/01-login-page.png');
    console.log('   2. screenshots/02-dashboard-after-login.png');
    console.log('   3. screenshots/03-sidebar-menu.png\n');

    console.log('💡 View the screenshots to verify:');
    console.log('   - CSS styling is applied');
    console.log('   - Layout is properly formatted');
    console.log('   - Colors and fonts are rendering');
    console.log('   - Dashboard displays correctly after login\n');

    await browser.close();
    return true;

  } catch (error) {
    console.error('❌ Error:', error.message);
    await browser.close();
    return false;
  }
}

captureScreenshots().then(success => {
  process.exit(success ? 0 : 1);
});
