const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  
  try {
    // Login first
    await page.goto('http://localhost:8080/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'test123');
    await page.click('button[type="submit"]');
    await page.waitForNavigation();
    
    console.log('✓ Logged in successfully');
    
    // Now check dashboard colors
    const cardBgColor = await page.evaluate(() => {
      const card = document.querySelector('.card');
      if (!card) return 'Card not found';
      return window.getComputedStyle(card).backgroundColor;
    });
    
    console.log('Card background color:', cardBgColor);
    console.log(cardBgColor === 'rgb(255, 255, 255)' ? '✓ Cards are white!' : '✗ Cards NOT white');
    
    const canvasBgColor = await page.evaluate(() => {
      const canvas = document.querySelector('canvas');
      if (!canvas) return 'Canvas not found';
      const parent = canvas.parentElement;
      return window.getComputedStyle(parent).backgroundColor;
    });
    
    console.log('Canvas parent background color:', canvasBgColor);
    
  } catch (error) {
    console.error('Error:', error.message);
  } finally {
    await browser.close();
  }
})();
