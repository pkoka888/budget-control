<?php
/**
 * Bank JSON Import View
 * Import transactions from bank JSON files
 */
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import z banky - Budget Control</title>
    <link href="/css/main.css" rel="stylesheet">
</head>
<body class="bg-slate-gray-50">
    <?php include __DIR__ . '/../partials/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-gray-900">Import transakcí z banky</h1>
            <p class="mt-2 text-slate-gray-600">Importujte transakce z JSON souborů vašich bank</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-slate-gray-600">Dostupné soubory</p>
                <p class="mt-2 text-3xl font-bold text-blue-600">
                    <?php echo count($files ?? []); ?>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-slate-gray-600">Importované transakce</p>
                <p class="mt-2 text-3xl font-bold text-green-600">
                    <?php echo number_format($stats['imported_transactions'] ?? 0, 0, ',', ' '); ?>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <p class="text-sm font-medium text-slate-gray-600">Složka pro JSON</p>
                <p class="mt-2 text-sm text-slate-gray-700 font-mono break-all">
                    <?php echo htmlspecialchars($bankJsonFolder ?? '/var/www/html/user-data/bank-json'); ?>
                </p>
            </div>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 mb-8">
            <h3 class="text-lg font-semibold text-blue-900 mb-2">📋 Jak importovat transakce</h3>
            <ol class="list-decimal list-inside space-y-2 text-blue-800">
                <li>Stáhněte si JSON soubor s transakcemi z vašeho bankovního účtu</li>
                <li>Umístěte soubor do složky: <code class="bg-blue-100 px-2 py-1 rounded"><?php echo htmlspecialchars($bankJsonFolder ?? '/var/www/html/user-data/bank-json'); ?></code></li>
                <li>Soubor se automaticky zobrazí v seznamu níže</li>
                <li>Klikněte na "Importovat" u vybraného souboru</li>
                <li>Transakce budou automaticky přidány do vašeho účtu</li>
            </ol>
        </div>

        <!-- Supported Banks -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">🏦 Podporované banky</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center p-4 bg-slate-gray-50 rounded">
                    <div class="text-2xl mb-2">🏦</div>
                    <p class="font-medium">Česká spořitelna</p>
                    <p class="text-xs text-slate-gray-600">George API</p>
                </div>
                <div class="text-center p-4 bg-slate-gray-50 rounded">
                    <div class="text-2xl mb-2">🏦</div>
                    <p class="font-medium">Fio banka</p>
                    <p class="text-xs text-slate-gray-600">API export</p>
                </div>
                <div class="text-center p-4 bg-slate-gray-50 rounded">
                    <div class="text-2xl mb-2">🏦</div>
                    <p class="font-medium">ČSOB</p>
                    <p class="text-xs text-slate-gray-600">JSON export</p>
                </div>
                <div class="text-center p-4 bg-slate-gray-50 rounded">
                    <div class="text-2xl mb-2">🏦</div>
                    <p class="font-medium">Komerční banka</p>
                    <p class="text-xs text-slate-gray-600">API export</p>
                </div>
            </div>
        </div>

        <!-- Quick Import Button -->
        <div class="mb-8">
            <button id="auto-import-all-btn" class="btn btn-primary btn-lg">
                <span class="mr-2">⚡</span>
                Automaticky importovat všechny soubory
            </button>
            <p class="mt-2 text-sm text-slate-gray-600">
                Importuje všechny dostupné JSON soubory najednou. Duplicitní transakce budou automaticky přeskočeny.
            </p>
        </div>

        <!-- Available Files -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-slate-gray-200">
                <h2 class="text-xl font-semibold text-slate-gray-900">Dostupné JSON soubory</h2>
            </div>

            <div class="p-6">
                <?php if (empty($files)): ?>
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📂</div>
                        <h3 class="text-lg font-medium text-slate-gray-900 mb-2">Žádné soubory nenalezeny</h3>
                        <p class="text-slate-gray-600 mb-4">
                            Umístěte JSON soubory do složky:<br>
                            <code class="bg-slate-gray-100 px-3 py-1 rounded"><?php echo htmlspecialchars($bankJsonFolder ?? '/var/www/html/user-data/bank-json'); ?></code>
                        </p>
                        <p class="text-sm text-slate-gray-500">
                            Pokud složka neexistuje, vytvořte ji příkazem:<br>
                            <code class="bg-slate-gray-800 text-white px-3 py-1 rounded">mkdir -p <?php echo htmlspecialchars($bankJsonFolder ?? '/var/www/html/user-data/bank-json'); ?></code>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Název souboru
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Velikost
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Upraveno
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Akce
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($files as $file): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="text-2xl mr-3">📄</span>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($file['name']); ?>
                                                    </div>
                                                    <div class="text-xs text-gray-500 font-mono">
                                                        <?php echo htmlspecialchars($file['path']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo number_format($file['size'] / 1024, 1); ?> KB
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('d.m.Y H:i', $file['modified']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button class="import-file-btn btn btn-primary btn-sm"
                                                    data-filename="<?php echo htmlspecialchars($file['name']); ?>">
                                                Importovat
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Import Progress -->
        <div id="import-progress" class="hidden mt-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">Import probíhá...</h3>

                <div class="mb-4">
                    <div class="w-full bg-slate-gray-200 rounded-full h-4">
                        <div id="progress-bar" class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="progress-text" class="mt-2 text-sm text-slate-gray-600">Inicializace...</p>
                </div>

                <div id="import-results" class="hidden">
                    <div class="border-t pt-4">
                        <h4 class="font-semibold mb-2">Výsledky importu:</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span>Importováno transakcí:</span>
                                <span id="result-imported" class="font-bold text-green-600">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Přeskočeno (duplicity):</span>
                                <span id="result-skipped" class="font-bold text-yellow-600">0</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Celkem zpracováno:</span>
                                <span id="result-total" class="font-bold text-blue-600">0</span>
                            </div>
                        </div>
                    </div>

                    <div id="import-errors" class="hidden mt-4">
                        <h4 class="font-semibold text-red-600 mb-2">Chyby při importu:</h4>
                        <div id="error-list" class="bg-red-50 border border-red-200 rounded p-4 text-sm text-red-800 max-h-48 overflow-y-auto">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-slate-gray-900 mb-4">❓ Často kladené otázky</h3>

            <div class="space-y-4">
                <div>
                    <h4 class="font-medium text-slate-gray-900 mb-1">Jaký formát JSON souboru je podporován?</h4>
                    <p class="text-sm text-slate-gray-600">
                        Aplikace podporuje standardní formát JSON exportu z českých bank. Každá transakce by měla obsahovat:
                        <code class="bg-slate-gray-100 px-2 py-1 rounded">amount</code>,
                        <code class="bg-slate-gray-100 px-2 py-1 rounded">booking</code>,
                        <code class="bg-slate-gray-100 px-2 py-1 rounded">partnerName</code>, a
                        <code class="bg-slate-gray-100 px-2 py-1 rounded">referenceNumber</code>.
                    </p>
                </div>

                <div>
                    <h4 class="font-medium text-slate-gray-900 mb-1">Co se stane s duplicitními transakcemi?</h4>
                    <p class="text-sm text-slate-gray-600">
                        Transakce se kontrolují podle referenčního čísla. Pokud transakce již existuje, bude automaticky přeskočena.
                        To umožňuje opakovaný import bez rizika duplikátů.
                    </p>
                </div>

                <div>
                    <h4 class="font-medium text-slate-gray-900 mb-1">Jak se vytváří kategorie?</h4>
                    <p class="text-sm text-slate-gray-600">
                        Kategorie z bankovních dat jsou automaticky mapovány na kategorie v aplikaci. Pokud kategorie neexistuje,
                        vytvoří se nová s odpovídající barvou.
                    </p>
                </div>

                <div>
                    <h4 class="font-medium text-slate-gray-900 mb-1">Co když banka není v seznamu podporovaných?</h4>
                    <p class="text-sm text-slate-gray-600">
                        Pokud vaše banka exportuje transakce v JSON formátu podobném českým standardům, import by měl fungovat.
                        V opačném případě použijte CSV import s vlastním mapováním sloupců.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Bank JSON Import UI Handler
    class BankImportUI {
        constructor() {
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            this.init();
        }

        init() {
            // Import single file
            document.querySelectorAll('.import-file-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const filename = e.target.dataset.filename;
                    this.importFile(filename);
                });
            });

            // Auto import all files
            document.getElementById('auto-import-all-btn')?.addEventListener('click', () => {
                this.autoImportAll();
            });
        }

        async importFile(filename) {
            const progressDiv = document.getElementById('import-progress');
            progressDiv.classList.remove('hidden');

            document.getElementById('progress-bar').style.width = '30%';
            document.getElementById('progress-text').textContent = `Importuji ${filename}...`;

            try {
                const response = await fetch('/bank-import/import-file', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': this.csrfToken
                    },
                    body: `filename=${encodeURIComponent(filename)}`
                });

                const data = await response.json();

                document.getElementById('progress-bar').style.width = '100%';
                document.getElementById('progress-text').textContent = 'Import dokončen!';

                this.showResults(data);

            } catch (error) {
                console.error('Import error:', error);
                document.getElementById('progress-text').textContent = 'Chyba při importu!';
                document.getElementById('progress-text').className = 'mt-2 text-sm text-red-600';
            }
        }

        async autoImportAll() {
            if (!confirm('Importovat všechny dostupné JSON soubory?\n\nDuplicityní transakce budou automaticky přeskočeny.')) {
                return;
            }

            const progressDiv = document.getElementById('import-progress');
            progressDiv.classList.remove('hidden');

            document.getElementById('progress-bar').style.width = '20%';
            document.getElementById('progress-text').textContent = 'Spouštím automatický import...';

            try {
                const response = await fetch('/bank-import/auto-import', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': this.csrfToken
                    }
                });

                const data = await response.json();

                if (data.job_id) {
                    // Poll for job status
                    this.pollJobStatus(data.job_id);
                }

            } catch (error) {
                console.error('Auto import error:', error);
                document.getElementById('progress-text').textContent = 'Chyba při automatickém importu!';
                document.getElementById('progress-text').className = 'mt-2 text-sm text-red-600';
            }
        }

        async pollJobStatus(jobId) {
            const interval = setInterval(async () => {
                try {
                    const response = await fetch(`/bank-import/job-status?job_id=${jobId}`);
                    const data = await response.json();

                    if (data.status === 'completed') {
                        clearInterval(interval);
                        document.getElementById('progress-bar').style.width = '100%';
                        document.getElementById('progress-text').textContent = 'Import dokončen!';

                        if (data.results) {
                            this.showResults(data.results);
                        }

                        // Reload page after 3 seconds to show new data
                        setTimeout(() => location.reload(), 3000);

                    } else if (data.status === 'failed') {
                        clearInterval(interval);
                        document.getElementById('progress-text').textContent = 'Import selhal!';
                        document.getElementById('progress-text').className = 'mt-2 text-sm text-red-600';

                    } else {
                        // Update progress
                        const progress = data.progress || {};
                        const percentage = progress.total_files > 0
                            ? (progress.processed_files / progress.total_files * 100)
                            : 20;
                        document.getElementById('progress-bar').style.width = percentage + '%';
                        document.getElementById('progress-text').textContent =
                            `Zpracováno ${progress.processed_files} z ${progress.total_files} souborů...`;
                    }

                } catch (error) {
                    console.error('Status check error:', error);
                    clearInterval(interval);
                }
            }, 2000); // Check every 2 seconds
        }

        showResults(data) {
            const resultsDiv = document.getElementById('import-results');
            resultsDiv.classList.remove('hidden');

            document.getElementById('result-imported').textContent = data.imported_count || 0;
            document.getElementById('result-skipped').textContent = data.skipped_count || 0;
            document.getElementById('result-total').textContent = data.total_processed || 0;

            if (data.errors && data.errors.length > 0) {
                const errorsDiv = document.getElementById('import-errors');
                errorsDiv.classList.remove('hidden');

                const errorList = document.getElementById('error-list');
                errorList.innerHTML = data.errors.map(err =>
                    `<div class="mb-1">${err}</div>`
                ).join('');
            }
        }
    }

    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.bankImportUI = new BankImportUI();
        });
    } else {
        window.bankImportUI = new BankImportUI();
    }
    </script>
</body>
</html>
