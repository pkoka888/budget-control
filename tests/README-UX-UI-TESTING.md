# UX/UI Testing Guide

**Version:** 1.0
**Last Updated:** 2025-11-15

---

## Overview

This directory contains specialized UX and UI tests for the Budget Control application, powered by Playwright and organized by testing concern.

### Testing Philosophy

- **UX Testing:** Focuses on user experience, flows, and performance
- **UI Testing:** Focuses on visual design, accessibility, and consistency
- **Automated:** All tests run automatically via Playwright
- **Agent-Driven:** Tests designed to be run by specialized AI agents

---

## Directory Structure

```
tests/
├── ux/                           # User Experience Tests
│   ├── user-flows/               # End-to-end user journeys
│   │   ├── transaction-management.spec.js
│   │   ├── budget-creation.spec.js
│   │   └── goal-tracking.spec.js
│   ├── responsive/               # Responsive design tests
│   │   ├── mobile.spec.js
│   │   ├── tablet.spec.js
│   │   └── desktop.spec.js
│   ├── performance/              # Performance metrics
│   │   ├── core-web-vitals.spec.js
│   │   ├── page-load.spec.js
│   │   └── interaction-timing.spec.js
│   └── feedback/                 # User feedback mechanisms
│       ├── loading-states.spec.js
│       ├── error-messages.spec.js
│       └── success-confirmations.spec.js
│
├── ui/                           # User Interface Tests
│   ├── accessibility/            # WCAG 2.1 AA compliance
│   │   ├── forms.spec.js
│   │   ├── navigation.spec.js
│   │   └── keyboard.spec.js
│   ├── visual-regression/        # Screenshot comparison
│   │   ├── dashboard.spec.js
│   │   ├── transactions.spec.js
│   │   └── budgets.spec.js
│   └── components/               # Individual component tests
│       ├── buttons.spec.js
│       ├── forms.spec.js
│       └── cards.spec.js
│
└── README-UX-UI-TESTING.md       # This file
```

---

## Installation

### Prerequisites

```bash
# Node.js 18+ and npm
node --version  # Should be >= 18

# Install Playwright and dependencies
npm install --save-dev @playwright/test
npm install --save-dev @axe-core/playwright

# Install browsers
npx playwright install chromium
npx playwright install firefox
npx playwright install webkit
```

### Optional Tools

```bash
# For visual regression comparison
npm install --save-dev pixelmatch

# For performance metrics
npm install --save-dev lighthouse
```

---

## Running Tests

### UX Tests

```bash
# All UX tests
npx playwright test tests/ux/

# User flow tests only
npx playwright test tests/ux/user-flows/

# Specific flow
npx playwright test tests/ux/user-flows/transaction-management.spec.js

# Mobile responsiveness
npx playwright test tests/ux/responsive/

# Performance tests
npx playwright test tests/ux/performance/
```

### UI Tests

```bash
# All UI tests
npx playwright test tests/ui/

# Accessibility tests only
npx playwright test tests/ui/accessibility/

# Visual regression tests
npx playwright test tests/ui/visual-regression/

# Update visual baselines (when design changes are intentional)
npx playwright test tests/ui/visual-regression/ --update-snapshots
```

### Test Options

```bash
# Headed mode (see browser)
npx playwright test --headed

# Debug mode (step through tests)
npx playwright test --debug

# Specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit

# Parallel execution
npx playwright test --workers=4

# Generate HTML report
npx playwright test --reporter=html
npx playwright show-report
```

---

## Test Configuration

Create `playwright.config.js` in project root:

```javascript
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',
  timeout: 30000,
  retries: 2,
  workers: process.env.CI ? 1 : 4,

  use: {
    baseURL: 'http://budget.okamih.cz',
    headless: true,
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    trace: 'on-first-retry'
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] }
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] }
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] }
    },
    {
      name: 'mobile-chrome',
      use: { ...devices['Pixel 5'] }
    },
    {
      name: 'mobile-safari',
      use: { ...devices['iPhone 12'] }
    },
    {
      name: 'tablet',
      use: { ...devices['iPad Pro'] }
    }
  ],

  reporter: [
    ['html', { outputFolder: 'playwright-report' }],
    ['json', { outputFile: 'test-results.json' }],
    ['junit', { outputFile: 'junit-results.xml' }],
    ['list']
  ]
});
```

---

## Writing Tests

### UX Test Template

```javascript
const { test, expect } = require('@playwright/test');

async function loginAsTestUser(page) {
  await page.goto('http://budget.okamih.cz/login');
  await page.fill('input[name="email"]', 'demo@example.com');
  await page.fill('input[name="password"]', 'demo123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard');
}

test.describe('Feature Name User Flow', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsTestUser(page);
  });

  test('user can complete primary action', async ({ page }) => {
    const startTime = performance.now();

    // Navigate
    await page.goto('/feature');

    // Interact
    await page.fill('input[name="field"]', 'value');
    await page.click('button[type="submit"]');

    // Verify
    await expect(page.locator('.success')).toBeVisible();

    const completionTime = performance.now() - startTime;
    expect(completionTime).toBeLessThan(5000); // UX goal: < 5s

    console.log(`✓ Completed in ${Math.round(completionTime)}ms`);
  });
});
```

### UI Accessibility Test Template

```javascript
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

test('page passes WCAG 2.1 AA audit', async ({ page }) => {
  await page.goto('http://budget.okamih.cz/page');

  const results = await new AxeBuilder({ page })
    .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
    .analyze();

  if (results.violations.length > 0) {
    console.log('Accessibility violations:');
    results.violations.forEach(v => {
      console.log(`- ${v.id}: ${v.description}`);
    });
  }

  expect(results.violations).toEqual([]);
});
```

### Visual Regression Test Template

```javascript
const { test, expect } = require('@playwright/test');

test('component matches baseline', async ({ page }) => {
  await page.goto('http://budget.okamih.cz/page');

  // Hide dynamic content
  await page.addStyleTag({
    content: `
      .timestamp, .current-time { visibility: hidden !important; }
    `
  });

  await expect(page).toHaveScreenshot('component-name.png', {
    fullPage: true,
    animations: 'disabled',
    maxDiffPixels: 100
  });
});
```

---

## CI/CD Integration

### GitHub Actions

Create `.github/workflows/ux-ui-tests.yml`:

```yaml
name: UX/UI Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'

      - name: Install dependencies
        run: npm ci

      - name: Install Playwright browsers
        run: npx playwright install --with-deps chromium

      - name: Start application
        run: |
          docker-compose up -d
          sleep 10

      - name: Run UX tests
        run: npx playwright test tests/ux/

      - name: Run UI tests
        run: npx playwright test tests/ui/

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: playwright-report/

      - name: Upload screenshots
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: screenshots
          path: test-results/
```

---

## Metrics and Reporting

### UX Metrics

Each UX test tracks:
- **Completion Time:** How long does user flow take?
- **Error Rate:** How often do users encounter errors?
- **Friction Points:** Where do users get stuck?
- **Performance:** Core Web Vitals (LCP, FID, CLS)

### UI Metrics

Each UI test tracks:
- **WCAG Violations:** Count and severity
- **Visual Diffs:** Pixel differences from baseline
- **Color Contrast:** Ratio for all text
- **Keyboard Navigation:** Tab order and focus indicators

### Viewing Reports

```bash
# Generate HTML report
npx playwright test --reporter=html

# Open report in browser
npx playwright show-report

# View specific test trace
npx playwright show-trace trace.zip
```

---

## Troubleshooting

### Common Issues

**Issue: Tests timing out**
```bash
# Increase timeout in playwright.config.js
timeout: 60000  # 60 seconds
```

**Issue: Visual regression false positives**
```bash
# Increase maxDiffPixels threshold
maxDiffPixels: 200
```

**Issue: Accessibility tests failing on third-party components**
```bash
# Exclude specific elements
await new AxeBuilder({ page })
  .exclude('#third-party-widget')
  .analyze();
```

**Issue: Flaky tests**
```bash
# Add explicit waits
await page.waitForLoadState('networkidle');
await page.waitForSelector('.element', { state: 'visible' });
```

---

## Best Practices

### UX Testing

1. **Test Real User Flows:** Don't just test features in isolation
2. **Measure Time:** Every flow should have a time goal
3. **Test All Viewports:** Mobile, tablet, desktop
4. **Consider Performance:** Track Core Web Vitals
5. **Think Like Users:** Test what users care about

### UI Testing

1. **WCAG Compliance:** Run accessibility audits on every page
2. **Keyboard First:** Test all interactions with keyboard
3. **Visual Baselines:** Update snapshots when design changes intentionally
4. **Color Contrast:** Verify 4.5:1 ratio for all text
5. **Screen Readers:** Use ARIA labels and landmarks

### General

1. **Stable Selectors:** Use data-testid attributes
2. **Clean Test Data:** Each test should create its own data
3. **Avoid Hardcoded Waits:** Use Playwright's auto-waiting
4. **Document Issues:** File tickets for failures with screenshots
5. **Keep Tests Fast:** Run in parallel, minimize setup time

---

## Agent Integration

### Invoking UX Tester Agent

```markdown
@ux-tester Test the transaction creation flow and measure completion time
@ux-tester Run responsive design tests on budget page
@ux-tester Audit Core Web Vitals for dashboard
```

### Invoking UI Tester Agent

```markdown
@ui-tester Run WCAG 2.1 AA audit on all forms
@ui-tester Take baseline screenshots for visual regression
@ui-tester Verify keyboard navigation on transaction form
```

### Test Results Format

Agents report results in structured format:

```json
{
  "test_suite": "ux/user-flows/transaction-management",
  "total_tests": 8,
  "passed": 6,
  "failed": 2,
  "duration": "45.3s",
  "issues": [
    {
      "test": "user can create transaction efficiently",
      "status": "failed",
      "reason": "Completion time exceeded goal",
      "expected": "< 10000ms",
      "actual": "12500ms",
      "severity": "medium"
    }
  ],
  "metrics": {
    "avg_completion_time": "8.2s",
    "error_rate": "5%",
    "performance_score": 85
  }
}
```

---

## Resources

### Documentation

- [Playwright Docs](https://playwright.dev)
- [Axe-Core Rules](https://github.com/dequelabs/axe-core/blob/develop/doc/rule-descriptions.md)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [Web Vitals](https://web.dev/vitals/)

### Tools

- [Playwright Inspector](https://playwright.dev/docs/debug)
- [Accessibility Insights](https://accessibilityinsights.io/)
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- [Lighthouse](https://developers.google.com/web/tools/lighthouse)

---

## Version History

**v1.0** (2025-11-15)
- Initial UX/UI testing framework
- UX test examples (user flows, responsive, performance)
- UI test examples (accessibility, visual regression)
- Agent integration documentation

---

**Questions?** See `/var/www/budget-control/agents/ux-tester.md` and `ui-tester.md` for detailed agent documentation.
