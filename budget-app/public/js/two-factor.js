/**
 * Two-Factor Authentication UI Controller
 * Handles all 2FA setup, verification, and management interactions
 */

class TwoFactorUI {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        this.setupData = null;

        this.init();
    }

    init() {
        this.bindEvents();
        this.checkInitialState();
    }

    bindEvents() {
        // Setup 2FA
        document.getElementById('setup-2fa-btn')?.addEventListener('click', () => this.startSetup());
        document.getElementById('continue-to-verify-btn')?.addEventListener('click', () => this.showVerifyStep());
        document.getElementById('verify-totp-form')?.addEventListener('submit', (e) => this.verifyAndEnable(e));

        // Disable 2FA
        document.getElementById('disable-2fa-btn')?.addEventListener('click', () => this.showDisableModal());
        document.getElementById('disable-2fa-form')?.addEventListener('submit', (e) => this.disable2FA(e));

        // Backup codes
        document.getElementById('regenerate-backup-codes-btn')?.addEventListener('click', () => this.showRegenerateModal());
        document.getElementById('regenerate-form')?.addEventListener('submit', (e) => this.regenerateBackupCodes(e));
        document.getElementById('download-backup-codes-btn')?.addEventListener('click', () => this.downloadBackupCodes());
        document.getElementById('download-new-codes-btn')?.addEventListener('click', () => this.downloadNewBackupCodes());

        // Trusted devices
        document.getElementById('manage-devices-btn')?.addEventListener('click', () => this.showDevicesModal());

        // Copy secret
        document.getElementById('copy-secret-btn')?.addEventListener('click', () => this.copySecret());

        // Finish setup
        document.getElementById('finish-setup-btn')?.addEventListener('click', () => this.finishSetup());

        // Modal close buttons
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                this.closeModal(modal.id);
            });
        });

        // TOTP input auto-formatting
        const totpInput = document.getElementById('totp-code');
        if (totpInput) {
            totpInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/\D/g, '').slice(0, 6);
            });
        }
    }

    checkInitialState() {
        // Check if there are any alerts to show from URL params
        const params = new URLSearchParams(window.location.search);
        if (params.get('setup') === 'success') {
            this.showAlert('2FA bylo úspěšně aktivováno!', 'success');
        }
    }

    async startSetup() {
        this.showLoading('setup-2fa-btn', true);

        try {
            const response = await fetch('/api/2fa/setup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to setup 2FA');
            }

            this.setupData = data.data;
            this.displaySetupModal(data.data);

        } catch (error) {
            this.showAlert(error.message, 'error');
        } finally {
            this.showLoading('setup-2fa-btn', false);
        }
    }

    displaySetupModal(setupData) {
        // Show QR code
        document.getElementById('qr-code').src = setupData.qr_code_url;
        document.getElementById('secret-code').textContent = setupData.manual_entry;

        // Show modal
        this.showModal('setup-modal');

        // Show step 1
        document.getElementById('setup-step-1').classList.remove('hidden');
        document.getElementById('setup-step-2').classList.add('hidden');
        document.getElementById('setup-step-3').classList.add('hidden');
    }

    showVerifyStep() {
        document.getElementById('setup-step-1').classList.add('hidden');
        document.getElementById('setup-step-2').classList.remove('hidden');

        // Focus on TOTP input
        setTimeout(() => {
            document.getElementById('totp-code').focus();
        }, 100);
    }

    async verifyAndEnable(e) {
        e.preventDefault();

        const totpCode = document.getElementById('totp-code').value;

        if (totpCode.length !== 6) {
            this.showAlert('Zadejte platný 6-místný kód', 'error');
            return;
        }

        this.showLoading('verify-btn', true);

        try {
            const response = await fetch('/api/2fa/enable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ totp_code: totpCode })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Ověření selhalo');
            }

            // Show backup codes
            this.displayBackupCodes(data.backup_codes);

            // Show step 3
            document.getElementById('setup-step-2').classList.add('hidden');
            document.getElementById('setup-step-3').classList.remove('hidden');

        } catch (error) {
            this.showAlert(error.message, 'error');
            document.getElementById('totp-code').value = '';
            document.getElementById('totp-code').focus();
        } finally {
            this.showLoading('verify-btn', false);
        }
    }

    displayBackupCodes(codes) {
        const container = document.getElementById('backup-codes-list');
        container.innerHTML = '';

        codes.forEach(code => {
            const codeEl = document.createElement('div');
            codeEl.className = 'bg-white px-3 py-2 rounded border border-slate-gray-300 font-mono text-sm text-center';
            codeEl.textContent = code;
            container.appendChild(codeEl);
        });

        this.backupCodes = codes;
    }

    downloadBackupCodes() {
        if (!this.backupCodes) return;

        const content = 'Budget Control - 2FA Backup Codes\n' +
                       'Generated: ' + new Date().toLocaleString('cs-CZ') + '\n\n' +
                       'Keep these codes in a safe place. Each code can only be used once.\n\n' +
                       this.backupCodes.join('\n') + '\n';

        this.downloadFile('budget-control-backup-codes.txt', content);
    }

    downloadNewBackupCodes() {
        if (!this.newBackupCodes) return;

        const content = 'Budget Control - 2FA Backup Codes (Regenerated)\n' +
                       'Generated: ' + new Date().toLocaleString('cs-CZ') + '\n\n' +
                       'Keep these codes in a safe place. Each code can only be used once.\n' +
                       'Your old backup codes are no longer valid.\n\n' +
                       this.newBackupCodes.join('\n') + '\n';

        this.downloadFile('budget-control-backup-codes-new.txt', content);
    }

    downloadFile(filename, content) {
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    finishSetup() {
        this.closeModal('setup-modal');
        window.location.reload();
    }

    showDisableModal() {
        this.showModal('disable-modal');
        setTimeout(() => {
            document.getElementById('disable-password').focus();
        }, 100);
    }

    async disable2FA(e) {
        e.preventDefault();

        const password = document.getElementById('disable-password').value;
        const submitBtn = e.target.querySelector('button[type="submit"]');

        this.showLoading(submitBtn, true);

        try {
            const response = await fetch('/api/2fa/disable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ password })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Deaktivace selhala');
            }

            this.showAlert('2FA bylo úspěšně deaktivováno', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);

        } catch (error) {
            this.showAlert(error.message, 'error');
        } finally {
            this.showLoading(submitBtn, false);
        }
    }

    showRegenerateModal() {
        this.showModal('regenerate-modal');
        document.getElementById('new-backup-codes').classList.add('hidden');
        document.getElementById('regenerate-form').reset();
        setTimeout(() => {
            document.getElementById('regenerate-password').focus();
        }, 100);
    }

    async regenerateBackupCodes(e) {
        e.preventDefault();

        const password = document.getElementById('regenerate-password').value;
        const submitBtn = e.target.querySelector('button[type="submit"]');

        this.showLoading(submitBtn, true);

        try {
            const response = await fetch('/api/2fa/backup-codes/regenerate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ password })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Generování selhalo');
            }

            // Display new backup codes
            const container = document.getElementById('new-backup-codes-list');
            container.innerHTML = '';

            data.backup_codes.forEach(code => {
                const codeEl = document.createElement('div');
                codeEl.className = 'bg-white px-3 py-2 rounded border border-slate-gray-300 font-mono text-sm text-center';
                codeEl.textContent = code;
                container.appendChild(codeEl);
            });

            this.newBackupCodes = data.backup_codes;

            document.getElementById('regenerate-form').classList.add('hidden');
            document.getElementById('new-backup-codes').classList.remove('hidden');

            this.showAlert('Nové záložní kódy byly vygenerovány', 'success');

        } catch (error) {
            this.showAlert(error.message, 'error');
        } finally {
            this.showLoading(submitBtn, false);
        }
    }

    async showDevicesModal() {
        this.showModal('devices-modal');

        try {
            const response = await fetch('/api/2fa/devices', {
                headers: {
                    'X-CSRF-Token': this.csrfToken
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to load devices');
            }

            this.displayDevices(data.devices);

        } catch (error) {
            this.showAlert(error.message, 'error');
        }
    }

    displayDevices(devices) {
        const container = document.getElementById('devices-list');

        if (devices.length === 0) {
            container.innerHTML = '<p class="text-slate-gray-600 text-center py-4">Žádná důvěryhodná zařízení</p>';
            return;
        }

        container.innerHTML = devices.map(device => `
            <div class="bg-slate-gray-50 p-4 rounded-lg">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 mr-2 text-slate-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <h4 class="font-semibold text-slate-gray-900">Zařízení</h4>
                        </div>
                        <p class="text-sm text-slate-gray-600 mb-1">
                            <strong>IP:</strong> ${this.escapeHtml(device.ip_address)}
                        </p>
                        <p class="text-sm text-slate-gray-600 mb-1">
                            <strong>Vytvořeno:</strong> ${this.formatDate(device.created_at)}
                        </p>
                        <p class="text-sm text-slate-gray-600 mb-1">
                            <strong>Poslední použití:</strong> ${this.formatDate(device.last_used_at)}
                        </p>
                        <p class="text-sm text-slate-gray-600">
                            <strong>Vyprší:</strong> ${this.formatDate(device.expires_at)}
                        </p>
                    </div>
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            onclick="twoFactorUI.revokeDevice(${device.id})"
                            aria-label="Odvolat zařízení">
                        Odvolat
                    </button>
                </div>
            </div>
        `).join('');
    }

    async revokeDevice(sessionId) {
        if (!confirm('Opravdu chcete odvolat toto zařízení?')) {
            return;
        }

        try {
            const response = await fetch('/api/2fa/devices/revoke', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({ session_id: sessionId })
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Odvolání selhalo');
            }

            this.showAlert('Zařízení bylo odvoláno', 'success');
            this.showDevicesModal(); // Reload devices

        } catch (error) {
            this.showAlert(error.message, 'error');
        }
    }

    copySecret() {
        const secretCode = document.getElementById('secret-code').textContent;

        navigator.clipboard.writeText(secretCode.replace(/\s/g, '')).then(() => {
            const btn = document.getElementById('copy-secret-btn');
            const originalHTML = btn.innerHTML;

            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            btn.classList.add('btn-success');

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-success');
            }, 2000);
        }).catch(() => {
            this.showAlert('Kopírování selhalo', 'error');
        });
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');

            // Focus trap
            const focusableElements = modal.querySelectorAll('button, input, select, textarea, [href], [tabindex]:not([tabindex="-1"])');
            if (focusableElements.length > 0) {
                focusableElements[0].focus();
            }
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
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    ${icon}
                </svg>
                ${this.escapeHtml(message)}
            </div>
        `;

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }

    showLoading(element, loading) {
        const el = typeof element === 'string' ? document.getElementById(element) : element;
        if (!el) return;

        if (loading) {
            el.disabled = true;
            el.setAttribute('aria-busy', 'true');

            const loadingSpan = el.querySelector('[id$="-loading"]');
            const textSpan = el.querySelector('[id$="-text"]');

            if (loadingSpan && textSpan) {
                loadingSpan.classList.remove('hidden');
                textSpan.classList.add('hidden');
            } else {
                el.dataset.originalText = el.textContent;
                el.textContent = 'Načítání...';
            }
        } else {
            el.disabled = false;
            el.setAttribute('aria-busy', 'false');

            const loadingSpan = el.querySelector('[id$="-loading"]');
            const textSpan = el.querySelector('[id$="-text"]');

            if (loadingSpan && textSpan) {
                loadingSpan.classList.add('hidden');
                textSpan.classList.remove('hidden');
            } else if (el.dataset.originalText) {
                el.textContent = el.dataset.originalText;
            }
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('cs-CZ', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

// Initialize on DOM ready
let twoFactorUI;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        twoFactorUI = new TwoFactorUI();
    });
} else {
    twoFactorUI = new TwoFactorUI();
}
