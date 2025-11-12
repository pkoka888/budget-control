<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Feed - Budget Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <?php
    $activities = $data['activities'] ?? [];
    $householdId = $data['household_id'] ?? 0;
    $householdName = $data['household_name'] ?? 'Household';
    $filters = $data['filters'] ?? [];
    $currentFilter = $data['current_filter'] ?? 'all';
    ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Activity Feed</h1>
                <p class="text-gray-600 dark:text-gray-400"><?= htmlspecialchars($householdName) ?></p>
            </div>
            <a href="/household/<?= $householdId ?>" class="text-blue-600 hover:text-blue-700">← Back to Household</a>
        </div>

        <!-- Filter Tabs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
            <div class="flex flex-wrap gap-2">
                <button onclick="filterActivities('all')"
                        class="filter-btn px-4 py-2 rounded-lg font-medium <?= $currentFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' ?>">
                    📋 All Activity
                </button>
                <button onclick="filterActivities('transaction')"
                        class="filter-btn px-4 py-2 rounded-lg font-medium <?= $currentFilter === 'transaction' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' ?>">
                    💰 Transactions
                </button>
                <button onclick="filterActivities('budget')"
                        class="filter-btn px-4 py-2 rounded-lg font-medium <?= $currentFilter === 'budget' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' ?>">
                    📊 Budgets
                </button>
                <button onclick="filterActivities('member')"
                        class="filter-btn px-4 py-2 rounded-lg font-medium <?= $currentFilter === 'member' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' ?>">
                    👥 Members
                </button>
                <button onclick="filterActivities('approval')"
                        class="filter-btn px-4 py-2 rounded-lg font-medium <?= $currentFilter === 'approval' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' ?>">
                    ✅ Approvals
                </button>
                <button onclick="filterActivities('chore')"
                        class="filter-btn px-4 py-2 rounded-lg font-medium <?= $currentFilter === 'chore' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' ?>">
                    ⭐ Chores
                </button>
                <button onclick="filterActivities('important')"
                        class="filter-btn px-4 py-2 rounded-lg font-medium <?= $currentFilter === 'important' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' ?>">
                    🔥 Important
                </button>
            </div>
        </div>

        <?php if (!empty($activities)): ?>
        <!-- Activity Timeline -->
        <div class="space-y-4">
            <?php
            $currentDate = null;
            foreach ($activities as $activity):
                $activityDate = date('Y-m-d', strtotime($activity['created_at']));
                $showDateHeader = $activityDate !== $currentDate;
                $currentDate = $activityDate;
            ?>

                <?php if ($showDateHeader): ?>
                <!-- Date Header -->
                <div class="flex items-center gap-4 my-6">
                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        <?php
                        $date = new DateTime($activity['created_at']);
                        $now = new DateTime();
                        $diff = $now->diff($date);

                        if ($diff->days === 0) {
                            echo 'Today';
                        } elseif ($diff->days === 1) {
                            echo 'Yesterday';
                        } else {
                            echo $date->format('F j, Y');
                        }
                        ?>
                    </div>
                    <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                </div>
                <?php endif; ?>

                <!-- Activity Item -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition-shadow p-6
                            <?= $activity['is_important'] ? 'border-l-4 border-red-500' : '' ?>">
                    <div class="flex gap-4">
                        <!-- Icon -->
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center text-2xl
                                        <?= getActivityBackgroundColor($activity['activity_type']) ?>">
                                <?= getActivityIcon($activity['activity_type']) ?>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        <?= htmlspecialchars($activity['username']) ?>
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        <?= getActionText($activity['action']) ?>
                                    </span>
                                    <?php if ($activity['is_important']): ?>
                                    <span class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded text-xs font-medium">
                                        Important
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap ml-4">
                                    <?= date('g:i A', strtotime($activity['created_at'])) ?>
                                </span>
                            </div>

                            <p class="text-gray-700 dark:text-gray-300 mb-2">
                                <?= htmlspecialchars($activity['description']) ?>
                            </p>

                            <!-- Metadata -->
                            <?php if ($activity['metadata']): ?>
                            <div class="flex flex-wrap gap-3 text-sm text-gray-600 dark:text-gray-400">
                                <?php
                                $metadata = json_decode($activity['metadata'], true);
                                if ($metadata):
                                ?>
                                    <?php if (isset($metadata['amount'])): ?>
                                    <span class="font-medium text-lg text-gray-900 dark:text-white">
                                        <?= number_format($metadata['amount'], 2) ?> CZK
                                    </span>
                                    <?php endif; ?>

                                    <?php if (isset($metadata['category'])): ?>
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">
                                        📂 <?= htmlspecialchars($metadata['category']) ?>
                                    </span>
                                    <?php endif; ?>

                                    <?php if (isset($metadata['status'])): ?>
                                    <span class="px-2 py-1 rounded <?= getStatusBadgeColor($metadata['status']) ?>">
                                        <?= ucfirst($metadata['status']) ?>
                                    </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Action Link -->
                            <?php if ($activity['entity_id']): ?>
                            <div class="mt-3">
                                <a href="<?= getEntityUrl($activity['entity_type'], $activity['entity_id']) ?>"
                                   class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                                    View Details →
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Load More -->
            <?php if (count($activities) >= 50): ?>
            <div class="text-center py-6">
                <button onclick="loadMoreActivities()"
                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 font-medium">
                    Load More Activity
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <div class="text-6xl mb-4">📭</div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">No Activity Yet</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Activity from your household will appear here as it happens.
            </p>
            <a href="/household/<?= $householdId ?>" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                Back to Household
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function filterActivities(type) {
            window.location.href = `/activity/${<?= $householdId ?>}?filter=${type}`;
        }

        async function loadMoreActivities() {
            const oldestActivity = document.querySelector('.activity-item:last-child');
            const beforeId = oldestActivity?.dataset.activityId || 0;

            try {
                const response = await fetch(`/activity/${<?= $householdId ?>}?before=${beforeId}&filter=<?= $currentFilter ?>`);
                const data = await response.json();

                if (data.success && data.activities.length > 0) {
                    // Append activities to timeline
                    // (Implementation would insert HTML dynamically)
                    location.reload(); // Simple reload for now
                } else {
                    showToast('No more activities to load', 'info');
                }
            } catch (error) {
                showToast('Failed to load more activities', 'error');
            }
        }

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
    </script>
</body>
</html>

<?php
// Helper Functions
function getActivityIcon($type) {
    $icons = [
        'transaction' => '💰',
        'budget' => '📊',
        'goal' => '🎯',
        'account' => '🏦',
        'category' => '📁',
        'member' => '👥',
        'invitation' => '📨',
        'approval' => '✅',
        'chore' => '⭐',
        'allowance' => '💵',
        'money_request' => '🤚',
        'comment' => '💬',
        'system' => '⚙️'
    ];
    return $icons[$type] ?? '📋';
}

function getActivityBackgroundColor($type) {
    $colors = [
        'transaction' => 'bg-green-100 dark:bg-green-900',
        'budget' => 'bg-blue-100 dark:bg-blue-900',
        'goal' => 'bg-purple-100 dark:bg-purple-900',
        'member' => 'bg-yellow-100 dark:bg-yellow-900',
        'invitation' => 'bg-pink-100 dark:bg-pink-900',
        'approval' => 'bg-green-100 dark:bg-green-900',
        'chore' => 'bg-orange-100 dark:bg-orange-900',
        'allowance' => 'bg-teal-100 dark:bg-teal-900'
    ];
    return $colors[$type] ?? 'bg-gray-100 dark:bg-gray-700';
}

function getActionText($action) {
    $actions = [
        'created' => 'created',
        'updated' => 'updated',
        'deleted' => 'deleted',
        'shared' => 'shared',
        'unshared' => 'made private',
        'approved' => 'approved',
        'rejected' => 'rejected',
        'completed' => 'completed',
        'verified' => 'verified',
        'joined' => 'joined',
        'left' => 'left',
        'invited' => 'invited',
        'accepted' => 'accepted invitation to',
        'commented' => 'commented on'
    ];
    return $actions[$action] ?? $action;
}

function getStatusBadgeColor($status) {
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'inactive' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-800';
}

function getEntityUrl($entityType, $entityId) {
    $urls = [
        'transaction' => "/transactions/{$entityId}",
        'budget' => "/budgets/{$entityId}",
        'goal' => "/goals/{$entityId}",
        'account' => "/accounts/{$entityId}",
        'approval' => "/approval/{$entityId}",
        'chore' => "/chores/{$entityId}"
    ];
    return $urls[$entityType] ?? "#";
}
?>
