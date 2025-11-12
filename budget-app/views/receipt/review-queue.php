<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Review Queue</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Review receipts that need manual verification</p>
    </div>

    <?php if (!empty($queue)): ?>
        <!-- Queue Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total in Queue</h3>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    <?php echo count($queue); ?>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">High Priority</h3>
                <div class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">
                    <?php echo count(array_filter($queue, fn($item) => $item['priority'] >= 2)); ?>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg. Confidence</h3>
                <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                    <?php
                    $avgConfidence = array_sum(array_column($queue, 'confidence_score')) / count($queue);
                    echo round($avgConfidence * 100);
                    ?>%
                </div>
            </div>
        </div>

        <!-- Queue Items -->
        <div class="space-y-4">
            <?php foreach ($queue as $item): ?>
                <?php
                $parsedData = json_decode($item['parsed_data'] ?? '{}', true);
                $priorityColors = [
                    2 => 'border-red-500 bg-red-50 dark:bg-red-900',
                    1 => 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900',
                    0 => 'border-blue-500 bg-blue-50 dark:bg-blue-900'
                ];
                $borderClass = $priorityColors[$item['priority']] ?? 'border-gray-300 bg-white dark:bg-gray-800';
                ?>
                <div class="border-l-4 <?php echo $borderClass; ?> rounded-lg shadow p-6">
                    <div class="flex flex-col lg:flex-row lg:space-x-6">
                        <!-- Receipt Image -->
                        <div class="lg:w-64 flex-shrink-0 mb-4 lg:mb-0">
                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="Receipt" class="w-full rounded-lg shadow">
                            <div class="mt-2 text-sm text-center">
                                <span class="font-semibold">Confidence: <?php echo round($item['confidence_score'] * 100); ?>%</span>
                            </div>
                        </div>

                        <!-- Edit Form -->
                        <div class="flex-1">
                            <form class="review-form" data-scan-id="<?php echo $item['id']; ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Merchant
                                        </label>
                                        <input type="text" name="merchant" value="<?php echo htmlspecialchars($parsedData['merchant'] ?? ''); ?>"
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Date
                                        </label>
                                        <input type="date" name="date" value="<?php echo $parsedData['date'] ?? ''; ?>"
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Total Amount
                                        </label>
                                        <input type="number" name="total" step="0.01" value="<?php echo $parsedData['total'] ?? ''; ?>"
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Currency
                                        </label>
                                        <select name="currency"
                                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                            <option value="CZK" <?php echo ($parsedData['currency'] ?? '') === 'CZK' ? 'selected' : ''; ?>>CZK</option>
                                            <option value="EUR" <?php echo ($parsedData['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                            <option value="USD" <?php echo ($parsedData['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD</option>
                                            <option value="GBP" <?php echo ($parsedData['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Tax
                                        </label>
                                        <input type="number" name="tax" step="0.01" value="<?php echo $parsedData['tax'] ?? ''; ?>"
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>

                                <!-- Line Items -->
                                <?php if (!empty($parsedData['items'])): ?>
                                    <div class="mt-4">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Line Items</h4>
                                        <div class="space-y-2">
                                            <?php foreach ($parsedData['items'] as $idx => $item): ?>
                                                <div class="grid grid-cols-4 gap-2 items-center">
                                                    <input type="text" name="items[<?php echo $idx; ?>][name]" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>"
                                                           placeholder="Item name"
                                                           class="col-span-2 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                                    <input type="number" name="items[<?php echo $idx; ?>][quantity]" value="<?php echo $item['quantity'] ?? 1; ?>"
                                                           placeholder="Qty"
                                                           class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                                    <input type="number" name="items[<?php echo $idx; ?>][total]" value="<?php echo $item['total'] ?? ''; ?>"
                                                           placeholder="Total" step="0.01"
                                                           class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700 dark:text-white">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- OCR Text -->
                                <details class="mt-4">
                                    <summary class="text-sm text-purple-600 dark:text-purple-400 cursor-pointer hover:underline">
                                        View OCR Text
                                    </summary>
                                    <pre class="mt-2 p-4 bg-gray-100 dark:bg-gray-700 rounded text-xs overflow-x-auto"><?php echo htmlspecialchars($item['ocr_text'] ?? 'No OCR text available'); ?></pre>
                                </details>

                                <!-- Actions -->
                                <div class="flex justify-end space-x-3 mt-6">
                                    <button type="button" onclick="deleteReceipt(<?php echo $item['id']; ?>)"
                                            class="px-4 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900 rounded-lg transition">
                                        Delete
                                    </button>
                                    <button type="submit" class="btn-primary">
                                        Approve & Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="w-20 h-20 mx-auto text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">All caught up!</h3>
            <p class="text-gray-600 dark:text-gray-400">No receipts need review at this time</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Handle review form submission
document.querySelectorAll('.review-form').forEach(form => {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const scanId = this.dataset.scanId;
        const formData = new FormData(this);

        // Build parsed data object
        const parsedData = {
            merchant: formData.get('merchant'),
            date: formData.get('date'),
            total: parseFloat(formData.get('total')),
            currency: formData.get('currency'),
            tax: parseFloat(formData.get('tax')) || null,
            items: []
        };

        // Collect items
        const itemInputs = this.querySelectorAll('[name^="items["]');
        const itemsMap = {};
        itemInputs.forEach(input => {
            const match = input.name.match(/items\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const idx = match[1];
                const field = match[2];
                if (!itemsMap[idx]) itemsMap[idx] = {};
                itemsMap[idx][field] = input.value;
            }
        });

        Object.values(itemsMap).forEach(item => {
            if (item.name && item.total) {
                parsedData.items.push({
                    name: item.name,
                    quantity: parseFloat(item.quantity) || 1,
                    total: parseFloat(item.total)
                });
            }
        });

        try {
            const response = await fetch('/receipt/update-scan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    scan_id: parseInt(scanId),
                    parsed_data: parsedData
                })
            });

            const result = await response.json();

            if (result.success) {
                // Remove from queue UI
                this.closest('.border-l-4').remove();

                // Check if queue is empty
                const remaining = document.querySelectorAll('.review-form').length;
                if (remaining === 0) {
                    location.reload();
                }
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            alert('Error updating receipt: ' + error.message);
        }
    });
});

async function deleteReceipt(scanId) {
    if (!confirm('Are you sure you want to delete this receipt?')) {
        return;
    }

    try {
        const response = await fetch('/receipt/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ scan_id: scanId })
        });

        const result = await response.json();

        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Error deleting receipt: ' + error.message);
    }
}
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}
</style>
