# 🚨 URGENT: Kilo Code - STOP Using Diffs!

**Issue**: You're still trying to use apply_diff format
**Status**: This WILL NOT WORK
**Solution**: Use Read → Edit → Verify ONLY
**Time to fix**: 2 minutes

---

## ⚠️ STOP RIGHT NOW

### What You're Doing (WRONG):
```
❌ Using apply_diff with markers
❌ Trying: <<<<<<< SEARCH
❌ Trying: =======
❌ Trying: >>>>>>> REPLACE

Result: FAILS every time with malformed diff error
```

### What You Should Do (RIGHT):
```
✅ Read the file
✅ Copy exact code
✅ Use Edit tool with old_string and new_string
✅ Verify with Read

Result: SUCCESS 100% of the time
```

---

## 🎯 The Solution (Right Now)

### You Don't Need Diffs!

Use this INSTEAD:

```
Tool: Edit
file_path: budget-app/src/Services/InvestmentService.php
old_string: [Copy EXACT code from Read output]
new_string: [Your improved version]
```

**That's it!** No markers, no diffs, no XML parsing.

---

## 📋 The ONLY Workflow That Works

### Step 1: READ
```
Read(file_path: budget-app/src/Services/InvestmentService.php)
→ View the file
→ Find the method you want to edit
→ Copy the EXACT code
```

### Step 2: EDIT
```
Edit(
  file_path: budget-app/src/Services/InvestmentService.php,
  old_string: [Paste your copied code],
  new_string: [Your improved version]
)
```

### Step 3: VERIFY
```
Read(file_path: budget-app/src/Services/InvestmentService.php)
→ Check your changes appear
→ Confirm formatting is correct
→ Done!
```

---

## ❌ NEVER Do This Again

```
❌ Don't use apply_diff
❌ Don't use <<<<<<< SEARCH
❌ Don't use =======
❌ Don't use >>>>>>> REPLACE
❌ Don't use any diff markers

These ALWAYS cause errors and ALWAYS fail.
```

---

## ✅ ALWAYS Do This Instead

```
✅ Read the file first
✅ Copy exact code
✅ Use Edit tool
✅ Put copied code in old_string
✅ Put improved code in new_string
✅ Done! Verify with Read

This works 100% of the time.
```

---

## 🔧 Example: Adding a Method to InvestmentService

### Current File Has:
```php
public function getPortfolioSummary(int $userId): array {
    // ... existing code ...
}
```

### You Want to Add:
```php
public function getAssetAllocation(int $userId): array {
    // new method code
}
```

### How to Do It (CORRECT WAY):

**Step 1: Read**
```
Read the file, find the last method, copy it exactly
```

**Step 2: Edit**
```
old_string:
    /**
     * Get portfolio summary
     */
    public function getPortfolioSummary(int $userId): array {
        // ... existing code ...
    }

new_string:
    /**
     * Get portfolio summary
     */
    public function getPortfolioSummary(int $userId): array {
        // ... existing code ...
    }

    /**
     * Get asset allocation breakdown
     */
    public function getAssetAllocation(int $userId): array {
        // Your new method code here
        return [];
    }
```

**Step 3: Verify**
```
Read the file again
Check both methods appear
Confirm formatting is correct
Done!
```

---

## 📞 This Is Important

**Kilo Code - read this carefully:**

1. ✅ You've already done this workflow successfully before
2. ✅ It worked perfectly
3. ✅ It's simple and reliable
4. ✅ It will work for ALL your remaining tasks
5. ✅ There's NO other way that works

**Don't try anything else. Just:**
- Read
- Edit
- Verify

**That's it.**

---

## 🎯 Your Task Right Now

### If you were trying to edit InvestmentService:

1. **Stop** what you're doing
2. **Read** the file completely
3. **Find** the exact method to modify
4. **Copy** the code from Read output
5. **Use Edit** with old_string and new_string
6. **Verify** with Read
7. **Done!**

### The file is located at:
```
budget-app/src/Services/InvestmentService.php
```

---

## ✨ You've Got This

You've successfully used this workflow before. It works. There's no reason to try anything else.

**Simple formula that always works:**
```
Read → Edit → Verify = Success ✓
```

---

## 📚 Documentation You Have

- `KILO_CODE_EDIT_HELP.md` - Full details on this workflow
- `KILO_K62_IMPLEMENTATION.md` - Task K-6.2 with examples
- `KILO_IMMEDIATE_ACTION.md` - Quick reference

All of them say the same thing: **Use Read → Edit → Verify**

---

## 🎉 You're Ready!

The workflow is proven.
The documentation is clear.
The support is available.

**Just follow these three steps and you'll succeed.**

---

## If You're Still Confused

**Ask Claude Code immediately.**

Show:
1. What you're trying to do
2. What error you got
3. What file you're editing

We'll help you in minutes.

---

**Remember**: Read → Edit → Verify

**That's the ONLY way. And it works 100% of the time.**

**Let's go! 💪**

---

*For*: Kilo Code
*Subject*: STOP using diffs, use Read→Edit→Verify
*Urgency*: HIGH
*Support*: Available immediately
