<?php
// No layout for auth pages
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zapomenuté heslo - Budget Control</title>
    <?php echo \BudgetApp\Middleware\CsrfProtection::metaTag(); ?>
    <!-- Tailwind CSS (Pre-compiled) -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-slate-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="card w-full max-w-md">
            <div class="card-header">
                <h1 class="text-center">Obnovení hesla</h1>
                <p class="text-center text-slate-gray-600 mt-2">
                    Zadejte svůj e-mail a pošleme vám odkaz pro obnovení hesla
                </p>
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

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success animate-slide-in-down mb-6" role="alert" aria-live="polite" aria-atomic="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/forgot-password" id="forgot-password-form">
                    <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

                    <div class="form-group">
                        <label for="email" class="form-label form-label-required">
                            E-mail
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            class="form-input"
                            placeholder="vase@email.com"
                            aria-required="true"
                            aria-describedby="email-help"
                        >
                        <p id="email-help" class="mt-1 text-sm text-slate-gray-600">
                            Použijte e-mail, se kterým jste se registrovali
                        </p>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        Odeslat odkaz pro obnovení
                    </button>
                </form>
            </div>

            <div class="card-footer">
                <div class="flex justify-between text-sm">
                    <a href="/login" class="text-google-blue-600 hover:text-google-blue-700 font-medium">
                        ← Zpět na přihlášení
                    </a>
                    <a href="/register" class="text-google-blue-600 hover:text-google-blue-700 font-medium">
                        Registrovat se
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add loading state to form submission
        document.getElementById('forgot-password-form').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            const email = document.getElementById('email');

            // Validate email format
            if (!email.validity.valid) {
                e.preventDefault();
                email.focus();
                email.setAttribute('aria-invalid', 'true');
                return false;
            }

            button.disabled = true;
            button.innerHTML = '<span aria-live="polite">Odesílání...</span>';
            button.setAttribute('aria-busy', 'true');
        });

        // Focus management for error messages
        window.addEventListener('DOMContentLoaded', function() {
            const errorAlert = document.querySelector('.alert-error');
            const successAlert = document.querySelector('.alert-success');

            if (errorAlert) {
                errorAlert.focus();
                errorAlert.setAttribute('tabindex', '-1');
            } else if (successAlert) {
                successAlert.focus();
                successAlert.setAttribute('tabindex', '-1');
            }
        });
    </script>
</body>
</html>
