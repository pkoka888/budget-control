
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-2xl mx-auto">
            <a href="/transactions" class="text-blue-600 hover:underline mb-6">← Zpět na transakce</a>

            <div class="bg-white rounded-lg shadow p-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo htmlspecialchars($transaction['description']); ?></h1>

                <div class="grid grid-cols-2 gap-6 mb-6 p-6 bg-gray-50 rounded">
                    <div>
                        <p class="text-gray-600 text-sm">Datum</p>
                        <p class="text-lg font-bold"><?php echo htmlspecialchars($transaction['date']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Typ</p>
                        <p class="text-lg font-bold"><?php echo htmlspecialchars(ucfirst($transaction['type'])); ?></p>
                    </div>
                </div>

                <div class="mb-6">
                    <p class="text-gray-600 text-sm mb-2">Částka</p>
                    <p class="text-4xl font-bold <?php echo $transaction['amount'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo number_format($transaction['amount'], 0, ',', ' '); ?> Kč
                    </p>
                </div>

                <?php if (!empty($splits)): ?>
                <div class="split-container">
                    <!-- Split Banner -->
                    <div class="split-banner">
                        Rozděleno napříč <?php echo count($splits); ?> kategoriemi
                    </div>

                    <!-- Split Summary -->
                    <div class="split-summary">
                        <div class="split-summary-item">
                            <div class="split-summary-label">Původní částka</div>
                            <div class="split-summary-value <?php echo $transaction['amount'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo number_format($transaction['amount'], 0, ',', ' '); ?> Kč
                            </div>
                        </div>
                        <div class="split-summary-item">
                            <div class="split-summary-label">Rozděleno do</div>
                            <div class="split-summary-value text-blue-600">
                                <?php echo count($splits); ?> kategorií
                            </div>
                        </div>
                        <div class="split-summary-item">
                            <div class="split-summary-label">Celkem rozděleno</div>
                            <div class="split-summary-value <?php echo array_sum(array_column($splits, 'amount')) > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo array_sum(array_column($splits, 'amount')) > 0 ? '+' : ''; ?><?php echo number_format(array_sum(array_column($splits, 'amount')), 0, ',', ' '); ?> Kč
                            </div>
                        </div>
                    </div>

                    <!-- Individual Splits -->
                    <div class="space-y-2">
                        <?php foreach ($splits as $index => $split): ?>
                            <?php
                            $percentage = abs($transaction['amount']) > 0 ? round((abs($split['amount']) / abs($transaction['amount'])) * 100, 1) : 0;
                            ?>
                        <div class="split-item">
                            <div class="flex items-center flex-1">
                                <div class="split-category-indicator" style="background-color: <?php echo htmlspecialchars($split['category_color'] ?? '#3b82f6'); ?>"></div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($split['category_name']); ?></div>
                                    <?php if (!empty($split['description'])): ?>
                                    <div class="text-sm text-gray-600"><?php echo htmlspecialchars($split['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <div class="split-amount <?php echo $split['amount'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $split['amount'] > 0 ? '+' : ''; ?><?php echo number_format($split['amount'], 0, ',', ' '); ?> Kč
                                    </div>
                                    <div class="split-percentage"><?php echo $percentage; ?>%</div>
                                </div>
                                <div class="split-actions">
                                    <button onclick="editSplit(<?php echo $split['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm font-medium" title="Upravit rozdělení">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="deleteSplit(<?php echo $split['id']; ?>)" class="text-red-600 hover:text-red-800 text-sm font-medium" title="Smazat rozdělení">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Add New Split Button -->
                    <div class="mt-4 text-center">
                        <button onclick="addNewSplit()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors inline-flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>Přidat nové rozdělení</span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <div class="space-y-2 text-gray-700 mb-6">
                    <p><strong>ID:</strong> <?php echo htmlspecialchars($transaction['id']); ?></p>
                    <p><strong>Vytvořeno:</strong> <?php echo htmlspecialchars($transaction['created_at']); ?></p>
                </div>

                <div class="space-x-4">
                    <button onclick="editTransaction(<?php echo $transaction['id']; ?>)" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Upravit
                    </button>
                    <button onclick="deleteTransaction(<?php echo $transaction['id']; ?>)" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700">
                        Smazat
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editTransaction(id) {
    alert('Edit transaction ' + id);
}

function deleteTransaction(id) {
    if (confirm('Opravdu chcete smazat tuto transakci?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/transactions/' + id + '/delete';
        document.body.appendChild(form);
        form.submit();
    }
}

function editSplit(splitId) {
    alert('Edit split ' + splitId);
    // TODO: Implement split editing modal/form
}

function deleteSplit(splitId) {
    if (confirm('Opravdu chcete smazat toto rozdělení transakce?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/transactions/splits/' + splitId + '/delete';
        document.body.appendChild(form);
        form.submit();
    }
}

function addNewSplit() {
    alert('Add new split');
    // TODO: Implement add split modal/form
}
</script>
