<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Receipt Scanner</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Scan receipts and automatically extract transaction data</p>
        </div>
        <div class="flex space-x-3">
            <a href="/receipt/list" class="btn-secondary">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Receipt History
            </a>
            <a href="/receipt/review-queue" class="btn-secondary">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Review Queue
            </a>
        </div>
    </div>

    <!-- Upload Section -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8">
        <div class="max-w-2xl mx-auto">
            <!-- Upload Area -->
            <div id="upload-area" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-12 text-center hover:border-purple-500 transition cursor-pointer">
                <svg class="w-20 h-20 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Upload Receipt Image</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">Drag and drop or click to select an image</p>
                <input type="file" id="receipt-file" accept="image/*" class="hidden">
                <button onclick="document.getElementById('receipt-file').click()" class="btn-primary">
                    Choose File
                </button>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">Supported formats: JPG, PNG, WEBP (max 10MB)</p>
            </div>

            <!-- Camera Capture (Mobile) -->
            <div class="mt-4 text-center">
                <button onclick="captureFromCamera()" class="text-purple-600 dark:text-purple-400 hover:underline text-sm">
                    📷 Or capture with camera
                </button>
            </div>

            <!-- Preview Area -->
            <div id="preview-area" class="hidden mt-6">
                <div class="relative">
                    <img id="receipt-preview" class="w-full rounded-lg shadow-lg">
                    <button onclick="clearPreview()" class="absolute top-4 right-4 p-2 bg-red-500 text-white rounded-full hover:bg-red-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Processing Status -->
                <div id="processing-status" class="mt-4 text-center hidden">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">Processing receipt...</p>
                </div>

                <!-- Results -->
                <div id="results-area" class="hidden mt-6">
                    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm text-blue-800 dark:text-blue-200">Confidence Score</span>
                                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                    <span id="confidence-score">-</span>%
                                </div>
                            </div>
                            <div id="confidence-badge" class="px-4 py-2 rounded-full text-sm font-semibold">
                                <!-- Filled by JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Extracted Data -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Merchant</label>
                            <input type="text" id="merchant" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date</label>
                            <input type="date" id="date" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Total Amount</label>
                            <input type="number" id="total" step="0.01" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Currency</label>
                            <input type="text" id="currency" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div id="line-items" class="mb-6">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Line Items</h4>
                        <div id="items-list" class="space-y-2">
                            <!-- Filled by JS -->
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <button onclick="clearPreview()" class="px-6 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            Cancel
                        </button>
                        <button onclick="createTransaction()" class="btn-primary">
                            Create Transaction
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Scans -->
    <?php if (!empty($recent_scans)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Recent Scans</h2>

            <div class="space-y-3">
                <?php foreach ($recent_scans as $scan): ?>
                    <a href="/receipt/scan?id=<?php echo $scan['id']; ?>" class="flex items-center space-x-4 p-4 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition">
                        <img src="<?php echo htmlspecialchars($scan['image_path']); ?>" alt="Receipt" class="w-16 h-16 object-cover rounded">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 dark:text-white">
                                <?php
                                $data = json_decode($scan['parsed_data'] ?? '{}', true);
                                echo htmlspecialchars($data['merchant'] ?? 'Unknown');
                                ?>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <?php echo date('M j, Y', strtotime($scan['created_at'])); ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-gray-900 dark:text-white">
                                <?php echo number_format($data['total'] ?? 0, 2); ?> <?php echo htmlspecialchars($data['currency'] ?? 'CZK'); ?>
                            </div>
                            <div class="text-xs">
                                <?php
                                $statusColors = [
                                    'completed' => 'text-green-600 dark:text-green-400',
                                    'review_needed' => 'text-yellow-600 dark:text-yellow-400',
                                    'processing' => 'text-blue-600 dark:text-blue-400',
                                    'failed' => 'text-red-600 dark:text-red-400'
                                ];
                                $statusClass = $statusColors[$scan['status']] ?? 'text-gray-600';
                                ?>
                                <span class="<?php echo $statusClass; ?>"><?php echo ucfirst($scan['status']); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
let currentScanId = null;

// File input change handler
document.getElementById('receipt-file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        processFile(file);
    }
});

// Drag and drop
const uploadArea = document.getElementById('upload-area');
uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('border-purple-500');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('border-purple-500');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('border-purple-500');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
        processFile(file);
    }
});

function processFile(file) {
    // Show preview
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('receipt-preview').src = e.target.result;
        document.getElementById('preview-area').classList.remove('hidden');
        document.getElementById('processing-status').classList.remove('hidden');
    };
    reader.readAsDataURL(file);

    // Upload and process
    const formData = new FormData();
    formData.append('receipt', file);

    fetch('/receipt/upload', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        document.getElementById('processing-status').classList.add('hidden');

        if (result.success) {
            currentScanId = result.scan_id;
            displayResults(result);
        } else {
            alert('Error: ' + result.error);
            clearPreview();
        }
    })
    .catch(error => {
        document.getElementById('processing-status').classList.add('hidden');
        alert('Error processing receipt: ' + error.message);
        clearPreview();
    });
}

function displayResults(result) {
    const data = result.parsed_data;

    // Show results
    document.getElementById('results-area').classList.remove('hidden');

    // Confidence score
    const confidence = Math.round(result.confidence * 100);
    document.getElementById('confidence-score').textContent = confidence;

    const badge = document.getElementById('confidence-badge');
    if (confidence >= 70) {
        badge.textContent = 'High Confidence';
        badge.className = 'px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    } else if (confidence >= 40) {
        badge.textContent = 'Medium Confidence';
        badge.className = 'px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    } else {
        badge.textContent = 'Low Confidence';
        badge.className = 'px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
    }

    // Fill form
    document.getElementById('merchant').value = data.merchant || '';
    document.getElementById('date').value = data.date || '';
    document.getElementById('total').value = data.total || '';
    document.getElementById('currency').value = data.currency || 'CZK';

    // Line items
    const itemsList = document.getElementById('items-list');
    itemsList.innerHTML = '';
    if (data.items && data.items.length > 0) {
        data.items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'flex justify-between text-sm p-2 bg-gray-50 dark:bg-gray-700 rounded';
            div.innerHTML = `
                <span>${item.name} x${item.quantity}</span>
                <span class="font-medium">${item.total.toFixed(2)}</span>
            `;
            itemsList.appendChild(div);
        });
    } else {
        itemsList.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No line items detected</p>';
    }
}

function clearPreview() {
    document.getElementById('preview-area').classList.add('hidden');
    document.getElementById('results-area').classList.add('hidden');
    document.getElementById('receipt-file').value = '';
    currentScanId = null;
}

function createTransaction() {
    if (!currentScanId) {
        alert('No receipt scan available');
        return;
    }

    // Redirect to transaction create page with scan ID
    window.location.href = '/transactions/create?receipt_scan_id=' + currentScanId;
}

function captureFromCamera() {
    document.getElementById('receipt-file').setAttribute('capture', 'environment');
    document.getElementById('receipt-file').click();
}
</script>

<style>
.btn-primary {
    @apply px-6 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white font-semibold rounded-lg shadow hover:from-purple-600 hover:to-indigo-600 transition;
}

.btn-secondary {
    @apply px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition;
}
</style>
