
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Čistá hodnota (Net Worth)</h1>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Celkova aktiva</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($netWorth['total_assets'], 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Celkove pasíva</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo number_format($netWorth['total_liabilities'], 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Čistá hodnota</p>
                    <p class="text-2xl font-bold <?php echo $netWorth['net_worth'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo number_format($netWorth['net_worth'], 0, ',', ' '); ?> Kč
                    </p>
                </div>
            </div>

            <!-- Asset Composition -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-bold mb-4">Aktiva podle typu</h2>
                    <div class="space-y-2">
                        <?php foreach ($netWorth['assets_by_type'] as $type => $amount): ?>
                            <div class="flex justify-between">
                                <span><?php echo htmlspecialchars(ucfirst($type)); ?></span>
                                <strong><?php echo number_format($amount, 0, ',', ' '); ?> Kč</strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-bold mb-4">Pasíva podle typu</h2>
                    <div class="space-y-2">
                        <?php foreach ($netWorth['liabilities_by_type'] as $type => $amount): ?>
                            <div class="flex justify-between">
                                <span><?php echo htmlspecialchars(ucfirst($type)); ?></span>
                                <strong><?php echo number_format($amount, 0, ',', ' '); ?> Kč</strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
