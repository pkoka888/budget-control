/**
 * Goals Management UI Controller
 * Handles financial goals CRUD operations and progress tracking
 */

class GoalsUI {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.currentGoalId = null;

        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Create goal button
        document.getElementById('create-goal-btn')?.addEventListener('click', () => this.openCreateModal());

        // Edit goal buttons
        document.querySelectorAll('.edit-goal-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const goalId = e.target.closest('button').dataset.goalId;
                this.editGoal(goalId);
            });
        });

        // Goal form submission
        document.getElementById('goal-form')?.addEventListener('submit', (e) => this.saveGoal(e));

        // Modal close buttons
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                this.closeModal(modal);
            });
        });

        // Update progress calculation when amounts change
        document.getElementById('goal-current-amount')?.addEventListener('input', () => {
            this.updateProgressPreview();
        });
        document.getElementById('goal-target-amount')?.addEventListener('input', () => {
            this.updateProgressPreview();
        });
    }

    openCreateModal() {
        const modal = document.getElementById('goal-modal');
        const form = document.getElementById('goal-form');

        form.reset();
        document.getElementById('goal-id').value = '';
        document.getElementById('goal-modal-title').textContent = 'Nový cíl';
        this.currentGoalId = null;

        this.openModal(modal);
    }

    async editGoal(goalId) {
        try {
            const response = await fetch(`/goals/${goalId}`, {
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch goal details');
            }

            const html = await response.text();
            // Parse the response to extract goal data
            // In a real implementation, we'd have a JSON API endpoint
            // For now, we'll use the existing data from the page

            const goalCard = document.querySelector(`[data-goal-id="${goalId}"]`);
            if (!goalCard) return;

            // Extract data from the card (simplified)
            const modal = document.getElementById('goal-modal');
            document.getElementById('goal-modal-title').textContent = 'Upravit cíl';
            document.getElementById('goal-id').value = goalId;

            this.currentGoalId = goalId;
            this.openModal(modal);

        } catch (error) {
            this.showAlert('Nepodařilo se načíst detail cíle', 'error');
        }
    }

    async saveGoal(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const goalId = formData.get('id');

        const data = {
            name: formData.get('name'),
            description: formData.get('description'),
            goal_type: formData.get('goal_type'),
            target_amount: parseFloat(formData.get('target_amount')),
            current_amount: parseFloat(formData.get('current_amount') || 0),
            target_date: formData.get('target_date') || null,
            priority: formData.get('priority')
        };

        // Validate
        if (!data.name || data.target_amount <= 0) {
            this.showAlert('Vyplňte prosím všechna povinná pole', 'error');
            return;
        }

        this.showLoading('save-goal-text', 'save-goal-loading', true);

        try {
            const url = goalId ? `/goals/${goalId}/update` : '/goals';
            const method = 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || result.errors?.join(', ') || 'Failed to save goal');
            }

            this.showAlert(goalId ? 'Cíl byl aktualizován' : 'Cíl byl vytvořen', 'success');

            const modal = document.getElementById('goal-modal');
            this.closeModal(modal);

            // Reload page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1000);

        } catch (error) {
            this.showAlert(error.message, 'error');
            this.showLoading('save-goal-text', 'save-goal-loading', false);
        }
    }

    updateProgressPreview() {
        const current = parseFloat(document.getElementById('goal-current-amount')?.value || 0);
        const target = parseFloat(document.getElementById('goal-target-amount')?.value || 0);

        if (target > 0) {
            const progress = (current / target) * 100;
            // Could show a preview here if we add a preview element
        }
    }

    openModal(modal) {
        modal?.classList.remove('hidden');
        modal?.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        // Focus first input
        const firstInput = modal?.querySelector('input:not([type="hidden"]), select, textarea');
        firstInput?.focus();
    }

    closeModal(modal) {
        modal?.classList.add('hidden');
        modal?.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    showAlert(message, type = 'info') {
        const container = document.getElementById('alert-container');
        if (!container) {
            // Create alert container if it doesn't exist
            const alertDiv = document.createElement('div');
            alertDiv.id = 'alert-container';
            alertDiv.className = 'fixed top-4 right-4 z-50';
            document.body.appendChild(alertDiv);
        }

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

        const alertContainer = document.getElementById('alert-container');
        alertContainer.innerHTML = `
            <div class="alert ${alertClass} animate-slide-in-down" role="alert">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${icon}
                </svg>
                ${this.escapeHtml(message)}
            </div>
        `;

        setTimeout(() => alertContainer.innerHTML = '', 5000);
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

// Goal Detail Page Controller
class GoalDetailUI {
    constructor(goalId) {
        this.goalId = goalId;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.chart = null;

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadMilestones();
        this.initializeChart();
    }

    bindEvents() {
        // Contribution form
        document.getElementById('contribution-form')?.addEventListener('submit', (e) => this.addContribution(e));

        // Milestone form
        document.getElementById('milestone-form')?.addEventListener('submit', (e) => this.addMilestone(e));

        // Delete goal button
        document.getElementById('delete-goal-btn')?.addEventListener('click', () => this.deleteGoal());

        // Calculate projection
        document.getElementById('monthly-contribution')?.addEventListener('input', () => {
            this.calculateProjection();
        });
    }

    async loadMilestones() {
        try {
            const response = await fetch(`/goals/${this.goalId}/milestones`, {
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            if (!response.ok) return;

            const data = await response.json();
            this.renderMilestones(data.milestones || []);

        } catch (error) {
            console.error('Failed to load milestones:', error);
        }
    }

    renderMilestones(milestones) {
        const container = document.getElementById('milestones-container');
        if (!container) return;

        if (milestones.length === 0) {
            container.innerHTML = '<p class="text-slate-gray-500 text-center py-4">Žádné milníky</p>';
            return;
        }

        container.innerHTML = milestones.map(milestone => `
            <div class="flex items-start p-4 border-l-4 ${milestone.is_completed ? 'border-green-500 bg-green-50' : 'border-slate-gray-300 bg-white'} rounded">
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h4 class="font-semibold text-slate-gray-900">${this.escapeHtml(milestone.name)}</h4>
                        <span class="text-sm font-medium ${milestone.is_completed ? 'text-green-600' : 'text-slate-gray-600'}">
                            ${this.formatAmount(milestone.target_amount)} Kč
                        </span>
                    </div>
                    ${milestone.description ? `<p class="text-sm text-slate-gray-600 mt-1">${this.escapeHtml(milestone.description)}</p>` : ''}
                    ${milestone.target_date ? `<p class="text-xs text-slate-gray-500 mt-2">Termín: ${milestone.target_date}</p>` : ''}
                </div>
                ${milestone.is_completed ? `
                    <svg class="w-6 h-6 text-green-600 ml-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                ` : ''}
            </div>
        `).join('');
    }

    async addContribution(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const amount = parseFloat(formData.get('amount'));

        if (amount <= 0) {
            alert('Částka musí být větší než 0');
            return;
        }

        try {
            const response = await fetch(`/goals/${this.goalId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    current_amount: amount // This should add to current, not replace
                })
            });

            if (!response.ok) {
                throw new Error('Failed to add contribution');
            }

            alert('Příspěvek byl přidán');
            window.location.reload();

        } catch (error) {
            alert('Chyba při přidávání příspěvku: ' + error.message);
        }
    }

    async addMilestone(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const data = {
            name: formData.get('name'),
            description: formData.get('description'),
            target_amount: parseFloat(formData.get('target_amount')),
            target_date: formData.get('target_date') || null
        };

        try {
            const response = await fetch(`/goals/${this.goalId}/milestones`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Failed to create milestone');
            }

            alert('Milník byl vytvořen');
            e.target.reset();
            this.loadMilestones();

        } catch (error) {
            alert('Chyba při vytváření milníku: ' + error.message);
        }
    }

    async deleteGoal() {
        if (!confirm('Opravdu chcete smazat tento cíl?')) return;

        try {
            const response = await fetch(`/goals/${this.goalId}/delete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            if (!response.ok) {
                throw new Error('Failed to delete goal');
            }

            alert('Cíl byl smazán');
            window.location.href = '/goals';

        } catch (error) {
            alert('Chyba při mazání cíle: ' + error.message);
        }
    }

    async calculateProjection() {
        const monthlyContribution = parseFloat(document.getElementById('monthly-contribution')?.value || 0);

        if (monthlyContribution <= 0) return;

        try {
            const response = await fetch(`/goals/${this.goalId}/projection?monthly_contribution=${monthlyContribution}`, {
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            if (!response.ok) return;

            const data = await response.json();
            this.displayProjection(data);

        } catch (error) {
            console.error('Failed to calculate projection:', error);
        }
    }

    displayProjection(projection) {
        const container = document.getElementById('projection-result');
        if (!container || !projection) return;

        container.innerHTML = `
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-semibold text-blue-900 mb-2">Projekce</h4>
                <p class="text-sm text-blue-700">
                    S měsíčním příspěvkem <strong>${this.formatAmount(projection.monthly_contribution)} Kč</strong>
                    dosáhnete cíle za <strong>${projection.months_to_target} měsíců</strong>
                    (${projection.target_date}).
                </p>
            </div>
        `;
    }

    initializeChart() {
        const chartCanvas = document.getElementById('goal-progress-chart');
        if (!chartCanvas) return;

        // This would show progress over time if we have historical data
        // For now, just a placeholder
    }

    formatAmount(amount) {
        return new Intl.NumberFormat('cs-CZ', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize
let goalsUI;
let goalDetailUI;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        goalsUI = new GoalsUI();

        // Check if we're on a goal detail page
        const goalDetailPage = document.querySelector('[data-page="goal-detail"]');
        if (goalDetailPage) {
            const goalId = goalDetailPage.dataset.goalId;
            goalDetailUI = new GoalDetailUI(goalId);
        }
    });
} else {
    goalsUI = new GoalsUI();

    const goalDetailPage = document.querySelector('[data-page="goal-detail"]');
    if (goalDetailPage) {
        const goalId = goalDetailPage.dataset.goalId;
        goalDetailUI = new GoalDetailUI(goalId);
    }
}
