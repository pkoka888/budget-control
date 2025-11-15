<?php /** Edit Transaction */ ?>
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Upravit transakci</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="/transactions/<?php echo $transaction['id']; ?>">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
            <input type="hidden" name="_method" value="PUT">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Typ</label>
                    <select name="type" id="type" class="w-full border rounded px-3 py-2" required>
                        <option value="expense" <?php echo ($transaction['type'] ?? '') === 'expense' ? 'selected' : ''; ?>>Výdaj</option>
                        <option value="income" <?php echo ($transaction['type'] ?? '') === 'income' ? 'selected' : ''; ?>>Příjem</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Částka *</label>
                    <input type="number" name="amount" value="<?php echo $transaction['amount'] ?? 0; ?>" step="0.01" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datum *</label>
                    <input type="date" name="date" value="<?php echo $transaction['date'] ?? date('Y-m-d'); ?>" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Účet *</label>
                    <select name="account_id" class="w-full border rounded px-3 py-2" required>
                        <option value="">Vyberte účet</option>
                        <?php if (!empty($accounts)): ?>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>" <?php echo ($transaction['account_id'] ?? '') == $account['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($account['name']); ?> (<?php echo number_format($account['balance'], 2); ?> Kč)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategorie *</label>
                    <select name="category_id" class="w-full border rounded px-3 py-2" required>
                        <option value="">Vyberte kategorii</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($transaction['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Popis</label>
                    <input type="text" name="description" value="<?php echo htmlspecialchars($transaction['description'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Obchodník</label>
                    <input type="text" name="merchant_name" value="<?php echo htmlspecialchars($transaction['merchant_name'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Poznámka</label>
                    <textarea name="notes" rows="3" class="w-full border rounded px-3 py-2"><?php echo htmlspecialchars($transaction['notes'] ?? ''); ?></textarea>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Uložit změny
                    </button>
                    <a href="/transactions/<?php echo $transaction['id']; ?>" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded">
                        Zrušit
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
