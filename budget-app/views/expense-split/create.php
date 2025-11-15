<?php /** Create Split Expense */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Rozdělit výdaj</h1>
        <p class="text-gray-600">Rozdělte transakci do více kategorií</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="/expense-split" id="splitForm">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

            <!-- Transaction Selection -->
            <div class="mb-6 pb-6 border-b">
                <h3 class="text-lg font-semibold mb-4">Vyberte transakci</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transakce *</label>
                    <select name="transaction_id" id="transaction_id" class="w-full border rounded px-3 py-2" required onchange="loadTransactionDetails(this.value)">
                        <option value="">Vyberte transakci</option>
                        <?php if (!empty($transactions)): ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <option value="<?php echo $transaction['id']; ?>" data-amount="<?php echo $transaction['amount']; ?>">
                                    <?php echo date('d.m.Y', strtotime($transaction['date'])); ?> -
                                    <?php echo htmlspecialchars($transaction['description'] ?? ''); ?>
                                    (<?php echo number_format($transaction['amount'], 2); ?> Kč)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Transaction Details -->
                <div id="transaction-details" class="mt-4 hidden">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-600">Celková částka:</span>
                                <span id="total-amount" class="font-semibold ml-2">0 Kč</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Zbývající částka:</span>
                                <span id="remaining-amount" class="font-semibold ml-2">0 Kč</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Split Items -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4">Rozdělené položky</h3>

                <div id="splits-container" class="space-y-3">
                    <!-- Split rows will be added here -->
                </div>

                <button type="button" onclick="addSplitRow()" class="mt-3 text-blue-600 hover:text-blue-800 text-sm">
                    + Přidat položku
                </button>

                <div id="split-warning" class="mt-4 bg-yellow-50 border border-yellow-200 rounded p-3 text-sm text-yellow-800 hidden">
                    Součet rozdělených částek neodpovídá celkové částce transakce.
                </div>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-6 border-t">
                <button type="submit" id="submitBtn" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded" disabled>
                    Uložit rozdělení
                </button>
                <a href="/transactions" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 px-6 py-3 rounded">
                    Zrušit
                </a>
            </div>
        </form>
    </div>

    <!-- Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
        <h3 class="font-semibold text-blue-900 mb-2">Jak rozdělovat výdaje</h3>
        <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
            <li>Vyberte transakci, kterou chcete rozdělit</li>
            <li>Přidejte položky s kategorií a částkou</li>
            <li>Součet všech položek musí odpovídat celkové částce</li>
            <li>Můžete přidat popis ke každé položce</li>
        </ul>
    </div>
</div>

<script>
let totalAmount = 0;
let categories = <?php echo json_encode($categories ?? []); ?>;

function loadTransactionDetails(transactionId) {
    if (!transactionId) {
        document.getElementById('transaction-details').classList.add('hidden');
        document.getElementById('splits-container').innerHTML = '';
        document.getElementById('submitBtn').disabled = true;
        return;
    }

    const select = document.getElementById('transaction_id');
    const option = select.options[select.selectedIndex];
    totalAmount = parseFloat(option.dataset.amount || 0);

    document.getElementById('total-amount').textContent = formatCurrency(totalAmount);
    document.getElementById('remaining-amount').textContent = formatCurrency(totalAmount);
    document.getElementById('transaction-details').classList.remove('hidden');
    document.getElementById('splits-container').innerHTML = '';

    // Add first split row
    addSplitRow();
}

function addSplitRow() {
    const container = document.getElementById('splits-container');
    const index = container.children.length;

    const row = document.createElement('div');
    row.className = 'split-row grid grid-cols-12 gap-3 items-start';
    row.innerHTML = `
        <div class="col-span-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Kategorie</label>
            <select name="splits[${index}][category_id]" class="w-full border rounded px-3 py-2" required onchange="updateRemaining()">
                <option value="">Vyberte kategorii</option>
                ${categories.map(cat => `<option value="${cat.id}">${cat.name}</option>`).join('')}
            </select>
        </div>
        <div class="col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Částka (Kč)</label>
            <input type="number" name="splits[${index}][amount]" step="0.01" class="split-amount w-full border rounded px-3 py-2" required oninput="updateRemaining()">
        </div>
        <div class="col-span-3">
            <label class="block text-sm font-medium text-gray-700 mb-1">Popis</label>
            <input type="text" name="splits[${index}][description]" class="w-full border rounded px-3 py-2" placeholder="Volitelné">
        </div>
        <div class="col-span-1 pt-7">
            <button type="button" onclick="this.closest('.split-row').remove(); updateRemaining()" class="text-red-600 hover:text-red-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;

    container.appendChild(row);
    updateRemaining();
}

function updateRemaining() {
    const inputs = document.querySelectorAll('.split-amount');
    let sum = 0;

    inputs.forEach(input => {
        const value = parseFloat(input.value || 0);
        sum += value;
    });

    const remaining = totalAmount - sum;
    document.getElementById('remaining-amount').textContent = formatCurrency(remaining);

    const warning = document.getElementById('split-warning');
    const submitBtn = document.getElementById('submitBtn');

    if (Math.abs(remaining) < 0.01 && sum > 0) {
        warning.classList.add('hidden');
        submitBtn.disabled = false;
    } else {
        if (sum > 0) {
            warning.classList.remove('hidden');
        }
        submitBtn.disabled = true;
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('cs-CZ', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount) + ' Kč';
}
</script>
