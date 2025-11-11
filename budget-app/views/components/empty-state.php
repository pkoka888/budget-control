<?php
/**
 * Empty State Component
 * Provides helpful empty state illustrations and messages
 */

// Default configuration
$emptyConfig = $emptyConfig ?? [
    'icon' => '📊', // emoji or icon class
    'title' => 'Žádná data nebyla nalezena',
    'message' => 'Začněte přidáním první položky.',
    'actionText' => 'Přidat položku',
    'actionUrl' => '#',
    'secondaryActionText' => null,
    'secondaryActionUrl' => null,
    'class' => '',
    'size' => 'default' // small, default, large
];

$icon = $emptyConfig['icon'] ?? '📊';
$title = $emptyConfig['title'] ?? 'Žádná data nebyla nalezena';
$message = $emptyConfig['message'] ?? 'Začněte přidáním první položky.';
$actionText = $emptyConfig['actionText'] ?? 'Přidat položku';
$actionUrl = $emptyConfig['actionUrl'] ?? '#';
$secondaryActionText = $emptyConfig['secondaryActionText'] ?? null;
$secondaryActionUrl = $emptyConfig['secondaryActionUrl'] ?? null;
$class = $emptyConfig['class'] ?? '';
$size = $emptyConfig['size'] ?? 'default';
?>

<div class="empty-state <?php echo $size === 'small' ? 'empty-state-small' : ($size === 'large' ? 'empty-state-large' : ''); ?> <?php echo htmlspecialchars($class); ?>">
    <div class="empty-state-content">
        <?php if ($icon): ?>
            <div class="empty-state-icon" role="img" aria-label="Ilustrace prázdného stavu">
                <?php echo htmlspecialchars($icon); ?>
            </div>
        <?php endif; ?>

        <h3 class="empty-state-title"><?php echo htmlspecialchars($title); ?></h3>
        <p class="empty-state-message"><?php echo htmlspecialchars($message); ?></p>

        <div class="empty-state-actions">
            <?php if ($actionText && $actionUrl): ?>
                <a href="<?php echo htmlspecialchars($actionUrl); ?>" class="btn btn-primary">
                    <?php echo htmlspecialchars($actionText); ?>
                </a>
            <?php endif; ?>

            <?php if ($secondaryActionText && $secondaryActionUrl): ?>
                <a href="<?php echo htmlspecialchars($secondaryActionUrl); ?>" class="btn btn-secondary">
                    <?php echo htmlspecialchars($secondaryActionText); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Empty State Styles */
.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #64748b; /* slate-gray-500 */
}

.empty-state-small {
    padding: 2rem 1rem;
}

.empty-state-large {
    padding: 4rem 3rem;
}

.empty-state-content {
    max-width: 400px;
    margin: 0 auto;
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.6;
    display: block;
}

.empty-state-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #0f172a; /* slate-gray-900 */
    margin-bottom: 0.5rem;
}

.empty-state-message {
    font-size: 0.875rem;
    color: #64748b; /* slate-gray-500 */
    margin-bottom: 2rem;
    line-height: 1.5;
}

.empty-state-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
}

.empty-state-actions .btn {
    min-width: 120px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .empty-state {
        padding: 2rem 1rem;
    }

    .empty-state-large {
        padding: 3rem 1.5rem;
    }

    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .empty-state-title {
        font-size: 1.125rem;
    }

    .empty-state-message {
        font-size: 0.8125rem;
        margin-bottom: 1.5rem;
    }

    .empty-state-actions {
        flex-direction: column;
        align-items: center;
    }

    .empty-state-actions .btn {
        width: 100%;
        max-width: 200px;
    }
}

/* Dark mode adjustments */
[data-theme="dark"] .empty-state-icon {
    opacity: 0.4;
}

[data-theme="dark"] .empty-state-title {
    color: #f8fafc; /* slate-gray-50 */
}

[data-theme="dark"] .empty-state-message {
    color: #94a3b8; /* slate-gray-400 */
}
</style>
