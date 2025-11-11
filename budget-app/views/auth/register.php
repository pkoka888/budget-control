<?php
// No layout for auth pages
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrace - Budget Control</title>
    <?php echo \BudgetApp\Middleware\CsrfProtection::metaTag(); ?>
    <!-- Tailwind CSS (Pre-compiled) -->
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-slate-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="card w-full max-w-md">
            <div class="card-header">
                <h1 class="text-center">Registrace</h1>
            </div>

            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error animate-slide-in-down mb-6" role="alert">
                        ✗ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/register">
                    <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

                    <div class="form-group">
                        <label for="name" class="form-label form-label-required">
                            Jméno
                        </label>
                        <input type="text" id="name" name="name" required class="form-input" placeholder="Vaše jméno" aria-required="true">
                    </div>

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
                        <input type="password" id="password" name="password" required minlength="8" class="form-input" placeholder="Minimálně 8 znaků" aria-required="true" aria-describedby="password-help">
                        <p id="password-help" class="mt-1 text-sm text-slate-gray-600">
                            Heslo musí mít alespoň 8 znaků
                        </p>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        Registrovat se
                    </button>
                </form>
            </div>

            <div class="card-footer">
                <p class="text-center text-slate-gray-600">
                    Už máte účet? <a href="/login" class="text-google-blue-600 hover:text-google-blue-700 font-medium">Přihlaste se</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
