<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Investment Portfolio</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Track your investments and portfolio performance</p>
        </div>
        <button onclick="updatePrices()" class="btn-primary">
            🔄 Update Prices
        </button>
    </div>

    <!-- Portfolio Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Value</h3>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                $<?php echo number_format($portfolio['total_value'], 2); ?>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Gain/Loss</h3>
            <div class="text-3xl font-bold <?php echo $portfolio['total_gain_loss'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> mt-2">
                <?php echo $portfolio['total_gain_loss'] >= 0 ? '+' : ''; ?>$<?php echo number_format($portfolio['total_gain_loss'], 2); ?>
            </div>
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                <?php echo number_format($portfolio['total_gain_loss_pct'], 2); ?>%
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Cost</h3>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                $<?php echo number_format($portfolio['total_cost'], 2); ?>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Accounts</h3>
            <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                <?php echo count($portfolio['accounts']); ?>
            </div>
        </div>
    </div>

    <!-- Performance Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">30-Day Performance</h2>
        <canvas id="performance-chart" height="80"></canvas>
    </div>

    <!-- Sector Allocation -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Sector Allocation</h2>
        <canvas id="sector-chart" height="80"></canvas>
    </div>

    <!-- Holdings -->
    <?php foreach ($portfolio['accounts'] as $account): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($account['name']); ?></h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Symbol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Avg Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Current</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Value</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gain/Loss</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($account['holdings'] as $holding): ?>
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($holding['symbol']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    <?php echo number_format($holding['quantity'], 4); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    $<?php echo number_format($holding['average_buy_price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                    $<?php echo number_format($holding['current_price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                    $<?php echo number_format($holding['current_value'], 2); ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-semibold <?php echo $holding['gain_loss'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $holding['gain_loss'] >= 0 ? '+' : ''; ?>$<?php echo number_format($holding['gain_loss'], 2); ?>
                                    <span class="text-xs">(<?php echo number_format($holding['gain_loss_pct'], 2); ?>%)</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
async function updatePrices() {
    try {
        const response = await fetch('/investment/update-prices', { method: 'POST' });
        const result = await response.json();
        if (result.success) {
            alert('Updated ' + result.updated + ' prices');
            location.reload();
        }
    } catch (error) {
        alert('Error updating prices: ' + error.message);
    }
}

// Performance Chart
if (document.getElementById('performance-chart')) {
    const ctx = document.getElementById('performance-chart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($performance['snapshots'], 'snapshot_date')); ?>,
            datasets: [{
                label: 'Portfolio Value',
                data: <?php echo json_encode(array_column($performance['snapshots'], 'total_value')); ?>,
                borderColor: 'rgb(124, 58, 237)',
                tension: 0.4
            }]
        }
    });
}

// Sector Chart
if (document.getElementById('sector-chart')) {
    const ctx = document.getElementById('sector-chart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($sectors, 'sector')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($sectors, 'percentage')); ?>,
                backgroundColor: ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#ef4444']
            }]
        }
    });
}
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}
</style>
