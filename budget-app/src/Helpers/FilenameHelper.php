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
