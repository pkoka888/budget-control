<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Currency Settings</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Manage your currency preferences and exchange rates</p>
        </div>
        <div class="flex space-x-3">
            <a href="/currency/converter" class="btn-secondary">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                Currency Converter
            </a>
            <a href="/currency/trends" class="btn-secondary">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
                Exchange Trends
            </a>
        </div>
    </div>

    <!-- Currency Preferences Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Currency Preferences</h2>

        <form id="currency-preferences-form" class="space-y-6">
            <!-- Base Currency -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Base Currency
                </label>
                <select id="base-currency" name="base_currency" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                    <?php foreach ($currencies as $currency): ?>
                        <option value="<?php echo $currency['code']; ?>" <?php echo $currency['code'] === $preferences['base_currency'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($currency['code']); ?> - <?php echo htmlspecialchars($currency['name']); ?> (<?php echo htmlspecialchars($currency['symbol']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your primary currency for reports and calculations</p>
            </div>

            <!-- Display Currencies -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Display Currencies
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <?php
                    $displayCurrencies = json_decode($preferences['display_currencies'] ?? '[]', true);
                    foreach ($currencies as $currency):
                        if (in_array($currency['code'], ['EUR', 'USD', 'GBP', 'CHF', 'JPY', 'CZK'])):
                    ?>
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="display_currencies[]" value="<?php echo $currency['code']; ?>"
                                   <?php echo in_array($currency['code'], $displayCurrencies) ? 'checked' : ''; ?>
                                   class="rounded text-purple-600 focus:ring-purple-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <?php echo htmlspecialchars($currency['code']); ?>
                            </span>
                        </label>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Show these currencies in quick converters</p>
            </div>

            <!-- Auto Convert -->
            <div>
                <label class="flex items-center space-x-3">
                    <input type="checkbox" name="auto_convert" id="auto-convert"
                           <?php echo $preferences['auto_convert'] ? 'checked' : ''; ?>
                           class="rounded text-purple-600 focus:ring-purple-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Automatically convert foreign currency transactions
                    </span>
                </label>
                <p class="mt-1 ml-6 text-sm text-gray-500 dark:text-gray-400">
                    When enabled, transactions in foreign currencies will be automatically converted to your base currency
                </p>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>

    <!-- Current Exchange Rates -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Current Exchange Rates</h2>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Base: <?php echo htmlspecialchars($preferences['base_currency']); ?>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($rates as $currency => $rate): ?>
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1"><?php echo htmlspecialchars($currency); ?></div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        <?php echo number_format($rate, 4); ?>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        1 <?php echo htmlspecialchars($preferences['base_currency']); ?> = <?php echo number_format($rate, 4); ?> <?php echo htmlspecialchars($currency); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($rates)): ?>
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                No exchange rates available. Select display currencies above.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Currency preferences form handler
document.getElementById('currency-preferences-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    const baseCurrency = formData.get('base_currency');
    const displayCurrencies = formData.getAll('display_currencies[]');
    const autoConvert = formData.get('auto_convert') ? 1 : 0;

    try {
        const response = await fetch('/currency/update-preferences', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                base_currency: baseCurrency,
                display_currencies: displayCurrencies,
                auto_convert: autoConvert
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Currency preferences updated successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Error updating preferences: ' + error.message);
    }
});
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}

.btn-secondary {
    @apply px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition;
}
</style>
