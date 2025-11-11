<?php
// No layout for auth pages
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení - Budget Control</title>
    <?php echo \BudgetApp\Middleware\CsrfProtection::metaTag(); ?>
    <!-- Tailwind CSS (Pre-compiled) -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-slate-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="card w-full max-w-md">
            <div class="card-header">
                <h1 class="text-center">Budget Control</h1>
            </div>

            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error animate-slide-in-down mb-6" role="alert">
                        ✗ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/login">
                    <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

                    <div class="form-group">
                        <label for="email" class="form-label form-label-required">
                            E-mail
                        </label>
                        <input type="email" id="email" name="email" required class="form-input" placeholder="vase@email.com" aria-required="true">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label form-label-required">
                            Heslo
                        </label>
                        <input type="password" id="password" name="password" required class="form-input" placeholder="Vaše heslo" aria-required="true">
                    </div>

                    <div class="flex justify-end mb-4">
                        <a href="/forgot-password" class="text-sm text-google-blue-600 hover:text-google-blue-700 font-medium">
                            Zapomněli jste heslo?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        Přihlásit se
                    </button>
                </form>
            </div>

            <div class="card-footer">
                <p class="text-center text-slate-gray-600">
                    Nemáte účet? <a href="/register" class="text-google-blue-600 hover:text-google-blue-700 font-medium">Registrujte se</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
