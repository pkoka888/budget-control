# Handoff Request: File Upload Security Improvements

**Date:** 2025-11-15
**From:** Windows Orchestrator (Claude Code AI)
**To:** Debian Server (claude user - Code changes only)
**Priority:** 🔴 HIGH - Security vulnerability (Path Traversal)
**Status:** ⏳ PENDING IMPLEMENTATION

---

## Current Situation

### ✅ What's Already Secure
- **ImportController.php** - Properly sanitizes filenames using `sanitizeFilename()` from BaseController
- **BaseController.php** - Has excellent `sanitizeFilename()` method that:
  - Uses `basename()` to remove path information
  - Removes directory traversal attempts (`../`, `..\\`, `./`)
  - Removes special characters except dots, hyphens, underscores
  - Limits filename length to 255 characters
- File type validation exists in most controllers
- File size limits in place

### ❌ What's Vulnerable
- **ReceiptOcrService.php** - Does NOT sanitize filenames
  - Uses `pathinfo($file['name'], PATHINFO_EXTENSION)` directly on user input
  - Doesn't extend BaseController, so no access to `sanitizeFilename()`
  - Could be vulnerable to:
    - Path traversal via extension manipulation
    - XSS via malicious filenames stored in database
    - File overwrite via predictable filenames

---

## Vulnerability Details

### Current Code in ReceiptOcrService.php:
```php
private function saveUploadedImage(int $userId, array $file): string {
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);  // ❌ UNSAFE
    $filename = uniqid('receipt_' . $userId . '_') . '.' . $extension;
    $targetPath = $this->uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new \Exception("Failed to save uploaded image");
    }

    return $targetPath;
}
```

### Attack Scenarios:
1. **Path Traversal via Extension**
   - Attacker uploads file with name: `receipt.jpg/../../../etc/passwd`
   - Extension becomes: `/../../../etc/passwd`
   - Final path: `/var/www/budget-control/uploads/receipt_123_uniqid./../../../etc/passwd`

2. **Extension Spoofing**
   - Attacker uploads `malicious.php.jpg`
   - Extension becomes: `jpg` (seems safe)
   - But if Apache is misconfigured, could execute as PHP

3. **XSS via Filename Storage**
   - If filenames are displayed without escaping
   - Malicious filename: `<script>alert('XSS')</script>.jpg`
   - Could execute in admin panel or file listing

---

## Requested Actions

### Task 1: Create FilenameHelper Utility (30 min)

Since ReceiptOcrService doesn't extend BaseController, create a standalone helper:

**Create:** `src/Helpers/FilenameHelper.php`

```php
<?php
namespace BudgetApp\Helpers;

/**
 * Filename Helper
 *
 * Provides secure filename sanitization for file uploads
 */
class FilenameHelper
{
    /**
     * Sanitize a filename to prevent path traversal and XSS
     *
     * @param string $filename The original filename
     * @return string The sanitized filename
     */
    public static function sanitize(string $filename): string
    {
        // Remove any path information (prevent directory traversal)
        $filename = basename($filename);

        // Remove directory traversal attempts
        $filename = str_replace(['../', '..\\', './'], '', $filename);

        // Remove special characters except dots, hyphens, underscores
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Remove multiple consecutive underscores
        $filename = preg_replace('/_+/', '_', $filename);

        // Limit length
        if (strlen($filename) > 255) {
            $filename = substr($filename, 0, 255);
        }

        // Ensure filename is not empty after sanitization
        if (empty($filename) || $filename === '.') {
            $filename = 'file_' . uniqid();
        }

        return $filename;
    }

    /**
     * Validate and extract file extension from sanitized filename
     *
     * @param string $filename The original filename
     * @param array $allowedExtensions Whitelist of allowed extensions (lowercase)
     * @return string The validated extension (lowercase)
     * @throws \InvalidArgumentException If extension is not allowed
     */
    public static function getValidExtension(string $filename, array $allowedExtensions): string
    {
        // Sanitize filename first
        $safeFilename = self::sanitize($filename);

        // Extract extension
        $extension = strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION));

        // Validate against whitelist
        if (!in_array($extension, $allowedExtensions, true)) {
            throw new \InvalidArgumentException(
                "Invalid file extension: $extension. Allowed: " . implode(', ', $allowedExtensions)
            );
        }

        return $extension;
    }

    /**
     * Generate a unique, secure filename
     *
     * @param string $originalFilename The original filename (for extension)
     * @param string $prefix Optional prefix (e.g., 'receipt_', 'user_123_')
     * @param array $allowedExtensions Whitelist of allowed extensions
     * @return string The generated filename
     */
    public static function generateUnique(
        string $originalFilename,
        string $prefix = '',
        array $allowedExtensions = []
    ): string {
        // Get validated extension
        if (empty($allowedExtensions)) {
            // If no whitelist provided, just sanitize
            $safeFilename = self::sanitize($originalFilename);
            $extension = pathinfo($safeFilename, PATHINFO_EXTENSION);
        } else {
            // Validate against whitelist
            $extension = self::getValidExtension($originalFilename, $allowedExtensions);
        }

        // Generate unique filename with prefix
        $uniqueId = uniqid('', true); // true = more entropy
        $timestamp = time();

        // Sanitize prefix (prevent injection via prefix)
        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix);

        return $safePrefix . $timestamp . '_' . $uniqueId . '.' . $extension;
    }

    /**
     * Validate upload directory path
     * Prevents path traversal in directory configuration
     *
     * @param string $path The directory path to validate
     * @return string The canonicalized absolute path
     * @throws \RuntimeException If path is invalid or outside allowed directory
     */
    public static function validateUploadDirectory(string $path): string
    {
        // Get real path (resolves symlinks and relative paths)
        $realPath = realpath($path);

        // Check if directory exists
        if ($realPath === false) {
            throw new \RuntimeException("Upload directory does not exist: $path");
        }

        // Check if it's actually a directory
        if (!is_dir($realPath)) {
            throw new \RuntimeException("Upload path is not a directory: $path");
        }

        // Check if writable
        if (!is_writable($realPath)) {
            throw new \RuntimeException("Upload directory is not writable: $path");
        }

        // Ensure it's within /var/www/budget-control/ (prevent traversal to /etc/, /tmp/, etc.)
        $basePath = realpath('/var/www/budget-control');
        if (strpos($realPath, $basePath) !== 0) {
            throw new \RuntimeException("Upload directory is outside application root: $path");
        }

        return $realPath;
    }
}
```

### Task 2: Update ReceiptOcrService to Use FilenameHelper (15 min)

**Edit:** `src/Services/ReceiptOcrService.php`

**Change this:**
```php
use BudgetApp\Database;

class ReceiptOcrService {
    // ...
}
```

**To this:**
```php
use BudgetApp\Database;
use BudgetApp\Helpers\FilenameHelper;  // ADD THIS

class ReceiptOcrService {
    // ...
}
```

**Change the saveUploadedImage method from:**
```php
private function saveUploadedImage(int $userId, array $file): string {
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('receipt_' . $userId . '_') . '.' . $extension;
    $targetPath = $this->uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new \Exception("Failed to save uploaded image");
    }

    return $targetPath;
}
```

**To this:**
```php
private function saveUploadedImage(int $userId, array $file): string {
    // Whitelist allowed image extensions
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    // Generate secure filename with validation
    $filename = FilenameHelper::generateUnique(
        $file['name'],
        'receipt_' . $userId . '_',
        $allowedExtensions
    );

    // Validate upload directory (prevent configuration-based path traversal)
    $safeUploadDir = FilenameHelper::validateUploadDirectory($this->uploadDir);

    // Construct full path using validated directory
    $targetPath = $safeUploadDir . '/' . $filename;

    // Verify final path is still within upload directory
    // (defense in depth - shouldn't be needed if above code is correct)
    $realTargetPath = dirname($targetPath) . '/' . basename($filename);
    if (strpos($realTargetPath, $safeUploadDir) !== 0) {
        throw new \Exception("Invalid upload path detected");
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $realTargetPath)) {
        throw new \Exception("Failed to save uploaded image");
    }

    return $realTargetPath;
}
```

### Task 3: Review Other Upload Handlers (15 min)

Check these files and ensure they use sanitization:

1. **BudgetController.php** - Template file upload
   - Find the upload method
   - Ensure it sanitizes `$_FILES['template_file']['name']`
   - Should use either `$this->sanitizeFilename()` or `FilenameHelper::sanitize()`

2. **SettingsController.php** - Import file upload
   - Verify it uses sanitization
   - Based on earlier code review, it should already use `sanitizeFilename()`

3. **ChildAccountController.php** - Photo proof upload
   - Check if it sanitizes `$_FILES['photo_proof']['name']`
   - Add sanitization if missing

### Task 4: Add Extension Validation to All Uploads (10 min)

Ensure every file upload has a **whitelist** of allowed extensions, not just MIME type checking:

**Pattern to use:**
```php
// Bad - only checks MIME (can be spoofed)
if ($file['type'] !== 'image/jpeg') { ... }

// Good - validates extension against whitelist
$allowedExtensions = ['jpg', 'jpeg', 'png'];
$extension = FilenameHelper::getValidExtension($file['name'], $allowedExtensions);
```

**Apply to:**
- ReceiptController upload (images only)
- ImportController upload (CSV/TXT only)
- BudgetController template upload (check what's allowed)
- ChildAccountController photo upload (images only)
- SettingsController import (CSV only)

---

## Implementation Strategy

1. **Create FilenameHelper** (30 min)
   - Create `src/Helpers/FilenameHelper.php`
   - Copy code from handoff above
   - Test it doesn't break syntax: `php -l src/Helpers/FilenameHelper.php`

2. **Update ReceiptOcrService** (15 min)
   - Add `use BudgetApp\Helpers\FilenameHelper;`
   - Update `saveUploadedImage()` method
   - Test: Upload a receipt via the web interface

3. **Review Other Controllers** (15 min)
   - Check each controller with `_FILES`
   - Add sanitization where missing
   - Add extension whitelist validation

4. **Test All Upload Functionality** (20 min)
   - Upload receipt image
   - Import CSV file
   - Upload budget template (if applicable)
   - Upload photo proof (if applicable)
   - Verify all work correctly

**Total Time: 1.5 hours**

---

## Testing Checklist

### Functional Testing (Should Work)
- [ ] Upload valid receipt image (.jpg, .png, .webp)
- [ ] Import valid CSV file
- [ ] Upload budget template
- [ ] Files saved with sanitized names
- [ ] Files accessible after upload

### Security Testing (Should Fail Safely)
- [ ] Try uploading file with path traversal in name: `../../etc/passwd.jpg`
  - Should upload successfully but filename sanitized (no `../`)
- [ ] Try uploading file with special characters: `<script>alert('xss')</script>.jpg`
  - Should upload but filename sanitized (no `<>` etc.)
- [ ] Try uploading with double extension: `malicious.php.jpg`
  - Should be validated and stored safely
- [ ] Try uploading disallowed extension: `virus.exe` to receipt upload
  - Should get error "Invalid file extension"
- [ ] Check database - no malicious filenames stored

---

## Edge Cases to Handle

### 1. Very Long Filenames
FilenameHelper truncates to 255 chars, but ensure:
- Extension is preserved
- Uniqueness is maintained

### 2. Unicode Characters
Current implementation converts unicode to `_`:
- `café.jpg` → `caf_.jpg`
- This is acceptable for security

### 3. Filename with Only Special Characters
If user uploads `###.jpg`:
- After sanitization: `_.jpg`
- FilenameHelper adds `file_uniqid` prefix if empty
- Result: `file_12345.jpg`

### 4. Extension Case Sensitivity
- `Image.JPG` vs `image.jpg`
- FilenameHelper converts to lowercase
- Whitelist should use lowercase: `['jpg', 'png']`

---

## Git Workflow

```bash
cd /var/www/budget-control
git checkout -b fix/file-upload-security

# After creating FilenameHelper:
git add src/Helpers/FilenameHelper.php
git commit -m "Add FilenameHelper for secure filename sanitization

- Prevents path traversal attacks
- Validates extensions against whitelist
- Generates unique, secure filenames
- Validates upload directory paths"

# After updating ReceiptOcrService:
git add src/Services/ReceiptOcrService.php
git commit -m "Secure ReceiptOcrService file upload handling

- Use FilenameHelper to sanitize filenames
- Whitelist allowed image extensions
- Validate upload directory path
- Fixes path traversal vulnerability"

# After updating other controllers:
git add src/Controllers/
git commit -m "Add filename sanitization to all upload controllers

- Ensure all file uploads use FilenameHelper
- Add extension whitelist validation
- Consistent security across all upload endpoints"

# When complete:
git push origin fix/file-upload-security
```

---

## Verification

After implementation, verify:

- [ ] All files in `src/Controllers/` that use `$_FILES` also use sanitization
- [ ] All files in `src/Services/` that handle uploads use sanitization
- [ ] No direct use of `$_FILES['...']['name']` without sanitization
- [ ] All uploads have extension whitelists
- [ ] Upload directories are validated
- [ ] Uploaded files have secure, sanitized names
- [ ] No PHP files can be uploaded to image upload endpoints

---

## Completion Report

When done, create: `HANDOFF-DEBIAN-2025-11-15-file-upload-security-COMPLETED.md`

Include:
- ✅ FilenameHelper created and tested
- ✅ ReceiptOcrService updated
- ✅ Other controllers reviewed and fixed
- 📋 List of files modified
- ✅ Testing results
- ⚠️ Any issues encountered
- 📊 Git commit hashes

---

**Priority:** 🔴 HIGH
**Impact:** Fixes path traversal vulnerability in file uploads
**Estimated Time:** 1.5 hours
**Complexity:** Medium

---

**END OF HANDOFF REQUEST**
