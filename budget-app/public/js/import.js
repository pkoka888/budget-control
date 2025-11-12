/**
 * Import/Export UI Controller
 * Handles CSV import, bank JSON import, and data export
 */

class ImportExportUI {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.uploadedData = null;

        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Tab switching
        document.getElementById('tab-csv')?.addEventListener('click', () => this.switchTab('csv'));
        document.getElementById('tab-bank')?.addEventListener('click', () => this.switchTab('bank'));
        document.getElementById('tab-export')?.addEventListener('click', () => this.switchTab('export'));

        // CSV upload
        document.getElementById('csv-upload-form')?.addEventListener('submit', (e) => this.uploadCSV(e));

        // CSV mapping
        document.getElementById('csv-mapping-form')?.addEventListener('submit', (e) => this.processCSVImport(e));

        // Bank upload
        document.getElementById('bank-upload-form')?.addEventListener('submit', (e) => this.uploadBankJSON(e));

        // Exports
        document.getElementById('export-transactions-form')?.addEventListener('submit', (e) => this.exportTransactions(e));
        document.getElementById('full-export-btn')?.addEventListener('click', () => this.fullExport());
    }

    switchTab(tab) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-selected', 'false');
        });

        document.getElementById(`tab-${tab}`)?.classList.add('active');
        document.getElementById(`tab-${tab}`)?.setAttribute('aria-selected', 'true');

        // Show/hide panels
        document.getElementById('panel-csv')?.classList.toggle('hidden', tab !== 'csv');
        document.getElementById('panel-bank')?.classList.toggle('hidden', tab !== 'bank');
        document.getElementById('panel-export')?.classList.toggle('hidden', tab !== 'export');
    }

    async uploadCSV(e) {
        e.preventDefault();

        const formData = new FormData(e.target);

        this.showLoading('upload-csv-text', 'upload-csv-loading', true);

        try {
            const response = await fetch('/import/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Upload failed');
            }

            const data = await response.json();
            this.uploadedData = data;

            // Show mapping step
            this.showMappingStep(data);

        } catch (error) {
            alert('Chyba při nahrávání: ' + error.message);
            this.showLoading('upload-csv-text', 'upload-csv-loading', false);
        }
    }

    showMappingStep(data) {
        document.getElementById('csv-mapping-step')?.classList.remove('hidden');
        document.getElementById('upload-id')?.setAttribute('value', data.upload_id);

        // Populate mapping fields
        const container = document.getElementById('mapping-fields');
        if (!container) return;

        const requiredFields = [
            { key: 'date', label: 'Datum', required: true },
            { key: 'amount', label: 'Částka', required: true },
            { key: 'description', label: 'Popis', required: false },
            { key: 'category', label: 'Kategorie', required: false },
            { key: 'account', label: 'Účet', required: false }
        ];

        const columns = data.columns || [];

        container.innerHTML = requiredFields.map(field => `
            <div class="grid grid-cols-3 gap-4 items-center">
                <label class="text-sm font-medium text-slate-gray-700">
                    ${field.label}
                    ${field.required ? '<span class="text-red-600">*</span>' : ''}
                </label>
                <select name="mapping[${field.key}]" class="form-input col-span-2" ${field.required ? 'required' : ''}>
                    <option value="">-- Vyberte sloupec --</option>
                    ${columns.map((col, idx) => `
                        <option value="${idx}" ${this.guessMapping(field.key, col) ? 'selected' : ''}>${col}</option>
                    `).join('')}
                </select>
            </div>
        `).join('');

        // Show preview
        this.showPreview(data.preview);

        // Scroll to mapping
        document.getElementById('csv-mapping-step')?.scrollIntoView({ behavior: 'smooth' });
    }

    guessMapping(fieldKey, columnName) {
        const lower = columnName.toLowerCase();

        const mappings = {
            'date': ['date', 'datum', 'transaction date', 'transaction_date'],
            'amount': ['amount', 'částka', 'castka', 'value', 'hodnota'],
            'description': ['description', 'popis', 'note', 'poznámka', 'poznámka'],
            'category': ['category', 'kategorie', 'type', 'typ'],
            'account': ['account', 'účet', 'ucet']
        };

        return mappings[fieldKey]?.some(keyword => lower.includes(keyword));
    }

    showPreview(previewData) {
        const container = document.getElementById('csv-preview');
        if (!container || !previewData || previewData.length === 0) return;

        const headers = Object.keys(previewData[0]);
        const rows = previewData.slice(0, 5); // Show first 5 rows

        container.innerHTML = `
            <table class="min-w-full divide-y divide-slate-gray-200">
                <thead class="bg-slate-gray-50">
                    <tr>
                        ${headers.map(h => `<th class="px-4 py-2 text-left text-xs font-medium text-slate-gray-500 uppercase">${h}</th>`).join('')}
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-gray-200">
                    ${rows.map(row => `
                        <tr>
                            ${headers.map(h => `<td class="px-4 py-2 text-sm text-slate-gray-900">${row[h] || ''}</td>`).join('')}
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    async processCSVImport(e) {
        e.preventDefault();

        const formData = new FormData(e.target);

        this.showLoading('import-csv-text', 'import-csv-loading', true);

        try {
            const response = await fetch('/import/process', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Import failed');
            }

            const result = await response.json();

            alert(`Import dokončen!\n\nImportováno: ${result.imported}\nPřeskočeno duplicit: ${result.skipped}`);

            // Redirect to transactions
            window.location.href = '/transactions';

        } catch (error) {
            alert('Chyba při importu: ' + error.message);
            this.showLoading('import-csv-text', 'import-csv-loading', false);
        }
    }

    async uploadBankJSON(e) {
        e.preventDefault();

        const formData = new FormData(e.target);

        this.showLoading('upload-bank-text', 'upload-bank-loading', true);

        try {
            const response = await fetch('/bank-import/import-file', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': this.csrfToken
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.error || 'Import failed');
            }

            const result = await response.json();

            const resultsContainer = document.getElementById('bank-import-results');
            if (resultsContainer) {
                resultsContainer.classList.remove('hidden');
                resultsContainer.innerHTML = `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h4 class="font-semibold text-green-900 mb-2">Import úspěšný</h4>
                        <p class="text-sm text-green-700">Importováno transakcí: ${result.imported || 0}</p>
                        ${result.skipped ? `<p class="text-sm text-green-700">Přeskočeno duplicit: ${result.skipped}</p>` : ''}
                    </div>
                `;
            }

            // Reset form
            e.target.reset();

        } catch (error) {
            alert('Chyba při importu: ' + error.message);
        } finally {
            this.showLoading('upload-bank-text', 'upload-bank-loading', false);
        }
    }

    async exportTransactions(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const format = formData.get('format');
        const startDate = formData.get('start_date');
        const endDate = formData.get('end_date');

        let url = `/transactions/export/${format}?`;
        if (startDate) url += `start_date=${startDate}&`;
        if (endDate) url += `end_date=${endDate}&`;

        // Download file
        window.location.href = url;
    }

    async fullExport() {
        if (!confirm('Exportovat všechna data? Může to trvat několik minut.')) return;

        try {
            const response = await fetch('/settings/export', {
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            if (!response.ok) {
                throw new Error('Export failed');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `budget-control-export-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

        } catch (error) {
            alert('Chyba při exportu: ' + error.message);
        }
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
}

// Initialize
let importExportUI;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        importExportUI = new ImportExportUI();
    });
} else {
    importExportUI = new ImportExportUI();
}
