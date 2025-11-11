
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <a href="/goals/<?php echo $goal['id']; ?>" class="text-blue-600 hover:underline">← Back to <?php echo htmlspecialchars($goal['name']); ?></a>
                    <h1 class="text-2xl font-bold text-gray-800 mt-2">Milestones</h1>
                </div>
                <button onclick="createMilestone()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    + Add Milestone
                </button>
            </div>

            <!-- Goal Progress Summary -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($goal['name']); ?> Progress</h2>
                    <span class="text-sm text-gray-600"><?php echo number_format($goal['progress_percentage'], 1, ',', ' '); ?>% complete</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar bg-blue-600" style="width: <?php echo min($goal['progress_percentage'], 100); ?>%"></div>
                </div>
                <div class="flex justify-between text-sm text-gray-600 mt-2">
                    <span><?php echo number_format($goal['current_amount'], 0, ',', ' '); ?> Kč saved</span>
                    <span><?php echo number_format($goal['target_amount'], 0, ',', ' '); ?> Kč target</span>
                </div>
            </div>

            <!-- Milestones Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo count($milestones); ?></div>
                    <div class="text-sm text-gray-600">Total Milestones</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo count(array_filter($milestones, fn($m) => $m['completed'])); ?></div>
                    <div class="text-sm text-gray-600">Completed</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-orange-600"><?php echo count(array_filter($milestones, fn($m) => !$m['completed'])); ?></div>
                    <div class="text-sm text-gray-600">Remaining</div>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600">
                        <?php
                        $completedMilestones = array_filter($milestones, fn($m) => $m['completed']);
                        $completionRate = count($milestones) > 0 ? (count($completedMilestones) / count($milestones)) * 100 : 0;
                        echo number_format($completionRate, 0, ',', ' ');
                        ?>%
                    </div>
                    <div class="text-sm text-gray-600">Completion Rate</div>
                </div>
            </div>

            <!-- Milestones List -->
            <?php if (!empty($milestones)): ?>
                <div class="space-y-4">
                    <?php foreach ($milestones as $index => $milestone): ?>
                        <div class="milestone-item bg-white rounded-lg shadow p-6 <?php echo $milestone['completed'] ? 'milestone-completed border-l-4 border-green-500' : 'border-l-4 border-gray-300'; ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    <!-- Milestone Number/Status -->
                                    <div class="milestone-indicator flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold
                                        <?php echo $milestone['completed'] ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600'; ?>">
                                        <?php echo $milestone['completed'] ? '✓' : $milestone['order']; ?>
                                    </div>

                                    <!-- Milestone Details -->
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="text-lg font-semibold <?php echo $milestone['completed'] ? 'text-green-800' : 'text-gray-800'; ?>">
                                                <?php echo htmlspecialchars($milestone['name']); ?>
                                            </h3>
                                            <?php if ($milestone['completed']): ?>
                                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                                    Completed
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded-full">
                                                    Pending
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($milestone['description'] ?? ''); ?></p>

                                        <!-- Progress towards milestone -->
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                            <div class="text-center p-3 bg-blue-50 rounded">
                                                <div class="text-sm text-blue-600">Target Amount</div>
                                                <div class="font-bold text-blue-800"><?php echo number_format($milestone['target_amount'], 0, ',', ' '); ?> Kč</div>
                                            </div>
                                            <div class="text-center p-3 bg-green-50 rounded">
                                                <div class="text-sm text-green-600">Current Progress</div>
                                                <div class="font-bold text-green-800">
                                                    <?php
                                                    $progressTowardsMilestone = min($goal['current_amount'], $milestone['target_amount']);
                                                    echo number_format($progressTowardsMilestone, 0, ',', ' ');
                                                    ?> Kč
                                                </div>
                                            </div>
                                            <div class="text-center p-3 bg-purple-50 rounded">
                                                <div class="text-sm text-purple-600">Progress %</div>
                                                <div class="font-bold text-purple-800">
                                                    <?php
                                                    $milestoneProgress = $milestone['target_amount'] > 0 ? ($progressTowardsMilestone / $milestone['target_amount']) * 100 : 0;
                                                    echo number_format(min($milestoneProgress, 100), 1, ',', ' ');
                                                    ?>%
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Progress bar for this milestone -->
                                        <div class="mb-3">
                                            <div class="progress-bar">
                                                <div class="progress-bar <?php echo $milestone['completed'] ? 'bg-green-600' : 'bg-blue-600'; ?>"
                                                     style="width: <?php echo min($milestoneProgress, 100); ?>%"></div>
                                            </div>
                                        </div>

                                        <!-- Dates and metadata -->
                                        <div class="flex flex-wrap gap-4 text-sm text-gray-500">
                                            <span>Created: <?php echo date('M j, Y', strtotime($milestone['created_at'])); ?></span>
                                            <?php if ($milestone['completed']): ?>
                                                <span class="text-green-600">✓ Completed: <?php echo date('M j, Y', strtotime($milestone['completed_at'])); ?></span>
                                            <?php else: ?>
                                                <span>Due: <?php echo date('M j, Y', strtotime($milestone['due_date'] ?? $goal['target_date'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col space-y-2 ml-4">
                                    <?php if (!$milestone['completed']): ?>
                                        <button onclick="completeMilestone(<?php echo $milestone['id']; ?>)"
                                                class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700 transition-colors">
                                            ✓ Mark Complete
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="editMilestone(<?php echo $milestone['id']; ?>)"
                                            class="bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition-colors">
                                        ✏️ Edit
                                    </button>
                                    <button onclick="deleteMilestone(<?php echo $milestone['id']; ?>)"
                                            class="bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700 transition-colors">
                                        🗑️ Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="bg-white rounded-lg shadow p-12 text-center">
                    <div class="text-6xl mb-4">🎯</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">No Milestones Yet</h3>
                    <p class="text-gray-600 mb-6">Break down your savings goal into smaller, achievable milestones to stay motivated and track your progress.</p>
                    <button onclick="createMilestone()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                        Create Your First Milestone
                    </button>
                </div>
            <?php endif; ?>

            <!-- Add Milestone CTA -->
            <?php if (!empty($milestones)): ?>
                <div class="mt-8 text-center">
                    <button onclick="createMilestone()" class="bg-green-600 text-white px-8 py-4 rounded-lg hover:bg-green-700 transition-colors text-lg font-medium">
                        + Add Another Milestone
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function createMilestone() {
    alert('Create milestone functionality');
    // TODO: Implement create milestone modal/form
}

function editMilestone(milestoneId) {
    alert('Edit milestone ' + milestoneId);
    // TODO: Implement edit milestone modal/form
}

function completeMilestone(milestoneId) {
    if (confirm('Mark this milestone as completed?')) {
        // TODO: Implement milestone completion
        alert('Milestone marked as completed!');
        location.reload();
    }
}

function deleteMilestone(milestoneId) {
    if (confirm('Are you sure you want to delete this milestone? This action cannot be undone.')) {
        // TODO: Implement milestone deletion
        alert('Milestone deletion functionality');
    }
}
</script>
