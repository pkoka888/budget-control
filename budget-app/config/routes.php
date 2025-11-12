<?php
/**
 * Application Routes Configuration
 *
 * Maps URLs to controllers and actions
 */

return [
    // Core Features (Phase 1-3)
    '/' => ['controller' => 'Dashboard', 'action' => 'index'],
    '/dashboard' => ['controller' => 'Dashboard', 'action' => 'index'],
    '/transactions' => ['controller' => 'Transaction', 'action' => 'index'],
    '/transactions/create' => ['controller' => 'Transaction', 'action' => 'create'],
    '/transactions/edit/{id}' => ['controller' => 'Transaction', 'action' => 'edit'],
    '/transactions/delete/{id}' => ['controller' => 'Transaction', 'action' => 'delete'],
    '/budgets' => ['controller' => 'Budget', 'action' => 'index'],
    '/goals' => ['controller' => 'Goal', 'action' => 'index'],
    '/accounts' => ['controller' => 'Account', 'action' => 'index'],
    '/reports' => ['controller' => 'Report', 'action' => 'index'],
    '/opportunities' => ['controller' => 'Opportunity', 'action' => 'index'],
    '/scenario' => ['controller' => 'Scenario', 'action' => 'index'],
    '/automation' => ['controller' => 'Automation', 'action' => 'index'],

    // v1.1 Features - Multi-Currency
    '/currency' => ['controller' => 'Currency', 'action' => 'index'],
    '/currency/converter' => ['controller' => 'Currency', 'action' => 'converter'],
    '/currency/trends' => ['controller' => 'Currency', 'action' => 'trends'],
    '/currency/update-preferences' => ['controller' => 'Currency', 'action' => 'updatePreferences', 'method' => 'POST'],
    '/currency/get-rate' => ['controller' => 'Currency', 'action' => 'getExchangeRate'],
    '/currency/convert' => ['controller' => 'Currency', 'action' => 'convert'],
    '/currency/history' => ['controller' => 'Currency', 'action' => 'getHistory'],
    '/currency/update-rates' => ['controller' => 'Currency', 'action' => 'updateRates', 'method' => 'POST'],
    '/currency/currencies' => ['controller' => 'Currency', 'action' => 'getCurrencies'],

    // v1.1 Features - Expense Splitting
    '/expense-split' => ['controller' => 'ExpenseSplit', 'action' => 'index'],
    '/expense-split/group' => ['controller' => 'ExpenseSplit', 'action' => 'show'],
    '/expense-split/create' => ['controller' => 'ExpenseSplit', 'action' => 'create'],
    '/expense-split/store' => ['controller' => 'ExpenseSplit', 'action' => 'store', 'method' => 'POST'],
    '/expense-split/add-expense' => ['controller' => 'ExpenseSplit', 'action' => 'addExpense', 'method' => 'POST'],
    '/expense-split/settle' => ['controller' => 'ExpenseSplit', 'action' => 'settle', 'method' => 'POST'],
    '/expense-split/confirm-settlement' => ['controller' => 'ExpenseSplit', 'action' => 'confirmSettlement', 'method' => 'POST'],
    '/expense-split/balance' => ['controller' => 'ExpenseSplit', 'action' => 'getBalance'],
    '/expense-split/accept/{token}' => ['controller' => 'ExpenseSplit', 'action' => 'acceptInvitation'],
    '/expense-split/invite' => ['controller' => 'ExpenseSplit', 'action' => 'inviteMember', 'method' => 'POST'],
    '/expense-split/leave' => ['controller' => 'ExpenseSplit', 'action' => 'leave', 'method' => 'POST'],
    '/expense-split/delete-expense' => ['controller' => 'ExpenseSplit', 'action' => 'deleteExpense', 'method' => 'POST'],

    // v1.1 Features - Receipt OCR
    '/receipt' => ['controller' => 'Receipt', 'action' => 'index'],
    '/receipt/upload' => ['controller' => 'Receipt', 'action' => 'upload', 'method' => 'POST'],
    '/receipt/scan' => ['controller' => 'Receipt', 'action' => 'show'],
    '/receipt/get-scan' => ['controller' => 'Receipt', 'action' => 'getScan'],
    '/receipt/list' => ['controller' => 'Receipt', 'action' => 'list'],
    '/receipt/review-queue' => ['controller' => 'Receipt', 'action' => 'reviewQueue'],
    '/receipt/update-scan' => ['controller' => 'Receipt', 'action' => 'updateScan', 'method' => 'POST'],
    '/receipt/create-transaction' => ['controller' => 'Receipt', 'action' => 'createTransaction', 'method' => 'POST'],
    '/receipt/delete' => ['controller' => 'Receipt', 'action' => 'delete', 'method' => 'POST'],
    '/receipt/stats' => ['controller' => 'Receipt', 'action' => 'stats'],
    '/receipt/merchants' => ['controller' => 'Receipt', 'action' => 'merchants'],

    // v2.0 Features - Investment Portfolio
    '/investment' => ['controller' => 'Investment', 'action' => 'index'],
    '/investment/accounts' => ['controller' => 'Investment', 'action' => 'accounts'],
    '/investment/add-holding' => ['controller' => 'Investment', 'action' => 'addHolding', 'method' => 'POST'],
    '/investment/update-prices' => ['controller' => 'Investment', 'action' => 'updatePrices', 'method' => 'POST'],
    '/investment/transactions' => ['controller' => 'Investment', 'action' => 'transactions'],
    '/investment/performance' => ['controller' => 'Investment', 'action' => 'performance'],
    '/investment/watchlist' => ['controller' => 'Investment', 'action' => 'watchlist'],
    '/investment/dividends' => ['controller' => 'Investment', 'action' => 'dividends'],
    '/investment/sectors' => ['controller' => 'Investment', 'action' => 'sectors'],

    // v2.0 Features - AI Insights
    '/ai-insights' => ['controller' => 'AIInsights', 'action' => 'index'],
    '/ai-insights/generate' => ['controller' => 'AIInsights', 'action' => 'generate', 'method' => 'POST'],
    '/ai-insights/chat' => ['controller' => 'AIInsights', 'action' => 'chat', 'method' => 'POST'],
    '/ai-insights/{id}/dismiss' => ['controller' => 'AIInsights', 'action' => 'dismiss', 'method' => 'POST'],
    '/ai-insights/{id}/mark-read' => ['controller' => 'AIInsights', 'action' => 'markRead', 'method' => 'POST'],
    '/ai-insights/patterns' => ['controller' => 'AIInsights', 'action' => 'patterns'],
    '/ai-insights/opportunities' => ['controller' => 'AIInsights', 'action' => 'opportunities'],
    '/ai-insights/predictions' => ['controller' => 'AIInsights', 'action' => 'predictions'],
    '/ai-insights/anomalies' => ['controller' => 'AIInsights', 'action' => 'anomalies'],

    // v2.0 Features - Bill Automation
    '/bill' => ['controller' => 'Bill', 'action' => 'index'],
    '/bill/create' => ['controller' => 'Bill', 'action' => 'create', 'method' => 'POST'],
    '/bill/edit/{id}' => ['controller' => 'Bill', 'action' => 'edit'],
    '/bill/mark-paid' => ['controller' => 'Bill', 'action' => 'markPaid', 'method' => 'POST'],
    '/bill/subscriptions' => ['controller' => 'Bill', 'action' => 'subscriptions'],
    '/bill/calendar' => ['controller' => 'Bill', 'action' => 'calendar'],
    '/bill/analytics' => ['controller' => 'Bill', 'action' => 'analytics'],
    '/bill/providers' => ['controller' => 'Bill', 'action' => 'providers'],
    '/bill/reminders' => ['controller' => 'Bill', 'action' => 'reminders'],
    '/bill/payment-methods' => ['controller' => 'Bill', 'action' => 'paymentMethods'],

    // v2.0 Features - Data Export & API
    '/export' => ['controller' => 'Export', 'action' => 'index'],
    '/export/create' => ['controller' => 'Export', 'action' => 'create', 'method' => 'POST'],
    '/export/download/{id}' => ['controller' => 'Export', 'action' => 'download'],
    '/export/api-keys' => ['controller' => 'Export', 'action' => 'apiKeys'],
    '/export/create-api-key' => ['controller' => 'Export', 'action' => 'createApiKey', 'method' => 'POST'],
    '/export/import' => ['controller' => 'Export', 'action' => 'import'],
    '/export/webhooks' => ['controller' => 'Export', 'action' => 'webhooks'],
    '/export/integrations' => ['controller' => 'Export', 'action' => 'integrations'],
    '/export/scheduled' => ['controller' => 'Export', 'action' => 'scheduled'],

    // API v1 Endpoints
    '/api/v1/transactions' => ['controller' => 'Api\\Transaction', 'action' => 'index'],
    '/api/v1/transactions/{id}' => ['controller' => 'Api\\Transaction', 'action' => 'show'],
    '/api/v1/budgets' => ['controller' => 'Api\\Budget', 'action' => 'index'],
    '/api/v1/goals' => ['controller' => 'Api\\Goal', 'action' => 'index'],
    '/api/v1/accounts' => ['controller' => 'Api\\Account', 'action' => 'index'],
    '/api/v1/investments' => ['controller' => 'Api\\Investment', 'action' => 'index'],
    '/api/v1/insights' => ['controller' => 'Api\\Insights', 'action' => 'index'],
    '/api/v1/bills' => ['controller' => 'Api\\Bill', 'action' => 'index'],

    // v2.0 Features - Family Sharing
    // Household Management
    '/household' => ['controller' => 'Household', 'action' => 'index'],
    '/household/create' => ['controller' => 'Household', 'action' => 'create'],
    '/household/store' => ['controller' => 'Household', 'action' => 'store', 'method' => 'POST'],
    '/household/{id}' => ['controller' => 'Household', 'action' => 'show'],
    '/household/{id}/update' => ['controller' => 'Household', 'action' => 'update', 'method' => 'POST'],
    '/household/{id}/delete' => ['controller' => 'Household', 'action' => 'delete', 'method' => 'POST'],
    '/household/{id}/leave' => ['controller' => 'Household', 'action' => 'leave', 'method' => 'POST'],

    // Member Management
    '/household/{id}/invite' => ['controller' => 'Household', 'action' => 'inviteMember', 'method' => 'POST'],
    '/household/{id}/member/{memberId}/role' => ['controller' => 'Household', 'action' => 'updateMemberRole', 'method' => 'POST'],
    '/household/{id}/member/{memberId}/remove' => ['controller' => 'Household', 'action' => 'removeMember', 'method' => 'POST'],

    // Invitations
    '/invitation/accept/{token}' => ['controller' => 'Household', 'action' => 'acceptInvitation'],
    '/invitation/accept' => ['controller' => 'Household', 'action' => 'processAcceptance', 'method' => 'POST'],
    '/invitation/{id}/cancel' => ['controller' => 'Household', 'action' => 'cancelInvitation', 'method' => 'POST'],
    '/invitation/{id}/resend' => ['controller' => 'Household', 'action' => 'resendInvitation', 'method' => 'POST'],

    // Activity Feed
    '/activity/{householdId}' => ['controller' => 'Household', 'action' => 'activity'],
    '/activity/{householdId}/filter' => ['controller' => 'Household', 'action' => 'filterActivity'],

    // Notifications
    '/notifications' => ['controller' => 'Notification', 'action' => 'index'],
    '/notifications/unread' => ['controller' => 'Notification', 'action' => 'unread'],
    '/notifications/{id}/read' => ['controller' => 'Notification', 'action' => 'markAsRead', 'method' => 'POST'],
    '/notifications/mark-all-read' => ['controller' => 'Notification', 'action' => 'markAllAsRead', 'method' => 'POST'],
    '/notifications/{id}/dismiss' => ['controller' => 'Notification', 'action' => 'dismiss', 'method' => 'POST'],

    // Approvals
    '/approval/household/{householdId}' => ['controller' => 'Approval', 'action' => 'index'],
    '/approval/{id}' => ['controller' => 'Approval', 'action' => 'show'],
    '/approval/{id}/approve' => ['controller' => 'Approval', 'action' => 'approve', 'method' => 'POST'],
    '/approval/{id}/reject' => ['controller' => 'Approval', 'action' => 'reject', 'method' => 'POST'],

    // Child Accounts
    '/child-account/{householdId}' => ['controller' => 'ChildAccount', 'action' => 'index'],
    '/child-account/{householdId}/settings' => ['controller' => 'ChildAccount', 'action' => 'settings'],
    '/child-account/{householdId}/transactions' => ['controller' => 'ChildAccount', 'action' => 'transactions'],
    '/child-account/{householdId}/allowance' => ['controller' => 'ChildAccount', 'action' => 'allowance'],
    '/child-account/{householdId}/money-request' => ['controller' => 'ChildAccount', 'action' => 'createMoneyRequest', 'method' => 'POST'],
    '/child-account/money-request/{id}/cancel' => ['controller' => 'ChildAccount', 'action' => 'cancelMoneyRequest', 'method' => 'POST'],
    '/child-account/chore/{choreId}/complete' => ['controller' => 'ChildAccount', 'action' => 'completeChore', 'method' => 'POST'],

    // Chores
    '/chores/household/{householdId}' => ['controller' => 'Chore', 'action' => 'index'],
    '/chores/household/{householdId}/stats' => ['controller' => 'Chore', 'action' => 'stats'],
    '/chores/my-chores' => ['controller' => 'Chore', 'action' => 'myChores'],
    '/chores/store' => ['controller' => 'Chore', 'action' => 'store', 'method' => 'POST'],
    '/chores/{id}' => ['controller' => 'Chore', 'action' => 'show'],
    '/chores/{id}/update' => ['controller' => 'Chore', 'action' => 'update', 'method' => 'POST'],
    '/chores/{id}/delete' => ['controller' => 'Chore', 'action' => 'delete', 'method' => 'POST'],
    '/chores/completion/{completionId}/verify' => ['controller' => 'Chore', 'action' => 'verifyCompletion', 'method' => 'POST'],

    // Comments (for transactions, budgets, etc.)
    '/comments/{entityType}/{entityId}' => ['controller' => 'Comment', 'action' => 'index'],
    '/comments/store' => ['controller' => 'Comment', 'action' => 'store', 'method' => 'POST'],
    '/comments/{id}/update' => ['controller' => 'Comment', 'action' => 'update', 'method' => 'POST'],
    '/comments/{id}/delete' => ['controller' => 'Comment', 'action' => 'delete', 'method' => 'POST'],

    // Auth Routes
    '/login' => ['controller' => 'Auth', 'action' => 'login'],
    '/logout' => ['controller' => 'Auth', 'action' => 'logout'],
    '/register' => ['controller' => 'Auth', 'action' => 'register'],
    '/forgot-password' => ['controller' => 'Auth', 'action' => 'forgotPassword'],

    // Settings
    '/settings' => ['controller' => 'Settings', 'action' => 'index'],
    '/settings/profile' => ['controller' => 'Settings', 'action' => 'profile'],
    '/settings/security' => ['controller' => 'Settings', 'action' => 'security'],
    '/settings/notifications' => ['controller' => 'Settings', 'action' => 'notifications'],
    '/settings/integrations' => ['controller' => 'Settings', 'action' => 'integrations'],
];
