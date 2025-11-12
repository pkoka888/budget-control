// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * Phase 2 E2E Tests
 * Tests for recurring transactions, transaction splits, budget templates,
 * 2FA, and email verification
 */

test.describe('Email Verification', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/auth/register');
  });

  test('should show verification page after registration', async ({ page }) => {
    await page.fill('[name="name"]', 'Test User');
    await page.fill('[name="email"]', 'newuser@example.com');
    await page.fill('[name="password"]', 'SecurePass123!');
    await page.fill('[name="password_confirm"]', 'SecurePass123!');
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/\/email-verification/);
    await expect(page.locator('h1')).toContainText('Ověřte email');
  });

  test('should display verification status', async ({ page }) => {
    // Log in as unverified user
    await page.goto('/auth/login');
    await page.fill('[name="email"]', 'unverified@example.com');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await page.goto('/email-verification');

    await expect(page.locator('.verification-status')).toBeVisible();
  });

  test('should allow resending verification email', async ({ page }) => {
    await page.goto('/email-verification');

    const resendButton = page.locator('button#resend-verification');
    await resendButton.click();

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('.alert-success')).toContainText('email byl odeslán');
  });

  test('should enforce rate limiting on resend', async ({ page }) => {
    await page.goto('/email-verification');

    // Send multiple requests rapidly
    for (let i = 0; i < 4; i++) {
      await page.locator('button#resend-verification').click();
      await page.waitForTimeout(100);
    }

    await expect(page.locator('.alert-error')).toBeVisible();
    await expect(page.locator('.alert-error')).toContainText(/limit|mnoho/i);
  });

  test('should verify email with valid token', async ({ page, request }) => {
    // Get verification token from database or API mock
    const token = 'test-verification-token-123456';

    await page.goto(`/verify-email?token=${token}`);

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('.alert-success')).toContainText('ověřen');
  });
});

test.describe('Two-Factor Authentication', () => {
  test.beforeEach(async ({ page }) => {
    // Log in as verified user
    await page.goto('/auth/login');
    await page.fill('[name="email"]', 'verified@example.com');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await page.goto('/settings/two-factor');
  });

  test('should display 2FA settings page', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Dvoufaktorové ověření');
    await expect(page.locator('#enable-2fa-btn')).toBeVisible();
  });

  test('should show QR code when setting up 2FA', async ({ page }) => {
    await page.click('#enable-2fa-btn');

    await expect(page.locator('#setup-modal')).toBeVisible();
    await expect(page.locator('#qr-code')).toBeVisible();
    await expect(page.locator('#secret-key')).toBeVisible();
  });

  test('should verify TOTP code during setup', async ({ page }) => {
    await page.click('#enable-2fa-btn');

    await page.waitForSelector('#qr-code');

    // Enter test TOTP code
    await page.fill('[name="totp_code"]', '123456');
    await page.click('button#verify-totp');

    // Should show backup codes or success message
    await page.waitForSelector('#backup-codes-modal, .alert-success', { timeout: 5000 });
  });

  test('should display backup codes after enabling 2FA', async ({ page }) => {
    await page.click('#enable-2fa-btn');
    await page.fill('[name="totp_code"]', '123456');
    await page.click('button#verify-totp');

    await expect(page.locator('#backup-codes-modal')).toBeVisible();

    const backupCodes = await page.locator('.backup-code').count();
    expect(backupCodes).toBe(8);
  });

  test('should allow downloading backup codes', async ({ page }) => {
    await page.click('#enable-2fa-btn');
    await page.fill('[name="totp_code"]', '123456');
    await page.click('button#verify-totp');

    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.click('button#download-backup-codes')
    ]);

    expect(download.suggestedFilename()).toContain('backup-codes');
  });

  test('should show trusted devices list', async ({ page }) => {
    await expect(page.locator('#trusted-devices-section')).toBeVisible();
  });

  test('should allow revoking trusted device', async ({ page }) => {
    const deviceCount = await page.locator('.trusted-device-item').count();

    if (deviceCount > 0) {
      await page.click('.revoke-device-btn >> nth=0');

      // Confirm revocation
      page.on('dialog', dialog => dialog.accept());

      await page.waitForTimeout(500);

      const newDeviceCount = await page.locator('.trusted-device-item').count();
      expect(newDeviceCount).toBeLessThan(deviceCount);
    }
  });

  test('should allow regenerating backup codes', async ({ page }) => {
    await page.click('button#regenerate-backup-codes');

    // Confirm regeneration
    page.on('dialog', dialog => dialog.accept());

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('#backup-codes-modal')).toBeVisible();
  });

  test('should disable 2FA with confirmation', async ({ page }) => {
    await page.click('button#disable-2fa-btn');

    // Confirm disable
    page.on('dialog', dialog => dialog.accept());

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('#enable-2fa-btn')).toBeVisible();
  });

  test('should require 2FA code at login when enabled', async ({ page }) => {
    // Log out
    await page.click('a[href="/auth/logout"]');

    // Log in with 2FA-enabled account
    await page.goto('/auth/login');
    await page.fill('[name="email"]', '2fa-user@example.com');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    // Should redirect to 2FA verification
    await expect(page).toHaveURL(/\/auth\/2fa/);
    await expect(page.locator('[name="totp_code"]')).toBeVisible();
  });

  test('should allow using backup code for login', async ({ page }) => {
    await page.goto('/auth/login');
    await page.fill('[name="email"]', '2fa-user@example.com');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await page.click('button#use-backup-code');
    await expect(page.locator('[name="backup_code"]')).toBeVisible();

    await page.fill('[name="backup_code"]', 'ABCD-1234');
    await page.click('button[type="submit"]');
  });

  test('should remember trusted device', async ({ page }) => {
    await page.goto('/auth/login');
    await page.fill('[name="email"]', '2fa-user@example.com');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await page.check('[name="trust_device"]');
    await page.fill('[name="totp_code"]', '123456');
    await page.click('button[type="submit"]');

    // Log out and log back in
    await page.click('a[href="/auth/logout"]');
    await page.goto('/auth/login');
    await page.fill('[name="email"]', '2fa-user@example.com');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    // Should skip 2FA on trusted device
    await expect(page).toHaveURL(/\/dashboard/);
  });
});

test.describe('Recurring Transactions', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/auth/login');
    await page.fill('[name="email"]', 'test@example.com');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await page.goto('/transactions/recurring');
  });

  test('should display recurring transactions page', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Opakující se transakce');
    await expect(page.locator('#tab-active')).toBeVisible();
    await expect(page.locator('#tab-inactive')).toBeVisible();
    await expect(page.locator('#tab-upcoming')).toBeVisible();
  });

  test('should display monthly statistics', async ({ page }) => {
    await expect(page.locator('#monthly-income')).toBeVisible();
    await expect(page.locator('#monthly-expenses')).toBeVisible();
    await expect(page.locator('#active-count')).toBeVisible();
  });

  test('should switch between tabs', async ({ page }) => {
    await page.click('#tab-inactive');
    await expect(page.locator('#panel-inactive')).toBeVisible();
    await expect(page.locator('#panel-active')).toBeHidden();

    await page.click('#tab-upcoming');
    await expect(page.locator('#panel-upcoming')).toBeVisible();
  });

  test('should open create recurring transaction modal', async ({ page }) => {
    await page.click('#create-recurring-btn');

    await expect(page.locator('#recurring-modal')).toBeVisible();
    await expect(page.locator('[name="description"]')).toBeVisible();
    await expect(page.locator('[name="amount"]')).toBeVisible();
    await expect(page.locator('[name="frequency"]')).toBeVisible();
  });

  test('should create new recurring transaction', async ({ page }) => {
    await page.click('#create-recurring-btn');

    await page.fill('[name="description"]', 'Monthly Rent');
    await page.fill('[name="amount"]', '15000');
    await page.selectOption('[name="frequency"]', 'monthly');
    await page.selectOption('[name="account_id"]', { index: 0 });
    await page.selectOption('[name="category_id"]', { index: 1 });
    await page.fill('[name="next_due_date"]', '2025-01-01');

    await page.click('button#save-recurring');

    await expect(page.locator('.alert-success')).toBeVisible();
    await expect(page.locator('.recurring-item')).toContainText('Monthly Rent');
  });

  test('should validate required fields', async ({ page }) => {
    await page.click('#create-recurring-btn');
    await page.click('button#save-recurring');

    // Check for HTML5 validation or custom errors
    const descriptionInput = page.locator('[name="description"]');
    await expect(descriptionInput).toBeFocused();
  });

  test('should show frequency preview', async ({ page }) => {
    await page.click('#create-recurring-btn');

    await page.selectOption('[name="frequency"]', 'weekly');
    await expect(page.locator('#frequency-preview')).toContainText('Každý týden');

    await page.selectOption('[name="frequency"]', 'monthly');
    await expect(page.locator('#frequency-preview')).toContainText('Každý měsíc');
  });

  test('should display upcoming calendar', async ({ page }) => {
    await page.click('#tab-upcoming');

    await expect(page.locator('#upcoming-calendar')).toBeVisible();
    await expect(page.locator('.calendar-month')).toHaveCount(3); // Next 3 months
  });

  test('should edit recurring transaction', async ({ page }) => {
    const firstTransaction = page.locator('.recurring-item >> nth=0');
    await firstTransaction.locator('.edit-btn').click();

    await expect(page.locator('#recurring-modal')).toBeVisible();

    await page.fill('[name="amount"]', '16000');
    await page.click('button#save-recurring');

    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('should toggle transaction active status', async ({ page }) => {
    const firstTransaction = page.locator('.recurring-item >> nth=0');
    await firstTransaction.locator('.toggle-active-btn').click();

    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('should delete recurring transaction', async ({ page }) => {
    const initialCount = await page.locator('.recurring-item').count();

    page.on('dialog', dialog => dialog.accept());

    const firstTransaction = page.locator('.recurring-item >> nth=0');
    await firstTransaction.locator('.delete-btn').click();

    await page.waitForTimeout(500);

    const newCount = await page.locator('.recurring-item').count();
    expect(newCount).toBeLessThan(initialCount);
  });

  test('should filter by frequency', async ({ page }) => {
    await page.selectOption('#frequency-filter', 'monthly');

    const items = page.locator('.recurring-item');
    const count = await items.count();

    for (let i = 0; i < count; i++) {
      await expect(items.nth(i)).toContainText(/měsíc/i);
    }
  });

  test('should calculate monthly amounts correctly', async ({ page }) => {
    // Create daily recurring transaction
    await page.click('#create-recurring-btn');
    await page.fill('[name="description"]', 'Daily Coffee');
    await page.fill('[name="amount"]', '50');
    await page.selectOption('[name="frequency"]', 'daily');
    await page.click('button#save-recurring');

    // Check that monthly amount is displayed (50 * 30 = 1500)
    await expect(page.locator('.monthly-amount')).toContainText('1 500');
  });
});

test.describe('Transaction Splits', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/auth/login');
    await page.fill('[name="email"]', 'test@example.com');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');
  });

  test('should display splits page for transaction', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    await expect(page.locator('h1')).toContainText('Rozdělit transakci');
    await expect(page.locator('#original-amount')).toBeVisible();
    await expect(page.locator('#splits-container')).toBeVisible();
  });

  test('should show transaction details', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    await expect(page.locator('.transaction-details')).toBeVisible();
    await expect(page.locator('.transaction-description')).toBeVisible();
    await expect(page.locator('.transaction-amount')).toBeVisible();
  });

  test('should add split row', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    const initialCount = await page.locator('.split-row').count();

    await page.click('#add-split-btn');

    const newCount = await page.locator('.split-row').count();
    expect(newCount).toBeGreaterThan(initialCount);
  });

  test('should remove split row', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    // Add multiple rows first
    await page.click('#add-split-btn');
    await page.click('#add-split-btn');

    const countBefore = await page.locator('.split-row').count();

    await page.click('.remove-split-btn >> nth=0');

    const countAfter = await page.locator('.split-row').count();
    expect(countAfter).toBe(countBefore - 1);
  });

  test('should update summary in real-time', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    // Assuming original amount is 1000
    await page.fill('.split-amount >> nth=0', '400');
    await page.fill('.split-amount >> nth=1', '600');

    await expect(page.locator('#allocated-amount')).toContainText('1 000');
    await expect(page.locator('#remaining-amount')).toContainText('0');
  });

  test('should show progress bar status', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    // Under-allocated
    await page.fill('.split-amount >> nth=0', '400');
    await expect(page.locator('#progress-bar')).toHaveClass(/bg-yellow/);

    // Fully allocated
    await page.fill('.split-amount >> nth=0', '1000');
    await expect(page.locator('#progress-bar')).toHaveClass(/bg-green/);

    // Over-allocated
    await page.fill('.split-amount >> nth=0', '1200');
    await expect(page.locator('#progress-bar')).toHaveClass(/bg-red/);
  });

  test('should validate total equals original amount', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    await page.fill('.split-amount >> nth=0', '400');
    await page.fill('.split-amount >> nth=1', '500');

    await page.click('button#save-splits');

    await expect(page.locator('.validation-error')).toBeVisible();
    await expect(page.locator('.validation-error')).toContainText(/must equal|musí se rovnat/i);
  });

  test('should save valid splits', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    await page.fill('.split-amount >> nth=0', '600');
    await page.selectOption('.split-category >> nth=0', { index: 1 });

    await page.fill('.split-amount >> nth=1', '400');
    await page.selectOption('.split-category >> nth=1', { index: 2 });

    await page.click('button#save-splits');

    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('should disable save button when invalid', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    await page.fill('.split-amount >> nth=0', '400');

    const saveButton = page.locator('button#save-splits');
    await expect(saveButton).toBeDisabled();
  });

  test('should enable save button when valid', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    await page.fill('.split-amount >> nth=0', '1000');
    await page.selectOption('.split-category >> nth=0', { index: 1 });

    const saveButton = page.locator('button#save-splits');
    await expect(saveButton).toBeEnabled();
  });

  test('should show validation message', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    await page.fill('.split-amount >> nth=0', '400');

    await expect(page.locator('#validation-message')).toContainText(/zbývá|remaining/i);
    await expect(page.locator('#remaining-amount')).toContainText('600');
  });
});

test.describe('Budget Templates', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/auth/login');
    await page.fill('[name="email"]', 'test@example.com');
    await page.fill('[name="password"]', 'password123');
    await page.click('button[type="submit"]');

    await page.goto('/budgets/templates');
  });

  test('should display templates page', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('Šablony rozpočtu');
    await expect(page.locator('#tab-system')).toBeVisible();
    await expect(page.locator('#tab-user')).toBeVisible();
  });

  test('should switch between system and user templates', async ({ page }) => {
    await page.click('#tab-user');
    await expect(page.locator('#panel-user')).toBeVisible();
    await expect(page.locator('#panel-system')).toBeHidden();

    await page.click('#tab-system');
    await expect(page.locator('#panel-system')).toBeVisible();
  });

  test('should display template cards', async ({ page }) => {
    const templates = page.locator('.template-card');
    await expect(templates.first()).toBeVisible();
  });

  test('should create new template', async ({ page }) => {
    await page.click('#create-template-btn');

    await expect(page.locator('#edit-template-modal')).toBeVisible();

    await page.fill('[name="name"]', 'My Budget Template');
    await page.selectOption('[name="template_type"]', 'single');
    await page.fill('[name="description"]', 'Test template');

    // Add category
    await page.selectOption('.category-select >> nth=0', { index: 1 });
    await page.fill('.category-amount >> nth=0', '5000');

    await page.click('button#save-template');

    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('should add multiple categories to template', async ({ page }) => {
    await page.click('#create-template-btn');

    await page.click('#add-category-btn');
    await page.click('#add-category-btn');

    const categoryRows = await page.locator('.category-row').count();
    expect(categoryRows).toBeGreaterThanOrEqual(3);
  });

  test('should remove category from template', async ({ page }) => {
    await page.click('#create-template-btn');

    await page.click('#add-category-btn');
    await page.click('#add-category-btn');

    const countBefore = await page.locator('.category-row').count();

    await page.click('.remove-category-btn >> nth=0');

    const countAfter = await page.locator('.category-row').count();
    expect(countAfter).toBe(countBefore - 1);
  });

  test('should update summary when adding categories', async ({ page }) => {
    await page.click('#create-template-btn');

    await page.fill('.category-amount >> nth=0', '5000');
    await page.click('#add-category-btn');
    await page.fill('.category-amount >> nth=1', '3000');

    await expect(page.locator('#summary-total')).toContainText('8 000');
    await expect(page.locator('#summary-categories')).toContainText('2');
  });

  test('should view template details', async ({ page }) => {
    await page.click('.view-template-btn >> nth=0');

    await expect(page.locator('#template-modal')).toBeVisible();
    await expect(page.locator('#template-detail')).toBeVisible();
  });

  test('should apply template to budget', async ({ page }) => {
    await page.click('.apply-template-btn >> nth=0');

    await expect(page.locator('#apply-modal')).toBeVisible();
    await expect(page.locator('[name="month"]')).toBeVisible();
    await expect(page.locator('[name="income"]')).toBeVisible();
  });

  test('should apply template with custom income', async ({ page }) => {
    await page.click('.apply-template-btn >> nth=0');

    await page.fill('[name="income"]', '45000');
    await page.fill('[name="month"]', '2025-01');

    await page.click('button#apply-template');

    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('should edit user template', async ({ page }) => {
    await page.click('#tab-user');

    await page.click('.edit-template-btn >> nth=0');

    await expect(page.locator('#edit-template-modal')).toBeVisible();

    await page.fill('[name="name"]', 'Updated Template Name');
    await page.click('button#save-template');

    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('should delete user template', async ({ page }) => {
    await page.click('#tab-user');

    page.on('dialog', dialog => dialog.accept());

    const countBefore = await page.locator('.template-card').count();

    await page.click('.delete-template-btn >> nth=0');

    await page.waitForTimeout(500);

    const countAfter = await page.locator('.template-card').count();
    expect(countAfter).toBeLessThan(countBefore);
  });

  test('should export template', async ({ page }) => {
    await page.click('.view-template-btn >> nth=0');

    const [download] = await Promise.all([
      page.waitForEvent('download'),
      page.click('#export-btn')
    ]);

    expect(download.suggestedFilename()).toMatch(/budget-template.*\.json/);
  });

  test('should import template', async ({ page }) => {
    const templateData = {
      name: 'Imported Template',
      template_type: 'family',
      description: 'Test import',
      categories: [
        { category_name: 'Food', amount: 8000, percentage: null },
        { category_name: 'Transport', amount: 2000, percentage: null }
      ]
    };

    // Create temporary file
    await page.setInputFiles('#import-file', {
      name: 'template.json',
      mimeType: 'application/json',
      buffer: Buffer.from(JSON.stringify(templateData))
    });

    await expect(page.locator('.alert-success')).toBeVisible();
  });

  test('should validate template before saving', async ({ page }) => {
    await page.click('#create-template-btn');

    // Try to save without filling required fields
    await page.click('button#save-template');

    const nameInput = page.locator('[name="name"]');
    await expect(nameInput).toBeFocused();
  });

  test('should calculate percentages in summary', async ({ page }) => {
    await page.click('#create-template-btn');

    await page.fill('.category-percentage >> nth=0', '50');
    await page.click('#add-category-btn');
    await page.fill('.category-percentage >> nth=1', '30');

    await expect(page.locator('#summary-percentage')).toContainText('80');
  });
});

test.describe('Phase 2 Accessibility', () => {
  test('recurring transactions should be keyboard navigable', async ({ page }) => {
    await page.goto('/transactions/recurring');

    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter'); // Should open modal

    await expect(page.locator('#recurring-modal')).toBeVisible();
  });

  test('transaction splits should have ARIA labels', async ({ page }) => {
    await page.goto('/transactions/1/splits');

    const addButton = page.locator('#add-split-btn');
    await expect(addButton).toHaveAttribute('aria-label');
  });

  test('budget templates should announce modal states', async ({ page }) => {
    await page.goto('/budgets/templates');

    await page.click('#create-template-btn');

    const modal = page.locator('#edit-template-modal');
    await expect(modal).toHaveAttribute('aria-hidden', 'false');
  });

  test('2FA setup should have proper focus management', async ({ page }) => {
    await page.goto('/settings/two-factor');

    await page.click('#enable-2fa-btn');

    // First focusable element should receive focus
    const firstInput = page.locator('#setup-modal input:visible >> nth=0');
    await expect(firstInput).toBeFocused();
  });
});

test.describe('Phase 2 Performance', () => {
  test('recurring transactions page should load quickly', async ({ page }) => {
    const startTime = Date.now();

    await page.goto('/transactions/recurring');
    await page.waitForSelector('.recurring-item, .empty-state');

    const loadTime = Date.now() - startTime;
    expect(loadTime).toBeLessThan(2000);
  });

  test('template creation should be responsive', async ({ page }) => {
    await page.goto('/budgets/templates');

    const startTime = Date.now();
    await page.click('#create-template-btn');

    const openTime = Date.now() - startTime;
    expect(openTime).toBeLessThan(500);
  });
});
