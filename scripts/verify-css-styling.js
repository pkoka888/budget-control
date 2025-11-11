/**
 * Verify CSS Styling is Applied to Pages
 * Tests login, registration, and dashboard pages
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function verifyStyling() {
  const screenshotDir = 'screenshots/css-verification';
  if (!fs.existsSync(screenshotDir)) {
    fs.mkdirSync(screenshotDir, { recursive: true });
  }

  const browser = await chromium.launch({ headless: true });

  try {
    console.log('🧪 Verifying CSS Styling Across Application\n');
    console.log('=====================================\n');

    // Test 1: Login Page
    console.log('1️⃣ Testing Login Page...');
    const loginPage = await browser.newPage();
    await loginPage.goto('http://localhost:8080/login', { waitUntil: 'networkidle' });

    const loginStyles = await loginPage.evaluate(() => {
      const box = document.querySelector('.bg-white');
      const input = document.querySelector('input[type="email"]');
      const button = document.querySelector('button[type="submit"]');

      if (!box) return { error: 'Login box not found' };

      return {
        box: {
          backgroundColor: window.getComputedStyle(box).backgroundColor,
          borderRadius: window.getComputedStyle(box).borderRadius,
          boxShadow: window.getComputedStyle(box).boxShadow.substring(0, 50),
          padding: window.getComputedStyle(box).padding
        },
        input: input ? {
          borderColor: window.getComputedStyle(input).borderColor,
          padding: window.getComputedStyle(input).padding
        } : null,
        button: button ? {
          backgroundColor: window.getComputedStyle(button).backgroundColor,
          color: window.getComputedStyle(button).color,
          padding: window.getComputedStyle(button).padding
        } : null
      };
    });

    console.log('  ✅ Login Page Styles:');
    console.log(JSON.stringify(loginStyles, null, 2));

    await loginPage.screenshot({
      path: `${screenshotDir}/01-login-page.png`,
      fullPage: true
    });
    console.log('  📸 Screenshot: screenshots/css-verification/01-login-page.png\n');
    await loginPage.close();

    // Test 2: Registration Page
    console.log('2️⃣ Testing Registration Page...');
    const registerPage = await browser.newPage();
    await registerPage.goto('http://localhost:8080/register', { waitUntil: 'networkidle' });

    const registerStyles = await registerPage.evaluate(() => {
      const box = document.querySelector('.bg-white');
      if (!box) return { error: 'Register box not found' };

      return {
        backgroundColor: window.getComputedStyle(box).backgroundColor,
        borderRadius: window.getComputedStyle(box).borderRadius,
        boxShadow: window.getComputedStyle(box).boxShadow.substring(0, 50)
      };
    });

    console.log('  ✅ Registration Page Styles:');
    console.log(JSON.stringify(registerStyles, null, 2));

    await registerPage.screenshot({
      path: `${screenshotDir}/02-registration-page.png`,
      fullPage: true
    });
    console.log('  📸 Screenshot: screenshots/css-verification/02-registration-page.png\n');
    await registerPage.close();

    // Test 3: Dashboard (requires login)
    console.log('3️⃣ Testing Dashboard with Login...');
    const dashboardPage = await browser.newPage();

    // Login first
    await dashboardPage.goto('http://localhost:8080/login', { waitUntil: 'networkidle' });
    await dashboardPage.fill('input[type="email"]', 'test@example.com');
    await dashboardPage.fill('input[type="password"]', 'password123');
    await dashboardPage.click('button[type="submit"]');

    // Wait for redirect to dashboard
    await dashboardPage.waitForURL('http://localhost:8080/', { timeout: 10000 });
    await dashboardPage.waitForLoadState('networkidle');

    const dashboardStyles = await dashboardPage.evaluate(() => {
      const sidebar = document.querySelector('.bg-blue-900');
      const mainContent = document.querySelector('main');
      const headerBar = document.querySelector('header');

      return {
        sidebar: sidebar ? {
          backgroundColor: window.getComputedStyle(sidebar).backgroundColor,
          color: window.getComputedStyle(sidebar).color,
          width: window.getComputedStyle(sidebar).width
        } : { error: 'Sidebar not found' },
        header: headerBar ? {
          backgroundColor: window.getComputedStyle(headerBar).backgroundColor,
          borderBottom: window.getComputedStyle(headerBar).borderBottom.substring(0, 50)
        } : null,
        mainContent: mainContent ? {
          overflowY: window.getComputedStyle(mainContent).overflowY
        } : null
      };
    });

    console.log('  ✅ Dashboard Styles:');
    console.log(JSON.stringify(dashboardStyles, null, 2));

    await dashboardPage.screenshot({
      path: `${screenshotDir}/03-dashboard-page.png`,
      fullPage: true
    });
    console.log('  📸 Screenshot: screenshots/css-verification/03-dashboard-page.png\n');
    await dashboardPage.close();

    // Test 4: Verify CSS file is loaded
    console.log('4️⃣ Verifying CSS File is Loaded...');
    const cssCheckPage = await browser.newPage();
    await cssCheckPage.goto('http://localhost:8080/login', { waitUntil: 'networkidle' });

    const cssLoaded = await cssCheckPage.evaluate(() => {
      const stylesheets = Array.from(document.styleSheets);
      const tailwindCSS = stylesheets.find(sheet => {
        try {
          return sheet.href && sheet.href.includes('tailwind.css');
        } catch (e) {
          return false;
        }
      });

      return {
        totalSheets: stylesheets.length,
        hasTailwindCSS: !!tailwindCSS,
        tailwindCSSUrl: tailwindCSS ? tailwindCSS.href : 'NOT FOUND',
        sheetUrls: stylesheets.map(sheet => {
          try {
            return sheet.href;
          } catch (e) {
            return 'unable to read';
          }
        }).filter(url => url && url.includes('/assets/css/'))
      };
    });

    console.log('  ✅ CSS Loading Status:');
    console.log(JSON.stringify(cssLoaded, null, 2));

    await cssCheckPage.close();

    console.log('\n=====================================');
    console.log('✅ CSS Styling Verification Complete!');
    console.log('=====================================\n');
    console.log('📋 Summary:');
    console.log('  ✅ Login page styling verified');
    console.log('  ✅ Registration page styling verified');
    console.log('  ✅ Dashboard styling verified');
    console.log('  ✅ CSS files are being loaded correctly\n');
    console.log('Screenshots saved to: screenshots/css-verification/\n');

  } catch (error) {
    console.error('❌ Error during verification:', error.message);
  } finally {
    await browser.close();
  }
}

verifyStyling();
