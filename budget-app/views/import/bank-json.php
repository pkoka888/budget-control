<?php
// Bank JSON Import View
$title = 'Import Bank Transactions';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-3xl font-bold text-slate-gray-900">Import Bank Data</h2>
            <p class="text-slate-gray-600 mt-2">Automatically load and process bank transaction JSON files</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="card bg-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-gray-600 text-sm font-medium">Imported Transactions</p>
                    <p class="text-3xl font-bold text-google-blue-600 mt-2"><?php echo $stats['imported_transactions'] ?? 0; ?></p>
                </div>
                <div class="text-4xl text-google-blue-100">📊</div>
            </div>
        </div>

        <div class="card bg-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-gray-600 text-sm font-medium">Available JSON Files</p>
                    <p class="text-3xl font-bold text-google-green-600 mt-2"><?php echo $stats['available_files'] ?? 0; ?></p>
                </div>
                <div class="text-4xl text-google-green-100">📁</div>
            </div>
        </div>
    </div>

    <!-- Instructions Card -->
    <div class="card bg-google-blue-50 border-l-4 border-google-blue-500">
        <h3 class="font-bold text-slate-gray-900 mb-3">How to use:</h3>
        <ol class="space-y-2 text-sm text-slate-gray-700">
            <li>1. <strong>Prepare your files:</strong> Place bank JSON exports in the following folder:</li>
            <li class="bg-white p-3 rounded font-mono text-xs break-all"><?php echo htmlspecialchars($bankJsonFolder ?? '/user-data/bank-json'); ?></li>
            <li class="mt-2">2. <strong>Click "Auto-Import All"</strong> to process all files in the folder</li>
            <li>3. <strong>Transactions are automatically:</strong>
                <ul class="ml-4 mt-1 space-y-1">
                    <li>✓ Categorized based on bank data</li>
                    <li>✓ Assigned to accounts</li>
                    <li>✓ Checked for duplicates (same reference number)</li>
                </ul>
            </li>
        </ol>
    </div>

    <!-- Auto-Import Button -->
    <?php if ($stats['available_files'] > 0): ?>
        <div class="card bg-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-gray-900">Import all available files</h3>
                    <p class="text-slate-gray-600 text-sm mt-1">Process <?php echo $stats['available_files']; ?> JSON file<?php echo $stats['available_files'] !== 1 ? 's' : ''; ?> at once</p>
                </div>
                <button id="autoImportBtn" class="btn btn-primary" type="button">
                    <span class="mr-2">▶</span> Auto-Import All
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="card bg-google-yellow-50 border-l-4 border-google-yellow-500">
            <p class="text-slate-gray-900 font-medium">
                ⚠️ No JSON files found in the bank-json folder
            </p>
            <p class="text-sm text-slate-gray-700 mt-2">
                Copy your bank JSON exports to: <code class="bg-white px-2 py-1 rounded"><?php echo htmlspecialchars($bankJsonFolder ?? '/user-data/bank-json'); ?></code>
            </p>
        </div>
    <?php endif; ?>

    <!-- File List -->
    <?php if (!empty($files)): ?>
        <div class="card bg-white">
            <h3 class="font-bold text-slate-gray-900 mb-4">Available files (<?php echo count($files); ?>)</h3>
            <div class="space-y-2">
                <?php foreach ($files as $file): ?>
                    <div class="flex items-center justify-between p-3 bg-slate-gray-50 rounded-lg border border-slate-gray-200">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            <span class="text-lg" aria-hidden="true">📄</span>
                            <div class="min-w-0">
                                <p class="font-medium text-slate-gray-900 truncate"><?php echo htmlspecialchars($file['name']); ?></p>
                                <p class="text-xs text-slate-gray-600">
                                    <?php echo round($file['size'] / 1024); ?> KB
                                    • Modified: <?php echo date('d.m.Y H:i', $file['modified']); ?>
                                </p>
                            </div>
                        </div>
                        <button class="btn btn-secondary btn-sm importFileBtn" type="button" data-filename="<?php echo htmlspecialchars($file['name']); ?>">
                            Import
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Import Progress -->
    <div id="importProgress" class="hidden">
        <div class="card bg-white">
            <div class="flex items-center space-x-3 mb-4">
                <div class="animate-spin">⏳</div>
                <div>
                    <h3 class="font-bold text-slate-gray-900">Importing transactions...</h3>
                    <p class="text-sm text-slate-gray-600" id="progressText">Processing files...</p>
                </div>
            </div>
            <div class="w-full bg-slate-gray-200 rounded-full h-2">
                <div id="progressBar" class="bg-google-blue-600 h-2 rounded-full transition-all" style="width: 0%;"></div>
            </div>
        </div>
    </div>

    <!-- Import Results -->
    <div id="importResults" class="hidden">
        <div class="card bg-google-green-50 border-l-4 border-google-green-500">
            <h3 class="font-bold text-google-green-700 mb-3">Import Complete!</h3>
            <div class="space-y-2 text-sm text-slate-gray-700">
                <p>✓ <strong id="resultImported">0</strong> transactions imported</p>
                <p>⊘ <strong id="resultSkipped">0</strong> transactions skipped (duplicates)</p>
                <p><strong id="resultFiles">0</strong> files processed</p>
            </div>
            <button class="btn btn-primary btn-sm mt-4" type="button" onclick="location.href='/transactions'">
                View Imported Transactions →
            </button>
        </div>
    </div>
</div>

<script>
document.getElementById('autoImportBtn')?.addEventListener('click', async function() {
    if (!confirm('Import all available JSON files? This may take a moment...')) {
        return;
    }

    document.getElementById('importProgress').classList.remove('hidden');
    document.getElementById('importResults').classList.add('hidden');

    try {
        const response = await fetch('/bank-import/auto-import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
        });

        const data = await response.json();

        if (response.ok) {
            let totalImported = 0;
            let totalSkipped = 0;

            data.files?.forEach(file => {
                if (file.status === 'success') {
                    totalImported += file.imported || 0;
                    totalSkipped += file.skipped || 0;
                }
            });

            document.getElementById('resultImported').textContent = totalImported;
            document.getElementById('resultSkipped').textContent = totalSkipped;
            document.getElementById('resultFiles').textContent = data.files?.length || 0;

            document.getElementById('importProgress').classList.add('hidden');
            document.getElementById('importResults').classList.remove('hidden');
        } else {
            alert('Error: ' + (data.error || 'Import failed'));
            document.getElementById('importProgress').classList.add('hidden');
        }
    } catch (error) {
        alert('Error: ' + error.message);
        document.getElementById('importProgress').classList.add('hidden');
    }
});

document.querySelectorAll('.importFileBtn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const filename = this.dataset.filename;
        if (!confirm(`Import ${filename}?`)) {
            return;
        }

        document.getElementById('importProgress').classList.remove('hidden');
        document.getElementById('importResults').classList.add('hidden');

        try {
            const formData = new FormData();
            formData.append('filename', filename);

            const response = await fetch('/bank-import/import-file', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();

            if (response.ok) {
                document.getElementById('resultImported').textContent = data.imported_count || 0;
                document.getElementById('resultSkipped').textContent = data.skipped_count || 0;
                document.getElementById('resultFiles').textContent = 1;

                document.getElementById('importProgress').classList.add('hidden');
                document.getElementById('importResults').classList.remove('hidden');
            } else {
                alert('Error: ' + (data.error || 'Import failed'));
                document.getElementById('importProgress').classList.add('hidden');
            }
        } catch (error) {
            alert('Error: ' + error.message);
            document.getElementById('importProgress').classList.add('hidden');
        }
    });
});
</script>
?>
