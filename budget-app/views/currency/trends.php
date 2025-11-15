<?php /** Currency Trends Analysis */ ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Trendy měnových kurzů</h1>
        <p class="text-gray-600">Sledujte vývoj měnových kurzů a plánujte své transakce</p>
    </div>

    <!-- Main Currency Rates -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <?php
            $currencies = [
                ['code' => 'EUR', 'name' => 'Euro', 'rate' => $rates['EUR'] ?? 25.50, 'change' => 0.12],
                ['code' => 'USD', 'name' => 'Americký dolar', 'rate' => $rates['USD'] ?? 23.50, 'change' => -0.08],
                ['code' => 'GBP', 'name' => 'Britská libra', 'rate' => $rates['GBP'] ?? 29.80, 'change' => 0.05]
            ];
        ?>

        <?php foreach ($currencies as $currency): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold"><?php echo $currency['code']; ?></h3>
                        <p class="text-sm text-gray-500"><?php echo $currency['name']; ?></p>
                    </div>
                    <span class="px-2 py-1 <?php echo $currency['change'] >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded text-sm">
                        <?php echo $currency['change'] >= 0 ? '+' : ''; ?><?php echo number_format($currency['change'], 2); ?>%
                    </span>
                </div>
                <p class="text-3xl font-bold"><?php echo number_format($currency['rate'], 2); ?> Kč</p>
                <p class="text-xs text-gray-500 mt-2">Aktualizováno: <?php echo date('d.m.Y H:i'); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Currency Trends Chart -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold">Vývoj kurzů</h2>
            <div class="flex gap-2">
                <button onclick="changePeriod('7d')" class="period-btn px-3 py-1 rounded text-sm active" data-period="7d">7 dní</button>
                <button onclick="changePeriod('30d')" class="period-btn px-3 py-1 rounded text-sm" data-period="30d">30 dní</button>
                <button onclick="changePeriod('90d')" class="period-btn px-3 py-1 rounded text-sm" data-period="90d">90 dní</button>
                <button onclick="changePeriod('1y')" class="period-btn px-3 py-1 rounded text-sm" data-period="1y">1 rok</button>
            </div>
        </div>

        <div class="h-96">
            <canvas id="currencyChart"></canvas>
        </div>

        <!-- Currency Selector -->
        <div class="flex gap-3 mt-4">
            <label class="flex items-center">
                <input type="checkbox" class="currency-toggle mr-2" data-currency="EUR" checked>
                <span class="text-sm">EUR</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" class="currency-toggle mr-2" data-currency="USD" checked>
                <span class="text-sm">USD</span>
            </label>
            <label class="flex items-center">
                <input type="checkbox" class="currency-toggle mr-2" data-currency="GBP" checked>
                <span class="text-sm">GBP</span>
            </label>
        </div>
    </div>

    <!-- Exchange Rate Calculator -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-6">Kalkulačka měn</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Částka</label>
                <input type="number" id="calc-amount" value="1000" step="0.01" class="w-full border rounded px-3 py-2" oninput="calculateExchange()">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Z měny</label>
                <select id="calc-from" class="w-full border rounded px-3 py-2" onchange="calculateExchange()">
                    <option value="CZK" selected>CZK</option>
                    <option value="EUR">EUR</option>
                    <option value="USD">USD</option>
                    <option value="GBP">GBP</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Na měnu</label>
                <select id="calc-to" class="w-full border rounded px-3 py-2" onchange="calculateExchange()">
                    <option value="EUR" selected>EUR</option>
                    <option value="CZK">CZK</option>
                    <option value="USD">USD</option>
                    <option value="GBP">GBP</option>
                </select>
            </div>
        </div>

        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
            <p class="text-sm text-gray-600">Výsledek</p>
            <p id="calc-result" class="text-2xl font-bold text-blue-900">—</p>
        </div>
    </div>

    <!-- Historical Data Table -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Historické kurzy</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-3 px-4">Datum</th>
                        <th class="text-right py-3 px-4">EUR</th>
                        <th class="text-right py-3 px-4">USD</th>
                        <th class="text-right py-3 px-4">GBP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($historical_rates)): ?>
                        <?php foreach ($historical_rates as $rate): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo date('d.m.Y', strtotime($rate['date'])); ?></td>
                                <td class="text-right py-3 px-4"><?php echo number_format($rate['EUR'], 3); ?> Kč</td>
                                <td class="text-right py-3 px-4"><?php echo number_format($rate['USD'], 3); ?> Kč</td>
                                <td class="text-right py-3 px-4"><?php echo number_format($rate['GBP'], 3); ?> Kč</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-8 text-gray-500">Žádná historická data k dispozici</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
        <h3 class="font-semibold text-blue-900 mb-2">O měnových kurzech</h3>
        <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
            <li>Kurzy jsou aktualizovány denně z ČNB</li>
            <li>Data jsou pouze informativní, pro skutečné transakce ověřte kurzy u své banky</li>
            <li>Historické kurzy jsou dostupné až 5 let zpět</li>
        </ul>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const rates = <?php echo json_encode($rates ?? []); ?>;
const chartData = <?php echo json_encode($chart_data ?? []); ?>;

// Initialize chart
let currencyChart;
const ctx = document.getElementById('currencyChart').getContext('2d');

function initChart(period = '7d') {
    if (currencyChart) {
        currencyChart.destroy();
    }

    currencyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData[period]?.labels || [],
            datasets: [
                {
                    label: 'EUR',
                    data: chartData[period]?.EUR || [],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    hidden: !document.querySelector('[data-currency="EUR"]').checked
                },
                {
                    label: 'USD',
                    data: chartData[period]?.USD || [],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    hidden: !document.querySelector('[data-currency="USD"]').checked
                },
                {
                    label: 'GBP',
                    data: chartData[period]?.GBP || [],
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    hidden: !document.querySelector('[data-currency="GBP"]').checked
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y.toFixed(3)} Kč`
                    }
                }
            },
            scales: {
                y: {
                    ticks: {
                        callback: (value) => value.toFixed(2) + ' Kč'
                    }
                }
            }
        }
    });
}

function changePeriod(period) {
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-blue-500', 'text-white');
        btn.classList.add('bg-gray-200');
    });

    const btn = document.querySelector(`[data-period="${period}"]`);
    btn.classList.add('active', 'bg-blue-500', 'text-white');
    btn.classList.remove('bg-gray-200');

    initChart(period);
}

document.querySelectorAll('.currency-toggle').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const currency = this.dataset.currency;
        const datasetIndex = ['EUR', 'USD', 'GBP'].indexOf(currency);
        currencyChart.setDatasetVisibility(datasetIndex, this.checked);
        currencyChart.update();
    });
});

function calculateExchange() {
    const amount = parseFloat(document.getElementById('calc-amount').value) || 0;
    const from = document.getElementById('calc-from').value;
    const to = document.getElementById('calc-to').value;

    let result;
    if (from === 'CZK') {
        result = amount / (rates[to] || 1);
    } else if (to === 'CZK') {
        result = amount * (rates[from] || 1);
    } else {
        const czk = amount * (rates[from] || 1);
        result = czk / (rates[to] || 1);
    }

    document.getElementById('calc-result').textContent =
        new Intl.NumberFormat('cs-CZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(result) + ' ' + to;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initChart('7d');
    calculateExchange();
});
</script>
