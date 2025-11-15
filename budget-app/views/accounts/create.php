<?php /** Create Account */ ?>
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Nový účet</h1>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="/accounts">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Název účtu *</label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2" placeholder="např. Běžný účet" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Typ účtu *</label>
                    <select name="type" class="w-full border rounded px-3 py-2" required>
                        <option value="checking">Běžný účet</option>
                        <option value="savings">Spořicí účet</option>
                        <option value="investment">Investiční účet</option>
                        <option value="loan">Úvěr</option>
                        <option value="credit_card">Kreditní karta</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Počáteční zůstatek</label>
                    <input type="number" name="initial_balance" step="0.01" value="0" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Měna</label>
                    <select name="currency" class="w-full border rounded px-3 py-2">
                        <option value="CZK">CZK (Kč)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="USD">USD ($)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Číslo účtu</label>
                    <input type="text" name="account_number" class="w-full border rounded px-3 py-2" placeholder="123456/0100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instituce</label>
                    <input type="text" name="institution" class="w-full border rounded px-3 py-2" placeholder="Česká spořitelna">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="mr-2">
                    <label for="is_active" class="text-sm text-gray-700">Aktivní účet</label>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Vytvořit účet
                    </button>
                    <a href="/accounts" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded">
                        Zrušit
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
