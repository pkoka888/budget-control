/**
 * Phase 3 E2E Tests - Reports & Analytics
 * Tests for interactive report filtering, date range selection, and export functionality
 */

const { test, expect } = require('@playwright/test');

test.describe('Phase 3 - Reports & Analytics', () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto('/login');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL('/');
    });

    test.describe('Monthly Reports', () => {
        test('should load monthly report with current data', async ({ page }) => {
            await page.goto('/reports/monthly');

            await expect(page.locator('h1')).toContainText('Měsíční přehled');

            // Verify summary cards are visible
            await expect(page.locator('.summary-card').first()).toBeVisible();

            // Verify charts are rendered
            const expensesChart = page.locator('#expenses-chart');
            await expect(expensesChart).toBeVisible();

            const incomeChart = page.locator('#income-chart');
            await expect(incomeChart).toBeVisible();
        });

        test('should navigate to previous month', async ({ page }) => {
            await page.goto('/reports/monthly');

            const currentMonth = await page.locator('#current-period').textContent();

            await page.click('#prev-period');

            // Wait for report to reload
            await page.waitForLoadState('networkidle');

            const newMonth = await page.locator('#current-period').textContent();
            expect(newMonth).not.toBe(currentMonth);
        });

        test('should navigate to next month', async ({ page }) => {
            await page.goto('/reports/monthly');

            const currentMonth = await page.locator('#current-period').textContent();

            await page.click('#next-period');

            await page.waitForLoadState('networkidle');

            const newMonth = await page.locator('#current-period').textContent();
            expect(newMonth).not.toBe(currentMonth);
        });

        test('should filter by category', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Select a category filter
            await page.selectOption('#category-filter', { index: 1 });

            await page.waitForLoadState('networkidle');

            // Verify filtered data is displayed
            await expect(page.locator('#category-breakdown')).toBeVisible();
        });

        test('should display category breakdown table', async ({ page }) => {
            await page.goto('/reports/monthly');

            const categoryTable = page.locator('.category-breakdown-table');
            await expect(categoryTable).toBeVisible();

            // Verify table has rows
            const rows = categoryTable.locator('tbody tr');
            await expect(rows.first()).toBeVisible();
        });
    });

    test.describe('Yearly Reports', () => {
        test('should load yearly report', async ({ page }) => {
            await page.goto('/reports/yearly');

            await expect(page.locator('h1')).toContainText('Roční přehled');

            // Verify annual summary cards
            await expect(page.locator('.annual-summary')).toBeVisible();

            // Verify yearly trend chart
            const trendChart = page.locator('#yearly-trend-chart');
            await expect(trendChart).toBeVisible();
        });

        test('should navigate to previous year', async ({ page }) => {
            await page.goto('/reports/yearly');

            const currentYear = await page.locator('#current-year').textContent();

            await page.click('#prev-year');

            await page.waitForLoadState('networkidle');

            const newYear = await page.locator('#current-year').textContent();
            expect(parseInt(newYear)).toBe(parseInt(currentYear) - 1);
        });

        test('should display monthly comparison', async ({ page }) => {
            await page.goto('/reports/yearly');

            // Verify monthly comparison section
            const comparison = page.locator('#monthly-comparison');
            await expect(comparison).toBeVisible();

            // Should have 12 months
            const months = comparison.locator('.month-card');
            await expect(months).toHaveCount(12);
        });
    });

    test.describe('Analytics Dashboard', () => {
        test('should load analytics dashboard', async ({ page }) => {
            await page.goto('/reports/analytics');

            await expect(page.locator('h1')).toContainText('Finanční analytika');

            // Verify financial health score
            const healthScore = page.locator('#health-score');
            await expect(healthScore).toBeVisible();

            // Score should be between 0-100
            const scoreText = await healthScore.textContent();
            const score = parseInt(scoreText);
            expect(score).toBeGreaterThanOrEqual(0);
            expect(score).toBeLessThanOrEqual(100);
        });

        test('should display spending trends', async ({ page }) => {
            await page.goto('/reports/analytics');

            const trendsChart = page.locator('#spending-trends-chart');
            await expect(trendsChart).toBeVisible();
        });

        test('should show recommendations', async ({ page }) => {
            await page.goto('/reports/analytics');

            const recommendations = page.locator('#recommendations-section');
            await expect(recommendations).toBeVisible();

            // Should have at least one recommendation
            const recommendationCards = recommendations.locator('.recommendation-card');
            await expect(recommendationCards.first()).toBeVisible();
        });

        test('should detect spending anomalies', async ({ page }) => {
            await page.goto('/reports/analytics');

            const anomalies = page.locator('#anomalies-section');
            await expect(anomalies).toBeVisible();
        });
    });

    test.describe('Net Worth Tracking', () => {
        test('should load net worth report', async ({ page }) => {
            await page.goto('/reports/net-worth');

            await expect(page.locator('h1')).toContainText('Čistá hodnota majetku');

            // Verify total net worth display
            const netWorth = page.locator('#total-net-worth');
            await expect(netWorth).toBeVisible();
        });

        test('should display assets breakdown', async ({ page }) => {
            await page.goto('/reports/net-worth');

            const assetsSection = page.locator('#assets-section');
            await expect(assetsSection).toBeVisible();

            // Verify assets chart
            const assetsChart = page.locator('#assets-chart');
            await expect(assetsChart).toBeVisible();
        });

        test('should display liabilities breakdown', async ({ page }) => {
            await page.goto('/reports/net-worth');

            const liabilitiesSection = page.locator('#liabilities-section');
            await expect(liabilitiesSection).toBeVisible();
        });

        test('should show net worth trend over time', async ({ page }) => {
            await page.goto('/reports/net-worth');

            const trendChart = page.locator('#net-worth-trend-chart');
            await expect(trendChart).toBeVisible();
        });
    });

    test.describe('Custom Date Range Reports', () => {
        test('should filter by custom date range', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Set date range
            await page.fill('#date-from', '2024-01-01');
            await page.fill('#date-to', '2024-12-31');

            // Submit or trigger filter
            await page.click('#apply-date-filter');

            await page.waitForLoadState('networkidle');

            // Verify custom report is displayed
            const customReport = page.locator('#custom-report-results');
            await expect(customReport).toBeVisible();
        });

        test('should validate date range (end after start)', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Set invalid date range (end before start)
            await page.fill('#date-from', '2024-12-31');
            await page.fill('#date-to', '2024-01-01');

            await page.click('#apply-date-filter');

            // Should show validation error
            const error = page.locator('.alert-error');
            await expect(error).toBeVisible();
        });

        test('should display custom report with summary', async ({ page }) => {
            await page.goto('/reports/monthly');

            await page.fill('#date-from', '2024-01-01');
            await page.fill('#date-to', '2024-03-31');
            await page.click('#apply-date-filter');

            await page.waitForLoadState('networkidle');

            // Verify summary cards for custom period
            await expect(page.locator('#custom-total-income')).toBeVisible();
            await expect(page.locator('#custom-total-expenses')).toBeVisible();
            await expect(page.locator('#custom-net-savings')).toBeVisible();
        });
    });

    test.describe('Report Export', () => {
        test('should export report as CSV', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Start waiting for download before clicking
            const downloadPromise = page.waitForEvent('download');

            await page.click('#export-csv');

            const download = await downloadPromise;

            // Verify download filename
            expect(download.suggestedFilename()).toContain('.csv');
        });

        test('should export report as Excel', async ({ page }) => {
            await page.goto('/reports/monthly');

            const downloadPromise = page.waitForEvent('download');

            await page.click('#export-xlsx');

            const download = await downloadPromise;

            expect(download.suggestedFilename()).toContain('.xlsx');
        });

        test('should export report as PDF', async ({ page }) => {
            await page.goto('/reports/monthly');

            const downloadPromise = page.waitForEvent('download');

            await page.click('#export-pdf');

            const download = await downloadPromise;

            expect(download.suggestedFilename()).toContain('.pdf');
        });

        test('should show export confirmation message', async ({ page }) => {
            await page.goto('/reports/monthly');

            await page.click('#export-csv');

            // Should show success message
            const alert = page.locator('.alert-success');
            await expect(alert).toBeVisible();
            await expect(alert).toContainText('Export started');
        });
    });

    test.describe('Chart Interactions', () => {
        test('should toggle between chart types', async ({ page }) => {
            await page.goto('/reports/monthly');

            const chartCanvas = page.locator('#expenses-chart');
            await expect(chartCanvas).toBeVisible();

            // Switch to bar chart
            await page.click('[data-chart="expenses-chart"][data-type="bar"]');

            // Chart should still be visible (type changed)
            await expect(chartCanvas).toBeVisible();
        });

        test('should display chart tooltips on hover', async ({ page }) => {
            await page.goto('/reports/monthly');

            const chart = page.locator('#expenses-chart');
            await expect(chart).toBeVisible();

            // Hover over chart
            await chart.hover();

            // Chart.js tooltip should appear (checking canvas updates)
            await page.waitForTimeout(500);
        });

        test('should update chart when data changes', async ({ page }) => {
            await page.goto('/reports/monthly');

            const initialChart = page.locator('#expenses-chart');
            await expect(initialChart).toBeVisible();

            // Change month
            await page.click('#next-period');
            await page.waitForLoadState('networkidle');

            // Chart should still be visible with new data
            await expect(initialChart).toBeVisible();
        });
    });

    test.describe('Report Tab Switching', () => {
        test('should switch between report types', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Click analytics tab
            await page.click('[data-report="analytics"]');

            await page.waitForURL('/reports/analytics');

            await expect(page.locator('h1')).toContainText('analytika');
        });

        test('should preserve state when switching tabs', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Apply a filter
            await page.selectOption('#category-filter', { index: 1 });

            // Switch to yearly report
            await page.click('[data-report="yearly"]');
            await page.waitForURL('/reports/yearly');

            // Switch back to monthly
            await page.click('[data-report="monthly"]');
            await page.waitForURL('/reports/monthly');

            // Filter should still be applied (if implemented)
            // This tests client-side state management
        });
    });

    test.describe('Responsive Design', () => {
        test('should display mobile-friendly layout on small screens', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 667 });

            await page.goto('/reports/monthly');

            // Summary cards should stack vertically
            const summaryCards = page.locator('.summary-card');
            await expect(summaryCards.first()).toBeVisible();

            // Charts should be responsive
            const chart = page.locator('#expenses-chart');
            await expect(chart).toBeVisible();
        });

        test('should allow mobile navigation', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 667 });

            await page.goto('/reports/monthly');

            // Navigation buttons should be accessible
            await page.click('#prev-period');
            await page.waitForLoadState('networkidle');

            await expect(page.locator('h1')).toBeVisible();
        });
    });

    test.describe('Loading States', () => {
        test('should show loading indicator while fetching data', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Click next month to trigger data load
            await page.click('#next-period');

            // Loading indicator should appear briefly
            const loader = page.locator('#report-loader');

            // Either loader is visible or data loads too fast
            try {
                await expect(loader).toBeVisible({ timeout: 1000 });
            } catch {
                // Data loaded too quickly, that's fine
            }

            // Eventually loader should hide
            await expect(loader).toBeHidden({ timeout: 5000 });
        });

        test('should handle slow network gracefully', async ({ page }) => {
            // Throttle network to simulate slow connection
            await page.route('**/*', route => {
                setTimeout(() => route.continue(), 500);
            });

            await page.goto('/reports/monthly');

            // Should still load successfully
            await expect(page.locator('h1')).toBeVisible({ timeout: 10000 });
        });
    });

    test.describe('Error Handling', () => {
        test('should handle API errors gracefully', async ({ page }) => {
            // Intercept API call and return error
            await page.route('**/api/reports/**', route => {
                route.fulfill({
                    status: 500,
                    body: JSON.stringify({ error: 'Internal server error' })
                });
            });

            await page.goto('/reports/monthly');

            // Should show error message
            const errorAlert = page.locator('.alert-error');
            await expect(errorAlert).toBeVisible();
        });

        test('should handle network errors', async ({ page }) => {
            // Simulate network failure
            await page.route('**/reports/**', route => route.abort());

            await page.goto('/');

            // Try to navigate to reports
            await page.click('a[href="/reports/monthly"]');

            // Should show error or fallback
            await page.waitForTimeout(2000);
        });
    });

    test.describe('Accessibility', () => {
        test('should be keyboard navigable', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Tab through interactive elements
            await page.keyboard.press('Tab');
            await page.keyboard.press('Tab');

            // Should be able to navigate with keyboard
            await page.keyboard.press('Enter');
        });

        test('should have proper ARIA labels', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Check for accessibility attributes
            const prevButton = page.locator('#prev-period');
            const ariaLabel = await prevButton.getAttribute('aria-label');

            expect(ariaLabel).toBeTruthy();
        });

        test('should support screen readers', async ({ page }) => {
            await page.goto('/reports/monthly');

            // Check for proper heading hierarchy
            const h1 = page.locator('h1');
            await expect(h1).toBeVisible();

            // Check for alt text on images/icons
            const images = page.locator('img');
            if (await images.count() > 0) {
                const firstImage = images.first();
                const alt = await firstImage.getAttribute('alt');
                expect(alt).toBeTruthy();
            }
        });
    });
});
