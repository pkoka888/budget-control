<?php
// No layout for auth pages
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nové heslo - Budget Control</title>
    <?php echo \BudgetApp\Middleware\CsrfProtection::metaTag(); ?>
    <!-- Tailwind CSS (Pre-compiled) -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-slate-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="card w-full max-w-md">
            <div class="card-header">
                <h1 class="text-center">Nastavení nového hesla</h1>
                <?php if (!empty($email)): ?>
                    <p class="text-center text-slate-gray-600 mt-2 text-sm">
                        Pro účet: <strong><?php echo htmlspecialchars($email); ?></strong>
                    </p>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error animate-slide-in-down mb-6" role="alert" aria-live="assertive" aria-atomic="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($token)): ?>
                    <form method="POST" action="/reset-password" id="reset-password-form">
                        <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="form-group">
                            <label for="password" class="form-label form-label-required">
                                Nové heslo
                            </label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                minlength="8"
                                class="form-input"
                                placeholder="Minimálně 8 znaků"
                                aria-required="true"
                                aria-describedby="password-help password-strength"
                            >
                            <p id="password-help" class="mt-1 text-sm text-slate-gray-600">
                                Heslo musí mít alespoň 8 znaků
                            </p>
                            <div id="password-strength" class="mt-2 hidden">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-slate-gray-200 rounded-full overflow-hidden">
                                        <div id="strength-bar" class="h-full transition-all duration-300"></div>
                                    </div>
                                    <span id="strength-text" class="text-xs font-medium"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirm" class="form-label form-label-required">
                                Potvrzení hesla
                            </label>
                            <input
                                type="password"
                                id="password_confirm"
                                name="password_confirm"
                                required
                                minlength="8"
                                class="form-input"
                                placeholder="Zadejte heslo znovu"
                                aria-required="true"
                                aria-describedby="confirm-help"
                            >
                            <p id="confirm-help" class="mt-1 text-sm text-slate-gray-600 hidden" role="alert">
                                <!-- Dynamically updated -->
                            </p>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            Nastavit nové heslo
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-slate-gray-600 mb-4">
                            Nemůžete nastavit nové heslo. Odkaz může být neplatný nebo vypršel.
                        </p>
                        <a href="/forgot-password" class="btn btn-primary">
                            Požádat o nový odkaz
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <p class="text-center text-sm">
                    <a href="/login" class="text-google-blue-600 hover:text-google-blue-700 font-medium">
                        ← Zpět na přihlášení
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('password_confirm');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        const strengthContainer = document.getElementById('password-strength');
        const confirmHelp = document.getElementById('confirm-help');

        passwordInput?.addEventListener('input', function() {
            const password = this.value;

            if (password.length > 0) {
                strengthContainer.classList.remove('hidden');

                let strength = 0;
                if (password.length >= 8) strength++;
                if (password.length >= 12) strength++;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z0-9]/.test(password)) strength++;

                const colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-lime-500', 'bg-green-500'];
                const texts = ['Velmi slabé', 'Slabé', 'Střední', 'Silné', 'Velmi silné'];
                const widths = ['20%', '40%', '60%', '80%', '100%'];

                strengthBar.className = 'h-full transition-all duration-300 ' + colors[strength];
                strengthBar.style.width = widths[strength];
                strengthText.textContent = texts[strength];
            } else {
                strengthContainer.classList.add('hidden');
            }
        });

        // Password confirmation validation
        confirmInput?.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirm = this.value;

            if (confirm.length > 0) {
                if (password === confirm) {
                    confirmHelp.classList.remove('hidden');
                    confirmHelp.className = 'mt-1 text-sm text-green-600';
                    confirmHelp.textContent = '✓ Hesla se shodují';
                } else {
                    confirmHelp.classList.remove('hidden');
                    confirmHelp.className = 'mt-1 text-sm text-red-600';
                    confirmHelp.textContent = '✗ Hesla se neshodují';
                }
            } else {
                confirmHelp.classList.add('hidden');
            }
        });

        // Form submission handling
        document.getElementById('reset-password-form')?.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirm = confirmInput.value;

            if (password !== confirm) {
                e.preventDefault();
                confirmHelp.classList.remove('hidden');
                confirmHelp.className = 'mt-1 text-sm text-red-600';
                confirmHelp.setAttribute('role', 'alert');
                confirmHelp.setAttribute('aria-live', 'assertive');
                confirmHelp.textContent = '✗ Hesla se musí shodovat';
                confirmInput.setAttribute('aria-invalid', 'true');
                confirmInput.focus();
                return false;
            }

            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.innerHTML = '<span aria-live="polite">Nastavuji heslo...</span>';
            button.setAttribute('aria-busy', 'true');
        });

        // Focus management for error messages
        window.addEventListener('DOMContentLoaded', function() {
            const errorAlert = document.querySelector('.alert-error');
            if (errorAlert) {
                errorAlert.focus();
                errorAlert.setAttribute('tabindex', '-1');
            }
        });
    </script>
</body>
</html>
