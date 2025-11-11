
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Nová transakce</h1>

            <form method="POST" action="/transactions" class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label for="account_id" class="block text-sm font-medium text-gray-700">Účet*</label>
                    <select id="account_id" name="account_id" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Vyberte účet --</option>
                        <?php foreach ($accounts as $account): ?>
                            <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Typ*</label>
                    <select id="type" name="type" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="expense">Výdaj</option>
                        <option value="income">Příjem</option>
                    </select>
                </div>

                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">Datum*</label>
                    <input type="date" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Popis*</label>
                    <input type="text" id="description" name="description" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Např. Nákup potravin">
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Částka*</label>
                    <input type="number" id="amount" name="amount" required step="0.01" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0,00">
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700">Kategorie</label>
                    <select id="category_id" name="category_id" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Bez kategorie --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition">
                        Vytvořit transakci
                    </button>
                    <a href="/transactions" class="flex-1 bg-gray-300 text-gray-800 font-semibold py-2 rounded-lg hover:bg-gray-400 transition text-center">
                        Zrušit
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
