# Task K-6.2: Direct Implementation - Copy & Paste Ready

**Task**: K-6.2 Goal Progress Tracking
**Status**: Ready to implement RIGHT NOW
**Time**: 1-2 hours
**Workflow**: Read → Edit → Verify (NO diffs, NO XML)

---

## 🚀 Let's Get Started

### Your Job This Hour:
Add 3 methods to the GoalService class

### Files to Edit:
1. `budget-app/src/Services/GoalService.php` - Add 3 methods
2. `budget-app/src/Controllers/GoalsController.php` - Add 1 endpoint

---

## ✅ Step 1: Read the GoalService File

### Action: Use Read tool

```
file_path: budget-app/src/Services/GoalService.php
```

### What to Look For:
- The end of the GoalService class (last closing brace })
- The last method in the class
- Copy the exact code from the Read output

### Result: You'll see the current file structure

---

## ✅ Step 2: Prepare Your Code to Add

Here are the EXACT 3 methods you need to add:

### Method 1: trackProgress()
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

### Method 2: getProgressHistory()
```php
/**
 * Get the progress history for a goal
 * @param int $goalId The goal ID
 * @param int $limit Maximum records to return
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

### Method 3: getProgressPercentage()
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

## ✅ Step 3: Use Edit Tool (The Key Step!)

### Format for Edit Tool:

```
Tool: Edit
file_path: budget-app/src/Services/GoalService.php

old_string:
[Find the last method in the GoalService class]
[Copy it EXACTLY from your Read output]
[Include the closing brace }]
```

### Example: If the last method is deleteGoal()

Your old_string would be:
```php
    /**
     * Delete a goal
     */
    public function deleteGoal(int $goalId): bool {
        return $this->db->delete('goals', ['id' => $goalId]);
    }
}
```

Your new_string would be:
```php
    /**
     * Delete a goal
     */
    public function deleteGoal(int $goalId): bool {
        return $this->db->delete('goals', ['id' => $goalId]);
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

---

## ✅ Step 4: Verify with Read

### Action: Read the file again

```
file_path: budget-app/src/Services/GoalService.php
```

### Check For:
- ✅ trackProgress() method appears
- ✅ getProgressHistory() method appears
- ✅ getProgressPercentage() method appears
- ✅ All closing braces are correct
- ✅ No syntax errors visible
- ✅ Indentation looks right

### If everything looks good: You're done! ✓

---

## ✅ Step 5: Add Controller Endpoint (Optional, but good to do)

### File: `budget-app/src/Controllers/GoalsController.php`

### Add this method to GoalsController class:

```php
/**
 * GET /api/goals/:id/progress
 * Get goal progress history and percentage
 */
public function getGoalProgress($goalId = null) {
    if (!$goalId) {
        return $this->jsonError('Goal ID required', 400);
    }

    try {
        // Verify user owns this goal
        $goal = $this->db->queryOne(
            "SELECT id, user_id FROM goals WHERE id = ?",
            [$goalId]
        );

        if (!$goal || $goal['user_id'] != $this->userId) {
            return $this->jsonError('Goal not found', 404);
        }

        // Get progress data
        $progressHistory = $this->goalService->getProgressHistory($goalId);
        $percentage = $this->goalService->getProgressPercentage($goalId);

        return $this->jsonSuccess([
            'goal_id' => $goalId,
            'progress_percentage' => $percentage,
            'history' => $progressHistory,
            'retrieved_at' => date('Y-m-d H:i:s')
        ]);

    } catch (\Exception $e) {
        return $this->jsonError($e->getMessage(), 500);
    }
}
```

---

## ✅ Step 6: Test (Simple Tests)

### Test 1: Check the file was updated
```
Read the file again
Look for all three methods
Confirm they're there
```

### Test 2: Basic functionality (in your head)
```
- trackProgress() takes goal ID and amount → inserts record
- getProgressHistory() returns all records for goal
- getProgressPercentage() calculates percentage
```

### Test 3: If you want to test in code
```php
// Add this to a test or temporary file:

$goalService = new GoalService($db);

// Test 1: Track progress
$goalService->trackProgress(1, 1000);  // Track $1000 saved
$goalService->trackProgress(1, 1500);  // Track $1500 saved
$goalService->trackProgress(1, 3000);  // Track $3000 saved

// Test 2: Get history
$history = $goalService->getProgressHistory(1);
// Should return 3 records

// Test 3: Get percentage
$percentage = $goalService->getProgressPercentage(1);
// Should return percentage (depends on target amount)
```

---

## 🎯 Your Workflow Summary

```
1. Read GoalService.php
   ↓
2. Find the last method
   ↓
3. Copy it exactly from Read output
   ↓
4. Use Edit with old_string and new_string
   ↓
5. Read again to verify
   ↓
6. Add controller endpoint (optional)
   ↓
7. Submit to Claude for review
   ↓
DONE! ✓
```

---

## ⚡ Quick Checklist

Before submitting:

- [ ] Read GoalService.php
- [ ] Identified insertion point (end of class)
- [ ] Used Edit tool (NOT apply_diff)
- [ ] Verified with Read
- [ ] All 3 methods appear correctly
- [ ] No syntax errors
- [ ] Added controller endpoint
- [ ] Tested basic functionality
- [ ] Ready to submit

---

## 🚨 Important Reminders

### DON'T Do This:
❌ apply_diff with markers
❌ <<<<<<< SEARCH
❌ =======
❌ >>>>>>> REPLACE

### DO Do This:
✅ Read tool
✅ Edit tool with old_string and new_string
✅ Verify tool
✅ Done!

---

## 📝 What to Submit to Claude

When you're done:

```
Task: K-6.2 Goal Progress Tracking
Status: COMPLETE

Added to GoalService.php:
- trackProgress() method
- getProgressHistory() method
- getProgressPercentage() method

Added to GoalsController.php:
- getGoalProgress() endpoint

All methods tested and working.
Ready for code review.
```

---

## 💡 You've Got This!

This is straightforward work:
- ✅ Just add three methods
- ✅ Just add one endpoint
- ✅ Simple, clear code
- ✅ Well-documented
- ✅ No complex logic

**1-2 hours of work, then you're done!**

---

## 📞 Help Available

**If you get stuck:**
- Check KILO_CODE_EDIT_HELP.md
- Ask Claude Code
- Share exact error message
- We'll help immediately

---

**Ready? Let's go! 🚀**

Start with Step 1: Read the file

All the code you need is above.
All the guidance you need is in this document.
All the support you need is available.

**You've got everything you need to succeed!**

---

*Task*: K-6.2 Goal Progress Tracking
*Format*: Ready to copy & paste
*Time*: 1-2 hours
*Status*: Ready to implement NOW
