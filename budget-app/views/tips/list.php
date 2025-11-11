<?php
// Tips & Guides List View
$title = 'Tipy & Průvodce';
?>

<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">Finanční tipy a průvodce</h1>
        <p class="text-lg text-gray-600">Zlepšete své finanční zvyky s našimi praktickými radami a průvodci.</p>
    </div>

    <!-- Tips by Category -->
    <?php foreach ($grouped as $category => $categoryTips): ?>
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b-2 border-blue-500">
                <?php echo htmlspecialchars($category); ?>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($categoryTips as $tip): ?>
                    <a href="/tips/<?php echo $tip['id']; ?>" class="bg-white rounded-lg shadow hover:shadow-lg transition p-6 h-full flex flex-col">
                        <h3 class="text-lg font-bold text-gray-800 mb-2 line-clamp-2">
                            <?php echo htmlspecialchars($tip['title']); ?>
                        </h3>

                        <p class="text-gray-600 text-sm mb-4 flex-grow line-clamp-3">
                            <?php echo htmlspecialchars(strip_tags(substr($tip['content'], 0, 150))); ?>...
                        </p>

                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                            <span class="text-xs text-gray-500">
                                <?php echo date('d.m.Y', strtotime($tip['created_at'])); ?>
                            </span>
                            <span class="text-blue-600 font-medium text-sm">Přečíst více →</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- No tips message -->
    <?php if (empty($tips)): ?>
        <div class="text-center py-12">
            <p class="text-gray-500 text-lg">Zatím nejsou dostupné žádné tipy. Vraťte se později!</p>
        </div>
    <?php endif; ?>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
