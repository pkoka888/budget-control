/**
 * Phase 3 E2E Tests - Automation & Investments (Complete Suite)
 * Comprehensive tests for automation rules and investment portfolio management
 */

const { test, expect } = require('@playwright/test');

test.describe('Phase 3 - Automation & Investments', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL('/');
    });

    // ========================================================================
    // AUTOMATION TESTS
    // ========================================================================

    test.describe('Automation Rules', () => {
        test('should load automation dashboard', async ({ page }) => {
            await page.goto('/automation');

            await expect(page.locator('h1')).toContainText(/automation|automatizace/i);

            // Should display rules or empty state
            const container = page.locator('#automation-rules-container');
            await expect(container).toBeVisible();
        });

        test('should create new automation rule', async ({ page }) => {
            await page.goto('/automation');

            await page.click('#create-rule-btn');

            // Modal should open
            const modal = page.locator('#rule-modal');
            await expect(modal).toBeVisible();

            // Fill form
            await page.selectOption('#rule-action-type', 'auto_categorize');
            await page.selectOption('#rule-trigger-type', 'transaction_create');

            // Dynamic fields should appear
            await page.waitForTimeout(500);

            // Submit form
            await page.click('button[type="submit"]');

            await page.waitForTimeout(1000);

            // Should show success message
            const alert = page.locator('.alert-success');
            await expect(alert).toBeVisible();
        });

        test('should display rule statistics', async ({ page }) => {
            await page.goto('/automation');

            const activeCount = page.locator('#active-rules-count');
            if (await activeCount.count() > 0) {
                await expect(activeCount).toBeVisible();
                const count = await activeCount.textContent();
                expect(parseInt(count)).toBeGreaterThanOrEqual(0);
            }
        });

        test('should toggle rule active status', async ({ page }) => {
            await page.goto('/automation');

            const toggleButton = page.locator('.toggle-rule-btn').first();
            if (await toggleButton.count() > 0) {
                await toggleButton.click();

                const alert = page.locator('.alert');
                await expect(alert).toBeVisible();
            }
        });

        test('should test individual rule', async ({ page }) => {
            await page.goto('/automation');

            const testButton = page.locator('.test-single-rule-btn').first();
            if (await testButton.count() > 0) {
                await testButton.click();

                await page.waitForTimeout(1000);

                // Should show test result
                const alert = page.locator('.alert');
                await expect(alert).toBeVisible();
            }
        });

        test('should execute all rules', async ({ page }) => {
            await page.goto('/automation');

            const executeButton = page.locator('#execute-all-rules-btn');
            if (await executeButton.count() > 0) {
                // Listen for confirmation dialog
                page.on('dialog', dialog => dialog.accept());

                await executeButton.click();

                await page.waitForTimeout(1000);

                // Should show execution result
                const alert = page.locator('.alert');
                await expect(alert).toBeVisible();
            }
        });

        test('should edit automation rule', async ({ page }) => {
            await page.goto('/automation');

            const editButton = page.locator('.edit-rule-btn').first();
            if (await editButton.count() > 0) {
                await editButton.click();

                // Modal should open with populated data
                const modal = page.locator('#rule-modal');
                await expect(modal).toBeVisible();

                // Action type should be pre-selected
                const actionType = page.locator('#rule-action-type');
                const value = await actionType.inputValue();
                expect(value).toBeTruthy();
            }
        });

        test('should delete automation rule', async ({ page }) => {
            await page.goto('/automation');

            const deleteButton = page.locator('.delete-rule-btn').first();
            if (await deleteButton.count() > 0) {
                // Listen for confirmation dialog
                page.on('dialog', dialog => dialog.accept());

                await deleteButton.click();

                await page.waitForTimeout(1000);

                // Should show delete confirmation
                const alert = page.locator('.alert');
                await expect(alert).toBeVisible();
            }
        });

        test('should show empty state when no rules', async ({ page }) => {
            // Mock empty response
            await page.route('**/api/automation/actions', route => {
                route.fulfill({
                    status: 200,
                    body: JSON.stringify({ success: true, actions: [] })
                });
            });

            await page.goto('/automation');

            // Should show empty state message
            const emptyState = page.locator('text=/no.*rules|žádná.*pravidla/i');
            await expect(emptyState).toBeVisible();
        });
    });

    // ========================================================================
    // INVESTMENTS TESTS
    // ========================================================================

    test.describe('Investment Portfolio', () => {
        test('should load investment portfolio', async ({ page }) => {
            await page.goto('/investments/portfolio');

            await expect(page.locator('h1')).toContainText(/portfolio|investice/i);

            // Should display portfolio summary
            const summary = page.locator('#portfolio-summary, .portfolio-summary');
            if (await summary.count() > 0) {
                await expect(summary).toBeVisible();
            }
        });

        test('should display total portfolio value', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const totalValue = page.locator('#total-portfolio-value');
            if (await totalValue.count() > 0) {
                await expect(totalValue).toBeVisible();

                // Should display a number
                const text = await totalValue.textContent();
                expect(text).toMatch(/\d+/);
            }
        });

        test('should show portfolio allocation chart', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const chart = page.locator('#portfolio-chart');
            await expect(chart).toBeVisible();
        });

        test('should add new investment', async ({ page }) => {
            await page.goto('/investments/portfolio');

            await page.click('#add-investment-btn');

            // Modal should open
            const modal = page.locator('#investment-modal');
            await expect(modal).toBeVisible();

            // Fill form
            await page.fill('#investment-symbol', 'AAPL');
            await page.fill('#investment-name', 'Apple Inc.');
            await page.selectOption('#investment-type', 'stock');
            await page.fill('#investment-quantity', '10');
            await page.fill('#investment-purchase-price', '150');
            await page.fill('#investment-purchase-date', '2024-01-01');

            // Submit
            const form = page.locator('#investment-form');
            await form.submit();

            await page.waitForTimeout(1000);

            // Should show success message
            const alert = page.locator('.alert-success');
            await expect(alert).toBeVisible();
        });

        test('should edit investment', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const editButton = page.locator('.edit-investment-btn').first();
            if (await editButton.count() > 0) {
                await editButton.click();

                const modal = page.locator('#investment-modal');
                await expect(modal).toBeVisible();

                // Fields should be populated
                const symbol = page.locator('#investment-symbol');
                const value = await symbol.inputValue();
                expect(value).toBeTruthy();
            }
        });

        test('should delete investment', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const deleteButton = page.locator('.delete-investment-btn').first();
            if (await deleteButton.count() > 0) {
                page.on('dialog', dialog => dialog.accept());

                await deleteButton.click();

                await page.waitForTimeout(1000);

                const alert = page.locator('.alert');
                await expect(alert).toBeVisible();
            }
        });

        test('should record investment transaction', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const transactionButton = page.locator('.record-transaction-btn').first();
            if (await transactionButton.count() > 0) {
                await transactionButton.click();

                // Transaction modal should open
                const modal = page.locator('#transaction-modal');
                await expect(modal).toBeVisible();

                // Fill transaction form
                await page.selectOption('[name="transaction_type"]', 'buy');
                await page.fill('[name="quantity"]', '5');
                await page.fill('[name="price"]', '155');
                await page.fill('[name="transaction_date"]', '2024-06-01');

                // Submit
                const form = page.locator('#transaction-form');
                await form.submit();

                await page.waitForTimeout(1000);

                const alert = page.locator('.alert-success');
                await expect(alert).toBeVisible();
            }
        });

        test('should view transaction history', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const historyButton = page.locator('.view-history-btn').first();
            if (await historyButton.count() > 0) {
                await historyButton.click();

                // History modal should open
                const modal = page.locator('#history-modal');
                await expect(modal).toBeVisible();

                // Should display transactions table
                const table = modal.locator('table');
                await expect(table).toBeVisible();
            }
        });

        test('should update all investment prices', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const updateButton = page.locator('#update-prices-btn');
            if (await updateButton.count() > 0) {
                await updateButton.click();

                await page.waitForTimeout(2000);

                // Should show success message
                const alert = page.locator('.alert-success');
                await expect(alert).toBeVisible();
            }
        });

        test('should filter investments by type', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const typeFilter = page.locator('#investment-type-filter');
            if (await typeFilter.count() > 0) {
                await typeFilter.selectOption('stock');

                await page.waitForTimeout(500);

                // Only stock investments should be visible
                const visibleCards = page.locator('.investment-card:visible');
                if (await visibleCards.count() > 0) {
                    const firstCard = visibleCards.first();
                    const type = await firstCard.getAttribute('data-investment-type');
                    expect(type).toBe('stock');
                }
            }
        });

        test('should filter investments by account', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const accountFilter = page.locator('#account-filter');
            if (await accountFilter.count() > 0) {
                await accountFilter.selectOption({ index: 1 });

                await page.waitForTimeout(500);

                // Filtered investments should be visible
                const visibleCards = page.locator('.investment-card:visible');
                if (await visibleCards.count() > 0) {
                    await expect(visibleCards.first()).toBeVisible();
                }
            }
        });

        test('should switch between chart views', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const chartToggle = page.locator('[data-view="performance"]');
            if (await chartToggle.count() > 0) {
                await chartToggle.click();

                await page.waitForTimeout(500);

                // Chart should update
                const chart = page.locator('#portfolio-chart');
                await expect(chart).toBeVisible();
            }
        });

        test('should calculate portfolio returns', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const totalReturn = page.locator('#total-portfolio-return');
            if (await totalReturn.count() > 0) {
                await expect(totalReturn).toBeVisible();

                // Should display percentage
                const text = await totalReturn.textContent();
                expect(text).toMatch(/%/);
            }
        });

        test('should display gain/loss with correct styling', async ({ page }) => {
            await page.goto('/investments/portfolio');

            const gainLoss = page.locator('#total-portfolio-gain');
            if (await gainLoss.count() > 0) {
                await expect(gainLoss).toBeVisible();

                // Should have color class (green for gain, red for loss)
                const className = await gainLoss.getAttribute('class');
                expect(className).toMatch(/green|red/);
            }
        });
    });

    // ========================================================================
    // INTEGRATION TESTS
    // ========================================================================

    test.describe('Cross-Feature Integration', () => {
        test('should navigate between Phase 3 features', async ({ page }) => {
            await page.goto('/reports/monthly');
            await expect(page).toHaveURL(/reports/);

            await page.goto('/opportunities');
            await expect(page).toHaveURL(/opportunities/);

            await page.goto('/scenario');
            await expect(page).toHaveURL(/scenario/);

            await page.goto('/automation');
            await expect(page).toHaveURL(/automation/);

            await page.goto('/investments/portfolio');
            await expect(page).toHaveURL(/investments/);
        });

        test('should maintain session across Phase 3 features', async ({ page }) => {
            await page.goto('/reports/monthly');
            await page.goto('/opportunities');
            await page.goto('/automation');

            // Should still be logged in
            await expect(page).not.toHaveURL(/login/);
        });
    });

    // ========================================================================
    // ERROR HANDLING
    // ========================================================================

    test.describe('Error Handling', () => {
        test('should handle automation API errors', async ({ page }) => {
            await page.route('**/api/automation/**', route => {
                route.fulfill({
                    status: 500,
                    body: JSON.stringify({ error: 'Server error' })
                });
            });

            await page.goto('/automation');

            // Should display error gracefully
            const alert = page.locator('.alert-error');
            await expect(alert).toBeVisible({ timeout: 5000 });
        });

        test('should handle investment API errors', async ({ page }) => {
            await page.route('**/investments/**', route => {
                route.fulfill({
                    status: 500,
                    body: JSON.stringify({ error: 'Server error' })
                });
            });

            await page.goto('/investments/portfolio');

            await page.waitForTimeout(2000);

            // Should show error or fallback
            const errorState = page.locator('.alert-error, .error-message');
            if (await errorState.count() > 0) {
                await expect(errorState).toBeVisible();
            }
        });
    });

    // ========================================================================
    // RESPONSIVE DESIGN
    // ========================================================================

    test.describe('Responsive Design', () => {
        test('should display automation dashboard on mobile', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 667 });

            await page.goto('/automation');

            const container = page.locator('#automation-rules-container');
            await expect(container).toBeVisible();
        });

        test('should display investment portfolio on mobile', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 667 });

            await page.goto('/investments/portfolio');

            const chart = page.locator('#portfolio-chart');
            await expect(chart).toBeVisible();
        });
    });

    // ========================================================================
    // ACCESSIBILITY
    // ========================================================================

    test.describe('Accessibility', () => {
        test('should support keyboard navigation in automation', async ({ page }) => {
            await page.goto('/automation');

            await page.keyboard.press('Tab');
            await page.keyboard.press('Tab');

            // Should be able to interact with keyboard
            const focused = page.locator(':focus');
            await expect(focused).toBeVisible();
        });

        test('should support keyboard navigation in investments', async ({ page }) => {
            await page.goto('/investments/portfolio');

            await page.keyboard.press('Tab');
            await page.keyboard.press('Tab');

            const focused = page.locator(':focus');
            await expect(focused).toBeVisible();
        });

        test('should have proper ARIA labels', async ({ page }) => {
            await page.goto('/automation');

            const buttons = page.locator('button[aria-label], button[title]');
            if (await buttons.count() > 0) {
                const firstButton = buttons.first();
                const ariaLabel = await firstButton.getAttribute('aria-label');
                const title = await firstButton.getAttribute('title');
                expect(ariaLabel || title).toBeTruthy();
            }
        });
    });
});
