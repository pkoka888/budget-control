/**
 * Approval Workflow JavaScript
 * Handles approval requests, notifications, and UI updates
 */

// Global approval state
let pendingApprovals = [];
let currentApprovalId = null;
let approvalAction = null;

/**
 * Initialize approval functionality
 */
function initApprovals(householdId) {
    if (householdId) {
        loadPendingApprovals(householdId);
        startApprovalPolling(householdId);
    }
}

/**
 * Load pending approvals from API
 */
async function loadPendingApprovals(householdId) {
    try {
        const response = await fetch(`/approval/household/${householdId}`);
        const data = await response.json();

        if (data.success) {
            pendingApprovals = data.pending_requests || [];
            updateApprovalBadge(pendingApprovals.length);
        }
    } catch (error) {
        console.error('Failed to load approvals:', error);
    }
}

/**
 * Update approval count badge in UI
 */
function updateApprovalBadge(count) {
    const badge = document.getElementById('approval-count-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

/**
 * Start polling for new approval requests
 */
function startApprovalPolling(householdId) {
    // Poll every 60 seconds
    setInterval(() => {
        loadPendingApprovals(householdId);
    }, 60000);
}

/**
 * Show approval modal
 */
function showApprovalModal(requestId, action) {
    currentApprovalId = requestId;
    approvalAction = action;

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
                    Notes ${isApprove ? '(optional)' : '(required)'}
                </label>
                <textarea
                    name="notes"
                    rows="4"
                    ${!isApprove ? 'required' : ''}
                    class="w-full px-3 py-2 border border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="${isApprove ? 'Approved. Good reason provided.' : 'Please explain why this is being rejected...'}"
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

/**
 * Hide approval modal
 */
function hideApprovalModal() {
    document.getElementById('approvalModal').classList.add('hidden');
    currentApprovalId = null;
    approvalAction = null;
}

/**
 * Submit approval or rejection
 */
async function submitApproval(event) {
    event.preventDefault();
    const form = event.target;
    const notes = form.notes.value;

    if (!currentApprovalId || !approvalAction) {
        showToast('Invalid approval request', 'error');
        return;
    }

    try {
        const response = await fetch(`/approval/${currentApprovalId}/${approvalAction}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `notes=${encodeURIComponent(notes)}`
        });

        const data = await response.json();

        if (data.success) {
            showToast(
                approvalAction === 'approve'
                    ? 'Request approved successfully ✓'
                    : 'Request rejected',
                'success'
            );
            hideApprovalModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to process request', 'error');
        }
    } catch (error) {
        console.error('Error processing approval:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * View approval request details
 */
function viewApprovalDetails(requestId) {
    window.location.href = `/approval/${requestId}`;
}

/**
 * Quick approve (without notes)
 */
async function quickApprove(requestId) {
    const confirmed = confirm('Approve this request?');
    if (!confirmed) return;

    try {
        const response = await fetch(`/approval/${requestId}/approve`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'notes=Approved'
        });

        const data = await response.json();

        if (data.success) {
            showToast('Request approved ✓', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to approve', 'error');
        }
    } catch (error) {
        console.error('Error approving:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Filter approvals by type
 */
function filterApprovals(type) {
    const approvalCards = document.querySelectorAll('.approval-card');

    approvalCards.forEach(card => {
        const requestType = card.dataset.type;

        if (type === 'all' || requestType === type) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });

    // Update filter buttons
    document.querySelectorAll('.approval-filter-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700');
    });

    if (event && event.target) {
        event.target.classList.remove('bg-gray-100', 'text-gray-700');
        event.target.classList.add('bg-blue-600', 'text-white');
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Check if toast function already exists globally
    if (typeof window.showToast === 'function' && window.showToast !== showToast) {
        window.showToast(message, type);
        return;
    }

    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        type === 'warning' ? 'bg-yellow-500' :
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

// Export functions to global scope
window.initApprovals = initApprovals;
window.showApprovalModal = showApprovalModal;
window.hideApprovalModal = hideApprovalModal;
window.submitApproval = submitApproval;
window.viewApprovalDetails = viewApprovalDetails;
window.quickApprove = quickApprove;
window.filterApprovals = filterApprovals;

// Close modal when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('approvalModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideApprovalModal();
            }
        });
    }
});
