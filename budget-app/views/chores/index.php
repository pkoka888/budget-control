<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chore Management - Budget Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <?php
    $chores = $data['chores'] ?? [];
    $children = $data['children'] ?? [];
    $householdId = $data['household_id'] ?? 0;
    $stats = $data['stats'] ?? ['total' => 0, 'pending_verification' => 0, 'completed_this_week' => 0];
    ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Chore Management</h1>
                <p class="text-gray-600 dark:text-gray-400">Assign and track chores for your family</p>
            </div>
            <div class="flex gap-3">
                <a href="/household/<?= $householdId ?>" class="text-blue-600 hover:text-blue-700">← Back</a>
                <button onclick="openCreateChoreModal()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    + Create Chore
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Active Chores</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><?= $stats['total'] ?></div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Pending Verification</div>
                <div class="text-3xl font-bold text-yellow-600"><?= $stats['pending_verification'] ?></div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Completed This Week</div>
                <div class="text-3xl font-bold text-green-600"><?= $stats['completed_this_week'] ?></div>
            </div>
        </div>

        <!-- Chore List -->
        <?php if (!empty($chores)): ?>
        <div class="space-y-4">
            <?php foreach ($chores as $chore): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?= htmlspecialchars($chore['title']) ?>
                            </h3>
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                <?= $chore['status'] === 'assigned' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' ?>
                                <?= $chore['status'] === 'pending_verification' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' ?>
                                <?= $chore['status'] === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' ?>">
                                <?= ucwords(str_replace('_', ' ', $chore['status'])) ?>
                            </span>
                            <?php if ($chore['is_recurring']): ?>
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded text-xs">
                                🔁 <?= ucfirst($chore['recurrence_pattern']) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            <?= htmlspecialchars($chore['description']) ?>
                        </p>

                        <div class="flex flex-wrap gap-4 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600 dark:text-gray-400">Assigned to:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($chore['assigned_to_name']) ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600 dark:text-gray-400">Reward:</span>
                                <span class="font-medium text-green-600 dark:text-green-400">
                                    💰 <?= number_format($chore['reward_amount'], 2) ?> CZK
                                </span>
                            </div>
                            <?php if ($chore['due_date']): ?>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600 dark:text-gray-400">Due:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    📅 <?= date('M j, Y', strtotime($chore['due_date'])) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex gap-2 ml-4">
                        <?php if ($chore['status'] === 'pending_verification'): ?>
                        <button onclick="verifyChore(<?= $chore['completion_id'] ?>, <?= $chore['id'] ?>)"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                            ✓ Verify
                        </button>
                        <?php endif; ?>
                        <button onclick="editChore(<?= $chore['id'] ?>)"
                                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 text-sm">
                            Edit
                        </button>
                        <button onclick="deleteChore(<?= $chore['id'] ?>)"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                            Delete
                        </button>
                    </div>
                </div>

                <!-- Completion Details -->
                <?php if ($chore['status'] === 'pending_verification' && $chore['completion_notes']): ?>
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                    <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">Completion Notes:</div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                        "<?= htmlspecialchars($chore['completion_notes']) ?>"
                    </p>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Completed <?= date('M j, Y \\a\\t g:i A', strtotime($chore['completed_at'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Verification History -->
                <?php if ($chore['status'] === 'completed' && $chore['verification_notes']): ?>
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                ✓ Verified by <?= htmlspecialchars($chore['verified_by_name']) ?>
                            </div>
                            <?php if ($chore['quality_rating']): ?>
                            <div class="mb-2">
                                <?php for ($i = 0; $i < $chore['quality_rating']; $i++): ?>⭐<?php endfor; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($chore['verification_notes']): ?>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                "<?= htmlspecialchars($chore['verification_notes']) ?>"
                            </p>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <?= date('M j, Y', strtotime($chore['verified_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <div class="text-6xl mb-4">⭐</div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Chores Yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Create your first chore to start tracking household tasks and rewards.
            </p>
            <button onclick="openCreateChoreModal()" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                + Create First Chore
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Create/Edit Chore Modal -->
    <div id="choreModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" id="choreModalTitle">Create Chore</h3>

                <form onsubmit="submitChore(event)" id="choreForm">
                    <input type="hidden" name="chore_id" id="chore_id">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Chore Title *
                            </label>
                            <input
                                type="text"
                                name="title"
                                id="title"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="e.g., Clean your room"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Description
                            </label>
                            <textarea
                                name="description"
                                id="description"
                                rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="What needs to be done?"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Assign To *
                            </label>
                            <select
                                name="assigned_to"
                                id="assigned_to"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="">Select child...</option>
                                <?php foreach ($children as $child): ?>
                                <option value="<?= $child['user_id'] ?>">
                                    <?= htmlspecialchars($child['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Reward Amount (CZK) *
                            </label>
                            <input
                                type="number"
                                name="reward_amount"
                                id="reward_amount"
                                step="0.01"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="0.00"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Due Date
                            </label>
                            <input
                                type="date"
                                name="due_date"
                                id="due_date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Recurring
                            </label>
                            <select
                                name="recurrence_pattern"
                                id="recurrence_pattern"
                                onchange="toggleRecurring()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                                <option value="">One-time</option>
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center mb-4">
                        <input
                            type="checkbox"
                            name="requires_photo"
                            id="requires_photo"
                            class="w-4 h-4 text-blue-600 rounded"
                        />
                        <label for="requires_photo" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            📸 Require photo proof when completed
                        </label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            onclick="closeChoreModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            Create Chore
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Verify Chore Modal -->
    <div id="verifyModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">✓ Verify Chore Completion</h3>

                <form onsubmit="submitVerification(event)" id="verifyForm">
                    <input type="hidden" name="completion_id" id="verify_completion_id">
                    <input type="hidden" name="chore_id" id="verify_chore_id">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Quality Rating
                        </label>
                        <div class="flex gap-2">
                            <button type="button" onclick="setRating(1)" class="rating-btn text-2xl">⭐</button>
                            <button type="button" onclick="setRating(2)" class="rating-btn text-2xl">⭐</button>
                            <button type="button" onclick="setRating(3)" class="rating-btn text-2xl">⭐</button>
                            <button type="button" onclick="setRating(4)" class="rating-btn text-2xl">⭐</button>
                            <button type="button" onclick="setRating(5)" class="rating-btn text-2xl">⭐</button>
                        </div>
                        <input type="hidden" name="quality_rating" id="quality_rating">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Notes (optional)
                        </label>
                        <textarea
                            name="notes"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Great job! / Please redo this part..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            onclick="closeVerifyModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            Verify & Pay Reward
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="/js/chores.js"></script>
    <script>
        // Inline scripts for modal functionality
        function openCreateChoreModal() {
            document.getElementById('choreModal').classList.remove('hidden');
            document.getElementById('choreForm').reset();
            document.getElementById('chore_id').value = '';
            document.getElementById('choreModalTitle').textContent = 'Create Chore';
        }

        function closeChoreModal() {
            document.getElementById('choreModal').classList.add('hidden');
        }

        function closeVerifyModal() {
            document.getElementById('verifyModal').classList.add('hidden');
        }

        async function submitChore(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            const url = formData.get('chore_id')
                ? `/chores/${formData.get('chore_id')}/update`
                : '/chores/store';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Chore saved successfully! ⭐', 'success');
                    closeChoreModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to save chore', 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        function verifyChore(completionId, choreId) {
            document.getElementById('verify_completion_id').value = completionId;
            document.getElementById('verify_chore_id').value = choreId;
            document.getElementById('verifyModal').classList.remove('hidden');
        }

        let selectedRating = 0;
        function setRating(rating) {
            selectedRating = rating;
            document.getElementById('quality_rating').value = rating;

            // Visual feedback
            const buttons = document.querySelectorAll('.rating-btn');
            buttons.forEach((btn, idx) => {
                btn.style.opacity = idx < rating ? '1' : '0.3';
            });
        }

        async function submitVerification(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            const completionId = formData.get('completion_id');

            try {
                const response = await fetch(`/chores/completion/${completionId}/verify`, {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Chore verified! Reward paid 💰', 'success');
                    closeVerifyModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to verify chore', 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        async function deleteChore(choreId) {
            if (!confirm('Are you sure you want to delete this chore?')) return;

            try {
                const response = await fetch(`/chores/${choreId}/delete`, {
                    method: 'POST'
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Chore deleted', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to delete chore', 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Close modals when clicking outside
        document.getElementById('choreModal').addEventListener('click', function(e) {
            if (e.target === this) closeChoreModal();
        });
        document.getElementById('verifyModal').addEventListener('click', function(e) {
            if (e.target === this) closeVerifyModal();
        });
    </script>
</body>
</html>
