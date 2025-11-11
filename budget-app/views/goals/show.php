
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <a href="/goals" class="text-blue-600 hover:underline">← Back to Goals</a>
                <div class="flex items-center space-x-4">
                    <button onclick="shareGoal()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        📤 Share Goal
                    </button>
                    <button onclick="editGoal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        ✏️ Edit Goal
                    </button>
                </div>
            </div>

            <!-- Goal Header -->
            <div class="goal-header bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($goal['name']); ?></h1>
                        <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($goal['description'] ?? ''); ?></p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-500">Priority</div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium
                            <?php
                            switch($goal['priority']) {
                                case 'high': echo 'bg-red-100 text-red-800'; break;
                                case 'medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'low': echo 'bg-green-100 text-green-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($goal['priority'] ?? 'medium'); ?>
                        </span>
                    </div>
                </div>

                <!-- Progress Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600"><?php echo number_format($goal['current_amount'], 0, ',', ' '); ?> Kč</div>
                        <div class="text-sm text-blue-600">Current Amount</div>
                    </div>
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600"><?php echo number_format($goal['target_amount'], 0, ',', ' '); ?> Kč</div>
                        <div class="text-sm text-green-600">Target Amount</div>
                    </div>
                    <div class="text-center p-4 bg-purple-50 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600"><?php echo number_format($goal['remaining_amount'], 0, ',', ' '); ?> Kč</div>
                        <div class="text-sm text-purple-600">Remaining</div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="progress-container mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                        <span class="text-sm font-medium text-gray-700"><?php echo number_format($goal['progress_percentage'], 1, ',', ' '); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-bar bg-blue-600" style="width: <?php echo min($goal['progress_percentage'], 100); ?>%"></div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center">
                        <div class="text-gray-500">Started</div>
                        <div class="font-semibold"><?php echo date('M j, Y', strtotime($goal['created_at'])); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-gray-500">Target Date</div>
                        <div class="font-semibold"><?php echo date('M j, Y', strtotime($goal['target_date'])); ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-gray-500">Days Left</div>
                        <div class="font-semibold <?php echo $goal['days_left'] < 30 ? 'text-red-600' : 'text-gray-800'; ?>">
                            <?php echo $goal['days_left']; ?> days
                        </div>
                    </div>
                </div>
            </div>

            <!-- Savings Projections -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">💡 Savings Projections</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="projection-card p-4 border rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">If you save</div>
                        <div class="text-lg font-bold text-blue-600"><?php echo number_format($goal['monthly_savings_needed'], 0, ',', ' '); ?> Kč/month</div>
                        <div class="text-sm text-gray-500">You'll reach your goal on time</div>
                    </div>
                    <div class="projection-card p-4 border rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">If you save</div>
                        <div class="text-lg font-bold text-green-600"><?php echo number_format($goal['monthly_savings_needed'] * 1.5, 0, ',', ' '); ?> Kč/month</div>
                        <div class="text-sm text-gray-500">You'll reach <?php echo round($goal['progress_percentage'] + 25, 0); ?>% ahead</div>
                    </div>
                    <div class="projection-card p-4 border rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">If you save</div>
                        <div class="text-lg font-bold text-purple-600"><?php echo number_format($goal['monthly_savings_needed'] * 2, 0, ',', ' '); ?> Kč/month</div>
                        <div class="text-sm text-gray-500">You'll complete in <?php echo round($goal['days_left'] * 0.5, 0); ?> days</div>
                    </div>
                </div>
            </div>

            <!-- Milestones -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">🎯 Milestones</h3>
                    <button onclick="createMilestone()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors text-sm">
                        + Add Milestone
                    </button>
                </div>

                <?php if (!empty($goal['milestones'])): ?>
                    <div class="space-y-3">
                        <?php foreach ($goal['milestones'] as $milestone): ?>
                            <div class="milestone-item flex items-center justify-between p-4 border rounded-lg <?php echo $milestone['completed'] ? 'milestone-completed bg-green-50 border-green-200' : 'bg-gray-50'; ?>">
                                <div class="flex items-center space-x-3">
                                    <div class="milestone-indicator w-6 h-6 rounded-full flex items-center justify-center text-sm font-bold
                                        <?php echo $milestone['completed'] ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600'; ?>">
                                        <?php echo $milestone['completed'] ? '✓' : $milestone['order']; ?>
                                    </div>
                                    <div>
                                        <h4 class="font-medium <?php echo $milestone['completed'] ? 'text-green-800' : 'text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($milestone['name']); ?>
                                        </h4>
                                        <p class="text-sm <?php echo $milestone['completed'] ? 'text-green-600' : 'text-gray-600'; ?>">
                                            <?php echo number_format($milestone['target_amount'], 0, ',', ' '); ?> Kč
                                            <?php if ($milestone['completed']): ?>
                                                - Completed <?php echo date('M j, Y', strtotime($milestone['completed_at'])); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <?php if (!$milestone['completed']): ?>
                                    <button onclick="completeMilestone(<?php echo $milestone['id']; ?>)" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                        Mark Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <div class="text-4xl mb-2">🎯</div>
                        <p>No milestones set yet. Add your first milestone to track progress!</p>
                    </div>
                <?php endif; ?>

                <div class="mt-4 text-center">
                    <a href="/goals/<?php echo $goal['id']; ?>/milestones" class="text-blue-600 hover:underline text-sm">
                        View all milestones →
                    </a>
                </div>
            </div>

            <!-- Recent Deposits -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800">💰 Recent Deposits</h3>
                    <button onclick="addDeposit()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                        + Add Deposit
                    </button>
                </div>

                <?php if (!empty($goal['deposits'])): ?>
                    <div class="space-y-2">
                        <?php foreach (array_slice($goal['deposits'], 0, 5) as $deposit): ?>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                <div>
                                    <div class="font-medium"><?php echo number_format($deposit['amount'], 0, ',', ' '); ?> Kč</div>
                                    <div class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($deposit['date'])); ?></div>
                                </div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($deposit['note'] ?? ''); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-6 text-gray-500">
                        <p>No deposits made yet. Start saving towards your goal!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Goal Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">⚙️ Goal Actions</h3>
                <div class="goal-actions grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button onclick="addDeposit()" class="flex items-center space-x-2 p-3 border border-green-300 rounded-lg hover:bg-green-50 transition-colors">
                        <span class="text-green-600">💰</span>
                        <span class="text-green-800 font-medium">Add Deposit</span>
                    </button>
                    <button onclick="createMilestone()" class="flex items-center space-x-2 p-3 border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors">
                        <span class="text-blue-600">🎯</span>
                        <span class="text-blue-800 font-medium">Create Milestone</span>
                    </button>
                    <button onclick="editGoal()" class="flex items-center space-x-2 p-3 border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors">
                        <span class="text-purple-600">✏️</span>
                        <span class="text-purple-800 font-medium">Edit Goal</span>
                    </button>
                    <button onclick="shareGoal()" class="flex items-center space-x-2 p-3 border border-indigo-300 rounded-lg hover:bg-indigo-50 transition-colors">
                        <span class="text-indigo-600">📤</span>
                        <span class="text-indigo-800 font-medium">Share Goal</span>
                    </button>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <button onclick="deleteGoal()" class="flex items-center space-x-2 p-3 border border-red-300 rounded-lg hover:bg-red-50 transition-colors w-full md:w-auto">
                        <span class="text-red-600">🗑️</span>
                        <span class="text-red-800 font-medium">Delete Goal</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editGoal() {
    alert('Edit goal functionality');
    // TODO: Implement edit goal modal/form
}

function addDeposit() {
    alert('Add deposit functionality');
    // TODO: Implement add deposit modal/form
}

function createMilestone() {
    alert('Create milestone functionality');
    // TODO: Implement create milestone modal/form
}

function completeMilestone(milestoneId) {
    if (confirm('Mark this milestone as completed?')) {
        // TODO: Implement milestone completion
        alert('Milestone marked as completed!');
        location.reload();
    }
}

function shareGoal() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo htmlspecialchars($goal['name']); ?>',
            text: 'Check out my savings goal: <?php echo htmlspecialchars($goal['name']); ?>',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Goal link copied to clipboard!');
        });
    }
}

function deleteGoal() {
    if (confirm('Are you sure you want to delete this goal? This action cannot be undone.')) {
        // TODO: Implement goal deletion
        alert('Goal deletion functionality');
    }
}
</script>
