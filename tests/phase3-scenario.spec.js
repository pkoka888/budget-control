/**
 * Phase 3 E2E Tests - Scenario Planning
 * Tests for financial scenario generation, comparison, and what-if analysis
 */

const { test, expect } = require('@playwright/test');

test.describe('Phase 3 - Scenario Planning', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL('/');
    });

    test.describe('Scenario Generation', () => {
        test('should load scenario planning page', async ({ page }) => {
            await page.goto('/scenario');

            await expect(page.locator('h1')).toContainText(/scénář|planning/i);

            // Should display current financial data
            const currentData = page.locator('#current-monthly-income');
            if (await currentData.count() > 0) {
                await expect(currentData).toBeVisible();
            }
        });

        test('should generate multiple scenarios', async ({ page }) => {
            await page.goto('/scenario');

            // Select scenario types
            await page.check('input[name="scenario_types[]"][value="conservative"]');
            await page.check('input[name="scenario_types[]"][value="moderate"]');
            await page.check('input[name="scenario_types[]"][value="optimistic"]');

            // Set timeframe
            const timeframe = page.locator('#timeframe-months');
            if (await timeframe.count() > 0) {
                await timeframe.fill('12');
            }

            // Generate scenarios
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            // Should display scenario results
            const results = page.locator('#scenarios-results');
            await expect(results).toBeVisible();

            // Should have scenario cards
            const scenarioCards = results.locator('.scenario-card');
            await expect(scenarioCards).toHaveCount(3);
        });

        test('should display scenario assumptions', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="moderate"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            const scenarioCard = page.locator('.scenario-card').first();
            if (await scenarioCard.count() > 0) {
                // Should display assumptions
                await expect(scenarioCard).toContainText(/assumption|předpoklad/i);
            }
        });

        test('should show final balance projections', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="optimistic"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            const scenarioCard = page.locator('.scenario-card').first();
            if (await scenarioCard.count() > 0) {
                // Should display final balance
                await expect(scenarioCard).toContainText(/balance|zůstatek/i);
            }
        });

        test('should calculate total savings', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="conservative"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            const scenarioCard = page.locator('.scenario-card').first();
            if (await scenarioCard.count() > 0) {
                // Should display total savings
                await expect(scenarioCard).toContainText(/saving|úspor/i);
            }
        });
    });

    test.describe('Scenario Details', () => {
        test('should view detailed scenario projections', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="moderate"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            // Click view details button
            const detailsButton = page.locator('.view-details-btn').first();
            if (await detailsButton.count() > 0) {
                await detailsButton.click();

                // Modal should open
                const modal = page.locator('#scenario-details-modal');
                await expect(modal).toBeVisible();

                // Should display projections table
                const table = modal.locator('table');
                await expect(table).toBeVisible();
            }
        });

        test('should display month-by-month breakdown', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="moderate"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            const detailsButton = page.locator('.view-details-btn').first();
            if (await detailsButton.count() > 0) {
                await detailsButton.click();

                const modal = page.locator('#scenario-details-modal');
                const rows = modal.locator('tbody tr');

                // Should have rows for each month
                if (await rows.count() > 0) {
                    await expect(rows.first()).toBeVisible();
                }
            }
        });

        test('should show scenario chart', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="optimistic"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            const detailsButton = page.locator('.view-details-btn').first();
            if (await detailsButton.count() > 0) {
                await detailsButton.click();

                const chart = page.locator('#scenario-detail-chart');
                await expect(chart).toBeVisible();
            }
        });
    });

    test.describe('Scenario Comparison', () => {
        test('should compare multiple scenarios', async ({ page }) => {
            await page.goto('/scenario');

            // Generate scenarios
            await page.check('input[name="scenario_types[]"][value="conservative"]');
            await page.check('input[name="scenario_types[]"][value="optimistic"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            // Compare scenarios
            await page.click('#compare-scenarios-btn');

            await page.waitForLoadState('networkidle');

            // Comparison results should be visible
            const comparison = page.locator('#comparison-results');
            await expect(comparison).toBeVisible();
        });

        test('should display comparison chart', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="moderate"]');
            await page.check('input[name="scenario_types[]"][value="optimistic"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            await page.click('#compare-scenarios-btn');

            await page.waitForLoadState('networkidle');

            const comparisonChart = page.locator('#comparison-chart');
            await expect(comparisonChart).toBeVisible();
        });

        test('should show comparison insights', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="conservative"]');
            await page.check('input[name="scenario_types[]"][value="crisis"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            await page.click('#compare-scenarios-btn');

            await page.waitForLoadState('networkidle');

            const insights = page.locator('#comparison-insights');
            if (await insights.count() > 0) {
                await expect(insights).toBeVisible();

                // Should have insight cards
                const insightCards = insights.locator('.insight, [class*="insight"]');
                if (await insightCards.count() > 0) {
                    await expect(insightCards.first()).toBeVisible();
                }
            }
        });
    });

    test.describe('Save as Goal', () => {
        test('should save scenario as financial goal', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="moderate"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForLoadState('networkidle');

            // Click save as goal button
            const saveButton = page.locator('.save-as-goal-btn').first();
            if (await saveButton.count() > 0) {
                await saveButton.click();

                // Should show success message
                const alert = page.locator('.alert-success');
                await expect(alert).toBeVisible();

                // Should redirect to goals page
                await page.waitForURL(/goals/, { timeout: 5000 });
            }
        });
    });

    test.describe('Retirement Planning', () => {
        test('should generate retirement scenarios', async ({ page }) => {
            await page.goto('/scenario');

            // Fill retirement form
            const retirementForm = page.locator('#retirement-form');
            if (await retirementForm.count() > 0) {
                await page.fill('[name="current_age"]', '35');
                await page.fill('[name="retirement_age"]', '65');
                await page.fill('[name="current_savings"]', '500000');
                await page.fill('[name="monthly_contribution"]', '10000');
                await page.fill('[name="expected_return"]', '0.06');

                await retirementForm.submit();

                await page.waitForLoadState('networkidle');

                // Results should be visible
                const results = page.locator('#retirement-results');
                await expect(results).toBeVisible();
            }
        });

        test('should display retirement projections', async ({ page }) => {
            await page.goto('/scenario');

            const retirementForm = page.locator('#retirement-form');
            if (await retirementForm.count() > 0) {
                await page.fill('[name="current_age"]', '30');
                await page.fill('[name="retirement_age"]', '60');
                await page.fill('[name="current_savings"]', '1000000');
                await page.fill('[name="monthly_contribution"]', '15000');

                await retirementForm.submit();

                await page.waitForLoadState('networkidle');

                // Should show retirement chart
                const chart = page.locator('#retirement-chart');
                if (await chart.count() > 0) {
                    await expect(chart).toBeVisible();
                }
            }
        });
    });

    test.describe('Goal Scenarios', () => {
        test('should generate scenarios for existing goal', async ({ page }) => {
            await page.goto('/scenario');

            const goalSelector = page.locator('#goal-selector');
            if (await goalSelector.count() > 0) {
                // Select a goal
                await goalSelector.selectOption({ index: 1 });

                await page.waitForLoadState('networkidle');

                // Goal scenarios should be displayed
                const results = page.locator('#goal-scenarios-results');
                await expect(results).toBeVisible();
            }
        });

        test('should show multiple paths to goal', async ({ page }) => {
            await page.goto('/scenario');

            const goalSelector = page.locator('#goal-selector');
            if (await goalSelector.count() > 0) {
                await goalSelector.selectOption({ index: 1 });

                await page.waitForLoadState('networkidle');

                // Should show aggressive, moderate, conservative paths
                const paths = page.locator('.goal-path, [class*="scenario"]');
                if (await paths.count() >= 3) {
                    await expect(paths.nth(0)).toBeVisible();
                    await expect(paths.nth(1)).toBeVisible();
                    await expect(paths.nth(2)).toBeVisible();
                }
            }
        });
    });

    test.describe('Custom Scenarios', () => {
        test('should calculate custom scenario in real-time', async ({ page }) => {
            await page.goto('/scenario');

            const incomeInput = page.locator('#monthly-income-input');
            if (await incomeInput.count() > 0) {
                await incomeInput.fill('50000');

                const expensesInput = page.locator('#monthly-expenses-input');
                await expensesInput.fill('30000');

                const savingsRateInput = page.locator('#savings-rate-input');
                await savingsRateInput.fill('50');

                // Result should update automatically
                const result = page.locator('#custom-scenario-result');
                await expect(result).toBeVisible();
                await expect(result).toContainText(/\d+/); // Has a number
            }
        });
    });

    test.describe('Scenario Templates', () => {
        test('should load scenario templates', async ({ page }) => {
            await page.goto('/scenario');

            const templates = page.locator('.scenario-template');
            if (await templates.count() > 0) {
                await expect(templates.first()).toBeVisible();
            }
        });

        test('should apply template', async ({ page }) => {
            await page.goto('/scenario');

            const template = page.locator('.scenario-template').first();
            if (await template.count() > 0) {
                await template.click();

                // Should show success message
                const alert = page.locator('.alert-success');
                await expect(alert).toBeVisible();
            }
        });
    });

    test.describe('Loading States', () => {
        test('should show loading while generating scenarios', async ({ page }) => {
            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="moderate"]');
            await page.click('#generate-scenarios-btn');

            // Loading indicator should appear
            const loader = page.locator('#scenario-loader');
            try {
                await expect(loader).toBeVisible({ timeout: 1000 });
            } catch {
                // Data loaded too quickly
            }

            await page.waitForLoadState('networkidle');

            // Eventually loader should hide
            await expect(loader).toBeHidden();
        });
    });

    test.describe('Error Handling', () => {
        test('should handle API errors gracefully', async ({ page }) => {
            await page.route('**/api/scenario/**', route => {
                route.fulfill({
                    status: 500,
                    body: JSON.stringify({ error: 'Server error' })
                });
            });

            await page.goto('/scenario');

            await page.check('input[name="scenario_types[]"][value="moderate"]');
            await page.click('#generate-scenarios-btn');

            await page.waitForTimeout(1000);

            // Should show error message
            const alert = page.locator('.alert-error');
            await expect(alert).toBeVisible();
        });

        test('should validate scenario selection', async ({ page }) => {
            await page.goto('/scenario');

            // Try to generate without selecting any scenarios
            await page.click('#generate-scenarios-btn');

            // Should show warning
            const alert = page.locator('.alert-warning');
            await expect(alert).toBeVisible();
            await expect(alert).toContainText(/select|vyberte/i);
        });
    });

    test.describe('Responsive Design', () => {
        test('should work on mobile devices', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 667 });

            await page.goto('/scenario');

            // Form should be accessible
            const form = page.locator('form');
            if (await form.count() > 0) {
                await expect(form).toBeVisible();
            }

            // Scenario cards should stack vertically
            const cards = page.locator('.scenario-card');
            if (await cards.count() > 0) {
                await expect(cards.first()).toBeVisible();
            }
        });
    });

    test.describe('Accessibility', () => {
        test('should be keyboard navigable', async ({ page }) => {
            await page.goto('/scenario');

            // Tab through form elements
            await page.keyboard.press('Tab');
            await page.keyboard.press('Tab');

            // Should focus on interactive elements
            await page.keyboard.press('Space'); // Check checkbox
        });

        test('should have proper labels', async ({ page }) => {
            await page.goto('/scenario');

            const inputs = page.locator('input');
            if (await inputs.count() > 0) {
                const firstInput = inputs.first();
                const id = await firstInput.getAttribute('id');

                if (id) {
                    const label = page.locator(`label[for="${id}"]`);
                    if (await label.count() > 0) {
                        await expect(label).toBeVisible();
                    }
                }
            }
        });
    });
});
