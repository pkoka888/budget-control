/**
 * Transaction Splits UI Controller
 * Manages transaction splitting with real-time calculation and validation
 */

class TransactionSplitsUI {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.originalAmount = parseFloat(document.getElementById('original-amount')?.value || 0);
        this.splitIndex = 0;
        this.categories = [];

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCategories();
        this.updateSummary();

        // Count existing splits
        const existingSplits = document.querySelectorAll('.split-row');
        this.splitIndex = existingSplits.length;
    }

    bindEvents() {
        // Add split button
        document.getElementById('add-split-btn')?.addEventListener('click', () => this.addSplitRow());

        // Form submission
        document.getElementById('splits-form')?.addEventListener('submit', (e) => this.saveSplits(e));

        // Listen for amount changes on existing splits
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('split-amount')) {
                this.updateSummary();
            }
        });

        // Remove split buttons (delegated)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.remove-split-btn')) {
                e.preventDefault();
                const row = e.target.closest('.split-row');
                this.removeSplitRow(row);
            }
        });
    }

    async loadCategories() {
        try {
            const response = await fetch('/api/v1/categories');
            if (!response.ok) return;

            const data = await response.json();
            this.categories = data.categories || [];

            // Populate existing selects
            this.updateCategorySelects();

        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    updateCategorySelects() {
        const selects = document.querySelectorAll('.split-category');

        selects.forEach(select => {
            const currentValue = select.value;

            select.innerHTML = '<option value="">Vyberte kategorii...</option>' +
                this.categories.map(cat =>
                    `<option value="${cat.id}">${this.escapeHtml(cat.name)}</option>`
                ).join('');

            // Restore selected value
            if (currentValue) {
                select.value = currentValue;
            }
        });
    }

    addSplitRow() {
        const container = document.getElementById('splits-container');
        const index = this.splitIndex++;

        const row = document.createElement('div');
        row.className = 'split-row';
        row.dataset.splitIndex = index;

        row.innerHTML = `
            <div class="grid grid-cols-12 gap-3 items-start">
                <div class="col-span-12 md:col-span-6">
                    <label class="form-label form-label-required">Kategorie</label>
                    <select name="splits[${index}][category_id]" class="form-input split-category" required aria-required="true">
                        <option value="">Vyberte kategorii...</option>
                        ${this.categories.map(cat =>
                            `<option value="${cat.id}">${this.escapeHtml(cat.name)}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="col-span-10 md:col-span-4">
                    <label class="form-label form-label-required">Částka</label>
                    <input type="number" name="splits[${index}][amount]" class="form-input split-amount"
                           required aria-required="true" min="0.01" step="0.01" placeholder="0.00">
                </div>
                <div class="col-span-2 md:col-span-2 flex items-end">
                    <button type="button" class="btn btn-danger btn-sm w-full remove-split-btn" aria-label="Odstranit řádek">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
                <div class="col-span-12">
                    <label class="form-label">Poznámka</label>
                    <input type="text" name="splits[${index}][description]" class="form-input" placeholder="Volitelné...">
                </div>
            </div>
        `;

        container.appendChild(row);

        // Add event listener for amount input
        const amountInput = row.querySelector('.split-amount');
        amountInput.addEventListener('input', () => this.updateSummary());

        this.updateRemoveButtons();
        this.updateSummary();

        // Focus on new category select
        row.querySelector('.split-category').focus();
    }

    removeSplitRow(row) {
        const rows = document.querySelectorAll('.split-row');

        // Don't allow removing if only one row
        if (rows.length <= 1) {
            this.showAlert('Musí existovat alespoň jedno rozdělení', 'warning');
            return;
        }

        row.remove();
        this.updateRemoveButtons();
        this.updateSummary();
    }

    updateRemoveButtons() {
        const rows = document.querySelectorAll('.split-row');
        const buttons = document.querySelectorAll('.remove-split-btn');

        // Disable remove button if only one row
        buttons.forEach(btn => {
            btn.disabled = rows.length <= 1;
        });
    }

    updateSummary() {
        const amounts = Array.from(document.querySelectorAll('.split-amount'))
            .map(input => parseFloat(input.value) || 0);

        const allocated = amounts.reduce((sum, val) => sum + val, 0);
        const remaining = this.originalAmount - allocated;
        const percentage = this.originalAmount > 0 ? (allocated / this.originalAmount) * 100 : 0;

        // Update display
        document.getElementById('allocated-amount').textContent = this.formatAmount(allocated);
        document.getElementById('remaining-amount').textContent = this.formatAmount(remaining);

        // Update progress bar
        const progressBar = document.getElementById('progress-bar');
        if (progressBar) {
            progressBar.style.width = `${Math.min(percentage, 100)}%`;
            progressBar.setAttribute('aria-valuenow', Math.round(percentage));

            // Change color based on status
            if (Math.abs(remaining) < 0.01) {
                // Perfect match
                progressBar.className = 'bg-green-600 h-2.5 rounded-full transition-all duration-300';
            } else if (percentage > 100) {
                // Over allocated
                progressBar.className = 'bg-red-600 h-2.5 rounded-full transition-all duration-300';
            } else {
                // Under allocated
                progressBar.className = 'bg-primary-600 h-2.5 rounded-full transition-all duration-300';
            }
        }

        // Show validation message
        this.showValidationMessage(allocated, remaining);

        // Enable/disable save button
        const saveBtn = document.getElementById('save-btn');
        if (saveBtn) {
            const isValid = Math.abs(remaining) < 0.01 && amounts.length > 0;
            saveBtn.disabled = !isValid;
        }
    }

    showValidationMessage(allocated, remaining) {
        const container = document.getElementById('validation-messages');
        if (!container) return;

        if (Math.abs(remaining) < 0.01) {
            container.innerHTML = `
                <div class="bg-green-50 border-l-4 border-green-400 p-4" role="status">
                    <div class="flex">
                        <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <p class="text-sm text-green-700">Celková částka souhlasí! Můžete uložit rozdělení.</p>
                    </div>
                </div>
            `;
        } else if (remaining < -0.01) {
            container.innerHTML = `
                <div class="bg-red-50 border-l-4 border-red-400 p-4" role="alert">
                    <div class="flex">
                        <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-sm text-red-700">Překročili jste původní částku o ${this.formatAmount(Math.abs(remaining))}!</p>
                    </div>
                </div>
            `;
        } else if (remaining > 0.01) {
            container.innerHTML = `
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4" role="alert">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-sm text-yellow-700">Ještě zbývá rozdělit ${this.formatAmount(remaining)}</p>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = '';
        }
    }

    async saveSplits(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const transactionId = formData.get('transaction_id');

        // Collect splits
        const splits = [];
        let index = 0;

        while (formData.has(`splits[${index}][category_id]`)) {
            const categoryId = formData.get(`splits[${index}][category_id]`);
            const amount = formData.get(`splits[${index}][amount]`);
            const description = formData.get(`splits[${index}][description]`);

            if (categoryId && amount) {
                splits.push({
                    category_id: parseInt(categoryId),
                    amount: parseFloat(amount),
                    description: description || ''
                });
            }

            index++;
        }

        // Validate
        if (splits.length === 0) {
            this.showAlert('Přidejte alespoň jedno rozdělení', 'error');
            return;
        }

        const total = splits.reduce((sum, s) => sum + s.amount, 0);
        if (Math.abs(total - this.originalAmount) > 0.01) {
            this.showAlert('Součet rozdělení neodpovídá původní částce', 'error');
            return;
        }

        this.showLoading('save-text', 'save-loading', true);

        try {
            const response = await fetch(`/transactions/${transactionId}/split`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ splits })
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Failed to save splits');
            }

            this.showAlert('Rozdělení transakce bylo uloženo', 'success');

            // Redirect after delay
            setTimeout(() => {
                window.location.href = '/transactions';
            }, 1500);

        } catch (error) {
            this.showAlert(error.message, 'error');
            this.showLoading('save-text', 'save-loading', false);
        }
    }

    formatAmount(amount) {
        return new Intl.NumberFormat('cs-CZ', {
            style: 'currency',
            currency: 'CZK',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    showAlert(message, type = 'info') {
        const container = document.getElementById('alert-container');
        if (!container) return;

        const alertClass = {
            success: 'alert-success',
            error: 'alert-error',
            warning: 'alert-warning',
            info: 'alert-info'
        }[type] || 'alert-info';

        const icon = {
            success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>',
            error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
            warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
            info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
        }[type];

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

// Initialize if transaction data exists
if (window.transactionData) {
    let splitsUI;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            splitsUI = new TransactionSplitsUI();
        });
    } else {
        splitsUI = new TransactionSplitsUI();
    }
}
