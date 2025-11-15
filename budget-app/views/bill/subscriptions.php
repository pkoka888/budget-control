<?php /** Subscription Management */ ?>
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold mb-2">Správa předplatných</h1>
            <p class="text-gray-600">Sledujte pravidelné platby a předplatná</p>
        </div>
        <button onclick="openAddSubscriptionModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
            + Přidat předplatné
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Měsíční náklady</h3>
            <p class="text-3xl font-bold mt-2"><?php echo number_format($summary['monthly_cost'] ?? 0, 2); ?> Kč</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Roční náklady</h3>
            <p class="text-3xl font-bold mt-2"><?php echo number_format($summary['yearly_cost'] ?? 0, 2); ?> Kč</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Aktivních předplatných</h3>
            <p class="text-3xl font-bold mt-2"><?php echo $summary['active_count'] ?? 0; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Nejbližší platba</h3>
            <p class="text-lg font-bold mt-2"><?php echo !empty($summary['next_payment']) ? date('d.m.Y', strtotime($summary['next_payment'])) : '—'; ?></p>
        </div>
    </div>

    <!-- Active Subscriptions -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <h2 class="text-lg font-semibold">Aktivní předplatná</h2>
        </div>

        <?php if (!empty($active_subscriptions)): ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($active_subscriptions as $sub): ?>
                    <div class="px-6 py-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <?php if (!empty($sub['logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($sub['logo']); ?>" alt="" class="w-10 h-10 rounded mr-3">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-blue-100 rounded flex items-center justify-center mr-3">
                                            <span class="text-blue-600 font-semibold"><?php echo strtoupper(substr($sub['name'] ?? 'S', 0, 1)); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($sub['name'] ?? ''); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($sub['description'] ?? ''); ?></p>
                                    </div>
                                </div>

                                <div class="ml-13 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Cena:</span>
                                        <p class="font-medium"><?php echo number_format($sub['amount'], 2); ?> Kč / <?php echo $sub['billing_cycle'] === 'monthly' ? 'měsíc' : 'rok'; ?></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Další platba:</span>
                                        <p class="font-medium"><?php echo date('d.m.Y', strtotime($sub['next_billing_date'])); ?></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Kategorie:</span>
                                        <p class="font-medium"><?php echo htmlspecialchars($sub['category_name'] ?? ''); ?></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Účet:</span>
                                        <p class="font-medium"><?php echo htmlspecialchars($sub['account_name'] ?? ''); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2 ml-4">
                                <button onclick="editSubscription(<?php echo $sub['id']; ?>)" class="text-blue-600 hover:text-blue-800 text-sm">
                                    Upravit
                                </button>
                                <button onclick="cancelSubscription(<?php echo $sub['id']; ?>)" class="text-red-600 hover:text-red-800 text-sm">
                                    Zrušit
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Žádná aktivní předplatná</h3>
                <p class="text-gray-600 mb-4">Přidejte svá pravidelná předplatná pro lepší přehled výdajů</p>
                <button onclick="openAddSubscriptionModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                    Přidat předplatné
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cancelled Subscriptions -->
    <?php if (!empty($cancelled_subscriptions)): ?>
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-semibold">Zrušená předplatná</h2>
            </div>

            <div class="divide-y divide-gray-200">
                <?php foreach ($cancelled_subscriptions as $sub): ?>
                    <div class="px-6 py-4 opacity-60">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-medium"><?php echo htmlspecialchars($sub['name'] ?? ''); ?></h3>
                                <p class="text-sm text-gray-500">Zrušeno: <?php echo date('d.m.Y', strtotime($sub['cancelled_at'])); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium"><?php echo number_format($sub['amount'], 2); ?> Kč</p>
                                <p class="text-sm text-gray-500">Ušetřeno: <?php echo number_format($sub['saved_amount'] ?? 0, 2); ?> Kč</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Upcoming Payments -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
        <h3 class="font-semibold text-blue-900 mb-3">Nadcházející platby (30 dní)</h3>
        <?php if (!empty($upcoming_payments)): ?>
            <div class="space-y-2">
                <?php foreach ($upcoming_payments as $payment): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-blue-800"><?php echo date('d.m.Y', strtotime($payment['date'])); ?> - <?php echo htmlspecialchars($payment['name']); ?></span>
                        <span class="text-blue-900 font-medium"><?php echo number_format($payment['amount'], 2); ?> Kč</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-blue-800">Žádné nadcházející platby</p>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Subscription Modal -->
<div id="subscriptionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-lg w-full mx-4 max-h-screen overflow-y-auto">
        <h2 id="modalTitle" class="text-2xl font-bold mb-6">Přidat předplatné</h2>

        <form id="subscriptionForm" method="POST" action="/bill/subscriptions">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
            <input type="hidden" id="subscription_id" name="subscription_id">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Název *</label>
                    <input type="text" id="sub_name" name="name" class="w-full border rounded px-3 py-2" placeholder="např. Netflix" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Popis</label>
                    <input type="text" id="sub_description" name="description" class="w-full border rounded px-3 py-2" placeholder="Volitelný popis">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Částka *</label>
                    <input type="number" id="sub_amount" name="amount" step="0.01" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Platební cyklus *</label>
                    <select id="sub_cycle" name="billing_cycle" class="w-full border rounded px-3 py-2" required>
                        <option value="monthly">Měsíčně</option>
                        <option value="yearly">Ročně</option>
                        <option value="quarterly">Čtvrtletně</option>
                        <option value="weekly">Týdně</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Datum první platby *</label>
                    <input type="date" id="sub_start_date" name="start_date" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Účet *</label>
                    <select id="sub_account" name="account_id" class="w-full border rounded px-3 py-2" required>
                        <option value="">Vyberte účet</option>
                        <?php if (!empty($accounts)): ?>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>"><?php echo htmlspecialchars($account['name']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategorie *</label>
                    <select id="sub_category" name="category_id" class="w-full border rounded px-3 py-2" required>
                        <option value="">Vyberte kategorii</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="sub_auto_renew" name="auto_renew" value="1" checked class="mr-2">
                    <label for="sub_auto_renew" class="text-sm text-gray-700">Automaticky obnovovat</label>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded">
                        Uložit
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 px-6 py-3 rounded">
                        Zrušit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openAddSubscriptionModal() {
    document.getElementById('modalTitle').textContent = 'Přidat předplatné';
    document.getElementById('subscriptionForm').action = '/bill/subscriptions';
    document.getElementById('subscription_id').value = '';
    document.getElementById('sub_name').value = '';
    document.getElementById('sub_description').value = '';
    document.getElementById('sub_amount').value = '';
    document.getElementById('sub_cycle').value = 'monthly';
    document.getElementById('sub_start_date').value = '';
    document.getElementById('sub_account').value = '';
    document.getElementById('sub_category').value = '';
    document.getElementById('sub_auto_renew').checked = true;
    document.getElementById('subscriptionModal').classList.remove('hidden');
}

function editSubscription(id) {
    fetch('/api/subscriptions/' + id)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Upravit předplatné';
            document.getElementById('subscriptionForm').action = '/bill/subscriptions/' + id;
            document.getElementById('subscription_id').value = id;
            document.getElementById('sub_name').value = data.name || '';
            document.getElementById('sub_description').value = data.description || '';
            document.getElementById('sub_amount').value = data.amount || '';
            document.getElementById('sub_cycle').value = data.billing_cycle || 'monthly';
            document.getElementById('sub_start_date').value = data.start_date || '';
            document.getElementById('sub_account').value = data.account_id || '';
            document.getElementById('sub_category').value = data.category_id || '';
            document.getElementById('sub_auto_renew').checked = data.auto_renew || false;
            document.getElementById('subscriptionModal').classList.remove('hidden');
        });
}

function closeModal() {
    document.getElementById('subscriptionModal').classList.add('hidden');
}

function cancelSubscription(id) {
    if (confirm('Opravdu chcete zrušit toto předplatné?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/bill/subscriptions/' + id + '/cancel';

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
document.getElementById('subscriptionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
