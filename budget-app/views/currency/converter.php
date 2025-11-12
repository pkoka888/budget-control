<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Currency Converter</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Convert between currencies using real-time exchange rates</p>
    </div>

    <!-- Converter Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 max-w-2xl mx-auto">
        <div class="space-y-6">
            <!-- From Currency -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    From
                </label>
                <div class="flex space-x-3">
                    <input type="number" id="from-amount" value="100" step="0.01" min="0"
                           class="flex-1 px-4 py-3 text-lg border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <select id="from-currency" class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>" <?php echo $currency['code'] === $preferences['base_currency'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['code']); ?> (<?php echo htmlspecialchars($currency['symbol']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Swap Button -->
            <div class="flex justify-center">
                <button id="swap-currencies" class="p-3 rounded-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                    </svg>
                </button>
            </div>

            <!-- To Currency -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    To
                </label>
                <div class="flex space-x-3">
                    <input type="number" id="to-amount" value="0" readonly
                           class="flex-1 px-4 py-3 text-lg font-bold border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 dark:text-white">
                    <select id="to-currency" class="px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                        <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>" <?php echo $currency['code'] === 'EUR' ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['code']); ?> (<?php echo htmlspecialchars($currency['symbol']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Exchange Rate Info -->
            <div id="rate-info" class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 text-center hidden">
                <div class="text-sm text-blue-800 dark:text-blue-200">
                    Exchange Rate
                </div>
                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100 mt-1">
                    <span id="rate-display">-</span>
                </div>
                <div class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                    <span id="rate-details">-</span>
                </div>
            </div>

            <!-- Quick Amounts -->
            <div>
                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quick Amounts</div>
                <div class="flex flex-wrap gap-2">
                    <button onclick="setAmount(10)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm">10</button>
                    <button onclick="setAmount(50)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm">50</button>
                    <button onclick="setAmount(100)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm">100</button>
                    <button onclick="setAmount(500)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm">500</button>
                    <button onclick="setAmount(1000)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm">1,000</button>
                    <button onclick="setAmount(5000)" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition text-sm">5,000</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Historical Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Exchange Rate History (30 Days)</h2>
        <canvas id="rate-history-chart" height="100"></canvas>
    </div>
</div>

<script>
let conversionTimeout;

// Auto-convert on input
document.getElementById('from-amount').addEventListener('input', function() {
    clearTimeout(conversionTimeout);
    conversionTimeout = setTimeout(convertCurrency, 500);
});

document.getElementById('from-currency').addEventListener('change', convertCurrency);
document.getElementById('to-currency').addEventListener('change', convertCurrency);

// Swap currencies
document.getElementById('swap-currencies').addEventListener('click', function() {
    const fromCurrency = document.getElementById('from-currency');
    const toCurrency = document.getElementById('to-currency');

    const temp = fromCurrency.value;
    fromCurrency.value = toCurrency.value;
    toCurrency.value = temp;

    convertCurrency();
});

// Set quick amount
function setAmount(amount) {
    document.getElementById('from-amount').value = amount;
    convertCurrency();
}

// Convert currency
async function convertCurrency() {
    const amount = parseFloat(document.getElementById('from-amount').value) || 0;
    const from = document.getElementById('from-currency').value;
    const to = document.getElementById('to-currency').value;

    if (from === to) {
        document.getElementById('to-amount').value = amount.toFixed(2);
        document.getElementById('rate-info').classList.add('hidden');
        return;
    }

    try {
        const response = await fetch(`/currency/convert?amount=${amount}&from=${from}&to=${to}`);
        const result = await response.json();

        if (result.converted_amount !== undefined) {
            document.getElementById('to-amount').value = result.converted_amount.toFixed(2);
            document.getElementById('rate-display').textContent = result.rate.toFixed(4);
            document.getElementById('rate-details').textContent = `1 ${from} = ${result.rate.toFixed(4)} ${to}`;
            document.getElementById('rate-info').classList.remove('hidden');

            // Update chart
            loadHistoricalRates(from, to);
        }
    } catch (error) {
        console.error('Conversion error:', error);
    }
}

// Load historical rates for chart
let rateChart;
async function loadHistoricalRates(from, to) {
    try {
        const response = await fetch(`/currency/history?from=${from}&to=${to}&days=30`);
        const result = await response.json();

        if (result.history) {
            const ctx = document.getElementById('rate-history-chart').getContext('2d');

            if (rateChart) {
                rateChart.destroy();
            }

            rateChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: result.history.map(h => h.date),
                    datasets: [{
                        label: `${from} to ${to}`,
                        data: result.history.map(h => h.rate),
                        borderColor: 'rgb(124, 58, 237)',
                        backgroundColor: 'rgba(124, 58, 237, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error loading historical rates:', error);
    }
}

// Initial conversion
convertCurrency();
</script>
