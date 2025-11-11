const fs = require('fs');
const path = require('path');

const files = [
    "C:\\ClaudeProjects\\budget-control\\budget-app\\views\\settings\\show.php",
    "C:\\ClaudeProjects\\budget-control\\budget-app\\views\\settings\\profile.php",
    "C:\\ClaudeProjects\\budget-control\\budget-app\\views\\settings\\notifications.php",
    "C:\\ClaudeProjects\\budget-control\\budget-app\\views\\settings\\preferences.php",
    "C:\\ClaudeProjects\\budget-control\\budget-app\\views\\settings\\security.php",
];

let fixed = 0;

files.forEach(file => {
    try {
        const content = fs.readFileSync(file, 'utf8');
        if (content.includes('$this->getFlash()')) {
            const updated = content.replace(
                /\$flash\s*=\s*\$this->getFlash\(\);/g,
                '// flash data is available from extract($data)'
            );
            fs.writeFileSync(file, updated, 'utf8');
            console.log('Fixed: ' + path.basename(file));
            fixed++;
        } else {
            console.log('Skipped: ' + path.basename(file) + ' (already fixed or no getFlash call)');
        }
    } catch (error) {
        console.log('Error: ' + path.basename(file) + ' - ' + error.message);
    }
});

console.log('\nComplete! Fixed: ' + fixed + ' files');
