<?php /** Accounts List */ ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Účty</h1>
        <a href="/accounts/create" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
            + Nový účet
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($accounts)): ?>
            <?php foreach ($accounts as $account): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($account['name']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($account['type'] ?? 'checking'); ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded <?php echo $account['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $account['is_active'] ? 'Aktivní' : 'Neaktivní'; ?>
                        </span>
                    </div>
                    <div class="mb-4">
                        <p class="text-2xl font-bold"><?php echo number_format($account['balance'] ?? 0, 2); ?> <?php echo htmlspecialchars($account['currency'] ?? 'CZK'); ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="/accounts/<?php echo $account['id']; ?>" class="flex-1 text-center bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded text-sm">
                            Zobrazit
                        </a>
                        <a href="/accounts/<?php echo $account['id']; ?>/edit" class="flex-1 text-center bg-blue-100 hover:bg-blue-200 text-blue-700 px-4 py-2 rounded text-sm">
                            Upravit
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 mb-4">Zatím nemáte žádné účty</p>
                <a href="/accounts/create" class="text-blue-600 hover:text-blue-800">Přidat první účet</a>
            </div>
        <?php endif; ?>
    </div>
</div>
