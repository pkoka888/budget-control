<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals - Budget Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <?php
    $pendingRequests = $data['pending_requests'] ?? [];
    $householdId = $data['household_id'] ?? 0;
    ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Pending Approvals</h1>
                <p class="text-gray-600 dark:text-gray-400">Review and approve household requests</p>
            </div>
            <a href="/household/<?= $householdId ?>" class="text-blue-600 hover:text-blue-700">← Back to Household</a>
        </div>

        <?php if (!empty($pendingRequests)): ?>
        <!-- Pending Requests -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($pendingRequests as $request): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border-l-4 border-yellow-500 p-6">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                            <?= ucwords(str_replace('_', ' ', $request['request_type'])) ?>
                        </h3>
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span>From: <strong><?= htmlspecialchars($request['requester_username']) ?></strong></span>
                            <?php if ($request['amount']): ?>
                            <span>•</span>
                            <span class="font-medium text-lg"><?= number_format($request['amount'], 2) ?> CZK</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-xs font-medium">
                        Pending
                    </span>
                </div>

                <!-- Description -->
                <p class="text-gray-900 dark:text-white mb-4 bg-gray-50 dark:bg-gray-700 p-3 rounded">
                    "<?= htmlspecialchars($request['description']) ?>"
                </p>

                <?php if ($request['justification']): ?>
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <strong>Reason:</strong> <?= htmlspecialchars($request['justification']) ?>
                </div>
                <?php endif; ?>

                <!-- Metadata -->
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
                    <span>Requested <?= date('M j, Y \a\t g:i A', strtotime($request['created_at'])) ?></span>
                    <?php if ($request['expires_at']): ?>
                    <span>Expires <?= date('M j', strtotime($request['expires_at'])) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button
                        onclick="showApprovalModal(<?= $request['id'] ?>, 'approve')"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium text-sm flex items-center justify-center gap-2"
                    >
                        <span>✓</span>
                        <span>Approve</span>
                    </button>
                    <button
                        onclick="showApprovalModal(<?= $request['id'] ?>, 'reject')"
                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm flex items-center justify-center gap-2"
                    >
                        <span>✗</span>
                        <span>Reject</span>
                    </button>
                    <button
                        onclick="viewDetails(<?= $request['id'] ?>)"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
                        title="View details"
                    >
                        👁️
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <div class="text-6xl mb-4">✅</div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">All Caught Up!</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">There are no pending approval requests at the moment.</p>
            <a href="/household/<?= $householdId ?>" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                Back to Household
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Approval Modal -->
    <div id="approvalModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div id="modalContent">
                <!-- Dynamic content loaded here -->
            </div>
        </div>
    </div>

    <script>
        let currentRequestId = null;
        let currentAction = null;

        function showApprovalModal(requestId, action) {
            currentRequestId = requestId;
            currentAction = action;

            const modal = document.getElementById('approvalModal');
            const content = document.getElementById('modalContent');

            const isApprove = action === 'approve';

            content.innerHTML = `
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                    ${isApprove ? '✓ Approve Request' : '✗ Reject Request'}
                </h3>
                <form onsubmit="submitApproval(event)">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Notes (optional)
                        </label>
                        <textarea
                            name="notes"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="${isApprove ? 'Approved. Good reason provided.' : 'Please provide a reason for rejection...'}"
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            onclick="hideApprovalModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-md text-white font-medium ${isApprove ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'}"
                        >
                            ${isApprove ? 'Approve' : 'Reject'}
                        </button>
                    </div>
                </form>
            `;

            modal.classList.remove('hidden');
        }

        function hideApprovalModal() {
            document.getElementById('approvalModal').classList.add('hidden');
            currentRequestId = null;
            currentAction = null;
        }

        async function submitApproval(event) {
            event.preventDefault();
            const form = event.target;
            const notes = form.notes.value;

            try {
                const response = await fetch(`/approval/${currentRequestId}/${currentAction}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `notes=${encodeURIComponent(notes)}`
                });

                const data = await response.json();

                if (data.success) {
                    showToast(
                        currentAction === 'approve' ? 'Request approved successfully' : 'Request rejected',
                        'success'
                    );
                    hideApprovalModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(data.error || 'Failed to process request', 'error');
                }
            } catch (error) {
                showToast('An error occurred. Please try again.', 'error');
            }
        }

        function viewDetails(requestId) {
            window.location.href = `/approval/${requestId}`;
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Close modal when clicking outside
        document.getElementById('approvalModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideApprovalModal();
            }
        });
    </script>
</body>
</html>
