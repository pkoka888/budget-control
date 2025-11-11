<?php
$pageTitle = 'Dvoufázové Ověření (2FA)';
$status = $status ?? ['enabled' => false, 'setup_complete' => false];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Budget Control</title>
    <?php echo \BudgetApp\Middleware\CsrfProtection::metaTag(); ?>
    <link rel="stylesheet" href="/css/output.css">
</head>
<body class="bg-slate-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="mb-8">
            <a href="/settings" class="text-primary-600 hover:text-primary-700 flex items-center mb-4" aria-label="Zpět na nastavení">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Zpět na nastavení
            </a>
            <h1 class="text-3xl font-bold text-slate-gray-900"><?php echo htmlspecialchars($pageTitle); ?></h1>
            <p class="text-slate-gray-600 mt-2">Zvy šte bezpečnost svého účtu pomocí dvoufázového ověření</p>
        </div>

        <!-- Alert Container -->
        <div id="alert-container" class="mb-6" role="alert" aria-live="assertive" aria-atomic="true"></div>

        <?php if ($status['enabled']): ?>
            <!-- 2FA is Enabled -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-slate-gray-900">Dvoufázové ověření je aktivní</h2>
                        <p class="text-slate-gray-600">Váš účet je chráněn pomocí TOTP kódu</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                    <!-- Backup Codes -->
                    <div class="bg-slate-gray-50 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 mr-2 text-slate-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            <h3 class="font-semibold text-slate-gray-900">Záložní Kódy</h3>
                        </div>
                        <p class="text-sm text-slate-gray-600 mb-3">
                            Zbývá: <strong><?php echo $status['backup_codes_remaining'] ?? 0; ?></strong> z 10
                        </p>
                        <button type="button" id="regenerate-backup-codes-btn"
                                class="btn btn-secondary btn-sm w-full"
                                aria-label="Vygenerovat nové záložní kódy">
                            Vygenerovat nové kódy
                        </button>
                    </div>

                    <!-- Trusted Devices -->
                    <div class="bg-slate-gray-50 p-4 rounded-lg">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 mr-2 text-slate-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="font-semibold text-slate-gray-900">Důvěryhodná Zařízení</h3>
                        </div>
                        <p class="text-sm text-slate-gray-600 mb-3">
                            Aktivních: <strong><?php echo $status['trusted_devices_count'] ?? 0; ?></strong>
                        </p>
                        <button type="button" id="manage-devices-btn"
                                class="btn btn-secondary btn-sm w-full"
                                aria-label="Spravovat důvěryhodná zařízení">
                            Spravovat zařízení
                        </button>
                    </div>
                </div>

                <!-- Disable 2FA -->
                <div class="mt-6 pt-6 border-t border-slate-gray-200">
                    <button type="button" id="disable-2fa-btn"
                            class="btn btn-danger"
                            aria-label="Deaktivovat dvoufázové ověření">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Deaktivovat 2FA
                    </button>
                </div>
            </div>
        <?php else: ?>
            <!-- 2FA is Not Enabled -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-slate-gray-900">Dvoufázové ověření není aktivní</h2>
                        <p class="text-slate-gray-600">Zvyšte zabezpečení svého účtu aktivací 2FA</p>
                    </div>
                </div>

                <div class="bg-blue-50 border-l-4 border-primary-600 p-4 mb-6" role="note">
                    <div class="flex">
                        <svg class="w-5 h-5 text-primary-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-sm text-primary-700">
                            <p class="font-semibold mb-1">Proč používat 2FA?</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Ochrana před neoprávněným přístupem</li>
                                <li>Vyšší bezpečnost než pouze heslo</li>
                                <li>Podporuje Google Authenticator a další TOTP aplikace</li>
                                <li>Obsahuje záložní kódy pro případ ztráty zařízení</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <button type="button" id="setup-2fa-btn"
                        class="btn btn-primary"
                        aria-label="Nastavit dvoufázové ověření">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Nastavit 2FA
                </button>
            </div>
        <?php endif; ?>

        <!-- Setup Modal -->
        <div id="setup-modal" class="modal hidden" role="dialog" aria-labelledby="setup-modal-title" aria-modal="true">
            <div class="modal-overlay"></div>
            <div class="modal-content max-w-2xl">
                <div class="modal-header">
                    <h3 id="setup-modal-title" class="modal-title">Nastavení Dvoufázového Ověření</h3>
                    <button type="button" class="modal-close" aria-label="Zavřít dialog">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="setup-step-1">
                        <h4 class="font-semibold text-lg mb-4">Krok 1: Naskenujte QR kód</h4>
                        <div class="text-center mb-6">
                            <div id="qr-code-container" class="inline-block p-4 bg-white border-2 border-slate-gray-200 rounded-lg">
                                <img id="qr-code" src="" alt="QR kód pro Google Authenticator" class="w-48 h-48">
                            </div>
                        </div>

                        <div class="bg-slate-gray-50 p-4 rounded-lg mb-6">
                            <h5 class="font-semibold text-sm mb-2">Manuální zadání:</h5>
                            <div class="flex items-center">
                                <code id="secret-code" class="flex-1 bg-white px-3 py-2 rounded border border-slate-gray-300 font-mono text-sm"></code>
                                <button type="button" id="copy-secret-btn" class="btn btn-secondary btn-sm ml-2" aria-label="Kopírovat tajný klíč">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="button" id="continue-to-verify-btn" class="btn btn-primary w-full">
                            Pokračovat k ověření
                        </button>
                    </div>

                    <div id="setup-step-2" class="hidden">
                        <h4 class="font-semibold text-lg mb-4">Krok 2: Ověřte TOTP kód</h4>
                        <p class="text-slate-gray-600 mb-4">Zadejte 6-místný kód z vaší autentikační aplikace:</p>

                        <form id="verify-totp-form">
                            <div class="form-group">
                                <label for="totp-code" class="form-label form-label-required">TOTP Kód</label>
                                <input type="text"
                                       id="totp-code"
                                       name="totp_code"
                                       class="form-input text-center text-2xl font-mono tracking-widest"
                                       placeholder="000000"
                                       maxlength="6"
                                       pattern="[0-9]{6}"
                                       required
                                       aria-required="true"
                                       aria-describedby="totp-help"
                                       autocomplete="off">
                                <p id="totp-help" class="mt-1 text-sm text-slate-gray-600">
                                    Kód je platný 30 sekund
                                </p>
                            </div>

                            <button type="submit" class="btn btn-primary w-full" id="verify-btn">
                                <span id="verify-btn-text">Ověřit a aktivovat</span>
                                <span id="verify-btn-loading" class="hidden" aria-live="polite">Ověřuji...</span>
                            </button>
                        </form>
                    </div>

                    <div id="setup-step-3" class="hidden">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h4 class="font-semibold text-lg text-green-600 mb-2">2FA aktivováno!</h4>
                            <p class="text-slate-gray-600">Uschovejte si následující záložní kódy na bezpečném místě</p>
                        </div>

                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6" role="alert">
                            <div class="flex">
                                <svg class="w-5 h-5 text-yellow-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div class="text-sm text-yellow-700">
                                    <strong>Důležité:</strong> Tyto kódy zobrazujeme pouze jednou. Použijte je, pokud ztratíte přístup k autentikační aplikaci.
                                </div>
                            </div>
                        </div>

                        <div id="backup-codes-container" class="bg-slate-gray-50 p-4 rounded-lg mb-6">
                            <div class="grid grid-cols-2 gap-2" id="backup-codes-list">
                                <!-- Backup codes will be inserted here -->
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" id="download-backup-codes-btn" class="btn btn-secondary flex-1">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Stáhnout kódy
                            </button>
                            <button type="button" id="finish-setup-btn" class="btn btn-primary flex-1">
                                Dokončit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disable 2FA Modal -->
        <div id="disable-modal" class="modal hidden" role="dialog" aria-labelledby="disable-modal-title" aria-modal="true">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="disable-modal-title" class="modal-title">Deaktivovat Dvoufázové Ověření</h3>
                    <button type="button" class="modal-close" aria-label="Zavřít dialog">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6" role="alert">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div class="text-sm text-red-700">
                                <strong>Varování:</strong> Deaktivací 2FA snížíte zabezpečení vašeho účtu. Pro potvrzení zadejte své heslo.
                            </div>
                        </div>
                    </div>

                    <form id="disable-2fa-form">
                        <div class="form-group">
                            <label for="disable-password" class="form-label form-label-required">Heslo</label>
                            <input type="password"
                                   id="disable-password"
                                   name="password"
                                   class="form-input"
                                   required
                                   aria-required="true"
                                   autocomplete="current-password">
                        </div>

                        <div class="flex gap-2">
                            <button type="button" class="btn btn-secondary flex-1 modal-close">
                                Zrušit
                            </button>
                            <button type="submit" class="btn btn-danger flex-1">
                                Deaktivovat 2FA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Regenerate Backup Codes Modal -->
        <div id="regenerate-modal" class="modal hidden" role="dialog" aria-labelledby="regenerate-modal-title" aria-modal="true">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="regenerate-modal-title" class="modal-title">Vygenerovat Nové Záložní Kódy</h3>
                    <button type="button" class="modal-close" aria-label="Zavřít dialog">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6" role="alert">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div class="text-sm text-yellow-700">
                                <strong>Upozornění:</strong> Generováním nových kódů zneplatníte všechny staré záložní kódy. Pro potvrzení zadejte své heslo.
                            </div>
                        </div>
                    </div>

                    <form id="regenerate-form">
                        <div class="form-group">
                            <label for="regenerate-password" class="form-label form-label-required">Heslo</label>
                            <input type="password"
                                   id="regenerate-password"
                                   name="password"
                                   class="form-input"
                                   required
                                   aria-required="true"
                                   autocomplete="current-password">
                        </div>

                        <div class="flex gap-2">
                            <button type="button" class="btn btn-secondary flex-1 modal-close">
                                Zrušit
                            </button>
                            <button type="submit" class="btn btn-primary flex-1">
                                Vygenerovat
                            </button>
                        </div>
                    </form>

                    <div id="new-backup-codes" class="hidden mt-6">
                        <div class="bg-slate-gray-50 p-4 rounded-lg mb-4">
                            <div class="grid grid-cols-2 gap-2" id="new-backup-codes-list">
                                <!-- New backup codes will be inserted here -->
                            </div>
                        </div>

                        <button type="button" id="download-new-codes-btn" class="btn btn-secondary w-full">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Stáhnout nové kódy
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trusted Devices Modal -->
        <div id="devices-modal" class="modal hidden" role="dialog" aria-labelledby="devices-modal-title" aria-modal="true">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="devices-modal-title" class="modal-title">Důvěryhodná Zařízení</h3>
                    <button type="button" class="modal-close" aria-label="Zavřít dialog">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="devices-list" class="space-y-4">
                        <!-- Devices will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/two-factor.js"></script>
</body>
</html>
