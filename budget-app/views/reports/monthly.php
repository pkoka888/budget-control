<?php
/**
 * Monthly Financial Report View
 * Displays income, expenses, and category breakdowns for a given month
 */

$currentMonth = $month ?? date('Y-m');
$totalIncome = $summary['total_income'] ?? 0;
$totalExpenses = $summary['total_expenses'] ?? 0;
$netIncome = $summary['net_income'] ?? 0;
$savingsRate = $totalIncome > 0 ? ($netIncome / $totalIncome) * 100 : 0;
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-gray-900">Měsíční přehled</h1>
                <p class="mt-2 text-slate-gray-600"><?php echo date('F Y', strtotime($currentMonth . '-01')); ?></p>
            </div>

            <!-- Month Selector -->
            <div class="flex items-center gap-4">
                <input type="month" id="month-selector" value="<?php echo htmlspecialchars($currentMonth); ?>"
                       class="form-input rounded-lg border-slate-gray-300">
                <div class="flex gap-2">
                    <a href="/reports/export/csv/monthly?month=<?php echo urlencode($currentMonth); ?>"
                       class="btn btn-secondary btn-sm">
                        Export CSV
                    </a>
                    <a href="/reports/export/xlsx/monthly?month=<?php echo urlencode($currentMonth); ?>"
                       class="btn btn-secondary btn-sm">
                        Export Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-gray-600">Příjmy</p>
                    <p class="mt-2 text-2xl font-bold text-green-600">
                        <?php echo number_format($totalIncome, 0, ',', ' '); ?> Kč
                    </p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-gray-600">Výdaje</p>
                    <p class="mt-2 text-2xl font-bold text-red-600">
                        <?php echo number_format($totalExpenses, 0, ',', ' '); ?> Kč
                    </p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-gray-600">Čistý příjem</p>
                    <p class="mt-2 text-2xl font-bold <?php echo $netIncome >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo number_format($netIncome, 0, ',', ' '); ?> Kč
                    </p>
                </div>
                <div class="<?php echo $netIncome >= 0 ? 'bg-green-100' : 'bg-red-100'; ?> rounded-full p-3">
                    <svg class="w-6 h-6 <?php echo $netIncome >= 0 ? 'text-green-600' : 'text-red-600'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-gray-600">Míra úspor</p>
                    <p class="mt-2 text-2xl font-bold text-primary-600">
                        <?php echo number_format($savingsRate, 1); ?>%
                    </p>
                </div>
                <div class="bg-primary-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Expenses by Category -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Výdaje podle kategorií</h3>
            <canvas id="expenses-chart" class="max-h-80"></canvas>
        </div>

        <!-- Income by Source -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Příjmy podle zdroje</h3>
            <canvas id="income-chart" class="max-h-80"></canvas>
        </div>
    </div>

    <!-- Category Details Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-gray-200">
            <h3 class="text-lg font-semibold text-slate-gray-900">Detailní rozčlenění výdajů</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-gray-200">
                <thead class="bg-slate-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-gray-500 uppercase tracking-wider">Kategorie</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-gray-500 uppercase tracking-wider">Částka</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-gray-500 uppercase tracking-wider">% z celkových výdajů</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-gray-500 uppercase tracking-wider">Počet transakcí</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-gray-200">
                    <?php foreach ($expensesByCategory as $category):
                        $percentage = $totalExpenses > 0 ? ($category['total'] / $totalExpenses) * 100 : 0;
                    ?>
                    <tr class="hover:bg-slate-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full mr-3" style="background-color: <?php echo htmlspecialchars($category['color'] ?? '#6B7280'); ?>"></div>
                                <span class="text-sm font-medium text-slate-gray-900"><?php echo htmlspecialchars($category['name']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-gray-900">
                            <?php echo number_format($category['total'], 0, ',', ' '); ?> Kč
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-gray-600">
                            <?php echo number_format($percentage, 1); ?>%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-gray-600">
                            <?php echo number_format($category['count'], 0); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-slate-gray-50">
                    <tr>
                        <td class="px-6 py-4 text-sm font-bold text-slate-gray-900">Celkem</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-gray-900 text-right">
                            <?php echo number_format($totalExpenses, 0, ',', ' '); ?> Kč
                        </td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-gray-900 text-right">100%</td>
                        <td class="px-6 py-4 text-sm font-bold text-slate-gray-900 text-right">
                            <?php echo number_format(array_sum(array_column($expensesByCategory, 'count')), 0); ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
// Expenses Chart Data
const expensesData = <?php echo json_encode($expensesByCategory); ?>;
const incomeData = <?php echo json_encode($incomeBySource); ?>;

// Expenses Pie Chart
const expensesCtx = document.getElementById('expenses-chart').getContext('2d');
new Chart(expensesCtx, {
    type: 'doughnut',
    data: {
        labels: expensesData.map(cat => cat.name),
        datasets: [{
            data: expensesData.map(cat => cat.total),
            backgroundColor: expensesData.map(cat => cat.color || '#6B7280'),
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 12,
                    padding: 10
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return context.label + ': ' + value.toLocaleString('cs-CZ') + ' Kč (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Income Pie Chart
const incomeCtx = document.getElementById('income-chart').getContext('2d');
new Chart(incomeCtx, {
    type: 'doughnut',
    data: {
        labels: incomeData.map(cat => cat.name),
        datasets: [{
            data: incomeData.map(cat => cat.total),
            backgroundColor: incomeData.map(cat => cat.color || '#10B981'),
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right',
                labels: {
                    boxWidth: 12,
                    padding: 10
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const value = context.parsed;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return context.label + ': ' + value.toLocaleString('cs-CZ') + ' Kč (' + percentage + '%)';
                    }
                }
            }
        }
    }
});

// Month selector change handler
document.getElementById('month-selector').addEventListener('change', function() {
    window.location.href = '/reports/monthly?month=' + this.value;
});
</script>
