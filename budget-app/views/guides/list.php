
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Průvodce a Tipy</h1>
            <p class="text-gray-600 mb-8">Naučte se spravovat vaše osobní finance lépe</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($guides as $guide): ?>
                    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($guide['title']); ?></h3>
                        <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars(substr($guide['content'], 0, 150) . '...'); ?></p>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500"><?php echo date('d.m.Y', strtotime($guide['created_at'])); ?></span>
                            <a href="/tips/<?php echo $guide['id']; ?>" class="text-blue-600 hover:underline font-semibold">Přečíst</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
