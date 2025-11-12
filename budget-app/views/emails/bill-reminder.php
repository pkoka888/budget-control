<h1>📅 Bill Payment Reminder</h1>

<p>Hi <?php echo htmlspecialchars($user_name); ?>,</p>

<p>This is a reminder that you have an upcoming bill payment:</p>

<div class="info-box">
    <h2 style="margin-top: 0; font-size: 18px;">Bill Details</h2>
    <p style="margin-bottom: 5px;"><strong>Bill:</strong> <?php echo htmlspecialchars($bill_name); ?></p>
    <p style="margin-bottom: 5px;"><strong>Amount:</strong> <?php echo $amount; ?> <?php echo $currency; ?></p>
    <p style="margin-bottom: 5px;"><strong>Due Date:</strong> <?php echo $due_date; ?></p>
    <p style="margin-bottom: 0;"><strong>Days Until Due:</strong> <?php echo $days_until_due; ?> days</p>
</div>

<?php if ($days_until_due <= 3): ?>
    <p style="color: #c53030;">
        🚨 <strong>Urgent:</strong> This bill is due very soon! Make sure you have sufficient funds available.
    </p>
<?php elseif ($days_until_due <= 7): ?>
    <p style="color: #dd6b20;">
        ⚠️ <strong>Reminder:</strong> This bill is due in less than a week.
    </p>
<?php else: ?>
    <p style="color: #2c5282;">
        ℹ️ You have <?php echo $days_until_due; ?> days to prepare for this payment.
    </p>
<?php endif; ?>

<p><strong>Action items:</strong></p>
<ul style="padding-left: 20px; margin-bottom: 20px;">
    <li>Ensure sufficient funds in your account</li>
    <li>Set up automatic payment if available</li>
    <li>Mark as paid once completed</li>
    <li>Review other upcoming bills</li>
</ul>

<a href="<?php echo $_ENV['APP_URL'] ?? 'https://budget.yourdomain.com'; ?>/transactions/recurring" class="button">
    View Bills
</a>

<p style="font-size: 14px; color: #718096; margin-top: 30px;">
    Never miss a payment with Budget Control reminders!
</p>
