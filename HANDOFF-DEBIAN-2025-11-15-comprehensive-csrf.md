# Handoff Request: Comprehensive CSRF Protection

**Date:** 2025-11-15
**From:** Windows Orchestrator (Claude Code AI)
**To:** Debian Server (claude user - Code changes only)
**Priority:** 🔴 HIGH - Security vulnerability
**Status:** ⏳ PENDING IMPLEMENTATION

---

## Current Situation

### ✅ What's Already Done
- CSRF protection class exists: `src/Middleware/CsrfProtection.php` (excellent implementation)
- Used in 3 controllers:
  - AuthController.php (6 uses)
  - TwoFactorController.php (7 uses)
  - EmailVerificationController.php (2 uses)
- Meta tag in `views/layout.php`
- JavaScript AJAX requests include token in some views

### ❌ What's Missing
CSRF protection is NOT used in **28 other controllers** that handle POST requests:
- AccountController
- TransactionController
- BudgetController
- BillController
- ImportController
- SettingsController
- And 22 more...

This leaves the application vulnerable to CSRF attacks on most state-changing operations.

---

## Requested Actions

### Task 1: Add CSRF Protection to All Controllers with POST Methods

For EACH controller that handles POST/PUT/PATCH/DELETE requests:

#### 1.1. Add Use Statement
At the top of each controller file, add:
```php
use BudgetApp\Middleware\CsrfProtection;
```

#### 1.2. Add Protection to State-Changing Methods
In each method that handles POST/PUT/PATCH/DELETE, add as **first line**:
```php
CsrfProtection::requireToken();
```

**Priority Controllers to Fix** (handle sensitive operations):
1. **TransactionController.php** - Financial data
   - Methods: create, update, delete, bulkDelete, etc.

2. **BudgetController.php** - Budget management
   - Methods: create, update, delete, etc.

3. **AccountController.php** - Account management
   - Methods: create, update, delete, etc.

4. **SettingsController.php** - User settings
   - Methods: updateProfile, updatePassword, etc.

5. **ImportController.php** - File uploads
   - Methods: uploadCSV, processImport, etc.

6. **BillController.php** - Bill payments
   - Methods: create, update, delete, markPaid, etc.

7. **ApprovalController.php** - Approval workflows
   - All POST methods

8. **BankImportController.php** - Bank data import
   - All POST methods

9. **CategoryController.php** - Category management
   - create, update, delete methods

10. **CurrencyController.php** - Currency settings
    - update methods

11. **ExpenseSplitController.php** - Expense splitting
    - All POST methods

12. **GoalsController.php** - Financial goals
    - create, update, delete methods

13. **HouseholdController.php** - Household management
    - All POST methods

14. **InvestmentController.php** - Investment tracking
    - All POST methods

15. **ReceiptController.php** - Receipt uploads (CRITICAL - file upload)
    - upload, delete methods

16. **NotificationController.php** - Notification settings
    - All POST methods

17. **AutomationController.php** - Automation rules
    - All POST methods

18. **ScenarioPlanningController.php** - Financial scenarios
    - All POST methods

19. **ChildAccountController.php** - Child account management
    - All POST methods

20. **CareerController.php** - Career planning
    - All POST methods

21. **AIInsightsController.php** - AI features
    - All POST methods

22. **OpportunitiesController.php** - Opportunities
    - All POST methods

23. **GuidesController.php** - If has POST methods
    - Check and add if needed

24. **TipsController.php** - If has POST methods
    - Check and add if needed

25. **ExportController.php** - Export operations
    - Check if needs protection

26. **ReportController.php** - Report generation
    - Check if needs protection

27. **DashboardController.php** - Dashboard widgets
    - Check if needs protection

28. **ApiController.php** - API endpoints
    - Check if needs protection (may use different auth)

### Task 2: Add CSRF Tokens to All Forms

Find all HTML forms in `views/` directory and add CSRF field:

```php
<form method="POST" action="/transactions/create">
    <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
    <!-- rest of form -->
</form>
```

**Or** if using the short helper:
```php
<?= \BudgetApp\Middleware\CsrfProtection::field() ?>
```

**Views to Check** (at minimum):
- `views/transactions/*.php` - Transaction forms
- `views/budgets/*.php` - Budget forms
- `views/accounts/*.php` - Account forms
- `views/settings/*.php` - Settings forms
- `views/import/*.php` - Import forms
- `views/bills/*.php` - Bill forms
- `views/auth/*.php` - Auth forms (likely already done)
- Any other views with `<form method="POST">` or `<form method="post">`

### Task 3: Ensure JavaScript AJAX Requests Include Token

Update any JavaScript files that make POST/PUT/PATCH/DELETE AJAX requests:

```javascript
// Get token from meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// Include in fetch/axios requests
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(data)
});
```

**Files to Check**:
- `views/**/*.php` (inline JavaScript)
- `public/js/**/*.js` (if any)
- Look for: `fetch()`, `XMLHttpRequest`, `$.ajax()`, `axios()`, etc.

---

## Implementation Strategy

### Recommended Order:

1. **Phase 1: Critical Controllers** (30 min)
   - TransactionController
   - BudgetController
   - AccountController
   - SettingsController
   - ImportController
   - ReceiptController (has file upload)

2. **Phase 2: Remaining Controllers** (30 min)
   - All other controllers with POST methods

3. **Phase 3: Forms** (45 min)
   - Add CSRF field to all HTML forms
   - Test each form renders correctly

4. **Phase 4: JavaScript** (30 min)
   - Update AJAX calls to include token
   - Test AJAX operations work

5. **Phase 5: Testing** (30 min)
   - Test critical workflows:
     - Create/edit/delete transaction
     - Create/edit budget
     - Import CSV file
     - Upload receipt
     - Update settings
   - Verify no 403 CSRF errors

**Total Estimated Time: 2.5 hours**

---

## How to Find Methods That Need Protection

```bash
# Find all methods that likely handle POST
cd /var/www/budget-control/budget-app/src/Controllers/

# Check each controller for methods like:
# - create, store, save
# - update, edit
# - delete, destroy, remove
# - process, handle, submit

grep -n "public function.*\(create\|update\|delete\|store\|save\|process\|handle\|submit\)" *.php
```

Then check if those methods already have `CsrfProtection::requireToken()`.

---

## Testing After Implementation

### Manual Testing Checklist

After implementing, test these workflows:

- [ ] **Login** (already protected, should still work)
- [ ] **Create transaction** - Should work with token
- [ ] **Edit transaction** - Should work with token
- [ ] **Delete transaction** - Should work with token
- [ ] **Create budget** - Should work with token
- [ ] **Import CSV** - Should work with token
- [ ] **Upload receipt** - Should work with token
- [ ] **Update user settings** - Should work with token
- [ ] **Try POST without token** - Should get 403 error
- [ ] **AJAX requests** - Should include X-CSRF-Token header

### Automated Testing (if Playwright tests exist)

```bash
# After code changes, run from Debian:
cd /var/www/budget-control
npx playwright test --grep "transaction"
npx playwright test --grep "budget"
npx playwright test --grep "import"

# Check for 403 errors in test output
```

**Note:** Don't restart Apache/PHP-FPM yourself - code changes in PHP don't require restart. Just refresh browser.

---

## Common Patterns to Add

### Pattern 1: Simple POST Method
```php
public function create(): void
{
    // ADD THIS AS FIRST LINE:
    CsrfProtection::requireToken();

    // Rest of existing code...
    $data = $_POST;
    // ...
}
```

### Pattern 2: AJAX Endpoint
```php
public function apiCreate(): void
{
    // ADD THIS:
    CsrfProtection::requireToken();

    // AJAX endpoints expect JSON response
    // The requireToken() method already handles this correctly
    // (returns 403 JSON on failure)

    // Rest of code...
}
```

### Pattern 3: File Upload
```php
public function uploadReceipt(): void
{
    // ADD THIS BEFORE FILE PROCESSING:
    CsrfProtection::requireToken();

    // Now process upload...
    $file = $_FILES['receipt'] ?? null;
    // ...
}
```

### Pattern 4: Bulk Operations
```php
public function bulkDelete(): void
{
    // ADD THIS:
    CsrfProtection::requireToken();

    // Process bulk operation...
    $ids = $_POST['ids'] ?? [];
    // ...
}
```

---

## Edge Cases to Consider

### 1. GET Methods That Shouldn't Change State
If a controller has a `delete()` method that uses GET (bad practice), **DON'T** add CSRF protection yet. Instead:
- Document it in completion report
- Recommend changing to POST/DELETE method first

### 2. API Endpoints with Different Authentication
If `ApiController.php` uses token-based auth instead of sessions:
- CSRF might not be needed
- Check if it uses session auth or API tokens
- Document decision in completion report

### 3. Read-Only Controllers
Controllers like `DashboardController`, `ReportController` that only display data:
- Might not need CSRF if they have no POST methods
- Verify they don't have hidden POST methods

---

## Verification Checklist

When complete, verify:

- [ ] All controllers with POST methods import `CsrfProtection`
- [ ] All state-changing methods call `requireToken()`
- [ ] All HTML forms include CSRF field
- [ ] All AJAX requests include CSRF header
- [ ] Login still works (already protected)
- [ ] Transaction create/edit/delete works
- [ ] Budget operations work
- [ ] Settings update works
- [ ] File upload works
- [ ] Submitting form without token returns 403
- [ ] No console errors in browser

---

## Git Workflow

```bash
cd /var/www/budget-control
git checkout -b fix/comprehensive-csrf-protection

# After each major section (e.g., all controllers done):
git add src/Controllers/
git commit -m "Add CSRF protection to all controllers

- Added CsrfProtection::requireToken() to all POST/PUT/PATCH/DELETE methods
- Covers 28 previously unprotected controllers
- Critical security fix for CSRF vulnerability"

# After forms:
git add views/
git commit -m "Add CSRF tokens to all HTML forms

- Added CsrfProtection::field() to all POST forms
- Ensures token is submitted with every state-changing request"

# After JavaScript:
git add views/ public/
git commit -m "Update AJAX requests to include CSRF token

- All fetch/AJAX calls now include X-CSRF-Token header
- Consistent with existing TwoFactorController implementation"

# When complete:
git push origin fix/comprehensive-csrf-protection
```

---

## Completion Report

When done, create: `HANDOFF-DEBIAN-2025-11-15-comprehensive-csrf-COMPLETED.md`

Include:
- ✅ List of controllers protected
- ✅ List of forms updated
- ✅ List of JavaScript files updated
- ⚠️ Any controllers skipped (with reason)
- ⚠️ Any edge cases encountered
- ✅ Testing results (what you tested and results)
- 📊 Git commit hashes
- 🔍 Any issues discovered during implementation

---

## Notes & Constraints

**What You CAN Do:**
- ✅ Edit PHP files in `/var/www/budget-control/budget-app/src/`
- ✅ Edit view files in `/var/www/budget-control/budget-app/views/`
- ✅ Run git commands
- ✅ Test by accessing site in browser
- ✅ Check error logs: `tail /var/log/apache2/budget.okamih.cz-error.log`

**What You CANNOT Do:**
- ❌ Restart Apache or PHP-FPM (not needed for code changes)
- ❌ Modify system files
- ❌ Install packages

**If You Need Help:**
- PHP changes auto-reload (just refresh browser)
- Check PHP errors in Apache error log
- Test one controller at a time to isolate issues

---

## Questions?

If you encounter:
- **Syntax errors**: Check PHP error log
- **CSRF protection too aggressive**: May need to whitelist certain endpoints
- **AJAX calls failing**: Verify X-CSRF-Token header is being sent
- **Form submissions failing**: Verify CSRF field is in form

Document any issues in completion report.

---

**Priority:** 🔴 HIGH
**Impact:** Fixes CSRF vulnerability across entire application
**Estimated Time:** 2.5 hours
**Complexity:** Medium (repetitive but straightforward)

---

**END OF HANDOFF REQUEST**
