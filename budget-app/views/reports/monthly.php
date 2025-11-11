
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-5xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Měsíční zpráva</h1>
                <input type="month" value="<?php echo htmlspecialchars($month); ?>" onchange="changeMonth(this.value)" class="px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Příjmy</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($summary['total_income'], 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Výdaje</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo number_format($summary['total_expenses'], 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Čistý příjem</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($summary['net_income'], 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Míra úspor</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($summary['savings_rate'], 1, ',', ' '); ?>%</p>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-bold mb-4">Výdaje podle kategorií</h2>
                    <canvas id="categoryChart"></canvas>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-bold mb-4">Příjmy</h2>
                    <div class="space-y-2">
                        <?php foreach ($incomeBySource as $source): ?>
                            <div class="flex justify-between">
                                <span><?php echo htmlspecialchars($source['source'] ?? 'Nezadáno'); ?></span>
                                <strong><?php echo number_format($source['total'], 0, ',', ' '); ?> Kč</strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-4">Výdaje podle kategorií</h2>
                <table class="w-full">
                    <thead class="border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Kategorie</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Částka</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">% z výdajů</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expensesByCategory as $category): ?>
                            <tr class="border-b">
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($category['name']); ?></td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900"><?php echo number_format($category['total'], 0, ',', ' '); ?> Kč</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900"><?php echo number_format(($category['total'] / $summary['total_expenses'] * 100), 1, ',', ' '); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function changeMonth(value) {
    window.location.href = '/reports/monthly?month=' + value;
}

// Chart.js
const categoryCtx = document.getElementById('categoryChart')?.getContext('2d');
if (categoryCtx) {
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map(fn($c) => $c['name'], $expensesByCategory)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_map(fn($c) => $c['total'], $expensesByCategory)); ?>,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}
</script>
