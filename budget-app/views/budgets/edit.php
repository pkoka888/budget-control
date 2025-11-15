<?php /** Edit Budget */ ?>
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Upravit rozpočet</h1>
        <p class="text-gray-600">Upravte nastavení rozpočtu</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="/budgets/<?php echo $budget['id']; ?>">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
            <input type="hidden" name="_method" value="PUT">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Název rozpočtu *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($budget['name'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategorie *</label>
                    <select name="category_id" class="w-full border rounded px-3 py-2" required>
                        <option value="">Vyberte kategorii</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($budget['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Částka *</label>
                    <input type="number" name="amount" value="<?php echo $budget['amount'] ?? 0; ?>" step="0.01" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Období</label>
                    <select name="period" class="w-full border rounded px-3 py-2">
                        <option value="monthly" <?php echo ($budget['period'] ?? 'monthly') === 'monthly' ? 'selected' : ''; ?>>Měsíčně</option>
                        <option value="yearly" <?php echo ($budget['period'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Ročně</option>
                        <option value="weekly" <?php echo ($budget['period'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Týdně</option>
                        <option value="quarterly" <?php echo ($budget['period'] ?? '') === 'quarterly' ? 'selected' : ''; ?>>Čtvrtletně</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Začátek období</label>
                    <input type="date" name="start_date" value="<?php echo $budget['start_date'] ?? date('Y-m-01'); ?>" class="w-full border rounded px-3 py-2">
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Uložit změny
                    </button>
                    <a href="/budgets" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded">
                        Zrušit
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
