<?php /** Transaction Details */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Detail transakce</h1>
        <a href="/transactions" class="text-blue-600 hover:text-blue-800">← Zpět na transakce</a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Typ</h3>
                <p class="text-lg <?php echo $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $transaction['type'] === 'income' ? 'Příjem' : 'Výdaj'; ?>
                </p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Částka</h3>
                <p class="text-2xl font-bold <?php echo $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?>
                    <?php echo number_format($transaction['amount'], 2); ?> Kč
                </p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Datum</h3>
                <p class="text-lg"><?php echo date('d.m.Y', strtotime($transaction['date'])); ?></p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Kategorie</h3>
                <p class="text-lg"><?php echo htmlspecialchars($transaction['category_name'] ?? 'Bez kategorie'); ?></p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Účet</h3>
                <p class="text-lg"><?php echo htmlspecialchars($transaction['account_name'] ?? ''); ?></p>
            </div>

            <?php if (!empty($transaction['merchant_name'])): ?>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Obchodník</h3>
                <p class="text-lg"><?php echo htmlspecialchars($transaction['merchant_name']); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($transaction['description'])): ?>
            <div class="col-span-2">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Popis</h3>
                <p class="text-lg"><?php echo htmlspecialchars($transaction['description']); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($transaction['notes'])): ?>
            <div class="col-span-2">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Poznámka</h3>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($transaction['notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="mt-6 pt-6 border-t flex gap-3">
            <a href="/transactions/<?php echo $transaction['id']; ?>/edit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                Upravit
            </a>
            <button onclick="deleteTransaction()" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded">
                Smazat
            </button>
        </div>
    </div>

    <!-- Transaction Splits -->
    <?php if (!empty($splits)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Rozdělené položky</h2>
            <div class="space-y-2">
                <?php foreach ($splits as $split): ?>
                    <div class="flex justify-between items-center p-3 border-b">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($split['category_name'] ?? ''); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($split['description'] ?? ''); ?></p>
                        </div>
                        <p class="font-semibold"><?php echo number_format($split['amount'], 2); ?> Kč</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteTransaction() {
    if (confirm('Opravdu chcete smazat tuto transakci?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/transactions/<?php echo $transaction['id']; ?>/delete';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
