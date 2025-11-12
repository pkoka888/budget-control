<h1>🎯 Goal Milestone Reached!</h1>

<p>Hi <?php echo htmlspecialchars($user_name); ?>,</p>

<p>Congratulations! You've reached a milestone in your savings goal!</p>

<div class="success-box">
    <h2 style="margin-top: 0; font-size: 18px;">Milestone Achieved</h2>
    <p style="margin-bottom: 5px;"><strong>Goal:</strong> <?php echo htmlspecialchars($goal_name); ?></p>
    <p style="margin-bottom: 5px;"><strong>Milestone:</strong> <?php echo htmlspecialchars($milestone_name); ?></p>
    <p style="margin-bottom: 5px;"><strong>Amount Reached:</strong> <?php echo $milestone_amount; ?> <?php echo $currency; ?></p>
    <p style="margin-bottom: 0;"><strong>Progress:</strong> <?php echo $progress_percentage; ?>%</p>
</div>

<p style="font-size: 18px; color: #2f855a;">
    🎉 <strong>Well done!</strong> You're making excellent progress towards your financial goal.
</p>

<p><strong>Keep up the momentum:</strong></p>
<ul style="padding-left: 20px; margin-bottom: 20px;">
    <li>Continue your regular savings contributions</li>
    <li>Review your budget to find more savings opportunities</li>
    <li>Celebrate this achievement (responsibly!)</li>
    <li>Set a new milestone to keep motivated</li>
</ul>

<a href="<?php echo $_ENV['APP_URL'] ?? 'https://budget.yourdomain.com'; ?>/goals" class="button">
    View Goal Progress
</a>

<p style="font-size: 14px; color: #718096; margin-top: 30px;">
    Every milestone brings you closer to your financial dreams!
</p>
