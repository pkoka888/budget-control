/**
 * Comprehensive Console Error & Warning Capture
 * Tests all pages and collects errors, warnings, and console messages
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Pages to test (both public and authenticated)
const PAGES_TO_TEST = [
  { url: '/login', name: 'Login', authenticated: false },
  { url: '/register', name: 'Registration', authenticated: false },
  { url: '/', name: 'Dashboard', authenticated: true },
  { url: '/accounts', name: 'Accounts', authenticated: true },
  { url: '/transactions', name: 'Transactions', authenticated: true },
  { url: '/categories', name: 'Categories', authenticated: true },
  { url: '/budgets', name: 'Budgets', authenticated: true },
  { url: '/investments', name: 'Investments', authenticated: true },
  { url: '/goals', name: 'Goals', authenticated: true },
  { url: '/reports/monthly', name: 'Reports', authenticated: true },
  { url: '/tips', name: 'Tips', authenticated: true },
  { url: '/settings', name: 'Settings', authenticated: true },
];

async function captureConsoleErrors() {
  const browser = await chromium.launch();
  const report = {
    timestamp: new Date().toISOString(),
    totalPages: PAGES_TO_TEST.length,
    pages: [],
    summary: {
      totalErrors: 0,
      totalWarnings: 0,
      totalLogs: 0,
      affectedPages: 0,
      cleanPages: 0,
    }
  };

  for (const page of PAGES_TO_TEST) {
    const browserPage = await browser.newPage();
    const errors = [];
    const warnings = [];
    const logs = [];

    // Capture console messages
    browserPage.on('console', (msg) => {
      const entry = {
        type: msg.type(),
        text: msg.text(),
        location: msg.location(),
        args: msg.args().length,
      };

      if (msg.type() === 'error') {
        errors.push(entry);
      } else if (msg.type() === 'warning') {
        warnings.push(entry);
      } else if (msg.type() === 'log') {
        logs.push(entry);
      }
    });

    // Capture page errors
    browserPage.on('pageerror', (error) => {
      errors.push({
        type: 'page-error',
        text: error.message,
        stack: error.stack,
      });
    });

    // Capture request failures
    browserPage.on('requestfailed', (request) => {
      errors.push({
        type: 'request-failed',
        url: request.url(),
        failure: request.failure(),
      });
    });

    try {
      console.log(`📄 Analyzing: ${page.name} (${page.url})`);

      await browserPage.goto(`http://localhost:8080${page.url}`, {
        waitUntil: 'networkidle',
        timeout: 30000
      }).catch(err => {
        // Catch navigation errors but continue
        errors.push({
          type: 'navigation-error',
          text: err.message,
        });
      });

      // Wait for any async errors
      await browserPage.waitForTimeout(2000);

      // Check for runtime errors in page
      const runtimeErrors = await browserPage.evaluate(() => {
        const errors = [];
        // Check for any error messages in the DOM
        const errorElements = document.querySelectorAll('[class*="error"], [class*="fatal"]');
        errorElements.forEach(el => {
          errors.push({
            type: 'dom-error',
            text: el.textContent.substring(0, 200),
            html: el.outerHTML.substring(0, 200),
          });
        });
        return errors;
      });

      errors.push(...runtimeErrors);

      // Get page title and status
      const pageInfo = await browserPage.evaluate(() => ({
        title: document.title,
        url: window.location.href,
        readyState: document.readyState,
      }));

      const pageReport = {
        page: page.name,
        url: page.url,
        authenticated: page.authenticated,
        pageInfo,
        errors: errors.length > 0 ? errors : null,
        warnings: warnings.length > 0 ? warnings : null,
        logs: logs.length > 0 ? logs.slice(0, 5) : null, // Limit to first 5 logs
        summary: {
          errorCount: errors.length,
          warningCount: warnings.length,
          logCount: logs.length,
          hasErrors: errors.length > 0,
          isClean: errors.length === 0 && warnings.length === 0,
        }
      };

      report.pages.push(pageReport);
      report.summary.totalErrors += errors.length;
      report.summary.totalWarnings += warnings.length;
      report.summary.totalLogs += logs.length;

      if (pageReport.summary.hasErrors) {
        report.summary.affectedPages++;
      } else {
        report.summary.cleanPages++;
      }

      // Print quick status
      const status = pageReport.summary.isClean ? '✅ CLEAN' : `⚠️ ${errors.length} errors, ${warnings.length} warnings`;
      console.log(`  ${status}\n`);

    } catch (error) {
      console.error(`❌ Failed to analyze ${page.name}:`, error.message);
      report.pages.push({
        page: page.name,
        url: page.url,
        error: error.message,
      });
      report.summary.affectedPages++;
    } finally {
      await browserPage.close();
    }
  }

  await browser.close();

  // Save report
  const reportPath = path.join(process.cwd(), 'console-errors-report.json');
  fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));

  // Print summary
  console.log('\n' + '='.repeat(70));
  console.log('CONSOLE ERRORS & WARNINGS SUMMARY');
  console.log('='.repeat(70));
  console.log(`\nTotal Pages Analyzed: ${report.summary.totalPages}`);
  console.log(`Clean Pages: ${report.summary.cleanPages}/${report.summary.totalPages}`);
  console.log(`Pages with Errors: ${report.summary.affectedPages}`);
  console.log(`\nTotal Errors Found: ${report.summary.totalErrors}`);
  console.log(`Total Warnings Found: ${report.summary.totalWarnings}`);
  console.log(`Total Log Entries: ${report.summary.totalLogs}`);
  console.log(`\nReport saved to: ${reportPath}\n`);

  // Print pages with errors
  if (report.summary.affectedPages > 0) {
    console.log('Pages with Issues:');
    report.pages.forEach(p => {
      if (p.summary && p.summary.hasErrors) {
        console.log(`  ❌ ${p.page}: ${p.summary.errorCount} errors, ${p.summary.warningCount} warnings`);
        if (p.errors) {
          p.errors.slice(0, 3).forEach(err => {
            console.log(`     - ${err.type}: ${err.text.substring(0, 80)}`);
          });
        }
      }
    });
  } else {
    console.log('\n✅ ALL PAGES ARE CLEAN - NO ERRORS OR WARNINGS FOUND!\n');
  }

  return report;
}

captureConsoleErrors().then(report => {
  process.exit(report.summary.totalErrors > 0 ? 1 : 0);
}).catch(error => {
  console.error('Fatal error:', error);
  process.exit(1);
});
