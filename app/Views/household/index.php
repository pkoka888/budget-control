<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Households - Budget Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Households</h1>
                <p class="text-gray-600 dark:text-gray-400">Manage your family financial workspaces</p>
            </div>
            <button onclick="showCreateHouseholdModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                + Create Household
            </button>
        </div>

        <!-- Primary Household -->
        <?php if ($primaryHousehold): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mb-6 border-l-4 border-blue-600">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($primaryHousehold['name']) ?></h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            Primary • <?= ucfirst($primaryHousehold['role']) ?>
                        </span>
                    </div>
                </div>
                <a href="/household/<?= $primaryHousehold['id'] ?>" class="text-blue-600 hover:text-blue-700 font-medium">
                    Manage →
                </a>
            </div>
            <?php if ($primaryHousehold['description']): ?>
            <p class="text-gray-600 dark:text-gray-400 mb-4"><?= htmlspecialchars($primaryHousehold['description']) ?></p>
            <?php endif; ?>
            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                <span class="mr-4">
                    <strong class="text-gray-700 dark:text-gray-300">Currency:</strong> <?= $primaryHousehold['currency'] ?>
                </span>
                <span>
                    <strong class="text-gray-700 dark:text-gray-300">Joined:</strong> <?= date('M j, Y', strtotime($primaryHousehold['joined_at'])) ?>
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Other Households -->
        <?php if (!empty($households) && count($households) > 1): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($households as $household): ?>
                <?php if ($primaryHousehold && $household['id'] === $primaryHousehold['id']) continue; ?>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($household['name']) ?></h3>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                <?= ucfirst($household['role']) ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($household['description']): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2"><?= htmlspecialchars($household['description']) ?></p>
                    <?php endif; ?>
                    <a href="/household/<?= $household['id'] ?>" class="block text-center bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 px-4 py-2 rounded-lg font-medium transition-colors">
                        View Details
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Empty State -->
        <?php if (empty($households)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No households</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a household workspace.</p>
            <div class="mt-6">
                <button onclick="showCreateHouseholdModal()" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    + Create Household
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Create Household Modal -->
    <div id="createHouseholdModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Create Household</h3>
                <form id="createHouseholdForm" onsubmit="createHousehold(event)">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Household Name*</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="e.g., The Smith Family">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Optional description"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                        <select name="currency" class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="CZK">CZK (Czech Koruna)</option>
                            <option value="EUR">EUR (Euro)</option>
                            <option value="USD">USD (US Dollar)</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" onclick="hideCreateHouseholdModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 dark:hover:bg-gray-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showCreateHouseholdModal() {
            document.getElementById('createHouseholdModal').classList.remove('hidden');
        }

        function hideCreateHouseholdModal() {
            document.getElementById('createHouseholdModal').classList.add('hidden');
            document.getElementById('createHouseholdForm').reset();
        }

        async function createHousehold(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('/household/store', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '/household/' + data.household_id;
                } else {
                    alert(data.error || 'Failed to create household');
                }
            } catch (error) {
                alert('An error occurred. Please try again.');
            }
        }
    </script>
</body>
</html>
