/**
 * Chore Management JavaScript
 * Handles chore creation, completion, verification, and UI updates
 */

// Global chore state
let currentChoreId = null;
let currentCompletionId = null;

/**
 * Initialize chore functionality
 */
function initChores(householdId) {
    if (householdId) {
        loadChoreStats(householdId);
    }
}

/**
 * Load chore statistics
 */
async function loadChoreStats(householdId) {
    try {
        const response = await fetch(`/chores/household/${householdId}/stats`);
        const data = await response.json();

        if (data.success && data.stats) {
            updateChoreStatsUI(data.stats);
        }
    } catch (error) {
        console.error('Failed to load chore stats:', error);
    }
}

/**
 * Update chore stats in UI
 */
function updateChoreStatsUI(stats) {
    Object.keys(stats).forEach(key => {
        const el = document.getElementById(`chore-stat-${key}`);
        if (el) el.textContent = stats[key];
    });
}

/**
 * Open create chore modal
 */
function openCreateChoreModal() {
    const modal = document.getElementById('choreModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('choreForm').reset();
        document.getElementById('chore_id').value = '';
        document.getElementById('choreModalTitle').textContent = 'Create Chore';
    }
}

/**
 * Close chore modal
 */
function closeChoreModal() {
    const modal = document.getElementById('choreModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

/**
 * Edit existing chore
 */
async function editChore(choreId) {
    currentChoreId = choreId;

    try {
        const response = await fetch(`/chores/${choreId}`);
        const data = await response.json();

        if (data.success && data.chore) {
            populateChoreForm(data.chore);
            document.getElementById('choreModalTitle').textContent = 'Edit Chore';
            document.getElementById('choreModal').classList.remove('hidden');
        } else {
            showToast('Failed to load chore details', 'error');
        }
    } catch (error) {
        console.error('Error loading chore:', error);
        showToast('An error occurred', 'error');
    }
}

/**
 * Populate chore form with existing data
 */
function populateChoreForm(chore) {
    document.getElementById('chore_id').value = chore.id;
    document.getElementById('title').value = chore.title;
    document.getElementById('description').value = chore.description || '';
    document.getElementById('assigned_to').value = chore.assigned_to;
    document.getElementById('reward_amount').value = chore.reward_amount;
    document.getElementById('due_date').value = chore.due_date || '';
    document.getElementById('recurrence_pattern').value = chore.recurrence_pattern || '';
    document.getElementById('requires_photo').checked = chore.requires_photo === 1;
}

/**
 * Submit chore form (create or update)
 */
async function submitChore(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    const choreId = formData.get('chore_id');
    const url = choreId ? `/chores/${choreId}/update` : '/chores/store';

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });

        const data = await response.json();

        if (data.success) {
            showToast('Chore saved successfully! ⭐', 'success');
            closeChoreModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to save chore', 'error');
        }
    } catch (error) {
        console.error('Error saving chore:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Delete chore
 */
async function deleteChore(choreId) {
    const confirmed = confirm('Are you sure you want to delete this chore?');
    if (!confirmed) return;

    try {
        const response = await fetch(`/chores/${choreId}/delete`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showToast('Chore deleted', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to delete chore', 'error');
        }
    } catch (error) {
        console.error('Error deleting chore:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Complete chore (child action)
 */
function completeChore(choreId, requiresPhoto) {
    currentChoreId = choreId;

    const modal = document.getElementById('completeChoreModal');
    if (modal) {
        document.getElementById('complete_chore_id').value = choreId;

        const photoSection = document.getElementById('photoUploadSection');
        const photoInput = document.getElementById('photo');

        if (photoSection && photoInput) {
            photoSection.style.display = requiresPhoto ? 'block' : 'none';
            photoInput.required = requiresPhoto;
        }

        modal.classList.remove('hidden');
    }
}

/**
 * Close complete chore modal
 */
function closeCompleteChoreModal() {
    const modal = document.getElementById('completeChoreModal');
    if (modal) {
        modal.classList.add('hidden');
        document.getElementById('completeForm').reset();
    }
}

/**
 * Submit chore completion
 */
async function submitCompletion(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    try {
        const response = await fetch(`/child-account/chore/${formData.get('chore_id')}/complete`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast('Chore submitted for verification! 🎉', 'success');
            closeCompleteChoreModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to complete chore', 'error');
        }
    } catch (error) {
        console.error('Error completing chore:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Open verify chore modal
 */
function verifyChore(completionId, choreId) {
    currentCompletionId = completionId;
    currentChoreId = choreId;

    const modal = document.getElementById('verifyModal');
    if (modal) {
        document.getElementById('verify_completion_id').value = completionId;
        document.getElementById('verify_chore_id').value = choreId;
        modal.classList.remove('hidden');

        // Reset rating
        setRating(0);
    }
}

/**
 * Close verify modal
 */
function closeVerifyModal() {
    const modal = document.getElementById('verifyModal');
    if (modal) {
        modal.classList.add('hidden');
        document.getElementById('verifyForm').reset();
    }
}

/**
 * Set quality rating
 */
let selectedRating = 0;
function setRating(rating) {
    selectedRating = rating;
    document.getElementById('quality_rating').value = rating;

    // Visual feedback
    const buttons = document.querySelectorAll('.rating-btn');
    buttons.forEach((btn, idx) => {
        btn.style.opacity = idx < rating ? '1' : '0.3';
    });
}

/**
 * Submit chore verification
 */
async function submitVerification(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const completionId = formData.get('completion_id');

    try {
        const response = await fetch(`/chores/completion/${completionId}/verify`, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });

        const data = await response.json();

        if (data.success) {
            showToast('Chore verified! Reward paid 💰', 'success');
            closeVerifyModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to verify chore', 'error');
        }
    } catch (error) {
        console.error('Error verifying chore:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Toggle recurring chore fields
 */
function toggleRecurring() {
    const pattern = document.getElementById('recurrence_pattern').value;
    const recurringFields = document.getElementById('recurringFields');

    if (recurringFields) {
        recurringFields.style.display = pattern ? 'block' : 'none';
    }
}

/**
 * Filter chores by status
 */
function filterChores(status) {
    const choreCards = document.querySelectorAll('.chore-card');

    choreCards.forEach(card => {
        const cardStatus = card.dataset.status;

        if (status === 'all' || cardStatus === status) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
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
window.initChores = initChores;
window.openCreateChoreModal = openCreateChoreModal;
window.closeChoreModal = closeChoreModal;
window.editChore = editChore;
window.submitChore = submitChore;
window.deleteChore = deleteChore;
window.completeChore = completeChore;
window.closeCompleteChoreModal = closeCompleteChoreModal;
window.submitCompletion = submitCompletion;
window.verifyChore = verifyChore;
window.closeVerifyModal = closeVerifyModal;
window.setRating = setRating;
window.submitVerification = submitVerification;
window.toggleRecurring = toggleRecurring;
window.filterChores = filterChores;

// Initialize modal close handlers
document.addEventListener('DOMContentLoaded', function() {
    // Close modals when clicking outside
    const modals = ['choreModal', 'completeChoreModal', 'verifyModal'];

    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    const closeFunc = window[`close${modalId.charAt(0).toUpperCase() + modalId.slice(1)}`];
                    if (typeof closeFunc === 'function') {
                        closeFunc();
                    }
                }
            });
        }
    });
});
