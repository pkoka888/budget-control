/**
 * Scenario Planning UI Controller
 * Handles financial scenario generation, comparison, and what-if analysis
 */

class ScenarioPlanningUI {
    constructor() {
        this.currentScenarios = {};
        this.comparisonChart = null;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        this.init();
    }

    init() {
        this.attachEventListeners();
        this.loadCurrentFinancialData();
    }

    attachEventListeners() {
        // Generate scenarios button
        document.getElementById('generate-scenarios-btn')?.addEventListener('click', () => {
            this.generateScenarios();
        });

        // Scenario type checkboxes
        document.querySelectorAll('input[name="scenario_types[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateSelectedScenarios();
            });
        });

        // Timeframe slider
        document.getElementById('timeframe-months')?.addEventListener('input', (e) => {
            document.getElementById('timeframe-display').textContent = `${e.target.value} months`;
        });

        // Compare scenarios button
        document.getElementById('compare-scenarios-btn')?.addEventListener('click', () => {
            this.compareScenarios();
        });

        // Save scenario as goal
        document.addEventListener('click', (e) => {
            if (e.target.matches('.save-as-goal-btn')) {
                e.preventDefault();
                const scenarioType = e.target.dataset.scenarioType;
                this.saveScenarioAsGoal(scenarioType);
            }
        });

        // Retirement planning form
        document.getElementById('retirement-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.generateRetirementScenarios();
        });

        // Goal scenario selector
        document.getElementById('goal-selector')?.addEventListener('change', (e) => {
            if (e.target.value) {
                this.generateGoalScenarios(e.target.value);
            }
        });

        // Scenario templates
        document.querySelectorAll('.scenario-template').forEach(template => {
            template.addEventListener('click', () => {
                this.applyTemplate(template.dataset.templateId);
            });
        });

        // Custom scenario inputs
        document.getElementById('monthly-income-input')?.addEventListener('input', () => {
            this.updateCustomScenario();
        });

        document.getElementById('monthly-expenses-input')?.addEventListener('input', () => {
            this.updateCustomScenario();
        });

        document.getElementById('savings-rate-input')?.addEventListener('input', (e) => {
            document.getElementById('savings-rate-display').textContent = `${e.target.value}%`;
            this.updateCustomScenario();
        });
    }

    async loadCurrentFinancialData() {
        // Current financial data is usually loaded from server-side and rendered in the view
        const currentIncomeEl = document.getElementById('current-monthly-income');
        const currentExpensesEl = document.getElementById('current-monthly-expenses');
        const currentSavingsEl = document.getElementById('current-savings');

        if (currentIncomeEl) {
            this.currentIncome = parseFloat(currentIncomeEl.textContent.replace(/[^\d.-]/g, ''));
        }
        if (currentExpensesEl) {
            this.currentExpenses = parseFloat(currentExpensesEl.textContent.replace(/[^\d.-]/g, ''));
        }
        if (currentSavingsEl) {
            this.currentSavings = parseFloat(currentSavingsEl.textContent.replace(/[^\d.-]/g, ''));
        }

        // Pre-fill custom inputs
        if (document.getElementById('monthly-income-input')) {
            document.getElementById('monthly-income-input').value = this.currentIncome || 0;
        }
        if (document.getElementById('monthly-expenses-input')) {
            document.getElementById('monthly-expenses-input').value = this.currentExpenses || 0;
        }
    }

    async generateScenarios() {
        const selectedTypes = Array.from(document.querySelectorAll('input[name="scenario_types[]"]:checked'))
            .map(cb => cb.value);

        if (selectedTypes.length === 0) {
            this.showAlert('Please select at least one scenario type', 'warning');
            return;
        }

        const timeframeMonths = document.getElementById('timeframe-months')?.value || 12;

        this.showLoadingState('Generating scenarios...');

        try {
            const response = await fetch(`/api/scenario/generate?months=${timeframeMonths}&type=${selectedTypes.join(',')}`);
            const data = await response.json();

            if (data.success) {
                this.currentScenarios = data.scenarios;
                this.renderScenarios(data.scenarios);
                this.showAlert('Scenarios generated successfully!', 'success');
            } else {
                this.showAlert('Failed to generate scenarios', 'error');
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error generating scenarios:', error);
            this.showAlert('Failed to generate scenarios', 'error');
            this.hideLoadingState();
        }
    }

    renderScenarios(scenarios) {
        const container = document.getElementById('scenarios-results');
        if (!container) return;

        const scenarioCards = Object.entries(scenarios).map(([type, scenario]) => {
            const finalProjection = scenario.projections[scenario.projections.length - 1];
            const finalBalance = finalProjection?.balance || 0;
            const totalSavings = scenario.projections.reduce((sum, p) => sum + (p.monthly_savings || 0), 0);
            const totalReturns = scenario.projections.reduce((sum, p) => sum + (p.investment_returns || 0), 0);

            const balanceClass = finalBalance >= 0 ? 'text-green-600' : 'text-red-600';

            return `
                <div class="bg-white rounded-lg shadow-md p-6 scenario-card" data-scenario-type="${type}">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-slate-gray-900">${scenario.name}</h3>
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 text-sm rounded">${type}</span>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-gray-600">Final Balance:</span>
                            <span class="font-semibold ${balanceClass}">${this.formatCurrency(finalBalance)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-gray-600">Total Savings:</span>
                            <span class="font-semibold text-green-600">${this.formatCurrency(totalSavings)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-slate-gray-600">Investment Returns:</span>
                            <span class="font-semibold text-blue-600">${this.formatCurrency(totalReturns)}</span>
                        </div>
                    </div>

                    <div class="border-t pt-4 mb-4">
                        <h4 class="text-sm font-medium text-slate-gray-700 mb-2">Assumptions:</h4>
                        <ul class="text-sm text-slate-gray-600 space-y-1">
                            ${Object.entries(scenario.assumptions || {}).map(([key, value]) => `
                                <li>• ${this.formatAssumptionKey(key)}: ${this.formatAssumptionValue(key, value)}</li>
                            `).join('')}
                        </ul>
                    </div>

                    <div class="flex gap-2">
                        <button class="btn btn-secondary btn-sm flex-1 view-details-btn"
                                onclick="scenarioPlanningUI.showScenarioDetails('${type}')">
                            View Details
                        </button>
                        <button class="btn btn-primary btn-sm flex-1 save-as-goal-btn"
                                data-scenario-type="${type}">
                            Save as Goal
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = scenarioCards;
    }

    showScenarioDetails(scenarioType) {
        const scenario = this.currentScenarios[scenarioType];
        if (!scenario) return;

        const modal = document.getElementById('scenario-details-modal');
        if (!modal) return;

        const modalContent = document.getElementById('scenario-details-content');
        if (!modalContent) return;

        // Render detailed projections table
        const projectionsTable = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Income</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Expenses</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Savings</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Returns</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${scenario.projections.map((proj, idx) => `
                            <tr>
                                <td class="px-4 py-3 text-sm">${idx + 1}</td>
                                <td class="px-4 py-3 text-sm text-right text-green-600">${this.formatCurrency(proj.income || 0)}</td>
                                <td class="px-4 py-3 text-sm text-right text-red-600">${this.formatCurrency(proj.expenses || 0)}</td>
                                <td class="px-4 py-3 text-sm text-right">${this.formatCurrency(proj.monthly_savings || 0)}</td>
                                <td class="px-4 py-3 text-sm text-right text-blue-600">${this.formatCurrency(proj.investment_returns || 0)}</td>
                                <td class="px-4 py-3 text-sm text-right font-semibold">${this.formatCurrency(proj.balance || 0)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;

        modalContent.innerHTML = `
            <h2 class="text-2xl font-bold mb-4">${scenario.name}</h2>
            <div class="mb-6">
                <canvas id="scenario-detail-chart" height="80"></canvas>
            </div>
            ${projectionsTable}
        `;

        modal.classList.remove('hidden');

        // Render chart
        this.renderScenarioDetailChart(scenario);
    }

    renderScenarioDetailChart(scenario) {
        const ctx = document.getElementById('scenario-detail-chart')?.getContext('2d');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: scenario.projections.map((_, idx) => `Month ${idx + 1}`),
                datasets: [
                    {
                        label: 'Balance',
                        data: scenario.projections.map(p => p.balance),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return 'Balance: ' + this.formatCurrency(context.parsed.y);
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

    async compareScenarios() {
        const selectedScenarios = Array.from(document.querySelectorAll('.scenario-card input[type="checkbox"]:checked'))
            .map(cb => cb.dataset.scenarioType);

        if (selectedScenarios.length < 2) {
            this.showAlert('Please select at least 2 scenarios to compare', 'warning');
            return;
        }

        const timeframeMonths = document.getElementById('timeframe-months')?.value || 12;

        this.showLoadingState('Comparing scenarios...');

        try {
            const response = await fetch(`/api/scenario/compare?months=${timeframeMonths}&types=${selectedScenarios.join(',')}`);
            const data = await response.json();

            if (data.success) {
                this.renderComparison(data.comparison);
            } else {
                this.showAlert('Failed to compare scenarios', 'error');
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error comparing scenarios:', error);
            this.showAlert('Failed to compare scenarios', 'error');
            this.hideLoadingState();
        }
    }

    renderComparison(comparison) {
        const container = document.getElementById('comparison-results');
        if (!container) return;

        // Render comparison chart
        const ctx = document.getElementById('comparison-chart')?.getContext('2d');
        if (ctx) {
            if (this.comparisonChart) {
                this.comparisonChart.destroy();
            }

            const colors = {
                conservative: '#10B981',
                moderate: '#3B82F6',
                optimistic: '#8B5CF6',
                crisis: '#EF4444'
            };

            this.comparisonChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: Array.from({ length: comparison.timeframe_months }, (_, i) => `Month ${i + 1}`),
                    datasets: Object.entries(comparison.scenarios).map(([type, scenario]) => ({
                        label: scenario.name,
                        data: Array.from({ length: comparison.timeframe_months }, (_, i) => scenario.final_balance / comparison.timeframe_months * (i + 1)),
                        borderColor: colors[type] || '#6B7280',
                        backgroundColor: `${colors[type] || '#6B7280'}20`,
                        tension: 0.4
                    }))
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

        // Render insights
        const insightsContainer = document.getElementById('comparison-insights');
        if (insightsContainer && comparison.insights) {
            insightsContainer.innerHTML = comparison.insights.map(insight => `
                <div class="p-4 rounded-lg ${this.getInsightClass(insight.type)}">
                    <h4 class="font-semibold mb-1">${insight.title}</h4>
                    <p class="text-sm">${insight.description}</p>
                </div>
            `).join('');
        }

        container.classList.remove('hidden');
    }

    async saveScenarioAsGoal(scenarioType) {
        const scenario = this.currentScenarios[scenarioType];
        if (!scenario) return;

        const finalProjection = scenario.projections[scenario.projections.length - 1];

        const goalData = {
            name: `Goal from ${scenario.name}`,
            description: `Financial goal based on ${scenario.name} scenario`,
            goal_type: 'savings',
            target_amount: finalProjection?.balance || 0,
            current_amount: this.currentSavings || 0,
            target_date: this.calculateTargetDate(scenario.projections.length),
            scenario_type: scenarioType
        };

        try {
            const response = await fetch('/api/scenario/save-as-goal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(goalData)
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('Scenario saved as goal successfully!', 'success');
                // Optionally redirect to goals page
                setTimeout(() => {
                    window.location.href = `/goals/${data.goal_id}`;
                }, 2000);
            } else {
                this.showAlert('Failed to save scenario as goal', 'error');
            }

        } catch (error) {
            console.error('Error saving scenario as goal:', error);
            this.showAlert('Failed to save scenario as goal', 'error');
        }
    }

    async generateRetirementScenarios() {
        const formData = new FormData(document.getElementById('retirement-form'));

        const params = new URLSearchParams({
            current_age: formData.get('current_age'),
            retirement_age: formData.get('retirement_age'),
            current_savings: formData.get('current_savings'),
            monthly_contribution: formData.get('monthly_contribution'),
            expected_return: formData.get('expected_return')
        });

        this.showLoadingState('Generating retirement scenarios...');

        try {
            const response = await fetch(`/api/scenario/retirement?${params}`);
            const data = await response.json();

            if (data.success) {
                this.renderRetirementScenarios(data.scenarios);
            } else {
                this.showAlert('Failed to generate retirement scenarios', 'error');
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error generating retirement scenarios:', error);
            this.showAlert('Failed to generate retirement scenarios', 'error');
            this.hideLoadingState();
        }
    }

    renderRetirementScenarios(scenarios) {
        const container = document.getElementById('retirement-results');
        if (!container) return;

        container.innerHTML = `
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-semibold mb-4">Retirement Projections</h3>
                <canvas id="retirement-chart" height="80"></canvas>
                <div class="mt-6 space-y-3">
                    ${Object.entries(scenarios).map(([key, value]) => `
                        <div class="flex justify-between">
                            <span class="text-slate-gray-700">${this.formatAssumptionKey(key)}:</span>
                            <span class="font-semibold">${this.formatCurrency(value)}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        container.classList.remove('hidden');
    }

    async generateGoalScenarios(goalId) {
        this.showLoadingState('Generating goal scenarios...');

        try {
            const response = await fetch(`/api/scenario/goal/${goalId}`);
            const data = await response.json();

            if (data.success) {
                this.renderGoalScenarios(data.scenarios);
            } else {
                this.showAlert(data.error || 'Failed to generate goal scenarios', 'error');
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error generating goal scenarios:', error);
            this.showAlert('Failed to generate goal scenarios', 'error');
            this.hideLoadingState();
        }
    }

    renderGoalScenarios(scenarios) {
        const container = document.getElementById('goal-scenarios-results');
        if (!container) return;

        container.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                ${Object.entries(scenarios).map(([type, scenario]) => `
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h4 class="font-semibold mb-2">${scenario.name}</h4>
                        <p class="text-2xl font-bold text-blue-600 mb-2">${scenario.months_to_completion} months</p>
                        <p class="text-sm text-slate-gray-600 mb-4">${scenario.description}</p>
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span>Monthly contribution:</span>
                                <span class="font-semibold">${this.formatCurrency(scenario.monthly_contribution)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Target date:</span>
                                <span class="font-semibold">${scenario.target_date}</span>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        container.classList.remove('hidden');
    }

    applyTemplate(templateId) {
        // Template application logic
        this.showAlert('Template applied', 'success');
    }

    updateCustomScenario() {
        // Real-time custom scenario calculation
        const income = parseFloat(document.getElementById('monthly-income-input')?.value) || 0;
        const expenses = parseFloat(document.getElementById('monthly-expenses-input')?.value) || 0;
        const savingsRate = parseFloat(document.getElementById('savings-rate-input')?.value) || 0;

        const monthlySavings = (income - expenses) * (savingsRate / 100);

        const resultEl = document.getElementById('custom-scenario-result');
        if (resultEl) {
            resultEl.textContent = `Estimated monthly savings: ${this.formatCurrency(monthlySavings)}`;
        }
    }

    updateSelectedScenarios() {
        const selected = Array.from(document.querySelectorAll('input[name="scenario_types[]"]:checked'));
        const count = selected.length;

        const badge = document.getElementById('selected-scenarios-count');
        if (badge) {
            badge.textContent = count;
        }
    }

    calculateTargetDate(months) {
        const date = new Date();
        date.setMonth(date.getMonth() + months);
        return date.toISOString().split('T')[0];
    }

    formatAssumptionKey(key) {
        return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    formatAssumptionValue(key, value) {
        if (key.includes('rate') || key.includes('percentage')) {
            return `${(value * 100).toFixed(1)}%`;
        }
        if (typeof value === 'number') {
            return this.formatCurrency(value);
        }
        return value;
    }

    getInsightClass(type) {
        const classes = {
            summary: 'bg-blue-50 border-l-4 border-blue-500',
            opportunity: 'bg-green-50 border-l-4 border-green-500',
            warning: 'bg-yellow-50 border-l-4 border-yellow-500',
            positive: 'bg-green-50 border-l-4 border-green-500'
        };
        return classes[type] || 'bg-gray-50 border-l-4 border-gray-500';
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('cs-CZ', {
            style: 'currency',
            currency: 'CZK',
            minimumFractionDigits: 0
        }).format(amount);
    }

    showLoadingState(message = 'Loading...') {
        const loader = document.getElementById('scenario-loader');
        if (loader) {
            loader.textContent = message;
            loader.classList.remove('hidden');
        }
    }

    hideLoadingState() {
        const loader = document.getElementById('scenario-loader');
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
        window.scenarioPlanningUI = new ScenarioPlanningUI();
    });
} else {
    window.scenarioPlanningUI = new ScenarioPlanningUI();
}
