<?php /** Account Details */ ?>
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($account['name'] ?? ''); ?></h1>
            <p class="text-gray-500"><?php echo htmlspecialchars($account['type'] ?? ''); ?></p>
        </div>
        <a href="/accounts" class="text-blue-600 hover:text-blue-800">← Zpět na účty</a>
    </div>

    <!-- Account Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Aktuální zůstatek</h3>
            <p class="text-2xl font-bold mt-2"><?php echo number_format($account['balance'] ?? 0, 2); ?> <?php echo htmlspecialchars($account['currency'] ?? 'CZK'); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Příjmy tento měsíc</h3>
            <p class="text-2xl font-bold mt-2 text-green-600"><?php echo number_format($summary['monthly_income'] ?? 0, 2); ?> Kč</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Výdaje tento měsíc</h3>
            <p class="text-2xl font-bold mt-2 text-red-600"><?php echo number_format($summary['monthly_expenses'] ?? 0, 2); ?> Kč</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Počet transakcí</h3>
            <p class="text-2xl font-bold mt-2"><?php echo $summary['transaction_count'] ?? 0; ?></p>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Poslední transakce</h2>
        <div class="space-y-3">
            <?php if (!empty($transactions)): ?>
                <?php foreach ($transactions as $transaction): ?>
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></p>
                            <p class="text-sm text-gray-500">
                                <?php echo date('d.m.Y', strtotime($transaction['date'])); ?> •
                                <?php echo htmlspecialchars($transaction['category_name'] ?? ''); ?>
                            </p>
                        </div>
                        <p class="font-semibold <?php echo $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                            <?php echo number_format($transaction['amount'], 2); ?> Kč
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500 text-center py-4">Žádné transakce</p>
            <?php endif; ?>
        </div>
        <?php if (!empty($transactions) && count($transactions) >= 10): ?>
            <a href="/transactions?account_id=<?php echo $account['id']; ?>" class="inline-block mt-4 text-blue-600 hover:text-blue-800">
                Zobrazit všechny transakce →
            </a>
        <?php endif; ?>
    </div>

    <!-- Account Actions -->
    <div class="flex gap-3 mt-6">
        <a href="/accounts/<?php echo $account['id']; ?>/edit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
            Upravit účet
        </a>
        <a href="/transactions/create?account_id=<?php echo $account['id']; ?>" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded">
            Přidat transakci
        </a>
    </div>
</div>
