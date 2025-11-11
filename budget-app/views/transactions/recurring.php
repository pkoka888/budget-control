<?php
$pageTitle = 'Opakující se Transakce';
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
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-gray-900"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p class="text-slate-gray-600 mt-2">Spravujte pravidelné příjmy a výdaje</p>
            </div>
            <button type="button" id="add-recurring-btn" class="btn btn-primary" aria-label="Přidat opakující se transakci">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Přidat
            </button>
        </div>

        <!-- Alert Container -->
        <div id="alert-container" class="mb-6" role="alert" aria-live="assertive" aria-atomic="true"></div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-2">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-slate-gray-600">Měsíční Příjmy</h3>
                </div>
                <p class="text-2xl font-bold text-slate-gray-900" id="monthly-income">0 Kč</p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-2">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-slate-gray-600">Měsíční Výdaje</h3>
                </div>
                <p class="text-2xl font-bold text-slate-gray-900" id="monthly-expenses">0 Kč</p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-2">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-slate-gray-600">Aktivních Transakcí</h3>
                </div>
                <p class="text-2xl font-bold text-slate-gray-900" id="active-count">0</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="border-b border-slate-gray-200">
                <nav class="flex -mb-px" role="tablist" aria-label="Typy opakujících se transakcí">
                    <button type="button" id="tab-active" class="tab-button active" role="tab" aria-selected="true" aria-controls="panel-active">
                        Aktivní
                    </button>
                    <button type="button" id="tab-inactive" class="tab-button" role="tab" aria-selected="false" aria-controls="panel-inactive">
                        Neaktivní
                    </button>
                    <button type="button" id="tab-upcoming" class="tab-button" role="tab" aria-selected="false" aria-controls="panel-upcoming">
                        Nadcházející
                    </button>
                </nav>
            </div>

            <!-- Active Transactions -->
            <div id="panel-active" class="p-6" role="tabpanel" aria-labelledby="tab-active">
                <div id="active-list" class="space-y-4">
                    <!-- Transactions will be loaded here -->
                </div>
            </div>

            <!-- Inactive Transactions -->
            <div id="panel-inactive" class="p-6 hidden" role="tabpanel" aria-labelledby="tab-inactive">
                <div id="inactive-list" class="space-y-4">
                    <!-- Transactions will be loaded here -->
                </div>
            </div>

            <!-- Upcoming Transactions -->
            <div id="panel-upcoming" class="p-6 hidden" role="tabpanel" aria-labelledby="tab-upcoming">
                <div id="upcoming-calendar" class="space-y-4">
                    <!-- Calendar preview will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <div id="recurring-modal" class="modal hidden" role="dialog" aria-labelledby="recurring-modal-title" aria-modal="true">
            <div class="modal-overlay"></div>
            <div class="modal-content max-w-2xl">
                <div class="modal-header">
                    <h3 id="recurring-modal-title" class="modal-title">Přidat Opakující se Transakci</h3>
                    <button type="button" class="modal-close" aria-label="Zavřít dialog">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="recurring-form">
                        <input type="hidden" id="recurring-id" name="id">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group md:col-span-2">
                                <label for="description" class="form-label form-label-required">Popis</label>
                                <input type="text" id="description" name="description" class="form-input" required aria-required="true" placeholder="např. Nájem">
                            </div>

                            <div class="form-group">
                                <label for="type" class="form-label form-label-required">Typ</label>
                                <select id="type" name="type" class="form-input" required aria-required="true">
                                    <option value="expense">Výdaj</option>
                                    <option value="income">Příjem</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="amount" class="form-label form-label-required">Částka</label>
                                <input type="number" id="amount" name="amount" class="form-input" required aria-required="true" min="0" step="0.01" placeholder="0.00">
                            </div>

                            <div class="form-group">
                                <label for="frequency" class="form-label form-label-required">Frekvence</label>
                                <select id="frequency" name="frequency" class="form-input" required aria-required="true">
                                    <option value="daily">Denně</option>
                                    <option value="weekly">Týdně</option>
                                    <option value="bi-weekly">Každé 2 týdny</option>
                                    <option value="monthly" selected>Měsíčně</option>
                                    <option value="quarterly">Čtvrtletně</option>
                                    <option value="yearly">Ročně</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="next-due-date" class="form-label form-label-required">Další datum</label>
                                <input type="date" id="next-due-date" name="next_due_date" class="form-input" required aria-required="true">
                            </div>

                            <div class="form-group">
                                <label for="account-id" class="form-label form-label-required">Účet</label>
                                <select id="account-id" name="account_id" class="form-input" required aria-required="true">
                                    <option value="">Vyberte účet...</option>
                                    <!-- Accounts will be loaded dynamically -->
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="category-id" class="form-label">Kategorie</label>
                                <select id="category-id" name="category_id" class="form-input">
                                    <option value="">Vyberte kategorii...</option>
                                    <!-- Categories will be loaded dynamically -->
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="flex items-center">
                                <input type="checkbox" id="is-active" name="is_active" class="form-checkbox" checked>
                                <span class="ml-2 text-sm text-slate-gray-700">Aktivní</span>
                            </label>
                        </div>

                        <!-- Preview -->
                        <div class="bg-blue-50 border-l-4 border-primary-600 p-4 mt-6" role="region" aria-label="Náhled opakování">
                            <h4 class="font-semibold text-sm text-primary-900 mb-2">Náhled opakování:</h4>
                            <p class="text-sm text-primary-700" id="recurrence-preview">-</p>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="button" class="btn btn-secondary flex-1 modal-close">Zrušit</button>
                            <button type="submit" class="btn btn-primary flex-1">
                                <span id="save-text">Uložit</span>
                                <span id="save-loading" class="hidden" aria-live="polite">Ukládání...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/recurring-transactions.js"></script>
</body>
</html>
