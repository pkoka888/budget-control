
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Investment Portfolio</h1>
                <div class="flex items-center space-x-4">
                    <a href="/investments" class="text-blue-600 hover:underline">← Back to Investments</a>
                    <button onclick="refreshPortfolio()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        🔄 Refresh
                    </button>
                </div>
            </div>

            <!-- Portfolio Summary -->
            <div class="portfolio-summary grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Total Value</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2">
                                <?php echo number_format($portfolio['total_value'], 0, ',', ' '); ?> Kč
                            </p>
                        </div>
                        <div class="text-4xl text-blue-100">💰</div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Total Cost</p>
                            <p class="text-3xl font-bold text-gray-600 mt-2">
                                <?php echo number_format($portfolio['total_cost'], 0, ',', ' '); ?> Kč
                            </p>
                        </div>
                        <div class="text-4xl text-gray-100">📊</div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Total Gain/Loss</p>
                            <p class="text-3xl font-bold <?php echo $portfolio['total_gain'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> mt-2">
                                <?php echo $portfolio['total_gain'] >= 0 ? '+' : ''; ?><?php echo number_format($portfolio['total_gain'], 0, ',', ' '); ?> Kč
                            </p>
                        </div>
                        <div class="text-4xl <?php echo $portfolio['total_gain'] >= 0 ? 'text-green-100' : 'text-red-100'; ?>">
                            <?php echo $portfolio['total_gain'] >= 0 ? '📈' : '📉'; ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Return %</p>
                            <p class="text-3xl font-bold <?php echo $portfolio['total_return_pct'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> mt-2">
                                <?php echo $portfolio['total_return_pct'] >= 0 ? '+' : ''; ?><?php echo number_format($portfolio['total_return_pct'], 1, ',', ' '); ?>%
                            </p>
                        </div>
                        <div class="text-4xl <?php echo $portfolio['total_return_pct'] >= 0 ? 'text-green-100' : 'text-red-100'; ?>">
                            <?php echo $portfolio['total_return_pct'] >= 0 ? '🚀' : '⚠️'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Highlights -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Top Performers</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded">
                            <div>
                                <p class="font-medium text-green-900">Best Performer</p>
                                <p class="text-sm text-green-700"><?php echo htmlspecialchars($portfolio['best_performer']['symbol'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600">+<?php echo number_format($portfolio['best_performer']['gain_pct'] ?? 0, 1, ',', ' '); ?>%</p>
                                <p class="text-sm text-green-600">+<?php echo number_format($portfolio['best_performer']['gain'] ?? 0, 0, ',', ' '); ?> Kč</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-red-50 rounded">
                            <div>
                                <p class="font-medium text-red-900">Worst Performer</p>
                                <p class="text-sm text-red-700"><?php echo htmlspecialchars($portfolio['worst_performer']['symbol'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-red-600"><?php echo number_format($portfolio['worst_performer']['gain_pct'] ?? 0, 1, ',', ' '); ?>%</p>
                                <p class="text-sm text-red-600"><?php echo number_format($portfolio['worst_performer']['gain'] ?? 0, 0, ',', ' '); ?> Kč</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Asset Allocation</h3>
                    <canvas id="assetAllocationChart" width="300" height="200"></canvas>
                    <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                        <?php foreach ($portfolio['asset_allocation'] as $asset): ?>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: <?php echo htmlspecialchars($asset['color']); ?>"></div>
                                <span><?php echo htmlspecialchars($asset['type']); ?>: <?php echo number_format($asset['percentage'], 1, ',', ' '); ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Account Allocation -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Account Allocation</h3>
                <div class="space-y-4">
                    <?php foreach ($portfolio['account_allocation'] as $account): ?>
                        <div class="account-section">
                            <div class="flex justify-between items-center mb-2">
                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($account['name']); ?></h4>
                                <span class="text-sm text-gray-600"><?php echo number_format($account['percentage'], 1, ',', ' '); ?>% of portfolio</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                                <div class="bg-blue-600 h-3 rounded-full" style="width: <?php echo $account['percentage']; ?>%"></div>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span>Value: <?php echo number_format($account['value'], 0, ',', ' '); ?> Kč</span>
                                <span class="gain-<?php echo $account['gain'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $account['gain'] >= 0 ? '+' : ''; ?><?php echo number_format($account['gain'], 0, ',', ' '); ?> Kč
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Holdings Table -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Current Holdings</h3>

                <!-- Desktop Table -->
                <div class="table-container">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Symbol</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Name</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Type</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Shares</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Avg Cost</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Current</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Gain/Loss</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($portfolio['holdings'] as $holding): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($holding['symbol']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($holding['name']); ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars(ucfirst($holding['type'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900"><?php echo number_format($holding['shares'], 2, ',', ' '); ?></td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900"><?php echo number_format($holding['avg_cost'], 2, ',', ' '); ?> Kč</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900"><?php echo number_format($holding['current_price'], 2, ',', ' '); ?> Kč</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold gain-<?php echo $holding['gain'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $holding['gain'] >= 0 ? '+' : ''; ?><?php echo number_format($holding['gain'], 0, ',', ' '); ?> Kč
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center font-semibold gain-<?php echo $holding['gain_pct'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $holding['gain_pct'] >= 0 ? '+' : ''; ?><?php echo number_format($holding['gain_pct'], 1, ',', ' '); ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="mobile-table-cards hidden">
                    <?php foreach ($portfolio['holdings'] as $holding): ?>
                        <div class="bg-gray-50 rounded-lg p-4 mb-3 border border-gray-200">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($holding['symbol']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($holding['name']); ?></p>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars(ucfirst($holding['type'])); ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Shares:</span>
                                    <span class="font-medium ml-1"><?php echo number_format($holding['shares'], 2, ',', ' '); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Avg Cost:</span>
                                    <span class="font-medium ml-1"><?php echo number_format($holding['avg_cost'], 2, ',', ' '); ?> Kč</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Current:</span>
                                    <span class="font-medium ml-1"><?php echo number_format($holding['current_price'], 2, ',', ' '); ?> Kč</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Gain/Loss:</span>
                                    <span class="font-medium ml-1 gain-<?php echo $holding['gain'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $holding['gain'] >= 0 ? '+' : ''; ?><?php echo number_format($holding['gain'], 0, ',', ' '); ?> Kč
                                    </span>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-t border-gray-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">Return</span>
                                    <span class="font-bold gain-<?php echo $holding['gain_pct'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $holding['gain_pct'] >= 0 ? '+' : ''; ?><?php echo number_format($holding['gain_pct'], 1, ',', ' '); ?>%
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Transaction History</h3>
                    <div class="flex items-center space-x-2">
                        <select id="transactionFilter" onchange="filterTransactions()" class="px-3 py-1 border border-gray-300 rounded text-sm">
                            <option value="all">All Types</option>
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                            <option value="dividend">Dividend</option>
                        </select>
                        <button onclick="exportTransactions()" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                            📊 Export
                        </button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="transaction-history w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Date</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Type</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Symbol</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Quantity</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Price</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Total</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Fees</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Net Gain</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($portfolio['transactions'] as $transaction): ?>
                                <tr class="border-b hover:bg-gray-50 transaction-row" data-type="<?php echo htmlspecialchars($transaction['type']); ?>">
                                    <td class="px-4 py-3 text-sm text-gray-900"><?php echo date('d.m.Y', strtotime($transaction['date'])); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="px-2 py-1 rounded text-xs font-medium
                                            <?php
                                            switch($transaction['type']) {
                                                case 'buy': echo 'bg-green-100 text-green-800'; break;
                                                case 'sell': echo 'bg-red-100 text-red-800'; break;
                                                case 'dividend': echo 'bg-yellow-100 text-yellow-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo htmlspecialchars(ucfirst($transaction['type'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($transaction['symbol']); ?></td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900"><?php echo number_format($transaction['quantity'], 2, ',', ' '); ?></td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900"><?php echo number_format($transaction['price'], 2, ',', ' '); ?> Kč</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900"><?php echo number_format($transaction['total'], 0, ',', ' '); ?> Kč</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900"><?php echo number_format($transaction['fees'], 0, ',', ' '); ?> Kč</td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold gain-<?php echo ($transaction['net_gain'] ?? 0) >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php $netGain = $transaction['net_gain'] ?? 0; ?>
                                        <?php echo $netGain >= 0 ? '+' : ''; ?><?php echo number_format($netGain, 0, ',', ' '); ?> Kč
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Asset Allocation Chart
const assetCtx = document.getElementById('assetAllocationChart')?.getContext('2d');
if (assetCtx) {
    new Chart(assetCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($portfolio['asset_allocation'], 'type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($portfolio['asset_allocation'], 'value')); ?>,
                backgroundColor: <?php echo json_encode(array_column($portfolio['asset_allocation'], 'color')); ?>,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ': ' + percentage + '%';
                        }
                    }
                }
            }
        }
    });
}

function refreshPortfolio() {
    // TODO: Implement portfolio refresh
    alert('Refreshing portfolio data...');
    location.reload();
}

function filterTransactions() {
    const filter = document.getElementById('transactionFilter').value;
    const rows = document.querySelectorAll('.transaction-row');

    rows.forEach(row => {
        if (filter === 'all' || row.dataset.type === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function exportTransactions() {
    // TODO: Implement export functionality
    alert('Export functionality coming soon!');
}
</script>
