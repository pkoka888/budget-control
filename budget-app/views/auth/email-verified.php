<?php
$pageTitle = $success ? 'E-mail Ověřen' : 'Ověření Selhalo';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Budget Control</title>
    <link rel="stylesheet" href="/css/output.css">
    <?php if ($success): ?>
        <meta http-equiv="refresh" content="3;url=/">
    <?php endif; ?>
</head>
<body class="bg-slate-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="container mx-auto max-w-md">
        <div class="card">
            <div class="card-body text-center">
                <?php if ($success): ?>
                    <!-- Success State -->
                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-slate-gray-900 mb-3">
                        E-mail Úspěšně Ověřen!
                    </h1>

                    <p class="text-slate-gray-600 mb-6">
                        <?php echo htmlspecialchars($message ?? 'Váš e-mail byl úspěšně ověřen. Nyní máte přístup ke všem funkcím.'); ?>
                    </p>

                    <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6" role="status">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-green-700">
                                Budete automaticky přesměrováni za 3 sekundy...
                            </p>
                        </div>
                    </div>

                    <a href="/" class="btn btn-primary">
                        Přejít na Dashboard
                    </a>

                <?php else: ?>
                    <!-- Error State -->
                    <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>

                    <h1 class="text-2xl font-bold text-slate-gray-900 mb-3">
                        Ověření Selhalo
                    </h1>

                    <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6" role="alert">
                        <div class="flex">
                            <svg class="w-5 h-5 text-red-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div class="text-sm text-red-700">
                                <strong>Chyba:</strong>
                                <?php echo htmlspecialchars($error ?? 'Odkaz je neplatný nebo vypršel.'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="/email-verification" class="btn btn-primary w-full">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Odeslat nový ověřovací odkaz
                            </a>
                        <?php else: ?>
                            <a href="/login" class="btn btn-primary w-full">
                                Přihlásit se
                            </a>
                        <?php endif; ?>

                        <a href="/" class="btn btn-secondary w-full">
                            Zpět na hlavní stránku
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Help Section -->
        <?php if (!$success): ?>
            <div class="mt-6 text-center">
                <details class="bg-white rounded-lg shadow-sm p-4">
                    <summary class="cursor-pointer font-semibold text-slate-gray-900 hover:text-primary-600">
                        Potřebujete pomoc?
                    </summary>
                    <div class="mt-4 text-sm text-slate-gray-600 text-left space-y-2">
                        <p><strong>Možné příčiny:</strong></p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>Odkaz vypršel (platnost 24 hodin)</li>
                            <li>Odkaz byl již použit</li>
                            <li>E-mail byl již dříve ověřen</li>
                        </ul>
                        <p class="mt-4">
                            Kontaktujte podporu:
                            <a href="mailto:support@budget-control.app" class="text-primary-600 hover:underline">
                                support@budget-control.app
                            </a>
                        </p>
                    </div>
                </details>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
