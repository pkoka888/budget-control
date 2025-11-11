
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-5xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($account['name']); ?></h1>
                <a href="/accounts" class="text-blue-600 hover:underline">← Zpět</a>
            </div>

            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="grid grid-cols-3 gap-6">
                    <div>
                        <p class="text-gray-600 text-sm">Typ účtu</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars(ucfirst($account['type'])); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Měna</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($account['currency']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Počáteční zůstatek</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo number_format($account['initial_balance'], 0, ',', ' '); ?></p>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-4">Poslední transakce</h2>
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Datum</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Popis</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Kategorie</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Částka</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($tx['date']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($tx['description']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($tx['category_name'] ?? 'N/A'); ?></td>
                                <td class="px-6 py-4 text-sm text-right font-semibold <?php echo $tx['amount'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $tx['amount'] > 0 ? '+' : ''; ?><?php echo number_format($tx['amount'], 0, ',', ' '); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
