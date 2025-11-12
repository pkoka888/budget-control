/**
 * Child Account JavaScript
 * Handles child dashboard functionality, money requests, and allowance tracking
 */

// Global child account state
let childBalance = 0;
let childSettings = null;
let pendingRequests = [];

/**
 * Initialize child account functionality
 */
function initChildAccount(householdId, balance) {
    childBalance = balance;
    loadChildSettings(householdId);
    updateBalanceDisplay();
}

/**
 * Load child account settings
 */
async function loadChildSettings(householdId) {
    try {
        const response = await fetch(`/child-account/${householdId}/settings`);
        const data = await response.json();

        if (data.success) {
            childSettings = data.settings;
            updateSpendingLimits(data.settings);
        }
    } catch (error) {
        console.error('Failed to load child settings:', error);
    }
}

/**
 * Update balance display
 */
function updateBalanceDisplay() {
    const balanceEl = document.getElementById('child-balance');
    if (balanceEl) {
        balanceEl.textContent = formatCurrency(childBalance);
    }
}

/**
 * Update spending limit progress bars
 */
function updateSpendingLimits(settings) {
    // Update daily limit
    updateLimitBar('daily', settings.daily_spent, settings.daily_limit);

    // Update weekly limit
    updateLimitBar('weekly', settings.weekly_spent, settings.weekly_limit);

    // Update monthly limit
    updateLimitBar('monthly', settings.monthly_spent, settings.monthly_limit);
}

/**
 * Update individual limit progress bar
 */
function updateLimitBar(period, spent, limit) {
    if (!limit) return;

    const percentEl = document.getElementById(`${period}-limit-percent`);
    const barEl = document.getElementById(`${period}-limit-bar`);
    const textEl = document.getElementById(`${period}-limit-text`);

    if (barEl && textEl) {
        const percent = Math.min(100, (spent / limit) * 100);

        barEl.style.width = `${percent}%`;
        barEl.className = `h-2 rounded-full ${
            percent >= 90 ? 'bg-red-500' :
            percent >= 70 ? 'bg-yellow-500' :
            'bg-green-500'
        }`;

        textEl.textContent = `${formatCurrency(spent)} / ${formatCurrency(limit)} CZK`;
    }

    if (percentEl) {
        percentEl.textContent = `${Math.round((spent / limit) * 100)}%`;
    }
}

/**
 * Open request money modal
 */
function openRequestMoneyModal() {
    const modal = document.getElementById('requestMoneyModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('request-amount').focus();
    }
}

/**
 * Close request money modal
 */
function closeRequestMoneyModal() {
    const modal = document.getElementById('requestMoneyModal');
    if (modal) {
        modal.classList.add('hidden');
        document.getElementById('requestMoneyForm').reset();
    }
}

/**
 * Submit money request
 */
async function submitMoneyRequest(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    const amount = parseFloat(formData.get('amount'));

    // Client-side validation
    if (amount <= 0) {
        showToast('Amount must be greater than 0', 'error');
        return;
    }

    if (amount > 10000) {
        showToast('Amount too large. Please request a smaller amount.', 'error');
        return;
    }

    try {
        const householdId = document.getElementById('household-id')?.value;
        const response = await fetch(`/child-account/${householdId}/money-request`, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });

        const data = await response.json();

        if (data.success) {
            showToast('Money request sent! 📤', 'success');
            closeRequestMoneyModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to send request', 'error');
        }
    } catch (error) {
        console.error('Error sending money request:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Cancel pending money request
 */
async function cancelMoneyRequest(requestId) {
    const confirmed = confirm('Cancel this money request?');
    if (!confirmed) return;

    try {
        const response = await fetch(`/child-account/money-request/${requestId}/cancel`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showToast('Request cancelled', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to cancel request', 'error');
        }
    } catch (error) {
        console.error('Error cancelling request:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Check if can spend amount
 */
function canSpend(amount) {
    if (!childSettings) return { allowed: true };

    const checks = {
        balance: childBalance >= amount,
        daily: !childSettings.daily_limit || (childSettings.daily_spent + amount) <= childSettings.daily_limit,
        weekly: !childSettings.weekly_limit || (childSettings.weekly_spent + amount) <= childSettings.weekly_limit,
        monthly: !childSettings.monthly_limit || (childSettings.monthly_spent + amount) <= childSettings.monthly_limit
    };

    const allowed = checks.balance && checks.daily && checks.weekly && checks.monthly;

    return {
        allowed,
        checks,
        message: !allowed ? getBlockReason(checks, amount) : null
    };
}

/**
 * Get reason why spending is blocked
 */
function getBlockReason(checks, amount) {
    if (!checks.balance) {
        return `Insufficient balance. You need ${formatCurrency(amount - childBalance)} more.`;
    }
    if (!checks.daily) {
        return 'Daily spending limit reached.';
    }
    if (!checks.weekly) {
        return 'Weekly spending limit reached.';
    }
    if (!checks.monthly) {
        return 'Monthly spending limit reached.';
    }
    return 'Cannot spend this amount.';
}

/**
 * Preview spending (shows if amount can be spent)
 */
function previewSpending(amount) {
    const result = canSpend(amount);

    if (!result.allowed) {
        showToast(result.message, 'warning');
        return false;
    }

    return true;
}

/**
 * View transaction history
 */
function viewTransactionHistory() {
    const householdId = document.getElementById('household-id')?.value;
    if (householdId) {
        window.location.href = `/child-account/${householdId}/transactions`;
    }
}

/**
 * View allowance settings
 */
function viewAllowanceSettings() {
    const householdId = document.getElementById('household-id')?.value;
    if (householdId) {
        window.location.href = `/child-account/${householdId}/allowance`;
    }
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return parseFloat(amount || 0).toFixed(2);
}

/**
 * Show spending breakdown
 */
function showSpendingBreakdown() {
    if (!childSettings) {
        showToast('Loading settings...', 'info');
        return;
    }

    const breakdown = `
Daily Spent: ${formatCurrency(childSettings.daily_spent)} / ${formatCurrency(childSettings.daily_limit || 'unlimited')}
Weekly Spent: ${formatCurrency(childSettings.weekly_spent)} / ${formatCurrency(childSettings.weekly_limit || 'unlimited')}
Monthly Spent: ${formatCurrency(childSettings.monthly_spent)} / ${formatCurrency(childSettings.monthly_limit || 'unlimited')}
    `.trim();

    alert(`Your Spending Breakdown:\n\n${breakdown}`);
}

/**
 * Show next allowance info
 */
function showNextAllowance() {
    const nextAllowanceEl = document.getElementById('next-allowance-date');
    if (!nextAllowanceEl) return;

    const nextDate = nextAllowanceEl.dataset.date;
    const amount = nextAllowanceEl.dataset.amount;

    if (nextDate && amount) {
        showToast(
            `Next allowance: ${formatCurrency(amount)} CZK on ${formatDate(nextDate)}`,
            'info'
        );
    } else {
        showToast('No allowance scheduled', 'info');
    }
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

/**
 * Auto-update money request reason based on amount
 */
function updateRequestReason() {
    const amountInput = document.getElementById('request-amount');
    const reasonTextarea = document.getElementById('request-reason');

    if (!amountInput || !reasonTextarea || reasonTextarea.value.length > 0) return;

    const amount = parseFloat(amountInput.value);

    if (amount >= 100) {
        reasonTextarea.placeholder = 'Please explain in detail why you need this amount...';
    } else {
        reasonTextarea.placeholder = 'What do you need it for?';
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
window.initChildAccount = initChildAccount;
window.openRequestMoneyModal = openRequestMoneyModal;
window.closeRequestMoneyModal = closeRequestMoneyModal;
window.submitMoneyRequest = submitMoneyRequest;
window.cancelMoneyRequest = cancelMoneyRequest;
window.canSpend = canSpend;
window.previewSpending = previewSpending;
window.viewTransactionHistory = viewTransactionHistory;
window.viewAllowanceSettings = viewAllowanceSettings;
window.showSpendingBreakdown = showSpendingBreakdown;
window.showNextAllowance = showNextAllowance;
window.updateRequestReason = updateRequestReason;

// Initialize event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside
    const modal = document.getElementById('requestMoneyModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRequestMoneyModal();
            }
        });
    }

    // Auto-update request reason placeholder
    const amountInput = document.getElementById('request-amount');
    if (amountInput) {
        amountInput.addEventListener('input', updateRequestReason);
    }

    // Initialize child account if data is present
    const householdIdEl = document.getElementById('household-id');
    const balanceEl = document.getElementById('child-balance');

    if (householdIdEl && balanceEl) {
        const householdId = householdIdEl.value;
        const balance = parseFloat(balanceEl.dataset.balance || balanceEl.textContent || 0);
        initChildAccount(householdId, balance);
    }
});
