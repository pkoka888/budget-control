<?php /** Transactions List */ ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Transakce</h1>
        <a href="/transactions/create" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
            + Nová transakce
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="/transactions" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kategorie</label>
                <select name="category_id" class="w-full border rounded px-3 py-2">
                    <option value="">Všechny</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category_id']) && $_GET['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Od data</label>
                <input type="date" name="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Do data</label>
                <input type="date" name="end_date" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">
                    Filtrovat
                </button>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Popis</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategorie</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Účet</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Částka</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Akce</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (!empty($transactions)): ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php echo date('d.m.Y', strtotime($transaction['date'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php echo htmlspecialchars($transaction['description'] ?? ''); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php echo htmlspecialchars($transaction['category_name'] ?? ''); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php echo htmlspecialchars($transaction['account_name'] ?? ''); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold <?php echo $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                                <?php echo number_format($transaction['amount'], 2); ?> Kč
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <a href="/transactions/<?php echo $transaction['id']; ?>" class="text-blue-600 hover:text-blue-800 mr-3">Zobrazit</a>
                                <a href="/transactions/<?php echo $transaction['id']; ?>/edit" class="text-gray-600 hover:text-gray-800">Upravit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Žádné transakce nenalezeny. <a href="/transactions/create" class="text-blue-600 hover:text-blue-800">Přidat první transakci</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
        <div class="mt-6 flex justify-center">
            <nav class="flex gap-2">
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="px-4 py-2 rounded <?php echo $i == ($pagination['current_page'] ?? 1) ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>
