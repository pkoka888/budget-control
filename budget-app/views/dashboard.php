<?php
// Dashboard View
$title = 'Dashboard - ' . date('F Y', strtotime($currentMonth . '-01'));
?>

<section class="space-y-6" aria-labelledby="dashboard-heading">
    <h2 id="dashboard-heading" class="sr-only">Přehled financí</h2>

    <!-- Key Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" role="region" aria-labelledby="metrics-heading">
        <h3 id="metrics-heading" class="sr-only">Klíčové finanční ukazatele</h3>

        <!-- Total Income -->
        <article class="card" aria-labelledby="income-label">
            <div class="flex items-center justify-between">
                <div>
                    <p id="income-label" class="text-slate-gray-600 text-sm font-medium">Příjmy tento měsíc</p>
                    <p class="text-3xl font-bold text-google-green-600 mt-2" aria-label="Celkové příjmy: <?php echo number_format($month['total_income'], 0, ',', ' '); ?> korun českých">
                        <?php echo number_format($month['total_income'], 0, ',', ' '); ?> Kč
                    </p>
                </div>
                <div class="text-4xl text-google-green-100" aria-hidden="true">💰</div>
            </div>
        </article>

        <!-- Total Expenses -->
        <article class="card" aria-labelledby="expenses-label">
            <div class="flex items-center justify-between">
                <div>
                    <p id="expenses-label" class="text-slate-gray-600 text-sm font-medium">Výdaje tento měsíc</p>
                    <p class="text-3xl font-bold text-google-red-600 mt-2" aria-label="Celkové výdaje: <?php echo number_format($month['total_expenses'], 0, ',', ' '); ?> korun českých">
                        <?php echo number_format($month['total_expenses'], 0, ',', ' '); ?> Kč
                    </p>
                </div>
                <div class="text-4xl text-google-red-100" aria-hidden="true">💸</div>
            </div>
        </article>

        <!-- Net Income -->
        <article class="card" aria-labelledby="net-income-label">
            <div class="flex items-center justify-between">
                <div>
                    <p id="net-income-label" class="text-slate-gray-600 text-sm font-medium">Čistý příjem</p>
                    <p class="text-3xl font-bold <?php echo $month['net_income'] >= 0 ? 'text-google-blue-600' : 'text-google-red-600'; ?> mt-2" aria-label="Čistý příjem: <?php echo number_format(abs($month['net_income']), 0, ',', ' '); ?> korun českých<?php echo $month['net_income'] >= 0 ? ' v plusu' : ' v mínusu'; ?>">
                        <?php echo number_format($month['net_income'], 0, ',', ' '); ?> Kč
                    </p>
                </div>
                <div class="text-4xl text-google-blue-100" aria-hidden="true">📊</div>
            </div>
        </article>

        <!-- Savings Rate -->
        <article class="card" aria-labelledby="savings-label">
            <div class="flex items-center justify-between">
                <div>
                    <p id="savings-label" class="text-slate-gray-600 text-sm font-medium">Míra úspor</p>
                    <p class="text-3xl font-bold text-google-blue-600 mt-2" aria-label="Míra úspor: <?php echo number_format($month['savings_rate'], 1, ',', ' '); ?> procent">
                        <?php echo number_format($month['savings_rate'], 1, ',', ' '); ?>%
                    </p>
                </div>
                <div class="text-4xl text-google-blue-100" aria-hidden="true">🎯</div>
            </div>
        </article>
    </div>

    <!-- Net Worth Card -->
    <div class="card">
        <h3 class="text-lg font-bold text-slate-gray-900 mb-4">Čistá hodnota majetku</h3>
        <div class="grid grid-cols-3 gap-4">
            <div class="p-4 bg-google-blue-50 rounded-lg">
                <p class="text-sm text-slate-gray-600">Aktiva</p>
                <p class="text-2xl font-bold text-google-blue-600 mt-1">
                    <?php echo number_format($netWorth['total_assets'], 0, ',', ' '); ?> Kč
                </p>
            </div>
            <div class="p-4 bg-google-red-50 rounded-lg">
                <p class="text-sm text-slate-gray-600">Závazky</p>
                <p class="text-2xl font-bold text-google-red-600 mt-1">
                    <?php echo number_format($netWorth['total_liabilities'], 0, ',', ' '); ?> Kč
                </p>
            </div>
            <div class="p-4 bg-google-green-50 rounded-lg">
                <p class="text-sm text-slate-gray-600">Čistá hodnota</p>
                <p class="text-2xl font-bold text-google-green-600 mt-1">
                    <?php echo number_format($netWorth['net_worth'], 0, ',', ' '); ?> Kč
                </p>
            </div>
        </div>
    </div>

    <!-- Financial Health Score -->
    <div class="card">
        <h3 class="text-lg font-bold text-slate-gray-900 mb-4">Finanční zdraví</h3>
        <div class="grid grid-cols-2 gap-6">
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-slate-gray-700">Celkový skóre</span>
                    <span class="text-2xl font-bold text-google-blue-600"><?php echo round($healthScore['overall_score']); ?>/100</span>
                </div>
                <div class="w-full bg-slate-gray-200 rounded-full h-4 overflow-hidden">
                    <div class="bg-google-blue-600 h-full" style="width: <?php echo min($healthScore['overall_score'], 100); ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-slate-gray-700">Skóre spořeníl</span>
                    <span class="text-lg font-bold text-google-green-600"><?php echo round($healthScore['savings_score']); ?>/100</span>
                </div>
                <div class="w-full bg-slate-gray-200 rounded-full h-4 overflow-hidden">
                    <div class="bg-google-green-600 h-full" style="width: <?php echo min($healthScore['savings_score'], 100); ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-slate-gray-700">Skóre dluhu</span>
                    <span class="text-lg font-bold text-google-blue-600"><?php echo round($healthScore['debt_score']); ?>/100</span>
                </div>
                <div class="w-full bg-slate-gray-200 rounded-full h-4 overflow-hidden">
                    <div class="bg-google-blue-600 h-full" style="width: <?php echo min($healthScore['debt_score'], 100); ?>%"></div>
                </div>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-medium text-slate-gray-700">Poměr dluhu</span>
                    <span class="text-lg font-bold text-google-yellow-500"><?php echo round($healthScore['debt_ratio']); ?>%</span>
                </div>
                <div class="w-full bg-slate-gray-200 rounded-full h-4 overflow-hidden">
                    <div class="bg-google-yellow-500 h-full" style="width: <?php echo min($healthScore['debt_ratio'], 100); ?>%"></div>
                </div>
            </div>
        </div>

        <?php if (!empty($healthScore['recommendations'])): ?>
            <div class="mt-6 p-4 bg-google-yellow-50 rounded-lg border-l-4 border-google-yellow-500">
                <h4 class="font-bold text-slate-gray-900 mb-2">Doporučení</h4>
                <ul class="text-sm text-slate-gray-700 space-y-1">
                    <?php foreach ($healthScore['recommendations'] as $rec): ?>
                        <li>• <?php echo htmlspecialchars($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6" aria-labelledby="charts-heading">
        <h3 id="charts-heading" class="sr-only">Grafy a vizualizace</h3>

        <!-- Spending by Category - Doughnut Chart -->
        <article class="card bg-white">
            <h4 class="text-lg font-bold text-slate-gray-900 mb-4">Výdaje podle kategorií</h4>
            <div class="relative bg-white">
                <canvas id="categoryChart" style="max-height: 250px; background-color: white;" role="img" aria-label="Koláčový graf zobrazující výdaje podle kategorií"></canvas>
            </div>
            <div class="mt-4 space-y-2 max-h-48 overflow-y-auto" role="list" aria-label="Seznam kategorií výdajů">
                <?php foreach ($topCategories as $cat): ?>
                    <div class="flex items-center justify-between p-2 hover:bg-slate-gray-50 rounded" role="listitem">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: <?php echo htmlspecialchars($cat['color'] ?? '#3b82f6'); ?>" aria-hidden="true"></div>
                            <span class="text-sm font-medium"><?php echo htmlspecialchars($cat['name'] ?? 'Bez kategorie'); ?></span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-slate-gray-900"><?php echo number_format($cat['total'], 0, ',', ' '); ?> Kč</div>
                            <div class="text-xs text-slate-gray-500"><?php echo number_format($cat['percentage'], 1, ',', ' '); ?>%</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <!-- Category Comparison - Bar Chart -->
        <article class="card bg-white">
            <h4 class="text-lg font-bold text-slate-gray-900 mb-4">Porovnání kategorií</h4>
            <div class="relative bg-white">
                <canvas id="categoryBarChart" style="max-height: 250px; background-color: white;" role="img" aria-label="Sloupcový graf porovnávající výdaje mezi kategoriemi"></canvas>
            </div>
            <div class="mt-4 text-center">
                <p class="text-sm text-slate-gray-600">Srovnění výdajů mezi kategoriemi</p>
            </div>
        </article>

        <!-- Net Worth Composition - Pie Chart -->
        <article class="card bg-white">
            <h4 class="text-lg font-bold text-slate-gray-900 mb-4">Složení majetku</h4>
            <div class="relative bg-white">
                <canvas id="netWorthChart" style="max-height: 250px; background-color: white;" role="img" aria-label="Koláčový graf zobrazující složení majetku - aktiva a závazky"></canvas>
            </div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-xs" role="list" aria-label="Přehled majetku">
                <div class="flex items-center space-x-2" role="listitem">
                    <div class="w-3 h-3 rounded-full bg-blue-500" aria-hidden="true"></div>
                    <span>Aktiva: <?php echo number_format($netWorth['total_assets'], 0, ',', ' '); ?> Kč</span>
                </div>
                <div class="flex items-center space-x-2" role="listitem">
                    <div class="w-3 h-3 rounded-full bg-red-500" aria-hidden="true"></div>
                    <span>Závazky: <?php echo number_format($netWorth['total_liabilities'], 0, ',', ' '); ?> Kč</span>
                </div>
            </div>
        </article>
    </section>

    <!-- AI Recommendations -->
    <section class="card">
        <h3 class="text-lg font-bold text-slate-gray-900 mb-4">🤖 AI Doporučení</h3>
        <?php if (empty($recommendations)): ?>
            <?php
            $emptyConfig = [
                'icon' => '🤖',
                'title' => 'Žádná doporučení',
                'message' => 'Začněte sledovat své finance pravidelně a AI vám poskytne užitečná doporučení.',
                'actionText' => 'Přidat transakci',
                'actionUrl' => '/transactions/create',
                'size' => 'default'
            ];
            include __DIR__ . '/components/empty-state.php';
            ?>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($recommendations as $rec): ?>
                    <article class="p-4 bg-google-blue-50 rounded-lg border-l-4 border-google-blue-500 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-bold text-sm text-slate-gray-900"><?php echo htmlspecialchars($rec['title']); ?></h4>
                            <span class="text-xs px-2 py-1 bg-google-blue-200 text-slate-gray-900 rounded-full">
                                <?php echo ucfirst($rec['priority']); ?>
                            </span>
                        </div>
                        <p class="text-xs text-slate-gray-700"><?php echo htmlspecialchars($rec['description']); ?></p>
                        <?php if (isset($rec['savings'])): ?>
                            <div class="mt-2 text-sm font-semibold text-google-green-600">
                                Potenciální úspora: <?php echo number_format($rec['savings'], 0, ',', ' '); ?> Kč
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Spending Trend Chart -->
    <section class="card bg-white">
        <h3 class="text-lg font-bold text-slate-gray-900 mb-4">Trend výdajů (poslední 30 dní)</h3>
        <div class="bg-white relative" style="height: 300px; width: 100%; overflow: hidden;">
            <canvas id="trendChart" style="background-color: white; max-width: 100%;" role="img" aria-label="Čárový graf zobrazující trend příjmů a výdajů za posledních 30 dní"></canvas>
        </div>
    </section>

    <!-- Recent Transactions -->
    <section class="card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-gray-900">Poslední transakce</h3>
            <a href="/transactions" class="text-google-blue-600 hover:text-google-blue-700 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-google-blue-500" aria-label="Zobrazit všechny transakce">Zobrazit všechny →</a>
        </div>
        <div class="table-container">
            <table class="table" role="table" aria-label="Posledních 5 transakcí">
                <thead>
                    <tr role="row">
                        <th class="text-left" role="columnheader" scope="col">Datum</th>
                        <th class="text-left" role="columnheader" scope="col">Popis</th>
                        <th class="text-left" role="columnheader" scope="col">Kategorie</th>
                        <th class="text-right" role="columnheader" scope="col">Částka</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $tx): ?>
                        <tr role="row">
                            <td role="cell"><?php echo date('d.m.Y', strtotime($tx['date'])); ?></td>
                            <td role="cell"><?php echo htmlspecialchars($tx['description']); ?></td>
                            <td role="cell">
                                <?php if ($tx['category_name']): ?>
                                    <span class="inline-block px-2 py-1 rounded text-xs font-medium" style="background-color: <?php echo htmlspecialchars($tx['color'] ?? '#e5e7eb'); ?>33; color: <?php echo htmlspecialchars($tx['color'] ?? '#6b7280'); ?>;" aria-label="Kategorie: <?php echo htmlspecialchars($tx['category_name']); ?>">
                                        <?php echo htmlspecialchars($tx['category_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-gray-400 text-xs" aria-label="Bez kategorie">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right font-medium <?php echo $tx['type'] === 'income' ? 'text-google-green-600' : 'text-google-red-600'; ?>" role="cell" aria-label="Částka: <?php echo number_format($tx['amount'], 0, ',', ' '); ?> korun českých, typ: <?php echo $tx['type'] === 'income' ? 'příjem' : 'výdaj'; ?>">
                                <?php echo ($tx['type'] === 'income' ? '+' : '-') . number_format($tx['amount'], 0, ',', ' '); ?> Kč
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
// Enhanced Chart.js defaults for better mobile experience
Chart.defaults.responsive = true;
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
Chart.defaults.plugins.tooltip.bodyColor = '#ffffff';
Chart.defaults.plugins.tooltip.borderColor = 'rgba(255, 255, 255, 0.1)';
Chart.defaults.plugins.tooltip.borderWidth = 1;

// Category Doughnut Chart
const categoryCtx = document.getElementById('categoryChart')?.getContext('2d');
if (categoryCtx) {
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map(fn($c) => $c['name'] ?? 'Bez kategorie', $topCategories)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_map(fn($c) => $c['total'], $topCategories)); ?>,
                backgroundColor: <?php echo json_encode(array_map(fn($c) => $c['color'] ?? '#3b82f6', $topCategories)); ?>,
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverBorderWidth: 3,
                hoverBorderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ': ' + value.toLocaleString('cs-CZ') + ' Kč (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
}

// Category Bar Chart
const categoryBarCtx = document.getElementById('categoryBarChart')?.getContext('2d');
if (categoryBarCtx) {
    new Chart(categoryBarCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(fn($c) => $c['name'] ?? 'Bez kategorie', $topCategories)); ?>,
            datasets: [{
                label: 'Výdaje (Kč)',
                data: <?php echo json_encode(array_map(fn($c) => $c['total'], $topCategories)); ?>,
                backgroundColor: <?php echo json_encode(array_map(fn($c) => $c['color'] ?? '#3b82f6', $topCategories)); ?>,
                borderWidth: 1,
                borderColor: '#ffffff',
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw.toLocaleString('cs-CZ') + ' Kč';
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) {
                        return value.toLocaleString('cs-CZ');
                    },
                    font: {
                        size: 10,
                        weight: 'bold'
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
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

// Net Worth Pie Chart
const netWorthCtx = document.getElementById('netWorthChart')?.getContext('2d');
if (netWorthCtx) {
    const netWorthData = {
        assets: <?php echo $netWorth['total_assets']; ?>,
        liabilities: <?php echo $netWorth['total_liabilities']; ?>
    };

    new Chart(netWorthCtx, {
        type: 'pie',
        data: {
            labels: ['Aktiva', 'Závazky'],
            datasets: [{
                data: [netWorthData.assets, netWorthData.liabilities],
                backgroundColor: ['#3b82f6', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverBorderWidth: 3,
                hoverBorderColor: '#ffffff'
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
                        usePointStyle: true,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return context.label + ': ' + value.toLocaleString('cs-CZ') + ' Kč (' + percentage + '%)';
                        }
                    }
                },
                datalabels: {
                    formatter: function(value, context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return percentage + '%';
                    },
                    color: '#ffffff',
                    font: {
                        size: 12,
                        weight: 'bold'
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

// Enhanced Trend Chart
const trendCtx = document.getElementById('trendChart')?.getContext('2d');
if (trendCtx) {
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_map(fn($t) => date('d.m.', strtotime($t['day'])), $spendingTrend)); ?>,
            datasets: [{
                label: 'Výdaje',
                data: <?php echo json_encode(array_map(fn($t) => $t['expenses'], $spendingTrend)); ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#ef4444',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }, {
                label: 'Příjmy',
                data: <?php echo json_encode(array_map(fn($t) => $t['income'], $spendingTrend)); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#ffffff',
                    bodyColor: '#ffffff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw.toLocaleString('cs-CZ') + ' Kč';
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
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        }
    });
}

// Mobile chart responsiveness
function updateChartsForMobile() {
    const isMobile = window.innerWidth < 768;

    // Update all charts to be more mobile-friendly
    // Find all canvas elements with chart IDs
    const chartCanvasIds = ['categoryChart', 'categoryBarChart', 'netWorthChart', 'trendChart'];
    chartCanvasIds.forEach(canvasId => {
        const canvas = document.getElementById(canvasId);
        if (canvas) {
            const chart = Chart.getChart(canvas);
            if (chart) {
                if (isMobile) {
                    chart.options.plugins.legend.position = 'bottom';
                    chart.options.plugins.legend.labels.font.size = 10;
                    chart.options.plugins.tooltip.titleFont.size = 12;
                    chart.options.plugins.tooltip.bodyFont.size = 12;
                } else {
                    chart.options.plugins.legend.position = 'top';
                    chart.options.plugins.legend.labels.font.size = 12;
                    chart.options.plugins.tooltip.titleFont.size = 14;
                    chart.options.plugins.tooltip.bodyFont.size = 14;
                }
                chart.update();
            }
        }
    });
}

// Update charts on window resize
window.addEventListener('resize', updateChartsForMobile);

// Initial check
updateChartsForMobile();
</script>
