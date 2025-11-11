
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Účty</h1>
                <a href="/accounts/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Vytvořit nový účet
                </a>
            </div>

            <?php
            if (isset($flash) && $flash):
            ?>
                <div class="bg-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-100 border border-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-400 text-<?php echo $flash['type'] === 'success' ? 'green' : 'red'; ?>-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($accounts as $account): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                        <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($account['name']); ?></h3>
                        <p class="text-gray-600 text-sm mb-4">
                            Typ: <?php echo htmlspecialchars(ucfirst($account['type'])); ?>
                        </p>

                        <div class="border-t pt-4">
                            <p class="text-gray-600 text-sm">Souon zůstatek</p>
                            <p class="text-2xl font-bold text-blue-600">
                                <?php echo number_format($account['current_balance'], 0, ',', ' '); ?> <?php echo htmlspecialchars($account['currency']); ?>
                            </p>
                        </div>

                        <div class="mt-4 space-x-2">
                            <a href="/accounts/<?php echo $account['id']; ?>" class="text-blue-600 hover:underline">
                                Zobrazit
                            </a>
                            <button onclick="editAccount(<?php echo $account['id']; ?>)" class="text-blue-600 hover:underline">
                                Upravit
                            </button>
                            <button onclick="deleteAccount(<?php echo $account['id']; ?>)" class="text-red-600 hover:underline">
                                Smazat
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($accounts)): ?>
                <div class="text-center py-12">
                    <p class="text-gray-600 text-lg">Nemáte žádné účty. <a href="/accounts/create" class="text-blue-600 hover:underline">Vytvořit první účet</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function editAccount(id) {
    // TODO: Implement edit modal
    alert('Edit account ' + id);
}

function deleteAccount(id) {
    if (confirm('Opravdu chcete smazat tento účet?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/accounts/' + id + '/delete';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
