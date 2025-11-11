# Kilo Code Backend Tasks

## Overview
Your role: Implement backend features, service methods, and API endpoints

**Key Principle**: One task at a time. Smaller, manageable pieces.

---

## Critical Workflow (ALWAYS USE THIS)

### For Every Task
```
Step 1: Read file
  → Get the file contents

Step 2: Find location
  → Understand where to add code
  → Review similar patterns

Step 3: Edit file
  → Use Edit tool with exact old_string and new_string
  → Match indentation precisely
  → Include surrounding context

Step 4: Verify
  → Read file again
  → Check that change was applied
  → Look for any issues
```

### If Edit Fails
```
1. Read file again
2. Copy the exact old_string (including spaces/tabs)
3. Check indentation matches
4. Add more surrounding context
5. Try Edit again
```

### If Stuck
```
1. Ask Claude (don't get frustrated)
2. Show what you're trying to do
3. Share the error message
4. Claude will help immediately
```

---

## Task K-6.2: Goal Progress Tracking Enhancement

### What It Does
Users want to see how their goals are progressing over time. Currently, we calculate progress, but we don't track historical data.

### What You'll Add
Methods to:
1. Record goal progress at a point in time
2. Retrieve progress history
3. Calculate progress trends
4. Get milestone completion timeline

### Files to Modify
- `src/Services/GoalService.php` - Add tracking methods

### Step-by-Step Implementation

#### Step 1: Add Progress History Tracking Method
```php
/**
 * Record goal progress snapshot
 */
public function recordProgressSnapshot(int $goalId, float $currentAmount): void {
    // Insert into a temporary tracking variable or database
    // This records: goalId, currentAmount, timestamp
}
```

**What to do**:
1. Read GoalService.php
2. Find the `calculateGoalProgress()` method
3. Add new method after it
4. Record: goal_id, amount, date
5. Test that it saves

#### Step 2: Add Progress History Retrieval
```php
/**
 * Get goal progress history
 */
public function getProgressHistory(int $goalId, int $limit = 30): array {
    // Return progress history for the goal
    // Latest first
}
```

**What to do**:
1. Add method to GoalService
2. Query database for progress records
3. Sort by date descending
4. Limit to specified number
5. Return array with dates and amounts

#### Step 3: Add Milestone Timeline
```php
/**
 * Get milestone completion timeline
 */
public function getMilestoneTimeline(int $goalId): array {
    // Return timeline of milestone completions
}
```

**What to do**:
1. Query goal_milestones table
2. Filter by goal_id
3. Get completed ones (is_completed = 1)
4. Include completion dates
5. Return sorted by completion date

#### Step 4: Update Controller
Update `src/Controllers/GoalsController.php`:
```php
public function getProgressHistory(array $params = []): void {
    // Get goal ID from params
    // Call service method
    // Return JSON response
}
```

**What to do**:
1. Add method to GoalsController
2. Get goalId from params
3. Call service getProgressHistory()
4. Return as JSON
5. Test with API call

### Success Criteria
- [ ] `recordProgressSnapshot()` saves data
- [ ] `getProgressHistory()` retrieves data
- [ ] `getMilestoneTimeline()` returns milestones
- [ ] Controller endpoint works
- [ ] No database errors

### Time: 1-2 hours
### Complexity: Medium
### Database: Uses goal_milestones table (already exists)

---

## Task K-6.3: Savings Calculator

### What It Does
Users want to know: "If I save X per month, when will I reach my goal?"

### What You'll Add
Methods to:
1. Calculate required monthly savings
2. Project completion date
3. Generate savings scenarios
4. Compare different saving rates

### Files to Modify
- `src/Services/GoalService.php` - Add calculator methods

### Step-by-Step Implementation

#### Step 1: Calculate Monthly Savings Needed
```php
/**
 * Calculate monthly savings needed to reach goal
 */
public function calculateSavingsNeeded(int $goalId): float {
    // Get goal
    // Get remaining amount
    // Get months until target date
    // Return: remaining_amount / months
}
```

**What to do**:
1. Read GoalService to understand goal structure
2. Add method to calculate
3. Get goal from database
4. Calculate remaining = target - current
5. Calculate months = (target_date - now) / 30
6. Return remaining / months
7. Test with sample goal

#### Step 2: Project Completion Date
```php
/**
 * Calculate when goal will be completed
 * based on monthly contribution
 */
public function projectCompletionDate(int $goalId, float $monthlyContribution): string {
    // Get goal
    // Calculate how many months needed
    // Add months to today
    // Return projected date
}
```

**What to do**:
1. Get goal from database
2. Calculate: months_needed = (target - current) / monthly_contribution
3. Add months to current date
4. Return date string
5. Test with various contributions

#### Step 3: Generate Savings Scenarios
```php
/**
 * Generate savings scenarios
 * Shows different saving rates and completion dates
 */
public function getSavingsScenarios(int $goalId): array {
    // Return array of scenarios:
    // [
    //   ['monthly_savings' => 100, 'completion_date' => '2026-01-15', 'months' => 12],
    //   ['monthly_savings' => 200, 'completion_date' => '2025-07-15', 'months' => 6],
    //   ...
    // ]
}
```

**What to do**:
1. Get goal details
2. Create scenarios for different amounts (e.g., $100, $200, $500, $1000)
3. For each: calculate completion date
4. Return array of scenarios
5. Test that dates are reasonable

#### Step 4: Update Controller
Update `src/Controllers/GoalsController.php`:
```php
public function getSavingsCalculation(array $params = []): void {
    // Get goal ID and optional monthly_contribution
    // Call service methods
    // Return calculations as JSON
}
```

**What to do**:
1. Add method to controller
2. Get goalId from params
3. Get optional monthlyContribution from params
4. Call calculateSavingsNeeded()
5. If contribution given, call projectCompletionDate()
6. Call getSavingsScenarios()
7. Return all as JSON

### Success Criteria
- [ ] `calculateSavingsNeeded()` returns correct value
- [ ] `projectCompletionDate()` returns valid date
- [ ] `getSavingsScenarios()` returns array of scenarios
- [ ] Controller endpoint returns JSON
- [ ] Math is accurate

### Time: 1-2 hours
### Complexity: Medium
### Database: Uses goals table (already exists)

---

## Task K-7.2: Data Management Features

### What It Does
Users want to:
1. Export all their data (backup)
2. Import data (restore)
3. Delete all data (privacy)

### What You'll Add
Methods to:
1. Export user data to JSON
2. Import JSON data back
3. Validate imported data
4. Delete all user data safely

### Files to Modify
- `src/Services/UserSettingsService.php` - Add data management
- `src/Controllers/SettingsController.php` - Already has stubs

### Step-by-Step Implementation

#### Step 1: Review Existing Methods
```php
// These should already exist:
$service->exportUserData($userId);
$service->importUserData($userId, $data);
$service->deleteUserAccount($userId);
```

**What to do**:
1. Read UserSettingsService.php
2. Check if these methods exist
3. If they do, verify they work correctly
4. If not, implement them

#### Step 2: Check Export Method
The export should include:
- User profile (name, email)
- All accounts
- All transactions
- All categories
- All budgets
- All investments
- All goals
- All settings

**What to do**:
1. Read exportUserData() method
2. Verify it collects all data
3. Check it returns JSON format
4. Test export with sample user

#### Step 3: Check Import Method
The import should:
- Validate JSON format
- Check all required fields
- Import without duplicates
- Maintain relationships

**What to do**:
1. Read importUserData() method
2. Check it validates data
3. Verify it imports correctly
4. Test that relationships are maintained
5. Test duplicate detection

#### Step 4: Check Delete Method
The delete should:
- Delete all user data
- Cascade properly
- Require confirmation
- Log deletion

**What to do**:
1. Read deleteUserAccount() method
2. Check it deletes from all tables
3. Verify cascade delete works
4. Test that user is completely removed

#### Step 5: Enhance Controller
If needed, update `SettingsController.php`:
```php
public function exportData(): void {
    // Already implemented, verify it works
}

public function importData(): void {
    // Already implemented, verify it works
}

public function deleteAccount(): void {
    // Already implemented, verify it works
}
```

**What to do**:
1. Test each endpoint
2. Verify file download works
3. Verify file upload works
4. Verify deletion confirmation works

### Success Criteria
- [ ] Export creates valid JSON file
- [ ] Import restores all data
- [ ] Data relationships maintained
- [ ] Delete removes everything
- [ ] All endpoints work

### Time: 2-3 hours
### Complexity: Medium-High
### Database: Multiple tables involved

---

## Task K-7.3: Security Settings Enhancement

### What It Does
Add two-factor authentication and advanced security options

### What You'll Add
Methods to:
1. Enable/disable 2FA
2. Generate backup codes
3. Verify 2FA token on login
4. Manage active sessions

### Files to Modify
- `src/Services/UserSettingsService.php` - Add security methods
- `src/Controllers/SettingsController.php` - Add endpoints

### Step-by-Step Implementation

#### Step 1: Add 2FA Enable Method
```php
/**
 * Enable two-factor authentication
 */
public function enable2FA(int $userId): array {
    // Generate secret key
    // Create backup codes (8 codes)
    // Save to database
    // Return secret and codes for QR code
}
```

**What to do**:
1. Add method to UserSettingsService
2. Generate random secret (using TOTP library or simple random)
3. Generate 8 backup codes (8 random codes)
4. Save secret to user_settings table
5. Save backup codes (encrypted if possible)
6. Return array with secret and codes
7. Test generation

#### Step 2: Add 2FA Disable Method
```php
/**
 * Disable two-factor authentication
 */
public function disable2FA(int $userId): bool {
    // Delete 2FA secret
    // Delete backup codes
    // Return success
}
```

**What to do**:
1. Add method to UserSettingsService
2. Delete from user_settings (2fa_secret)
3. Delete backup codes
4. Log the change
5. Return true on success

#### Step 3: Add 2FA Verification Method
```php
/**
 * Verify 2FA token
 */
public function verify2FA(int $userId, string $token): bool {
    // Get user's 2FA secret
    // Verify token matches
    // Return true if valid
}
```

**What to do**:
1. Add method
2. Get user's 2FA secret from database
3. Generate current TOTP token
4. Compare with provided token
5. Return true if match
6. Also check backup codes

#### Step 4: Add Backup Code Generation
```php
/**
 * Generate backup codes
 */
private function generateBackupCodes(): array {
    // Create 8 unique codes
    // Format: XXXX-XXXX-XXXX pattern
    // Return array of codes
}
```

**What to do**:
1. Add helper method
2. Generate 8 random codes
3. Format nicely (dashes, uppercase)
4. Store in database
5. Return for user to save

#### Step 5: Update Controller
Add to `SettingsController.php`:
```php
public function updateSecurity(array $params = []): void {
    // Already has stub, verify it works
    // Handle 2FA enable/disable
}
```

**What to do**:
1. Check if updateSecurity() method exists
2. Verify it calls service methods
3. Test 2FA enable
4. Test 2FA disable
5. Test token verification

### Success Criteria
- [ ] 2FA can be enabled
- [ ] QR code generation works
- [ ] Backup codes generated and stored
- [ ] Token verification works
- [ ] 2FA can be disabled
- [ ] Backup codes work as fallback

### Time: 2-3 hours
### Complexity: Medium-High
### Database: Uses user_settings table

---

## Task K-8.2: API Authentication Enhancement

### What It Does
Improve API security with permission levels and scope-based access

### What You'll Add
Methods to:
1. Add permission levels to API keys
2. Implement scope validation
3. Add API key rotation
4. Enforce permission checking

### Files to Modify
- `src/Middleware/ApiAuthMiddleware.php` - Enhance auth
- `src/Controllers/ApiController.php` - Add key management

### Step-by-Step Implementation

#### Step 1: Add Permission Levels
Update `ApiAuthMiddleware.php`:
```php
/**
 * Define permission levels
 * read: GET endpoints only
 * write: GET, POST, PUT endpoints
 * admin: All endpoints
 */
private function hasPermission(string $apiKey, string $requiredPermission): bool {
    // Get key's permission level
    // Check if it has access
    // Return true/false
}
```

**What to do**:
1. Read ApiAuthMiddleware.php
2. Add permission checking method
3. Query api_keys table for permissions
4. Compare required vs actual
5. Return boolean
6. Test with different permissions

#### Step 2: Add Scope Validation
```php
/**
 * Validate request scope
 */
private function validateScope(string $apiKey, string $endpoint): bool {
    // Get key's scopes
    // Check if endpoint is in scopes
    // Return true/false
}
```

**What to do**:
1. Add method to middleware
2. Get scopes from api_keys table
3. Compare requested endpoint to scopes
4. Return validation result
5. Test with different scopes

#### Step 3: Add Key Rotation
Update `ApiController.php`:
```php
/**
 * Rotate API key
 */
public function rotateKey(array $params = []): void {
    // Generate new key
    // Mark old key as inactive
    // Return new key
}
```

**What to do**:
1. Add method to ApiController
2. Get API key ID from params
3. Generate new random key
4. Update database with new key
5. Optionally retire old key
6. Return new key to user

#### Step 4: Add Rate Limiting
The middleware should already have rate limiting. Verify it:
```php
// Check if implemented in middleware
// If not, add rate limiting tracking
```

**What to do**:
1. Check ApiAuthMiddleware for rate limiting
2. If missing, add rate limit tracking
3. Track requests per hour per key
4. Return 429 if limit exceeded
5. Test rate limiting

#### Step 5: Update Controller
Add endpoints to `ApiController.php`:
```php
public function createApiKey(array $params = []): void {
    // Create new API key
}

public function deleteApiKey(array $params = []): void {
    // Delete API key
}

public function getApiKeys(array $params = []): void {
    // List user's API keys
}
```

**What to do**:
1. Add methods to controller
2. Implement key generation
3. Implement key deletion
4. Implement key listing
5. Test all endpoints

### Success Criteria
- [ ] Permission levels enforced
- [ ] Scope validation works
- [ ] Keys can be rotated
- [ ] Rate limiting active
- [ ] All endpoints secure

### Time: 2-3 hours
### Complexity: Medium
### Database: Uses api_keys, api_rate_limits tables

---

## Task K-8.3: API Documentation

### What It Does
Create comprehensive documentation for the API

### What You'll Create
- Endpoint reference
- Request/response examples
- Authentication guide
- Error codes
- Rate limiting info

### Files to Create
- `docs/API.md` - New documentation file
- Add PHPDoc comments to ApiController

### Step-by-Step Implementation

#### Step 1: Create API Documentation File
Create `docs/API.md`:
```markdown
# Budget Control API Documentation

## Base URL
https://yourapp.com/api/v1

## Authentication
... (document required headers)

## Endpoints
... (list all endpoints)

## Examples
... (show request/response examples)

## Error Codes
... (document all errors)

## Rate Limiting
... (document rate limits)
```

**What to do**:
1. Create docs/API.md file
2. Add authentication section
3. Document each endpoint
4. Add example requests
5. Add example responses
6. Document error codes
7. Document rate limiting

#### Step 2: Document Each Endpoint
For each endpoint, document:
```
GET /api/v1/transactions
- Description: List transactions
- Authentication: Required (api_key header)
- Parameters: page, limit, category, etc.
- Response: { transactions: [], total: 0 }
- Status Codes: 200, 401, 429
```

**What to do**:
1. List all API endpoints
2. For each: document method, path, description
3. Document required parameters
4. Document optional parameters
5. Document response format
6. Document status codes
7. Add example request/response

#### Step 3: Add Code Comments
Update `ApiController.php` with PHPDoc:
```php
/**
 * List transactions
 * GET /api/v1/transactions
 *
 * @param array $params Query parameters (page, limit, etc.)
 * @return void JSON response
 */
public function getTransactions(array $params = []): void {
    // ...
}
```

**What to do**:
1. Add PHPDoc to each method
2. Include method type (GET, POST, etc.)
3. Include description
4. Include parameters
5. Include return type
6. Include example response

#### Step 4: Document Error Codes
Add error reference:
```markdown
## Error Codes

401 Unauthorized
- Missing api_key header
- Invalid api_key

403 Forbidden
- Insufficient permissions
- API key disabled

429 Too Many Requests
- Rate limit exceeded
- Retry after X seconds

500 Internal Server Error
- Unexpected error
- Contact support
```

**What to do**:
1. List all HTTP status codes used
2. Explain each error
3. Provide solutions
4. Add retry information

### Success Criteria
- [ ] All endpoints documented
- [ ] Examples provided
- [ ] Error codes listed
- [ ] Authentication clear
- [ ] Easy to understand

### Time: 2-3 hours
### Complexity: Low-Medium
### Dependencies: Existing API endpoints

---

## Task K-5.3: Asset Allocation & Rebalancing

### What It Does
Provide portfolio optimization recommendations and rebalancing tools

### What You'll Add
Methods to:
1. Calculate ideal asset allocation
2. Get rebalancing advice
3. Suggest allocation by risk profile
4. Calculate rebalancing trades

### Files to Modify
- `src/Services/InvestmentService.php` - Add methods

### Step-by-Step Implementation

#### Step 1: Add Asset Allocation Calculator
```php
/**
 * Get current asset allocation
 */
public function getCurrentAssetAllocation(int $userId): array {
    // Get all investments
    // Calculate total value per asset type
    // Calculate percentage
    // Return allocation array
}
```

**What to do**:
1. Read InvestmentService.php
2. Add method to calculate allocation
3. Group investments by asset_type (stock, bond, etf, etc.)
4. Calculate total value for each type
5. Calculate percentages
6. Return structured array
7. Test calculation

#### Step 2: Add Ideal Allocation by Risk Profile
```php
/**
 * Get ideal allocation for risk profile
 */
public function getIdealAllocationByRisk(string $riskProfile): array {
    // Return ideal allocation for risk level
    // conservative: 70% bonds, 30% stocks
    // moderate: 50% bonds, 50% stocks
    // aggressive: 20% bonds, 80% stocks
}
```

**What to do**:
1. Add method with risk profiles
2. Define allocations for each profile:
   - conservative: safe, low growth
   - moderate: balanced
   - aggressive: high growth, high risk
3. Return as array with percentages
4. Test different profiles

#### Step 3: Add Rebalancing Recommendation
```php
/**
 * Get rebalancing advice
 */
public function getRebalancingAdvice(int $userId, string $riskProfile): array {
    // Get current allocation
    // Get ideal allocation
    // Calculate difference
    // Recommend which to buy/sell
}
```

**What to do**:
1. Get current allocation
2. Get ideal allocation for profile
3. Calculate difference for each type
4. Identify overweight positions (sell)
5. Identify underweight positions (buy)
6. Return recommendations
7. Test recommendations

#### Step 4: Add Allocation Comparison
```php
/**
 * Compare current vs ideal allocation
 */
public function compareAllocations(int $userId, string $riskProfile): array {
    // Return comparison showing
    // current %, ideal %, difference %
}
```

**What to do**:
1. Get current allocation
2. Get ideal allocation
3. For each type: calculate difference
4. Format for display
5. Return comparison
6. Test comparison

#### Step 5: Update Controller
Update `InvestmentController.php`:
```php
public function getRebalancingAdvice(array $params = []): void {
    // Get risk profile from params
    // Call service method
    // Return JSON
}

public function getAllocationComparison(array $params = []): void {
    // Get allocation data
    // Return JSON
}
```

**What to do**:
1. Add methods to controller
2. Get userId from session
3. Get riskProfile from request
4. Call service methods
5. Return JSON response
6. Test endpoints

### Success Criteria
- [ ] Current allocation calculated correctly
- [ ] Ideal allocations defined per risk
- [ ] Rebalancing advice accurate
- [ ] Comparison data complete
- [ ] Controller endpoints work
- [ ] Math is precise

### Time: 2-3 hours
### Complexity: Medium-High
### Database: Uses investments table

---

## Support & Getting Help

### Ask Claude When:
- Implementation is unclear
- You get an Edit error
- You're not sure how to query database
- You need to understand existing code
- Performance seems slow

### Common Database Queries
```php
// Get single record
$user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);

// Get multiple records
$items = $this->db->query("SELECT * FROM items WHERE user_id = ?", [$userId]);

// Insert
$id = $this->db->insert('table', ['col1' => 'val1', 'col2' => 'val2']);

// Update
$this->db->update('table', ['col' => 'new'], ['id' => 1]);

// Delete
$this->db->delete('table', ['id' => 1]);
```

### Common Patterns
```php
// Check ownership
$item = $this->db->queryOne("SELECT * FROM items WHERE id = ? AND user_id = ?", [$id, $userId]);
if (!$item) {
    $this->json(['error' => 'Not found'], 404);
    return;
}

// Validate input
if (empty($value)) {
    $this->json(['error' => 'Required field'], 400);
    return;
}

// Return success
$this->json(['success' => true, 'data' => $data]);
```

---

## Submission Process

When you complete a task:

1. **Test thoroughly**: Make sure it works
2. **Tell Claude**: Task name and status
3. **Show what you did**: Brief description
4. **Mention any issues**: Problems you faced
5. **Ask to review**: Submit for approval

Claude will:
- Review code quality
- Test functionality
- Check for bugs
- Verify database queries
- Approve or provide feedback

---

## Getting Started

**Recommended Order**:
1. K-6.2: Goal Progress Tracking (simpler, builds confidence)
2. K-6.3: Savings Calculator (similar patterns)
3. K-8.3: API Documentation (low complexity)
4. K-7.2: Data Management (medium complexity)
5. K-8.2: API Authentication (security focus)
6. K-7.3: Security Settings (2FA complexity)
7. K-5.3: Asset Allocation (most complex)

**First Steps**:
1. Read this file completely
2. Review the workflow section carefully
3. Read Quick Start guide for Kilo
4. Ask Claude any questions
5. Start with K-6.2

---

**Remember**: Small tasks, manageable pieces, one at a time.

**You've got this! Let's build! 🚀**
