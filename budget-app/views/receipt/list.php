<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Receipt History</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">View all your scanned receipts</p>
        </div>
        <a href="/receipt" class="btn-primary">
            <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Scan New Receipt
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" id="search" placeholder="Search receipts..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
            </div>
            <select id="status-filter" onchange="filterByStatus()"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                <option value="">All Status</option>
                <option value="completed">Completed</option>
                <option value="review_needed">Review Needed</option>
                <option value="processing">Processing</option>
                <option value="failed">Failed</option>
            </select>
            <select id="sort-order" onchange="sortReceipts()"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white">
                <option value="date_desc">Newest First</option>
                <option value="date_asc">Oldest First</option>
                <option value="amount_desc">Highest Amount</option>
                <option value="amount_asc">Lowest Amount</option>
            </select>
        </div>
    </div>

    <!-- Receipts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($scans as $scan): ?>
            <?php
            $parsedData = json_decode($scan['parsed_data'] ?? '{}', true);
            $statusColors = [
                'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'review_needed' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
            ];
            $statusClass = $statusColors[$scan['status']] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
            ?>
            <a href="/receipt/scan?id=<?php echo $scan['id']; ?>" class="block bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition overflow-hidden">
                <!-- Receipt Image -->
                <div class="aspect-[3/4] bg-gray-100 dark:bg-gray-700 relative overflow-hidden">
                    <img src="<?php echo htmlspecialchars($scan['image_path']); ?>" alt="Receipt" class="w-full h-full object-cover">
                    <div class="absolute top-3 right-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $scan['status'])); ?>
                        </span>
                    </div>
                    <?php if ($scan['confidence_score']): ?>
                        <div class="absolute bottom-3 left-3 bg-black bg-opacity-75 text-white px-2 py-1 rounded text-xs">
                            <?php echo round($scan['confidence_score'] * 100); ?>% confidence
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Receipt Details -->
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                        <?php echo htmlspecialchars($parsedData['merchant'] ?? 'Unknown Merchant'); ?>
                    </h3>
                    <div class="mt-2 flex justify-between items-center">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                            <?php echo number_format($parsedData['total'] ?? 0, 2); ?>
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            <?php echo htmlspecialchars($parsedData['currency'] ?? 'CZK'); ?>
                        </span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <?php echo $parsedData['date'] ? date('M j, Y', strtotime($parsedData['date'])) : 'No date'; ?>
                    </div>
                    <?php if (!empty($parsedData['items'])): ?>
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <?php echo count($parsedData['items']); ?> items
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>

        <?php if (empty($scans)): ?>
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                <svg class="w-20 h-20 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No receipts found</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Start by scanning your first receipt</p>
                <a href="/receipt" class="btn-primary inline-block">
                    Scan Receipt
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if (!empty($scans)): ?>
        <div class="flex justify-center">
            <nav class="flex space-x-2">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        Previous
                    </a>
                <?php endif; ?>

                <span class="px-4 py-2 bg-purple-500 text-white rounded-lg">
                    Page <?php echo $current_page; ?>
                </span>

                <?php if (count($scans) >= $per_page): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>" class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        Next
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
// Search functionality
let searchTimeout;
document.getElementById('search').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const query = e.target.value.toLowerCase();
        // Implement search logic
        console.log('Searching for:', query);
    }, 300);
});

function filterByStatus() {
    const status = document.getElementById('status-filter').value;
    window.location.href = '/receipt/list' + (status ? '?status=' + status : '');
}

function sortReceipts() {
    const order = document.getElementById('sort-order').value;
    // Implement sorting
    console.log('Sorting by:', order);
}
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}
</style>
