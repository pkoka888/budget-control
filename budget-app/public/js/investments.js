/**
 * Investments UI Controller
 * Handles portfolio management, transactions, and performance tracking
 */

class InvestmentsUI {
    constructor() {
        this.investments = [];
        this.portfolioChart = null;
        this.performanceChart = null;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        this.init();
    }

    init() {
        this.attachEventListeners();
        this.loadPortfolio();
    }

    attachEventListeners() {
        // Add investment button
        document.getElementById('add-investment-btn')?.addEventListener('click', () => {
            this.showInvestmentModal();
        });

        // Investment form submission
        document.getElementById('investment-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveInvestment();
        });

        // Transaction form submission
        document.getElementById('transaction-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.recordTransaction();
        });

        // Investment actions (delegated)
        document.addEventListener('click', (e) => {
            if (e.target.matches('.edit-investment-btn')) {
                e.preventDefault();
                const investmentId = e.target.dataset.investmentId;
                this.editInvestment(investmentId);
            }

            if (e.target.matches('.delete-investment-btn')) {
                e.preventDefault();
                const investmentId = e.target.dataset.investmentId;
                this.deleteInvestment(investmentId);
            }

            if (e.target.matches('.record-transaction-btn')) {
                e.preventDefault();
                const investmentId = e.target.dataset.investmentId;
                this.showTransactionModal(investmentId);
            }

            if (e.target.matches('.view-history-btn')) {
                e.preventDefault();
                const investmentId = e.target.dataset.investmentId;
                this.showTransactionHistory(investmentId);
            }
        });

        // Update prices button
        document.getElementById('update-prices-btn')?.addEventListener('click', () => {
            this.updateAllPrices();
        });

        // Chart type toggle
        document.querySelectorAll('.chart-view-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                this.switchChartView(e.target.dataset.view);
            });
        });

        // Portfolio filters
        document.getElementById('investment-type-filter')?.addEventListener('change', (e) => {
            this.filterByType(e.target.value);
        });

        document.getElementById('account-filter')?.addEventListener('change', (e) => {
            this.filterByAccount(e.target.value);
        });
    }

    async loadPortfolio() {
        this.showLoadingState();

        try {
            const response = await fetch('/investments/portfolio');
            const html = await response.text();

            const container = document.getElementById('portfolio-container');
            if (container) {
                container.innerHTML = html;
            }

            // Load investments data for charts
            await this.loadInvestmentsData();

            this.hideLoadingState();

        } catch (error) {
            console.error('Error loading portfolio:', error);
            this.showAlert('Failed to load portfolio', 'error');
            this.hideLoadingState();
        }
    }

    async loadInvestmentsData() {
        try {
            const response = await fetch('/api/investments');
            const data = await response.json();

            if (data.success) {
                this.investments = data.investments || [];
                this.renderPortfolioChart();
                this.updateTotalValues();
            }

        } catch (error) {
            console.error('Error loading investments data:', error);
        }
    }

    renderPortfolioChart() {
        const ctx = document.getElementById('portfolio-chart')?.getContext('2d');
        if (!ctx) return;

        if (this.portfolioChart) {
            this.portfolioChart.destroy();
        }

        // Group by type
        const byType = {};
        this.investments.forEach(inv => {
            const type = inv.type || 'other';
            if (!byType[type]) {
                byType[type] = 0;
            }
            byType[type] += (inv.quantity * (inv.current_price || inv.purchase_price));
        });

        const colors = {
            stock: '#3B82F6',
            bond: '#10B981',
            etf: '#8B5CF6',
            mutual_fund: '#F59E0B',
            crypto: '#EF4444',
            real_estate: '#6366F1',
            other: '#6B7280'
        };

        this.portfolioChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(byType).map(type => this.formatInvestmentType(type)),
                datasets: [{
                    data: Object.values(byType),
                    backgroundColor: Object.keys(byType).map(type => colors[type] || colors.other)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${context.label}: ${this.formatCurrency(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    showInvestmentModal(investment = null) {
        const modal = document.getElementById('investment-modal');
        if (!modal) return;

        // Reset form
        document.getElementById('investment-form')?.reset();
        document.getElementById('investment-id')?.setAttribute('value', investment ? investment.id : '');

        // Populate form if editing
        if (investment) {
            document.getElementById('investment-symbol').value = investment.symbol;
            document.getElementById('investment-name').value = investment.name;
            document.getElementById('investment-type').value = investment.type;
            document.getElementById('investment-quantity').value = investment.quantity;
            document.getElementById('investment-purchase-price').value = investment.purchase_price;
            document.getElementById('investment-purchase-date').value = investment.purchase_date;
            document.getElementById('investment-account').value = investment.account_id;
        }

        modal.classList.remove('hidden');
    }

    hideInvestmentModal() {
        const modal = document.getElementById('investment-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    async saveInvestment() {
        const form = document.getElementById('investment-form');
        const formData = new FormData(form);

        const investmentId = document.getElementById('investment-id')?.value;
        const isEdit = !!investmentId;

        const data = {
            symbol: formData.get('symbol'),
            name: formData.get('name'),
            type: formData.get('type'),
            quantity: parseFloat(formData.get('quantity')),
            purchase_price: parseFloat(formData.get('purchase_price')),
            purchase_date: formData.get('purchase_date'),
            account_id: formData.get('account_id')
        };

        try {
            const url = isEdit ? `/investments/${investmentId}/update` : '/investments';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert(isEdit ? 'Investment updated!' : 'Investment added!', 'success');
                this.hideInvestmentModal();
                await this.loadPortfolio();
            } else {
                this.showAlert(result.error || 'Failed to save investment', 'error');
            }

        } catch (error) {
            console.error('Error saving investment:', error);
            this.showAlert('Failed to save investment', 'error');
        }
    }

    async editInvestment(investmentId) {
        const investment = this.investments.find(i => i.id == investmentId);
        if (investment) {
            this.showInvestmentModal(investment);
        }
    }

    async deleteInvestment(investmentId) {
        if (!confirm('Are you sure you want to delete this investment?')) {
            return;
        }

        try {
            const response = await fetch(`/investments/${investmentId}/delete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('Investment deleted', 'success');
                await this.loadPortfolio();
            } else {
                this.showAlert('Failed to delete investment', 'error');
            }

        } catch (error) {
            console.error('Error deleting investment:', error);
            this.showAlert('Failed to delete investment', 'error');
        }
    }

    showTransactionModal(investmentId) {
        const modal = document.getElementById('transaction-modal');
        if (!modal) return;

        document.getElementById('transaction-investment-id')?.setAttribute('value', investmentId);
        document.getElementById('transaction-form')?.reset();

        modal.classList.remove('hidden');
    }

    hideTransactionModal() {
        const modal = document.getElementById('transaction-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    async recordTransaction() {
        const form = document.getElementById('transaction-form');
        const formData = new FormData(form);

        const data = {
            investment_id: document.getElementById('transaction-investment-id').value,
            transaction_type: formData.get('transaction_type'),
            quantity: parseFloat(formData.get('quantity')),
            price: parseFloat(formData.get('price')),
            fees: parseFloat(formData.get('fees')) || 0,
            transaction_date: formData.get('transaction_date'),
            notes: formData.get('notes')
        };

        try {
            const response = await fetch('/investments/transactions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showAlert('Transaction recorded!', 'success');
                this.hideTransactionModal();
                await this.loadPortfolio();
            } else {
                this.showAlert('Failed to record transaction', 'error');
            }

        } catch (error) {
            console.error('Error recording transaction:', error);
            this.showAlert('Failed to record transaction', 'error');
        }
    }

    async showTransactionHistory(investmentId) {
        try {
            const response = await fetch(`/investments/transactions?investment_id=${investmentId}`);
            const data = await response.json();

            if (data.success) {
                this.renderTransactionHistory(data.transactions);
            }

        } catch (error) {
            console.error('Error loading transaction history:', error);
            this.showAlert('Failed to load transaction history', 'error');
        }
    }

    renderTransactionHistory(transactions) {
        const modal = document.getElementById('history-modal');
        if (!modal) return;

        const container = document.getElementById('history-content');
        if (!container) return;

        container.innerHTML = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Fees</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${transactions.map(txn => `
                            <tr>
                                <td class="px-4 py-3 text-sm">${txn.transaction_date}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs rounded ${this.getTransactionTypeClass(txn.transaction_type)}">
                                        ${txn.transaction_type}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">${txn.quantity}</td>
                                <td class="px-4 py-3 text-sm text-right">${this.formatCurrency(txn.price)}</td>
                                <td class="px-4 py-3 text-sm text-right">${this.formatCurrency(txn.fees)}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">
                                    ${this.formatCurrency(txn.quantity * txn.price + txn.fees)}
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        modal.classList.remove('hidden');
    }

    async updateAllPrices() {
        this.showLoadingState('Updating prices...');

        try {
            const response = await fetch('/investments/prices', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('Prices updated successfully!', 'success');
                await this.loadPortfolio();
            } else {
                this.showAlert('Failed to update prices', 'error');
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error updating prices:', error);
            this.showAlert('Failed to update prices', 'error');
            this.hideLoadingState();
        }
    }

    switchChartView(view) {
        // Switch between different chart views (allocation, performance, etc.)
        document.querySelectorAll('.chart-view-toggle').forEach(toggle => {
            toggle.classList.toggle('active', toggle.dataset.view === view);
        });

        if (view === 'allocation') {
            this.renderPortfolioChart();
        } else if (view === 'performance') {
            this.renderPerformanceChart();
        }
    }

    async renderPerformanceChart() {
        try {
            const response = await fetch('/investments/performance');
            const data = await response.json();

            if (!data.success) return;

            const ctx = document.getElementById('portfolio-chart')?.getContext('2d');
            if (!ctx) return;

            if (this.performanceChart) {
                this.performanceChart.destroy();
            }

            this.performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.performance.dates || [],
                    datasets: [{
                        label: 'Portfolio Value',
                        data: data.performance.values || [],
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) => 'Value: ' + this.formatCurrency(context.parsed.y)
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: (value) => this.formatCurrency(value)
                            }
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Error rendering performance chart:', error);
        }
    }

    filterByType(type) {
        const cards = document.querySelectorAll('.investment-card');

        cards.forEach(card => {
            if (!type || type === 'all' || card.dataset.investmentType === type) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    filterByAccount(accountId) {
        const cards = document.querySelectorAll('.investment-card');

        cards.forEach(card => {
            if (!accountId || accountId === 'all' || card.dataset.accountId === accountId) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    updateTotalValues() {
        let totalValue = 0;
        let totalCost = 0;
        let totalGain = 0;

        this.investments.forEach(inv => {
            const currentValue = inv.quantity * (inv.current_price || inv.purchase_price);
            const cost = inv.quantity * inv.purchase_price;

            totalValue += currentValue;
            totalCost += cost;
            totalGain += (currentValue - cost);
        });

        const totalValueEl = document.getElementById('total-portfolio-value');
        const totalGainEl = document.getElementById('total-portfolio-gain');
        const totalReturnEl = document.getElementById('total-portfolio-return');

        if (totalValueEl) totalValueEl.textContent = this.formatCurrency(totalValue);
        if (totalGainEl) {
            totalGainEl.textContent = this.formatCurrency(totalGain);
            totalGainEl.className = totalGain >= 0 ? 'text-green-600' : 'text-red-600';
        }
        if (totalReturnEl) {
            const returnPct = totalCost > 0 ? (totalGain / totalCost * 100) : 0;
            totalReturnEl.textContent = `${returnPct.toFixed(2)}%`;
            totalReturnEl.className = returnPct >= 0 ? 'text-green-600' : 'text-red-600';
        }
    }

    formatInvestmentType(type) {
        const types = {
            stock: 'Stocks',
            bond: 'Bonds',
            etf: 'ETFs',
            mutual_fund: 'Mutual Funds',
            crypto: 'Cryptocurrency',
            real_estate: 'Real Estate'
        };
        return types[type] || type;
    }

    getTransactionTypeClass(type) {
        const classes = {
            buy: 'bg-green-100 text-green-800',
            sell: 'bg-red-100 text-red-800',
            dividend: 'bg-blue-100 text-blue-800',
            split: 'bg-yellow-100 text-yellow-800'
        };
        return classes[type] || 'bg-gray-100 text-gray-800';
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('cs-CZ', {
            style: 'currency',
            currency: 'CZK',
            minimumFractionDigits: 0
        }).format(amount);
    }

    showLoadingState(message = 'Loading...') {
        const loader = document.getElementById('investments-loader');
        if (loader) {
            loader.textContent = message;
            loader.classList.remove('hidden');
        }
    }

    hideLoadingState() {
        const loader = document.getElementById('investments-loader');
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
        window.investmentsUI = new InvestmentsUI();
    });
} else {
    window.investmentsUI = new InvestmentsUI();
}
