const { chromium } = require('playwright');

async function verifyInvestmentsPage() {
  const browser = await chromium.launch();
  const page = await browser.newPage();

  try {
    const response = await page.goto('http://localhost:8080/investments', { waitUntil: 'networkidle' });
    console.log('Investments page response status: ' + response.status());

    // Check if page has errors
    const errors = await page.evaluate(() => {
      const body = document.body.textContent;
      return {
        hasFatalError: body.includes('Fatal error'),
        hasUndefinedProperty: body.includes('Undefined property'),
        hasCallToMember: body.includes('Call to a member function'),
        pageTitle: document.title
      };
    });

    console.log('\nInvestments Page Status:');
    console.log('  Title: ' + errors.pageTitle);
    console.log('  Fatal Error: ' + errors.hasFatalError);
    console.log('  Undefined Property Error: ' + errors.hasUndefinedProperty);
    console.log('  Call to Member Error: ' + errors.hasCallToMember);

    if (!errors.hasFatalError && !errors.hasUndefinedProperty && !errors.hasCallToMember) {
      console.log('\n✅ SUCCESS: Investments page loaded without errors!');
    } else {
      console.log('\n❌ FAILED: Errors detected on page');
    }

  } catch (error) {
    console.error('Error: ' + error.message);
  } finally {
    await browser.close();
  }
}

verifyInvestmentsPage();
