<?php
// Check if we have the new yearly report data or fall back to old format
if (isset($yearlyReport)) {
    $period = $yearlyReport['period'];
    $yearlyData = $yearlyReport['yearly_data'];
    $categoryTrends = $yearlyReport['category_trends'];
    $totals = $yearlyReport['totals'];
    $isNewFormat = true;
} else {
    // Fallback to old format for backward compatibility
    $yearlyData = [];
    $categoryTrends = [];
    $totals = $yearlyTotals;
    $period = ['start_year' => $year, 'end_year' => $year];
    $isNewFormat = false;
}
?>


<div class="flex-1 flex flex-col overflow-hidden">
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Roční zpráva</h1>
                <div class="flex gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Od roku:</label>
                        <input type="number" id="startYear" value="<?php echo htmlspecialchars($period['start_year']); ?>" min="2000" class="px-3 py-2 border border-gray-300 rounded-lg w-20">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Do roku:</label>
                        <input type="number" id="endYear" value="<?php echo htmlspecialchars($period['end_year']); ?>" min="2000" class="px-3 py-2 border border-gray-300 rounded-lg w-20">
                    </div>
                    <button onclick="updateYearRange()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Aktualizovat</button>
                </div>
            </div>

            <!-- Overall Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Celkové příjmy</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($totals['total_income'], 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Celkové výdaje</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo number_format($totals['total_expenses'], 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Čistý příjem</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo number_format($totals['total_net_income'], 0, ',', ' '); ?> Kč</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-gray-600 text-sm">Průměrná úspora</p>
                    <p class="text-2xl font-bold text-purple-600"><?php echo number_format($totals['average_savings_rate'], 1, ',', ' '); ?>%</p>
                </div>
            </div>

            <?php if ($isNewFormat && !empty($yearlyData)): ?>
            <!-- Yearly Trends -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">Roční trendy</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Rok</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Příjmy</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Růst příjmů</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Výdaje</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Růst výdajů</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Čistý příjem</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-700">Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($yearlyData as $data): ?>
                                <tr class="border-b">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($data['year']); ?></td>
                                    <td class="px-4 py-3 text-sm text-right text-green-600 font-semibold"><?php echo number_format($data['total_income'], 0, ',', ' '); ?> Kč</td>
                                    <td class="px-4 py-3 text-sm text-right <?php echo $data['income_growth_rate'] > 0 ? 'text-green-600' : ($data['income_growth_rate'] < 0 ? 'text-red-600' : 'text-gray-500'); ?>">
                                        <?php if ($data['income_growth_rate'] != 0): ?>
                                            <?php echo ($data['income_growth_rate'] > 0 ? '+' : '') . number_format($data['income_growth_rate'], 1, ',', ' '); ?>%
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-red-600 font-semibold"><?php echo number_format($data['total_expenses'], 0, ',', ' '); ?> Kč</td>
                                    <td class="px-4 py-3 text-sm text-right <?php echo $data['expense_growth_rate'] > 0 ? 'text-red-600' : ($data['expense_growth_rate'] < 0 ? 'text-green-600' : 'text-gray-500'); ?>">
                                        <?php if ($data['expense_growth_rate'] != 0): ?>
                                            <?php echo ($data['expense_growth_rate'] > 0 ? '+' : '') . number_format($data['expense_growth_rate'], 1, ',', ' '); ?>%
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right font-semibold <?php echo $data['net_income'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo number_format($data['net_income'], 0, ',', ' '); ?> Kč
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php
                                            echo $data['net_income_trend'] === 'increasing' ? 'bg-green-100 text-green-800' :
                                                 ($data['net_income_trend'] === 'decreasing' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800');
                                        ?>">
                                            <?php
                                            echo $data['net_income_trend'] === 'increasing' ? 'Rostoucí' :
                                                 ($data['net_income_trend'] === 'decreasing' ? 'Klesající' : 'Stabilní');
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Category Trends -->
            <?php if (!empty($categoryTrends)): ?>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-bold mb-4">Kategorii trendy</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($categoryTrends as $year => $categories): ?>
                        <div class="border rounded-lg p-4">
                            <h3 class="font-semibold text-gray-800 mb-3"><?php echo htmlspecialchars($year); ?></h3>
                            <div class="space-y-2">
                                <?php
                                // Sort categories by expenses descending and take top 5
                                usort($categories, fn($a, $b) => $b['expenses'] <=> $a['expenses']);
                                $topCategories = array_slice($categories, 0, 5);
                                foreach ($topCategories as $category):
                                ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($category['category_name']); ?></span>
                                        <span class="text-sm font-medium text-red-600"><?php echo number_format($category['expenses'], 0, ',', ' '); ?> Kč</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Monthly Breakdown (for backward compatibility) -->
            <?php if (!$isNewFormat && isset($monthlyData)): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-bold mb-4">Měsíční přehled</h2>
                <table class="w-full">
                    <thead class="border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Měsíc</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Příjmy</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Výdaje</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-700">Čistý</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $months = ['ledna', 'února', 'března', 'dubna', 'května', 'június',
                                  'července', 'srpna', 'září', 'října', 'listopadu', 'prosince'];
                        foreach ($monthlyData as $m => $data):
                        ?>
                            <tr class="border-b">
                                <td class="px-6 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($months[$m - 1]); ?></td>
                                <td class="px-6 py-4 text-sm text-right text-green-600 font-semibold"><?php echo number_format($data['total_income'], 0, ',', ' '); ?></td>
                                <td class="px-6 py-4 text-sm text-right text-red-600 font-semibold"><?php echo number_format($data['total_expenses'], 0, ',', ' '); ?></td>
                                <td class="px-6 py-4 text-sm text-right font-semibold <?php echo $data['net_income'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo number_format($data['net_income'], 0, ',', ' '); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateYearRange() {
    const startYear = document.getElementById('startYear').value;
    const endYear = document.getElementById('endYear').value;

    if (parseInt(startYear) > parseInt(endYear)) {
        alert('Počáteční rok nemůže být větší než koncový rok');
        return;
    }

    window.location.href = '/reports/yearly?start_year=' + startYear + '&end_year=' + endYear;
}

function changeYear(value) {
    window.location.href = '/reports/yearly?year=' + value;
}
</script>
