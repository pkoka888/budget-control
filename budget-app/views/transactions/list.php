
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Transakce</h1>
                <a href="/transactions/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Nová transakce
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <form method="GET" action="/transactions" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="account" class="block text-sm font-medium text-gray-700">Účet</label>
                        <select id="account" name="account" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded">
                            <option value="">Všechny</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>" <?php echo isset($_GET['account']) && $_GET['account'] == $account['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($account['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">Kategorie</label>
                        <select id="category" name="category" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded">
                            <option value="">Všechny</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Od data</label>
                        <input type="date" id="start_date" name="start_date" class="mt-1 w-full px-3 py-2 border border-gray-300 rounded" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : ''; ?>">
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Filtrovat
                        </button>
                    </div>
                </form>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Datum</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Popis</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Účet</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Kategorie</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Částka</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Rozdělení</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($transaction['date']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($transaction['account_name']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($transaction['category_name'] ?? 'Bez kategorie'); ?></td>
                                <td class="px-6 py-4 text-sm text-right font-semibold <?php echo $transaction['amount'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $transaction['amount'] > 0 ? '+' : ''; ?><?php echo number_format($transaction['amount'], 2, ',', ' '); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-center">
                                    <?php if (isset($transaction['split_count']) && $transaction['split_count'] > 0): ?>
                                        <?php
                                        $splitCount = $transaction['split_count'];
                                        $badgeClass = 'split-badge ';
                                        $badgeColor = '';

                                        if ($splitCount === 1) {
                                            $badgeColor = 'bg-blue-100 text-blue-800';
                                        } elseif ($splitCount === 2) {
                                            $badgeColor = 'bg-green-100 text-green-800';
                                        } elseif ($splitCount === 3) {
                                            $badgeColor = 'bg-yellow-100 text-yellow-800';
                                        } elseif ($splitCount >= 4) {
                                            $badgeColor = 'bg-purple-100 text-purple-800';
                                        }
                                        ?>
                                        <span class="<?php echo $badgeClass . $badgeColor; ?>">
                                            Split (<?php echo $splitCount; ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-center space-x-2">
                                    <a href="/transactions/<?php echo $transaction['id']; ?>" class="text-blue-600 hover:underline">Edit</a>
                                    <a href="#" onclick="deleteTransaction(<?php echo $transaction['id']; ?>)" class="text-red-600 hover:underline">Smazat</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-center space-x-2">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="/transactions?page=<?php echo $p; ?>" class="px-4 py-2 rounded <?php echo $p === $page ? 'bg-blue-600 text-white' : 'bg-gray-300 hover:bg-gray-400'; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteTransaction(id) {
    if (confirm('Opravdu chcete smazat tuto transakci?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/transactions/' + id + '/delete';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
