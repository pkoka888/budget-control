<?php
/**
 * Import/Export Interface
 * CSV and Bank JSON import with mapping wizard
 */
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-gray-900">Import &amp; Export</h1>
        <p class="mt-2 text-slate-gray-600">Importujte transakce z CSV nebo bankovních exportů</p>
    </div>

    <!-- Tab Navigation -->
    <nav class="flex space-x-4 mb-8" role="tablist">
        <button id="tab-csv" class="tab-button active" role="tab" aria-selected="true" aria-controls="panel-csv">
            CSV Import
        </button>
        <button id="tab-bank" class="tab-button" role="tab" aria-selected="false" aria-controls="panel-bank">
            Bankovní JSON
        </button>
        <button id="tab-export" class="tab-button" role="tab" aria-selected="false" aria-controls="panel-export">
            Export
        </button>
    </nav>

    <!-- CSV Import Panel -->
    <div id="panel-csv" role="tabpanel" aria-labelledby="tab-csv">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-slate-gray-900 mb-4">CSV Import</h2>

            <form id="csv-upload-form" enctype="multipart/form-data">
                <div class="mb-6">
                    <label class="form-label form-label-required">Vyberte CSV soubor</label>
                    <div class="mt-2">
                        <input type="file" id="csv-file" name="file" accept=".csv" class="form-input" required>
                    </div>
                    <p class="mt-2 text-sm text-slate-gray-600">
                        Podporované formáty: CSV s oddělovači čárka (,) nebo středník (;)
                    </p>
                </div>

                <div class="mb-6">
                    <label class="form-label">Nastavení</label>
                    <div class="grid grid-cols-2 gap-4 mt-2">
                        <div>
                            <label for="csv-delimiter" class="block text-sm text-slate-gray-700">Oddělovač</label>
                            <select id="csv-delimiter" name="delimiter" class="form-input mt-1">
                                <option value=",">Čárka (,)</option>
                                <option value=";">Středník (;)</option>
                                <option value="\t">Tab</option>
                            </select>
                        </div>
                        <div>
                            <label for="csv-encoding" class="block text-sm text-slate-gray-700">Kódování</label>
                            <select id="csv-encoding" name="encoding" class="form-input mt-1">
                                <option value="UTF-8">UTF-8</option>
                                <option value="Windows-1250">Windows-1250</option>
                                <option value="ISO-8859-2">ISO-8859-2</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center mb-6">
                    <input type="checkbox" id="csv-has-header" name="has_header" class="form-checkbox" checked>
                    <label for="csv-has-header" class="ml-2 text-sm text-slate-gray-700">První řádek obsahuje záhlaví</label>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span id="upload-csv-text">Nahrát a pokračovat</span>
                    <span id="upload-csv-loading" class="hidden">Nahrávám...</span>
                </button>
            </form>
        </div>

        <!-- Mapping Step (shown after upload) -->
        <div id="csv-mapping-step" class="hidden mt-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Mapování sloupců</h3>
                <p class="text-sm text-slate-gray-600 mb-6">Přiřaďte sloupce z CSV k polím v aplikaci</p>

                <form id="csv-mapping-form">
                    <input type="hidden" id="upload-id" name="upload_id">

                    <div class="space-y-4" id="mapping-fields">
                        <!-- Dynamically populated -->
                    </div>

                    <div class="mt-6 flex items-center">
                        <input type="checkbox" id="skip-duplicates" name="skip_duplicates" class="form-checkbox" checked>
                        <label for="skip-duplicates" class="ml-2 text-sm text-slate-gray-700">Přeskočit duplicitní transakce</label>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">
                            <span id="import-csv-text">Importovat transakce</span>
                            <span id="import-csv-loading" class="hidden">Importuji...</span>
                        </button>
                        <button type="button" class="btn btn-secondary ml-2" onclick="location.reload()">Zrušit</button>
                    </div>
                </form>
            </div>

            <!-- Preview -->
            <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Náhled dat</h3>
                <div id="csv-preview" class="overflow-x-auto"></div>
            </div>
        </div>
    </div>

    <!-- Bank JSON Panel -->
    <div id="panel-bank" class="hidden" role="tabpanel" aria-labelledby="tab-bank">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-slate-gray-900 mb-4">Import bankovního JSON</h2>
            <p class="text-slate-gray-600 mb-6">
                Podporované banky: ČSOB, Česká spořitelna, Komerční banka, Fio banka
            </p>

            <form id="bank-upload-form" enctype="multipart/form-data">
                <div class="mb-6">
                    <label class="form-label form-label-required">Vyberte JSON soubor</label>
                    <div class="mt-2">
                        <input type="file" id="bank-file" name="file" accept=".json" class="form-input" required>
                    </div>
                </div>

                <div class="mb-6">
                    <label for="bank-type" class="form-label">Typ banky (volitelné)</label>
                    <select id="bank-type" name="bank_type" class="form-input">
                        <option value="">Automaticky detekovat</option>
                        <option value="csob">ČSOB</option>
                        <option value="ceska_sporitelna">Česká spořitelna</option>
                        <option value="komercni_banka">Komerční banka</option>
                        <option value="fio">Fio banka</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span id="upload-bank-text">Importovat</span>
                    <span id="upload-bank-loading" class="hidden">Importuji...</span>
                </button>
            </form>

            <!-- Import Results -->
            <div id="bank-import-results" class="hidden mt-6"></div>
        </div>
    </div>

    <!-- Export Panel -->
    <div id="panel-export" class="hidden" role="tabpanel" aria-labelledby="tab-export">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-slate-gray-900 mb-4">Export dat</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Transactions Export -->
                <div class="border border-slate-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Transakce</h3>

                    <form id="export-transactions-form">
                        <div class="space-y-4 mb-6">
                            <div>
                                <label for="export-start-date" class="form-label">Od data</label>
                                <input type="date" id="export-start-date" name="start_date" class="form-input">
                            </div>
                            <div>
                                <label for="export-end-date" class="form-label">Do data</label>
                                <input type="date" id="export-end-date" name="end_date" class="form-input">
                            </div>
                            <div>
                                <label for="export-format" class="form-label">Formát</label>
                                <select id="export-format" name="format" class="form-input">
                                    <option value="csv">CSV</option>
                                    <option value="xlsx">Excel (XLSX)</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-full">
                            Exportovat transakce
                        </button>
                    </form>
                </div>

                <!-- Budget Templates Export -->
                <div class="border border-slate-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Šablony rozpočtu</h3>
                    <p class="text-sm text-slate-gray-600 mb-6">
                        Exportujte své šablony rozpočtu pro zálohování nebo sdílení
                    </p>

                    <a href="/budgets/templates" class="btn btn-secondary w-full">
                        Správa šablon
                    </a>
                </div>

                <!-- Full Data Export -->
                <div class="border border-slate-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Kompletní export</h3>
                    <p class="text-sm text-slate-gray-600 mb-6">
                        Exportujte všechna data (transakce, účty, rozpočty, cíle)
                    </p>

                    <button id="full-export-btn" class="btn btn-primary w-full">
                        Exportovat všechna data
                    </button>
                </div>

                <!-- Settings Export -->
                <div class="border border-slate-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Nastavení</h3>
                    <p class="text-sm text-slate-gray-600 mb-6">
                        Export nastavení aplikace a preferencí
                    </p>

                    <a href="/settings/export" class="btn btn-secondary w-full">
                        Exportovat nastavení
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/import.js"></script>
