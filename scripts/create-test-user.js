/**
 * Simple script to create a test user using Playwright
 * Run with: node create-test-user.js
 */

const { chromium } = require('playwright');

async function createTestUser() {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  try {
    console.log('🚀 Starting test user creation...\n');

    // Step 1: Navigate to registration
    console.log('📝 Step 1: Opening registration page...');
    await page.goto('http://localhost:8080/register', { waitUntil: 'domcontentloaded', timeout: 30000 });
    console.log('✅ Registration page loaded\n');

    // Step 2: Fill form
    console.log('📝 Step 2: Filling registration form...');
    console.log('   - Name: Test User');
    console.log('   - Email: test@example.com');
    console.log('   - Password: password123\n');

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password123');
    console.log('✅ Form filled\n');

    // Step 3: Submit
    console.log('📝 Step 3: Submitting registration form...');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

    const currentUrl = page.url();
    console.log(`✅ Form submitted. Current URL: ${currentUrl}\n`);

    // Step 4: Check for errors
    const errorElement = await page.locator('.bg-red-100').isVisible({ timeout: 2000 }).catch(() => false);
    if (errorElement) {
      const errorText = await page.locator('.bg-red-100').textContent();
      console.log(`❌ Error: ${errorText}`);
      await browser.close();
      return false;
    }

    // Step 5: Verify login
    console.log('📝 Step 4: Verifying login status...');
    if (currentUrl.includes('register')) {
      console.log('❌ Still on register page - registration may have failed');
      await browser.close();
      return false;
    }

    console.log('✅ Successfully registered!\n');

    // Step 6: Logout
    console.log('📝 Step 5: Testing logout...');
    const logoutSelector = 'a:has-text("Odhlásit"), a:has-text("Logout"), a[href*="logout"]';
    const logoutVisible = await page.locator(logoutSelector).isVisible({ timeout: 3000 }).catch(() => false);

    if (logoutVisible) {
      await page.locator(logoutSelector).first().click();
      await page.waitForLoadState('domcontentloaded', { timeout: 5000 });
      console.log('✅ Logged out\n');
    } else {
      console.log('⚠️  Could not find logout button (may still be logged in)\n');
    }

    // Step 7: Test login
    console.log('📝 Step 6: Testing login with new credentials...');
    await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded', timeout: 30000 });

    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password123');
    console.log('✅ Login form filled\n');

    console.log('📝 Step 7: Submitting login...');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

    const loginUrl = page.url();
    console.log(`✅ Login submitted. Current URL: ${loginUrl}\n`);

    // Check for login error
    const loginError = await page.locator('.bg-red-100').isVisible({ timeout: 2000 }).catch(() => false);
    if (loginError) {
      const errorText = await page.locator('.bg-red-100').textContent();
      console.log(`❌ Login error: ${errorText}`);
      await browser.close();
      return false;
    }

    if (loginUrl.includes('login')) {
      console.log('❌ Still on login page - login failed');
      await browser.close();
      return false;
    }

    // Success!
    console.log('\n✅ ═══════════════════════════════════════════════════════');
    console.log('✅ TEST USER CREATED AND LOGIN VERIFIED!');
    console.log('✅ ═══════════════════════════════════════════════════════\n');

    console.log('📋 Login Credentials:');
    console.log('   Email: test@example.com');
    console.log('   Password: password123\n');

    console.log('🔗 Access here:');
    console.log('   http://localhost:8080/login\n');

    console.log('✅ You should now see the dashboard');

    await browser.close();
    return true;

  } catch (error) {
    console.error('❌ Error:', error.message);
    await browser.close();
    return false;
  }
}

createTestUser().then(success => {
  process.exit(success ? 0 : 1);
});
