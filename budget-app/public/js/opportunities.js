/**
 * Opportunities UI Controller
 * Handles job opportunities, learning paths, freelance gigs, events, and certifications
 */

class OpportunitiesUI {
    constructor() {
        this.currentView = 'jobs'; // jobs, learning, freelance, events, certifications
        this.savedOpportunities = [];
        this.filters = {
            region: '',
            remoteOnly: false,
            category: '',
            minSalary: 0,
            maxCost: 0
        };
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        this.init();
    }

    init() {
        this.attachEventListeners();
        this.loadOpportunities();
        this.loadSavedOpportunities();
    }

    attachEventListeners() {
        // View tabs
        document.querySelectorAll('.opportunity-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchView(tab.dataset.view);
            });
        });

        // Filter controls
        document.getElementById('region-filter')?.addEventListener('change', (e) => {
            this.filters.region = e.target.value;
            this.applyFilters();
        });

        document.getElementById('remote-only')?.addEventListener('change', (e) => {
            this.filters.remoteOnly = e.target.checked;
            this.applyFilters();
        });

        document.getElementById('category-filter')?.addEventListener('change', (e) => {
            this.filters.category = e.target.value;
            this.applyFilters();
        });

        document.getElementById('min-salary')?.addEventListener('input', (e) => {
            this.filters.minSalary = parseFloat(e.target.value) || 0;
            this.applyFilters();
        });

        document.getElementById('max-cost')?.addEventListener('input', (e) => {
            this.filters.maxCost = parseFloat(e.target.value) || 0;
            this.applyFilters();
        });

        // Search
        document.getElementById('opportunity-search')?.addEventListener('input', (e) => {
            this.searchOpportunities(e.target.value);
        });

        // Save buttons (delegated)
        document.addEventListener('click', (e) => {
            if (e.target.matches('.save-opportunity-btn')) {
                e.preventDefault();
                const opportunityId = e.target.dataset.opportunityId;
                const opportunityType = e.target.dataset.opportunityType;
                this.saveOpportunity(opportunityId, opportunityType, e.target);
            }

            if (e.target.matches('.unsave-opportunity-btn')) {
                e.preventDefault();
                const savedId = e.target.dataset.savedId;
                this.removeSavedOpportunity(savedId);
            }

            if (e.target.matches('.track-interaction-btn')) {
                e.preventDefault();
                const opportunityId = e.target.dataset.opportunityId;
                const opportunityType = e.target.dataset.opportunityType;
                const interactionType = e.target.dataset.interactionType || 'click';
                this.trackInteraction(opportunityId, opportunityType, interactionType);
            }

            if (e.target.matches('.apply-opportunity-btn')) {
                e.preventDefault();
                const url = e.target.dataset.url;
                const opportunityId = e.target.dataset.opportunityId;
                const opportunityType = e.target.dataset.opportunityType;
                this.applyToOpportunity(url, opportunityId, opportunityType);
            }
        });

        // Saved opportunities tab
        document.getElementById('view-saved')?.addEventListener('click', () => {
            this.showSavedOpportunities();
        });
    }

    switchView(view) {
        this.currentView = view;

        // Update active tab
        document.querySelectorAll('.opportunity-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.view === view);
        });

        // Update filters UI
        this.updateFiltersUI();

        // Load opportunities for this view
        this.loadOpportunities();
    }

    updateFiltersUI() {
        // Show/hide relevant filters based on current view
        const jobFilters = document.getElementById('job-filters');
        const learningFilters = document.getElementById('learning-filters');
        const freelanceFilters = document.getElementById('freelance-filters');
        const eventFilters = document.getElementById('event-filters');
        const certFilters = document.getElementById('cert-filters');

        if (jobFilters) jobFilters.style.display = this.currentView === 'jobs' ? 'block' : 'none';
        if (learningFilters) learningFilters.style.display = this.currentView === 'learning' ? 'block' : 'none';
        if (freelanceFilters) freelanceFilters.style.display = this.currentView === 'freelance' ? 'block' : 'none';
        if (eventFilters) eventFilters.style.display = this.currentView === 'events' ? 'block' : 'none';
        if (certFilters) certFilters.style.display = this.currentView === 'certifications' ? 'block' : 'none';
    }

    async loadOpportunities() {
        this.showLoadingState();

        try {
            let endpoint;
            switch (this.currentView) {
                case 'jobs':
                    endpoint = '/api/opportunities/jobs';
                    break;
                case 'learning':
                    endpoint = '/api/opportunities/learning';
                    break;
                case 'freelance':
                    endpoint = '/api/opportunities/freelance';
                    break;
                case 'events':
                    endpoint = '/api/opportunities/events';
                    break;
                case 'certifications':
                    endpoint = '/api/opportunities/certifications';
                    break;
                default:
                    endpoint = '/api/opportunities/dashboard';
            }

            const response = await fetch(endpoint);
            const data = await response.json();

            if (data.success) {
                this.renderOpportunities(data);
            } else {
                this.showAlert('Failed to load opportunities', 'error');
            }

            this.hideLoadingState();

        } catch (error) {
            console.error('Error loading opportunities:', error);
            this.showAlert('Failed to load opportunities', 'error');
            this.hideLoadingState();
        }
    }

    renderOpportunities(data) {
        const container = document.getElementById('opportunities-container');
        if (!container) return;

        let opportunities;
        switch (this.currentView) {
            case 'jobs':
                opportunities = data.jobs || [];
                break;
            case 'learning':
                opportunities = data.learning_paths || [];
                break;
            case 'freelance':
                opportunities = data.freelance_gigs || [];
                break;
            case 'events':
                opportunities = data.events || [];
                break;
            case 'certifications':
                opportunities = data.certifications || [];
                break;
            default:
                opportunities = [];
        }

        if (opportunities.length === 0) {
            container.innerHTML = '<div class="text-center py-8 text-slate-gray-600">No opportunities found matching your criteria.</div>';
            return;
        }

        container.innerHTML = opportunities.map(opp => this.renderOpportunityCard(opp)).join('');
    }

    renderOpportunityCard(opportunity) {
        const isSaved = this.savedOpportunities.some(s => s.opportunity_id === opportunity.id);
        const saveButtonClass = isSaved ? 'unsave-opportunity-btn text-red-600' : 'save-opportunity-btn text-blue-600';
        const saveButtonText = isSaved ? '★ Saved' : '☆ Save';

        let cardContent = '';

        if (this.currentView === 'jobs') {
            cardContent = `
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-slate-gray-900">${this.escapeHtml(opportunity.title)}</h3>
                            <p class="text-sm text-slate-gray-600">${this.escapeHtml(opportunity.company)}</p>
                            <p class="text-sm text-slate-gray-500 mt-1">
                                📍 ${this.escapeHtml(opportunity.location)} ${opportunity.remote === 'remote' ? '• 🏠 Remote' : ''}
                            </p>
                        </div>
                        <button class="${saveButtonClass}"
                                data-opportunity-id="${opportunity.id}"
                                data-opportunity-type="jobs"
                                ${isSaved ? `data-saved-id="${this.getSavedId(opportunity.id)}"` : ''}>
                            ${saveButtonText}
                        </button>
                    </div>
                    <p class="text-slate-gray-700 mb-4">${this.escapeHtml(opportunity.description || '').substring(0, 150)}...</p>
                    <div class="flex items-center justify-between">
                        <div class="text-green-600 font-semibold">
                            ${opportunity.salary_min ? `${this.formatCurrency(opportunity.salary_min)} - ${this.formatCurrency(opportunity.salary_max)}` : 'Salary not specified'}
                        </div>
                        <button class="apply-opportunity-btn btn btn-primary btn-sm"
                                data-url="${opportunity.url}"
                                data-opportunity-id="${opportunity.id}"
                                data-opportunity-type="jobs">
                            Apply Now →
                        </button>
                    </div>
                    <div class="mt-4 flex gap-2 flex-wrap">
                        ${(opportunity.skills || []).slice(0, 3).map(skill =>
                            `<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">${this.escapeHtml(skill)}</span>`
                        ).join('')}
                    </div>
                </div>
            `;
        } else if (this.currentView === 'learning') {
            cardContent = `
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-lg font-semibold text-slate-gray-900">${this.escapeHtml(opportunity.title)}</h3>
                        <button class="${saveButtonClass}"
                                data-opportunity-id="${opportunity.id}"
                                data-opportunity-type="learning"
                                ${isSaved ? `data-saved-id="${this.getSavedId(opportunity.id)}"` : ''}>
                            ${saveButtonText}
                        </button>
                    </div>
                    <p class="text-sm text-slate-gray-600 mb-2">📚 ${opportunity.provider}</p>
                    <p class="text-slate-gray-700 mb-4">${this.escapeHtml(opportunity.description || '')}</p>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm text-slate-gray-600">⏱️ ${opportunity.duration}</span>
                        <span class="text-green-600 font-semibold">${opportunity.price === 0 ? 'Free' : this.formatCurrency(opportunity.price)}</span>
                    </div>
                    <button class="apply-opportunity-btn btn btn-primary btn-sm w-full"
                            data-url="${opportunity.url}"
                            data-opportunity-id="${opportunity.id}"
                            data-opportunity-type="learning">
                        Start Learning →
                    </button>
                </div>
            `;
        } else if (this.currentView === 'freelance') {
            cardContent = `
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-slate-gray-900">${this.escapeHtml(opportunity.title)}</h3>
                            <p class="text-sm text-slate-gray-600">via ${opportunity.platform}</p>
                        </div>
                        <button class="${saveButtonClass}"
                                data-opportunity-id="${opportunity.id}"
                                data-opportunity-type="freelance"
                                ${isSaved ? `data-saved-id="${this.getSavedId(opportunity.id)}"` : ''}>
                            ${saveButtonText}
                        </button>
                    </div>
                    <p class="text-slate-gray-700 mb-4">${this.escapeHtml(opportunity.description || '').substring(0, 150)}...</p>
                    <div class="flex items-center justify-between">
                        <div class="text-green-600 font-semibold">
                            💰 ${this.formatCurrency(opportunity.budget_range?.min)} - ${this.formatCurrency(opportunity.budget_range?.max)}
                        </div>
                        <button class="apply-opportunity-btn btn btn-primary btn-sm"
                                data-url="${opportunity.url}"
                                data-opportunity-id="${opportunity.id}"
                                data-opportunity-type="freelance">
                            View Gig →
                        </button>
                    </div>
                </div>
            `;
        } else if (this.currentView === 'events') {
            cardContent = `
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-slate-gray-900">${this.escapeHtml(opportunity.title)}</h3>
                            <p class="text-sm text-slate-gray-600">📅 ${opportunity.date}</p>
                            <p class="text-sm text-slate-gray-500">📍 ${this.escapeHtml(opportunity.location)}</p>
                        </div>
                        <button class="${saveButtonClass}"
                                data-opportunity-id="${opportunity.id}"
                                data-opportunity-type="events"
                                ${isSaved ? `data-saved-id="${this.getSavedId(opportunity.id)}"` : ''}>
                            ${saveButtonText}
                        </button>
                    </div>
                    <p class="text-slate-gray-700 mb-4">${this.escapeHtml(opportunity.description || '')}</p>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-gray-600">${opportunity.type}</span>
                        <button class="apply-opportunity-btn btn btn-primary btn-sm"
                                data-url="${opportunity.url}"
                                data-opportunity-id="${opportunity.id}"
                                data-opportunity-type="events">
                            Register →
                        </button>
                    </div>
                </div>
            `;
        } else if (this.currentView === 'certifications') {
            cardContent = `
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-slate-gray-900">${this.escapeHtml(opportunity.title)}</h3>
                            <p class="text-sm text-slate-gray-600">${opportunity.provider}</p>
                        </div>
                        <button class="${saveButtonClass}"
                                data-opportunity-id="${opportunity.id}"
                                data-opportunity-type="certifications"
                                ${isSaved ? `data-saved-id="${this.getSavedId(opportunity.id)}"` : ''}>
                            ${saveButtonText}
                        </button>
                    </div>
                    <p class="text-slate-gray-700 mb-4">${this.escapeHtml(opportunity.description || '')}</p>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm text-slate-gray-600">⏱️ ${opportunity.duration}</span>
                        <span class="text-green-600 font-semibold">
                            💰 ${this.formatCurrency(opportunity.cost + (opportunity.exam_fee || 0))}
                        </span>
                    </div>
                    <button class="apply-opportunity-btn btn btn-primary btn-sm w-full"
                            data-url="${opportunity.url}"
                            data-opportunity-id="${opportunity.id}"
                            data-opportunity-type="certifications">
                        Learn More →
                    </button>
                </div>
            `;
        }

        return cardContent;
    }

    async saveOpportunity(opportunityId, opportunityType, button) {
        try {
            const response = await fetch('/api/opportunities/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    opportunity_id: opportunityId,
                    opportunity_type: opportunityType,
                    opportunity_data: {} // Could include additional data
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('Opportunity saved successfully!', 'success');
                button.textContent = '★ Saved';
                button.classList.remove('save-opportunity-btn');
                button.classList.add('unsave-opportunity-btn', 'text-red-600');
                await this.loadSavedOpportunities();
            } else {
                this.showAlert(data.error || 'Failed to save opportunity', 'error');
            }

        } catch (error) {
            console.error('Error saving opportunity:', error);
            this.showAlert('Failed to save opportunity', 'error');
        }
    }

    async removeSavedOpportunity(savedId) {
        try {
            const response = await fetch(`/api/opportunities/saved/${savedId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('Removed from saved', 'success');
                await this.loadSavedOpportunities();
                await this.loadOpportunities(); // Refresh to update buttons
            }

        } catch (error) {
            console.error('Error removing saved opportunity:', error);
            this.showAlert('Failed to remove saved opportunity', 'error');
        }
    }

    async loadSavedOpportunities() {
        try {
            const response = await fetch('/api/opportunities/saved');
            const data = await response.json();

            if (data.success) {
                this.savedOpportunities = data.saved_opportunities || [];
                this.updateSavedCount();
            }

        } catch (error) {
            console.error('Error loading saved opportunities:', error);
        }
    }

    showSavedOpportunities() {
        const container = document.getElementById('opportunities-container');
        if (!container) return;

        if (this.savedOpportunities.length === 0) {
            container.innerHTML = '<div class="text-center py-8 text-slate-gray-600">You haven\'t saved any opportunities yet.</div>';
            return;
        }

        container.innerHTML = this.savedOpportunities.map(saved => {
            const opportunity = JSON.parse(saved.opportunity_data || '{}');
            opportunity.id = saved.opportunity_id;
            return this.renderOpportunityCard(opportunity);
        }).join('');
    }

    async trackInteraction(opportunityId, opportunityType, interactionType) {
        try {
            await fetch('/api/opportunities/track', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    opportunity_id: opportunityId,
                    opportunity_type: opportunityType,
                    interaction_type: interactionType
                })
            });
        } catch (error) {
            console.error('Error tracking interaction:', error);
        }
    }

    applyToOpportunity(url, opportunityId, opportunityType) {
        // Track the apply interaction
        this.trackInteraction(opportunityId, opportunityType, 'apply');

        // Open in new tab
        window.open(url, '_blank');
    }

    async applyFilters() {
        await this.loadOpportunities();
    }

    searchOpportunities(query) {
        // Simple client-side search
        const cards = document.querySelectorAll('#opportunities-container > div');

        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            const matches = text.includes(query.toLowerCase());
            card.style.display = matches ? 'block' : 'none';
        });
    }

    getSavedId(opportunityId) {
        const saved = this.savedOpportunities.find(s => s.opportunity_id === opportunityId);
        return saved ? saved.id : null;
    }

    updateSavedCount() {
        const badge = document.getElementById('saved-count');
        if (badge) {
            badge.textContent = this.savedOpportunities.length;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('cs-CZ', {
            style: 'currency',
            currency: 'CZK',
            minimumFractionDigits: 0
        }).format(amount);
    }

    showLoadingState() {
        const container = document.getElementById('opportunities-container');
        if (container) {
            container.innerHTML = '<div class="text-center py-8"><div class="spinner"></div><p class="mt-2 text-slate-gray-600">Loading opportunities...</p></div>';
        }
    }

    hideLoadingState() {
        // Loading state is replaced by content
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
        window.opportunitiesUI = new OpportunitiesUI();
    });
} else {
    window.opportunitiesUI = new OpportunitiesUI();
}
