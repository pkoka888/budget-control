<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <?php if ($group['image_url']): ?>
                <img src="<?php echo htmlspecialchars($group['image_url']); ?>" alt="<?php echo htmlspecialchars($group['name']); ?>" class="w-16 h-16 rounded-lg object-cover">
            <?php else: ?>
                <div class="w-16 h-16 rounded-lg bg-gradient-to-br from-purple-500 to-indigo-500 flex items-center justify-center text-white text-3xl font-bold">
                    <?php echo strtoupper(substr($group['name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($group['name']); ?></h1>
                <p class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($group['description']); ?></p>
            </div>
        </div>
        <div class="flex space-x-3">
            <button onclick="showAddExpenseModal()" class="btn-primary">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Expense
            </button>
            <button onclick="showSettleModal()" class="btn-secondary">
                Settle Up
            </button>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Your Balance -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Your Balance</h3>
            <?php
            $userBalance = 0;
            foreach ($balances['details'] as $detail) {
                if ($detail['user_id'] == $current_user_id) {
                    $userBalance = $detail['balance'];
                    break;
                }
            }
            ?>
            <?php if ($userBalance > 0): ?>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                    +<?php echo number_format($userBalance, 2); ?> <?php echo $group['currency'] ?? 'CZK'; ?>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">You are owed</p>
            <?php elseif ($userBalance < 0): ?>
                <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                    <?php echo number_format($userBalance, 2); ?> <?php echo $group['currency'] ?? 'CZK'; ?>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">You owe</p>
            <?php else: ?>
                <div class="text-3xl font-bold text-gray-600 dark:text-gray-400">
                    0.00 <?php echo $group['currency'] ?? 'CZK'; ?>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">All settled up</p>
            <?php endif; ?>
        </div>

        <!-- Total Expenses -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Total Expenses</h3>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                <?php
                $totalExpenses = array_sum(array_column($expenses, 'total_amount'));
                echo number_format($totalExpenses, 2);
                ?> <?php echo $group['currency'] ?? 'CZK'; ?>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1"><?php echo count($expenses); ?> transactions</p>
        </div>

        <!-- Members -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Members</h3>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">
                <?php echo count($members); ?>
            </div>
            <div class="flex -space-x-2 mt-2">
                <?php foreach (array_slice($members, 0, 5) as $member): ?>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-purple-500 to-indigo-500 flex items-center justify-center text-white text-xs font-bold border-2 border-white dark:border-gray-800">
                        <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($members) > 5): ?>
                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 text-xs font-bold border-2 border-white dark:border-gray-800">
                        +<?php echo count($members) - 5; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Settlement Suggestions -->
    <?php if (!empty($balances['settlements'])): ?>
        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">💡 Suggested Settlements</h3>
            <div class="space-y-3">
                <?php foreach ($balances['settlements'] as $settlement): ?>
                    <div class="flex items-center justify-between bg-white dark:bg-blue-800 rounded-lg p-4">
                        <div class="flex items-center space-x-3">
                            <div class="text-gray-900 dark:text-white">
                                <span class="font-semibold"><?php echo htmlspecialchars($settlement['from_name']); ?></span>
                                <span class="text-gray-500 dark:text-gray-400">pays</span>
                                <span class="font-semibold"><?php echo htmlspecialchars($settlement['to_name']); ?></span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($settlement['amount'], 2); ?> <?php echo $group['currency'] ?? 'CZK'; ?>
                            </span>
                            <?php if ($settlement['from_user'] == $current_user_id): ?>
                                <button onclick="recordSettlement(<?php echo $settlement['to_user']; ?>, <?php echo $settlement['amount']; ?>)" class="btn-primary-sm">
                                    Record Payment
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Expenses List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Expenses</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($expenses as $expense): ?>
                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($expense['description']); ?>
                                </h3>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $expense['split_type'] === 'equal' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'; ?>">
                                    <?php echo ucfirst($expense['split_type']); ?>
                                </span>
                            </div>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Paid by <span class="font-medium"><?php echo htmlspecialchars($expense['paid_by_name']); ?></span>
                                on <?php echo date('M j, Y', strtotime($expense['date'])); ?>
                            </div>
                            <?php if ($expense['notes']): ?>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($expense['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($expense['total_amount'], 2); ?> <?php echo $expense['currency']; ?>
                            </div>
                            <?php
                            // Find user's share
                            $userShare = 0;
                            foreach ($expense['splits'] as $split) {
                                if ($split['user_id'] == $current_user_id) {
                                    $userShare = $split['amount'];
                                    break;
                                }
                            }
                            ?>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Your share: <?php echo number_format($userShare, 2); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Split Details -->
                    <details class="mt-4">
                        <summary class="text-sm text-purple-600 dark:text-purple-400 cursor-pointer hover:underline">
                            View split details
                        </summary>
                        <div class="mt-3 space-y-2">
                            <?php foreach ($expense['splits'] as $split): ?>
                                <div class="flex justify-between text-sm p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                    <span><?php echo htmlspecialchars($split['user_name']); ?></span>
                                    <span class="font-medium">
                                        <?php echo number_format($split['amount'], 2); ?>
                                        <?php if ($split['percentage']): ?>
                                            (<?php echo $split['percentage']; ?>%)
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
            <?php endforeach; ?>

            <?php if (empty($expenses)): ?>
                <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                    No expenses yet. Add your first expense above.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div id="add-expense-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Add Expense</h2>
                <button onclick="hideAddExpenseModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="add-expense-form" class="space-y-4">
                <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <input type="text" name="description" required placeholder="e.g., Groceries, Dinner"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Total Amount</label>
                        <input type="number" name="total_amount" required step="0.01" min="0"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date</label>
                        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Split Type</label>
                        <select name="split_type" id="split-type" onchange="updateSplitUI()"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                            <option value="equal">Split Equally</option>
                            <option value="percentage">By Percentage</option>
                            <option value="shares">By Shares</option>
                            <option value="custom">Custom Amounts</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notes (Optional)</label>
                    <textarea name="notes" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>

                <div id="split-details" class="border-t border-gray-200 dark:border-gray-700 pt-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Split Between</h3>
                    <div id="split-members" class="space-y-2">
                        <?php foreach ($members as $member): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <label class="flex items-center space-x-3 flex-1">
                                    <input type="checkbox" name="split_members[]" value="<?php echo $member['user_id']; ?>" checked
                                           class="rounded text-purple-600 focus:ring-purple-500">
                                    <span class="text-gray-900 dark:text-white"><?php echo htmlspecialchars($member['name']); ?></span>
                                </label>
                                <div class="split-input hidden">
                                    <input type="number" name="split_value_<?php echo $member['user_id']; ?>" step="0.01" min="0"
                                           placeholder="0.00"
                                           class="w-24 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="hideAddExpenseModal()" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary">
                        Add Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showAddExpenseModal() {
    document.getElementById('add-expense-modal').classList.remove('hidden');
    document.getElementById('add-expense-modal').classList.add('flex');
}

function hideAddExpenseModal() {
    document.getElementById('add-expense-modal').classList.add('hidden');
    document.getElementById('add-expense-modal').classList.remove('flex');
}

function showSettleModal() {
    // Implementation for settle up modal
    alert('Settlement feature - choose who to pay');
}

function updateSplitUI() {
    const splitType = document.getElementById('split-type').value;
    const splitInputs = document.querySelectorAll('.split-input');

    if (splitType === 'equal') {
        splitInputs.forEach(input => input.classList.add('hidden'));
    } else {
        splitInputs.forEach(input => input.classList.remove('hidden'));
    }
}

function recordSettlement(toUserId, amount) {
    if (confirm(`Record payment of ${amount} CZK?`)) {
        fetch('/expense-split/settle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                group_id: <?php echo $group['id']; ?>,
                to_user_id: toUserId,
                amount: amount
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Settlement recorded!');
                window.location.reload();
            } else {
                alert('Error: ' + result.error);
            }
        });
    }
}

document.getElementById('add-expense-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = {
        group_id: parseInt(formData.get('group_id')),
        description: formData.get('description'),
        total_amount: parseFloat(formData.get('total_amount')),
        date: formData.get('date'),
        split_type: formData.get('split_type'),
        notes: formData.get('notes'),
        splits: []
    };

    // Get split members
    const members = formData.getAll('split_members[]');
    members.forEach(userId => {
        const splitValue = formData.get(`split_value_${userId}`);
        data.splits.push({
            user_id: parseInt(userId),
            value: splitValue ? parseFloat(splitValue) : null
        });
    });

    try {
        const response = await fetch('/expense-split/add-expense', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            window.location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Error adding expense: ' + error.message);
    }
});
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}

.btn-primary-sm {
    @apply px-4 py-1 text-sm bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}

.btn-secondary {
    @apply px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition;
}
</style>
