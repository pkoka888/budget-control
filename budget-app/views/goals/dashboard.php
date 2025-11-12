<?php
/**
 * Goals Dashboard
 * Overview of all financial goals with progress tracking
 */

$totalGoals = $dashboard['total_goals'] ?? 0;
$activeGoals = $dashboard['active_goals'] ?? 0;
$completedGoals = $dashboard['completed_goals'] ?? 0;
$totalTargetAmount = $dashboard['total_target_amount'] ?? 0;
$totalCurrentAmount = $dashboard['total_current_amount'] ?? 0;
$overallProgress = $totalTargetAmount > 0 ? ($totalCurrentAmount / $totalTargetAmount) * 100 : 0;
$goals = $dashboard['goals'] ?? [];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-gray-900">Finanční cíle</h1>
                <p class="mt-2 text-slate-gray-600">Sledujte a dosahujte svých finančních cílů</p>
            </div>
            <button id="create-goal-btn" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nový cíl
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-primary-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-slate-gray-600">Celkem cílů</p>
            <p class="mt-2 text-3xl font-bold text-slate-gray-900"><?php echo $totalGoals; ?></p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-slate-gray-600">Aktivní</p>
            <p class="mt-2 text-3xl font-bold text-blue-600"><?php echo $activeGoals; ?></p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-slate-gray-600">Dokončeno</p>
            <p class="mt-2 text-3xl font-bold text-green-600"><?php echo $completedGoals; ?></p>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-medium text-slate-gray-600">Celkový pokrok</p>
            <p class="mt-2 text-3xl font-bold text-purple-600"><?php echo number_format($overallProgress, 1); ?>%</p>
        </div>
    </div>

    <!-- Overall Progress -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-gray-900">Celkový pokrok</h3>
            <span class="text-sm text-slate-gray-600">
                <?php echo number_format($totalCurrentAmount, 0, ',', ' '); ?> Kč / <?php echo number_format($totalTargetAmount, 0, ',', ' '); ?> Kč
            </span>
        </div>
        <div class="w-full bg-slate-gray-200 rounded-full h-4">
            <div class="bg-gradient-to-r from-primary-500 to-primary-600 h-4 rounded-full transition-all duration-500"
                 style="width: <?php echo min($overallProgress, 100); ?>%"></div>
        </div>
    </div>

    <!-- Goals Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="goals-container">
        <?php foreach ($goals as $goal):
            $progress = isset($goal['progress']) ? $goal['progress'] : 0;
            $progressColor = $progress >= 100 ? 'bg-green-600' : ($progress >= 75 ? 'bg-blue-600' : ($progress >= 50 ? 'bg-yellow-600' : 'bg-red-600'));
            $daysRemaining = isset($goal['target_date']) ? floor((strtotime($goal['target_date']) - time()) / 86400) : null;
        ?>
        <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden" data-goal-id="<?php echo $goal['id']; ?>">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-slate-gray-900 mb-1"><?php echo htmlspecialchars($goal['name']); ?></h3>
                        <p class="text-sm text-slate-gray-600"><?php echo htmlspecialchars($goal['description']); ?></p>
                    </div>
                    <span class="badge badge-<?php echo $goal['priority'] === 'high' ? 'danger' : ($goal['priority'] === 'medium' ? 'warning' : 'secondary'); ?>">
                        <?php echo ucfirst($goal['priority']); ?>
                    </span>
                </div>

                <!-- Progress -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-slate-gray-600">Pokrok</span>
                        <span class="text-sm font-bold text-slate-gray-900"><?php echo number_format($progress, 1); ?>%</span>
                    </div>
                    <div class="w-full bg-slate-gray-200 rounded-full h-2.5">
                        <div class="<?php echo $progressColor; ?> h-2.5 rounded-full transition-all duration-500"
                             style="width: <?php echo min($progress, 100); ?>%"></div>
                    </div>
                </div>

                <!-- Amount -->
                <div class="flex items-center justify-between mb-4 text-sm">
                    <span class="text-slate-gray-600">Aktuálně:</span>
                    <span class="font-semibold text-slate-gray-900"><?php echo number_format($goal['current_amount'], 0, ',', ' '); ?> Kč</span>
                </div>
                <div class="flex items-center justify-between mb-4 text-sm">
                    <span class="text-slate-gray-600">Cíl:</span>
                    <span class="font-semibold text-slate-gray-900"><?php echo number_format($goal['target_amount'], 0, ',', ' '); ?> Kč</span>
                </div>

                <?php if ($daysRemaining !== null): ?>
                <div class="flex items-center mb-4 text-sm">
                    <svg class="w-4 h-4 mr-2 text-slate-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-slate-gray-600">
                        <?php if ($daysRemaining > 0): ?>
                            Zbývá <?php echo $daysRemaining; ?> dní
                        <?php elseif ($daysRemaining === 0): ?>
                            Dnes je termín!
                        <?php else: ?>
                            Termín uplynul
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="flex gap-2">
                    <a href="/goals/<?php echo $goal['id']; ?>" class="btn btn-secondary btn-sm flex-1">
                        Detail
                    </a>
                    <button class="btn btn-primary btn-sm flex-1 edit-goal-btn" data-goal-id="<?php echo $goal['id']; ?>">
                        Upravit
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($goals)): ?>
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-slate-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-slate-gray-900">Žádné cíle</h3>
        <p class="mt-1 text-sm text-slate-gray-500">Začněte vytvořením svého prvního finančního cíle</p>
        <div class="mt-6">
            <button class="btn btn-primary" onclick="document.getElementById('create-goal-btn').click()">
                Vytvořit cíl
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Create/Edit Goal Modal -->
<div id="goal-modal" class="modal hidden" aria-hidden="true" role="dialog">
    <div class="modal-overlay"></div>
    <div class="modal-container max-w-2xl">
        <div class="modal-header">
            <h2 id="goal-modal-title" class="text-xl font-semibold">Nový cíl</h2>
            <button class="modal-close" aria-label="Zavřít">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form id="goal-form" class="modal-body space-y-4">
            <input type="hidden" id="goal-id" name="id">

            <div>
                <label for="goal-name" class="form-label form-label-required">Název cíle</label>
                <input type="text" id="goal-name" name="name" class="form-input" required placeholder="např. Dovolená v Itálii">
            </div>

            <div>
                <label for="goal-description" class="form-label">Popis</label>
                <textarea id="goal-description" name="description" class="form-input" rows="3" placeholder="Podrobný popis cíle..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="goal-type" class="form-label form-label-required">Typ cíle</label>
                    <select id="goal-type" name="goal_type" class="form-input" required>
                        <option value="savings">Úspory</option>
                        <option value="debt_payment">Splácení dluhu</option>
                        <option value="investment">Investice</option>
                        <option value="purchase">Nákup</option>
                        <option value="emergency_fund">Nouzový fond</option>
                    </select>
                </div>

                <div>
                    <label for="goal-priority" class="form-label form-label-required">Priorita</label>
                    <select id="goal-priority" name="priority" class="form-input" required>
                        <option value="low">Nízká</option>
                        <option value="medium" selected>Střední</option>
                        <option value="high">Vysoká</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="goal-target-amount" class="form-label form-label-required">Cílová částka (Kč)</label>
                    <input type="number" id="goal-target-amount" name="target_amount" class="form-input" required min="0" step="100">
                </div>

                <div>
                    <label for="goal-current-amount" class="form-label">Aktuální částka (Kč)</label>
                    <input type="number" id="goal-current-amount" name="current_amount" class="form-input" min="0" step="100" value="0">
                </div>
            </div>

            <div>
                <label for="goal-target-date" class="form-label">Datum dokončení</label>
                <input type="date" id="goal-target-date" name="target_date" class="form-input">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary modal-close">Zrušit</button>
                <button type="submit" class="btn btn-primary">
                    <span id="save-goal-text">Uložit cíl</span>
                    <span id="save-goal-loading" class="hidden">Ukládám...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/js/goals.js"></script>
