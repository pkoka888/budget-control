<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Bills & Subscriptions</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Manage recurring bills and automate payments</p>
        </div>
        <button onclick="showAddBillModal()" class="btn-primary">
            + Add Bill
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Total</h3>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                <?php echo number_format($analytics['average_monthly'], 2); ?> CZK
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Bills</h3>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                <?php echo count($bills); ?>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Upcoming (30 days)</h3>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                <?php echo count($upcoming); ?>
            </div>
        </div>
    </div>

    <!-- Upcoming Bills -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Upcoming Bills</h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($upcoming as $bill): ?>
                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($bill['name']); ?>
                            </h3>
                            <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Due: <?php echo date('M j, Y', strtotime($bill['due_date'])); ?>
                                <?php if ($bill['auto_pay_enabled']): ?>
                                    <span class="ml-2 px-2 py-1 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">Auto-pay</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($bill['payment_amount'], 2); ?> <?php echo $bill['currency']; ?>
                            </div>
                            <button onclick="markPaid(<?php echo $bill['payment_id']; ?>)" class="mt-2 text-sm text-purple-600 dark:text-purple-400 hover:underline">
                                Mark as Paid
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- All Bills -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">All Bills</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Frequency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Next Due</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($bill['name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($bill['category']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo number_format($bill['amount'], 2); ?> <?php echo $bill['currency']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo ucfirst($bill['frequency']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo date('M j, Y', strtotime($bill['next_due_date'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($bill['auto_pay_enabled']): ?>
                                    <span class="px-2 py-1 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">Auto-pay</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded">Manual</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function markPaid(paymentId) {
    if (!confirm('Mark this bill as paid?')) return;

    try {
        const response = await fetch('/bill/mark-paid', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                payment_id: paymentId,
                paid_date: new Date().toISOString().split('T')[0]
            })
        });
        const result = await response.json();
        if (result.success) {
            alert('Bill marked as paid!');
            location.reload();
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function showAddBillModal() {
    alert('Add bill modal - implement with form');
}
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}
</style>
