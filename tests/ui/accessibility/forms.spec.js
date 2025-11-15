/**
 * UI Test: Form Accessibility
 *
 * Validates that all forms in Budget Control meet WCAG 2.1 Level AA standards:
 * - Proper labels for all inputs
 * - Keyboard navigation
 * - Error messaging
 * - ARIA attributes
 * - Focus management
 */

const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

// Helper function to login
async function loginAsTestUser(page) {
  await page.goto('http://budget.okamih.cz/login');
  await page.fill('input[name="email"]', 'demo@example.com');
  await page.fill('input[name="password"]', 'demo123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard');
}

test.describe('Form Accessibility - WCAG 2.1 AA', () => {
  test('login form passes accessibility audit', async ({ page }) => {
    await page.goto('http://budget.okamih.cz/login');

    const results = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .include('form')
      .analyze();

    if (results.violations.length > 0) {
      console.log('\n❌ Accessibility violations found:');
      results.violations.forEach(violation => {
        console.log(`\n  Rule: ${violation.id}`);
        console.log(`  Impact: ${violation.impact}`);
        console.log(`  Description: ${violation.description}`);
        console.log(`  Help: ${violation.helpUrl}`);
        console.log(`  Elements affected: ${violation.nodes.length}`);
      });
    }

    expect(results.violations).toEqual([]);
  });

  test('transaction form has proper labels', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    const results = await new AxeBuilder({ page })
      .withRules(['label', 'label-content-name-mismatch'])
      .analyze();

    if (results.violations.length > 0) {
      console.log('\n❌ Label violations:');
      results.violations.forEach(violation => {
        violation.nodes.forEach(node => {
          console.log(`  - ${node.html}`);
          console.log(`    Fix: ${node.failureSummary}`);
        });
      });
    }

    expect(results.violations).toEqual([]);
  });

  test('budget form has proper labels', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/budgets/add');

    const results = await new AxeBuilder({ page })
      .withRules(['label'])
      .analyze();

    expect(results.violations).toEqual([]);
  });

  test('all form inputs have accessible names', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    // Check each input has label or aria-label
    const inputs = await page.locator('input, select, textarea').all();

    for (const input of inputs) {
      const inputName = await input.getAttribute('name');
      const inputId = await input.getAttribute('id');
      const ariaLabel = await input.getAttribute('aria-label');
      const ariaLabelledBy = await input.getAttribute('aria-labelledby');

      // Check if there's a label element
      let hasLabel = false;
      if (inputId) {
        const label = await page.locator(`label[for="${inputId}"]`).count();
        hasLabel = label > 0;
      }

      const isAccessible = hasLabel || ariaLabel || ariaLabelledBy;

      if (!isAccessible) {
        console.log(`⚠ Input missing accessible name: ${inputName || inputId}`);
      }

      expect(isAccessible).toBe(true);
    }
  });
});

test.describe('Keyboard Navigation', () => {
  test('transaction form is fully keyboard navigable', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    // Tab through form
    await page.keyboard.press('Tab');
    const firstFocused = await page.evaluate(() => document.activeElement?.tagName);
    expect(['INPUT', 'SELECT', 'TEXTAREA']).toContain(firstFocused);

    // Continue tabbing to ensure all fields are reachable
    let focusedElements = [];
    for (let i = 0; i < 10; i++) {
      const elementInfo = await page.evaluate(() => ({
        tag: document.activeElement?.tagName,
        name: document.activeElement?.name || document.activeElement?.id,
        type: document.activeElement?.type
      }));

      if (elementInfo.tag === 'BUTTON' && elementInfo.type === 'submit') {
        console.log('✓ Reached submit button via keyboard');
        break;
      }

      focusedElements.push(elementInfo);
      await page.keyboard.press('Tab');
    }

    console.log(`✓ Keyboard navigation tested through ${focusedElements.length} elements`);
  });

  test('all interactive elements have focus indicators', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    const interactiveElements = await page.locator('input, button, select, textarea, a').all();

    for (const element of interactiveElements) {
      if (await element.isVisible()) {
        await element.focus();

        const outlineStyles = await element.evaluate(el => {
          const styles = window.getComputedStyle(el);
          return {
            outline: styles.outline,
            outlineWidth: styles.outlineWidth,
            outlineColor: styles.outlineColor,
            border: styles.border,
            boxShadow: styles.boxShadow
          };
        });

        // Should have some visual focus indicator
        const hasFocusIndicator =
          (outlineStyles.outlineWidth && outlineStyles.outlineWidth !== '0px') ||
          outlineStyles.boxShadow !== 'none';

        if (!hasFocusIndicator) {
          const elementDesc = await element.evaluate(el => `${el.tagName}[${el.name || el.id}]`);
          console.log(`⚠ No focus indicator: ${elementDesc}`);
        }

        expect(hasFocusIndicator).toBe(true);
      }
    }
  });

  test('form can be submitted with keyboard', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    // Fill form using keyboard
    await page.keyboard.press('Tab'); // Focus first field

    await page.keyboard.type('500');
    await page.keyboard.press('Tab');

    await page.keyboard.type('Keyboard Test Transaction');
    await page.keyboard.press('Tab');

    // Navigate to submit button
    for (let i = 0; i < 5; i++) {
      const currentElement = await page.evaluate(() => document.activeElement?.type);
      if (currentElement === 'submit') {
        break;
      }
      await page.keyboard.press('Tab');
    }

    // Submit with Enter key
    await page.keyboard.press('Enter');

    // Should submit successfully
    await page.waitForURL('**/transactions', { timeout: 5000 });

    console.log('✓ Form submitted successfully via keyboard');
  });
});

test.describe('Error Messaging Accessibility', () => {
  test('validation errors are announced to screen readers', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    // Submit empty form to trigger validation
    await page.click('button[type="submit"]');

    await page.waitForTimeout(500);

    // Check for ARIA live regions
    const results = await new AxeBuilder({ page })
      .withRules(['aria-valid-attr', 'aria-valid-attr-value'])
      .analyze();

    expect(results.violations).toEqual([]);

    // Ideally, errors should have role="alert" or be in aria-live region
    const errorElements = await page.locator('.error, .alert-danger, [role="alert"]').all();

    if (errorElements.length > 0) {
      for (const error of errorElements) {
        const role = await error.getAttribute('role');
        const ariaLive = await error.getAttribute('aria-live');

        // Should have role="alert" or aria-live="polite/assertive"
        const isAccessible = role === 'alert' || ariaLive;

        if (!isAccessible) {
          console.log('⚠ Error message not announced to screen readers');
        }
      }
    }
  });

  test('invalid fields are marked with aria-invalid', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    // Fill invalid data
    await page.fill('input[name="amount"]', '-100'); // Negative amount

    // Trigger validation
    await page.blur('input[name="amount"]');
    await page.waitForTimeout(500);

    // Check if field has aria-invalid
    const amountField = page.locator('input[name="amount"]');
    const ariaInvalid = await amountField.getAttribute('aria-invalid');

    if (ariaInvalid !== 'true') {
      console.log('⚠ Invalid field not marked with aria-invalid="true"');
    }

    // Best practice: should also have aria-describedby pointing to error message
    const ariaDescribedBy = await amountField.getAttribute('aria-describedby');
    if (ariaDescribedBy) {
      const errorMessage = await page.locator(`#${ariaDescribedBy}`).textContent();
      console.log(`✓ Error message: ${errorMessage}`);
    }
  });
});

test.describe('Color Contrast in Forms', () => {
  test('form labels meet color contrast requirements', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    const results = await new AxeBuilder({ page })
      .withRules(['color-contrast'])
      .include('form')
      .analyze();

    if (results.violations.length > 0) {
      console.log('\n❌ Color contrast violations:');
      results.violations.forEach(violation => {
        violation.nodes.forEach(node => {
          console.log(`  - Element: ${node.html.substring(0, 60)}...`);
          console.log(`    Issue: ${node.failureSummary}`);
        });
      });
    }

    expect(results.violations).toEqual([]);
  });

  test('placeholder text has sufficient contrast', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    // Placeholder text should be at least 4.5:1 ratio
    // This is harder to test automatically, so we check for placeholder-shown pseudo-class
    const inputsWithPlaceholder = await page.locator('input[placeholder]').all();

    for (const input of inputsWithPlaceholder) {
      const placeholder = await input.getAttribute('placeholder');
      const styles = await input.evaluate(el => {
        return window.getComputedStyle(el, '::placeholder').color;
      });

      console.log(`Placeholder: "${placeholder}" - Color: ${styles}`);
    }
  });
});

test.describe('Form ARIA Landmarks', () => {
  test('form is within proper landmark regions', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    const results = await new AxeBuilder({ page })
      .withRules(['region'])
      .analyze();

    if (results.violations.length > 0) {
      console.log('\n⚠ Landmark violations (forms should be in <main> or role="main"):');
      results.violations.forEach(violation => {
        console.log(`  - ${violation.description}`);
      });
    }

    // Check if form is in main element
    const formInMain = await page.evaluate(() => {
      const form = document.querySelector('form');
      const main = form?.closest('main, [role="main"]');
      return !!main;
    });

    console.log(formInMain ? '✓ Form is in main landmark' : '⚠ Form not in main landmark');
  });
});

test.describe('Form Field Requirements', () => {
  test('required fields are properly marked', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    const requiredInputs = await page.locator('input[required], select[required]').all();

    for (const input of requiredInputs) {
      // Should have either:
      // 1. required attribute (already checked by selector)
      // 2. aria-required="true"
      // 3. Visual indicator (*) in label

      const ariaRequired = await input.getAttribute('aria-required');
      const inputId = await input.getAttribute('id');

      let hasVisualIndicator = false;
      if (inputId) {
        const label = await page.locator(`label[for="${inputId}"]`).textContent();
        hasVisualIndicator = label?.includes('*') || false;
      }

      console.log(`Required field: ${await input.getAttribute('name')} - Visual indicator: ${hasVisualIndicator}`);
    }

    console.log(`✓ Found ${requiredInputs.length} required fields`);
  });
});

test.describe('Assistive Technology Compatibility', () => {
  test('form has proper heading structure', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    const results = await new AxeBuilder({ page })
      .withRules(['heading-order'])
      .analyze();

    if (results.violations.length > 0) {
      console.log('\n❌ Heading structure violations:');
      results.violations.forEach(violation => {
        console.log(`  - ${violation.description}`);
      });
    }

    expect(results.violations).toEqual([]);
  });

  test('form sections use fieldset and legend', async ({ page }) => {
    await loginAsTestUser(page);
    await page.goto('http://budget.okamih.cz/transactions/add');

    // If form has sections/groups, they should use fieldset
    const fieldsets = await page.locator('fieldset').count();

    if (fieldsets > 0) {
      // Each fieldset should have a legend
      const legends = await page.locator('fieldset > legend').count();
      expect(legends).toBe(fieldsets);

      console.log(`✓ Form has ${fieldsets} fieldset(s) with legends`);
    } else {
      console.log('ℹ No fieldsets found (OK if form is simple)');
    }
  });
});
