<?php
namespace BudgetApp\Services;

use BudgetApp\Database;

/**
 * Bill Automation Service
 * 
 * Manages recurring bills, payment scheduling, and automation
 */
class BillAutomationService {
    private Database $db;
    private $emailService;
    
    public function __construct(Database $db, $emailService = null) {
        $this->db = $db;
        $this->emailService = $emailService;
    }
    
    // ===== BILL MANAGEMENT =====
    
    public function createRecurringBill(int $userId, array $data): int {
        $this->db->query(
            "INSERT INTO recurring_bills 
             (user_id, provider_id, name, category, amount, amount_type, currency, frequency, next_due_date, payment_method, reminder_days_before, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $data['provider_id'] ?? null,
                $data['name'],
                $data['category'],
                $data['amount'] ?? null,
                $data['amount_type'] ?? 'fixed',
                $data['currency'] ?? 'CZK',
                $data['frequency'],
                $data['next_due_date'],
                $data['payment_method'] ?? null,
                $data['reminder_days_before'] ?? 3,
                $data['notes'] ?? null
            ]
        );
        
        $billId = $this->db->lastInsertId();
        
        // Create first payment record
        $this->createBillPayment($billId, $data['next_due_date'], $data['amount'] ?? 0);
        
        return $billId;
    }
    
    public function getUserBills(int $userId, bool $activeOnly = true): array {
        $query = "SELECT rb.*, bp.name as provider_name 
                  FROM recurring_bills rb
                  LEFT JOIN bill_providers bp ON bp.id = rb.provider_id
                  WHERE rb.user_id = ?";
        
        if ($activeOnly) {
            $query .= " AND rb.is_active = 1";
        }
        
        $query .= " ORDER BY rb.next_due_date ASC";
        
        return $this->db->query($query, [$userId]);
    }
    
    public function updateBill(int $billId, array $data): bool {
        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = ?";
            $params[] = $value;
        }
        
        $params[] = $billId;
        
        $this->db->query(
            "UPDATE recurring_bills SET " . implode(', ', $sets) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            $params
        );
        
        return true;
    }
    
    // ===== PAYMENT TRACKING =====
    
    private function createBillPayment(int $billId, string $dueDate, float $amount): int {
        $bill = $this->db->query(
            "SELECT * FROM recurring_bills WHERE id = ?",
            [$billId]
        )[0] ?? null;
        
        if (!$bill) throw new \Exception("Bill not found");
        
        $this->db->query(
            "INSERT INTO bill_payments 
             (recurring_bill_id, user_id, amount, currency, due_date, status, payment_method)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $billId,
                $bill['user_id'],
                $amount ?: $bill['amount'],
                $bill['currency'],
                $dueDate,
                'pending',
                $bill['payment_method']
            ]
        );
        
        $paymentId = $this->db->lastInsertId();
        
        // Create reminder
        $this->createReminder($bill['user_id'], $paymentId, $dueDate, $bill['reminder_days_before']);
        
        return $paymentId;
    }
    
    public function markBillPaid(int $paymentId, array $data): bool {
        $this->db->query(
            "UPDATE bill_payments 
             SET status = 'paid', 
                 paid_date = ?,
                 account_id = ?,
                 transaction_id = ?,
                 confirmation_number = ?,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [
                $data['paid_date'] ?? date('Y-m-d'),
                $data['account_id'] ?? null,
                $data['transaction_id'] ?? null,
                $data['confirmation_number'] ?? null,
                $paymentId
            ]
        );
        
        // Update bill's next due date
        $payment = $this->db->query(
            "SELECT * FROM bill_payments WHERE id = ?",
            [$paymentId]
        )[0] ?? null;
        
        if ($payment) {
            $this->updateNextDueDate($payment['recurring_bill_id'], $payment['due_date']);
        }
        
        return true;
    }
    
    private function updateNextDueDate(int $billId, string $currentDueDate): void {
        $bill = $this->db->query(
            "SELECT * FROM recurring_bills WHERE id = ?",
            [$billId]
        )[0] ?? null;
        
        if (!$bill) return;
        
        // Calculate next due date based on frequency
        $nextDate = $this->calculateNextDueDate($currentDueDate, $bill['frequency']);
        
        $this->db->query(
            "UPDATE recurring_bills 
             SET next_due_date = ?, last_due_date = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$nextDate, $currentDueDate, $billId]
        );
        
        // Create next payment record
        $this->createBillPayment($billId, $nextDate, $bill['amount']);
    }
    
    private function calculateNextDueDate(string $currentDate, string $frequency): string {
        $date = new \DateTime($currentDate);
        
        switch ($frequency) {
            case 'weekly':
                $date->modify('+1 week');
                break;
            case 'monthly':
                $date->modify('+1 month');
                break;
            case 'quarterly':
                $date->modify('+3 months');
                break;
            case 'annually':
                $date->modify('+1 year');
                break;
        }
        
        return $date->format('Y-m-d');
    }
    
    // ===== REMINDERS =====
    
    private function createReminder(int $userId, int $paymentId, string $dueDate, int $daysBefore): void {
        $reminderDate = date('Y-m-d', strtotime($dueDate . " -{$daysBefore} days"));
        
        $this->db->query(
            "INSERT INTO bill_reminders (user_id, bill_payment_id, reminder_date)
             VALUES (?, ?, ?)",
            [$userId, $paymentId, $reminderDate]
        );
    }
    
    public function sendDueReminders(): int {
        $today = date('Y-m-d');
        
        $reminders = $this->db->query(
            "SELECT r.*, bp.*, rb.name as bill_name, u.email, u.name as user_name
             FROM bill_reminders r
             JOIN bill_payments bp ON bp.id = r.bill_payment_id
             JOIN recurring_bills rb ON rb.id = bp.recurring_bill_id
             JOIN users u ON u.id = r.user_id
             WHERE r.reminder_date <= ? AND r.is_sent = 0 AND bp.status = 'pending'",
            [$today]
        );
        
        $sent = 0;
        foreach ($reminders as $reminder) {
            if ($this->emailService) {
                $this->emailService->sendBillReminder([
                    'email' => $reminder['email'],
                    'name' => $reminder['user_name']
                ], [
                    'name' => $reminder['bill_name'],
                    'amount' => $reminder['amount'],
                    'due_date' => $reminder['due_date']
                ]);
            }
            
            $this->db->query(
                "UPDATE bill_reminders SET is_sent = 1, sent_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$reminder['id']]
            );
            
            $sent++;
        }
        
        return $sent;
    }
    
    // ===== AUTOPAY =====
    
    public function processAutoPay(): int {
        $today = date('Y-m-d');
        
        $bills = $this->db->query(
            "SELECT rb.*, bp.*
             FROM recurring_bills rb
             JOIN bill_payments bp ON bp.recurring_bill_id = rb.id
             WHERE rb.auto_pay_enabled = 1 
                   AND bp.due_date = ?
                   AND bp.status = 'pending'",
            [$today]
        );
        
        $processed = 0;
        foreach ($bills as $bill) {
            try {
                // Process payment
                $this->executeAutoPay($bill);
                $processed++;
            } catch (\Exception $e) {
                // Mark as failed
                $this->db->query(
                    "UPDATE bill_payments SET status = 'failed', notes = ? WHERE id = ?",
                    [$e->getMessage(), $bill['id']]
                );
            }
        }
        
        return $processed;
    }
    
    private function executeAutoPay(array $bill): void {
        if (!$bill['auto_pay_account_id']) {
            throw new \Exception("No account configured for auto-pay");
        }
        
        // Check account balance
        $account = $this->db->query(
            "SELECT * FROM accounts WHERE id = ?",
            [$bill['auto_pay_account_id']]
        )[0] ?? null;
        
        if (!$account) {
            throw new \Exception("Auto-pay account not found");
        }
        
        if ($account['balance'] < $bill['amount']) {
            throw new \Exception("Insufficient funds");
        }
        
        // Create transaction
        $this->db->query(
            "INSERT INTO transactions 
             (user_id, account_id, type, amount, category, description, date)
             VALUES (?, ?, 'expense', ?, ?, ?, ?)",
            [
                $bill['user_id'],
                $bill['auto_pay_account_id'],
                $bill['amount'],
                $bill['category'],
                "Auto-pay: {$bill['name']}",
                date('Y-m-d')
            ]
        );
        
        $transactionId = $this->db->lastInsertId();
        
        // Update account balance
        $this->db->query(
            "UPDATE accounts SET balance = balance - ? WHERE id = ?",
            [$bill['amount'], $bill['auto_pay_account_id']]
        );
        
        // Mark bill as paid
        $this->markBillPaid($bill['id'], [
            'paid_date' => date('Y-m-d'),
            'account_id' => $bill['auto_pay_account_id'],
            'transaction_id' => $transactionId
        ]);
    }
    
    // ===== ANALYTICS =====
    
    public function getBillAnalytics(int $userId, int $months = 6): array {
        $startDate = date('Y-m-d', strtotime("-{$months} months"));
        
        $payments = $this->db->query(
            "SELECT 
                strftime('%Y-%m', paid_date) as month,
                SUM(amount) as total,
                COUNT(*) as count,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
                SUM(late_fee) as total_late_fees
             FROM bill_payments
             WHERE user_id = ? AND paid_date >= ?
             GROUP BY month
             ORDER BY month",
            [$userId, $startDate]
        );
        
        $byCategory = $this->db->query(
            "SELECT 
                rb.category,
                SUM(bp.amount) as total,
                COUNT(*) as count,
                AVG(bp.amount) as average
             FROM bill_payments bp
             JOIN recurring_bills rb ON rb.id = bp.recurring_bill_id
             WHERE bp.user_id = ? AND bp.paid_date >= ?
             GROUP BY rb.category
             ORDER BY total DESC",
            [$userId, $startDate]
        );
        
        return [
            'monthly' => $payments,
            'by_category' => $byCategory,
            'total_spent' => array_sum(array_column($payments, 'total')),
            'average_monthly' => count($payments) > 0 ? array_sum(array_column($payments, 'total')) / count($payments) : 0
        ];
    }
    
    public function getUpcomingBills(int $userId, int $days = 30): array {
        $endDate = date('Y-m-d', strtotime("+{$days} days"));
        
        return $this->db->query(
            "SELECT rb.*, bp.id as payment_id, bp.due_date, bp.status, bp.amount as payment_amount
             FROM recurring_bills rb
             JOIN bill_payments bp ON bp.recurring_bill_id = rb.id
             WHERE rb.user_id = ? AND rb.is_active = 1 
                   AND bp.due_date <= ? AND bp.status = 'pending'
             ORDER BY bp.due_date ASC",
            [$userId, $endDate]
        );
    }
    
    // ===== SUBSCRIPTIONS =====
    
    public function createSubscription(int $userId, array $data): int {
        // Create recurring bill first
        $billId = $this->createRecurringBill($userId, [
            'name' => $data['service_name'],
            'category' => $data['category'] ?? 'subscription',
            'amount' => $data['amount'],
            'frequency' => $data['billing_cycle'],
            'next_due_date' => $data['renewal_date'],
            'auto_pay_enabled' => $data['auto_renew'] ?? 1
        ]);
        
        // Create subscription record
        $this->db->query(
            "INSERT INTO subscriptions 
             (user_id, recurring_bill_id, service_name, category, amount, currency, billing_cycle, start_date, renewal_date, auto_renew, trial_end_date, is_trial)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $billId,
                $data['service_name'],
                $data['category'] ?? 'streaming',
                $data['amount'],
                $data['currency'] ?? 'CZK',
                $data['billing_cycle'],
                $data['start_date'] ?? date('Y-m-d'),
                $data['renewal_date'],
                $data['auto_renew'] ?? 1,
                $data['trial_end_date'] ?? null,
                $data['is_trial'] ?? 0
            ]
        );
        
        return $this->db->lastInsertId();
    }
    
    public function getUserSubscriptions(int $userId, ?string $status = 'active'): array {
        $query = "SELECT * FROM subscriptions WHERE user_id = ?";
        $params = [$userId];
        
        if ($status) {
            $query .= " AND status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY renewal_date ASC";
        
        return $this->db->query($query, $params);
    }
    
    public function cancelSubscription(int $subscriptionId): bool {
        $sub = $this->db->query(
            "SELECT * FROM subscriptions WHERE id = ?",
            [$subscriptionId]
        )[0] ?? null;
        
        if (!$sub) return false;
        
        $this->db->query(
            "UPDATE subscriptions SET status = 'cancelled', cancellation_date = ? WHERE id = ?",
            [date('Y-m-d'), $subscriptionId]
        );
        
        // Deactivate recurring bill
        if ($sub['recurring_bill_id']) {
            $this->db->query(
                "UPDATE recurring_bills SET is_active = 0 WHERE id = ?",
                [$sub['recurring_bill_id']]
            );
        }
        
        return true;
    }
}
