<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Budget Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <?php
    $childSettings = $data['child_settings'] ?? null;
    $balance = $data['balance'] ?? 0;
    $myChores = $data['my_chores'] ?? [];
    $pendingRequests = $data['pending_requests'] ?? [];
    $recentTransactions = $data['recent_transactions'] ?? [];
    $householdId = $data['household_id'] ?? 0;
    $nextAllowance = $data['next_allowance'] ?? null;
    $canRequest = $data['can_request'] ?? true;
    ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">My Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400">Track your money, chores, and goals</p>
        </div>

        <!-- Balance Card -->
        <div class="bg-gradient-to-br from-purple-600 to-blue-600 rounded-2xl shadow-2xl p-8 mb-8 text-white">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="text-sm opacity-90 mb-1">My Balance</div>
                    <div class="text-5xl font-bold mb-2"><?= number_format($balance, 2) ?> CZK</div>
                    <?php if ($nextAllowance): ?>
                    <div class="text-sm opacity-90">
                        🗓️ Next allowance: <?= date('M j', strtotime($nextAllowance)) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="text-6xl">💰</div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 gap-3 mt-6">
                <button onclick="openRequestMoneyModal()"
                        class="px-4 py-3 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg font-medium backdrop-blur-sm transition-all">
                    🤚 Request Money
                </button>
                <a href="/child-account/<?= $householdId ?>/transactions"
                   class="px-4 py-3 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg font-medium backdrop-blur-sm transition-all text-center">
                    📊 View History
                </a>
            </div>
        </div>

        <!-- Spending Limits -->
        <?php if ($childSettings): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 My Spending Limits</h2>

            <div class="space-y-4">
                <!-- Daily Limit -->
                <?php if ($childSettings['daily_limit']): ?>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Daily Limit</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            <?= number_format($childSettings['daily_spent'] ?? 0, 2) ?> / <?= number_format($childSettings['daily_limit'], 2) ?> CZK
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <?php
                        $dailyPercent = min(100, ($childSettings['daily_spent'] ?? 0) / $childSettings['daily_limit'] * 100);
                        ?>
                        <div class="h-2 rounded-full <?= $dailyPercent >= 90 ? 'bg-red-500' : ($dailyPercent >= 70 ? 'bg-yellow-500' : 'bg-green-500') ?>"
                             style="width: <?= $dailyPercent ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Weekly Limit -->
                <?php if ($childSettings['weekly_limit']): ?>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Weekly Limit</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            <?= number_format($childSettings['weekly_spent'] ?? 0, 2) ?> / <?= number_format($childSettings['weekly_limit'], 2) ?> CZK
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <?php
                        $weeklyPercent = min(100, ($childSettings['weekly_spent'] ?? 0) / $childSettings['weekly_limit'] * 100);
                        ?>
                        <div class="h-2 rounded-full <?= $weeklyPercent >= 90 ? 'bg-red-500' : ($weeklyPercent >= 70 ? 'bg-yellow-500' : 'bg-green-500') ?>"
                             style="width: <?= $weeklyPercent ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Monthly Limit -->
                <?php if ($childSettings['monthly_limit']): ?>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Monthly Limit</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            <?= number_format($childSettings['monthly_spent'] ?? 0, 2) ?> / <?= number_format($childSettings['monthly_limit'], 2) ?> CZK
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <?php
                        $monthlyPercent = min(100, ($childSettings['monthly_spent'] ?? 0) / $childSettings['monthly_limit'] * 100);
                        ?>
                        <div class="h-2 rounded-full <?= $monthlyPercent >= 90 ? 'bg-red-500' : ($monthlyPercent >= 70 ? 'bg-yellow-500' : 'bg-green-500') ?>"
                             style="width: <?= $monthlyPercent ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($childSettings['approval_threshold']): ?>
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    💡 <strong>Note:</strong> Transactions over <?= number_format($childSettings['approval_threshold'], 2) ?> CZK need parent approval
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- My Chores -->
        <?php if (!empty($myChores)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">⭐ My Chores</h2>
                <a href="/chores/my-chores" class="text-blue-600 hover:text-blue-700 text-sm font-medium">View All →</a>
            </div>

            <div class="space-y-3">
                <?php foreach (array_slice($myChores, 0, 3) as $chore): ?>
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900 dark:text-white mb-1">
                                <?= htmlspecialchars($chore['title']) ?>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <?= htmlspecialchars($chore['description']) ?>
                            </p>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="text-green-600 dark:text-green-400 font-medium">
                                    💰 +<?= number_format($chore['reward_amount'], 2) ?> CZK
                                </span>
                                <?php if ($chore['due_date']): ?>
                                <span class="text-gray-500 dark:text-gray-400">
                                    📅 Due <?= date('M j', strtotime($chore['due_date'])) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($chore['status'] === 'assigned'): ?>
                        <button onclick="completeChore(<?= $chore['id'] ?>)"
                                class="ml-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium">
                            ✓ Complete
                        </button>
                        <?php elseif ($chore['status'] === 'pending_verification'): ?>
                        <span class="ml-4 px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-xs font-medium">
                            Waiting for parent
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pending Money Requests -->
        <?php if (!empty($pendingRequests)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🤚 Pending Requests</h2>

            <div class="space-y-3">
                <?php foreach ($pendingRequests as $request): ?>
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white mb-1">
                                <?= number_format($request['amount'], 2) ?> CZK
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                <?= htmlspecialchars($request['reason']) ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Requested from <?= htmlspecialchars($request['parent_name']) ?> •
                                <?= date('M j, g:i A', strtotime($request['created_at'])) ?>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-xs font-medium">
                            Pending
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Transactions -->
        <?php if (!empty($recentTransactions)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">📊 Recent Activity</h2>
                <a href="/child-account/<?= $householdId ?>/transactions" class="text-blue-600 hover:text-blue-700 text-sm font-medium">View All →</a>
            </div>

            <div class="space-y-3">
                <?php foreach (array_slice($recentTransactions, 0, 5) as $txn): ?>
                <div class="flex justify-between items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="text-2xl">
                            <?= $txn['type'] === 'income' ? '💰' : '🛍️' ?>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($txn['description']) ?>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?= date('M j, Y', strtotime($txn['date'])) ?>
                            </div>
                        </div>
                    </div>
                    <div class="font-medium <?= $txn['type'] === 'income' ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $txn['type'] === 'income' ? '+' : '-' ?><?= number_format($txn['amount'], 2) ?> CZK
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Request Money Modal -->
    <div id="requestMoneyModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">🤚 Request Money</h3>

                <form onsubmit="submitMoneyRequest(event)">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Amount (CZK)
                        </label>
                        <input
                            type="number"
                            name="amount"
                            step="0.01"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="0.00"
                        />
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            What do you need it for?
                        </label>
                        <textarea
                            name="reason"
                            rows="4"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Explain why you need this money..."
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            onclick="closeRequestMoneyModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            Send Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRequestMoneyModal() {
            document.getElementById('requestMoneyModal').classList.remove('hidden');
        }

        function closeRequestMoneyModal() {
            document.getElementById('requestMoneyModal').classList.add('hidden');
        }

        async function submitMoneyRequest(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch('/child-account/<?= $householdId ?>/money-request', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Money request sent! 📤', 'success');
                    closeRequestMoneyModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to send request', 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        async function completeChore(choreId) {
            const notes = prompt('Any notes about completing this chore? (optional)');

            try {
                const response = await fetch(`/child-account/chore/${choreId}/complete`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `notes=${encodeURIComponent(notes || '')}`
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Chore marked complete! Waiting for verification 🎉', 'success');
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
        document.getElementById('requestMoneyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRequestMoneyModal();
            }
        });
    </script>
</body>
</html>
