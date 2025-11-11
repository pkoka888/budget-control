<?php
$pageTitle = 'Ověření E-mailu';
$status = $status ?? ['verified' => false, 'can_resend' => true];
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
<body class="bg-slate-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="container mx-auto max-w-md">
        <div class="card">
            <div class="card-header text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h1 class="card-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p class="text-slate-gray-600">Ověřte svou e-mailovou adresu pro pokračování</p>
            </div>

            <div class="card-body">
                <!-- Alert Container -->
                <div id="alert-container" class="mb-4" role="alert" aria-live="polite" aria-atomic="true"></div>

                <div class="bg-blue-50 border-l-4 border-primary-600 p-4 mb-6" role="note">
                    <div class="flex">
                        <svg class="w-5 h-5 text-primary-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-sm text-primary-700">
                            <p class="font-semibold mb-2">Byl vám odeslán ověřovací e-mail</p>
                            <p class="mb-1">E-mail: <strong><?php echo htmlspecialchars($status['email'] ?? ''); ?></strong></p>
                            <p>Klikněte na odkaz v e-mailu pro ověření vaší adresy.</p>
                        </div>
                    </div>
                </div>

                <?php if ($status['verification_sent_at']): ?>
                    <div class="bg-slate-gray-50 p-4 rounded-lg mb-6">
                        <p class="text-sm text-slate-gray-600 mb-1">
                            <strong>Odeslán:</strong>
                            <?php echo date('d.m.Y H:i', strtotime($status['verification_sent_at'])); ?>
                        </p>
                        <?php if ($status['pending_token_expires_at']): ?>
                            <p class="text-sm text-slate-gray-600">
                                <strong>Vyprší:</strong>
                                <?php echo date('d.m.Y H:i', strtotime($status['pending_token_expires_at'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <button type="button"
                        id="resend-verification-btn"
                        class="btn btn-primary w-full mb-4"
                        <?php echo !$status['can_resend'] ? 'disabled' : ''; ?>
                        aria-label="Znovu odeslat ověřovací e-mail">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span id="resend-text">Odeslat e-mail znovu</span>
                    <span id="resend-loading" class="hidden" aria-live="polite">Odesílání...</span>
                </button>

                <?php if (!$status['can_resend']): ?>
                    <p class="text-sm text-slate-gray-600 text-center mb-4">
                        Příliš mnoho pokusů. Zkuste to prosím později.
                    </p>
                <?php endif; ?>

                <div class="text-center">
                    <a href="/logout" class="text-sm text-slate-gray-600 hover:text-slate-gray-900 underline">
                        Odhlásit se
                    </a>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-6 text-center">
            <details class="bg-white rounded-lg shadow-sm p-4">
                <summary class="cursor-pointer font-semibold text-slate-gray-900 hover:text-primary-600">
                    Neobdrželi jste e-mail?
                </summary>
                <div class="mt-4 text-sm text-slate-gray-600 text-left space-y-2">
                    <p><strong>Zkontrolujte:</strong></p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Složku spam/nevyžádaná pošta</li>
                        <li>Správnost e-mailové adresy</li>
                        <li>Zda váš e-mailový poskytovatel neblokuje e-maily</li>
                    </ul>
                    <p class="mt-4">
                        Stále máte problémy? Kontaktujte podporu na
                        <a href="mailto:support@budget-control.app" class="text-primary-600 hover:underline">support@budget-control.app</a>
                    </p>
                </div>
            </details>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        document.getElementById('resend-verification-btn')?.addEventListener('click', async function() {
            const btn = this;
            const textSpan = document.getElementById('resend-text');
            const loadingSpan = document.getElementById('resend-loading');

            btn.disabled = true;
            btn.setAttribute('aria-busy', 'true');
            textSpan.classList.add('hidden');
            loadingSpan.classList.remove('hidden');

            try {
                const response = await fetch('/api/email/resend', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Odeslání selhalo');
                }

                showAlert('Ověřovací e-mail byl úspěšně odeslán!', 'success');

                // Disable button for 60 seconds
                let countdown = 60;
                const interval = setInterval(() => {
                    countdown--;
                    textSpan.textContent = `Zkuste znovu za ${countdown}s`;

                    if (countdown <= 0) {
                        clearInterval(interval);
                        btn.disabled = false;
                        textSpan.textContent = 'Odeslat e-mail znovu';
                    }
                }, 1000);

            } catch (error) {
                showAlert(error.message, 'error');
                btn.disabled = false;
            } finally {
                btn.setAttribute('aria-busy', 'false');
                textSpan.classList.remove('hidden');
                loadingSpan.classList.add('hidden');
            }
        });

        function showAlert(message, type = 'info') {
            const container = document.getElementById('alert-container');
            if (!container) return;

            const alertClass = {
                success: 'alert-success',
                error: 'alert-error',
                warning: 'alert-warning',
                info: 'alert-info'
            }[type] || 'alert-info';

            const icon = {
                success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>',
                error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
                info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
            }[type];

            const div = document.createElement('div');
            div.textContent = message;
            const escapedMessage = div.innerHTML;

            container.innerHTML = `
                <div class="alert ${alertClass} animate-slide-in-down">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        ${icon}
                    </svg>
                    ${escapedMessage}
                </div>
            `;

            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
