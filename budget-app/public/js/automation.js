/**
 * Automation UI Controller
 * Handles automation rules creation, testing, and management
 */

class AutomationUI {
    constructor() {
        this.automationRules = [];
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        this.init();
    }

    init() {
        this.attachEventListeners();
        this.loadAutomationRules();
    }

    attachEventListeners() {
        // Create new rule button
        document.getElementById('create-rule-btn')?.addEventListener('click', () => {
            this.showRuleModal();
        });

        // Rule form submission
        document.getElementById('automation-rule-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveRule();
        });

        // Rule type selection
        document.getElementById('rule-action-type')?.addEventListener('change', (e) => {
            this.updateRuleForm(e.target.value);
        });

        // Trigger type selection
        document.getElementById('rule-trigger-type')?.addEventListener('change', (e) => {
            this.updateTriggerForm(e.target.value);
        });

        // Test rule button
        document.getElementById('test-rule-btn')?.addEventListener('click', () => {
            this.testRule();
        });

        // Rule actions (delegated)
        document.addEventListener('click', (e) => {
            if (e.target.matches('.edit-rule-btn')) {
                e.preventDefault();
                const ruleId = e.target.dataset.ruleId;
                this.editRule(ruleId);
            }

            if (e.target.matches('.delete-rule-btn')) {
                e.preventDefault();
                const ruleId = e.target.dataset.ruleId;
                this.deleteRule(ruleId);
            }

            if (e.target.matches('.toggle-rule-btn')) {
                e.preventDefault();
                const ruleId = e.target.dataset.ruleId;
                this.toggleRule(ruleId);
            }

            if (e.target.matches('.test-single-rule-btn')) {
                e.preventDefault();
                const ruleId = e.target.dataset.ruleId;
                this.testSingleRule(ruleId);
            }
        });

        // Execute all rules button
        document.getElementById('execute-all-rules-btn')?.addEventListener('click', () => {
            this.executeAllRules();
        });

        // Close modal
        document.getElementById('close-rule-modal')?.addEventListener('click', () => {
            this.hideRuleModal();
        });
    }

    async loadAutomationRules() {
        this.showLoadingState();

        try {
            const response = await fetch('/api/automation/actions');
            const data = await response.json();

            if (data.success) {
                this.automationRules = data.actions || [];
                this.renderRules();
            } else {
                this.showAlert('Failed to load automation rules', 'error');
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error loading automation rules:', error);
            this.showAlert('Failed to load automation rules', 'error');
            this.hideLoadingState();
        }
    }

    renderRules() {
        const container = document.getElementById('automation-rules-container');
        if (!container) return;

        if (this.automationRules.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">🤖</div>
                    <p class="text-slate-gray-600 mb-4">No automation rules created yet</p>
                    <button class="btn btn-primary" onclick="automationUI.showRuleModal()">
                        Create Your First Rule
                    </button>
                </div>
            `;
            return;
        }

        const rulesByType = this.groupRulesByType();

        container.innerHTML = Object.entries(rulesByType).map(([type, rules]) => `
            <div class="mb-8">
                <h3 class="text-lg font-semibold mb-4">${this.formatRuleType(type)}</h3>
                <div class="space-y-4">
                    ${rules.map(rule => this.renderRuleCard(rule)).join('')}
                </div>
            </div>
        `).join('');

        this.updateStatistics();
    }

    groupRulesByType() {
        const grouped = {};

        this.automationRules.forEach(rule => {
            const type = rule.action_type || 'other';
            if (!grouped[type]) {
                grouped[type] = [];
            }
            grouped[type].push(rule);
        });

        return grouped;
    }

    renderRuleCard(rule) {
        const isActive = rule.is_active === 1 || rule.is_active === true;
        const statusClass = isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600';
        const statusText = isActive ? 'Active' : 'Inactive';

        const triggerCondition = JSON.parse(rule.trigger_condition || '{}');
        const actionData = JSON.parse(rule.action_data || '{}');

        return `
            <div class="bg-white rounded-lg shadow-md p-6 rule-card ${!isActive ? 'opacity-60' : ''}">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <h4 class="text-lg font-semibold text-slate-gray-900">${this.getRuleName(rule)}</h4>
                        <p class="text-sm text-slate-gray-600 mt-1">${this.getRuleDescription(rule)}</p>
                    </div>
                    <span class="px-3 py-1 ${statusClass} text-sm rounded">${statusText}</span>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-xs font-medium text-slate-gray-500 mb-1">TRIGGER</p>
                        <p class="text-sm text-slate-gray-700">${this.formatTriggerType(rule.trigger_type)}</p>
                        ${Object.keys(triggerCondition).length > 0 ? `
                            <p class="text-xs text-slate-gray-500 mt-1">${this.formatTriggerCondition(triggerCondition)}</p>
                        ` : ''}
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-gray-500 mb-1">ACTION</p>
                        <p class="text-sm text-slate-gray-700">${this.formatActionType(rule.action_type)}</p>
                        ${Object.keys(actionData).length > 0 ? `
                            <p class="text-xs text-slate-gray-500 mt-1">${this.formatActionData(actionData)}</p>
                        ` : ''}
                    </div>
                </div>

                ${rule.last_executed_at ? `
                    <p class="text-xs text-slate-gray-500 mb-4">
                        Last executed: ${new Date(rule.last_executed_at).toLocaleString('cs-CZ')}
                    </p>
                ` : ''}

                <div class="flex gap-2">
                    <button class="test-single-rule-btn btn btn-secondary btn-sm flex-1"
                            data-rule-id="${rule.id}">
                        Test Rule
                    </button>
                    <button class="toggle-rule-btn btn ${isActive ? 'btn-secondary' : 'btn-primary'} btn-sm flex-1"
                            data-rule-id="${rule.id}">
                        ${isActive ? 'Deactivate' : 'Activate'}
                    </button>
                    <button class="edit-rule-btn btn btn-secondary btn-sm"
                            data-rule-id="${rule.id}">
                        Edit
                    </button>
                    <button class="delete-rule-btn btn btn-danger btn-sm"
                            data-rule-id="${rule.id}">
                        Delete
                    </button>
                </div>
            </div>
        `;
    }

    showRuleModal(rule = null) {
        const modal = document.getElementById('rule-modal');
        if (!modal) return;

        // Reset form
        document.getElementById('automation-rule-form')?.reset();
        document.getElementById('rule-id')?.setAttribute('value', rule ? rule.id : '');

        // Populate form if editing
        if (rule) {
            document.getElementById('rule-action-type').value = rule.action_type;
            document.getElementById('rule-trigger-type').value = rule.trigger_type;
            this.updateRuleForm(rule.action_type);
            this.updateTriggerForm(rule.trigger_type);

            // Populate action and trigger data
            const actionData = JSON.parse(rule.action_data || '{}');
            const triggerCondition = JSON.parse(rule.trigger_condition || '{}');

            Object.entries(actionData).forEach(([key, value]) => {
                const input = document.getElementById(`action-${key}`);
                if (input) input.value = value;
            });

            Object.entries(triggerCondition).forEach(([key, value]) => {
                const input = document.getElementById(`trigger-${key}`);
                if (input) input.value = value;
            });
        }

        modal.classList.remove('hidden');
    }

    hideRuleModal() {
        const modal = document.getElementById('rule-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    updateRuleForm(actionType) {
        const container = document.getElementById('action-config');
        if (!container) return;

        let formFields = '';

        switch (actionType) {
            case 'auto_categorize':
                formFields = `
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select id="action-category_id" class="form-input" required>
                            <option value="">Select category...</option>
                            <!-- Categories will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pattern (regex or keyword)</label>
                        <input type="text" id="action-pattern" class="form-input" required
                               placeholder="e.g., Tesco, Lidl, Kaufland">
                    </div>
                `;
                break;

            case 'budget_alert':
                formFields = `
                    <div class="form-group">
                        <label class="form-label">Alert Threshold (%)</label>
                        <input type="number" id="action-threshold" class="form-input" required
                               min="0" max="100" value="80">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notification Method</label>
                        <select id="action-notification_method" class="form-input">
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="push">Push Notification</option>
                        </select>
                    </div>
                `;
                break;

            case 'recurring_create':
                formFields = `
                    <div class="form-group">
                        <label class="form-label">Account</label>
                        <select id="action-account_id" class="form-input" required>
                            <option value="">Select account...</option>
                            <!-- Accounts will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount</label>
                        <input type="number" id="action-amount" class="form-input" required step="0.01">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Frequency</label>
                        <select id="action-frequency" class="form-input">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                `;
                break;

            default:
                formFields = '<p class="text-slate-gray-600">Select an action type to configure</p>';
        }

        container.innerHTML = formFields;
    }

    updateTriggerForm(triggerType) {
        const container = document.getElementById('trigger-config');
        if (!container) return;

        let formFields = '';

        switch (triggerType) {
            case 'schedule':
                formFields = `
                    <div class="form-group">
                        <label class="form-label">Schedule Type</label>
                        <select id="trigger-schedule_type" class="form-input">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <input type="time" id="trigger-time" class="form-input" value="09:00">
                    </div>
                `;
                break;

            case 'transaction_create':
                formFields = `
                    <div class="form-group">
                        <label class="form-label">Transaction Type</label>
                        <select id="trigger-transaction_type" class="form-input">
                            <option value="any">Any</option>
                            <option value="income">Income Only</option>
                            <option value="expense">Expense Only</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Min Amount (optional)</label>
                        <input type="number" id="trigger-min_amount" class="form-input" step="0.01">
                    </div>
                `;
                break;

            case 'budget_threshold':
                formFields = `
                    <div class="form-group">
                        <label class="form-label">Threshold (%)</label>
                        <input type="number" id="trigger-threshold" class="form-input"
                               min="0" max="100" value="80" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Budget Category</label>
                        <select id="trigger-category_id" class="form-input">
                            <option value="">Any category</option>
                            <!-- Categories will be loaded dynamically -->
                        </select>
                    </div>
                `;
                break;

            default:
                formFields = '<p class="text-slate-gray-600">Select a trigger type to configure</p>';
        }

        container.innerHTML = formFields;
    }

    async saveRule() {
        const form = document.getElementById('automation-rule-form');
        const formData = new FormData(form);

        const ruleId = document.getElementById('rule-id')?.value;
        const isEdit = !!ruleId;

        // Collect action data
        const actionData = {};
        form.querySelectorAll('[id^="action-"]').forEach(input => {
            const key = input.id.replace('action-', '');
            actionData[key] = input.value;
        });

        // Collect trigger condition
        const triggerCondition = {};
        form.querySelectorAll('[id^="trigger-"]').forEach(input => {
            const key = input.id.replace('trigger-', '');
            triggerCondition[key] = input.value;
        });

        const ruleData = {
            action_type: formData.get('action_type'),
            trigger_type: formData.get('trigger_type'),
            trigger_condition: triggerCondition,
            action_data: actionData
        };

        try {
            const url = isEdit ? `/api/automation/actions/${ruleId}` : '/api/automation/actions';
            const method = isEdit ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(ruleData)
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert(isEdit ? 'Rule updated successfully!' : 'Rule created successfully!', 'success');
                this.hideRuleModal();
                await this.loadAutomationRules();
            } else {
                this.showAlert('Failed to save rule', 'error');
            }

        } catch (error) {
            console.error('Error saving rule:', error);
            this.showAlert('Failed to save rule', 'error');
        }
    }

    async editRule(ruleId) {
        const rule = this.automationRules.find(r => r.id == ruleId);
        if (rule) {
            this.showRuleModal(rule);
        }
    }

    async deleteRule(ruleId) {
        if (!confirm('Are you sure you want to delete this automation rule?')) {
            return;
        }

        try {
            const response = await fetch(`/api/automation/actions/${ruleId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('Rule deleted successfully', 'success');
                await this.loadAutomationRules();
            } else {
                this.showAlert('Failed to delete rule', 'error');
            }

        } catch (error) {
            console.error('Error deleting rule:', error);
            this.showAlert('Failed to delete rule', 'error');
        }
    }

    async toggleRule(ruleId) {
        const rule = this.automationRules.find(r => r.id == ruleId);
        if (!rule) return;

        const newStatus = rule.is_active ? 0 : 1;

        try {
            const response = await fetch(`/api/automation/actions/${ruleId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ is_active: newStatus })
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert(newStatus ? 'Rule activated' : 'Rule deactivated', 'success');
                await this.loadAutomationRules();
            } else {
                this.showAlert('Failed to toggle rule', 'error');
            }

        } catch (error) {
            console.error('Error toggling rule:', error);
            this.showAlert('Failed to toggle rule', 'error');
        }
    }

    async testSingleRule(ruleId) {
        this.showAlert('Testing rule...', 'info');

        try {
            const response = await fetch(`/api/automation/test/${ruleId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert(`Test result: ${data.result || 'Rule executed successfully'}`, 'success');
            } else {
                this.showAlert('Test failed: ' + (data.error || 'Unknown error'), 'error');
            }

        } catch (error) {
            console.error('Error testing rule:', error);
            this.showAlert('Failed to test rule', 'error');
        }
    }

    async testRule() {
        // Test current rule form without saving
        this.showAlert('Test functionality coming soon', 'info');
    }

    async executeAllRules() {
        if (!confirm('Execute all active automation rules now?')) {
            return;
        }

        this.showLoadingState('Executing rules...');

        try {
            const response = await fetch('/api/automation/execute', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            const data = await response.json();

            if (data.success) {
                const results = data.results || {};
                this.showAlert(`Executed ${results.success_count || 0} rules successfully`, 'success');
                await this.loadAutomationRules();
            } else {
                this.showAlert('Failed to execute rules', 'error');
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error executing rules:', error);
            this.showAlert('Failed to execute rules', 'error');
            this.hideLoadingState();
        }
    }

    updateStatistics() {
        const activeCount = this.automationRules.filter(r => r.is_active).length;
        const totalCount = this.automationRules.length;

        const activeCountEl = document.getElementById('active-rules-count');
        const totalCountEl = document.getElementById('total-rules-count');

        if (activeCountEl) activeCountEl.textContent = activeCount;
        if (totalCountEl) totalCountEl.textContent = totalCount;
    }

    getRuleName(rule) {
        return rule.name || `${this.formatActionType(rule.action_type)} on ${this.formatTriggerType(rule.trigger_type)}`;
    }

    getRuleDescription(rule) {
        const trigger = this.formatTriggerType(rule.trigger_type);
        const action = this.formatActionType(rule.action_type);
        return `When ${trigger.toLowerCase()}, ${action.toLowerCase()}`;
    }

    formatRuleType(type) {
        const types = {
            auto_categorize: 'Automatic Categorization',
            budget_alert: 'Budget Alerts',
            recurring_create: 'Recurring Transactions',
            other: 'Other Rules'
        };
        return types[type] || type;
    }

    formatTriggerType(type) {
        const triggers = {
            schedule: 'On Schedule',
            transaction_create: 'New Transaction Created',
            budget_threshold: 'Budget Threshold Reached'
        };
        return triggers[type] || type;
    }

    formatActionType(type) {
        const actions = {
            auto_categorize: 'Auto-categorize Transaction',
            budget_alert: 'Send Budget Alert',
            recurring_create: 'Create Recurring Transaction'
        };
        return actions[type] || type;
    }

    formatTriggerCondition(condition) {
        return Object.entries(condition).map(([key, value]) => `${key}: ${value}`).join(', ');
    }

    formatActionData(data) {
        return Object.entries(data).map(([key, value]) => `${key}: ${value}`).join(', ');
    }

    showLoadingState(message = 'Loading...') {
        const loader = document.getElementById('automation-loader');
        if (loader) {
            loader.textContent = message;
            loader.classList.remove('hidden');
        }
    }

    hideLoadingState() {
        const loader = document.getElementById('automation-loader');
        if (loader) {
            loader.classList.add('hidden');
        }
    }

    showAlert(message, type = 'info') {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} fixed top-4 right-4 z-50 max-w-md`;
        alert.textContent = message;

        document.body.appendChild(alert);

        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.automationUI = new AutomationUI();
    });
} else {
    window.automationUI = new AutomationUI();
}
