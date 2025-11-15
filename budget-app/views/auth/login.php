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
    <link rel="stylesheet" href="/assets/css/tailwind.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md">
            <div class="px-8 pt-8 pb-6 border-b">
                <h1 class="text-3xl font-bold text-center text-gray-800">Budget Control</h1>
                <p class="text-center text-gray-600 mt-2">Správa osobních financí</p>
            </div>

            <div class="p-8">
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6" role="alert">
                        <strong>✗</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Demo Account Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm font-semibold text-blue-900 mb-2">Demo účet:</p>
                    <div class="text-sm text-blue-800 space-y-1">
                        <p><strong>Email:</strong> demo@budgetcontrol.cz</p>
                        <p><strong>Heslo:</strong> DemoPassword123!</p>
                    </div>
                </div>

                <form method="POST" action="/login">
                    <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            E-mail *
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="vase@email.com"
                               value="demo@budgetcontrol.cz"
                               aria-required="true">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Heslo *
                        </label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Vaše heslo"
                               aria-required="true">
                    </div>

                    <div class="flex justify-end mb-6">
                        <a href="/forgot-password" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            Zapomněli jste heslo?
                        </a>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-md transition duration-200">
                        Přihlásit se
                    </button>
                </form>
            </div>

            <div class="px-8 pb-8 pt-6 border-t bg-gray-50 rounded-b-lg">
                <p class="text-center text-gray-600 text-sm">
                    Nemáte účet? <a href="/register" class="text-blue-600 hover:text-blue-800 font-medium">Registrujte se</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
