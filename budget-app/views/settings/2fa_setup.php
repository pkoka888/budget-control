<?php /** Two-Factor Authentication Setup */ ?>
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">Dvoufaktorové ověření (2FA)</h1>
        <p class="text-gray-600">Zvyšte zabezpečení svého účtu pomocí dvoufaktorového ověření</p>
    </div>

    <?php if (!empty($enabled)): ?>
        <!-- 2FA Enabled -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <svg class="h-6 w-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h2 class="text-lg font-semibold text-green-900">Dvoufaktorové ověření je aktivní</h2>
            </div>
            <p class="text-sm text-green-800 mb-4">Váš účet je chráněn dvoufaktorovým ověřením.</p>

            <form method="POST" action="/settings/2fa/disable">
                <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded">
                    Deaktivovat 2FA
                </button>
            </form>
        </div>

        <!-- Backup Codes -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold mb-4">Záložní kódy</h3>
            <p class="text-sm text-gray-600 mb-4">Každý kód lze použít pouze jednou. Uložte si je na bezpečné místo.</p>

            <?php if (!empty($backup_codes)): ?>
                <div class="grid grid-cols-2 gap-2 mb-4 font-mono text-sm">
                    <?php foreach ($backup_codes as $code): ?>
                        <div class="bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($code); ?></div>
                    <?php endforeach; ?>
                </div>
                <button onclick="printCodes()" class="text-blue-600 hover:text-blue-800 text-sm">
                    Vytisknout kódy
                </button>
            <?php endif; ?>

            <form method="POST" action="/settings/2fa/regenerate-codes" class="mt-4">
                <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
                <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm">
                    Generovat nové záložní kódy
                </button>
            </form>
        </div>
    <?php else: ?>
        <!-- Setup 2FA -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Nastavit dvoufaktorové ověření</h2>

            <div class="space-y-6">
                <!-- Step 1: Install App -->
                <div>
                    <h3 class="font-semibold mb-2">1. Nainstalujte autentizační aplikaci</h3>
                    <p class="text-sm text-gray-600 mb-3">Doporučujeme jednu z těchto aplikací:</p>
                    <ul class="text-sm text-gray-700 list-disc list-inside space-y-1">
                        <li>Google Authenticator (Android, iOS)</li>
                        <li>Microsoft Authenticator (Android, iOS)</li>
                        <li>Authy (Android, iOS, Desktop)</li>
                    </ul>
                </div>

                <!-- Step 2: Scan QR Code -->
                <div>
                    <h3 class="font-semibold mb-2">2. Naskenujte QR kód</h3>
                    <p class="text-sm text-gray-600 mb-3">Použijte svou autentizační aplikaci k naskenování tohoto QR kódu:</p>

                    <?php if (!empty($qr_code)): ?>
                        <div class="bg-white p-4 inline-block border rounded">
                            <img src="<?php echo htmlspecialchars($qr_code); ?>" alt="QR Code" class="w-48 h-48">
                        </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <p class="text-xs text-gray-500 mb-1">Nebo zadejte tento kód ručně:</p>
                        <code class="bg-gray-100 px-3 py-2 rounded text-sm"><?php echo htmlspecialchars($secret ?? ''); ?></code>
                    </div>
                </div>

                <!-- Step 3: Verify -->
                <div>
                    <h3 class="font-semibold mb-2">3. Ověřte nastavení</h3>
                    <form method="POST" action="/settings/2fa/enable">
                        <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

                        <input type="hidden" name="secret" value="<?php echo htmlspecialchars($secret ?? ''); ?>">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Zadejte 6-místný kód z aplikace</label>
                            <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" class="w-full md:w-48 border rounded px-3 py-2 text-center text-2xl tracking-widest" placeholder="000000" required autofocus>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                                Aktivovat 2FA
                            </button>
                            <a href="/settings" class="bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded">
                                Zrušit
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Security Info -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mt-6">
        <h3 class="font-semibold text-yellow-900 mb-2">Bezpečnostní upozornění</h3>
        <ul class="text-sm text-yellow-800 list-disc list-inside space-y-1">
            <li>Nikdy nesdílejte své 2FA kódy s nikým</li>
            <li>Uložte záložní kódy na bezpečné místo</li>
            <li>Pokud ztratíte přístup k aplikaci, použijte záložní kód</li>
            <li>V případě problémů kontaktujte podporu</li>
        </ul>
    </div>
</div>

<script>
function printCodes() {
    window.print();
}
</script>
