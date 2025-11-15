<?php /** Settings */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Nastavení</h1>

    <div class="space-y-6">
        <!-- Profile Settings -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Profil</h2>
            <form method="POST" action="/settings/profile">
                <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jméno</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="w-full border rounded px-3 py-2">
                    </div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Uložit změny
                    </button>
                </div>
            </form>
        </div>

        <!-- Password Change -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Změna hesla</h2>
            <form method="POST" action="/settings/password">
                <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Současné heslo</label>
                        <input type="password" name="current_password" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nové heslo</label>
                        <input type="password" name="new_password" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Potvrdit nové heslo</label>
                        <input type="password" name="confirm_password" class="w-full border rounded px-3 py-2">
                    </div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Změnit heslo
                    </button>
                </div>
            </form>
        </div>

        <!-- Preferences -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Předvolby</h2>
            <form method="POST" action="/settings/preferences">
                <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Měna</label>
                        <select name="currency" class="w-full border rounded px-3 py-2">
                            <option value="CZK" <?php echo ($user['currency'] ?? 'CZK') === 'CZK' ? 'selected' : ''; ?>>CZK (Kč)</option>
                            <option value="EUR" <?php echo ($user['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                            <option value="USD" <?php echo ($user['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Časové pásmo</label>
                        <select name="timezone" class="w-full border rounded px-3 py-2">
                            <option value="Europe/Prague" <?php echo ($user['timezone'] ?? 'Europe/Prague') === 'Europe/Prague' ? 'selected' : ''; ?>>Europe/Prague</option>
                            <option value="Europe/London" <?php echo ($user['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Uložit předvolby
                    </button>
                </div>
            </form>
        </div>

        <!-- Data Management -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Správa dat</h2>
            <div class="space-y-4">
                <a href="/export" class="inline-block bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded">
                    Exportovat data
                </a>
                <a href="/import" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded ml-3">
                    Importovat data
                </a>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-red-800 mb-4">Nebezpečná zóna</h2>
            <p class="text-sm text-red-600 mb-4">Smazání účtu je nevratné.</p>
            <button onclick="confirmDelete()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded">
                Smazat účet
            </button>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Opravdu chcete smazat váš účet? Tato akce je nevratná!')) {
        if (confirm('Jste si jistí? Všechna data budou trvale smazána.')) {
            window.location.href = '/settings/delete-account';
        }
    }
}
</script>
