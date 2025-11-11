/**
 * Test User Setup Script
 * Creates a test user in the database so you can login
 *
 * Test Credentials:
 * - Email: test@example.com
 * - Password: password123
 */

const { test, expect } = require('@playwright/test');

test('Setup: Create test user and verify login', async ({ page }) => {
  // Step 1: Navigate to registration page
  console.log('📝 Step 1: Opening registration page...');
  await page.goto('http://localhost:8080/register', { waitUntil: 'domcontentloaded', timeout: 30000 });

  const registerTitle = await page.title();
  console.log(`✅ Registration page loaded: "${registerTitle}"`);

  // Step 2: Fill registration form
  console.log('\n📝 Step 2: Filling registration form...');
  await page.fill('input[name="name"]', 'Test User');
  await page.fill('input[name="email"]', 'test@example.com');
  await page.fill('input[name="password"]', 'password123');

  console.log('✅ Form filled with:');
  console.log('   - Name: Test User');
  console.log('   - Email: test@example.com');
  console.log('   - Password: password123');

  // Step 3: Submit registration
  console.log('\n📝 Step 3: Submitting registration...');
  await page.click('button[type="submit"]');

  // Wait for redirect or error
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

  const currentUrl = page.url();
  console.log(`✅ Form submitted. Current URL: ${currentUrl}`);

  // Step 4: Check if we're logged in (redirected from /register)
  if (currentUrl.includes('register')) {
    // Still on register page - check for error
    const errorMsg = await page.locator('.bg-red-100').textContent().catch(() => '');
    if (errorMsg) {
      console.log(`⚠️  Registration error: ${errorMsg}`);
    }
    throw new Error('Registration failed - still on register page');
  }

  // Step 5: Verify we're logged in
  console.log('\n📝 Step 4: Verifying login...');
  const pageContent = await page.content();

  if (pageContent.includes('Odhlásit se') || pageContent.includes('Logout')) {
    console.log('✅ Successfully registered and logged in!');
  } else {
    console.log('⚠️  Warning: May not be fully logged in');
  }

  // Step 6: Test logout and login
  console.log('\n📝 Step 5: Testing logout...');
  const logoutLink = await page.locator('a:has-text("Odhlásit"), a:has-text("Logout")');
  if (await logoutLink.isVisible({ timeout: 5000 }).catch(() => false)) {
    await logoutLink.click();
    console.log('✅ Logged out');
  }

  // Step 7: Login with the credentials
  console.log('\n📝 Step 6: Testing login with new credentials...');
  await page.goto('http://localhost:8080/login', { waitUntil: 'domcontentloaded', timeout: 30000 });

  await page.fill('input[name="email"]', 'test@example.com');
  await page.fill('input[name="password"]', 'password123');

  console.log('✅ Login form filled');

  // Submit login
  await page.click('button[type="submit"]');
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 });

  const loginUrl = page.url();
  console.log(`✅ Login submitted. Current URL: ${loginUrl}`);

  if (loginUrl.includes('login')) {
    const error = await page.locator('.bg-red-100').textContent().catch(() => '');
    console.log(`❌ Login failed: ${error}`);
    throw new Error('Login failed');
  }

  console.log('\n✅ SUCCESS! Test user created and login verified!');
  console.log('\n📋 Test Credentials:');
  console.log('   Email: test@example.com');
  console.log('   Password: password123');
  console.log('\n🔗 Login at: http://localhost:8080/login');
});
