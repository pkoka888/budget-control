<?php
/**
 * Investment Portfolio View
 * Simplified portfolio overview with allocation
 */
$totalValue = $dashboard['total_value'] ?? 0;
$totalGainLoss = $dashboard['total_gain_loss'] ?? 0;
$totalGainLossPercent = $dashboard['total_gain_loss_percent'] ?? 0;
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-gray-900">Investiční portfolio</h1>
        <p class="mt-2 text-slate-gray-600">Přehled vašich investic a výkonnosti</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm font-medium text-slate-gray-600">Celková hodnota</p>
            <p class="mt-2 text-3xl font-bold text-slate-gray-900"><?php echo number_format($totalValue, 0, ',', ' '); ?> Kč</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm font-medium text-slate-gray-600">Zisk/Ztráta</p>
            <p class="mt-2 text-3xl font-bold <?php echo $totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo number_format($totalGainLoss, 0, ',', ' '); ?> Kč
            </p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-sm font-medium text-slate-gray-600">Výkonnost</p>
            <p class="mt-2 text-3xl font-bold <?php echo $totalGainLossPercent >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                <?php echo number_format($totalGainLossPercent, 2); ?>%
            </p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Asset Allocation</h2>
        <canvas id="allocation-chart" class="max-h-80"></canvas>
    </div>
</div>
