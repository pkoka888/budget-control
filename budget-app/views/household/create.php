<?php /** Create Household */ ?>
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Vytvořit domácnost</h1>
        <p class="text-gray-600">Sdílejte finance a účty s členy rodiny</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="/household">
            <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

            <div class="space-y-4">
                <!-- Basic Information -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Název domácnosti *</label>
                    <input type="text" name="name" class="w-full border rounded px-3 py-2" placeholder="např. Rodina Nováků" required>
                    <p class="text-xs text-gray-500 mt-1">Název viditelný všem členům</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Popis</label>
                    <textarea name="description" rows="3" class="w-full border rounded px-3 py-2" placeholder="Volitelný popis domácnosti"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Výchozí měna</label>
                    <select name="currency" class="w-full border rounded px-3 py-2">
                        <option value="CZK" selected>CZK (Kč)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="USD">USD ($)</option>
                    </select>
                </div>

                <!-- Member Permissions -->
                <div class="pt-4 border-t">
                    <h3 class="text-lg font-semibold mb-3">Nastavení oprávnění</h3>

                    <div class="space-y-3">
                        <div class="flex items-start">
                            <input type="checkbox" name="allow_member_transactions" id="allow_member_transactions" value="1" checked class="mt-1 mr-2">
                            <div>
                                <label for="allow_member_transactions" class="text-sm font-medium text-gray-700">Členové mohou vytvářet transakce</label>
                                <p class="text-xs text-gray-500">Povolit všem členům přidávat a upravovat transakce</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <input type="checkbox" name="require_approval_threshold" id="require_approval_threshold" value="1" class="mt-1 mr-2">
                            <div>
                                <label for="require_approval_threshold" class="text-sm font-medium text-gray-700">Vyžadovat schválení pro velké transakce</label>
                                <p class="text-xs text-gray-500">Transakce nad stanovenou částku vyžadují schválení</p>
                            </div>
                        </div>

                        <div id="approval_threshold_container" class="ml-6 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Částka pro schválení (Kč)</label>
                            <input type="number" name="approval_threshold" step="0.01" class="w-full border rounded px-3 py-2" placeholder="10000">
                        </div>

                        <div class="flex items-start">
                            <input type="checkbox" name="allow_member_budgets" id="allow_member_budgets" value="1" checked class="mt-1 mr-2">
                            <div>
                                <label for="allow_member_budgets" class="text-sm font-medium text-gray-700">Členové mohou vytvářet rozpočty</label>
                                <p class="text-xs text-gray-500">Povolit všem členům vytvářet a upravovat rozpočty</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <input type="checkbox" name="share_all_accounts" id="share_all_accounts" value="1" class="mt-1 mr-2">
                            <div>
                                <label for="share_all_accounts" class="text-sm font-medium text-gray-700">Sdílet všechny účty automaticky</label>
                                <p class="text-xs text-gray-500">Nové účty budou automaticky sdíleny s domácností</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Initial Members -->
                <div class="pt-4 border-t">
                    <h3 class="text-lg font-semibold mb-3">Pozvat členy</h3>
                    <p class="text-sm text-gray-600 mb-4">Můžete pozvat členy později z nastavení domácnosti</p>

                    <div id="members-container" class="space-y-3">
                        <div class="member-row grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="md:col-span-2">
                                <input type="email" name="member_emails[]" class="w-full border rounded px-3 py-2" placeholder="email@example.com">
                            </div>
                            <div>
                                <select name="member_roles[]" class="w-full border rounded px-3 py-2">
                                    <option value="member">Člen</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <button type="button" onclick="addMemberRow()" class="mt-3 text-blue-600 hover:text-blue-800 text-sm">
                        + Přidat další člena
                    </button>
                </div>

                <!-- Submit -->
                <div class="flex gap-3 pt-6 border-t">
                    <button type="submit" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded">
                        Vytvořit domácnost
                    </button>
                    <a href="/household" class="flex-1 text-center bg-gray-200 hover:bg-gray-300 px-6 py-3 rounded">
                        Zrušit
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
        <h3 class="font-semibold text-blue-900 mb-2">O domácnostech</h3>
        <ul class="text-sm text-blue-800 list-disc list-inside space-y-1">
            <li>Sdílejte účty a rozpočty s rodinou</li>
            <li>Sledujte společné výdaje a příjmy</li>
            <li>Nastavte oprávnění pro jednotlivé členy</li>
            <li>Vyžadujte schválení pro velké transakce</li>
            <li>Jako vlastník můžete kdykoliv změnit nastavení</li>
        </ul>
    </div>
</div>

<script>
// Show/hide approval threshold field
document.getElementById('require_approval_threshold').addEventListener('change', function() {
    const container = document.getElementById('approval_threshold_container');
    if (this.checked) {
        container.classList.remove('hidden');
    } else {
        container.classList.add('hidden');
    }
});

// Add member invitation row
function addMemberRow() {
    const container = document.getElementById('members-container');
    const row = document.createElement('div');
    row.className = 'member-row grid grid-cols-1 md:grid-cols-3 gap-3';
    row.innerHTML = `
        <div class="md:col-span-2">
            <input type="email" name="member_emails[]" class="w-full border rounded px-3 py-2" placeholder="email@example.com">
        </div>
        <div class="flex gap-2">
            <select name="member_roles[]" class="flex-1 border rounded px-3 py-2">
                <option value="member">Člen</option>
                <option value="admin">Admin</option>
            </select>
            <button type="button" onclick="this.closest('.member-row').remove()" class="text-red-600 hover:text-red-800 px-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    container.appendChild(row);
}
</script>
