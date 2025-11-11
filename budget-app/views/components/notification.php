<?php
/**
 * Notification Component
 * Provides success, error, warning, and info notifications
 */

// Default configuration
$notificationConfig = $notificationConfig ?? [
    'type' => 'success', // success, error, warning, info
    'title' => '',
    'message' => '',
    'dismissible' => true,
    'autoHide' => false,
    'duration' => 5000, // milliseconds
    'class' => '',
    'icon' => null // auto-determined based on type
];

$type = $notificationConfig['type'];
$title = $notificationConfig['title'];
$message = $notificationConfig['message'];
$dismissible = $notificationConfig['dismissible'];
$autoHide = $notificationConfig['autoHide'];
$duration = $notificationConfig['duration'];
$class = $notificationConfig['class'];
$icon = $notificationConfig['icon'];

// Auto-determine icon based on type
if (!$icon) {
    switch ($type) {
        case 'success':
            $icon = '✅';
            break;
        case 'error':
            $icon = '❌';
            break;
        case 'warning':
            $icon = '⚠️';
            break;
        case 'info':
        default:
            $icon = 'ℹ️';
            break;
    }
}

// Generate unique ID for auto-hide functionality
$notificationId = 'notification-' . uniqid();
?>

<div id="<?php echo $notificationId; ?>" class="alert alert-<?php echo $type; ?> animate-slide-in-down <?php echo $class; ?>" role="alert" aria-live="polite">
    <div class="flex items-start gap-3 flex-1">
        <?php if ($icon): ?>
            <span aria-hidden="true" class="text-lg flex-shrink-0">
                <?php echo htmlspecialchars($icon); ?>
            </span>
        <?php endif; ?>

        <div class="flex-1">
            <?php if ($title): ?>
                <strong><?php echo htmlspecialchars($title); ?></strong>
            <?php endif; ?>
            <?php if ($message): ?>
                <p class="<?php echo $title ? 'mt-1' : ''; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($dismissible): ?>
            <button type="button" class="text-lg hover:opacity-70 flex-shrink-0" aria-label="Zavřít upozornění" onclick="dismissNotification('<?php echo $notificationId; ?>')">
                <span aria-hidden="true">&times;</span>
            </button>
        <?php endif; ?>
    </div>

    <?php if ($autoHide): ?>
        <div class="notification-progress" style="animation-duration: <?php echo $duration; ?>ms"></div>
    <?php endif; ?>
</div>

<style>
/* Notification Styles */
.notification {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    border: 1px solid;
    box-shadow: var(--shadow);
    margin-bottom: 1rem;
    animation: slideInFromTop 0.3s ease-out;
    overflow: hidden;
}

.notification-success {
    background-color: #d1fae5;
    border-color: #a7f3d0;
    color: #065f46;
}

.notification-error {
    background-color: #fee2e2;
    border-color: #fecaca;
    color: #991b1b;
}

.notification-warning {
    background-color: #fef3c7;
    border-color: #fde68a;
    color: #92400e;
}

.notification-info {
    background-color: #dbeafe;
    border-color: #bfdbfe;
    color: #1e40af;
}

.notification-content {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    flex: 1;
}

.notification-icon {
    font-size: 1.25rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.notification-text {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.notification-message {
    font-size: 0.8125rem;
    line-height: 1.4;
}

.notification-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0;
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
    opacity: 0.7;
    transition: opacity 0.2s;
    flex-shrink: 0;
}

.notification-close:hover {
    opacity: 1;
    background-color: rgba(0, 0, 0, 0.1);
}

.notification-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: currentColor;
    animation: progressBar linear forwards;
}

@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes progressBar {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

/* Notification container for stacking */
.notifications-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 1000;
    max-width: 400px;
    pointer-events: none;
}

.notifications-container .notification {
    pointer-events: auto;
    margin-bottom: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .notifications-container {
        left: 1rem;
        right: 1rem;
        max-width: none;
    }

    .notification {
        padding: 0.875rem 1rem;
    }

    .notification-content {
        gap: 0.5rem;
    }

    .notification-icon {
        font-size: 1.125rem;
    }

    .notification-title {
        font-size: 0.8125rem;
    }

    .notification-message {
        font-size: 0.75rem;
    }
}

/* Dark mode adjustments */
[data-theme="dark"] .notification-success {
    background-color: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

[data-theme="dark"] .notification-error {
    background-color: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.2);
    color: #f87171;
}

[data-theme="dark"] .notification-warning {
    background-color: rgba(245, 158, 11, 0.1);
    border-color: rgba(245, 158, 11, 0.2);
    color: #fbbf24;
}

[data-theme="dark"] .notification-info {
    background-color: rgba(59, 130, 246, 0.1);
    border-color: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
}

[data-theme="dark"] .notification-close:hover {
    background-color: rgba(255, 255, 255, 0.1);
}
</style>

<script>
// Notification dismissal functionality
function dismissNotification(notificationId) {
    const notification = document.getElementById(notificationId);
    if (notification) {
        notification.style.animation = 'slideOutToRight 0.3s ease-in forwards';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
}

// Auto-hide notifications
document.addEventListener('DOMContentLoaded', function() {
    const autoHideNotifications = document.querySelectorAll('.notification[aria-live]');
    autoHideNotifications.forEach(notification => {
        const progressBar = notification.querySelector('.notification-progress');
        if (progressBar) {
            const duration = parseInt(progressBar.style.animationDuration);
            setTimeout(() => {
                dismissNotification(notification.id);
            }, duration);
        }
    });
});

// Add slide out animation
const style = document.createElement('style');
style.textContent = `
@keyframes slideOutToRight {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100%);
    }
}
`;
document.head.appendChild(style);
</script>
