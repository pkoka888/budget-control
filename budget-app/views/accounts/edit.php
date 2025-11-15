<?php /** Edit Account */ ?>
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Upravit účet</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="/accounts/<?php echo $account['id']; ?>">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
            <input type="hidden" name="_method" value="PUT">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Název účtu *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($account['name'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Typ účtu *</label>
                    <select name="type" class="w-full border rounded px-3 py-2" required>
                        <option value="checking" <?php echo ($account['type'] ?? '') === 'checking' ? 'selected' : ''; ?>>Běžný účet</option>
                        <option value="savings" <?php echo ($account['type'] ?? '') === 'savings' ? 'selected' : ''; ?>>Spořicí účet</option>
                        <option value="investment" <?php echo ($account['type'] ?? '') === 'investment' ? 'selected' : ''; ?>>Investiční účet</option>
                        <option value="loan" <?php echo ($account['type'] ?? '') === 'loan' ? 'selected' : ''; ?>>Úvěr</option>
                        <option value="credit_card" <?php echo ($account['type'] ?? '') === 'credit_card' ? 'selected' : ''; ?>>Kreditní karta</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aktuální zůstatek</label>
                    <input type="number" name="balance" value="<?php echo $account['balance'] ?? 0; ?>" step="0.01" class="w-full border rounded px-3 py-2">
                    <p class="text-xs text-gray-500 mt-1">Upravte pouze při úpravě nesprávného zůstatku</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Měna</label>
                    <select name="currency" class="w-full border rounded px-3 py-2">
                        <option value="CZK" <?php echo ($account['currency'] ?? 'CZK') === 'CZK' ? 'selected' : ''; ?>>CZK (Kč)</option>
                        <option value="EUR" <?php echo ($account['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                        <option value="USD" <?php echo ($account['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Číslo účtu</label>
                    <input type="text" name="account_number" value="<?php echo htmlspecialchars($account['account_number'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instituce</label>
                    <input type="text" name="institution" value="<?php echo htmlspecialchars($account['institution'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo !empty($account['is_active']) ? 'checked' : ''; ?> class="mr-2">
                    <label for="is_active" class="text-sm text-gray-700">Aktivní účet</label>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Uložit změny
                    </button>
                    <a href="/accounts" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded">
                        Zrušit
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
