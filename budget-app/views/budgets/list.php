<?php /** Budgets List */ ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Rozpočty</h1>
        <a href="/budgets/create" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
            + Nový rozpočet
        </a>
    </div>

    <div class="space-y-6">
        <?php if (!empty($budgets)): ?>
            <?php foreach ($budgets as $budget): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($budget['category_name'] ?? 'Kategorie'); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($budget['period'] ?? 'monthly'); ?> rozpočet</p>
                        </div>
                        <p class="text-sm font-medium">
                            <?php echo number_format($budget['spent'] ?? 0, 2); ?> / <?php echo number_format($budget['amount'], 2); ?> Kč
                        </p>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                        <?php
                            $percentage = $budget['amount'] > 0 ? min(($budget['spent'] ?? 0) / $budget['amount'] * 100, 100) : 0;
                            $color = $percentage > 90 ? 'bg-red-500' : ($percentage > 75 ? 'bg-yellow-500' : 'bg-green-500');
                        ?>
                        <div class="<?php echo $color; ?> h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <p class="text-sm text-gray-600"><?php echo round($percentage); ?>% využito</p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500 mb-4">Zatím nemáte žádné rozpočty</p>
                <a href="/budgets/create" class="text-blue-600 hover:text-blue-800">Vytvořit první rozpočet</a>
            </div>
        <?php endif; ?>
    </div>
</div>
