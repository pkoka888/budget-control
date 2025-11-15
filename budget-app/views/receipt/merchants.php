<?php /** Merchant Management */ ?>
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold mb-2">Správa obchodníků</h1>
            <p class="text-gray-600">Spravujte obchodníky a automatickou kategorizaci</p>
        </div>
        <button onclick="openAddMerchantModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
            + Přidat obchodníka
        </button>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <form method="GET" action="/receipt/merchants" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="w-full border rounded px-4 py-2" placeholder="Hledat obchodníka...">
            </div>
            <div>
                <select name="category" class="w-full border rounded px-4 py-2" onchange="this.form.submit()">
                    <option value="">Všechny kategorie</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo ($_GET['category'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Merchants List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-6 font-semibold text-gray-700">Obchodník</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Kategorie</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Transakcí</th>
                    <th class="text-right py-3 px-4 font-semibold text-gray-700">Celkem</th>
                    <th class="text-right py-3 px-6 font-semibold text-gray-700">Akce</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (!empty($merchants)): ?>
                    <?php foreach ($merchants as $merchant): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($merchant['name'] ?? ''); ?></p>
                                    <?php if (!empty($merchant['address'])): ?>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($merchant['address']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <?php if (!empty($merchant['default_category_name'])): ?>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                        <?php echo htmlspecialchars($merchant['default_category_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">Nenastaveno</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right py-4 px-4 text-gray-700">
                                <?php echo number_format($merchant['transaction_count'] ?? 0); ?>
                            </td>
                            <td class="text-right py-4 px-4 font-medium">
                                <?php echo number_format($merchant['total_amount'] ?? 0, 2); ?> Kč
                            </td>
                            <td class="text-right py-4 px-6">
                                <button onclick="editMerchant(<?php echo $merchant['id']; ?>)" class="text-blue-600 hover:text-blue-800 mr-3">
                                    Upravit
                                </button>
                                <button onclick="deleteMerchant(<?php echo $merchant['id']; ?>)" class="text-red-600 hover:text-red-800">
                                    Smazat
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Žádní obchodníci
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Celkem obchodníků</h3>
            <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['total_merchants'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">S nastavenou kategorií</h3>
            <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['with_category'] ?? 0); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Celková částka transakcí</h3>
            <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['total_amount'] ?? 0, 2); ?> Kč</p>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
        <h3 class="font-semibold text-blue-900 mb-2">O obchodnících</h3>
        <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
            <li>Nastavte výchozí kategorii pro automatickou kategorizaci transakcí</li>
            <li>Obchodníci jsou automaticky rozpoznáváni z účtenek pomocí OCR</li>
            <li>Můžete ručně přidat obchodníky pro lepší kategorizaci</li>
            <li>Statistiky zahrnují všechny transakce s daným obchodníkem</li>
        </ul>
    </div>
</div>

<!-- Add/Edit Merchant Modal -->
<div id="merchantModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full mx-4">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6">Přidat obchodníka</h2>

        <form id="merchantForm" method="POST" action="/receipt/merchants">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
            <input type="hidden" id="merchant_id" name="merchant_id">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Název obchodníka *</label>
                    <input type="text" id="merchant_name" name="name" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresa</label>
                    <textarea id="merchant_address" name="address" rows="2" class="w-full border rounded px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IČO</label>
                    <input type="text" id="merchant_tax_id" name="tax_id" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Výchozí kategorie</label>
                    <select id="merchant_category" name="default_category_id" class="w-full border rounded px-3 py-2">
                        <option value="">Vyberte kategorii</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Uložit
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded">
                        Zrušit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openAddMerchantModal() {
    document.getElementById('modalTitle').textContent = 'Přidat obchodníka';
    document.getElementById('merchantForm').action = '/receipt/merchants';
    document.getElementById('merchant_id').value = '';
    document.getElementById('merchant_name').value = '';
    document.getElementById('merchant_address').value = '';
    document.getElementById('merchant_tax_id').value = '';
    document.getElementById('merchant_category').value = '';
    document.getElementById('merchantModal').classList.remove('hidden');
}

function editMerchant(id) {
    // Fetch merchant data via AJAX or use inline data
    fetch('/api/merchants/' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Upravit obchodníka';
            document.getElementById('merchantForm').action = '/receipt/merchants/' + id;
            document.getElementById('merchant_id').value = id;
            document.getElementById('merchant_name').value = data.name || '';
            document.getElementById('merchant_address').value = data.address || '';
            document.getElementById('merchant_tax_id').value = data.tax_id || '';
            document.getElementById('merchant_category').value = data.default_category_id || '';
            document.getElementById('merchantModal').classList.remove('hidden');
        });
}

function closeModal() {
    document.getElementById('merchantModal').classList.add('hidden');
}

function deleteMerchant(id) {
    if (confirm('Opravdu chcete smazat tohoto obchodníka? Transakce zůstanou zachovány.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/receipt/merchants/' + id + '/delete';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal on outside click
document.getElementById('merchantModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
