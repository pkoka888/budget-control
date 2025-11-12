<?php
/**
 * Visibility Toggle Component
 * Allows users to mark data as shared with household or keep private
 *
 * Usage in forms:
 * <?php
 * $entity = 'transaction'; // or 'budget', 'goal', 'account'
 * $currentVisibility = 'private'; // or 'shared'
 * $householdId = 123;
 * include 'views/partials/visibility-toggle.php';
 * ?>
 */

$entity = $entity ?? 'item';
$currentVisibility = $currentVisibility ?? 'private';
$householdId = $householdId ?? ($data['household_id'] ?? 0);
$isInHousehold = $householdId > 0;
?>

<?php if ($isInHousehold): ?>
<!-- Visibility Control -->
<div class="visibility-control mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
        🔒 Privacy Settings
    </label>

    <div class="space-y-2">
        <!-- Private Option -->
        <label class="flex items-start p-3 border-2 rounded-lg cursor-pointer transition-all
                      <?= $currentVisibility === 'private' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' ?>">
            <input
                type="radio"
                name="visibility"
                value="private"
                <?= $currentVisibility === 'private' ? 'checked' : '' ?>
                class="mt-1 w-4 h-4 text-blue-600"
            />
            <div class="ml-3 flex-1">
                <div class="font-medium text-gray-900 dark:text-white">
                    🔒 Private (Only Me)
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Only you can see this <?= htmlspecialchars($entity) ?>
                </div>
            </div>
        </label>

        <!-- Shared Option -->
        <label class="flex items-start p-3 border-2 rounded-lg cursor-pointer transition-all
                      <?= $currentVisibility === 'shared' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' ?>">
            <input
                type="radio"
                name="visibility"
                value="shared"
                <?= $currentVisibility === 'shared' ? 'checked' : '' ?>
                class="mt-1 w-4 h-4 text-blue-600"
            />
            <div class="ml-3 flex-1">
                <div class="font-medium text-gray-900 dark:text-white">
                    👨‍👩‍👧 Shared with Household
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    All household members can see this <?= htmlspecialchars($entity) ?>
                </div>
            </div>
        </label>
    </div>

    <!-- Hidden field to ensure household_id is included -->
    <input type="hidden" name="household_id" value="<?= $householdId ?>">

    <!-- Info Box -->
    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg">
        <p class="text-xs text-blue-800 dark:text-blue-200">
            <strong>💡 Tip:</strong> Shared items help your household track finances together. You can change this later in settings.
        </p>
    </div>
</div>

<!-- Simplified Toggle (Alternative - Checkbox Style) -->
<!--
<div class="visibility-control-simple mb-4">
    <label class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-all">
        <input
            type="checkbox"
            name="shared"
            value="1"
            <?= $currentVisibility === 'shared' ? 'checked' : '' ?>
            class="w-5 h-5 text-blue-600 rounded"
        />
        <div class="flex-1">
            <div class="font-medium text-gray-900 dark:text-white">
                👨‍👩‍👧 Share with household
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Let all household members see this <?= htmlspecialchars($entity) ?>
            </div>
        </div>
    </label>
    <input type="hidden" name="household_id" value="<?= $householdId ?>">
</div>
-->

<script>
// Optional: Visual feedback when toggling visibility
document.querySelectorAll('input[name="visibility"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const isShared = this.value === 'shared';
        const message = isShared
            ? 'This will be shared with your household members'
            : 'This will be private (only visible to you)';

        // Show temporary feedback (optional)
        console.log(message);

        // Update form styling
        document.querySelectorAll('input[name="visibility"]').forEach(r => {
            const label = r.closest('label');
            if (r.checked) {
                label.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900');
                label.classList.remove('border-gray-200', 'dark:border-gray-600');
            } else {
                label.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900');
                label.classList.add('border-gray-200', 'dark:border-gray-600');
            }
        });
    });
});
</script>

<?php else: ?>
<!-- User not in household - hide visibility controls -->
<input type="hidden" name="visibility" value="private">
<?php endif; ?>
