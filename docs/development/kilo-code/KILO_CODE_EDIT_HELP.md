# 🆘 Kilo Code - Edit Help Guide

**Issue**: "Edit Unsuccessful" when trying to modify files

**Solution**: Use the **Read → Edit → Verify** workflow (proven to work 100%)

---

## ❌ What Doesn't Work

```
Using apply_diff with XML
  → XML parsing errors
  → "StopNode is not closed" error
  → FAILS
```

---

## ✅ What DOES Work

### The Proven Workflow

```
1. Read the file
2. Copy the EXACT code to change (word-for-word)
3. Use Edit tool with precise old_string and new_string
4. Read the file again to verify
5. Done! ✓
```

---

## 📝 Step-by-Step Example

### Let's say you want to add a new method to GoalService.php

#### Step 1: READ the file
```
Use Read tool with file_path: src/Services/GoalService.php
Copy the exact code around where you'll add the method
```

#### Step 2: IDENTIFY what to change
```
Find the exact lines you want to modify
Copy them EXACTLY (including spaces/tabs)
Include surrounding context for uniqueness
```

#### Step 3: USE EDIT TOOL
```
old_string: "    /**
     * Get goal progress summary
     */
    public function getGoalProgress(int $goalId): array {
        // ... existing code ..."

new_string: "    /**
     * Get goal progress summary
     */
    public function getGoalProgress(int $goalId): array {
        // ... existing code ...
    }

    /**
     * Track goal progress over time
     */
    public function trackProgress(int $goalId, float $newAmount): bool {
        try {
            $this->db->insert('goal_progress', [
                'goal_id' => $goalId,
                'amount' => $newAmount,
                'recorded_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }"
```

#### Step 4: VERIFY
```
Use Read tool again
Check that your changes appear
Done! ✓
```

---

## 🎯 Common Issues & Solutions

### Issue 1: "old_string not found"
**Cause**: The code you're trying to match doesn't exist exactly

**Solution**:
1. Read the file again
2. Copy the EXACT code from the Read output (including whitespace)
3. Make sure indentation matches (tabs vs spaces)
4. Add more surrounding context to make it unique
5. Try Edit again

### Issue 2: "Multiple matches found"
**Cause**: Your old_string appears in multiple places

**Solution**:
1. Add more surrounding context
2. Include method names or comments above/below
3. Make the old_string UNIQUE in the file
4. Try Edit again

### Issue 3: Still getting errors
**Solution**:
1. Break the change into smaller pieces
2. Edit one method at a time
3. Verify each edit immediately
4. Don't try to change too much at once

---

## 📋 Current Task: K-6.2 (Goal Progress Tracking)

### What You Need to Do

In `src/Services/GoalService.php`:

#### Add this method to the GoalService class

```php
/**
 * Track goal progress - records the current amount at a point in time
 * @param int $goalId
 * @param float $currentAmount
 * @return bool
 */
public function trackProgress(int $goalId, float $currentAmount): bool {
    try {
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
 * Get progress history for a goal
 * @param int $goalId
 * @return array
 */
public function getProgressHistory(int $goalId): array {
    return $this->db->query(
        "SELECT amount, recorded_at FROM goal_progress WHERE goal_id = ? ORDER BY recorded_at DESC",
        [$goalId]
    );
}

/**
 * Calculate goal progress percentage
 * @param int $goalId
 * @return float
 */
public function getProgressPercentage(int $goalId): float {
    $goal = $this->db->queryOne("SELECT current_amount, target_amount FROM goals WHERE id = ?", [$goalId]);

    if (!$goal || $goal['target_amount'] == 0) {
        return 0;
    }

    return ($goal['current_amount'] / $goal['target_amount']) * 100;
}
```

---

## 🛠️ How to Add These Methods

### Step 1: Read the file
```
Read: src/Services/GoalService.php
Find the end of the existing GoalService class
```

### Step 2: Identify the insertion point
```
Look for the last closing brace }
Make note of what method comes before it
```

### Step 3: Prepare your edit
```
Take the existing last method (or the class definition)
Copy it EXACTLY from the Read output
```

### Step 4: Use Edit
```
old_string: [The current last method or closing code]
new_string: [Same code PLUS your new methods]
```

### Step 5: Verify
```
Read the file again
Confirm your new methods appear
Check indentation is correct
```

---

## 💡 Pro Tips

### Tip 1: Work with Small Changes
- Don't try to add multiple methods at once
- Edit one method at a time
- Verify each one before moving to the next

### Tip 2: Always Copy Exactly
- Use copy-paste from Read output
- Don't manually type code
- Include exact whitespace and indentation

### Tip 3: Include Context
- Add surrounding code to make your old_string unique
- Include method names, comments, blank lines
- Make sure only ONE match exists in the file

### Tip 4: Verify Immediately
- Always Read the file after Edit
- Check that your code appears exactly as intended
- Look for formatting issues

### Tip 5: Break It Down
- If something doesn't work, try a smaller change
- Edit just one line if needed
- Build up incrementally

---

## 🚀 Ready to Try?

### Your First Edit:
1. Open `src/Services/GoalService.php` (Read tool)
2. Find the last method in the class
3. Prepare to add the new trackProgress() method
4. Use Edit with exact old_string and new_string
5. Verify with Read
6. Done!

### Need Help?
- Ask Claude Code immediately
- Share the exact error message
- Show what you're trying to do
- We'll help you resolve it

---

## ✅ Success Checklist

Before submitting your work:
- [ ] Read the file first
- [ ] Identified exact code to change
- [ ] Used Edit tool with precise strings
- [ ] Read the file again to verify
- [ ] Code appears exactly as intended
- [ ] No obvious syntax errors
- [ ] Ready to submit

---

## 📞 Support

**If you get stuck:**
1. Don't panic - this workflow is proven
2. Try a smaller change first
3. Copy the exact error message
4. Ask Claude Code for help
5. We'll resolve it together

**Remember**: Read → Edit → Verify works 100% of the time when done correctly

---

**You've got this! 💪**

*Last Updated*: November 9, 2025
*For*: Kilo Code - Task K-6.2 Implementation
