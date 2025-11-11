
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-5xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Analytika</h1>
                <select onchange="changePeriod(this.value)" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="30days" <?php echo $period === '30days' ? 'selected' : ''; ?>>Posledních 30 dní</option>
                    <option value="90days" <?php echo $period === '90days' ? 'selected' : ''; ?>>Posledních 90 dní</option>
                    <option value="12months" <?php echo $period === '12months' ? 'selected' : ''; ?>>Posledních 12 měsíců</option>
                </select>
            </div>

            <!-- Health Score -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">Finanční zdraví</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-blue-600"><?php echo number_format($healthScore['overall_score'], 0); ?></div>
                        <p class="text-gray-600 text-sm">Celkové skóre</p>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">
                            <?php echo isset($healthScore['recommendations']) ? count($healthScore['recommendations']) : 0; ?>
                        </div>
                        <p class="text-gray-600 text-sm">Doporučení</p>
                    </div>
                </div>
            </div>

            <!-- Anomalies -->
            <?php if (!empty($anomalies)): ?>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">Detekované anomálie</h2>
                <div class="space-y-2">
                    <?php foreach ($anomalies as $anomaly): ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                            <p class="font-semibold text-yellow-800"><?php echo htmlspecialchars($anomaly['description']); ?></p>
                            <p class="text-sm text-yellow-700"><?php echo htmlspecialchars($anomaly['detail']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Spending Trend -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-4">Trend výdajů - <?php echo htmlspecialchars($label); ?></h2>
                <canvas id="trendChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
function changePeriod(value) {
    window.location.href = '/reports/analytics?period=' + value;
}

// Trend Chart
const trendCtx = document.getElementById('trendChart')?.getContext('2d');
if (trendCtx) {
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(fn($d) => $d['date'], $trend)); ?>,
            datasets: [{
                label: 'Výdaje',
                data: <?php echo json_encode(array_map(fn($d) => $d['expenses'], $trend)); ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
}
</script>
