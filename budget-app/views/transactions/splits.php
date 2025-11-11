<?php
$transaction = $transaction ?? null;
$splits = $splits ?? [];
$pageTitle = $transaction ? 'Rozdělit Transakci' : 'Transakce Nenalezena';
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
        <?php if (!$transaction): ?>
            <!-- Transaction Not Found -->
            <div class="card">
                <div class="card-body text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-gray-900 mb-2">Transakce nenalezena</h1>
                    <p class="text-slate-gray-600 mb-6">Požadovaná transakce neexistuje nebo k ní nemáte přístup.</p>
                    <a href="/transactions" class="btn btn-primary">Zpět na transakce</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Header -->
            <div class="mb-8">
                <a href="/transactions" class="text-primary-600 hover:text-primary-700 flex items-center mb-4" aria-label="Zpět na transakce">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Zpět na transakce
                </a>
                <h1 class="text-3xl font-bold text-slate-gray-900">Rozdělit Transakci</h1>
                <p class="text-slate-gray-600 mt-2">Rozdělte transakci mezi více kategorií</p>
            </div>

            <!-- Alert Container -->
            <div id="alert-container" class="mb-6" role="alert" aria-live="assertive" aria-atomic="true"></div>

            <!-- Original Transaction Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-lg font-semibold text-slate-gray-900 mb-4">Původní Transakce</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-slate-gray-600">Popis</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($transaction['description']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-gray-600">Datum</p>
                        <p class="font-semibold"><?php echo date('d.m.Y', strtotime($transaction['date'])); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-slate-gray-600">Celková částka</p>
                        <p class="font-semibold text-2xl" id="total-amount">
                            <?php echo number_format(abs($transaction['amount']), 2, ',', ' '); ?> Kč
                        </p>
                        <input type="hidden" id="original-amount" value="<?php echo abs($transaction['amount']); ?>">
                    </div>
                    <div>
                        <p class="text-sm text-slate-gray-600">Původní kategorie</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($transaction['category_name'] ?? 'Bez kategorie'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Split Form -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-gray-900">Rozdělení</h2>
                    <button type="button" id="add-split-btn" class="btn btn-secondary btn-sm" aria-label="Přidat rozdělení">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Přidat řádek
                    </button>
                </div>

                <form id="splits-form">
                    <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">

                    <div id="splits-container" class="space-y-4 mb-6">
                        <?php if (empty($splits)): ?>
                            <!-- Default first split -->
                            <div class="split-row" data-split-index="0">
                                <div class="grid grid-cols-12 gap-3 items-start">
                                    <div class="col-span-12 md:col-span-6">
                                        <label class="form-label form-label-required">Kategorie</label>
                                        <select name="splits[0][category_id]" class="form-input split-category" required aria-required="true">
                                            <option value="">Vyberte kategorii...</option>
                                            <!-- Categories will be loaded dynamically -->
                                        </select>
                                    </div>
                                    <div class="col-span-10 md:col-span-4">
                                        <label class="form-label form-label-required">Částka</label>
                                        <input type="number" name="splits[0][amount]" class="form-input split-amount" required aria-required="true" min="0.01" step="0.01" placeholder="0.00">
                                    </div>
                                    <div class="col-span-2 md:col-span-2 flex items-end">
                                        <button type="button" class="btn btn-danger btn-sm w-full remove-split-btn" aria-label="Odstranit řádek" disabled>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="col-span-12">
                                        <label class="form-label">Poznámka</label>
                                        <input type="text" name="splits[0][description]" class="form-input" placeholder="Volitelné...">
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($splits as $index => $split): ?>
                                <div class="split-row" data-split-index="<?php echo $index; ?>">
                                    <div class="grid grid-cols-12 gap-3 items-start">
                                        <div class="col-span-12 md:col-span-6">
                                            <label class="form-label form-label-required">Kategorie</label>
                                            <select name="splits[<?php echo $index; ?>][category_id]" class="form-input split-category" required>
                                                <option value="">Vyberte kategorii...</option>
                                                <!-- Categories will be loaded with selected value -->
                                            </select>
                                        </div>
                                        <div class="col-span-10 md:col-span-4">
                                            <label class="form-label form-label-required">Částka</label>
                                            <input type="number" name="splits[<?php echo $index; ?>][amount]" class="form-input split-amount" required value="<?php echo $split['amount']; ?>" min="0.01" step="0.01">
                                        </div>
                                        <div class="col-span-2 md:col-span-2 flex items-end">
                                            <button type="button" class="btn btn-danger btn-sm w-full remove-split-btn" aria-label="Odstranit řádek">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="col-span-12">
                                            <label class="form-label">Poznámka</label>
                                            <input type="text" name="splits[<?php echo $index; ?>][description]" class="form-input" value="<?php echo htmlspecialchars($split['description'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Summary -->
                    <div class="bg-slate-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-slate-gray-700">Celkem rozděleno:</span>
                            <span class="text-lg font-bold" id="allocated-amount">0,00 Kč</span>
                        </div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-slate-gray-700">Zbývá:</span>
                            <span class="text-lg font-bold" id="remaining-amount">
                                <?php echo number_format(abs($transaction['amount']), 2, ',', ' '); ?> Kč
                            </span>
                        </div>
                        <div class="mt-3">
                            <div class="w-full bg-slate-gray-200 rounded-full h-2.5">
                                <div id="progress-bar" class="bg-primary-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Validation Messages -->
                    <div id="validation-messages" class="mb-6" role="alert" aria-live="polite"></div>

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <a href="/transactions" class="btn btn-secondary flex-1">Zrušit</a>
                        <button type="submit" id="save-btn" class="btn btn-primary flex-1">
                            <span id="save-text">Uložit rozdělení</span>
                            <span id="save-loading" class="hidden" aria-live="polite">Ukládání...</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Help Card -->
            <div class="bg-blue-50 border-l-4 border-primary-600 p-4 rounded-lg">
                <div class="flex">
                    <svg class="w-5 h-5 text-primary-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="text-sm text-primary-700">
                        <p class="font-semibold mb-1">Tipy pro rozdělení transakcí:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Celková částka rozdělení musí odpovídat původní transakci</li>
                            <li>Můžete přidat poznámku ke každému rozdělení</li>
                            <li>Rozdělení můžete kdykoli upravit nebo odstranit</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($transaction): ?>
        <script>
            window.transactionData = <?php echo json_encode([
                'id' => $transaction['id'],
                'amount' => abs($transaction['amount']),
                'splits' => $splits
            ]); ?>;
        </script>
        <script src="/js/transaction-splits.js"></script>
    <?php endif; ?>
</body>
</html>
