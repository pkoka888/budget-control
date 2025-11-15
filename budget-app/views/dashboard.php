<?php /** Dashboard */ ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Dashboard</h1>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Celkový zůstatek</h3>
            <p class="text-2xl font-bold mt-2"><?php echo number_format($summary['total_balance'] ?? 0, 2); ?> Kč</p>
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
            <h3 class="text-gray-500 text-sm">Čistá hotovost</h3>
            <p class="text-2xl font-bold mt-2"><?php echo number_format(($summary['monthly_income'] ?? 0) - ($summary['monthly_expenses'] ?? 0), 2); ?> Kč</p>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Poslední transakce</h2>
        <div class="space-y-3">
            <?php if (!empty($recent_transactions)): ?>
                <?php foreach ($recent_transactions as $transaction): ?>
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($transaction['category_name'] ?? ''); ?></p>
                        </div>
                        <p class="font-semibold <?php echo $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                            <?php echo number_format($transaction['amount'], 2); ?> Kč
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-gray-500">Žádné transakce</p>
            <?php endif; ?>
        </div>
        <a href="/transactions" class="inline-block mt-4 text-blue-600 hover:text-blue-800">Zobrazit vše →</a>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="/transactions/create" class="bg-blue-500 hover:bg-blue-600 text-white rounded-lg p-6 text-center transition">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <p class="font-semibold">Nová transakce</p>
        </a>
        <a href="/budgets" class="bg-green-500 hover:bg-green-600 text-white rounded-lg p-6 text-center transition">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <p class="font-semibold">Spravovat rozpočty</p>
        </a>
        <a href="/goals" class="bg-purple-500 hover:bg-purple-600 text-white rounded-lg p-6 text-center transition">
            <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
            <p class="font-semibold">Moje cíle</p>
        </a>
    </div>
</div>
