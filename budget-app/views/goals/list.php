<?php /** Goals List */ ?>
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold mb-2">Finanční cíle</h1>
            <p class="text-gray-600">Sledujte pokrok svých finančních cílů</p>
        </div>
        <a href="/goals/create" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
            + Nový cíl
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Aktivních cílů</h3>
            <p class="text-3xl font-bold mt-2"><?php echo $summary['active_count'] ?? 0; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Celková cílová částka</h3>
            <p class="text-3xl font-bold mt-2"><?php echo number_format($summary['total_target'] ?? 0, 2); ?> Kč</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Celkem naspořeno</h3>
            <p class="text-3xl font-bold mt-2 text-green-600"><?php echo number_format($summary['total_saved'] ?? 0, 2); ?> Kč</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-gray-500 text-sm">Průměrný pokrok</h3>
            <p class="text-3xl font-bold mt-2"><?php echo number_format($summary['avg_progress'] ?? 0, 1); ?>%</p>
        </div>
    </div>

    <!-- Active Goals -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 bg-gray-50 border-b">
            <h2 class="text-lg font-semibold">Aktivní cíle</h2>
        </div>

        <?php if (!empty($active_goals)): ?>
            <div class="p-6 space-y-6">
                <?php foreach ($active_goals as $goal): ?>
                    <?php
                        $percentage = ($goal['target_amount'] ?? 0) > 0
                            ? min(($goal['current_amount'] ?? 0) / $goal['target_amount'] * 100, 100)
                            : 0;
                        $remaining = max(0, ($goal['target_amount'] ?? 0) - ($goal['current_amount'] ?? 0));
                    ?>
                    <div class="border-b pb-6 last:border-b-0">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-lg font-semibold">
                                    <a href="/goals/<?php echo $goal['id']; ?>" class="hover:text-blue-600">
                                        <?php echo htmlspecialchars($goal['name'] ?? ''); ?>
                                    </a>
                                </h3>
                                <?php if (!empty($goal['description'])): ?>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($goal['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-green-600"><?php echo number_format($goal['current_amount'] ?? 0, 2); ?> Kč</p>
                                <p class="text-sm text-gray-500">z <?php echo number_format($goal['target_amount'] ?? 0, 2); ?> Kč</p>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span><?php echo number_format($percentage, 1); ?>% splněno</span>
                                <span>Zbývá: <?php echo number_format($remaining, 2); ?> Kč</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="<?php echo $percentage >= 100 ? 'bg-green-500' : 'bg-blue-500'; ?> h-3 rounded-full transition-all" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>

                        <!-- Goal Details -->
                        <div class="flex justify-between items-center text-sm text-gray-600">
                            <div class="flex gap-6">
                                <?php if (!empty($goal['target_date'])): ?>
                                    <span>Cíl: <?php echo date('d.m.Y', strtotime($goal['target_date'])); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($goal['days_remaining'])): ?>
                                    <span><?php echo $goal['days_remaining']; ?> dní zbývá</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex gap-2">
                                <a href="/goals/<?php echo $goal['id']; ?>/contribute" class="text-blue-600 hover:text-blue-800">
                                    Přidat příspěvek
                                </a>
                                <a href="/goals/<?php echo $goal['id']; ?>" class="text-gray-600 hover:text-gray-800">
                                    Detail
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($goals)): ?>
            <!-- Fallback for simple $goals array -->
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($goals as $goal): ?>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4"><?php echo htmlspecialchars($goal['name'] ?? ''); ?></h3>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min($goal['progress'] ?? 0, 100); ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Žádné aktivní cíle</h3>
                <p class="text-gray-600 mb-4">Vytvořte si první finanční cíl a začněte spořit</p>
                <a href="/goals/create" class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                    Vytvořit první cíl
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completed Goals -->
    <?php if (!empty($completed_goals)): ?>
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-lg font-semibold">Splněné cíle</h2>
            </div>

            <div class="divide-y divide-gray-200">
                <?php foreach ($completed_goals as $goal): ?>
                    <div class="px-6 py-4 hover:bg-gray-50">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <h3 class="font-medium"><?php echo htmlspecialchars($goal['name'] ?? ''); ?></h3>
                                    <p class="text-sm text-gray-500">
                                        Splněno: <?php echo isset($goal['completed_at']) ? date('d.m.Y', strtotime($goal['completed_at'])) : '—'; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-green-600"><?php echo number_format($goal['target_amount'] ?? 0, 2); ?> Kč</p>
                                <a href="/goals/<?php echo $goal['id']; ?>" class="text-sm text-blue-600 hover:text-blue-800">Detail</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
