# Task K-6.2: Goal Progress Tracking - Implementation Guide

**Task**: K-6.2 Goal Progress Tracking Enhancement
**Time**: 1-2 hours
**Complexity**: Medium
**Status**: Ready to implement

---

## 🎯 What This Task Is About

Add the ability to **track goal progress over time** - recording when a user has saved/contributed to a goal so we can show progress history and trends.

### Example
- Goal: Save $5,000 for vacation
- October 1: Added $1,000 (progress recorded)
- October 15: Added $500 (progress recorded)
- November 1: Added $1,500 (progress recorded)
- → Can now show: "Saved $3,000 in 2 weeks" or "On track to complete by June"

---

## 📋 Files You'll Modify

**Primary File**:
- `budget-app/src/Services/GoalService.php` - Add new methods

**Secondary File**:
- `budget-app/src/Controllers/GoalsController.php` - Add endpoint

---

## 🔧 Implementation Steps

### Step 1: Read the Current File

**Action**: Read `src/Services/GoalService.php`

```
Use Read tool to view the entire GoalService.php file
Identify where the class ends (the closing brace })
Note what the last method is
```

**Why**: We need to know where to add our new methods

---

### Step 2: Add Three New Methods to GoalService

You need to add these three methods to the `GoalService` class:

#### Method 1: trackProgress()

```php
/**
 * Record goal progress at a specific point in time
 * @param int $goalId The goal ID
 * @param float $currentAmount The current saved amount
 * @return bool Success status
 */
public function trackProgress(int $goalId, float $currentAmount): bool {
    try {
        // Verify goal exists
        $goal = $this->db->queryOne("SELECT id FROM goals WHERE id = ?", [$goalId]);
        if (!$goal) {
            return false;
        }

        // Insert progress record
        $this->db->insert('goal_progress', [
            'goal_id' => $goalId,
            'amount' => $currentAmount,
            'recorded_at' => date('Y-m-d H:i:s')
        ]);

        return true;
    } catch (\Exception $e) {
        return false;
    }
}
```

#### Method 2: getProgressHistory()

```php
/**
 * Get the progress history for a goal
 * @param int $goalId The goal ID
 * @param int $limit Maximum records to return (optional)
 * @return array Array of progress records
 */
public function getProgressHistory(int $goalId, int $limit = 100): array {
    return $this->db->query(
        "SELECT amount, recorded_at FROM goal_progress
         WHERE goal_id = ?
         ORDER BY recorded_at DESC
         LIMIT ?",
        [$goalId, $limit]
    );
}
```

#### Method 3: getProgressPercentage()

```php
/**
 * Calculate the current progress percentage for a goal
 * @param int $goalId The goal ID
 * @return float Progress as percentage (0-100)
 */
public function getProgressPercentage(int $goalId): float {
    $goal = $this->db->queryOne(
        "SELECT current_amount, target_amount FROM goals WHERE id = ?",
        [$goalId]
    );

    if (!$goal || $goal['target_amount'] <= 0) {
        return 0;
    }

    return ($goal['current_amount'] / $goal['target_amount']) * 100;
}
```

---

### Step 3: How to Add These Methods Using Edit Tool

#### Workflow:
1. **Read** the file to find the end
2. **Find** the last method or closing brace
3. **Copy** the exact code from Read output
4. **Edit** by adding your new methods
5. **Verify** with another Read

---

### Step 4: Add Controller Endpoint

**File**: `src/Controllers/GoalsController.php`

Add this method to the GoalsController class:

```php
/**
 * GET /api/goals/:id/progress
 * Get goal progress history
 */
public function getGoalProgress(int $goalId): array {
    $this->verifyUserOwnsGoal($goalId);

    $progressHistory = $this->goalService->getProgressHistory($goalId);
    $percentage = $this->goalService->getProgressPercentage($goalId);

    return [
        'goal_id' => $goalId,
        'progress_percentage' => $percentage,
        'history' => $progressHistory,
        'recorded_at' => date('Y-m-d H:i:s')
    ];
}
```

---

### Step 5: Test the Implementation

**Test with these scenarios**:

#### Test 1: Track progress
```php
// Create a test goal
$goalId = 1;

// Track multiple progress points
$goalService->trackProgress($goalId, 1000);  // Oct 1
$goalService->trackProgress($goalId, 1500);  // Oct 15
$goalService->trackProgress($goalId, 3000);  // Nov 1

// Verify history was recorded
$history = $goalService->getProgressHistory($goalId);
// Should show 3 records
```

#### Test 2: Check percentage
```php
$percentage = $goalService->getProgressPercentage($goalId);
// If target is 5000 and current is 3000
// Should return: 60.0
```

#### Test 3: API endpoint
```php
// GET /api/goals/1/progress
// Should return:
{
    "goal_id": 1,
    "progress_percentage": 60,
    "history": [
        {"amount": 3000, "recorded_at": "2025-11-01 10:00:00"},
        {"amount": 1500, "recorded_at": "2025-10-15 08:30:00"},
        {"amount": 1000, "recorded_at": "2025-10-01 09:00:00"}
    ],
    "recorded_at": "2025-11-09 12:00:00"
}
```

---

## 🛠️ The Edit Process (Detailed)

### Example: Adding trackProgress() method

#### 1. First, READ the file
```
Read: budget-app/src/Services/GoalService.php
Look for the end of the class
```

The Read output will show the last method, something like:

```php
    /**
     * Delete a goal
     */
    public function deleteGoal(int $goalId): bool {
        // ... code ...
    }
}  // This is the end of the class
```

#### 2. Prepare your Edit

Your **old_string** should be:
```php
    /**
     * Delete a goal
     */
    public function deleteGoal(int $goalId): bool {
        // existing code
    }
}
```

Your **new_string** should be:
```php
    /**
     * Delete a goal
     */
    public function deleteGoal(int $goalId): bool {
        // existing code
    }

    /**
     * Record goal progress at a specific point in time
     */
    public function trackProgress(int $goalId, float $currentAmount): bool {
        try {
            $goal = $this->db->queryOne("SELECT id FROM goals WHERE id = ?", [$goalId]);
            if (!$goal) {
                return false;
            }

            $this->db->insert('goal_progress', [
                'goal_id' => $goalId,
                'amount' => $currentAmount,
                'recorded_at' => date('Y-m-d H:i:s')
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the progress history for a goal
     */
    public function getProgressHistory(int $goalId, int $limit = 100): array {
        return $this->db->query(
            "SELECT amount, recorded_at FROM goal_progress
             WHERE goal_id = ?
             ORDER BY recorded_at DESC
             LIMIT ?",
            [$goalId, $limit]
        );
    }

    /**
     * Calculate the current progress percentage for a goal
     */
    public function getProgressPercentage(int $goalId): float {
        $goal = $this->db->queryOne(
            "SELECT current_amount, target_amount FROM goals WHERE id = ?",
            [$goalId]
        );

        if (!$goal || $goal['target_amount'] <= 0) {
            return 0;
        }

        return ($goal['current_amount'] / $goal['target_amount']) * 100;
    }
}
```

#### 3. Use the Edit Tool

```
Tool: Edit
File: budget-app/src/Services/GoalService.php
old_string: [Copy from step 2 above]
new_string: [Copy from step 2 above]
```

#### 4. Verify with Read
```
Read: budget-app/src/Services/GoalService.php
Check that your three new methods appear
Check indentation is correct
Check no syntax errors
```

---

## ✅ Checklist Before Submitting

- [ ] Read the GoalService.php file
- [ ] Added trackProgress() method
- [ ] Added getProgressHistory() method
- [ ] Added getProgressPercentage() method
- [ ] All methods have proper docblocks
- [ ] Verified changes with Read
- [ ] Added endpoint to GoalsController
- [ ] No syntax errors
- [ ] Ready to submit

---

## 📝 Testing Checklist

- [ ] trackProgress() records data correctly
- [ ] getProgressHistory() returns records in correct order
- [ ] getProgressPercentage() calculates correctly
- [ ] API endpoint returns proper JSON
- [ ] Error handling works (invalid goal ID)

---

## 🔍 Common Issues & Solutions

### Issue: "old_string not found"
**Cause**: Indentation or whitespace doesn't match exactly

**Solution**:
1. Read the file again
2. Copy the EXACT code (use copy-paste, not typing)
3. Include spaces exactly as shown
4. Try Edit again

### Issue: "Multiple matches found"
**Cause**: Your old_string appears more than once

**Solution**:
1. Include more surrounding context
2. Add method names or comments
3. Make the string unique
4. Try Edit again

### Issue: Syntax error after edit
**Cause**: Missing closing brace or semicolon

**Solution**:
1. Read the file
2. Check the closing brace is present
3. Make sure new_string has all braces
4. Verify matching braces

---

## 📞 Need Help?

If you get stuck:

1. **Copy the exact error message**
2. **Show what you're trying to do**
3. **Ask Claude Code immediately**
4. **We'll help you resolve it**

Remember: The Read → Edit → Verify workflow works 100% when done correctly!

---

## 🎉 When Complete

After successfully implementing:

1. ✅ Three new methods in GoalService
2. ✅ One new endpoint in GoalsController
3. ✅ All tests passing
4. ✅ Code verified with Read

**Submit to Claude Code for review!**

---

**Ready? Let's go! 💪**

*Start*: Step 1 - Read the file
*Next*: Step 2 - Add the methods
*Finish*: Step 5 - Test and verify

Questions? Ask Claude Code - support available 24/7!

---

*Task*: K-6.2 Goal Progress Tracking
*Updated*: November 9, 2025
*Status*: Ready to implement
