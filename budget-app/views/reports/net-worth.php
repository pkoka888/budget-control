<?php /** Net Worth Report */ ?>
<div class="max-w-7xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Čistá hodnota majetku</h1>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-4"><?php echo number_format($netWorth['total'] ?? 0, 0, ',', ' '); ?> Kč</h2>
        <canvas id="networth-chart"></canvas>
    </div>
</div>
