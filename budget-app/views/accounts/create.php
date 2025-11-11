
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Vytvořit nový účet</h1>

            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/accounts" class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Jméno účtu*</label>
                    <input type="text" id="name" name="name" required class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Např. Běžný účet, Spořící účet">
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Typ účtu*</label>
                    <select id="type" name="type" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="checking">Běžný účet</option>
                        <option value="savings">Spořící účet</option>
                        <option value="investment">Investiční účet</option>
                        <option value="loan">Půjčka</option>
                        <option value="credit_card">Kreditní karta</option>
                        <option value="crypto">Kryptopeníze</option>
                    </select>
                </div>

                <div>
                    <label for="initial_balance" class="block text-sm font-medium text-gray-700">Počáteční zůstatek</label>
                    <input type="number" id="initial_balance" name="initial_balance" step="0.01" value="0" class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="0,00">
                </div>

                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white font-semibold py-2 rounded-lg hover:bg-blue-700 transition">
                        Vytvořit účet
                    </button>
                    <a href="/accounts" class="flex-1 bg-gray-300 text-gray-800 font-semibold py-2 rounded-lg hover:bg-gray-400 transition text-center">
                        Zrušit
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
