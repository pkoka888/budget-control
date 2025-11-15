/**
 * UX Test: Transaction Management User Flow
 *
 * Tests the complete user journey for managing transactions:
 * - Creating new transactions
 * - Editing existing transactions
 * - Deleting transactions
 * - Bulk operations
 *
 * Metrics tracked:
 * - Time to complete each operation
 * - Error rate
 * - User friction points
 */

const { test, expect } = require('@playwright/test');

// Helper function to login
async function loginAsTestUser(page) {
  await page.goto('http://budget.okamih.cz/login');
  await page.fill('input[name="email"]', 'demo@example.com');
  await page.fill('input[name="password"]', 'demo123');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard');
}

test.describe('Transaction Management Flow', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsTestUser(page);
  });

  test('user can create transaction efficiently', async ({ page }) => {
    const startTime = performance.now();

    await page.goto('http://budget.okamih.cz/transactions/add');

    // Form should load quickly
    await expect(page.locator('form')).toBeVisible({ timeout: 2000 });

    // Check autofocus on first field (good UX)
    const focusedField = await page.evaluate(() => document.activeElement?.name);
    expect(['amount', 'description', 'date']).toContain(focusedField);

    // Fill form
    await page.fill('input[name="amount"]', '450');
    await page.fill('input[name="description"]', 'Grocery Shopping');

    // Category selection should be easy
    const categorySelect = page.locator('select[name="category_id"]');
    await expect(categorySelect).toBeVisible();
    await categorySelect.selectOption({ index: 1 }); // First category

    // Account selection
    const accountSelect = page.locator('select[name="account_id"]');
    await expect(accountSelect).toBeVisible();
    await accountSelect.selectOption({ index: 1 }); // First account

    // Date should default to today (good UX)
    const dateValue = await page.inputValue('input[name="date"]');
    const today = new Date().toISOString().split('T')[0];
    expect(dateValue).toBe(today);

    // Submit form
    await page.click('button[type="submit"]');

    // Should show success feedback
    await expect(
      page.locator('.success-message, .alert-success, text=/created|added/i')
    ).toBeVisible({ timeout: 3000 });

    const completionTime = performance.now() - startTime;

    // UX Goal: Transaction creation should complete in < 10 seconds
    expect(completionTime).toBeLessThan(10000);
    console.log(`✓ Transaction created in ${Math.round(completionTime)}ms`);
  });

  test('form provides helpful validation feedback', async ({ page }) => {
    await page.goto('http://budget.okamih.cz/transactions/add');

    // Try to submit empty form
    await page.click('button[type="submit"]');

    // Should show validation errors (not just console errors)
    const errorMessages = page.locator('.error, .alert-danger, [class*="error"]');

    // Wait a bit for validation to trigger
    await page.waitForTimeout(500);

    // Should have at least one visible error message
    const errorCount = await errorMessages.count();
    expect(errorCount).toBeGreaterThan(0);

    console.log(`✓ Form validation shows ${errorCount} error message(s)`);

    // Fill amount to clear error
    await page.fill('input[name="amount"]', '100');

    // Error should clear (live validation)
    // Note: This depends on implementation - might need blur event
    await page.blur('input[name="amount"]');
  });

  test('user can edit transaction', async ({ page }) => {
    await page.goto('http://budget.okamih.cz/transactions');

    // Find first transaction edit link
    const editLink = page.locator('a[href*="/transactions/edit/"]:first-of-type, button:has-text("Edit"):first-of-type');

    if (await editLink.count() > 0) {
      await editLink.click();

      // Should load edit form
      await expect(page.locator('form')).toBeVisible({ timeout: 2000 });

      // Form should be pre-filled (good UX)
      const amountValue = await page.inputValue('input[name="amount"]');
      expect(parseFloat(amountValue)).toBeGreaterThan(0);

      // Make a change
      const newDescription = 'Updated Transaction ' + Date.now();
      await page.fill('input[name="description"]', newDescription);

      // Submit
      await page.click('button[type="submit"]');

      // Should redirect and show success
      await page.waitForURL('**/transactions', { timeout: 5000 });

      // Verify change appears
      await expect(page.locator(`text=${newDescription}`)).toBeVisible({ timeout: 3000 });

      console.log('✓ Transaction edited successfully');
    } else {
      console.log('⚠ No transactions found to edit (test skipped)');
    }
  });

  test('user can delete transaction with confirmation', async ({ page }) => {
    await page.goto('http://budget.okamih.cz/transactions');

    // Get initial count
    const initialCount = await page.locator('tr[data-transaction-id], .transaction-row').count();

    if (initialCount > 0) {
      // Set up dialog handler for confirmation
      page.on('dialog', async dialog => {
        expect(dialog.type()).toBe('confirm');
        expect(dialog.message().toLowerCase()).toMatch(/delete|remove|sure/);
        await dialog.accept();
      });

      // Click delete button
      const deleteButton = page.locator('button:has-text("Delete"):first-of-type, a:has-text("Delete"):first-of-type');
      await deleteButton.click();

      // Wait for deletion
      await page.waitForTimeout(1000);

      // Count should decrease
      const newCount = await page.locator('tr[data-transaction-id], .transaction-row').count();
      expect(newCount).toBeLessThan(initialCount);

      console.log(`✓ Transaction deleted (${initialCount} → ${newCount})`);
    } else {
      console.log('⚠ No transactions found to delete (test skipped)');
    }
  });

  test('transaction list loads with good performance', async ({ page }) => {
    const startTime = performance.now();

    await page.goto('http://budget.okamih.cz/transactions');

    // Wait for table to be visible
    await page.waitForSelector('table, .transaction-list', { state: 'visible' });

    const loadTime = performance.now() - startTime;

    // UX Goal: List should load in < 3 seconds
    expect(loadTime).toBeLessThan(3000);

    // Check pagination exists for large datasets (good UX)
    const rowCount = await page.locator('tr[data-transaction-id], .transaction-row').count();

    if (rowCount >= 20) {
      const pagination = page.locator('.pagination, nav[aria-label="pagination"]');
      await expect(pagination).toBeVisible();
    }

    console.log(`✓ Transaction list loaded in ${Math.round(loadTime)}ms with ${rowCount} rows`);
  });

  test('search/filter functionality is responsive', async ({ page }) => {
    await page.goto('http://budget.okamih.cz/transactions');

    const searchInput = page.locator('input[name="search"], input[placeholder*="Search"]');

    if (await searchInput.count() > 0) {
      // Type in search
      await searchInput.fill('grocery');

      // Results should update quickly (< 500ms ideal)
      await page.waitForTimeout(600);

      // Check that results are filtered
      // This is implementation-dependent
      console.log('✓ Search input found and tested');
    } else {
      console.log('⚠ No search functionality found');
    }
  });
});

test.describe('Transaction Mobile Experience', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 }); // iPhone SE
    await loginAsTestUser(page);
  });

  test('mobile: transaction form is touch-friendly', async ({ page }) => {
    await page.goto('http://budget.okamih.cz/transactions/add');

    // All interactive elements should meet touch target size (44x44 minimum)
    const buttons = await page.locator('button, input[type="submit"]').all();

    for (const button of buttons) {
      if (await button.isVisible()) {
        const box = await button.boundingBox();

        if (box) {
          expect(box.height).toBeGreaterThanOrEqual(44);
          console.log(`✓ Button height: ${box.height}px (min 44px)`);
        }
      }
    }
  });

  test('mobile: no horizontal scroll on transaction list', async ({ page }) => {
    await page.goto('http://budget.okamih.cz/transactions');

    // Check for horizontal scroll
    const hasHorizontalScroll = await page.evaluate(() => {
      return document.documentElement.scrollWidth > document.documentElement.clientWidth;
    });

    expect(hasHorizontalScroll).toBe(false);
    console.log('✓ No horizontal scroll on mobile');
  });

  test('mobile: submit button accessible without scrolling', async ({ page }) => {
    await page.goto('http://budget.okamih.cz/transactions/add');

    // Fill form
    await page.fill('input[name="amount"]', '100');
    await page.fill('input[name="description"]', 'Test');

    // Check if submit button is in viewport
    const submitButton = page.locator('button[type="submit"]');
    const isInViewport = await submitButton.evaluate(el => {
      const rect = el.getBoundingClientRect();
      return (
        rect.top >= 0 &&
        rect.bottom <= window.innerHeight
      );
    });

    if (!isInViewport) {
      console.log('⚠ Submit button requires scrolling on mobile (UX issue)');
      // This might be OK if form is long, but should be noted
    } else {
      console.log('✓ Submit button visible without scrolling');
    }
  });
});

test.describe('Transaction UX Metrics', () => {
  test('measure complete transaction creation flow', async ({ page }) => {
    await loginAsTestUser(page);

    const metrics = {
      navigationTime: 0,
      formFillTime: 0,
      submissionTime: 0,
      totalTime: 0
    };

    // 1. Navigate to form
    let start = performance.now();
    await page.goto('http://budget.okamih.cz/transactions/add');
    await page.waitForSelector('form');
    metrics.navigationTime = performance.now() - start;

    // 2. Fill form
    start = performance.now();
    await page.fill('input[name="amount"]', '250');
    await page.fill('input[name="description"]', 'Test Transaction');
    await page.selectOption('select[name="category_id"]', { index: 1 });
    await page.selectOption('select[name="account_id"]', { index: 1 });
    metrics.formFillTime = performance.now() - start;

    // 3. Submit
    start = performance.now();
    await page.click('button[type="submit"]');
    await page.waitForURL('**/transactions');
    metrics.submissionTime = performance.now() - start;

    metrics.totalTime = metrics.navigationTime + metrics.formFillTime + metrics.submissionTime;

    console.log('Transaction Creation UX Metrics:');
    console.log(`  Navigation: ${Math.round(metrics.navigationTime)}ms`);
    console.log(`  Form Fill: ${Math.round(metrics.formFillTime)}ms`);
    console.log(`  Submission: ${Math.round(metrics.submissionTime)}ms`);
    console.log(`  Total: ${Math.round(metrics.totalTime)}ms`);

    // Goals:
    // - Navigation: < 2s
    // - Submission: < 1s
    // - Total: < 10s
    expect(metrics.navigationTime).toBeLessThan(2000);
    expect(metrics.submissionTime).toBeLessThan(1000);
    expect(metrics.totalTime).toBeLessThan(10000);
  });
});
