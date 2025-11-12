/**
 * Household Management JavaScript
 * Handles household-related interactions and UI updates
 */

// Global household state
let currentHouseholdId = null;
let householdMembers = [];

/**
 * Initialize household functionality
 */
function initHousehold(householdId) {
    currentHouseholdId = householdId;
    loadHouseholdData();
}

/**
 * Load household data from API
 */
async function loadHouseholdData() {
    if (!currentHouseholdId) return;

    try {
        const response = await fetch(`/household/${currentHouseholdId}`);
        const data = await response.json();

        if (data.success) {
            householdMembers = data.members || [];
            updateHouseholdUI(data);
        }
    } catch (error) {
        console.error('Failed to load household data:', error);
    }
}

/**
 * Update household UI with fresh data
 */
function updateHouseholdUI(data) {
    // Update member count
    const memberCountEl = document.getElementById('household-member-count');
    if (memberCountEl) {
        memberCountEl.textContent = data.members?.length || 0;
    }

    // Update stats
    if (data.stats) {
        Object.keys(data.stats).forEach(key => {
            const el = document.getElementById(`household-stat-${key}`);
            if (el) el.textContent = data.stats[key];
        });
    }
}

/**
 * Open invite member modal
 */
function openInviteMemberModal() {
    const modal = document.getElementById('inviteMemberModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.getElementById('invite-email').focus();
    }
}

/**
 * Close invite member modal
 */
function closeInviteMemberModal() {
    const modal = document.getElementById('inviteMemberModal');
    if (modal) {
        modal.classList.add('hidden');
        document.getElementById('inviteForm').reset();
    }
}

/**
 * Submit invitation
 */
async function submitInvitation(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    try {
        const response = await fetch(`/household/${currentHouseholdId}/invite`, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });

        const data = await response.json();

        if (data.success) {
            showToast('Invitation sent successfully! 📧', 'success');
            closeInviteMemberModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to send invitation', 'error');
        }
    } catch (error) {
        console.error('Error sending invitation:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Change member role
 */
async function changeMemberRole(memberId, currentRole) {
    const roles = ['owner', 'partner', 'viewer', 'child'];
    const roleDescriptions = {
        owner: '👑 Owner - Full control',
        partner: '🤝 Partner - Manage finances',
        viewer: '👁️ Viewer - View only',
        child: '👶 Child - Limited access'
    };

    // Show role selection
    const newRole = prompt(
        `Change role for this member:\n\nAvailable roles:\n${roles.map(r => `- ${roleDescriptions[r]}`).join('\n')}\n\nEnter new role:`,
        currentRole
    );

    if (!newRole || newRole === currentRole) return;

    if (!roles.includes(newRole.toLowerCase())) {
        showToast('Invalid role', 'error');
        return;
    }

    try {
        const response = await fetch(`/household/${currentHouseholdId}/member/${memberId}/role`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `role=${newRole.toLowerCase()}`
        });

        const data = await response.json();

        if (data.success) {
            showToast('Member role updated successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to update role', 'error');
        }
    } catch (error) {
        console.error('Error updating role:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Remove member from household
 */
async function removeMember(memberId, memberName) {
    const confirmed = confirm(
        `Are you sure you want to remove ${memberName} from this household?\n\nThis action cannot be undone.`
    );

    if (!confirmed) return;

    try {
        const response = await fetch(`/household/${currentHouseholdId}/member/${memberId}/remove`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showToast('Member removed successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to remove member', 'error');
        }
    } catch (error) {
        console.error('Error removing member:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Leave household
 */
async function leaveHousehold() {
    const confirmed = confirm(
        'Are you sure you want to leave this household?\n\nYou will lose access to all shared data.'
    );

    if (!confirmed) return;

    try {
        const response = await fetch(`/household/${currentHouseholdId}/leave`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showToast('You have left the household', 'success');
            setTimeout(() => window.location.href = '/dashboard', 1500);
        } else {
            showToast(data.error || 'Failed to leave household', 'error');
        }
    } catch (error) {
        console.error('Error leaving household:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Update household settings
 */
async function updateHouseholdSettings(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    try {
        const response = await fetch(`/household/${currentHouseholdId}/update`, {
            method: 'POST',
            body: new URLSearchParams(formData)
        });

        const data = await response.json();

        if (data.success) {
            showToast('Settings updated successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to update settings', 'error');
        }
    } catch (error) {
        console.error('Error updating settings:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Cancel pending invitation
 */
async function cancelInvitation(invitationId) {
    const confirmed = confirm('Cancel this invitation?');
    if (!confirmed) return;

    try {
        const response = await fetch(`/invitation/${invitationId}/cancel`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showToast('Invitation cancelled', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.error || 'Failed to cancel invitation', 'error');
        }
    } catch (error) {
        console.error('Error cancelling invitation:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Resend invitation
 */
async function resendInvitation(invitationId) {
    try {
        const response = await fetch(`/invitation/${invitationId}/resend`, {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            showToast('Invitation resent successfully 📧', 'success');
        } else {
            showToast(data.error || 'Failed to resend invitation', 'error');
        }
    } catch (error) {
        console.error('Error resending invitation:', error);
        showToast('An error occurred. Please try again.', 'error');
    }
}

/**
 * Filter household members
 */
function filterMembers(filterType) {
    const memberCards = document.querySelectorAll('.member-card');

    memberCards.forEach(card => {
        const role = card.dataset.role;

        if (filterType === 'all' || role === filterType) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });

    // Update filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-gray-100', 'text-gray-700');
    });

    event.target.classList.remove('bg-gray-100', 'text-gray-700');
    event.target.classList.add('bg-blue-600', 'text-white');
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Check if toast function already exists globally
    if (typeof window.showToast === 'function') {
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
window.initHousehold = initHousehold;
window.openInviteMemberModal = openInviteMemberModal;
window.closeInviteMemberModal = closeInviteMemberModal;
window.submitInvitation = submitInvitation;
window.changeMemberRole = changeMemberRole;
window.removeMember = removeMember;
window.leaveHousehold = leaveHousehold;
window.updateHouseholdSettings = updateHouseholdSettings;
window.cancelInvitation = cancelInvitation;
window.resendInvitation = resendInvitation;
window.filterMembers = filterMembers;
