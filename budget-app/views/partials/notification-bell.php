<?php
/**
 * Notification Bell Component
 * Displays notification icon with count and dropdown
 *
 * Usage: <?php include 'views/partials/notification-bell.php'; ?>
 */

// Get unread count (in real app, this would be from a service)
$unreadCount = $data['unread_count'] ?? 0;
$userId = $_SESSION['user_id'] ?? 0;
?>

<!-- Notification Bell -->
<div class="relative notification-bell-container" data-user-id="<?= $userId ?>">
    <!-- Bell Icon Button -->
    <button
        onclick="toggleNotificationDropdown()"
        class="relative p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
        aria-label="Notifications"
        id="notification-bell-button"
    >
        <!-- Bell Icon -->
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        <!-- Badge Count -->
        <?php if ($unreadCount > 0): ?>
        <span id="notification-count" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full min-w-[20px]">
            <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
        </span>
        <?php endif; ?>
    </button>

    <!-- Dropdown -->
    <div
        id="notification-dropdown"
        class="hidden absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-[600px] overflow-hidden flex flex-col"
    >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h3>
            <button
                onclick="markAllAsRead()"
                class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400"
            >
                Mark all read
            </button>
        </div>

        <!-- Notification List -->
        <div id="notification-list" class="overflow-y-auto flex-1 max-h-[400px]">
            <div class="notification-loading p-6 text-center text-gray-500">
                <svg class="animate-spin h-6 w-6 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p>Loading notifications...</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <a href="/notifications" class="block text-center text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                View all notifications
            </a>
        </div>
    </div>
</div>

<!-- Notification Bell Styles -->
<style>
.notification-bell-container {
    display: inline-block;
}

.notification-item {
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    cursor: pointer;
    transition: background-color 0.15s;
}

.notification-item:hover {
    background-color: #f9fafb;
}

.dark .notification-item:hover {
    background-color: #374151;
}

.notification-item.unread {
    background-color: #eff6ff;
}

.dark .notification-item.unread {
    background-color: #1e3a5f;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-center;
    font-size: 20px;
    flex-shrink: 0;
}

.notification-priority-urgent {
    border-left: 3px solid #ef4444;
}

.notification-priority-high {
    border-left: 3px solid #f59e0b;
}
</style>

<!-- Notification Bell JavaScript -->
<script>
let notificationDropdownOpen = false;
let notifications = [];
let pollingInterval = null;

// Toggle dropdown
function toggleNotificationDropdown() {
    const dropdown = document.getElementById('notification-dropdown');
    notificationDropdownOpen = !notificationDropdownOpen;

    if (notificationDropdownOpen) {
        dropdown.classList.remove('hidden');
        loadNotifications();
        startPolling();
    } else {
        dropdown.classList.add('hidden');
        stopPolling();
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const container = event.target.closest('.notification-bell-container');
    if (!container && notificationDropdownOpen) {
        toggleNotificationDropdown();
    }
});

// Load notifications
async function loadNotifications() {
    try {
        const response = await fetch('/notifications?unread=0');
        const data = await response.json();

        if (data.success) {
            notifications = data.notifications || [];
            renderNotifications();
            updateNotificationCount(data.unread_count || 0);
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
        showNotificationError();
    }
}

// Render notifications
function renderNotifications() {
    const list = document.getElementById('notification-list');

    if (notifications.length === 0) {
        list.innerHTML = `
            <div class="p-12 text-center text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="font-medium">No notifications</p>
                <p class="text-sm">You're all caught up!</p>
            </div>
        `;
        return;
    }

    list.innerHTML = notifications.map(notif => `
        <div
            class="notification-item ${notif.is_read ? '' : 'unread'} ${notif.priority === 'urgent' || notif.priority === 'high' ? 'notification-priority-' + notif.priority : ''}"
            onclick="handleNotificationClick(${notif.id}, '${notif.action_url || ''}')"
        >
            <div class="flex gap-3">
                <div class="notification-icon bg-gray-100 dark:bg-gray-700">
                    ${notif.icon || getNotificationIcon(notif.notification_type)}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                        ${escapeHtml(notif.title)}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                        ${escapeHtml(notif.message)}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        ${formatNotificationTime(notif.created_at)}
                    </p>
                </div>
                ${!notif.is_read ? '<div class="w-2 h-2 bg-blue-600 rounded-full mt-2"></div>' : ''}
            </div>
        </div>
    `).join('');
}

// Handle notification click
async function handleNotificationClick(notificationId, actionUrl) {
    // Mark as read
    try {
        await fetch(`/notifications/${notificationId}/read`, { method: 'POST' });

        // Update UI
        const notification = notifications.find(n => n.id === notificationId);
        if (notification) {
            notification.is_read = 1;
            renderNotifications();
            updateNotificationCount();
        }

        // Navigate if has action URL
        if (actionUrl) {
            window.location.href = actionUrl;
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
}

// Mark all as read
async function markAllAsRead() {
    try {
        const response = await fetch('/notifications/read-all', { method: 'POST' });
        const data = await response.json();

        if (data.success) {
            notifications.forEach(n => n.is_read = 1);
            renderNotifications();
            updateNotificationCount(0);
            showToast('All notifications marked as read', 'success');
        }
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    }
}

// Update notification count
function updateNotificationCount(count) {
    if (count === undefined) {
        count = notifications.filter(n => !n.is_read).length;
    }

    const badge = document.getElementById('notification-count');
    if (count > 0) {
        if (badge) {
            badge.textContent = count > 99 ? '99+' : count;
        } else {
            const button = document.getElementById('notification-bell-button');
            const newBadge = document.createElement('span');
            newBadge.id = 'notification-count';
            newBadge.className = 'absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full min-w-[20px]';
            newBadge.textContent = count > 99 ? '99+' : count;
            button.appendChild(newBadge);
        }
    } else if (badge) {
        badge.remove();
    }
}

// Polling for new notifications
function startPolling() {
    // Poll every 30 seconds while dropdown is open
    pollingInterval = setInterval(loadNotifications, 30000);
}

function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

// Helper functions
function getNotificationIcon(type) {
    const icons = {
        'activity': '📋',
        'approval': '✅',
        'invitation': '📨',
        'alert': '⚠️',
        'achievement': '🏆'
    };
    return icons[type] || '🔔';
}

function formatNotificationTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotificationError() {
    const list = document.getElementById('notification-list');
    list.innerHTML = `
        <div class="p-12 text-center text-red-600">
            <p>Failed to load notifications</p>
            <button onclick="loadNotifications()" class="mt-2 text-sm text-blue-600 hover:text-blue-700">
                Try again
            </button>
        </div>
    `;
}

// Initial poll on page load (background)
setTimeout(() => {
    fetch('/notifications?unread=1')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                updateNotificationCount(data.unread_count || 0);
            }
        })
        .catch(console.error);
}, 1000);

// Poll for new notifications every 60 seconds (background)
setInterval(() => {
    if (!notificationDropdownOpen) {
        fetch('/notifications?unread=1')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const oldCount = notifications.filter(n => !n.is_read).length;
                    const newCount = data.unread_count || 0;

                    if (newCount > oldCount) {
                        // New notification!
                        updateNotificationCount(newCount);
                        // Optional: show desktop notification
                        showNewNotificationAlert();
                    }
                }
            })
            .catch(console.error);
    }
}, 60000);

function showNewNotificationAlert() {
    // Visual feedback for new notification
    const bell = document.getElementById('notification-bell-button');
    bell.classList.add('animate-bounce');
    setTimeout(() => bell.classList.remove('animate-bounce'), 1000);
}

// Toast function (if not already defined globally)
if (typeof showToast === 'undefined') {
    function showToast(message, type = 'info') {
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
}
</script>
