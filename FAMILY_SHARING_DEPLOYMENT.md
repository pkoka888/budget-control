# Family Sharing Deployment Guide

This guide covers deploying the Family Sharing features to production.

## 📋 Pre-Deployment Checklist

### 1. Database Migrations

✅ **Migrations Applied:**
- ✅ Migration 013: Household Foundation
- ✅ Migration 014: Shared Data Flags
- ✅ Migration 015: Activity & Audit
- ✅ Migration 016: Comments & Communication
- ⚠️  Migration 017: Child Accounts (optional, needs manual verification)

**To apply migrations:**
```bash
cd budget-app/database
php fix_household_migrations.php
```

### 2. Services & Controllers

✅ **Services Copied** (9 services):
- PermissionService
- HouseholdService
- InvitationService
- ActivityService
- NotificationService
- ApprovalService
- CommentService
- ChildAccountService
- ChoreService

✅ **Controllers Copied** (4 controllers):
- HouseholdController
- NotificationController
- ApprovalController
- ChildAccountController

**Location:** `budget-app/src/Services/` and `budget-app/src/Controllers/`

### 3. Environment Configuration

Add these variables to your `.env` file:

```bash
# Email Configuration (Required for invitations)
EMAIL_PROVIDER=smtp                    # Options: smtp, sendgrid, mailgun, aws_ses
EMAIL_FROM=noreply@yourdomain.com
EMAIL_FROM_NAME="Budget Control"

# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your_email@gmail.com
SMTP_PASSWORD=your_app_password
SMTP_ENCRYPTION=tls

# SendGrid (Alternative)
# SENDGRID_API_KEY=your_sendgrid_key

# Mailgun (Alternative)
# MAILGUN_API_KEY=your_mailgun_key
# MAILGUN_DOMAIN=your_domain

# Application URL (for invitation links)
APP_URL=https://yourdomain.com

# Household Settings
HOUSEHOLD_DEFAULT_CURRENCY=CZK
HOUSEHOLD_MAX_MEMBERS=10
HOUSEHOLD_INVITATION_EXPIRY_DAYS=7
APPROVAL_REQUEST_EXPIRY_HOURS=48

# Child Account Limits
CHILD_DEFAULT_DAILY_LIMIT=10.00
CHILD_DEFAULT_WEEKLY_LIMIT=50.00
CHILD_DEFAULT_MONTHLY_LIMIT=200.00
CHILD_DEFAULT_TRANSACTION_LIMIT=20.00
CHILD_DEFAULT_APPROVAL_THRESHOLD=10.00
```

## 🚀 Deployment Steps

### Step 1: Backup Database

```bash
cp budget-app/database/budget.db budget-app/database/budget.db.backup
```

### Step 2: Apply Migrations

```bash
cd budget-app/database
php fix_household_migrations.php
```

**Expected output:**
```
✅ Migration 013 complete
✅ Migration 014 complete
✅ Migration 015 complete
✅ All household migrations applied successfully!
```

### Step 3: Copy Assets

```bash
# Copy new services
cp -r app/Services/*.php budget-app/src/Services/

# Copy new controllers
cp -r app/Controllers/*.php budget-app/src/Controllers/

# Copy new views
cp -r app/Views/household budget-app/src/Views/
```

### Step 4: Update Router Configuration

Add these routes to your router (example for budget-app):

```php
// Household Management
$router->get('/household', 'HouseholdController@index');
$router->get('/household/{id}', 'HouseholdController@show');
$router->post('/household/store', 'HouseholdController@store');
$router->put('/household/{id}/update', 'HouseholdController@update');
$router->post('/household/{id}/invite', 'HouseholdController@inviteMember');

// Notifications
$router->get('/notifications', 'NotificationController@index');
$router->post('/notifications/{id}/read', 'NotificationController@markAsRead');
$router->post('/notifications/read-all', 'NotificationController@markAllAsRead');

// Approvals
$router->get('/approval/household/{id}', 'ApprovalController@index');
$router->post('/approval/{id}/approve', 'ApprovalController@approve');
$router->post('/approval/{id}/reject', 'ApprovalController@reject');

// Child Accounts
$router->get('/child-account/{householdId}', 'ChildAccountController@index');
$router->post('/child-account/{householdId}/money-request', 'ChildAccountController@createMoneyRequest');
$router->post('/child-account/chore/{choreId}/complete', 'ChildAccountController@completeChore');
```

See `FAMILY_SHARING_ROUTES.md` for complete route list.

### Step 5: Set Up Cron Jobs

Add to your crontab:

```bash
# Process allowances, expire invitations (daily at midnight)
0 0 * * * /usr/bin/php /path/to/budget-control/cron/family_sharing_automation.php daily

# Cleanup tasks (every 6 hours)
0 */6 * * * /usr/bin/php /path/to/budget-control/cron/family_sharing_automation.php periodic
```

### Step 6: Test Email Configuration

```bash
cd budget-app
php -r "
require_once 'src/bootstrap.php';
\$email = new \BudgetApp\Services\EmailService(\$db, \$config);
\$result = \$email->send('your-email@example.com', 'Test', 'Test email');
echo \$result ? 'Email sent successfully!' : 'Email failed!';
"
```

### Step 7: Verify Migrations

```bash
php -r "
\$db = new PDO('sqlite:budget-app/database/budget.db');
\$tables = ['households', 'household_members', 'household_invitations', 'household_settings',
           'household_activities', 'approval_requests', 'notifications'];
foreach (\$tables as \$table) {
    \$count = \$db->query(\"SELECT COUNT(*) FROM \$table\")->fetchColumn();
    echo \"\$table: \$count rows\n\";
}
"
```

## 🧪 Testing

### 1. Create Test Household

```bash
# Visit your app
http://yourdomain.com/household

# Or via API
curl -X POST http://yourdomain.com/household/store \
  -d "name=Test Family&description=Test&currency=CZK" \
  --cookie "session=YOUR_SESSION"
```

### 2. Test Invitation Flow

1. Create household
2. Invite member via email
3. Check invitation email received
4. Accept invitation via link
5. Verify member appears in household

### 3. Test Permissions

```bash
# Test as Owner (should succeed)
curl -X POST http://yourdomain.com/household/1/invite \
  -d "email=partner@example.com&role=partner"

# Test as Viewer (should fail)
# Login as viewer and try same request
```

### 4. Test Child Account

1. Add child member to household
2. Set spending limits
3. Create chore
4. Child completes chore
5. Parent verifies chore
6. Check child balance updated

## 📊 Monitoring

### Database Health Check

```bash
php -r "
\$db = new PDO('sqlite:budget-app/database/budget.db');

// Check for orphaned data
\$orphans = \$db->query('
    SELECT COUNT(*) FROM household_members
    WHERE household_id NOT IN (SELECT id FROM households)
')->fetchColumn();

echo \"Orphaned members: \$orphans\n\";

// Check pending invitations
\$pending = \$db->query('
    SELECT COUNT(*) FROM household_invitations
    WHERE status = \"pending\" AND expires_at < datetime(\"now\")
')->fetchColumn();

echo \"Expired pending invitations: \$pending\n\";
"
```

### Performance Monitoring

Monitor these queries for performance:

```sql
-- Slow query: Activity feed
EXPLAIN QUERY PLAN SELECT * FROM household_activities WHERE household_id = ? ORDER BY created_at DESC LIMIT 50;

-- Slow query: Permission checks
EXPLAIN QUERY PLAN SELECT * FROM household_members WHERE user_id = ? AND household_id = ?;
```

Consider adding indexes if needed.

## 🔒 Security Considerations

### 1. Permission Checks

Ensure all controllers check permissions:

```php
// Example from HouseholdController
$this->permissionService->requirePermission($userId, $householdId, PermissionService::PERM_MANAGE_MEMBERS);
```

### 2. Data Isolation

Verify private data is not leaked:

```php
// Bad: Returns all transactions
SELECT * FROM transactions;

// Good: Returns only accessible transactions
SELECT * FROM transactions
WHERE (user_id = ? OR (visibility = 'shared' AND household_id IN (...)));
```

### 3. Child Safety

- [ ] Spending limits enforced
- [ ] Approval thresholds working
- [ ] Category restrictions applied
- [ ] Time-of-day limits working

### 4. Audit Logging

Check audit logs are being created:

```sql
SELECT COUNT(*) FROM audit_logs WHERE created_at > datetime('now', '-1 day');
```

## 🐛 Troubleshooting

### Issue: Migrations Fail

**Symptom:** Migration script exits with errors

**Solution:**
```bash
# Check what migrations are applied
php -r "
\$db = new PDO('sqlite:budget-app/database/budget.db');
\$result = \$db->query('SELECT * FROM schema_migrations ORDER BY applied_at');
while (\$row = \$result->fetch()) {
    echo \$row['migration_name'] . ' - ' . \$row['applied_at'] . \"\n\";
}
"

# Manually fix failed migration
# Edit the SQL file to fix issues, then rerun
```

### Issue: Email Not Sending

**Symptom:** Invitations not received

**Solution:**
1. Check `.env` email configuration
2. Test email service manually
3. Check SMTP credentials
4. Verify firewall allows outbound port 587
5. Check spam folder

### Issue: Permission Denied Errors

**Symptom:** Users can't access household data

**Solution:**
```bash
# Check user is member of household
php -r "
\$db = new PDO('sqlite:budget-app/database/budget.db');
\$stmt = \$db->prepare('SELECT * FROM household_members WHERE user_id = ? AND household_id = ?');
\$stmt->execute([USER_ID, HOUSEHOLD_ID]);
var_dump(\$stmt->fetch());
"

# Check permission level
# Owner: 100, Partner: 75, Viewer: 50, Child: 25
```

### Issue: Slow Queries

**Symptom:** Household pages load slowly

**Solution:**
```bash
# Add missing indexes
php -r "
\$db = new PDO('sqlite:budget-app/database/budget.db');
\$db->exec('CREATE INDEX IF NOT EXISTS idx_transactions_household_user ON transactions(household_id, user_id)');
\$db->exec('CREATE INDEX IF NOT EXISTS idx_activities_household_created ON household_activities(household_id, created_at DESC)');
echo 'Indexes created';
"
```

## 📈 Scaling Considerations

### For Large Households (10+ members)

1. **Cache household member data**
   - Store member list in Redis/Memcached
   - Invalidate on membership changes

2. **Paginate activity feeds**
   - Implement cursor-based pagination
   - Load activities on scroll

3. **Async processing**
   - Move allowance processing to queue
   - Process notifications in batches

### For Many Households (100+ households)

1. **Database optimization**
   - Add composite indexes
   - Consider partitioning large tables
   - Use read replicas for reporting

2. **Caching strategy**
   - Cache permission checks
   - Cache household settings
   - Use ETags for API responses

## ✅ Post-Deployment Checklist

- [ ] All migrations applied successfully
- [ ] Services and controllers copied
- [ ] Router configuration updated
- [ ] Environment variables configured
- [ ] Email sending works
- [ ] Cron jobs configured
- [ ] Test household created
- [ ] Invitation flow tested
- [ ] Permissions working correctly
- [ ] Child accounts tested
- [ ] Monitoring configured
- [ ] Backup strategy in place

## 🆘 Support

If you encounter issues:

1. Check logs: `tail -f budget-app/logs/error.log`
2. Review audit logs: `SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 100`
3. Check database integrity: `PRAGMA integrity_check`
4. Restore from backup if needed

## 🎉 Success!

Once deployed, you should be able to:

- ✅ Create households and invite family members
- ✅ Share transactions and budgets
- ✅ Set up child accounts with allowances
- ✅ Track chores and rewards
- ✅ Manage approval workflows
- ✅ View activity feed
- ✅ Receive notifications

Enjoy your new multi-user budget app!
