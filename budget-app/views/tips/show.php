<?php /** Individual Tip Details */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <a href="/dashboard" class="text-blue-600 hover:text-blue-800">← Zpět na dashboard</a>
        <div class="flex gap-2">
            <?php if (!empty($tip['is_helpful'])): ?>
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded text-sm">Označeno jako užitečné</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-8">
        <!-- Tip Header -->
        <div class="mb-6">
            <div class="flex items-center text-sm text-gray-500 mb-2">
                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded">
                    <?php
                        $categories = [
                            'budgeting' => 'Rozpočtování',
                            'saving' => 'Spoření',
                            'investing' => 'Investování',
                            'debt' => 'Dluhy',
                            'general' => 'Obecné'
                        ];
                        echo $categories[$tip['category']] ?? 'Tip';
                    ?>
                </span>
                <?php if (!empty($tip['priority']) && $tip['priority'] === 'high'): ?>
                    <span class="ml-2 px-2 py-1 bg-red-100 text-red-800 rounded">Důležité</span>
                <?php endif; ?>
            </div>

            <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($tip['title'] ?? ''); ?></h1>

            <?php if (!empty($tip['subtitle'])): ?>
                <p class="text-xl text-gray-600"><?php echo htmlspecialchars($tip['subtitle']); ?></p>
            <?php endif; ?>
        </div>

        <!-- Tip Content -->
        <div class="prose max-w-none mb-8">
            <?php if (!empty($tip['content'])): ?>
                <?php echo nl2br(htmlspecialchars($tip['content'])); ?>
            <?php endif; ?>

            <?php if (!empty($tip['description'])): ?>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($tip['description'])); ?></p>
            <?php endif; ?>
        </div>

        <!-- Action Steps -->
        <?php if (!empty($tip['action_steps'])): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-blue-900 mb-3">Akční kroky</h3>
                <ul class="space-y-2">
                    <?php foreach (json_decode($tip['action_steps'], true) ?? [] as $step): ?>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-blue-900"><?php echo htmlspecialchars($step); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Potential Savings -->
        <?php if (!empty($tip['potential_savings'])): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-green-900 mb-2">Potenciální úspora</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo number_format($tip['potential_savings'], 2); ?> Kč / měsíc</p>
                <p class="text-sm text-green-800 mt-1">Za rok to může být až <?php echo number_format($tip['potential_savings'] * 12, 2); ?> Kč!</p>
            </div>
        <?php endif; ?>

        <!-- Related Links -->
        <?php if (!empty($tip['related_links'])): ?>
            <div class="mb-6">
                <h3 class="font-semibold mb-3">Další zdroje</h3>
                <ul class="space-y-2">
                    <?php foreach (json_decode($tip['related_links'], true) ?? [] as $link): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($link['url'] ?? '#'); ?>" class="text-blue-600 hover:text-blue-800" target="_blank" rel="noopener">
                                <?php echo htmlspecialchars($link['title'] ?? $link['url']); ?> →
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Feedback -->
        <div class="pt-6 border-t">
            <p class="text-gray-700 mb-3">Byl tento tip užitečný?</p>
            <div class="flex gap-3">
                <form method="POST" action="/tips/<?php echo $tip['id']; ?>/feedback" class="inline">
                    <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
                    <input type="hidden" name="helpful" value="1">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 10.5a1.5 1.5 0 113 0v6a1.5 1.5 0 01-3 0v-6zM6 10.333v5.43a2 2 0 001.106 1.79l.05.025A4 4 0 008.943 18h5.416a2 2 0 001.962-1.608l1.2-6A2 2 0 0015.56 8H12V4a2 2 0 00-2-2 1 1 0 00-1 1v.667a4 4 0 01-.8 2.4L6.8 7.933a4 4 0 00-.8 2.4z"></path>
                        </svg>
                        Ano, užitečné
                    </button>
                </form>

                <form method="POST" action="/tips/<?php echo $tip['id']; ?>/feedback" class="inline">
                    <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
                    <input type="hidden" name="helpful" value="0">
                    <button type="submit" class="bg-gray-200 hover:bg-gray-300 px-6 py-2 rounded flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M18 9.5a1.5 1.5 0 11-3 0v-6a1.5 1.5 0 013 0v6zM14 9.667v-5.43a2 2 0 00-1.105-1.79l-.05-.025A4 4 0 0011.055 2H5.64a2 2 0 00-1.962 1.608l-1.2 6A2 2 0 004.44 12H8v4a2 2 0 002 2 1 1 0 001-1v-.667a4 4 0 01.8-2.4l1.4-1.866a4 4 0 00.8-2.4z"></path>
                        </svg>
                        Ne, neužitečné
                    </button>
                </form>

                <form method="POST" action="/tips/<?php echo $tip['id']; ?>/dismiss" class="inline ml-auto">
                    <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>
                    <button type="submit" class="text-gray-600 hover:text-gray-800 px-4 py-2">
                        Skrýt tento tip
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Related Tips -->
    <?php if (!empty($related_tips)): ?>
        <div class="mt-8">
            <h2 class="text-xl font-semibold mb-4">Související tipy</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($related_tips as $related): ?>
                    <a href="/tips/<?php echo $related['id']; ?>" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
                        <h3 class="font-semibold mb-2"><?php echo htmlspecialchars($related['title'] ?? ''); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars(mb_substr($related['description'] ?? '', 0, 100)); ?>...</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
