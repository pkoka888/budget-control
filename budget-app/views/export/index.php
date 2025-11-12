<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Data Export & API</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Export your data and manage API access</p>
    </div>

    <!-- Quick Export -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Export Transactions</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Export all transactions to various formats</p>
            <select id="export-format-tx" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white mb-3">
                <option value="csv">CSV</option>
                <option value="xlsx">Excel</option>
                <option value="json">JSON</option>
                <option value="qif">QIF</option>
                <option value="ofx">OFX</option>
            </select>
            <button onclick="quickExport('transactions')" class="btn-primary w-full">Export</button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📈 Export Investments</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Export portfolio and holdings data</p>
            <select id="export-format-inv" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white mb-3">
                <option value="csv">CSV</option>
                <option value="xlsx">Excel</option>
                <option value="json">JSON</option>
            </select>
            <button onclick="quickExport('investments')" class="btn-primary w-full">Export</button>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">💾 Full Backup</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Complete backup of all your data</p>
            <select id="export-format-backup" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white mb-3">
                <option value="json">JSON</option>
                <option value="xlsx">Excel</option>
            </select>
            <button onclick="quickExport('full_backup')" class="btn-primary w-full">Backup</button>
        </div>
    </div>

    <!-- Export Jobs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Recent Exports</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Format</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo ucfirst(str_replace('_', ' ', $job['export_type'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo strtoupper($job['format']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php
                                $statusColors = [
                                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                    'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                                ];
                                $statusClass = $statusColors[$job['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 py-1 text-xs font-medium <?php echo $statusClass; ?> rounded">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo date('M j, Y g:i A', strtotime($job['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo $job['file_size'] ? round($job['file_size'] / 1024, 2) . ' KB' : '-'; ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if ($job['status'] === 'completed' && $job['file_path']): ?>
                                    <a href="/export/download/<?php echo $job['id']; ?>" class="text-purple-600 dark:text-purple-400 hover:underline">
                                        Download
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- API Keys -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">API Keys</h2>
            <a href="/export/api-keys" class="text-purple-600 dark:text-purple-400 hover:underline">
                Manage Keys →
            </a>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Use API keys to integrate Budget Control with external applications and services.
        </p>
    </div>
</div>

<script>
async function quickExport(exportType) {
    let formatId = 'export-format-tx';
    if (exportType === 'investments') formatId = 'export-format-inv';
    if (exportType === 'full_backup') formatId = 'export-format-backup';

    const format = document.getElementById(formatId).value;

    try {
        const response = await fetch('/export/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: exportType, format: format, async: false })
        });
        const result = await response.json();

        if (result.success) {
            alert('Export created! Refreshing...');
            location.reload();
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}
</style>
