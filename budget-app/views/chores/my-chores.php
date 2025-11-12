<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Chores - Budget Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <?php
    $chores = $data['chores'] ?? [];
    $householdId = $data['household_id'] ?? 0;
    $totalEarned = $data['total_earned'] ?? 0;
    $pendingRewards = $data['pending_rewards'] ?? 0;
    ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">My Chores</h1>
                <p class="text-gray-600 dark:text-gray-400">Complete chores to earn rewards!</p>
            </div>
            <a href="/child-account/<?= $householdId ?>" class="text-blue-600 hover:text-blue-700">← Back to Dashboard</a>
        </div>

        <!-- Earnings Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                <div class="text-sm opacity-90 mb-1">Total Earned</div>
                <div class="text-4xl font-bold mb-2">💰 <?= number_format($totalEarned, 2) ?> CZK</div>
                <div class="text-sm opacity-90">From completed chores</div>
            </div>
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg shadow-lg p-6 text-white">
                <div class="text-sm opacity-90 mb-1">Pending Rewards</div>
                <div class="text-4xl font-bold mb-2">⏳ <?= number_format($pendingRewards, 2) ?> CZK</div>
                <div class="text-sm opacity-90">Waiting for verification</div>
            </div>
        </div>

        <?php if (!empty($chores)): ?>
        <!-- Chore List -->
        <div class="space-y-4">
            <?php foreach ($chores as $chore): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6
                        <?= $chore['status'] === 'pending_verification' ? 'border-l-4 border-yellow-500' : '' ?>
                        <?= $chore['status'] === 'completed' ? 'border-l-4 border-green-500' : '' ?>">

                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                <?= htmlspecialchars($chore['title']) ?>
                            </h3>
                            <?php if ($chore['is_recurring']): ?>
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded text-xs">
                                🔁 <?= ucfirst($chore['recurrence_pattern']) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            <?= htmlspecialchars($chore['description']) ?>
                        </p>

                        <div class="flex flex-wrap gap-4 text-sm mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">💰</span>
                                <span class="font-bold text-green-600 dark:text-green-400 text-lg">
                                    +<?= number_format($chore['reward_amount'], 2) ?> CZK
                                </span>
                            </div>
                            <?php if ($chore['due_date']): ?>
                            <div class="flex items-center gap-2">
                                <span class="text-gray-600 dark:text-gray-400">Due:</span>
                                <span class="font-medium <?= strtotime($chore['due_date']) < time() ? 'text-red-600' : 'text-gray-900 dark:text-white' ?>">
                                    📅 <?= date('M j, Y', strtotime($chore['due_date'])) ?>
                                    <?php if (strtotime($chore['due_date']) < time()): ?>
                                    <span class="text-red-600">(Overdue!)</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if ($chore['requires_photo']): ?>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded text-xs">
                                    📸 Photo Required
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Status Badge -->
                        <div class="mb-3">
                            <?php if ($chore['status'] === 'assigned'): ?>
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full text-sm font-medium">
                                📋 To Do
                            </span>
                            <?php elseif ($chore['status'] === 'pending_verification'): ?>
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-sm font-medium">
                                ⏳ Waiting for Parent
                            </span>
                            <?php elseif ($chore['status'] === 'completed'): ?>
                            <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full text-sm font-medium">
                                ✅ Verified & Paid
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <?php if ($chore['status'] === 'assigned'): ?>
                    <button onclick="completeChore(<?= $chore['id'] ?>, <?= $chore['requires_photo'] ? 'true' : 'false' ?>)"
                            class="ml-4 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium shadow-lg hover:shadow-xl transition-all">
                        ✓ Mark Complete
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Completion Info -->
                <?php if ($chore['status'] === 'pending_verification'): ?>
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                    <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                        ⏳ Pending Verification
                    </div>
                    <?php if ($chore['completion_notes']): ?>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                        Your notes: "<?= htmlspecialchars($chore['completion_notes']) ?>"
                    </p>
                    <?php endif; ?>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Submitted <?= date('M j, Y \\a\\t g:i A', strtotime($chore['completed_at'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Verification Result -->
                <?php if ($chore['status'] === 'completed'): ?>
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                ✅ Verified by <?= htmlspecialchars($chore['verified_by_name']) ?>
                            </div>
                            <?php if ($chore['quality_rating']): ?>
                            <div class="mb-2">
                                <?php for ($i = 0; $i < $chore['quality_rating']; $i++): ?>⭐<?php endfor; ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($chore['verification_notes']): ?>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                "<?= htmlspecialchars($chore['verification_notes']) ?>"
                            </p>
                            <?php endif; ?>
                            <div class="text-sm font-medium text-green-600 dark:text-green-400">
                                💰 Reward paid: +<?= number_format($chore['reward_amount'], 2) ?> CZK
                            </div>
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
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Chores Assigned</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                You don't have any chores assigned yet. Check back later!
            </p>
            <a href="/child-account/<?= $householdId ?>" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                Back to Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Complete Chore Modal -->
    <div id="completeChoreModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">✓ Complete Chore</h3>

                <form onsubmit="submitCompletion(event)" id="completeForm">
                    <input type="hidden" name="chore_id" id="complete_chore_id">

                    <div class="mb-4" id="photoUploadSection" style="display: none;">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Upload Photo Proof *
                        </label>
                        <input
                            type="file"
                            name="photo"
                            id="photo"
                            accept="image/*"
                            capture="environment"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            📸 Take a photo or upload from gallery
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Notes (optional)
                        </label>
                        <textarea
                            name="notes"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Any details about completing this chore..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            onclick="closeCompleteChoreModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            Submit for Verification
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function completeChore(choreId, requiresPhoto) {
            document.getElementById('complete_chore_id').value = choreId;
            document.getElementById('photoUploadSection').style.display = requiresPhoto ? 'block' : 'none';
            document.getElementById('photo').required = requiresPhoto;
            document.getElementById('completeChoreModal').classList.remove('hidden');
        }

        function closeCompleteChoreModal() {
            document.getElementById('completeChoreModal').classList.add('hidden');
        }

        async function submitCompletion(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch(`/child-account/chore/${formData.get('chore_id')}/complete`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Chore submitted for verification! 🎉', 'success');
                    closeCompleteChoreModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to complete chore', 'error');
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

        // Close modal when clicking outside
        document.getElementById('completeChoreModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCompleteChoreModal();
            }
        });
    </script>
</body>
</html>
