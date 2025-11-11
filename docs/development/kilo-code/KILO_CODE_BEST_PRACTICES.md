# Kilo Code - Best Practices for Reliable File Editing

## Recommended Workflow: Read → Edit → Verify

Instead of using `apply_diff` with XML, use this proven three-step approach:

### Step 1: Read the File
```
Read file path to understand current structure
```

### Step 2: Use Edit Tool with Exact Matching
```
Match EXACT string (including all whitespace, newlines, indentation)
Old String: Copy EXACTLY from the file (including indentation)
New String: Replacement code with same indentation
```

### Step 3: Verify the Change
```
Read the same file range again to confirm the change was applied
```

## Example: Proper Edit Workflow

### ✓ CORRECT APPROACH
```
1. Read file from line 216-266
2. Identify the exact string to replace (including all formatting)
3. Use Edit with precise old_string and new_string
4. Read the same range again to verify
```

### ✗ INCORRECT APPROACH
```
1. Use apply_diff with XML
2. Trust that XML parsing works
3. Get "StopNode is not closed" error
```

## Why Read → Edit → Verify is Better

| Aspect | apply_diff XML | Read → Edit → Verify |
|--------|----------------|----------------------|
| XML Parsing | ✗ Can fail | ✓ No parsing needed |
| Reliability | ✗ Fragile | ✓ Robust |
| Debugging | ✗ Hard | ✓ Easy (verify step) |
| Special Characters | ✗ Escaping issues | ✓ No escaping needed |
| Complex Code | ✗ CDATA problems | ✓ Works perfectly |
| Error Recovery | ✗ Complete failure | ✓ Clear feedback |

## InvestmentService.php - The Fix That Worked

**File**: `budget-app/src/Services/InvestmentService.php`
**Method**: `getTransactions()` (line 216)

This fix was applied successfully using the Read → Edit → Verify approach:

### What Was Changed
- Replaced fragile `str_replace()` + `preg_replace()` pattern
- Created explicit COUNT query that mirrors the main query
- Improved pagination metadata accuracy

### Why It Matters
- Pagination was returning incorrect total_pages when filters were applied
- String manipulation method was error-prone and unmaintainable
- New approach is testable and reliable

## File Editing Best Practices

### 1. Always Include Surrounding Context
When using Edit, include a few lines before and after to ensure unique matching:

```php
// GOOD - includes surrounding context
        $transactions = $this->db->query($query, $params);

        // Get total count
        $countQuery = "SELECT COUNT(*) as total_count...
```

### 2. Preserve Indentation
Match the exact indentation from the Read output:
- If file uses spaces, use spaces
- If file uses tabs, use tabs
- Include exact number of spaces/tabs

### 3. Handle Multiline Strings Carefully
For strings spanning multiple lines:
```php
// Copy EXACTLY as it appears in file, including quotes and newlines
$countQuery = "SELECT COUNT(*) as total_count
               FROM investment_transactions it
               JOIN investments i ON it.investment_id = i.id
               LEFT JOIN investment_accounts ia ON it.account_id = ia.id
               WHERE it.user_id = ?";
```

### 4. Test with Small Changes First
Before making large edits:
1. Start with a small, testable change
2. Verify it works
3. Move to larger changes

## Common Issues and Solutions

### Issue: "File has not been read yet"
**Solution**: Use Read tool first to cache the file

### Issue: "old_string is not unique in the file"
**Solution**: Add more surrounding context to make it unique

### Issue: "Edit failed" with no clear reason
**Solution**:
1. Read the file again
2. Copy the EXACT string again (check for special characters)
3. Verify indentation matches perfectly

### Issue: XML parsing errors (like StopNode)
**Solution**: Don't use apply_diff - use Read → Edit instead

## Controller Enhancement Example

If Kilo Code needs to enhance controllers, here's the proper workflow:

```
1. Read the controller file (e.g., TransactionController.php)
2. Locate the method to enhance
3. Identify the exact code to replace (with full context)
4. Use Edit tool with precise old_string and new_string
5. Read the same range to verify the change
6. Test by reviewing related service calls
```

## Service Enhancement Example

To enhance a service method:

```
1. Read the service file (e.g., InvestmentService.php)
2. Find the method (e.g., getTransactions)
3. Review current implementation
4. Identify improvement points
5. Use Edit with exact matching
6. Verify by reading the method again
7. Check that related methods still work
```

## Recommended File Modification Order

For systematic enhancements:

1. **Services First** - Core business logic
   - Fix bugs and improve methods
   - Ensure all methods work correctly

2. **Controllers Second** - API layer
   - Update to use fixed services
   - Add new endpoints if needed

3. **Views Last** - Presentation layer
   - Update HTML templates
   - Add new UI elements
   - Style with CSS

## Performance Optimization Tips

### Index Usage
The database schema has 40+ indexes. When modifying queries:
- Check if an index exists for the column being filtered
- Use indexes in WHERE clauses
- Avoid calculations on indexed columns

### Query Optimization
- Use pagination for large result sets
- Use LIMIT to control data volume
- Pre-calculate complex values when possible
- Cache frequently accessed data

### Code Reuse
Services already provide:
- `getPortfolioSummary()` - Investment overview
- `getTransactions()` - Paginated transaction list
- `calculateGoalProgress()` - Goal metrics
- `getMonthSummary()` - Financial analysis

Use these instead of duplicating code.

## Summary

**Best Practice**: Use Read → Edit → Verify workflow
**Avoid**: apply_diff with XML (causes parsing errors)
**Result**: Reliable, maintainable code changes

---
**Updated**: November 8, 2025
**Status**: Ready for implementation
