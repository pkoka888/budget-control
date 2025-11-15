<?php /** Approval Details */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Žádost o schválení</h1>
        <a href="/household" class="text-blue-600 hover:text-blue-800">← Zpět na domácnost</a>
    </div>

    <!-- Approval Status -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <span class="px-3 py-1 rounded text-sm font-medium <?php
                    echo match($approval['status'] ?? 'pending') {
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        default => 'bg-yellow-100 text-yellow-800'
                    };
                ?>">
                    <?php
                        echo match($approval['status'] ?? 'pending') {
                            'approved' => 'Schváleno',
                            'rejected' => 'Zamítnuto',
                            default => 'Čeká na schválení'
                        };
                    ?>
                </span>
            </div>
            <div class="text-right text-sm text-gray-500">
                <p>Vytvořeno: <?php echo date('d.m.Y H:i', strtotime($approval['created_at'])); ?></p>
                <?php if (!empty($approval['reviewed_at'])): ?>
                    <p>Vyřízeno: <?php echo date('d.m.Y H:i', strtotime($approval['reviewed_at'])); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Request Details -->
        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Typ žádosti</h3>
                <p class="text-lg font-medium">
                    <?php
                        echo match($approval['approval_type'] ?? '') {
                            'transaction' => 'Transakce',
                            'budget' => 'Rozpočet',
                            'account' => 'Účet',
                            'goal' => 'Cíl',
                            default => 'Ostatní'
                        };
                    ?>
                </p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Popis</h3>
                <p class="text-lg"><?php echo htmlspecialchars($approval['description'] ?? ''); ?></p>
            </div>

            <?php if (!empty($approval['amount'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Částka</h3>
                    <p class="text-2xl font-bold"><?php echo number_format($approval['amount'], 2); ?> Kč</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($approval['notes'])): ?>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Poznámka žadatele</h3>
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($approval['notes'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Requester Information -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Informace o žadateli</h2>
        <div class="flex items-center">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                <span class="text-blue-600 font-semibold text-lg">
                    <?php echo strtoupper(substr($approval['requested_by_name'] ?? 'U', 0, 1)); ?>
                </span>
            </div>
            <div>
                <p class="font-medium"><?php echo htmlspecialchars($approval['requested_by_name'] ?? ''); ?></p>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($approval['requested_by_email'] ?? ''); ?></p>
            </div>
        </div>
    </div>

    <!-- Related Item Details -->
    <?php if (!empty($related_item)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Detail položky</h2>

            <?php if ($approval['approval_type'] === 'transaction'): ?>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Datum:</span>
                        <span class="font-medium"><?php echo date('d.m.Y', strtotime($related_item['date'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Kategorie:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($related_item['category_name'] ?? ''); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Účet:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($related_item['account_name'] ?? ''); ?></span>
                    </div>
                    <?php if (!empty($related_item['merchant_name'])): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Obchodník:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($related_item['merchant_name']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Approval Actions -->
    <?php if ($approval['status'] === 'pending' && ($is_admin ?? false)): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Akce</h2>

            <form method="POST" action="/household/approvals/<?php echo $approval['id']; ?>/review">
                <?php echo \BudgetApp\Middleware\CsrfProtection::field(); ?>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Poznámka k rozhodnutí (volitelné)</label>
                    <textarea name="review_notes" rows="3" class="w-full border rounded px-3 py-2" placeholder="Důvod schválení nebo zamítnutí..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="submit" name="action" value="approve" class="flex-1 bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded font-medium">
                        Schválit
                    </button>
                    <button type="submit" name="action" value="reject" class="flex-1 bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded font-medium">
                        Zamítnout
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Review Information -->
    <?php if ($approval['status'] !== 'pending'): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Informace o vyřízení</h2>

            <div class="space-y-3">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Vyřídil</h3>
                    <p class="font-medium"><?php echo htmlspecialchars($approval['reviewed_by_name'] ?? ''); ?></p>
                </div>

                <?php if (!empty($approval['review_notes'])): ?>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-1">Poznámka</h3>
                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($approval['review_notes'])); ?></p>
                    </div>
                <?php endif; ?>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Datum vyřízení</h3>
                    <p class="font-medium"><?php echo date('d.m.Y H:i', strtotime($approval['reviewed_at'])); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Activity Log -->
    <?php if (!empty($activity_log)): ?>
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-lg font-semibold mb-4">Historie aktivit</h2>

            <div class="space-y-3">
                <?php foreach ($activity_log as $activity): ?>
                    <div class="flex items-start border-l-2 border-blue-500 pl-4 py-2">
                        <div class="flex-1">
                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['description'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500">
                                <?php echo htmlspecialchars($activity['user_name'] ?? ''); ?> •
                                <?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
