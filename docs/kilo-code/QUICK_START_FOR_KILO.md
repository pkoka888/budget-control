# Quick Start Guide for Kilo Code

## TL;DR

**If you get XML parsing errors:**
- Don't use `apply_diff` with XML
- Use this workflow instead:
  1. Read file
  2. Use Edit tool with exact old_string and new_string
  3. Read file again to verify
  4. Done!

## The Formula That Works

```
Read([file_path])
  ↓
Edit([file_path], [exact_old_string], [exact_new_string])
  ↓
Read([file_path]) // to verify
  ↓
SUCCESS
```

## Key Rules

1. **Match EXACTLY** - Copy from Read output, including spaces/tabs
2. **Include Context** - Add lines before/after to make unique
3. **Verify Always** - Read the file again to confirm change
4. **Small Steps** - Make one change at a time
5. **Never** - Don't use apply_diff with XML if it's causing issues

## Where to Edit

### Services (Most Important)
```
budget-app/src/Services/
├── InvestmentService.php ✓ (Recently improved)
├── GoalService.php (Ready to enhance)
├── BudgetAlertService.php (Ready to enhance)
├── UserSettingsService.php (Ready to enhance)
└── RecurringTransactionService.php (Ready to enhance)
```

### Controllers
```
budget-app/src/Controllers/
├── TransactionController.php
├── ReportController.php
├── SettingsController.php
├── GoalsController.php
├── InvestmentController.php
├── BudgetController.php
└── ApiController.php
```

### Views
```
budget-app/views/
├── transactions/ (Check splits display)
├── investments/ (Check portfolio display)
├── budgets/ (Check alerts display)
├── settings/ (Check all categories)
└── goals/ (Check milestone display)
```

### Styling & Scripts
```
budget-app/public/assets/
├── css/style.css (Add new styles)
└── js/main.js (Add new utilities)
```

## Example: Edit Workflow in Action

### Step 1: Read to Find Location
```
Read("C:\\ClaudeProjects\\budget-control\\budget-app\\src\\Services\\InvestmentService.php")
// Returns file contents, shows line numbers
// Find line 216 where getTransactions() starts
```

### Step 2: Identify Change
```
Find the exact section you want to change
Copy it EXACTLY as it appears in the Read output
Pay attention to indentation and newlines
```

### Step 3: Use Edit
```
Edit(
  file_path: "C:\\ClaudeProjects\\budget-control\\budget-app\\src\\Services\\InvestmentService.php",
  old_string: "PASTE EXACT STRING HERE",
  new_string: "PASTE REPLACEMENT HERE"
)
```

### Step 4: Verify
```
Read("C:\\ClaudeProjects\\budget-control\\budget-app\\src\\Services\\InvestmentService.php")
// Check that your change was applied
// If it looks right, you're done!
```

## Common Gotchas

### ❌ Indentation Mismatch
```php
// WRONG - different indentation
        $query = "...";  // original has 8 spaces
    $query = "...";      // you used 4 spaces
```

### ✅ Correct Indentation
```php
// Copy EXACTLY from Read output
        $query = "...";  // matches exactly
        $query = "...";  // replacement also 8 spaces
```

### ❌ Missing Context
```php
// Can't find unique match - too generic
$count = $this->db->queryOne($countQuery, $countParams);
```

### ✅ Include Surrounding Code
```php
// Unique match - includes context
        $transactions = $this->db->query($query, $params);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total_count
        $count = $this->db->queryOne($countQuery, $countParams);
```

### ❌ Multiline Strings Gone Wrong
```php
// Missing internal newlines
"SELECT COUNT(*) as total_count FROM investments WHERE user_id = ?"
```

### ✅ Preserve Newlines
```php
// Keep exact formatting from file
"SELECT COUNT(*) as total_count
 FROM investments WHERE user_id = ?"
```

## Files You Should Read First

In this order:
1. `KILO_CODE_NEXT_STEPS.md` - What to do next
2. `KILO_CODE_BEST_PRACTICES.md` - How to work reliably
3. `KILO_CODE_FIX_LOG.md` - What was fixed
4. `KILO_CODE_STATUS.md` - System status

## Recent Fix: What Changed

**File**: `InvestmentService.php`
**Method**: `getTransactions()` at line 216

**Old Code** (fragile):
```php
$countQuery = str_replace("SELECT it.*, ...", "SELECT COUNT(*)", $query);
$countQuery = preg_replace('/ORDER BY.*$/', '', $countQuery);
```

**New Code** (robust):
```php
$countQuery = "SELECT COUNT(*) as total_count
               FROM investment_transactions it
               JOIN investments i ON it.investment_id = i.id
               LEFT JOIN investment_accounts ia ON it.account_id = ia.id
               WHERE it.user_id = ?";
// Then apply same filters dynamically
```

**Why**: More maintainable, handles filters correctly, accurate pagination.

## What Works Now

✓ All 11 services
✓ All middleware
✓ Complete database schema
✓ Integrated controllers
✓ Ready for enhancement

## What's Next

1. **View Templates** - Ensure all exist and display new features
2. **UI Components** - Add transaction splits UI
3. **Budget Alerts** - Add alert management UI
4. **Investment Portfolio** - Enhance portfolio view
5. **Goals** - Add milestone tracking UI

## Emergency: If You Get Stuck

If an Edit fails:
1. Read the file again
2. Copy the old_string EXACTLY again
3. Verify indentation matches
4. Check for special characters
5. If still stuck: use smaller old_string with more context

If you get XML error:
1. Stop using apply_diff
2. Use Read → Edit → Verify instead
3. Problem solved!

## One More Time: The Working Approach

```
IF error with apply_diff:
  USE: Read → Edit → Verify
  SUCCESS: 100%

IF trying to use apply_diff:
  ERROR: "StopNode is not closed"
  SOLUTION: Stop, use Read → Edit instead
```

---

**Save this file. Reference it always.**

When in doubt: Read → Edit → Verify. Always works.

Updated: November 8, 2025
