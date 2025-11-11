/**
 * Remove old-style layout rendering calls from view files
 * These files have manual ob_start() and $this->app->render('layout') calls
 * that conflict with the new automatic layout wrapping in Application.php
 */

const fs = require('fs');
const path = require('path');

const filesToFix = [
    'budget-app/views/404.php',
    'budget-app/views/investments/portfolio.php',
    'budget-app/views/settings/security.php',
    'budget-app/views/settings/preferences.php',
    'budget-app/views/settings/notifications.php',
    'budget-app/views/settings/profile.php',
    'budget-app/views/settings/show.php',
    'budget-app/views/goals/milestones.php',
    'budget-app/views/goals/show.php',
    'budget-app/views/budgets/list.php',
    'budget-app/views/budgets/alerts.php',
    'budget-app/views/transactions/list.php',
    'budget-app/views/transactions/show.php',
    'budget-app/views/reports/yearly.php',
    'budget-app/views/reports/analytics.php',
    'budget-app/views/reports/net-worth.php',
    'budget-app/views/accounts/show.php',
    'budget-app/views/guides/list.php',
    'budget-app/views/reports/monthly.php',
    'budget-app/views/goals/list.php',
    'budget-app/views/investments/list.php',
    'budget-app/views/categories/list.php',
    'budget-app/views/transactions/create.php',
    'budget-app/views/accounts/create.php',
    'budget-app/views/accounts/list.php'
];

function fixViewFile(filePath) {
    try {
        const fullPath = path.join(process.cwd(), filePath);
        let content = fs.readFileSync(fullPath, 'utf8');
        const originalContent = content;

        // Remove the first line if it contains the old layout rendering pattern
        const lines = content.split('\n');
        let firstLineIndex = 0;

        // Find the first non-empty, non-comment line
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line && !line.startsWith('<?php //')) {
                if (line.includes("$this->app->render('layout'") ||
                    line.includes("$this->render('layout'") ||
                    line.includes('ob_start()')) {
                    // Remove this line
                    lines.splice(i, 1);
                    break;
                }
            }
        }

        content = lines.join('\n');

        // Also remove the closing ob_start() line if it exists
        content = content.replace(/\s*ob_start\(\);\s*\?>?\s*$/, '');

        // Remove any trailing ob_start() calls
        content = content.replace(/\n\s*ob_start\(\);\s*$/, '');

        // Only write if content changed
        if (content !== originalContent) {
            fs.writeFileSync(fullPath, content, 'utf8');
            console.log(`✅ Fixed: ${filePath}`);
            return true;
        } else {
            console.log(`⏭️ Skipped: ${filePath} (no changes needed)`);
            return false;
        }
    } catch (error) {
        console.error(`❌ Error fixing ${filePath}:`, error.message);
        return false;
    }
}

console.log('🔧 Removing old-style layout rendering calls from view files\n');
console.log('========================================================\n');

let fixed = 0;
let skipped = 0;

filesToFix.forEach(file => {
    if (fixViewFile(file)) {
        fixed++;
    } else {
        skipped++;
    }
});

console.log('\n========================================================');
console.log(`\n✅ Complete! Fixed: ${fixed} files\n`);
