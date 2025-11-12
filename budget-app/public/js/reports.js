/**
 * Reports UI Controller
 * Handles interactive report filtering, date range selection, and data visualization
 */

class ReportsUI {
    constructor() {
        this.currentReport = 'monthly';
        this.currentMonth = new Date().getMonth() + 1;
        this.currentYear = new Date().getFullYear();
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.charts = {};

        this.init();
    }

    init() {
        this.attachEventListeners();
        this.loadReportData();
    }

    attachEventListeners() {
        // Month/Year navigation
        document.getElementById('prev-period')?.addEventListener('click', () => this.navigatePeriod(-1));
        document.getElementById('next-period')?.addEventListener('click', () => this.navigatePeriod(1));

        // Date range picker
        document.getElementById('date-from')?.addEventListener('change', () => this.filterByDateRange());
        document.getElementById('date-to')?.addEventListener('change', () => this.filterByDateRange());

        // Report type tabs
        document.querySelectorAll('.report-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchReportType(tab.dataset.report);
            });
        });

        // Category filter
        document.getElementById('category-filter')?.addEventListener('change', (e) => {
            this.filterByCategory(e.target.value);
        });

        // Export buttons
        document.getElementById('export-csv')?.addEventListener('click', () => this.exportData('csv'));
        document.getElementById('export-xlsx')?.addEventListener('click', () => this.exportData('xlsx'));
        document.getElementById('export-pdf')?.addEventListener('click', () => this.exportData('pdf'));

        // Chart type toggle
        document.querySelectorAll('.chart-type-toggle').forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                this.toggleChartType(e.target.dataset.chart, e.target.dataset.type);
            });
        });
    }

    navigatePeriod(direction) {
        if (this.currentReport === 'monthly') {
            this.currentMonth += direction;
            if (this.currentMonth > 12) {
                this.currentMonth = 1;
                this.currentYear++;
            } else if (this.currentMonth < 1) {
                this.currentMonth = 12;
                this.currentYear--;
            }
        } else if (this.currentReport === 'yearly') {
            this.currentYear += direction;
        }

        this.loadReportData();
    }

    switchReportType(reportType) {
        this.currentReport = reportType;

        // Update active tab
        document.querySelectorAll('.report-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.report === reportType);
        });

        // Show/hide appropriate controls
        this.updateControls();

        // Load new report data
        this.loadReportData();
    }

    updateControls() {
        const monthlyControls = document.getElementById('monthly-controls');
        const yearlyControls = document.getElementById('yearly-controls');
        const customControls = document.getElementById('custom-controls');

        if (monthlyControls) monthlyControls.style.display = this.currentReport === 'monthly' ? 'block' : 'none';
        if (yearlyControls) yearlyControls.style.display = this.currentReport === 'yearly' ? 'block' : 'none';
        if (customControls) customControls.style.display = this.currentReport === 'custom' ? 'block' : 'none';
    }

    async loadReportData() {
        this.showLoadingState();

        try {
            let url;
            if (this.currentReport === 'monthly') {
                url = `/reports/monthly?month=${this.currentMonth}&year=${this.currentYear}`;
            } else if (this.currentReport === 'yearly') {
                url = `/reports/yearly?year=${this.currentYear}`;
            } else if (this.currentReport === 'analytics') {
                url = `/reports/analytics`;
            } else if (this.currentReport === 'net-worth') {
                url = `/reports/net-worth`;
            }

            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Failed to load report data');

            const html = await response.text();

            // Update report container
            const container = document.getElementById('report-container');
            if (container) {
                container.innerHTML = html;
                this.reinitializeCharts();
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error loading report:', error);
            this.showAlert('Failed to load report data', 'error');
            this.hideLoadingState();
        }
    }

    async filterByDateRange() {
        const dateFrom = document.getElementById('date-from')?.value;
        const dateTo = document.getElementById('date-to')?.value;

        if (!dateFrom || !dateTo) return;

        this.showLoadingState();

        try {
            const response = await fetch(`/api/reports/custom?from=${dateFrom}&to=${dateTo}`);
            const data = await response.json();

            if (data.success) {
                this.renderCustomReport(data.report);
            } else {
                this.showAlert('Failed to generate custom report', 'error');
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error filtering by date range:', error);
            this.showAlert('Failed to filter report', 'error');
            this.hideLoadingState();
        }
    }

    async filterByCategory(categoryId) {
        if (!categoryId || categoryId === 'all') {
            this.loadReportData();
            return;
        }

        this.showLoadingState();

        try {
            const response = await fetch(`/api/reports/category?category_id=${categoryId}&month=${this.currentMonth}&year=${this.currentYear}`);
            const data = await response.json();

            if (data.success) {
                this.updateCategoryData(data.category_data);
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error filtering by category:', error);
            this.showAlert('Failed to filter by category', 'error');
            this.hideLoadingState();
        }
    }

    renderCustomReport(reportData) {
        // Render custom date range report
        const container = document.getElementById('custom-report-results');
        if (!container) return;

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <p class="text-sm font-medium text-slate-gray-600">Total Income</p>
                    <p class="mt-2 text-3xl font-bold text-green-600">
                        ${this.formatCurrency(reportData.total_income)}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <p class="text-sm font-medium text-slate-gray-600">Total Expenses</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">
                        ${this.formatCurrency(reportData.total_expenses)}
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <p class="text-sm font-medium text-slate-gray-600">Net Savings</p>
                    <p class="mt-2 text-3xl font-bold ${reportData.net_savings >= 0 ? 'text-green-600' : 'text-red-600'}">
                        ${this.formatCurrency(reportData.net_savings)}
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <canvas id="custom-chart"></canvas>
            </div>
        `;

        // Render chart
        this.renderCustomChart(reportData);
    }

    renderCustomChart(reportData) {
        const ctx = document.getElementById('custom-chart')?.getContext('2d');
        if (!ctx) return;

        if (this.charts.custom) {
            this.charts.custom.destroy();
        }

        this.charts.custom = new Chart(ctx, {
            type: 'line',
            data: {
                labels: reportData.labels || [],
                datasets: [
                    {
                        label: 'Income',
                        data: reportData.income_data || [],
                        borderColor: '#10B981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Expenses',
                        data: reportData.expense_data || [],
                        borderColor: '#EF4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return context.dataset.label + ': ' + this.formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => this.formatCurrency(value)
                        }
                    }
                }
            }
        });
    }

    updateCategoryData(categoryData) {
        // Update category-specific sections
        const breakdown = document.getElementById('category-breakdown');
        if (!breakdown) return;

        breakdown.innerHTML = categoryData.transactions.map(txn => `
            <tr>
                <td class="px-4 py-3">${txn.date}</td>
                <td class="px-4 py-3">${txn.description}</td>
                <td class="px-4 py-3 text-right font-semibold">
                    ${this.formatCurrency(txn.amount)}
                </td>
            </tr>
        `).join('');

        // Update summary
        document.getElementById('category-total').textContent = this.formatCurrency(categoryData.total);
        document.getElementById('category-count').textContent = categoryData.count;
    }

    toggleChartType(chartId, chartType) {
        const chartCanvas = document.getElementById(chartId);
        if (!chartCanvas || !this.charts[chartId]) return;

        const chart = this.charts[chartId];
        chart.config.type = chartType;
        chart.update();

        // Update toggle button states
        document.querySelectorAll(`[data-chart="${chartId}"]`).forEach(btn => {
            btn.classList.toggle('active', btn.dataset.type === chartType);
        });
    }

    async exportData(format) {
        this.showLoadingState('Preparing export...');

        try {
            const url = `/reports/export/${format}/${this.currentReport}?month=${this.currentMonth}&year=${this.currentYear}`;

            // Open in new window to trigger download
            window.open(url, '_blank');

            this.showAlert(`Export started. Your ${format.toUpperCase()} file will download shortly.`, 'success');
            this.hideLoadingState();

        } catch (error) {
            console.error('Export error:', error);
            this.showAlert('Failed to export data', 'error');
            this.hideLoadingState();
        }
    }

    reinitializeCharts() {
        // Find all canvas elements and reinitialize charts
        const canvases = document.querySelectorAll('canvas[id$="-chart"]');

        canvases.forEach(canvas => {
            if (window[`${canvas.id}Config`]) {
                const chartId = canvas.id.replace('-chart', '');
                if (this.charts[chartId]) {
                    this.charts[chartId].destroy();
                }
                // Chart.js initialization happens in the view templates
            }
        });
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('cs-CZ', {
            style: 'currency',
            currency: 'CZK',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    showLoadingState(message = 'Loading...') {
        const loader = document.getElementById('report-loader');
        if (loader) {
            loader.textContent = message;
            loader.classList.remove('hidden');
        }
    }

    hideLoadingState() {
        const loader = document.getElementById('report-loader');
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
        window.reportsUI = new ReportsUI();
    });
} else {
    window.reportsUI = new ReportsUI();
}
