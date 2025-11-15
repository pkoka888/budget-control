<?php /** API Key Management */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold mb-2">API klíče</h1>
            <p class="text-gray-600">Spravujte přístup k API pro externí aplikace</p>
        </div>
        <button onclick="openCreateKeyModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
            + Vytvořit klíč
        </button>
    </div>

    <!-- Active API Keys -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <h2 class="text-lg font-semibold">Aktivní klíče</h2>
        </div>

        <?php if (!empty($api_keys)): ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($api_keys as $key): ?>
                    <div class="px-6 py-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($key['name'] ?? ''); ?></h3>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($key['description'] ?? ''); ?></p>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="revokeKey(<?php echo $key['id']; ?>)" class="text-red-600 hover:text-red-800 text-sm">
                                    Zrušit
                                </button>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded p-3 mb-3">
                            <div class="flex items-center justify-between">
                                <code class="text-sm font-mono"><?php echo htmlspecialchars($key['key_preview'] ?? str_repeat('*', 32)); ?></code>
                                <button onclick="copyToClipboard('<?php echo htmlspecialchars($key['api_key'] ?? ''); ?>')" class="text-blue-600 hover:text-blue-800 text-sm ml-2">
                                    Kopírovat
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Vytvořeno:</span>
                                <p class="font-medium"><?php echo date('d.m.Y', strtotime($key['created_at'])); ?></p>
                            </div>
                            <div>
                                <span class="text-gray-500">Poslední použití:</span>
                                <p class="font-medium"><?php echo !empty($key['last_used_at']) ? date('d.m.Y H:i', strtotime($key['last_used_at'])) : 'Nikdy'; ?></p>
                            </div>
                            <div>
                                <span class="text-gray-500">Požadavků:</span>
                                <p class="font-medium"><?php echo number_format($key['request_count'] ?? 0); ?></p>
                            </div>
                            <div>
                                <span class="text-gray-500">Oprávnění:</span>
                                <p class="font-medium">
                                    <?php
                                        $scopes = json_decode($key['scopes'] ?? '[]', true);
                                        echo count($scopes) > 0 ? count($scopes) . ' oprávnění' : 'Žádná';
                                    ?>
                                </p>
                            </div>
                        </div>

                        <?php if (!empty($key['expires_at'])): ?>
                            <div class="mt-2 text-sm">
                                <span class="text-orange-600">Vyprší: <?php echo date('d.m.Y', strtotime($key['expires_at'])); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Žádné API klíče</h3>
                <p class="text-gray-600 mb-4">Vytvořte API klíč pro přístup k API</p>
                <button onclick="openCreateKeyModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                    Vytvořit první klíč
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- API Documentation -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">API dokumentace</h2>
        <p class="text-gray-600 mb-4">Základní použití API:</p>

        <div class="bg-gray-900 text-gray-100 rounded-lg p-4 mb-4 font-mono text-sm overflow-x-auto">
            <div class="mb-2">curl -X GET \</div>
            <div class="ml-4 mb-2">https://api.example.com/v1/transactions \</div>
            <div class="ml-4">-H "Authorization: Bearer YOUR_API_KEY"</div>
        </div>

        <div class="space-y-2 text-sm">
            <a href="/docs/api" class="block text-blue-600 hover:text-blue-800">→ Kompletní API dokumentace</a>
            <a href="/docs/api/authentication" class="block text-blue-600 hover:text-blue-800">→ Autentizace</a>
            <a href="/docs/api/endpoints" class="block text-blue-600 hover:text-blue-800">→ Seznam endpointů</a>
            <a href="/docs/api/examples" class="block text-blue-600 hover:text-blue-800">→ Příklady použití</a>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
        <h3 class="font-semibold text-yellow-900 mb-2">Bezpečnostní upozornění</h3>
        <ul class="text-sm text-yellow-800 list-disc list-inside space-y-1">
            <li>API klíče poskytují úplný přístup k vašim datům</li>
            <li>Nikdy nesdílejte své API klíče veřejně</li>
            <li>Klíče ukládejte bezpečně (proměnné prostředí, secrets management)</li>
            <li>Pravidelně rotujte klíče</li>
            <li>Používejte minimální potřebná oprávnění</li>
            <li>Okamžitě zrušte kompromitované klíče</li>
        </ul>
    </div>
</div>

<!-- Create API Key Modal -->
<div id="createKeyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-lg w-full mx-4">
        <h2 class="text-2xl font-bold mb-6">Vytvořit nový API klíč</h2>

        <form id="createKeyForm" method="POST" action="/export/api-keys">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Název klíče *</label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2" placeholder="např. Mobilní aplikace" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Popis</label>
                    <textarea name="description" rows="2" class="w-full border rounded px-3 py-2" placeholder="Volitelný popis použití"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Oprávnění</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto border rounded p-3">
                        <label class="flex items-center">
                            <input type="checkbox" name="scopes[]" value="read:transactions" class="mr-2">
                            <span class="text-sm">Číst transakce</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="scopes[]" value="write:transactions" class="mr-2">
                            <span class="text-sm">Vytvářet a upravovat transakce</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="scopes[]" value="read:accounts" class="mr-2">
                            <span class="text-sm">Číst účty</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="scopes[]" value="read:budgets" class="mr-2">
                            <span class="text-sm">Číst rozpočty</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="scopes[]" value="read:analytics" class="mr-2">
                            <span class="text-sm">Číst analytické údaje</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platnost klíče</label>
                    <select name="expires_in" class="w-full border rounded px-3 py-2">
                        <option value="">Neomezená</option>
                        <option value="30">30 dní</option>
                        <option value="90">90 dní</option>
                        <option value="365">1 rok</option>
                    </select>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded">
                        Vytvořit klíč
                    </button>
                    <button type="button" onclick="closeCreateKeyModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 px-6 py-3 rounded">
                        Zrušit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateKeyModal() {
    document.getElementById('createKeyModal').classList.remove('hidden');
}

function closeCreateKeyModal() {
    document.getElementById('createKeyModal').classList.add('hidden');
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('API klíč zkopírován do schránky');
    });
}

function revokeKey(id) {
    if (confirm('Opravdu chcete zrušit tento API klíč? Aplikace používající tento klíč přestanou fungovat.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/export/api-keys/' + id + '/revoke';

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
document.getElementById('createKeyModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateKeyModal();
    }
});
</script>
