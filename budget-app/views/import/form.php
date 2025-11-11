<?php
/**
 * CSV Import Form
 */
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-2">Import transakcí z CSV</h2>
        <p class="text-gray-600">Nahrajte CSV soubor s transakcemi z vaší banky</p>
    </div>

    <!-- Upload Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <form id="import-form" class="space-y-4">
            <!-- Account Selection -->
            <div class="form-group">
                <label for="account_id" class="form-label">Vyberte účet</label>
                <select id="account_id" name="account_id" required class="form-select w-full">
                    <option value="">-- Vyberte účet --</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo htmlspecialchars($account['id']); ?>">
                            <?php echo htmlspecialchars($account['name']); ?>
                            (<?php echo htmlspecialchars($account['type']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- File Upload -->
            <div class="form-group">
                <label for="csv_file" class="form-label">Vyberte CSV soubor</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 transition" id="drop-zone">
                    <input type="file" id="csv_file" name="csv_file" accept=".csv" required class="hidden">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-12l-3.172-3.172a4 4 0 00-5.656 0L28 20M12 20v16m16-4v4m0-16l3.172-3.172a4 4 0 015.656 0L44 20" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-600">
                        <span class="font-medium text-blue-600 hover:text-blue-500">Klikněte pro výběr souboru</span> nebo přetáhněte sem
                    </p>
                    <p class="text-xs text-gray-500 mt-1">CSV (max 10MB)</p>
                    <p id="file-name" class="mt-2 text-sm font-medium text-gray-900"></p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3">
                <button type="submit" class="btn btn-primary">
                    Nahrát a náhled
                </button>
                <a href="/transactions" class="btn btn-secondary">
                    Zrušit
                </a>
            </div>
        </form>
    </div>

    <!-- Preview Section (hidden by default) -->
    <div id="preview-section" class="hidden bg-white rounded-lg shadow p-6 space-y-4">
        <h3 class="text-xl font-bold">Náhled importu</h3>
        <p id="preview-count" class="text-sm text-gray-600"></p>

        <div id="preview-table" class="overflow-x-auto">
            <!-- Preview will be inserted here -->
        </div>

        <div class="flex gap-3">
            <button id="confirm-button" class="btn btn-primary">
                Potvrdit import
            </button>
            <button id="cancel-preview" class="btn btn-secondary">
                Zrušit
            </button>
        </div>
    </div>

    <!-- Status Messages -->
    <div id="success-message" class="hidden bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <p id="success-text"></p>
    </div>

    <div id="error-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <p id="error-text"></p>
    </div>
</div>

<style>
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--text-primary);
    }

    .form-select {
        padding: 0.625rem;
        border: 1px solid var(--border-primary);
        border-radius: 0.375rem;
        font-size: 1rem;
        background-color: var(--bg-input);
        color: var(--text-primary);
        transition: border-color 0.2s;
    }

    .form-select:focus {
        outline: none;
        border-color: var(--border-focus);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('import-form');
    const fileInput = document.getElementById('csv_file');
    const dropZone = document.getElementById('drop-zone');
    const fileName = document.getElementById('file-name');
    const previewSection = document.getElementById('preview-section');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');

    // File selection handling
    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            fileName.textContent = files[0].name;
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            fileName.textContent = e.target.files[0].name;
        }
    });

    // Form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const accountId = document.getElementById('account_id').value;
        const file = fileInput.files[0];

        if (!accountId) {
            showError('Vyberte prosím účet');
            return;
        }

        if (!file) {
            showError('Vyberte prosím CSV soubor');
            return;
        }

        // Upload file
        const formData = new FormData();
        formData.append('account_id', accountId);
        formData.append('csv_file', file);

        try {
            const response = await fetch('/import/upload', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!response.ok) {
                showError(data.error || 'Chyba při nahrávání souboru');
                return;
            }

            // Show preview
            showPreview(data);
        } catch (error) {
            showError('Chyba: ' + error.message);
        }
    });

    // Confirm import
    document.getElementById('confirm-button').addEventListener('click', async () => {
        try {
            const response = await fetch('/import/process', {
                method: 'POST'
            });

            const data = await response.json();

            if (!response.ok) {
                showError(data.error || 'Chyba při importu');
                return;
            }

            showSuccess(`Importováno ${data.imported} transakcí`);
            form.reset();
            previewSection.classList.add('hidden');

            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = '/transactions';
            }, 2000);
        } catch (error) {
            showError('Chyba: ' + error.message);
        }
    });

    // Cancel preview
    document.getElementById('cancel-preview').addEventListener('click', () => {
        previewSection.classList.add('hidden');
        form.reset();
        fileName.textContent = '';
    });

    function showPreview(data) {
        const count = document.getElementById('preview-count');
        const table = document.getElementById('preview-table');

        count.textContent = `Připraveno k importu: ${data.count} transakcí`;

        let html = '<table class="w-full border-collapse border border-gray-300"><thead><tr class="bg-gray-100">';
        html += '<th class="border border-gray-300 p-2 text-left">Datum</th>';
        html += '<th class="border border-gray-300 p-2 text-left">Popis</th>';
        html += '<th class="border border-gray-300 p-2 text-right">Částka</th>';
        html += '</tr></thead><tbody>';

        data.preview.forEach(tx => {
            html += `<tr>
                <td class="border border-gray-300 p-2">${tx.date}</td>
                <td class="border border-gray-300 p-2">${htmlEscape(tx.description)}</td>
                <td class="border border-gray-300 p-2 text-right">${tx.amount}</td>
            </tr>`;
        });

        html += '</tbody></table>';
        table.innerHTML = html;
        previewSection.classList.remove('hidden');
    }

    function showError(message) {
        successMessage.classList.add('hidden');
        errorMessage.classList.remove('hidden');
        document.getElementById('error-text').textContent = message;
    }

    function showSuccess(message) {
        errorMessage.classList.add('hidden');
        successMessage.classList.remove('hidden');
        document.getElementById('success-text').textContent = message;
    }

    function htmlEscape(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
