/**
 * Recurring Transactions UI Controller
 * Manages recurring transaction operations and calendar preview
 */

class RecurringTransactionsUI {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.currentTab = 'active';
        this.recurringTransactions = [];

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadRecurringTransactions();
        this.loadAccounts();
        this.loadCategories();
        this.updateRecurrencePreview();
    }

    bindEvents() {
        // Tab switching
        document.getElementById('tab-active')?.addEventListener('click', () => this.switchTab('active'));
        document.getElementById('tab-inactive')?.addEventListener('click', () => this.switchTab('inactive'));
        document.getElementById('tab-upcoming')?.addEventListener('click', () => this.switchTab('upcoming'));

        // Add button
        document.getElementById('add-recurring-btn')?.addEventListener('click', () => this.showAddModal());

        // Form submission
        document.getElementById('recurring-form')?.addEventListener('submit', (e) => this.saveRecurring(e));

        // Form changes for preview
        ['frequency', 'next-due-date', 'amount'].forEach(id => {
            document.getElementById(id)?.addEventListener('change', () => this.updateRecurrencePreview());
        });

        // Modal close
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', () => this.closeModal('recurring-modal'));
        });
    }

    async loadRecurringTransactions() {
        try {
            const response = await fetch('/api/transactions/recurring', {
                headers: { 'X-CSRF-Token': this.csrfToken }
            });

            if (!response.ok) throw new Error('Failed to load recurring transactions');

            const data = await response.json();
            this.recurringTransactions = data.transactions || [];

            this.renderTransactions();
            this.updateStatistics();

        } catch (error) {
            this.showAlert('Nepodařilo se načíst opakující se transakce', 'error');
        }
    }

    async loadAccounts() {
        try {
            const response = await fetch('/api/v1/accounts');
            if (!response.ok) return;

            const data = await response.json();
            const select = document.getElementById('account-id');

            if (select && data.accounts) {
                select.innerHTML = '<option value="">Vyberte účet...</option>' +
                    data.accounts.map(acc =>
                        `<option value="${acc.id}">${this.escapeHtml(acc.name)}</option>`
                    ).join('');
            }
        } catch (error) {
            console.error('Failed to load accounts:', error);
        }
    }

    async loadCategories() {
        try {
            const response = await fetch('/api/v1/categories');
            if (!response.ok) return;

            const data = await response.json();
            const select = document.getElementById('category-id');

            if (select && data.categories) {
                select.innerHTML = '<option value="">Vyberte kategorii...</option>' +
                    data.categories.map(cat =>
                        `<option value="${cat.id}">${this.escapeHtml(cat.name)}</option>`
                    ).join('');
            }
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    renderTransactions() {
        const activeList = document.getElementById('active-list');
        const inactiveList = document.getElementById('inactive-list');
        const upcomingCalendar = document.getElementById('upcoming-calendar');

        const active = this.recurringTransactions.filter(t => t.is_active);
        const inactive = this.recurringTransactions.filter(t => !t.is_active);

        if (activeList) {
            activeList.innerHTML = active.length > 0
                ? active.map(t => this.renderTransactionCard(t)).join('')
                : '<p class="text-center text-slate-gray-600 py-8">Žádné aktivní opakující se transakce</p>';
        }

        if (inactiveList) {
            inactiveList.innerHTML = inactive.length > 0
                ? inactive.map(t => this.renderTransactionCard(t)).join('')
                : '<p class="text-center text-slate-gray-600 py-8">Žádné neaktivní transakce</p>';
        }

        if (upcomingCalendar) {
            this.renderUpcomingCalendar(upcomingCalendar, active);
        }
    }

    renderTransactionCard(transaction) {
        const typeIcon = transaction.type === 'income'
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>';

        const typeColor = transaction.type === 'income' ? 'text-green-600' : 'text-red-600';
        const typeBg = transaction.type === 'income' ? 'bg-green-100' : 'bg-red-100';

        return `
            <div class="bg-slate-gray-50 rounded-lg p-4 hover:bg-slate-gray-100 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 ${typeBg} rounded-full flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 ${typeColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    ${typeIcon}
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-gray-900">${this.escapeHtml(transaction.description)}</h3>
                                <p class="text-sm text-slate-gray-600">${this.formatFrequency(transaction.frequency)}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-3 text-sm">
                            <div>
                                <span class="text-slate-gray-600">Částka:</span>
                                <span class="font-semibold ml-2">${this.formatAmount(transaction.amount)}</span>
                            </div>
                            <div>
                                <span class="text-slate-gray-600">Další datum:</span>
                                <span class="font-semibold ml-2">${this.formatDate(transaction.next_due_date)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2 ml-4">
                        <button type="button" onclick="recurringUI.editRecurring(${transaction.id})"
                                class="btn btn-secondary btn-sm" aria-label="Upravit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="recurringUI.deleteRecurring(${transaction.id})"
                                class="btn btn-danger btn-sm" aria-label="Smazat">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    renderUpcomingCalendar(container, activeTransactions) {
        const upcoming = [];
        const today = new Date();
        const endDate = new Date(today.getTime() + 90 * 24 * 60 * 60 * 1000); // Next 90 days

        activeTransactions.forEach(transaction => {
            let currentDate = new Date(transaction.next_due_date);

            while (currentDate <= endDate) {
                if (currentDate >= today) {
                    upcoming.push({
                        ...transaction,
                        dueDate: new Date(currentDate)
                    });
                }

                // Calculate next occurrence
                currentDate = this.calculateNextDate(currentDate, transaction.frequency);
            }
        });

        // Sort by date
        upcoming.sort((a, b) => a.dueDate - b.dueDate);

        // Group by month
        const byMonth = {};
        upcoming.forEach(item => {
            const monthKey = item.dueDate.toLocaleDateString('cs-CZ', { year: 'numeric', month: 'long' });
            if (!byMonth[monthKey]) byMonth[monthKey] = [];
            byMonth[monthKey].push(item);
        });

        container.innerHTML = Object.keys(byMonth).length > 0
            ? Object.entries(byMonth).map(([month, items]) => `
                <div class="mb-6">
                    <h3 class="font-semibold text-slate-gray-900 mb-3">${month}</h3>
                    <div class="space-y-2">
                        ${items.map(item => `
                            <div class="flex items-center justify-between bg-slate-gray-50 p-3 rounded">
                                <div class="flex items-center flex-1">
                                    <span class="text-sm font-medium text-slate-gray-700 w-16">
                                        ${item.dueDate.getDate()}. ${item.dueDate.toLocaleDateString('cs-CZ', { month: 'short' })}
                                    </span>
                                    <span class="text-sm text-slate-gray-900 flex-1">${this.escapeHtml(item.description)}</span>
                                </div>
                                <span class="text-sm font-semibold ${item.type === 'income' ? 'text-green-600' : 'text-red-600'}">
                                    ${this.formatAmount(item.amount)}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('')
            : '<p class="text-center text-slate-gray-600 py-8">Žádné nadcházející transakce</p>';
    }

    calculateNextDate(date, frequency) {
        const next = new Date(date);

        switch (frequency) {
            case 'daily': next.setDate(next.getDate() + 1); break;
            case 'weekly': next.setDate(next.getDate() + 7); break;
            case 'bi-weekly': next.setDate(next.getDate() + 14); break;
            case 'monthly': next.setMonth(next.getMonth() + 1); break;
            case 'quarterly': next.setMonth(next.getMonth() + 3); break;
            case 'yearly': next.setFullYear(next.getFullYear() + 1); break;
        }

        return next;
    }

    updateStatistics() {
        const active = this.recurringTransactions.filter(t => t.is_active);

        const monthlyIncome = active
            .filter(t => t.type === 'income')
            .reduce((sum, t) => sum + this.getMonthlyAmount(t.amount, t.frequency), 0);

        const monthlyExpenses = active
            .filter(t => t.type === 'expense')
            .reduce((sum, t) => sum + Math.abs(this.getMonthlyAmount(t.amount, t.frequency)), 0);

        document.getElementById('monthly-income').textContent = this.formatAmount(monthlyIncome);
        document.getElementById('monthly-expenses').textContent = this.formatAmount(monthlyExpenses);
        document.getElementById('active-count').textContent = active.length;
    }

    getMonthlyAmount(amount, frequency) {
        const multipliers = {
            'daily': 30,
            'weekly': 4.33,
            'bi-weekly': 2.17,
            'monthly': 1,
            'quarterly': 0.33,
            'yearly': 0.083
        };

        return amount * (multipliers[frequency] || 1);
    }

    switchTab(tab) {
        this.currentTab = tab;

        // Update tab buttons
        ['active', 'inactive', 'upcoming'].forEach(t => {
            const btn = document.getElementById(`tab-${t}`);
            const panel = document.getElementById(`panel-${t}`);

            if (t === tab) {
                btn?.classList.add('active');
                btn?.setAttribute('aria-selected', 'true');
                panel?.classList.remove('hidden');
            } else {
                btn?.classList.remove('active');
                btn?.setAttribute('aria-selected', 'false');
                panel?.classList.add('hidden');
            }
        });
    }

    showAddModal() {
        document.getElementById('recurring-modal-title').textContent = 'Přidat Opakující se Transakci';
        document.getElementById('recurring-form').reset();
        document.getElementById('recurring-id').value = '';

        // Set default date to today
        document.getElementById('next-due-date').valueAsDate = new Date();

        this.showModal('recurring-modal');
    }

    async editRecurring(id) {
        const transaction = this.recurringTransactions.find(t => t.id === id);
        if (!transaction) return;

        document.getElementById('recurring-modal-title').textContent = 'Upravit Opakující se Transakci';
        document.getElementById('recurring-id').value = transaction.id;
        document.getElementById('description').value = transaction.description;
        document.getElementById('type').value = transaction.type;
        document.getElementById('amount').value = Math.abs(transaction.amount);
        document.getElementById('frequency').value = transaction.frequency;
        document.getElementById('next-due-date').value = transaction.next_due_date;
        document.getElementById('account-id').value = transaction.account_id;
        document.getElementById('category-id').value = transaction.category_id || '';
        document.getElementById('is-active').checked = transaction.is_active == 1;

        this.updateRecurrencePreview();
        this.showModal('recurring-modal');
    }

    async saveRecurring(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const id = formData.get('id');
        const data = {
            description: formData.get('description'),
            type: formData.get('type'),
            amount: parseFloat(formData.get('amount')) * (formData.get('type') === 'expense' ? -1 : 1),
            frequency: formData.get('frequency'),
            next_due_date: formData.get('next_due_date'),
            account_id: parseInt(formData.get('account_id')),
            category_id: formData.get('category_id') ? parseInt(formData.get('category_id')) : null,
            is_active: formData.get('is_active') ? 1 : 0
        };

        this.showLoading('save-text', 'save-loading', true);

        try {
            const url = id ? `/transactions/recurring/${id}/update` : '/transactions/recurring/create';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) throw new Error('Failed to save');

            this.showAlert(id ? 'Transakce aktualizována' : 'Transakce vytvořena', 'success');
            this.closeModal('recurring-modal');
            await this.loadRecurringTransactions();

        } catch (error) {
            this.showAlert('Nepodařilo se uložit transakci', 'error');
        } finally {
            this.showLoading('save-text', 'save-loading', false);
        }
    }

    async deleteRecurring(id) {
        if (!confirm('Opravdu chcete smazat tuto opakující se transakci?')) return;

        try {
            const response = await fetch(`/transactions/recurring/${id}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                }
            });

            if (!response.ok) throw new Error('Failed to delete');

            this.showAlert('Transakce smazána', 'success');
            await this.loadRecurringTransactions();

        } catch (error) {
            this.showAlert('Nepodařilo se smazat transakci', 'error');
        }
    }

    updateRecurrencePreview() {
        const frequency = document.getElementById('frequency')?.value;
        const nextDate = document.getElementById('next-due-date')?.value;
        const amount = document.getElementById('amount')?.value;

        if (!frequency || !nextDate || !amount) {
            document.getElementById('recurrence-preview').textContent = '-';
            return;
        }

        const frequencyText = this.formatFrequency(frequency);
        const formattedAmount = this.formatAmount(parseFloat(amount));
        const date = new Date(nextDate);
        const dateText = date.toLocaleDateString('cs-CZ');

        document.getElementById('recurrence-preview').textContent =
            `${formattedAmount} ${frequencyText}, další platba: ${dateText}`;
    }

    formatFrequency(frequency) {
        const map = {
            'daily': 'denně',
            'weekly': 'týdně',
            'bi-weekly': 'každé 2 týdny',
            'monthly': 'měsíčně',
            'quarterly': 'čtvrtletně',
            'yearly': 'ročně'
        };
        return map[frequency] || frequency;
    }

    formatAmount(amount) {
        return new Intl.NumberFormat('cs-CZ', {
            style: 'currency',
            currency: 'CZK'
        }).format(amount);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('cs-CZ');
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }
    }

    showAlert(message, type = 'info') {
        const container = document.getElementById('alert-container');
        if (!container) return;

        const alertClass = type === 'success' ? 'alert-success' : type === 'error' ? 'alert-error' : 'alert-info';
        const icon = type === 'success'
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';

        container.innerHTML = `
            <div class="alert ${alertClass} animate-slide-in-down" role="alert">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${icon}
                </svg>
                ${this.escapeHtml(message)}
            </div>
        `;

        setTimeout(() => container.innerHTML = '', 5000);
    }

    showLoading(textId, loadingId, loading) {
        const textEl = document.getElementById(textId);
        const loadingEl = document.getElementById(loadingId);

        if (loading) {
            textEl?.classList.add('hidden');
            loadingEl?.classList.remove('hidden');
        } else {
            textEl?.classList.remove('hidden');
            loadingEl?.classList.add('hidden');
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize
let recurringUI;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        recurringUI = new RecurringTransactionsUI();
    });
} else {
    recurringUI = new RecurringTransactionsUI();
}
