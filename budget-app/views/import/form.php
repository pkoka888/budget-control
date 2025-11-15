<?php /** Import Form */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Import dat</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- CSV Import -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Import CSV</h2>
            <form method="POST" action="/import/csv" enctype="multipart/form-data">
                <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">CSV soubor</label>
                        <input type="file" name="csv_file" accept=".csv,.txt" class="w-full border rounded px-3 py-2" required>
                        <p class="text-xs text-gray-500 mt-1">Podporované formáty: CSV, TXT</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Oddělovač</label>
                        <select name="delimiter" class="w-full border rounded px-3 py-2">
                            <option value=",">, (čárka)</option>
                            <option value=";">; (středník)</option>
                            <option value="\t">Tab</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cílový účet</label>
                        <select name="account_id" class="w-full border rounded px-3 py-2" required>
                            <option value="">Vyberte účet</option>
                            <?php if (!empty($accounts)): ?>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars($account['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                        Importovat CSV
                    </button>
                </div>
            </form>
        </div>

        <!-- JSON Import (Bank Statements) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Import bankovního výpisu (JSON)</h2>
            <form method="POST" action="/import/bank-json" enctype="multipart/form-data">
                <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">JSON soubor</label>
                        <input type="file" name="json_file" accept=".json" class="w-full border rounded px-3 py-2" required>
                        <p class="text-xs text-gray-500 mt-1">Bankovní výpis ve formátu JSON</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Formát</label>
                        <select name="bank_format" class="w-full border rounded px-3 py-2">
                            <option value="csob">ČSOB</option>
                            <option value="kb">Komerční banka</option>
                            <option value="csas">Česká spořitelna</option>
                            <option value="moneta">Moneta</option>
                            <option value="generic">Obecný formát</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded">
                        Importovat JSON
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import History -->
    <?php if (!empty($import_history)): ?>
        <div class="bg-white rounded-lg shadow p-6 mt-8">
            <h2 class="text-xl font-semibold mb-4">Historie importů</h2>
            <div class="space-y-2">
                <?php foreach ($import_history as $import): ?>
                    <div class="flex justify-between items-center p-3 border-b">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($import['filename'] ?? ''); ?></p>
                            <p class="text-sm text-gray-500"><?php echo date('d.m.Y H:i', strtotime($import['imported_at'])); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium"><?php echo $import['transactions_imported'] ?? 0; ?> transakcí</p>
                            <span class="text-xs px-2 py-1 rounded <?php echo $import['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                <?php echo htmlspecialchars($import['status'] ?? ''); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Instructions -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
        <h3 class="font-semibold text-blue-900 mb-2">Formát CSV souboru</h3>
        <p class="text-sm text-blue-800 mb-2">CSV soubor by měl obsahovat následující sloupce:</p>
        <ul class="text-sm text-blue-700 list-disc list-inside">
            <li>Datum (DD.MM.YYYY, YYYY-MM-DD, nebo MM/DD/YYYY)</li>
            <li>Částka (použijte desetinnou tečku nebo čárku)</li>
            <li>Popis/Obchodník</li>
            <li>Kategorie (volitelné - auto-kategorizace je k dispozici)</li>
        </ul>
    </div>
</div>
