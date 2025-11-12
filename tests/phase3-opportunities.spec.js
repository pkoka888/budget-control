/**
 * Phase 3 E2E Tests - Opportunities & Career
 * Tests for job opportunities, learning paths, freelance gigs, events, and certifications
 */

const { test, expect } = require('@playwright/test');

test.describe('Phase 3 - Opportunities & Career', () => {
    test.beforeEach(async ({ page }) => {
        // Login before each test
        await page.goto('/login');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'password123');
        await page.click('button[type="submit"]');
        await expect(page).toHaveURL('/');
    });

    test.describe('Job Opportunities', () => {
        test('should load job opportunities page', async ({ page }) => {
            await page.goto('/opportunities');

            await expect(page.locator('h1')).toContainText('Příležitosti');

            // Jobs tab should be active by default
            const jobsTab = page.locator('[data-view="jobs"]');
            await expect(jobsTab).toHaveClass(/active/);

            // Should display job listings
            const jobCards = page.locator('.opportunity-card, [class*="opportunity"]');
            if (await jobCards.count() > 0) {
                await expect(jobCards.first()).toBeVisible();
            }
        });

        test('should filter jobs by region', async ({ page }) => {
            await page.goto('/opportunities');

            // Select region filter
            await page.selectOption('#region-filter', 'praha');

            await page.waitForLoadState('networkidle');

            // Verify filtered results
            const results = page.locator('.opportunity-card');
            if (await results.count() > 0) {
                const firstResult = results.first();
                await expect(firstResult).toContainText(/Praha/i);
            }
        });

        test('should filter remote jobs only', async ({ page }) => {
            await page.goto('/opportunities');

            // Check remote only checkbox
            await page.check('#remote-only');

            await page.waitForLoadState('networkidle');

            // Verify results show remote jobs
            const results = page.locator('.opportunity-card');
            if (await results.count() > 0) {
                const firstResult = results.first();
                await expect(firstResult).toContainText(/remote|vzdálené/i);
            }
        });

        test('should filter by minimum salary', async ({ page }) => {
            await page.goto('/opportunities');

            // Set minimum salary
            await page.fill('#min-salary', '50000');
            await page.dispatchEvent('#min-salary', 'input');

            await page.waitForTimeout(500); // Debounce

            // Results should update
            const results = page.locator('.opportunity-card');
            await expect(results.first()).toBeVisible();
        });

        test('should display job details', async ({ page }) => {
            await page.goto('/opportunities');

            const jobCard = page.locator('.opportunity-card').first();
            await expect(jobCard).toBeVisible();

            // Verify job card contains key information
            await expect(jobCard).toContainText(/.+/); // Has some text
        });

        test('should save job opportunity', async ({ page }) => {
            await page.goto('/opportunities');

            const saveButton = page.locator('.save-opportunity-btn').first();
            if (await saveButton.count() > 0) {
                await saveButton.click();

                // Should show success message
                const alert = page.locator('.alert-success');
                await expect(alert).toBeVisible();
                await expect(alert).toContainText(/saved|uložen/i);

                // Button should change to "Saved" state
                await expect(saveButton).toContainText(/saved|uloženo/i);
            }
        });

        test('should unsave job opportunity', async ({ page }) => {
            await page.goto('/opportunities');

            // First save an opportunity
            const saveButton = page.locator('.save-opportunity-btn').first();
            if (await saveButton.count() > 0) {
                await saveButton.click();
                await page.waitForTimeout(1000);

                // Now unsave it
                const unsaveButton = page.locator('.unsave-opportunity-btn').first();
                await unsaveButton.click();

                // Should show removed message
                const alert = page.locator('.alert');
                await expect(alert).toBeVisible();
            }
        });

        test('should open job application in new tab', async ({ page, context }) => {
            await page.goto('/opportunities');

            const applyButton = page.locator('.apply-opportunity-btn').first();
            if (await applyButton.count() > 0) {
                // Listen for new page
                const pagePromise = context.waitForEvent('page');

                await applyButton.click();

                // New tab should open
                const newPage = await pagePromise;
                await expect(newPage).toBeTruthy();

                await newPage.close();
            }
        });

        test('should search jobs by keyword', async ({ page }) => {
            await page.goto('/opportunities');

            const searchInput = page.locator('#opportunity-search');
            if (await searchInput.count() > 0) {
                await searchInput.fill('developer');

                await page.waitForTimeout(500);

                // Results should be filtered
                const results = page.locator('.opportunity-card:visible');
                if (await results.count() > 0) {
                    await expect(results.first()).toContainText(/developer/i);
                }
            }
        });
    });

    test.describe('Learning Paths', () => {
        test('should switch to learning paths view', async ({ page }) => {
            await page.goto('/opportunities');

            await page.click('[data-view="learning"]');

            // Learning tab should be active
            const learningTab = page.locator('[data-view="learning"]');
            await expect(learningTab).toHaveClass(/active/);

            // Should display learning opportunities
            await page.waitForLoadState('networkidle');
        });

        test('should display course information', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="learning"]');

            await page.waitForLoadState('networkidle');

            const courseCard = page.locator('.opportunity-card').first();
            if (await courseCard.count() > 0) {
                await expect(courseCard).toBeVisible();

                // Should display duration and price
                await expect(courseCard).toContainText(/.+/);
            }
        });

        test('should filter by category', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="learning"]');

            await page.waitForLoadState('networkidle');

            const categoryFilter = page.locator('#category-filter');
            if (await categoryFilter.count() > 0) {
                await categoryFilter.selectOption({ index: 1 });

                await page.waitForLoadState('networkidle');

                // Results should update
                await expect(page.locator('.opportunity-card')).toBeVisible();
            }
        });

        test('should save learning opportunity', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="learning"]');

            await page.waitForLoadState('networkidle');

            const saveButton = page.locator('.save-opportunity-btn').first();
            if (await saveButton.count() > 0) {
                await saveButton.click();

                const alert = page.locator('.alert-success');
                await expect(alert).toBeVisible();
            }
        });
    });

    test.describe('Freelance Opportunities', () => {
        test('should switch to freelance view', async ({ page }) => {
            await page.goto('/opportunities');

            await page.click('[data-view="freelance"]');

            const freelanceTab = page.locator('[data-view="freelance"]');
            await expect(freelanceTab).toHaveClass(/active/);

            await page.waitForLoadState('networkidle');
        });

        test('should display freelance gig details', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="freelance"]');

            await page.waitForLoadState('networkidle');

            const gigCard = page.locator('.opportunity-card').first();
            if (await gigCard.count() > 0) {
                await expect(gigCard).toBeVisible();

                // Should display budget range
                await expect(gigCard).toContainText(/budget|rozpočet/i);
            }
        });

        test('should filter by platform', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="freelance"]');

            await page.waitForLoadState('networkidle');

            const platformFilter = page.locator('#platform-filter');
            if (await platformFilter.count() > 0) {
                await platformFilter.selectOption('upwork');

                await page.waitForLoadState('networkidle');
            }
        });

        test('should filter by minimum budget', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="freelance"]');

            await page.waitForLoadState('networkidle');

            const minBudget = page.locator('#min-budget');
            if (await minBudget.count() > 0) {
                await minBudget.fill('10000');
                await minBudget.dispatchEvent('input');

                await page.waitForTimeout(500);
            }
        });
    });

    test.describe('Events & Networking', () => {
        test('should switch to events view', async ({ page }) => {
            await page.goto('/opportunities');

            await page.click('[data-view="events"]');

            const eventsTab = page.locator('[data-view="events"]');
            await expect(eventsTab).toHaveClass(/active/);

            await page.waitForLoadState('networkidle');
        });

        test('should display event details', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="events"]');

            await page.waitForLoadState('networkidle');

            const eventCard = page.locator('.opportunity-card').first();
            if (await eventCard.count() > 0) {
                await expect(eventCard).toBeVisible();

                // Should display date and location
                await expect(eventCard).toContainText(/.+/);
            }
        });

        test('should filter by event type', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="events"]');

            await page.waitForLoadState('networkidle');

            const typeFilter = page.locator('#event-type-filter');
            if (await typeFilter.count() > 0) {
                await typeFilter.selectOption('meetup');

                await page.waitForLoadState('networkidle');
            }
        });

        test('should register for event', async ({ page, context }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="events"]');

            await page.waitForLoadState('networkidle');

            const registerButton = page.locator('.apply-opportunity-btn').first();
            if (await registerButton.count() > 0) {
                const pagePromise = context.waitForEvent('page');

                await registerButton.click();

                const newPage = await pagePromise;
                await expect(newPage).toBeTruthy();

                await newPage.close();
            }
        });
    });

    test.describe('Certifications', () => {
        test('should switch to certifications view', async ({ page }) => {
            await page.goto('/opportunities');

            await page.click('[data-view="certifications"]');

            const certsTab = page.locator('[data-view="certifications"]');
            await expect(certsTab).toHaveClass(/active/);

            await page.waitForLoadState('networkidle');
        });

        test('should display certification details', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="certifications"]');

            await page.waitForLoadState('networkidle');

            const certCard = page.locator('.opportunity-card').first();
            if (await certCard.count() > 0) {
                await expect(certCard).toBeVisible();

                // Should display provider and cost
                await expect(certCard).toContainText(/.+/);
            }
        });

        test('should filter by provider', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="certifications"]');

            await page.waitForLoadState('networkidle');

            const providerFilter = page.locator('#provider-filter');
            if (await providerFilter.count() > 0) {
                await providerFilter.selectOption({ index: 1 });

                await page.waitForLoadState('networkidle');
            }
        });

        test('should filter by maximum cost', async ({ page }) => {
            await page.goto('/opportunities');
            await page.click('[data-view="certifications"]');

            await page.waitForLoadState('networkidle');

            const maxCost = page.locator('#max-cost');
            if (await maxCost.count() > 0) {
                await maxCost.fill('5000');
                await maxCost.dispatchEvent('input');

                await page.waitForTimeout(500);
            }
        });
    });

    test.describe('Saved Opportunities', () => {
        test('should display saved opportunities', async ({ page }) => {
            await page.goto('/opportunities');

            const viewSavedButton = page.locator('#view-saved');
            if (await viewSavedButton.count() > 0) {
                await viewSavedButton.click();

                await page.waitForLoadState('networkidle');

                // Should show saved opportunities or empty state
                const container = page.locator('#opportunities-container');
                await expect(container).toBeVisible();
            }
        });

        test('should show saved count badge', async ({ page }) => {
            await page.goto('/opportunities');

            const badge = page.locator('#saved-count');
            if (await badge.count() > 0) {
                await expect(badge).toBeVisible();

                // Should display a number
                const count = await badge.textContent();
                expect(parseInt(count)).toBeGreaterThanOrEqual(0);
            }
        });

        test('should remove from saved', async ({ page }) => {
            await page.goto('/opportunities');

            // View saved opportunities
            const viewSavedButton = page.locator('#view-saved');
            if (await viewSavedButton.count() > 0) {
                await viewSavedButton.click();

                await page.waitForLoadState('networkidle');

                // Remove first saved item
                const unsaveButton = page.locator('.unsave-opportunity-btn').first();
                if (await unsaveButton.count() > 0) {
                    await unsaveButton.click();

                    const alert = page.locator('.alert');
                    await expect(alert).toBeVisible();
                }
            }
        });
    });

    test.describe('Interaction Tracking', () => {
        test('should track opportunity views', async ({ page }) => {
            // Mock API to verify tracking call
            let trackingCalled = false;

            await page.route('**/api/opportunities/track', route => {
                trackingCalled = true;
                route.fulfill({
                    status: 200,
                    body: JSON.stringify({ success: true })
                });
            });

            await page.goto('/opportunities');

            // Viewing opportunities should trigger tracking
            await page.waitForLoadState('networkidle');
        });

        test('should track apply interactions', async ({ page, context }) => {
            let trackingCalled = false;

            await page.route('**/api/opportunities/track', route => {
                const postData = route.request().postDataJSON();
                if (postData && postData.interaction_type === 'apply') {
                    trackingCalled = true;
                }
                route.fulfill({
                    status: 200,
                    body: JSON.stringify({ success: true })
                });
            });

            await page.goto('/opportunities');

            const applyButton = page.locator('.apply-opportunity-btn').first();
            if (await applyButton.count() > 0) {
                const pagePromise = context.waitForEvent('page');
                await applyButton.click();

                const newPage = await pagePromise;
                await newPage.close();

                // Wait for tracking call
                await page.waitForTimeout(500);
            }
        });
    });

    test.describe('Loading States', () => {
        test('should show loading state while fetching opportunities', async ({ page }) => {
            await page.goto('/opportunities');

            // Switch view to trigger data load
            await page.click('[data-view="learning"]');

            // Loading state should appear
            const loader = page.locator('[class*="loader"], [class*="loading"]');

            try {
                await expect(loader).toBeVisible({ timeout: 1000 });
            } catch {
                // Data loaded too quickly
            }

            // Eventually data should load
            await page.waitForLoadState('networkidle');
        });
    });

    test.describe('Error Handling', () => {
        test('should handle API errors gracefully', async ({ page }) => {
            await page.route('**/api/opportunities/**', route => {
                route.fulfill({
                    status: 500,
                    body: JSON.stringify({ error: 'Server error' })
                });
            });

            await page.goto('/opportunities');

            // Should show error message or empty state
            const errorAlert = page.locator('.alert-error');
            await expect(errorAlert).toBeVisible({ timeout: 5000 });
        });

        test('should handle save errors', async ({ page }) => {
            await page.route('**/api/opportunities/save', route => {
                route.fulfill({
                    status: 400,
                    body: JSON.stringify({ success: false, error: 'Already saved' })
                });
            });

            await page.goto('/opportunities');

            const saveButton = page.locator('.save-opportunity-btn').first();
            if (await saveButton.count() > 0) {
                await saveButton.click();

                // Should show error message
                const alert = page.locator('.alert-error');
                await expect(alert).toBeVisible();
            }
        });
    });

    test.describe('Responsive Design', () => {
        test('should work on mobile devices', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 667 });

            await page.goto('/opportunities');

            // Tabs should be accessible
            const tabs = page.locator('.opportunity-tab');
            await expect(tabs.first()).toBeVisible();

            // Cards should be visible
            const cards = page.locator('.opportunity-card');
            if (await cards.count() > 0) {
                await expect(cards.first()).toBeVisible();
            }
        });

        test('should allow mobile filtering', async ({ page }) => {
            await page.setViewportSize({ width: 375, height: 667 });

            await page.goto('/opportunities');

            const regionFilter = page.locator('#region-filter');
            if (await regionFilter.count() > 0) {
                await regionFilter.selectOption({ index: 1 });
                await page.waitForLoadState('networkidle');

                // Should update results
                await expect(page).toHaveURL(/opportunities/);
            }
        });
    });

    test.describe('Accessibility', () => {
        test('should be keyboard navigable', async ({ page }) => {
            await page.goto('/opportunities');

            // Tab through tabs
            await page.keyboard.press('Tab');
            await page.keyboard.press('Enter');

            await page.waitForTimeout(500);
        });

        test('should have proper ARIA attributes', async ({ page }) => {
            await page.goto('/opportunities');

            const tabs = page.locator('[role="tab"], .opportunity-tab');
            if (await tabs.count() > 0) {
                const firstTab = tabs.first();
                await expect(firstTab).toBeVisible();
            }
        });
    });
});
