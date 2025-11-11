<?php
/**
 * Loading Skeleton Component
 * Provides animated skeleton placeholders for loading states
 */

// Default configuration
$skeletonConfig = $skeletonConfig ?? [
    'type' => 'card', // card, table, text, avatar
    'lines' => 3,
    'showAvatar' => false,
    'class' => ''
];

$type = $skeletonConfig['type'];
$lines = $skeletonConfig['lines'];
$showAvatar = $skeletonConfig['showAvatar'];
$class = $skeletonConfig['class'];
?>

<div class="skeleton-wrapper <?php echo htmlspecialchars($class); ?>" role="status" aria-label="Načítání obsahu">
    <?php if ($type === 'card'): ?>
        <!-- Card Skeleton -->
        <div class="skeleton-card">
            <?php if ($showAvatar): ?>
                <div class="skeleton-avatar"></div>
            <?php endif; ?>
            <div class="skeleton-text">
                <div class="skeleton-text-line skeleton-title"></div>
                <?php for ($i = 0; $i < $lines; $i++): ?>
                    <div class="skeleton-text-line"></div>
                <?php endfor; ?>
            </div>
        </div>

    <?php elseif ($type === 'table'): ?>
        <!-- Table Skeleton -->
        <div class="skeleton-table">
            <?php for ($i = 0; $i < $lines; $i++): ?>
                <div class="skeleton-table-row">
                    <div class="skeleton-table-cell"></div>
                    <div class="skeleton-table-cell"></div>
                    <div class="skeleton-table-cell"></div>
                    <div class="skeleton-table-cell skeleton-table-cell-small"></div>
                </div>
            <?php endfor; ?>
        </div>

    <?php elseif ($type === 'text'): ?>
        <!-- Text Skeleton -->
        <div class="skeleton-text">
            <?php for ($i = 0; $i < $lines; $i++): ?>
                <div class="skeleton-text-line <?php echo $i === 0 ? 'skeleton-title' : ''; ?>"></div>
            <?php endfor; ?>
        </div>

    <?php elseif ($type === 'avatar'): ?>
        <!-- Avatar Skeleton -->
        <div class="skeleton-avatar-large"></div>

    <?php endif; ?>

    <span class="sr-only">Načítání...</span>
</div>

<style>
/* Skeleton Loading Styles */
@keyframes skeleton-pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.skeleton-wrapper {
    animation: skeleton-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.skeleton-card {
    background: var(--bg-card);
    border: 1px solid var(--border-primary);
    border-radius: 0.5rem;
    padding: 1.5rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.skeleton-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gray-200);
    flex-shrink: 0;
}

.skeleton-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--gray-200);
    margin: 0 auto;
}

.skeleton-text {
    flex: 1;
}

.skeleton-text-line {
    height: 12px;
    background: var(--gray-200);
    border-radius: 4px;
    margin-bottom: 8px;
}

.skeleton-title {
    width: 60%;
    height: 16px;
    margin-bottom: 12px;
}

.skeleton-text-line:last-child {
    width: 80%;
}

.skeleton-text-line:nth-child(2) {
    width: 90%;
}

.skeleton-text-line:nth-child(3) {
    width: 70%;
}

.skeleton-table {
    background: var(--bg-card);
    border: 1px solid var(--border-primary);
    border-radius: 0.5rem;
    overflow: hidden;
}

.skeleton-table-row {
    display: flex;
    padding: 1rem;
    border-bottom: 1px solid var(--border-primary);
}

.skeleton-table-row:last-child {
    border-bottom: none;
}

.skeleton-table-cell {
    flex: 1;
    height: 12px;
    background: var(--gray-200);
    border-radius: 4px;
    margin-right: 1rem;
}

.skeleton-table-cell:last-child {
    margin-right: 0;
}

.skeleton-table-cell-small {
    flex: 0 0 80px;
}

/* Dark mode adjustments */
[data-theme="dark"] .skeleton-card,
[data-theme="dark"] .skeleton-table {
    background: var(--bg-card);
    border-color: var(--border-primary);
}

[data-theme="dark"] .skeleton-text-line,
[data-theme="dark"] .skeleton-avatar,
[data-theme="dark"] .skeleton-avatar-large,
[data-theme="dark"] .skeleton-table-cell {
    background: var(--gray-600);
}
</style>
