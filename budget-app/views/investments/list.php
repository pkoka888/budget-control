
<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-5xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Investiční portfolio</h1>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Investováno</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($totalInvested, 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Aktuální hodnota</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($totalCurrent, 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Zisk/Ztráta</p>
                    <p class="text-2xl font-bold <?php echo ($totalCurrent - $totalInvested) > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo number_format($totalCurrent - $totalInvested, 0, ',', ' '); ?> Kč
                    </p>
                </div>
            </div>

            <!-- Investments Table -->
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Symbol</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Typ</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Množství</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Cena</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Aktuální cena</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Zisk/Ztráta</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-700">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($investments as $inv): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($inv['symbol']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars(ucfirst($inv['type'])); ?></td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900"><?php echo number_format($inv['quantity'], 2, ',', ' '); ?></td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900"><?php echo number_format($inv['purchase_price'], 2, ',', ' '); ?> Kč</td>
                                <td class="px-6 py-4 text-sm text-right text-gray-900"><?php echo number_format($inv['current_price'], 2, ',', ' '); ?> Kč</td>
                                <td class="px-6 py-4 text-sm text-right font-semibold <?php echo $inv['gain'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $inv['gain'] > 0 ? '+' : ''; ?><?php echo number_format($inv['gain'], 0, ',', ' '); ?> Kč
                                </td>
                                <td class="px-6 py-4 text-sm text-center font-semibold <?php echo $inv['gain_percentage'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $inv['gain_percentage'] > 0 ? '+' : ''; ?><?php echo number_format($inv['gain_percentage'], 1, ',', ' '); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
