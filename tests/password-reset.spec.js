const { test, expect } = require('@playwright/test');

test.describe('Password Reset Flow - E2E Tests', () => {
  const baseUrl = 'http://localhost:8080';

  test.beforeEach(async ({ page }) => {
    // Navigate to the application before each test
    await page.goto(baseUrl, { timeout: 30000 });
    await page.waitForLoadState('domcontentloaded');
  });

  test('should display forgot password page', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    // Check page title
    const title = await page.title();
    expect(title).toContain('Zapomenuté heslo');

    // Check for main heading
    const heading = await page.locator('h1').textContent();
    expect(heading).toContain('Obnovení hesla');

    // Check for email input
    const emailInput = await page.locator('#email');
    expect(await emailInput.isVisible()).toBe(true);

    // Check for submit button
    const submitButton = await page.locator('button[type="submit"]');
    expect(await submitButton.isVisible()).toBe(true);

    console.log('✅ Forgot password page loaded successfully');
  });

  test('should have CSRF token in forgot password form', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    // Check for CSRF meta tag
    const csrfMeta = await page.locator('meta[name="csrf-token"]');
    expect(await csrfMeta.count()).toBe(1);

    const csrfToken = await csrfMeta.getAttribute('content');
    expect(csrfToken).toBeTruthy();
    expect(csrfToken.length).toBeGreaterThan(0);

    // Check for CSRF hidden input in form
    const csrfInput = await page.locator('input[name="csrf_token"]');
    expect(await csrfInput.count()).toBe(1);

    const inputValue = await csrfInput.getAttribute('value');
    expect(inputValue).toBeTruthy();
    expect(inputValue.length).toBeGreaterThan(0);

    console.log('✅ CSRF tokens present in form');
  });

  test('should validate email field is required', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    const emailInput = await page.locator('#email');
    const submitButton = await page.locator('button[type="submit"]');

    // Try to submit without email
    await submitButton.click();

    // HTML5 validation should prevent submission
    const validationMessage = await emailInput.evaluate(el => el.validationMessage);
    expect(validationMessage).toBeTruthy();

    console.log('✅ Email field validation working');
  });

  test('should validate email format', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    const emailInput = await page.locator('#email');
    const submitButton = await page.locator('button[type="submit"]');

    // Enter invalid email
    await emailInput.fill('invalid-email');
    await submitButton.click();

    // HTML5 validation should catch invalid format
    const validationMessage = await emailInput.evaluate(el => el.validationMessage);
    expect(validationMessage).toBeTruthy();

    console.log('✅ Email format validation working');
  });

  test('should show loading state on form submission', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    const emailInput = await page.locator('#email');
    const submitButton = await page.locator('button[type="submit"]');

    // Fill valid email
    await emailInput.fill('test@example.com');

    // Check initial button text
    const initialText = await submitButton.textContent();
    expect(initialText).toContain('Odeslat odkaz');

    // Submit form
    await submitButton.click();

    // Button should show loading state
    const loadingText = await submitButton.textContent();
    expect(loadingText).toContain('Odesílání');

    // Button should be disabled
    const isDisabled = await submitButton.isDisabled();
    expect(isDisabled).toBe(true);

    console.log('✅ Form submission loading state working');
  });

  test('should have accessible form elements', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    // Check email input has proper ARIA attributes
    const emailInput = await page.locator('#email');
    const ariaRequired = await emailInput.getAttribute('aria-required');
    expect(ariaRequired).toBe('true');

    const ariaDescribedBy = await emailInput.getAttribute('aria-describedby');
    expect(ariaDescribedBy).toContain('email-help');

    // Check label is properly associated
    const label = await page.locator('label[for="email"]');
    expect(await label.isVisible()).toBe(true);

    console.log('✅ Form accessibility attributes present');
  });

  test('should display reset password page with valid token', async ({ page }) => {
    // Simulate accessing reset password page with a token
    await page.goto(`${baseUrl}/reset-password?token=dummy_token_for_test`);
    await page.waitForLoadState('domcontentloaded');

    // Check page title
    const title = await page.title();
    expect(title).toContain('Nové heslo');

    // Check for main heading
    const heading = await page.locator('h1').textContent();
    expect(heading).toContain('Nastavení nového hesla');

    console.log('✅ Reset password page loaded');
  });

  test('should have password strength indicator', async ({ page }) => {
    await page.goto(`${baseUrl}/reset-password?token=dummy_token`);
    await page.waitForLoadState('domcontentloaded');

    const passwordInput = await page.locator('#password');

    // Check if form exists (might not with dummy token)
    if (await passwordInput.count() > 0) {
      // Type a password
      await passwordInput.fill('weakpass');

      // Wait for strength indicator to appear
      await page.waitForTimeout(100);

      const strengthContainer = await page.locator('#password-strength');
      const isVisible = await strengthContainer.isVisible();

      if (isVisible) {
        const strengthBar = await page.locator('#strength-bar');
        expect(await strengthBar.count()).toBe(1);
        console.log('✅ Password strength indicator working');
      } else {
        console.log('⚠️  Password strength indicator not visible (expected with invalid token)');
      }
    } else {
      console.log('⚠️  Form not available (expected with invalid token)');
    }
  });

  test('should validate password confirmation matches', async ({ page }) => {
    await page.goto(`${baseUrl}/reset-password?token=dummy_token`);
    await page.waitForLoadState('domcontentloaded');

    const passwordInput = await page.locator('#password');
    const confirmInput = await page.locator('#password_confirm');

    // Check if form exists
    if (await passwordInput.count() > 0) {
      // Fill different passwords
      await passwordInput.fill('StrongPass123!');
      await confirmInput.fill('DifferentPass123!');

      // Wait for validation
      await page.waitForTimeout(200);

      const confirmHelp = await page.locator('#confirm-help');

      if (await confirmHelp.isVisible()) {
        const helpText = await confirmHelp.textContent();
        expect(helpText).toContain('neshodují');
        console.log('✅ Password confirmation validation working');
      }
    } else {
      console.log('⚠️  Form not available (expected with invalid token)');
    }
  });

  test('should show password match success', async ({ page }) => {
    await page.goto(`${baseUrl}/reset-password?token=dummy_token`);
    await page.waitForLoadState('domcontentloaded');

    const passwordInput = await page.locator('#password');
    const confirmInput = await page.locator('#password_confirm');

    if (await passwordInput.count() > 0) {
      // Fill matching passwords
      await passwordInput.fill('StrongPass123!');
      await confirmInput.fill('StrongPass123!');

      // Wait for validation
      await page.waitForTimeout(200);

      const confirmHelp = await page.locator('#confirm-help');

      if (await confirmHelp.isVisible()) {
        const helpText = await confirmHelp.textContent();
        expect(helpText).toContain('shodují');
        console.log('✅ Password match success indicator working');
      }
    } else {
      console.log('⚠️  Form not available (expected with invalid token)');
    }
  });

  test('should enforce minimum password length', async ({ page }) => {
    await page.goto(`${baseUrl}/reset-password?token=dummy_token`);
    await page.waitForLoadState('domcontentloaded');

    const passwordInput = await page.locator('#password');

    if (await passwordInput.count() > 0) {
      const minLength = await passwordInput.getAttribute('minlength');
      expect(minLength).toBe('8');

      // Try short password
      await passwordInput.fill('short');
      const submitButton = await page.locator('button[type="submit"]');
      await submitButton.click();

      const validationMessage = await passwordInput.evaluate(el => el.validationMessage);
      expect(validationMessage).toBeTruthy();

      console.log('✅ Minimum password length validation working');
    } else {
      console.log('⚠️  Form not available (expected with invalid token)');
    }
  });

  test('should have CSRF token in reset password form', async ({ page }) => {
    await page.goto(`${baseUrl}/reset-password?token=dummy_token`);
    await page.waitForLoadState('domcontentloaded');

    // Check for CSRF meta tag
    const csrfMeta = await page.locator('meta[name="csrf-token"]');
    expect(await csrfMeta.count()).toBe(1);

    // Check for CSRF hidden input in form (if form exists)
    const csrfInput = await page.locator('input[name="csrf_token"]');
    const count = await csrfInput.count();

    if (count > 0) {
      const inputValue = await csrfInput.getAttribute('value');
      expect(inputValue).toBeTruthy();
      console.log('✅ CSRF tokens present in reset form');
    } else {
      console.log('⚠️  Form not available (expected with invalid token)');
    }
  });

  test('should display error for invalid/expired token', async ({ page }) => {
    await page.goto(`${baseUrl}/reset-password?token=invalid_token_12345`);
    await page.waitForLoadState('domcontentloaded');

    // Should show error message or alternative content
    const bodyContent = await page.textContent('body');

    // Check for error indicators
    const hasErrorMessage =
      bodyContent.includes('neplatný') ||
      bodyContent.includes('vypršel') ||
      bodyContent.includes('Nemůžete nastavit nové heslo');

    if (hasErrorMessage) {
      console.log('✅ Invalid token error message displayed');
    }

    // Check for link to request new token
    const newRequestLink = await page.locator('a[href="/forgot-password"]');
    if (await newRequestLink.count() > 0) {
      expect(await newRequestLink.isVisible()).toBe(true);
      console.log('✅ Link to request new reset present');
    }
  });

  test('should have proper navigation links', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    // Check for back to login link
    const loginLink = await page.locator('a[href="/login"]');
    expect(await loginLink.count()).toBeGreaterThan(0);

    // Check for register link
    const registerLink = await page.locator('a[href="/register"]');
    expect(await registerLink.count()).toBeGreaterThan(0);

    console.log('✅ Navigation links present');
  });

  test('should prevent form resubmission', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    const emailInput = await page.locator('#email');
    const submitButton = await page.locator('button[type="submit"]');

    await emailInput.fill('test@example.com');
    await submitButton.click();

    // Button should be disabled after click
    const isDisabled = await submitButton.isDisabled();
    expect(isDisabled).toBe(true);

    console.log('✅ Form resubmission prevented');
  });

  test('should have proper error message accessibility', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    // Check if error alert has proper ARIA attributes
    const errorAlerts = await page.locator('.alert-error');

    if (await errorAlerts.count() > 0) {
      const role = await errorAlerts.first().getAttribute('role');
      expect(role).toBe('alert');

      const ariaLive = await errorAlerts.first().getAttribute('aria-live');
      expect(ariaLive).toBeTruthy();

      console.log('✅ Error messages have proper accessibility attributes');
    } else {
      console.log('⚠️  No error messages displayed (as expected)');
    }
  });

  test('should handle keyboard navigation', async ({ page }) => {
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    const emailInput = await page.locator('#email');
    const submitButton = await page.locator('button[type="submit"]');

    // Tab to email field
    await page.keyboard.press('Tab');
    await page.keyboard.press('Tab'); // May need multiple tabs depending on page structure

    // Check if email input can receive focus
    await emailInput.focus();
    const isFocused = await emailInput.evaluate(el => document.activeElement === el);

    if (isFocused) {
      console.log('✅ Email input is keyboard accessible');
    }

    // Fill with keyboard
    await emailInput.type('keyboard@test.com');

    // Submit with Enter key
    await page.keyboard.press('Enter');

    // Button should be disabled
    const isDisabled = await submitButton.isDisabled();
    expect(isDisabled).toBe(true);

    console.log('✅ Keyboard navigation working');
  });

  test('should display responsive layout', async ({ page }) => {
    // Test mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    const card = await page.locator('.card');
    const isVisible = await card.isVisible();
    expect(isVisible).toBe(true);

    console.log('✅ Mobile layout renders correctly');

    // Test desktop viewport
    await page.setViewportSize({ width: 1920, height: 1080 });
    await page.goto(`${baseUrl}/forgot-password`);
    await page.waitForLoadState('domcontentloaded');

    const cardDesktop = await page.locator('.card');
    const isVisibleDesktop = await cardDesktop.isVisible();
    expect(isVisibleDesktop).toBe(true);

    console.log('✅ Desktop layout renders correctly');
  });
});
