<h1>📊 Your Weekly Financial Summary</h1>

<p>Hi <?php echo htmlspecialchars($user_name); ?>,</p>

<p>Here's your financial summary for the week of <strong><?php echo $week_start; ?></strong> to <strong><?php echo $week_end; ?></strong>:</p>

<div class="stats-box">
    <h2 style="margin-top: 0; font-size: 18px;">Week at a Glance</h2>

    <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                <strong>Total Income</strong>
            </td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #2f855a;">
                +<?php echo $total_income; ?> <?php echo $currency; ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                <strong>Total Expenses</strong>
            </td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #c53030;">
                -<?php echo $total_expenses; ?> <?php echo $currency; ?>
            </td>
        </tr>
        <tr>
            <td style="padding: 12px 0;">
                <strong style="font-size: 16px;">Net Savings</strong>
            </td>
            <td style="padding: 12px 0; text-align: right; font-size: 18px; font-weight: bold; color: <?php echo floatval($net_savings) >= 0 ? '#2f855a' : '#c53030'; ?>;">
                <?php echo $net_savings; ?> <?php echo $currency; ?>
            </td>
        </tr>
    </table>
</div>

<?php if (!empty($top_categories)): ?>
<div style="margin-top: 25px;">
    <h2 style="font-size: 18px;">Top Spending Categories</h2>
    <table style="width: 100%; border-collapse: collapse; margin: 10px 0;">
        <?php foreach ($top_categories as $category): ?>
        <tr>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0;">
                <?php echo htmlspecialchars($category['name']); ?>
            </td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right;">
                <?php echo number_format($category['amount'], 2); ?> <?php echo $currency; ?>
            </td>
            <td style="padding: 8px 0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #718096;">
                <?php echo round($category['percentage'], 1); ?>%
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>

<?php if (floatval($net_savings) >= 0): ?>
    <p style="color: #2f855a; margin-top: 20px;">
        💪 <strong>Great job!</strong> You saved <?php echo $net_savings; ?> <?php echo $currency; ?> this week.
    </p>
<?php else: ?>
    <p style="color: #c53030; margin-top: 20px;">
        ⚠️ <strong>Spending Alert:</strong> You spent <?php echo number_format(abs(floatval($net_savings)), 2); ?> <?php echo $currency; ?> more than you earned this week.
    </p>
<?php endif; ?>

<p><strong>Financial tips for next week:</strong></p>
<ul style="padding-left: 20px; margin-bottom: 20px;">
    <li>Review your top spending categories</li>
    <li>Look for opportunities to reduce expenses</li>
    <li>Set spending limits for problem areas</li>
    <li>Track daily expenses to stay on budget</li>
</ul>

<a href="<?php echo $_ENV['APP_URL'] ?? 'https://budget.yourdomain.com'; ?>/reports/weekly" class="button">
    View Detailed Report
</a>

<p style="font-size: 14px; color: #718096; margin-top: 30px;">
    Stay informed with weekly summaries from Budget Control!
</p>
