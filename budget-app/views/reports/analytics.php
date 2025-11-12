<?php
/**
 * Financial Analytics Dashboard
 * Advanced insights with trends, anomalies, and health score
 */

$healthScore = $healthScore ?? 50;
$healthColor = $healthScore >= 80 ? 'text-green-600' : ($healthScore >= 60 ? 'text-yellow-600' : 'text-red-600');
$healthBgColor = $healthScore >= 80 ? 'bg-green-100' : ($healthScore >= 60 ? 'bg-yellow-100' : 'bg-red-100');
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-gray-900">Finanční analýza</h1>
                <p class="mt-2 text-slate-gray-600"><?php echo htmlspecialchars($label); ?></p>
            </div>

            <!-- Period Selector -->
            <div class="flex gap-2">
                <a href="/reports/analytics?period=30days"
                   class="btn <?php echo $period === '30days' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                    30 dní
                </a>
                <a href="/reports/analytics?period=90days"
                   class="btn <?php echo $period === '90days' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                    90 dní
                </a>
                <a href="/reports/analytics?period=12months"
                   class="btn <?php echo $period === '12months' ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                    12 měsíců
                </a>
            </div>
        </div>
    </div>

    <!-- Financial Health Score -->
    <div class="bg-white rounded-lg shadow-md p-8 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-gray-900 mb-2">Finanční zdraví</h2>
                <p class="text-slate-gray-600">Celkové skóre vašeho finančního stavu</p>
            </div>
            <div class="text-center">
                <div class="relative inline-flex items-center justify-center w-32 h-32">
                    <svg class="w-32 h-32 transform -rotate-90">
                        <circle cx="64" cy="64" r="56" stroke="#E5E7EB" stroke-width="8" fill="none"></circle>
                        <circle cx="64" cy="64" r="56"
                                stroke="<?php echo $healthScore >= 80 ? '#10B981' : ($healthScore >= 60 ? '#F59E0B' : '#EF4444'); ?>"
                                stroke-width="8" fill="none"
                                stroke-dasharray="<?php echo 2 * 3.14159 * 56; ?>"
                                stroke-dashoffset="<?php echo 2 * 3.14159 * 56 * (1 - $healthScore / 100); ?>"
                                stroke-linecap="round"></circle>
                    </svg>
                    <div class="absolute">
                        <div class="text-4xl font-bold <?php echo $healthColor; ?>">
                            <?php echo round($healthScore); ?>
                        </div>
                        <div class="text-sm text-slate-gray-600">/ 100</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Score Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-8">
            <div class="text-center p-4 bg-slate-gray-50 rounded-lg">
                <div class="text-sm text-slate-gray-600 mb-1">Míra úspor</div>
                <div class="text-2xl font-bold text-slate-gray-900"><?php echo $healthScore['savings_rate'] ?? 'N/A'; ?></div>
            </div>
            <div class="text-center p-4 bg-slate-gray-50 rounded-lg">
                <div class="text-sm text-slate-gray-600 mb-1">Dodržování rozpočtu</div>
                <div class="text-2xl font-bold text-slate-gray-900"><?php echo $healthScore['budget_adherence'] ?? 'N/A'; ?></div>
            </div>
            <div class="text-center p-4 bg-slate-gray-50 rounded-lg">
                <div class="text-sm text-slate-gray-600 mb-1">Stabilita příjmů</div>
                <div class="text-2xl font-bold text-slate-gray-900"><?php echo $healthScore['income_stability'] ?? 'N/A'; ?></div>
            </div>
            <div class="text-center p-4 bg-slate-gray-50 rounded-lg">
                <div class="text-sm text-slate-gray-600 mb-1">Finanční rezerva</div>
                <div class="text-2xl font-bold text-slate-gray-900"><?php echo $healthScore['emergency_fund'] ?? 'N/A'; ?></div>
            </div>
        </div>
    </div>

    <!-- Spending Trend Chart -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Trend výdajů</h3>
        <canvas id="trend-chart" class="max-h-96"></canvas>
    </div>

    <!-- Anomalies Detection -->
    <?php if (!empty($anomalies)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-gray-900">Detekované anomálie</h3>
            <span class="badge badge-warning"><?php echo count($anomalies); ?> nalezeno</span>
        </div>

        <div class="space-y-3">
            <?php foreach ($anomalies as $anomaly): ?>
            <div class="flex items-start p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                <svg class="w-5 h-5 text-yellow-600 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                    <p class="font-medium text-yellow-900"><?php echo htmlspecialchars($anomaly['title']); ?></p>
                    <p class="text-sm text-yellow-700 mt-1"><?php echo htmlspecialchars($anomaly['description']); ?></p>
                    <p class="text-sm text-yellow-600 mt-2">
                        Kategorie: <span class="font-medium"><?php echo htmlspecialchars($anomaly['category'] ?? 'Neznámá'); ?></span> |
                        Částka: <span class="font-medium"><?php echo number_format($anomaly['amount'], 0, ',', ' '); ?> Kč</span>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Insights & Recommendations -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Top Spending Categories -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Největší výdajové kategorie</h3>
            <div class="space-y-4">
                <?php
                $topCategories = array_slice($trend['categories'] ?? [], 0, 5);
                foreach ($topCategories as $category):
                ?>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-slate-gray-900"><?php echo htmlspecialchars($category['name']); ?></span>
                        <span class="text-sm font-bold text-slate-gray-900"><?php echo number_format($category['total'], 0, ',', ' '); ?> Kč</span>
                    </div>
                    <div class="w-full bg-slate-gray-200 rounded-full h-2">
                        <div class="bg-primary-600 h-2 rounded-full" style="width: <?php echo $category['percentage']; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Insights -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Doporučení</h3>
            <div class="space-y-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-slate-gray-900">Úspěšné úspory</p>
                        <p class="text-sm text-slate-gray-600 mt-1">Vaše míra úspor je vyšší než průměr</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-slate-gray-900">Optimalizujte kategorie</p>
                        <p class="text-sm text-slate-gray-600 mt-1">Zvažte snížení výdajů v kategorii "Restaurace"</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <svg class="w-5 h-5 text-purple-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-medium text-slate-gray-900">Potenciální úspora</p>
                        <p class="text-sm text-slate-gray-600 mt-1">Můžete ušetřit ~2 500 Kč měsíčně</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
// Spending Trend Data
const trendData = <?php echo json_encode($trend ?? []); ?>;

const trendCtx = document.getElementById('trend-chart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: trendData.dates || [],
        datasets: [
            {
                label: 'Výdaje',
                data: trendData.expenses || [],
                borderColor: '#EF4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Příjmy',
                data: trendData.income || [],
                borderColor: '#10B981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString('cs-CZ') + ' Kč';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('cs-CZ') + ' Kč';
                    }
                }
            }
        }
    }
});
</script>
