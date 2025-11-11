<?php
$pageTitle = 'Šablony Rozpočtu';
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
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-gray-900"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <p class="text-slate-gray-600 mt-2">Vytvořte a spravujte šablony rozpočtu pro rychlé plánování</p>
            </div>
            <button type="button" id="create-template-btn" class="btn btn-primary" aria-label="Vytvořit novou šablonu">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Vytvořit šablonu
            </button>
        </div>

        <!-- Alert Container -->
        <div id="alert-container" class="mb-6" role="alert" aria-live="assertive" aria-atomic="true"></div>

        <!-- Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-4" role="tablist" aria-label="Typy šablon">
                <button type="button" id="tab-system" class="tab-button active" role="tab" aria-selected="true" aria-controls="panel-system">
                    Systémové šablony
                </button>
                <button type="button" id="tab-user" class="tab-button" role="tab" aria-selected="false" aria-controls="panel-user">
                    Moje šablony
                </button>
            </nav>
        </div>

        <!-- System Templates -->
        <div id="panel-system" role="tabpanel" aria-labelledby="tab-system">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="system-templates">
                <!-- System templates will be loaded here -->
            </div>
        </div>

        <!-- User Templates -->
        <div id="panel-user" class="hidden" role="tabpanel" aria-labelledby="tab-user">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="user-templates">
                <!-- User templates will be loaded here -->
            </div>
        </div>

        <!-- Template Detail Modal -->
        <div id="template-modal" class="modal hidden" role="dialog" aria-labelledby="template-modal-title" aria-modal="true">
            <div class="modal-overlay"></div>
            <div class="modal-content max-w-4xl">
                <div class="modal-header">
                    <h3 id="template-modal-title" class="modal-title">Detail Šablony</h3>
                    <button type="button" class="modal-close" aria-label="Zavřít dialog">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="template-detail">
                        <!-- Template detail will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Template Modal -->
        <div id="edit-template-modal" class="modal hidden" role="dialog" aria-labelledby="edit-template-modal-title" aria-modal="true">
            <div class="modal-overlay"></div>
            <div class="modal-content max-w-4xl">
                <div class="modal-header">
                    <h3 id="edit-template-modal-title" class="modal-title">Vytvořit Šablonu</h3>
                    <button type="button" class="modal-close" aria-label="Zavřít dialog">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="template-form">
                        <input type="hidden" id="template-id" name="id">

                        <div class="space-y-6">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label for="template-name" class="form-label form-label-required">Název šablony</label>
                                    <input type="text" id="template-name" name="name" class="form-input" required aria-required="true" placeholder="např. Rodinný rozpočet">
                                </div>

                                <div class="form-group">
                                    <label for="template-type" class="form-label form-label-required">Typ</label>
                                    <select id="template-type" name="template_type" class="form-input" required aria-required="true">
                                        <option value="single">Jednotlivec</option>
                                        <option value="family">Rodina</option>
                                        <option value="student">Student</option>
                                        <option value="retiree">Důchodce</option>
                                        <option value="minimalist">Minimalista</option>
                                        <option value="luxury">Luxusní</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="template-description" class="form-label">Popis</label>
                                <textarea id="template-description" name="description" class="form-input" rows="3" placeholder="Krátký popis šablony..."></textarea>
                            </div>

                            <!-- Categories -->
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-semibold text-slate-gray-900">Kategorie rozpočtu</h4>
                                    <button type="button" id="add-category-btn" class="btn btn-secondary btn-sm" aria-label="Přidat kategorii">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Přidat kategorii
                                    </button>
                                </div>

                                <div id="categories-container" class="space-y-3">
                                    <!-- Categories will be added here -->
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="bg-slate-gray-50 rounded-lg p-4">
                                <h4 class="font-semibold text-slate-gray-900 mb-3">Souhrn</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-slate-gray-600">Celkem kategorií:</span>
                                        <span class="font-semibold" id="summary-categories">0</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-gray-600">Celková částka:</span>
                                        <span class="font-semibold" id="summary-total">0 Kč</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-gray-600">% z příjmu:</span>
                                        <span class="font-semibold" id="summary-percentage">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="button" class="btn btn-secondary flex-1 modal-close">Zrušit</button>
                            <button type="submit" class="btn btn-primary flex-1">
                                <span id="save-template-text">Uložit šablonu</span>
                                <span id="save-template-loading" class="hidden" aria-live="polite">Ukládání...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Apply Template Modal -->
        <div id="apply-modal" class="modal hidden" role="dialog" aria-labelledby="apply-modal-title" aria-modal="true">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="apply-modal-title" class="modal-title">Použít Šablonu</h3>
                    <button type="button" class="modal-close" aria-label="Zavřít dialog">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="apply-form">
                        <input type="hidden" id="apply-template-id" name="template_id">

                        <div class="bg-blue-50 border-l-4 border-primary-600 p-4 mb-6" role="note">
                            <p class="text-sm text-primary-700">
                                Šablona bude aplikována na vybraný měsíc. Můžete upravit částky podle vašeho příjmu.
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="apply-month" class="form-label form-label-required">Měsíc</label>
                            <input type="month" id="apply-month" name="month" class="form-input" required aria-required="true">
                        </div>

                        <div class="form-group">
                            <label for="apply-income" class="form-label">Váš měsíční příjem (volitelné)</label>
                            <input type="number" id="apply-income" name="income" class="form-input" min="0" step="0.01" placeholder="Zadejte pro přepočet procentních částek">
                            <p class="mt-1 text-sm text-slate-gray-600">
                                Pokud zadáte příjem, procentní částky budou automaticky přepočítány
                            </p>
                        </div>

                        <div class="flex gap-3 mt-6">
                            <button type="button" class="btn btn-secondary flex-1 modal-close">Zrušit</button>
                            <button type="submit" class="btn btn-primary flex-1">
                                <span id="apply-text">Použít šablonu</span>
                                <span id="apply-loading" class="hidden" aria-live="polite">Aplikuji...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Export/Import -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-slate-gray-900 mb-4">Import & Export</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-medium text-slate-gray-900 mb-2">Exportovat šablonu</h3>
                    <p class="text-sm text-slate-gray-600 mb-3">Stáhněte šablonu jako JSON soubor pro sdílení nebo zálohu</p>
                    <button type="button" id="export-btn" class="btn btn-secondary w-full" disabled aria-label="Exportovat vybranou šablonu">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Exportovat
                    </button>
                    <p class="text-xs text-slate-gray-500 mt-2">Vyberte šablonu pro export</p>
                </div>

                <div>
                    <h3 class="font-medium text-slate-gray-900 mb-2">Importovat šablonu</h3>
                    <p class="text-sm text-slate-gray-600 mb-3">Nahrajte JSON soubor se šablonou rozpočtu</p>
                    <label for="import-file" class="btn btn-secondary w-full cursor-pointer inline-flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Vybrat soubor
                    </label>
                    <input type="file" id="import-file" class="hidden" accept=".json" aria-label="Vybrat soubor pro import">
                    <p class="text-xs text-slate-gray-500 mt-2" id="import-filename">Žádný soubor vybrán</p>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/budget-templates.js"></script>
</body>
</html>
