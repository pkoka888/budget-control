<?php /** Create Goal */ ?>
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Nový finanční cíl</h1>
        <p class="text-gray-600">Stanovte si cíl a sledujte pokrok</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="/goals">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Název cíle *</label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2" placeholder="např. Dovolená v létě" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Popis</label>
                    <textarea name="description" rows="3" class="w-full border rounded px-3 py-2" placeholder="Popište svůj cíl..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cílová částka *</label>
                    <input type="number" name="target_amount" step="0.01" class="w-full border rounded px-3 py-2" placeholder="0.00" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aktuální částka</label>
                    <input type="number" name="current_amount" step="0.01" value="0" class="w-full border rounded px-3 py-2">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Datum zahájení</label>
                        <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" class="w-full border rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cílové datum</label>
                        <input type="date" name="target_date" class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategorie cíle</label>
                    <select name="goal_category" class="w-full border rounded px-3 py-2">
                        <option value="savings">Spoření</option>
                        <option value="debt_payment">Splácení dluhu</option>
                        <option value="purchase">Velká koupě</option>
                        <option value="vacation">Dovolená</option>
                        <option value="emergency_fund">Nouzový fond</option>
                        <option value="retirement">Důchod</option>
                        <option value="other">Jiné</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Připojený účet (volitelné)</label>
                    <select name="account_id" class="w-full border rounded px-3 py-2">
                        <option value="">Nepřipojeno</option>
                        <?php if (!empty($accounts)): ?>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo htmlspecialchars($account['name']); ?> (<?php echo number_format($account['balance'], 2); ?> Kč)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" checked class="mr-2">
                    <label for="is_active" class="text-sm text-gray-700">Aktivní cíl</label>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Vytvořit cíl
                    </button>
                    <a href="/goals" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded">
                        Zrušit
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Goal Tips -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
        <h3 class="font-semibold text-blue-900 mb-2">Tipy pro stanovení cílů</h3>
        <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
            <li>Stanovte si konkrétní a dosažitelnou částku</li>
            <li>Určete realistické časové období</li>
            <li>Rozdělte velké cíle na menší milníky</li>
            <li>Pravidelně sledujte svůj pokrok</li>
            <li>Nastavte automatické převody pro snadnější spoření</li>
        </ul>
    </div>
</div>
