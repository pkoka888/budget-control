
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Finanční cíle</h1>

            <div class="space-y-4">
                <?php foreach ($goals as $goal): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($goal['name']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($goal['description'] ?? ''); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Cíl: <strong><?php echo number_format($goal['target_amount'], 0, ',', ' '); ?> Kč</strong></p>
                                <p class="text-sm text-gray-600">Zbývá: <strong><?php echo $goal['days_left']; ?> dní</strong></p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">Pokrok</span>
                                <span class="text-sm font-medium text-gray-700"><?php echo number_format($goal['progress_percentage'], 1, ',', ' '); ?>%</span>
                            </div>
                            <div class="bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min($goal['progress_percentage'], 100); ?>%"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600">Máte</p>
                                <p class="font-semibold text-gray-800"><?php echo number_format($goal['current_amount'], 0, ',', ' '); ?> Kč</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Musíte spořit měsíčně</p>
                                <p class="font-semibold text-gray-800"><?php echo number_format($goal['monthly_savings_needed'], 0, ',', ' '); ?> Kč</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($goals)): ?>
                <div class="text-center py-12 bg-white rounded-lg shadow">
                    <p class="text-gray-600 mb-4">Zatím nemáte žádné finanční cíle.</p>
                    <button onclick="addGoal()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Přidat cíl
                    </button>
                </div>
            <?php else: ?>
                <div class="mt-6">
                    <button onclick="addGoal()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Přidat cíl
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function addGoal() {
    alert('Add goal modal');
}
</script>
