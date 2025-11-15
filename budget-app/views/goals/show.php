<?php /** Goal Details */ ?>
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($goal['name'] ?? ''); ?></h1>
        <a href="/goals" class="text-blue-600 hover:text-blue-800">← Zpět na cíle</a>
    </div>

    <!-- Goal Progress -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Cílová částka</h3>
                <p class="text-2xl font-bold"><?php echo number_format($goal['target_amount'] ?? 0, 2); ?> Kč</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Aktuální stav</h3>
                <p class="text-2xl font-bold text-green-600"><?php echo number_format($goal['current_amount'] ?? 0, 2); ?> Kč</p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Zbývá</h3>
                <p class="text-2xl font-bold text-orange-600">
                    <?php echo number_format(max(0, ($goal['target_amount'] ?? 0) - ($goal['current_amount'] ?? 0)), 2); ?> Kč
                </p>
            </div>
        </div>

        <!-- Progress Bar -->
        <?php
            $percentage = ($goal['target_amount'] ?? 0) > 0
                ? min(($goal['current_amount'] ?? 0) / $goal['target_amount'] * 100, 100)
                : 0;
        ?>
        <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Progres</span>
                <span><?php echo number_format($percentage, 1); ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                <div class="bg-green-500 h-4 rounded-full transition-all" style="width: <?php echo $percentage; ?>%"></div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Datum zahájení</h3>
                <p class="text-lg"><?php echo isset($goal['start_date']) ? date('d.m.Y', strtotime($goal['start_date'])) : '—'; ?></p>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-1">Cílové datum</h3>
                <p class="text-lg"><?php echo isset($goal['target_date']) ? date('d.m.Y', strtotime($goal['target_date'])) : '—'; ?></p>
            </div>
        </div>

        <?php if (!empty($goal['description'])): ?>
        <div class="mt-6 pt-6 border-t">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Popis</h3>
            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($goal['description'])); ?></p>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="mt-6 pt-6 border-t flex gap-3">
            <a href="/goals/<?php echo $goal['id']; ?>/edit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
                Upravit cíl
            </a>
            <a href="/goals/<?php echo $goal['id']; ?>/contribute" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded">
                Přidat příspěvek
            </a>
            <button onclick="deleteGoal()" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded">
                Smazat
            </button>
        </div>
    </div>

    <!-- Contribution History -->
    <?php if (!empty($contributions)): ?>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Historie příspěvků</h2>
            <div class="space-y-3">
                <?php foreach ($contributions as $contribution): ?>
                    <div class="flex justify-between items-center border-b pb-3">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($contribution['description'] ?? 'Příspěvek'); ?></p>
                            <p class="text-sm text-gray-500"><?php echo date('d.m.Y', strtotime($contribution['date'])); ?></p>
                        </div>
                        <p class="font-semibold text-green-600">
                            +<?php echo number_format($contribution['amount'], 2); ?> Kč
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Projected Completion -->
    <?php if (!empty($projection)): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
            <h3 class="font-semibold text-blue-900 mb-2">Projekce dokončení</h3>
            <p class="text-sm text-blue-800">
                Při průměrném měsíčním příspěvku <?php echo number_format($projection['avg_monthly'] ?? 0, 2); ?> Kč
                dosáhnete cíle <?php echo $projection['projected_date'] ?? '—'; ?>.
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteGoal() {
    if (confirm('Opravdu chcete smazat tento cíl?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/goals/<?php echo $goal['id']; ?>/delete';

        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrf);

        document.body.appendChild(form);
        form.submit();
    }
}
</script>
