# Family & Partner Sharing - Safe Implementation Plan

## 🎯 Executive Summary

**Goal**: Enable secure multi-user access with household management, shared finances, and granular permissions.

**Approach**: Phased parallel implementation with safety gates and rollback capabilities.

**Timeline**: 4 weeks (with parallel execution: 2-3 weeks)

**Risk Level**: HIGH (affects core data model and security)

---

## 📋 Implementation Phases

### Phase 1: Foundation & Data Model (Week 1)
**Risk**: HIGH - Core schema changes
**Parallelization**: LOW - Must be sequential
**Rollback**: Database migration rollback

### Phase 2: Core Services & Logic (Week 1-2)
**Risk**: MEDIUM - Business logic
**Parallelization**: HIGH - Independent services
**Rollback**: Feature flags

### Phase 3: UI & Controllers (Week 2-3)
**Risk**: LOW - Presentation layer
**Parallelization**: VERY HIGH - All parallel
**Rollback**: Easy - just views

### Phase 4: Testing & Security (Week 3-4)
**Risk**: CRITICAL - Security validation
**Parallelization**: HIGH - Independent tests
**Rollback**: N/A - Must pass before release

---

## 🔐 Security Considerations (Critical!)

### Data Isolation Requirements
```
RULE 1: Private data MUST be isolated from household data
RULE 2: User permissions MUST be checked on EVERY query
RULE 3: Shared data MUST have explicit visibility rules
RULE 4: Audit logs MUST track all access attempts
RULE 5: Invitations MUST expire and be single-use
```

### Permission Model
```
Owner (100):
  - Full household control
  - Can delete household
  - Can manage all members
  - Can see all shared + private (own)

Partner (75):
  - Can manage shared finances
  - Can invite members
  - Can see all shared data
  - Cannot delete household
  - Can see own private data only

Viewer (50):
  - Read-only shared data
  - Cannot modify anything
  - Can see own private data only

Child (25):
  - Limited account access
  - Spending limits enforced
  - Requires approval for large expenses
  - Can see own data only
```

---

## 📊 Phase 1: Foundation & Data Model

### 1.1 Database Migrations (Sequential - Day 1-2)

**Migration 013: Household Foundation**
```sql
-- Critical: Add households table
CREATE TABLE households (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    created_by INTEGER NOT NULL,
    currency TEXT DEFAULT 'CZK',
    timezone TEXT DEFAULT 'Europe/Prague',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Critical: Track household members
CREATE TABLE household_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('owner', 'partner', 'viewer', 'child')),
    permissions TEXT, -- JSON: {can_edit_budgets: true, can_delete_transactions: false, ...}
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    invited_by INTEGER,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id),
    UNIQUE(household_id, user_id)
);

CREATE INDEX idx_household_members_user ON household_members(user_id);
CREATE INDEX idx_household_members_household ON household_members(household_id);

-- Critical: Secure invitation system
CREATE TABLE household_invitations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    email TEXT NOT NULL,
    role TEXT NOT NULL,
    token TEXT NOT NULL UNIQUE,
    invited_by INTEGER NOT NULL,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'accepted', 'rejected', 'expired')),
    expires_at DATETIME NOT NULL,
    accepted_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id)
);

CREATE INDEX idx_invitations_token ON household_invitations(token);
CREATE INDEX idx_invitations_email ON household_invitations(email);
```

**Migration 014: Shared Data Flags**
```sql
-- Add household_id and sharing flags to ALL core tables

-- Accounts
ALTER TABLE accounts ADD COLUMN household_id INTEGER REFERENCES households(id);
ALTER TABLE accounts ADD COLUMN is_shared INTEGER DEFAULT 0;
ALTER TABLE accounts ADD COLUMN visible_to TEXT; -- JSON array of user IDs who can see this
ALTER TABLE accounts ADD COLUMN created_by INTEGER REFERENCES users(id);

CREATE INDEX idx_accounts_household ON accounts(household_id);

-- Transactions
ALTER TABLE transactions ADD COLUMN household_id INTEGER REFERENCES households(id);
ALTER TABLE transactions ADD COLUMN is_private INTEGER DEFAULT 0; -- Private from household
ALTER TABLE transactions ADD COLUMN created_by INTEGER REFERENCES users(id);

CREATE INDEX idx_transactions_household ON transactions(household_id);
CREATE INDEX idx_transactions_private ON transactions(user_id, is_private);

-- Budgets
ALTER TABLE budgets ADD COLUMN household_id INTEGER REFERENCES households(id);
ALTER TABLE budgets ADD COLUMN is_shared INTEGER DEFAULT 0;
ALTER TABLE budgets ADD COLUMN managed_by TEXT; -- JSON array of user IDs who can manage

CREATE INDEX idx_budgets_household ON budgets(household_id);

-- Goals
ALTER TABLE goals ADD COLUMN household_id INTEGER REFERENCES households(id);
ALTER TABLE goals ADD COLUMN is_shared INTEGER DEFAULT 0;
ALTER TABLE goals ADD COLUMN contributors TEXT; -- JSON array of user IDs contributing

CREATE INDEX idx_goals_household ON goals(household_id);

-- Investment Accounts
ALTER TABLE investment_accounts ADD COLUMN household_id INTEGER REFERENCES households(id);
ALTER TABLE investment_accounts ADD COLUMN is_shared INTEGER DEFAULT 0;

-- Bills
ALTER TABLE recurring_bills ADD COLUMN household_id INTEGER REFERENCES households(id);
ALTER TABLE recurring_bills ADD COLUMN is_shared INTEGER DEFAULT 0;

-- Receipt Scans
ALTER TABLE receipt_scans ADD COLUMN is_private INTEGER DEFAULT 0;

-- Expense Groups (Already multi-user, but add household link)
ALTER TABLE expense_groups ADD COLUMN household_id INTEGER REFERENCES households(id);
```

**Migration 015: Activity & Audit**
```sql
-- Activity feed for transparency
CREATE TABLE household_activity (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    action TEXT NOT NULL, -- created_transaction, updated_budget, deleted_goal, etc.
    entity_type TEXT NOT NULL, -- transaction, budget, goal, account
    entity_id INTEGER,
    details TEXT, -- JSON with old/new values
    ip_address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE INDEX idx_activity_household ON household_activity(household_id, created_at DESC);
CREATE INDEX idx_activity_user ON household_activity(user_id, created_at DESC);

-- Approval workflows for sensitive actions
CREATE TABLE approval_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    requested_by INTEGER NOT NULL,
    request_type TEXT NOT NULL, -- large_expense, budget_change, delete_shared, etc.
    entity_type TEXT,
    entity_id INTEGER,
    amount REAL,
    reason TEXT,
    details TEXT, -- JSON
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected', 'cancelled')),
    approved_by INTEGER,
    approval_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

CREATE INDEX idx_approvals_household ON approval_requests(household_id, status);

-- Notifications system
CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    household_id INTEGER,
    notification_type TEXT NOT NULL, -- invitation, approval_needed, budget_alert, etc.
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    link TEXT,
    priority TEXT DEFAULT 'normal' CHECK(priority IN ('low', 'normal', 'high', 'urgent')),
    is_read INTEGER DEFAULT 0,
    read_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user_unread ON notifications(user_id, is_read, created_at DESC);
```

**Migration 016: Comments & Communication**
```sql
-- Comments on transactions, budgets, goals
CREATE TABLE comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    household_id INTEGER,
    entity_type TEXT NOT NULL, -- transaction, budget, goal, account
    entity_id INTEGER NOT NULL,
    comment TEXT NOT NULL,
    parent_comment_id INTEGER, -- For threaded replies
    is_edited INTEGER DEFAULT 0,
    edited_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE CASCADE
);

CREATE INDEX idx_comments_entity ON comments(entity_type, entity_id);
CREATE INDEX idx_comments_household ON comments(household_id, created_at DESC);

-- @mentions in comments
CREATE TABLE comment_mentions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    comment_id INTEGER NOT NULL,
    mentioned_user_id INTEGER NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (mentioned_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_mentions_user_unread ON comment_mentions(mentioned_user_id, is_read);
```

**Migration 017: Child Accounts**
```sql
-- Child account management
CREATE TABLE child_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    child_user_id INTEGER NOT NULL,
    parent_id INTEGER NOT NULL,
    account_id INTEGER, -- Linked account for the child
    allowance_amount REAL DEFAULT 0,
    allowance_frequency TEXT CHECK(allowance_frequency IN ('weekly', 'biweekly', 'monthly')),
    next_allowance_date DATE,
    spending_limit_daily REAL,
    spending_limit_weekly REAL,
    spending_limit_monthly REAL,
    requires_approval_over REAL, -- Transactions over this amount need approval
    approved_categories TEXT, -- JSON array of allowed categories
    approved_merchants TEXT, -- JSON array of allowed merchants
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (child_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    UNIQUE(household_id, child_user_id)
);

-- Allowance payment history
CREATE TABLE allowance_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    child_account_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    payment_date DATE NOT NULL,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'paid', 'skipped')),
    transaction_id INTEGER, -- Link to actual transaction
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (child_account_id) REFERENCES child_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id)
);

-- Chores & rewards (optional gamification)
CREATE TABLE chores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    household_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    reward_amount REAL DEFAULT 0,
    assigned_to INTEGER, -- NULL = available to all
    frequency TEXT, -- once, daily, weekly, monthly
    next_due_date DATE,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (household_id) REFERENCES households(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

CREATE TABLE chore_completions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chore_id INTEGER NOT NULL,
    completed_by INTEGER NOT NULL,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    verified_by INTEGER,
    verified_at DATETIME,
    reward_paid INTEGER DEFAULT 0,
    transaction_id INTEGER, -- Link to reward payment
    notes TEXT,
    FOREIGN KEY (chore_id) REFERENCES chores(id) ON DELETE CASCADE,
    FOREIGN KEY (completed_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(id)
);
```

### 1.2 Data Migration Strategy (Critical!)

**SAFETY FIRST: Zero Data Loss**

```php
// Migration script for existing users
class MigrateExistingUsersToHouseholds {

    public function up() {
        // Step 1: Create single-member households for all existing users
        $users = $db->query("SELECT id, email FROM users");

        foreach ($users as $user) {
            // Create household
            $db->query(
                "INSERT INTO households (name, created_by) VALUES (?, ?)",
                ["{$user['email']}'s Household", $user['id']]
            );
            $householdId = $db->lastInsertId();

            // Add user as owner
            $db->query(
                "INSERT INTO household_members (household_id, user_id, role, permissions)
                 VALUES (?, ?, 'owner', ?)",
                [$householdId, $user['id'], json_encode(['full_access' => true])]
            );

            // Link all existing data to this household
            $db->query("UPDATE accounts SET household_id = ?, is_shared = 0, created_by = ? WHERE user_id = ?",
                       [$householdId, $user['id'], $user['id']]);
            $db->query("UPDATE transactions SET household_id = ?, created_by = ? WHERE user_id = ?",
                       [$householdId, $user['id'], $user['id']]);
            $db->query("UPDATE budgets SET household_id = ?, is_shared = 0 WHERE user_id = ?",
                       [$householdId, $user['id']]);
            $db->query("UPDATE goals SET household_id = ?, is_shared = 0 WHERE user_id = ?",
                       [$householdId, $user['id']]);
            // ... continue for all tables
        }
    }

    public function down() {
        // Rollback: Remove household links but preserve data
        $db->query("UPDATE accounts SET household_id = NULL, is_shared = 0");
        $db->query("UPDATE transactions SET household_id = NULL");
        $db->query("UPDATE budgets SET household_id = NULL, is_shared = 0");
        $db->query("UPDATE goals SET household_id = NULL, is_shared = 0");
        // ... continue for all tables

        $db->query("DELETE FROM household_members");
        $db->query("DELETE FROM households");
    }
}
```

---

## 🔧 Phase 2: Core Services & Logic (Parallel Execution)

### 2.1 Permission System (Day 3-4) - CRITICAL

**Task 2.1.A: Permission Service** (Agent 1)
```php
class PermissionService {

    // Core permission check - MUST be called before EVERY data access
    public function canAccess(int $userId, string $entityType, int $entityId, string $action = 'read'): bool

    // Household membership check
    public function isHouseholdMember(int $userId, int $householdId): bool

    // Role-based permission check
    public function hasPermission(int $userId, int $householdId, string $permission): bool

    // Check if user can see specific entity
    public function canSeeEntity(int $userId, string $entityType, int $entityId): bool

    // Check if user can modify entity
    public function canModifyEntity(int $userId, string $entityType, int $entityId): bool

    // Get user's role in household
    public function getUserRole(int $userId, int $householdId): string

    // Get user's effective permissions
    public function getUserPermissions(int $userId, int $householdId): array
}
```

**Task 2.1.B: Query Modifier Service** (Agent 1)
```php
class QueryModifier {

    // Automatically add household filters to queries
    public function addHouseholdFilter(string $query, int $userId, string $table): string

    // Add visibility filters based on user permissions
    public function addVisibilityFilter(string $query, int $userId, string $table): string

    // Example:
    // SELECT * FROM transactions WHERE user_id = 1
    // becomes:
    // SELECT * FROM transactions WHERE
    //   (household_id IN (SELECT household_id FROM household_members WHERE user_id = 1))
    //   AND (is_private = 0 OR user_id = 1)
}
```

### 2.2 Household Management Service (Day 3-5) - PARALLEL

**Task 2.2.A: Household Service** (Agent 2)
```php
class HouseholdService {

    public function createHousehold(int $userId, array $data): int
    public function getHousehold(int $householdId): array
    public function updateHousehold(int $householdId, array $data): bool
    public function deleteHousehold(int $householdId, int $userId): bool

    public function getMembers(int $householdId): array
    public function addMember(int $householdId, int $userId, string $role, int $invitedBy): bool
    public function removeMember(int $householdId, int $userId, int $removedBy): bool
    public function updateMemberRole(int $householdId, int $userId, string $newRole, int $updatedBy): bool

    public function getUserHouseholds(int $userId): array
    public function switchActiveHousehold(int $userId, int $householdId): bool
}
```

**Task 2.2.B: Invitation Service** (Agent 2)
```php
class InvitationService {

    public function createInvitation(int $householdId, string $email, string $role, int $invitedBy): string
    public function getInvitation(string $token): array
    public function acceptInvitation(string $token, int $userId): bool
    public function rejectInvitation(string $token, int $userId): bool
    public function cancelInvitation(int $invitationId, int $userId): bool

    public function getPendingInvitations(int $householdId): array
    public function getInvitationsForEmail(string $email): array

    public function sendInvitationEmail(string $email, array $invitation): bool

    // Security: Expire old invitations
    public function expireOldInvitations(): int
}
```

### 2.3 Activity & Audit Service (Day 4-6) - PARALLEL

**Task 2.3.A: Activity Service** (Agent 3)
```php
class ActivityService {

    public function logActivity(int $householdId, int $userId, string $action, string $entityType, int $entityId, array $details = []): void

    public function getHouseholdActivity(int $householdId, int $limit = 50, int $offset = 0): array
    public function getUserActivity(int $userId, int $limit = 50): array
    public function getEntityActivity(string $entityType, int $entityId): array

    // Examples of tracked actions:
    // - created_transaction, updated_transaction, deleted_transaction
    // - created_budget, exceeded_budget
    // - created_goal, contributed_to_goal, achieved_goal
    // - invited_member, removed_member
    // - shared_account, unshared_account
}
```

**Task 2.3.B: Notification Service** (Agent 3)
```php
class NotificationService {

    public function createNotification(int $userId, string $type, string $title, string $message, array $options = []): int

    public function getUserNotifications(int $userId, bool $unreadOnly = false): array
    public function markAsRead(int $notificationId, int $userId): bool
    public function markAllAsRead(int $userId): bool
    public function deleteNotification(int $notificationId, int $userId): bool

    // Notification types:
    // - household_invitation
    // - member_joined, member_left
    // - approval_needed, approval_granted, approval_denied
    // - budget_exceeded, budget_warning
    // - large_transaction_detected
    // - goal_achieved
    // - bill_due, bill_paid
    // - comment_mention

    public function sendEmailNotification(int $notificationId): bool
    public function sendPushNotification(int $notificationId): bool
}
```

### 2.4 Approval Workflow Service (Day 5-6) - PARALLEL

**Task 2.4.A: Approval Service** (Agent 4)
```php
class ApprovalService {

    public function createApprovalRequest(int $householdId, int $userId, string $type, array $data): int
    public function approveRequest(int $requestId, int $approverId, string $notes = ''): bool
    public function rejectRequest(int $requestId, int $approverId, string $notes = ''): bool
    public function cancelRequest(int $requestId, int $userId): bool

    public function getPendingApprovals(int $householdId): array
    public function getUserPendingApprovals(int $userId): array
    public function canApprove(int $userId, int $requestId): bool

    // Configurable approval rules
    public function setApprovalRules(int $householdId, array $rules): bool
    public function getApprovalRules(int $householdId): array

    // Example rules:
    // - Transactions over X amount need approval
    // - Budget changes need approval
    // - Deleting shared items needs approval
    // - Child spending over limit needs approval
}
```

### 2.5 Comment Service (Day 5-6) - PARALLEL

**Task 2.5.A: Comment Service** (Agent 5)
```php
class CommentService {

    public function addComment(int $userId, string $entityType, int $entityId, string $comment, int $parentId = null): int
    public function editComment(int $commentId, int $userId, string $newComment): bool
    public function deleteComment(int $commentId, int $userId): bool

    public function getComments(string $entityType, int $entityId): array
    public function getComment(int $commentId): array

    // @mention parsing and notifications
    public function parseMentions(string $comment): array
    public function notifyMentionedUsers(int $commentId, array $mentionedUserIds): void

    public function getUserMentions(int $userId, bool $unreadOnly = true): array
    public function markMentionRead(int $mentionId, int $userId): bool
}
```

### 2.6 Child Account Service (Day 6-7) - PARALLEL

**Task 2.6.A: Child Account Service** (Agent 6)
```php
class ChildAccountService {

    public function createChildAccount(int $householdId, int $childUserId, int $parentId, array $settings): int
    public function updateChildAccount(int $childAccountId, array $settings): bool
    public function getChildAccount(int $childAccountId): array

    public function setSpendingLimits(int $childAccountId, array $limits): bool
    public function checkSpendingLimit(int $childAccountId, float $amount, string $period = 'daily'): bool

    public function setAllowance(int $childAccountId, float $amount, string $frequency): bool
    public function processAllowancePayments(): int // Cron job

    public function requiresApproval(int $childAccountId, float $amount, string $category = null): bool

    public function getChildTransactions(int $childAccountId, array $filters = []): array
    public function getSpendingReport(int $childAccountId, string $period = 'month'): array
}
```

**Task 2.6.B: Chore Service** (Agent 6)
```php
class ChoreService {

    public function createChore(int $householdId, array $data): int
    public function updateChore(int $choreId, array $data): bool
    public function deleteChore(int $choreId): bool

    public function getHouseholdChores(int $householdId, bool $activeOnly = true): array
    public function getAssignedChores(int $userId): array

    public function completeChore(int $choreId, int $userId, string $notes = ''): int
    public function verifyCompletion(int $completionId, int $verifierId, bool $approved): bool

    public function payReward(int $completionId): int // Returns transaction_id

    public function getChoreHistory(int $userId, int $days = 30): array
    public function getChoreStats(int $householdId): array
}
```

---

## 🎨 Phase 3: UI & Controllers (Highly Parallel)

### 3.1 Controllers (Day 7-10) - FULL PARALLEL

**Task 3.1.A: HouseholdController** (Agent 7)
```php
Endpoints:
GET    /household - Dashboard
GET    /household/create - Create form
POST   /household/create - Create household
GET    /household/{id} - View household
POST   /household/{id}/update - Update household
POST   /household/{id}/delete - Delete household
GET    /household/{id}/members - Member management
POST   /household/{id}/invite - Send invitation
POST   /household/{id}/remove-member - Remove member
POST   /household/{id}/update-role - Update member role
GET    /household/{id}/activity - Activity feed
GET    /household/switch/{id} - Switch active household
```

**Task 3.1.B: InvitationController** (Agent 7)
```php
Endpoints:
GET    /invitation/{token} - View invitation
POST   /invitation/{token}/accept - Accept invitation
POST   /invitation/{token}/reject - Reject invitation
GET    /invitations - My invitations
POST   /invitation/{id}/cancel - Cancel invitation
POST   /invitation/{id}/resend - Resend invitation email
```

**Task 3.1.C: NotificationController** (Agent 8)
```php
Endpoints:
GET    /notifications - List notifications
GET    /notifications/unread - Unread count
POST   /notifications/{id}/read - Mark as read
POST   /notifications/read-all - Mark all as read
POST   /notifications/{id}/delete - Delete notification
GET    /notifications/settings - Notification preferences
POST   /notifications/settings - Update preferences
```

**Task 3.1.D: ApprovalController** (Agent 8)
```php
Endpoints:
GET    /approvals - Pending approvals
GET    /approvals/{id} - View approval details
POST   /approvals/{id}/approve - Approve request
POST   /approvals/{id}/reject - Reject request
POST   /approvals/{id}/cancel - Cancel own request
GET    /approvals/settings - Approval rules
POST   /approvals/settings - Update approval rules
```

**Task 3.1.E: CommentController** (Agent 9)
```php
Endpoints:
GET    /comments/{entityType}/{entityId} - Get comments
POST   /comments/{entityType}/{entityId} - Add comment
PUT    /comments/{id} - Edit comment
DELETE /comments/{id} - Delete comment
GET    /mentions - My mentions
POST   /mentions/{id}/read - Mark mention as read
```

**Task 3.1.F: ChildAccountController** (Agent 9)
```php
Endpoints:
GET    /child-accounts - List child accounts
GET    /child-accounts/create - Create form
POST   /child-accounts/create - Create child account
GET    /child-accounts/{id} - View child account
POST   /child-accounts/{id}/update - Update settings
GET    /child-accounts/{id}/transactions - Child transactions
GET    /child-accounts/{id}/report - Spending report
POST   /child-accounts/{id}/allowance - Update allowance
```

**Task 3.1.G: ChoreController** (Agent 10)
```php
Endpoints:
GET    /chores - List chores
POST   /chores/create - Create chore
GET    /chores/{id} - View chore
POST   /chores/{id}/update - Update chore
POST   /chores/{id}/delete - Delete chore
POST   /chores/{id}/complete - Complete chore
POST   /chores/completion/{id}/verify - Verify completion
GET    /chores/history - Chore history
GET    /chores/stats - Chore statistics
```

### 3.2 Update Existing Controllers (Day 8-10) - PARALLEL

**Task 3.2.A: Update TransactionController** (Agent 11)
```php
Changes:
- Add permission checks using PermissionService
- Add household context to queries
- Add is_private flag support
- Add activity logging
- Add approval workflow for large transactions
- Add comment support
```

**Task 3.2.B: Update BudgetController** (Agent 11)
```php
Changes:
- Add shared budget support
- Add household filters
- Add permission checks
- Add approval workflow for budget changes
- Add activity logging
```

**Task 3.2.C: Update GoalController** (Agent 12)
```php
Changes:
- Add shared goal support
- Add multiple contributors
- Add household filters
- Add permission checks
- Add activity logging
```

**Task 3.2.D: Update AccountController** (Agent 12)
```php
Changes:
- Add shared account support
- Add visibility controls
- Add household filters
- Add permission checks
- Add activity logging
```

### 3.3 Views (Day 9-12) - FULL PARALLEL

**Task 3.3.A: Household Views** (Agent 13)
```
Files:
- views/household/index.php - List households
- views/household/dashboard.php - Household dashboard
- views/household/create.php - Create household form
- views/household/members.php - Member management
- views/household/activity.php - Activity feed
- views/household/settings.php - Household settings
```

**Task 3.3.B: Invitation Views** (Agent 13)
```
Files:
- views/invitation/accept.php - Accept invitation page
- views/invitation/list.php - My invitations
```

**Task 3.3.C: Notification Views** (Agent 14)
```
Files:
- views/notifications/index.php - Notification center
- views/notifications/settings.php - Notification preferences
- components/notification-bell.php - Header notification icon
```

**Task 3.3.D: Approval Views** (Agent 14)
```
Files:
- views/approvals/index.php - Pending approvals
- views/approvals/detail.php - Approval details
- views/approvals/settings.php - Approval rules
```

**Task 3.3.E: Comment Components** (Agent 15)
```
Files:
- components/comments.php - Comment section component
- components/comment-form.php - Add comment form
- components/mention-autocomplete.php - @mention autocomplete
```

**Task 3.3.F: Child Account Views** (Agent 15)
```
Files:
- views/child-accounts/index.php - Child accounts list
- views/child-accounts/create.php - Create child account
- views/child-accounts/dashboard.php - Child dashboard
- views/child-accounts/report.php - Spending report
```

**Task 3.3.G: Chore Views** (Agent 16)
```
Files:
- views/chores/index.php - Chore list
- views/chores/board.php - Chore board (Kanban style)
- views/chores/create.php - Create chore form
- views/chores/history.php - Chore history
```

**Task 3.3.H: Update Existing Views** (Agent 16)
```
Updates needed:
- Add "Share" buttons to accounts, budgets, goals
- Add household switcher in header
- Add visibility indicators (👁️ shared, 🔒 private)
- Add comment sections to detail pages
- Add approval status indicators
- Add activity indicators
```

---

## 🧪 Phase 4: Testing & Security (Parallel Testing)

### 4.1 Unit Tests (Day 11-13) - PARALLEL

**Task 4.1.A: Permission Tests** (Agent 17)
```php
Tests:
- test_owner_has_full_access()
- test_partner_cannot_delete_household()
- test_viewer_cannot_modify_data()
- test_child_spending_limits_enforced()
- test_private_data_isolated()
- test_shared_data_visible_to_household()
- test_permission_inheritance()
- test_permission_revocation()
```

**Task 4.1.B: Household Service Tests** (Agent 17)
```php
Tests:
- test_create_household()
- test_add_member()
- test_remove_member()
- test_update_member_role()
- test_delete_household_cascades()
- test_cannot_remove_last_owner()
```

**Task 4.1.C: Invitation Tests** (Agent 18)
```php
Tests:
- test_create_invitation()
- test_accept_invitation()
- test_reject_invitation()
- test_expired_invitation_fails()
- test_duplicate_email_invitation()
- test_invitation_token_security()
```

**Task 4.1.D: Approval Workflow Tests** (Agent 18)
```php
Tests:
- test_large_transaction_requires_approval()
- test_approve_request()
- test_reject_request()
- test_cannot_approve_own_request()
- test_approval_rules_enforced()
```

**Task 4.1.E: Activity & Audit Tests** (Agent 19)
```php
Tests:
- test_activity_logged()
- test_get_household_activity()
- test_activity_includes_details()
- test_activity_privacy()
```

**Task 4.1.F: Child Account Tests** (Agent 19)
```php
Tests:
- test_spending_limits_enforced()
- test_requires_approval_for_large_expense()
- test_allowance_payment_processing()
- test_category_restrictions()
```

### 4.2 Integration Tests (Day 12-14) - PARALLEL

**Task 4.2.A: Multi-User Scenarios** (Agent 20)
```php
Scenarios:
- test_partner_invites_joins_sees_shared_data()
- test_create_shared_budget_both_can_edit()
- test_private_transaction_not_visible_to_partner()
- test_household_activity_feed_shows_all_actions()
- test_approval_workflow_end_to_end()
- test_comment_and_mention_notification()
```

**Task 4.2.B: Data Isolation Tests** (Agent 20)
```php
Critical Tests:
- test_user_cannot_access_other_household_data()
- test_viewer_cannot_see_private_transactions()
- test_child_cannot_exceed_spending_limits()
- test_removed_member_loses_access()
- test_left_household_data_still_accessible()
```

**Task 4.2.C: Performance Tests** (Agent 21)
```php
Tests:
- test_household_queries_performant() // < 100ms
- test_activity_feed_paginated_efficiently()
- test_permission_checks_cached()
- test_bulk_operations_dont_timeout()
```

### 4.3 Security Audit (Day 13-14) - CRITICAL

**Task 4.3.A: Security Checklist** (Manual Review)
```
□ Permission checks on EVERY controller action
□ SQL injection prevention (prepared statements)
□ XSS prevention (output encoding)
□ CSRF tokens on all forms
□ Invitation tokens are cryptographically secure
□ Invitation tokens expire after use
□ Activity logs cannot be tampered with
□ Private data is truly isolated
□ API endpoints have permission checks
□ Bulk operations have rate limiting
□ File uploads are validated
□ Email addresses are validated
□ SQL queries use indexes
□ Sensitive data is not logged
```

**Task 4.3.B: Penetration Testing** (Manual)
```
Tests:
- Try to access another household's data
- Try to escalate permissions
- Try to bypass approval workflows
- Try to see private transactions
- Try to use expired invitation tokens
- Try to SQL inject household queries
- Try to XSS through comments
- Try to impersonate another user
```

### 4.4 User Acceptance Testing (Day 14) - PARALLEL

**Task 4.4.A: UAT Scenarios** (Manual Testing)
```
Scenario 1: Couple Setting Up Shared Finances
1. User A creates household "Smith Family"
2. User A invites User B as Partner
3. User B accepts invitation
4. User A creates shared bank account
5. Both users can see the account
6. User A adds transaction, User B sees it in activity feed
7. User B comments on transaction
8. Both users create shared budget
9. Both get notified when budget threshold reached

Scenario 2: Parent Managing Child Account
1. Parent creates child account for teenager
2. Sets $50/week allowance
3. Sets $100/day spending limit
4. Teenager makes $150 purchase -> requires approval
5. Parent reviews and approves
6. Teenager completes chore
7. Parent verifies and pays reward
8. Allowance automatically pays weekly

Scenario 3: Privacy & Private Transactions
1. Partner A marks transaction as private
2. Partner B cannot see it
3. Partner A has separate private account
4. Partner B sees account exists but not balance/transactions
5. Shared budget excludes private transactions
```

---

## 🚨 Risk Mitigation & Rollback Plan

### Feature Flags
```php
// .env configuration
ENABLE_HOUSEHOLD_FEATURES=false  // Master switch
ENABLE_INVITATIONS=false
ENABLE_APPROVALS=false
ENABLE_CHILD_ACCOUNTS=false
ENABLE_CHORES=false
ENABLE_COMMENTS=false

// Granular rollback capability
if (!config('ENABLE_HOUSEHOLD_FEATURES')) {
    // Old behavior: Single-user mode
    return $this->singleUserMode();
}
```

### Rollback Procedures

**Level 1: Disable Feature (Instant)**
```bash
# Set feature flag to false in .env
ENABLE_HOUSEHOLD_FEATURES=false

# Restart application
php artisan config:clear
```

**Level 2: Rollback Migrations (15 minutes)**
```bash
# Rollback all household migrations
php artisan migrate:rollback --step=5

# This will:
# - Remove household tables
# - Remove household_id columns
# - Preserve all user data
```

**Level 3: Database Restore (30 minutes)**
```bash
# Restore from backup taken before migration
./scripts/restore-database.sh backup_pre_household.db
```

### Monitoring & Alerts

**Critical Metrics to Monitor:**
```
- Permission check failures (should be 0)
- Unauthorized access attempts (alert if > 10/hour)
- Invitation token usage (detect brute force)
- Query performance (alert if > 500ms)
- Activity log growth (ensure not excessive)
- Notification queue depth (ensure processing)
```

**Alert Triggers:**
```
CRITICAL: Any user accessing another household's data
WARNING: Permission check taking > 100ms
WARNING: Invitation acceptance rate < 50%
INFO: New household created
INFO: Member added to household
```

---

## 📊 Parallel Execution Plan

### Week 1: Foundation
```
Day 1-2: Database Migrations (Sequential)
  └─ Agent: DBA
  └─ Output: Migrations 013-017

Day 3-4: Core Services (Parallel - 4 agents)
  ├─ Agent 1: PermissionService + QueryModifier
  ├─ Agent 2: HouseholdService + InvitationService
  ├─ Agent 3: ActivityService + NotificationService
  └─ Agent 4: ApprovalService

Day 5-6: Additional Services (Parallel - 3 agents)
  ├─ Agent 5: CommentService
  ├─ Agent 6: ChildAccountService + ChoreService
  └─ Agent 7: Start Controllers

Day 7: Integration Testing of Services
```

### Week 2: Controllers & Views
```
Day 8-10: Controllers (Parallel - 6 agents)
  ├─ Agent 7: HouseholdController + InvitationController
  ├─ Agent 8: NotificationController + ApprovalController
  ├─ Agent 9: CommentController + ChildAccountController
  ├─ Agent 10: ChoreController
  ├─ Agent 11: Update TransactionController + BudgetController
  └─ Agent 12: Update GoalController + AccountController

Day 9-12: Views (Parallel - 4 agents)
  ├─ Agent 13: Household + Invitation views
  ├─ Agent 14: Notification + Approval views
  ├─ Agent 15: Comment + ChildAccount views
  └─ Agent 16: Chore views + Update existing views
```

### Week 3: Testing
```
Day 11-13: Unit Tests (Parallel - 3 agents)
  ├─ Agent 17: Permission + Household tests
  ├─ Agent 18: Invitation + Approval tests
  └─ Agent 19: Activity + ChildAccount tests

Day 12-14: Integration Tests (Parallel - 2 agents)
  ├─ Agent 20: Multi-user scenarios + Data isolation
  └─ Agent 21: Performance tests

Day 13-14: Security Audit (Manual Review)
  └─ Security team review

Day 14: User Acceptance Testing
  └─ Manual testing scenarios
```

---

## ✅ Definition of Done

### For Each Service:
- [ ] All public methods have PHPDoc comments
- [ ] All database queries use prepared statements
- [ ] Permission checks implemented
- [ ] Activity logging implemented
- [ ] Error handling implemented
- [ ] Unit tests written (>80% coverage)
- [ ] Code review completed
- [ ] Security review completed

### For Each Controller:
- [ ] All endpoints have permission checks
- [ ] CSRF protection on POST/PUT/DELETE
- [ ] Input validation on all inputs
- [ ] Proper HTTP status codes
- [ ] Error messages are user-friendly
- [ ] Activity logging on state changes
- [ ] Integration tests written
- [ ] API documentation updated

### For Each View:
- [ ] Responsive design (mobile/tablet/desktop)
- [ ] Dark mode support
- [ ] Accessibility (ARIA labels, keyboard nav)
- [ ] XSS protection (output encoding)
- [ ] Loading states implemented
- [ ] Error states implemented
- [ ] Empty states implemented
- [ ] Manual testing completed

### For Overall Feature:
- [ ] All migrations run successfully
- [ ] Data migration completed without loss
- [ ] All tests passing
- [ ] Security audit completed
- [ ] Performance benchmarks met
- [ ] Documentation updated
- [ ] Feature flags configured
- [ ] Rollback plan tested
- [ ] Monitoring configured
- [ ] User acceptance testing passed

---

## 📝 Documentation Requirements

1. **API Documentation** - Update API_DOCUMENTATION.md with:
   - All new endpoints
   - Permission requirements
   - Request/response examples
   - Error codes

2. **User Guide** - Create HOUSEHOLD_USER_GUIDE.md with:
   - How to create a household
   - How to invite members
   - How to share accounts/budgets/goals
   - How to manage permissions
   - How to use approval workflows
   - How to set up child accounts

3. **Admin Guide** - Create HOUSEHOLD_ADMIN_GUIDE.md with:
   - Database schema explanation
   - Permission model
   - Security best practices
   - Monitoring & troubleshooting
   - Rollback procedures

4. **Migration Guide** - Create MIGRATION_TO_HOUSEHOLDS.md with:
   - Steps for existing users
   - What changes
   - FAQ
   - Troubleshooting

---

## 🎯 Success Criteria

1. **Functionality**: All scenarios in UAT pass
2. **Security**: Zero security issues in audit
3. **Performance**: All queries < 100ms, pages < 500ms
4. **Reliability**: Feature flags allow instant rollback
5. **User Experience**: Intuitive for non-technical users
6. **Data Integrity**: Zero data loss during migration
7. **Privacy**: Private data is truly isolated
8. **Scalability**: Household queries remain fast with 10+ members

---

## 🚀 Ready to Begin?

This plan provides:
- ✅ Safe, phased implementation
- ✅ Maximum parallelization
- ✅ Security-first approach
- ✅ Comprehensive testing
- ✅ Rollback capabilities
- ✅ Clear success criteria

**Estimated Timeline**: 2-3 weeks with parallel execution

**Next Step**: Shall I begin implementing Phase 1 (Database Migrations)?
