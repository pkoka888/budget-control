<?php /** Goals List */ ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Všechny cíle</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($goals as $goal): ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($goal['name']); ?></h3>
            <div class="mt-4">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-primary-600 h-2 rounded-full" style="width: <?php echo min($goal['progress'], 100); ?>%"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
