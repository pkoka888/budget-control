/**
 * Budget Templates UI Controller
 * Manages budget template creation, application, and import/export
 */

class BudgetTemplatesUI {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.currentTab = 'system';
        this.templates = [];
        this.categories = [];
        this.categoryIndex = 0;
        this.selectedTemplateId = null;

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadTemplates();
        this.loadCategories();
    }

    bindEvents() {
        // Tab switching
        document.getElementById('tab-system')?.addEventListener('click', () => this.switchTab('system'));
        document.getElementById('tab-user')?.addEventListener('click', () => this.switchTab('user'));

        // Create template button
        document.getElementById('create-template-btn')?.addEventListener('click', () => this.openCreateModal());

        // Template form submission
        document.getElementById('template-form')?.addEventListener('submit', (e) => this.saveTemplate(e));

        // Add category button
        document.getElementById('add-category-btn')?.addEventListener('click', () => this.addCategoryRow());

        // Apply template form
        document.getElementById('apply-form')?.addEventListener('submit', (e) => this.applyTemplate(e));

        // Import/Export
        document.getElementById('export-btn')?.addEventListener('click', () => this.exportTemplate());
        document.getElementById('import-file')?.addEventListener('change', (e) => this.importTemplate(e));

        // Modal close buttons
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                this.closeModal(modal);
            });
        });

        // Dynamic category row listeners (delegated)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.remove-category-btn')) {
                e.preventDefault();
                const row = e.target.closest('.category-row');
                this.removeCategoryRow(row);
            }
        });

        // Update summary on category changes
        document.addEventListener('input', (e) => {
            if (e.target.closest('#categories-container')) {
                this.updateSummary();
            }
        });

        // Preview recalculation when income changes
        document.getElementById('apply-income')?.addEventListener('input', () => {
            this.updateApplyPreview();
        });
    }

    async loadTemplates() {
        try {
            const response = await fetch('/api/budgets/templates', {
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            if (!response.ok) return;

            const data = await response.json();
            this.templates = data.templates || [];
            this.renderTemplates();

        } catch (error) {
            console.error('Failed to load templates:', error);
        }
    }

    async loadCategories() {
        try {
            const response = await fetch('/api/v1/categories');
            if (!response.ok) return;

            const data = await response.json();
            this.categories = data.categories || [];

        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    switchTab(tab) {
        this.currentTab = tab;

        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });

        const activeTab = document.getElementById(`tab-${tab}`);
        activeTab?.classList.add('active');
        activeTab?.setAttribute('aria-selected', 'true');

        // Show/hide panels
        document.getElementById('panel-system')?.classList.toggle('hidden', tab !== 'system');
        document.getElementById('panel-user')?.classList.toggle('hidden', tab !== 'user');

        this.renderTemplates();
    }

    renderTemplates() {
        const systemTemplates = this.templates.filter(t => t.is_system);
        const userTemplates = this.templates.filter(t => !t.is_system);

        const systemContainer = document.getElementById('system-templates');
        const userContainer = document.getElementById('user-templates');

        if (systemContainer) {
            systemContainer.innerHTML = systemTemplates.length > 0
                ? systemTemplates.map(t => this.renderTemplateCard(t)).join('')
                : '<p class="text-slate-gray-600 text-center py-8">Žádné systémové šablony nejsou k dispozici</p>';
        }

        if (userContainer) {
            userContainer.innerHTML = userTemplates.length > 0
                ? userTemplates.map(t => this.renderTemplateCard(t)).join('')
                : '<p class="text-slate-gray-600 text-center py-8">Zatím jste nevytvořili žádné šablony</p>';
        }

        // Attach event listeners
        this.attachTemplateCardEvents();
    }

    renderTemplateCard(template) {
        const totalAmount = template.categories?.reduce((sum, c) => sum + parseFloat(c.amount || 0), 0) || 0;
        const categoryCount = template.categories?.length || 0;

        const typeLabels = {
            'single': 'Jednotlivec',
            'family': 'Rodina',
            'student': 'Student',
            'retiree': 'Důchodce',
            'minimalist': 'Minimalista',
            'luxury': 'Luxusní'
        };

        return `
            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow" data-template-id="${template.id}">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-gray-900 mb-1">${this.escapeHtml(template.name)}</h3>
                            <p class="text-sm text-slate-gray-600">${typeLabels[template.template_type] || template.template_type}</p>
                        </div>
                        ${template.is_system ? '<span class="badge badge-primary">Systémová</span>' : ''}
                    </div>

                    ${template.description ? `<p class="text-sm text-slate-gray-600 mb-4">${this.escapeHtml(template.description)}</p>` : ''}

                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div>
                            <p class="text-slate-gray-600">Kategorií</p>
                            <p class="font-semibold text-slate-gray-900">${categoryCount}</p>
                        </div>
                        <div>
                            <p class="text-slate-gray-600">Celková částka</p>
                            <p class="font-semibold text-slate-gray-900">${this.formatAmount(totalAmount)}</p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" class="btn btn-primary btn-sm flex-1 view-template-btn" data-template-id="${template.id}" aria-label="Zobrazit detail šablony">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Detail
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm flex-1 apply-template-btn" data-template-id="${template.id}" aria-label="Použít šablonu">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Použít
                        </button>
                        ${!template.is_system ? `
                            <button type="button" class="btn btn-secondary btn-sm edit-template-btn" data-template-id="${template.id}" aria-label="Upravit šablonu">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm delete-template-btn" data-template-id="${template.id}" aria-label="Smazat šablonu">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    attachTemplateCardEvents() {
        // View template
        document.querySelectorAll('.view-template-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const templateId = parseInt(e.target.closest('button').dataset.templateId);
                this.viewTemplate(templateId);
            });
        });

        // Apply template
        document.querySelectorAll('.apply-template-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const templateId = parseInt(e.target.closest('button').dataset.templateId);
                this.openApplyModal(templateId);
            });
        });

        // Edit template
        document.querySelectorAll('.edit-template-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const templateId = parseInt(e.target.closest('button').dataset.templateId);
                this.editTemplate(templateId);
            });
        });

        // Delete template
        document.querySelectorAll('.delete-template-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const templateId = parseInt(e.target.closest('button').dataset.templateId);
                this.deleteTemplate(templateId);
            });
        });
    }

    openCreateModal() {
        const modal = document.getElementById('edit-template-modal');
        const form = document.getElementById('template-form');

        form.reset();
        document.getElementById('template-id').value = '';
        document.getElementById('edit-template-modal-title').textContent = 'Vytvořit Šablonu';

        // Clear categories
        document.getElementById('categories-container').innerHTML = '';
        this.categoryIndex = 0;

        // Add one default category
        this.addCategoryRow();

        this.openModal(modal);
    }

    viewTemplate(templateId) {
        const template = this.templates.find(t => t.id === templateId);
        if (!template) return;

        const modal = document.getElementById('template-modal');
        const detailContainer = document.getElementById('template-detail');

        const totalAmount = template.categories?.reduce((sum, c) => sum + parseFloat(c.amount || 0), 0) || 0;

        detailContainer.innerHTML = `
            <div class="space-y-6">
                <div>
                    <h4 class="font-semibold text-slate-gray-900 mb-2">${this.escapeHtml(template.name)}</h4>
                    ${template.description ? `<p class="text-sm text-slate-gray-600">${this.escapeHtml(template.description)}</p>` : ''}
                </div>

                <div class="bg-slate-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-slate-gray-900 mb-3">Kategorie (${template.categories?.length || 0})</h4>
                    <div class="space-y-2">
                        ${template.categories?.map(cat => `
                            <div class="flex justify-between items-center bg-white p-3 rounded">
                                <span class="font-medium">${this.escapeHtml(cat.category_name || cat.name || 'Kategorie')}</span>
                                <span class="font-semibold">${this.formatAmount(cat.amount)}${cat.percentage ? ` (${cat.percentage}%)` : ''}</span>
                            </div>
                        `).join('') || '<p class="text-slate-gray-600">Žádné kategorie</p>'}
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4 border-t">
                    <span class="font-semibold text-slate-gray-900">Celkem:</span>
                    <span class="text-xl font-bold text-primary-600">${this.formatAmount(totalAmount)}</span>
                </div>
            </div>
        `;

        this.selectedTemplateId = templateId;
        document.getElementById('export-btn').disabled = false;

        this.openModal(modal);
    }

    editTemplate(templateId) {
        const template = this.templates.find(t => t.id === templateId);
        if (!template) return;

        const modal = document.getElementById('edit-template-modal');
        const form = document.getElementById('template-form');

        // Populate form
        document.getElementById('template-id').value = template.id;
        document.getElementById('template-name').value = template.name;
        document.getElementById('template-type').value = template.template_type;
        document.getElementById('template-description').value = template.description || '';
        document.getElementById('edit-template-modal-title').textContent = 'Upravit Šablonu';

        // Clear and populate categories
        const container = document.getElementById('categories-container');
        container.innerHTML = '';
        this.categoryIndex = 0;

        template.categories?.forEach(cat => {
            this.addCategoryRow(cat);
        });

        if ((template.categories?.length || 0) === 0) {
            this.addCategoryRow();
        }

        this.updateSummary();
        this.openModal(modal);
    }

    async deleteTemplate(templateId) {
        if (!confirm('Opravdu chcete smazat tuto šablonu?')) return;

        try {
            const response = await fetch(`/budgets/templates/${templateId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Failed to delete template');
            }

            this.showAlert('Šablona byla smazána', 'success');
            await this.loadTemplates();

        } catch (error) {
            this.showAlert(error.message, 'error');
        }
    }

    addCategoryRow(data = null) {
        const container = document.getElementById('categories-container');
        const index = this.categoryIndex++;

        const row = document.createElement('div');
        row.className = 'category-row border border-slate-gray-200 rounded-lg p-4';
        row.dataset.categoryIndex = index;

        row.innerHTML = `
            <div class="grid grid-cols-12 gap-3 items-start">
                <div class="col-span-12 md:col-span-5">
                    <label class="form-label form-label-required">Kategorie</label>
                    <select name="categories[${index}][category_id]" class="form-input category-select" required aria-required="true">
                        <option value="">Vyberte kategorii...</option>
                        ${this.categories.map(cat =>
                            `<option value="${cat.id}" ${data?.category_id == cat.id ? 'selected' : ''}>${this.escapeHtml(cat.name)}</option>`
                        ).join('')}
                    </select>
                </div>
                <div class="col-span-6 md:col-span-3">
                    <label class="form-label form-label-required">Částka</label>
                    <input type="number" name="categories[${index}][amount]" class="form-input category-amount"
                           required aria-required="true" min="0" step="0.01" value="${data?.amount || ''}" placeholder="0.00">
                </div>
                <div class="col-span-4 md:col-span-2">
                    <label class="form-label">% z příjmu</label>
                    <input type="number" name="categories[${index}][percentage]" class="form-input category-percentage"
                           min="0" max="100" step="0.1" value="${data?.percentage || ''}" placeholder="0">
                </div>
                <div class="col-span-2 md:col-span-2 flex items-end">
                    <button type="button" class="btn btn-danger btn-sm w-full remove-category-btn" aria-label="Odstranit kategorii">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        container.appendChild(row);
        this.updateRemoveButtons();
        this.updateSummary();
    }

    removeCategoryRow(row) {
        const rows = document.querySelectorAll('.category-row');

        if (rows.length <= 1) {
            this.showAlert('Musí existovat alespoň jedna kategorie', 'warning');
            return;
        }

        row.remove();
        this.updateRemoveButtons();
        this.updateSummary();
    }

    updateRemoveButtons() {
        const rows = document.querySelectorAll('.category-row');
        const buttons = document.querySelectorAll('.remove-category-btn');

        buttons.forEach(btn => {
            btn.disabled = rows.length <= 1;
        });
    }

    updateSummary() {
        const amounts = Array.from(document.querySelectorAll('.category-amount'))
            .map(input => parseFloat(input.value) || 0);

        const percentages = Array.from(document.querySelectorAll('.category-percentage'))
            .map(input => parseFloat(input.value) || 0);

        const totalAmount = amounts.reduce((sum, val) => sum + val, 0);
        const totalPercentage = percentages.reduce((sum, val) => sum + val, 0);
        const categoryCount = document.querySelectorAll('.category-row').length;

        document.getElementById('summary-categories').textContent = categoryCount;
        document.getElementById('summary-total').textContent = this.formatAmount(totalAmount);
        document.getElementById('summary-percentage').textContent = totalPercentage.toFixed(1) + '%';
    }

    async saveTemplate(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const templateId = formData.get('id');

        // Collect categories
        const categories = [];
        let index = 0;

        while (formData.has(`categories[${index}][category_id]`)) {
            const categoryId = formData.get(`categories[${index}][category_id]`);
            const amount = formData.get(`categories[${index}][amount]`);
            const percentage = formData.get(`categories[${index}][percentage]`);

            if (categoryId && amount) {
                categories.push({
                    category_id: parseInt(categoryId),
                    amount: parseFloat(amount),
                    percentage: percentage ? parseFloat(percentage) : null
                });
            }

            index++;
        }

        if (categories.length === 0) {
            this.showAlert('Přidejte alespoň jednu kategorii', 'error');
            return;
        }

        const payload = {
            name: formData.get('name'),
            template_type: formData.get('template_type'),
            description: formData.get('description') || '',
            categories: categories
        };

        this.showLoading('save-template-text', 'save-template-loading', true);

        try {
            const url = templateId ? `/budgets/templates/${templateId}` : '/budgets/templates';
            const method = templateId ? 'PUT' : 'POST';

            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Failed to save template');
            }

            this.showAlert(templateId ? 'Šablona byla aktualizována' : 'Šablona byla vytvořena', 'success');

            const modal = document.getElementById('edit-template-modal');
            this.closeModal(modal);

            await this.loadTemplates();

        } catch (error) {
            this.showAlert(error.message, 'error');
            this.showLoading('save-template-text', 'save-template-loading', false);
        }
    }

    openApplyModal(templateId) {
        const template = this.templates.find(t => t.id === templateId);
        if (!template) return;

        const modal = document.getElementById('apply-modal');
        document.getElementById('apply-template-id').value = templateId;

        // Set default month to current
        const now = new Date();
        const monthString = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
        document.getElementById('apply-month').value = monthString;

        document.getElementById('apply-income').value = '';

        this.openModal(modal);
    }

    async applyTemplate(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const templateId = formData.get('template_id');
        const month = formData.get('month');
        const income = formData.get('income');

        const payload = {
            month: month,
            income: income ? parseFloat(income) : null
        };

        this.showLoading('apply-text', 'apply-loading', true);

        try {
            const response = await fetch(`/budgets/templates/${templateId}/apply`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.error || 'Failed to apply template');
            }

            this.showAlert('Šablona byla aplikována na rozpočet', 'success');

            const modal = document.getElementById('apply-modal');
            this.closeModal(modal);

            // Redirect to budget page after delay
            setTimeout(() => {
                window.location.href = '/budgets';
            }, 1500);

        } catch (error) {
            this.showAlert(error.message, 'error');
            this.showLoading('apply-text', 'apply-loading', false);
        }
    }

    exportTemplate() {
        if (!this.selectedTemplateId) return;

        const template = this.templates.find(t => t.id === this.selectedTemplateId);
        if (!template) return;

        // Prepare export data
        const exportData = {
            name: template.name,
            template_type: template.template_type,
            description: template.description,
            categories: template.categories.map(cat => ({
                category_name: cat.category_name || cat.name,
                amount: cat.amount,
                percentage: cat.percentage
            })),
            exported_at: new Date().toISOString(),
            version: '1.0'
        };

        const json = JSON.stringify(exportData, null, 2);
        const filename = `budget-template-${template.name.toLowerCase().replace(/\s+/g, '-')}.json`;

        this.downloadFile(filename, json, 'application/json');
        this.showAlert('Šablona byla exportována', 'success');
    }

    async importTemplate(e) {
        const file = e.target.files[0];
        if (!file) return;

        document.getElementById('import-filename').textContent = file.name;

        try {
            const content = await file.text();
            const data = JSON.parse(content);

            // Validate structure
            if (!data.name || !data.template_type || !Array.isArray(data.categories)) {
                throw new Error('Neplatný formát šablony');
            }

            // Map categories to system categories
            const categoryMapping = {};
            for (const cat of data.categories) {
                const match = this.categories.find(c =>
                    c.name.toLowerCase() === cat.category_name.toLowerCase()
                );
                if (match) {
                    categoryMapping[cat.category_name] = match.id;
                }
            }

            // Create new template
            const payload = {
                name: data.name + ' (Import)',
                template_type: data.template_type,
                description: data.description || '',
                categories: data.categories
                    .filter(cat => categoryMapping[cat.category_name])
                    .map(cat => ({
                        category_id: categoryMapping[cat.category_name],
                        amount: parseFloat(cat.amount),
                        percentage: cat.percentage ? parseFloat(cat.percentage) : null
                    }))
            };

            if (payload.categories.length === 0) {
                throw new Error('Žádné kategorie nebyly namapovány. Zkontrolujte názvy kategorií.');
            }

            const response = await fetch('/budgets/templates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const result = await response.json();
                throw new Error(result.error || 'Failed to import template');
            }

            this.showAlert('Šablona byla importována', 'success');
            await this.loadTemplates();

            // Switch to user templates tab
            this.switchTab('user');

        } catch (error) {
            this.showAlert(error.message, 'error');
        } finally {
            // Reset file input
            e.target.value = '';
            setTimeout(() => {
                document.getElementById('import-filename').textContent = 'Žádný soubor vybrán';
            }, 3000);
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

    downloadFile(filename, content, mimeType = 'text/plain') {
        const blob = new Blob([content], { type: mimeType });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize
let templatesUI;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        templatesUI = new BudgetTemplatesUI();
    });
} else {
    templatesUI = new BudgetTemplatesUI();
}
