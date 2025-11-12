<h1>⚠️ Budget Alert</h1>

<p>Hi <?php echo htmlspecialchars($user_name); ?>,</p>

<p>Your budget "<strong><?php echo htmlspecialchars($budget_name); ?></strong>" has exceeded or is close to its limit!</p>

<div class="alert-box">
    <h2 style="margin-top: 0; font-size: 18px;">Budget Status</h2>
    <p style="margin-bottom: 5px;"><strong>Budget Name:</strong> <?php echo htmlspecialchars($budget_name); ?></p>
    <p style="margin-bottom: 5px;"><strong>Current Spending:</strong> <?php echo number_format($current_amount, 2); ?> <?php echo $currency; ?></p>
    <p style="margin-bottom: 5px;"><strong>Budget Limit:</strong> <?php echo number_format($budget_amount, 2); ?> <?php echo $currency; ?></p>
    <p style="margin-bottom: 0;"><strong>Usage:</strong> <?php echo $percentage; ?>%</p>
</div>

<?php if ($percentage >= 100): ?>
    <p style="color: #c53030;">
        🚨 <strong>Budget Exceeded!</strong> You've gone over your budget by <?php echo number_format($current_amount - $budget_amount, 2); ?> <?php echo $currency; ?>.
    </p>
<?php elseif ($percentage >= 90): ?>
    <p style="color: #dd6b20;">
        ⚠️ <strong>Warning:</strong> You've used <?php echo $percentage; ?>% of your budget. Only <?php echo number_format($budget_amount - $current_amount, 2); ?> <?php echo $currency; ?> remaining.
    </p>
<?php endif; ?>

<p><strong>What you can do:</strong></p>
<ul style="padding-left: 20px; margin-bottom: 20px;">
    <li>Review your recent transactions</li>
    <li>Adjust spending in this category</li>
    <li>Consider increasing the budget if necessary</li>
    <li>Set up spending alerts for better control</li>
</ul>

<a href="<?php echo $_ENV['APP_URL'] ?? 'https://budget.yourdomain.com'; ?>/budgets" class="button">
    View Budget Details
</a>

<p style="font-size: 14px; color: #718096; margin-top: 30px;">
    Stay on track with your financial goals!
</p>
