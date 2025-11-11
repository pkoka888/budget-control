/**
 * Accessibility Testing Suite
 * Tests WCAG compliance and accessibility features
 */

const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;

test.describe('Budget Control - Accessibility Tests', () => {
  test('should pass accessibility audit on login page', async ({ page }) => {
    await page.goto('/login');

    const accessibilityScanResults = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test('should pass accessibility audit on register page', async ({ page }) => {
    await page.goto('/register');

    const accessibilityScanResults = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa'])
      .analyze();

    expect(accessibilityScanResults.violations).toEqual([]);
  });

  test('should have proper ARIA labels and roles', async ({ page }) => {
    await page.goto('/login');

    // Check for proper form labels
    const emailInput = page.locator('input[type="email"]');
    await expect(emailInput).toHaveAttribute('aria-label', /.*/);

    const passwordInput = page.locator('input[type="password"]');
    await expect(passwordInput).toHaveAttribute('aria-label', /.*/);

    // Check for proper button roles
    const submitButton = page.locator('button[type="submit"]');
    await expect(submitButton).toHaveAttribute('aria-label', /.*/);
  });

  test('should support keyboard navigation', async ({ page }) => {
    await page.goto('/login');

    // Tab through form elements
    await page.keyboard.press('Tab');
    let focusedElement = await page.evaluate(() => document.activeElement?.tagName);
    expect(['INPUT', 'BUTTON']).toContain(focusedElement);

    await page.keyboard.press('Tab');
    focusedElement = await page.evaluate(() => document.activeElement?.tagName);
    expect(['INPUT', 'BUTTON']).toContain(focusedElement);
  });

  test('should have sufficient color contrast', async ({ page }) => {
    await page.goto('/login');

    // Check contrast ratios using axe
    const results = await new AxeBuilder({ page })
      .withRules(['color-contrast'])
      .analyze();

    // Should not have color contrast violations
    const contrastViolations = results.violations.filter(v => v.id === 'color-contrast');
    expect(contrastViolations.length).toBe(0);
  });

  test('should support screen reader navigation', async ({ page }) => {
    await page.goto('/login');

    // Check for semantic HTML structure
    const headings = await page.locator('h1, h2, h3, h4, h5, h6').count();
    expect(headings).toBeGreaterThan(0);

    // Check for landmarks
    const main = await page.locator('main').count();
    expect(main).toBeGreaterThan(0);

    // Check for form structure
    const form = await page.locator('form');
    await expect(form).toHaveAttribute('role', 'form');
  });

  test('should handle focus management properly', async ({ page }) => {
    await page.goto('/login');

    // Focus should be on the first input field
    const activeElement = await page.evaluate(() => document.activeElement?.tagName);
    expect(activeElement).toBe('INPUT');

    // Focus should move logically through the form
    await page.keyboard.press('Tab');
    const secondActiveElement = await page.evaluate(() => document.activeElement?.tagName);
    expect(['INPUT', 'BUTTON']).toContain(secondActiveElement);
  });
});
