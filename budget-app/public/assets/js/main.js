// Budget Control - Main JavaScript

/**
 * Theme Management
 */
class ThemeManager {
    constructor() {
        this.initTheme();
        this.setupThemeToggle();
        this.updateThemeIcon();
    }

    initTheme() {
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

        // Priority: saved theme > system preference > light
        let theme = 'light';
        if (savedTheme) {
            theme = savedTheme;
        } else if (prefersDark) {
            theme = 'dark';
        }

        this.setTheme(theme);

        // Listen for system preference changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            // Only auto-switch if no saved preference
            if (!localStorage.getItem('theme')) {
                this.setTheme(e.matches ? 'dark' : 'light');
            }
        });
    }

    setupThemeToggle() {
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => this.toggleTheme());
        }
    }

    toggleTheme() {
        const current = this.getCurrentTheme();
        const next = current === 'light' ? 'dark' : 'light';
        this.setTheme(next);
        localStorage.setItem('theme', next);
    }

    setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        this.updateThemeIcon();
    }

    getCurrentTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }

    updateThemeIcon() {
        const toggle = document.getElementById('theme-toggle');
        if (!toggle) return;

        const isDark = this.getCurrentTheme() === 'dark';
        const icon = toggle.querySelector('svg');

        if (isDark) {
            // Moon icon for dark mode
            icon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            `;
        } else {
            // Sun icon for light mode
            icon.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            `;
        }
    }
}

/**
 * Form Validation
 */
class FormValidator {
    static validate(formElement, rules) {
        const errors = {};
        const formData = new FormData(formElement);

        for (const [field, fieldRules] of Object.entries(rules)) {
            const value = formData.get(field);
            const fieldErrors = [];

            for (const rule of fieldRules.split('|')) {
                if (rule === 'required' && !value) {
                    fieldErrors.push(`${field} je povinné`);
                } else if (rule === 'email' && value && !this.isValidEmail(value)) {
                    fieldErrors.push(`${field} není platný e-mail`);
                } else if (rule.startsWith('min:')) {
                    const min = parseInt(rule.split(':')[1]);
                    if (value && value.length < min) {
                        fieldErrors.push(`${field} musí mít minimálně ${min} znaků`);
                    }
                } else if (rule.startsWith('max:')) {
                    const max = parseInt(rule.split(':')[1]);
                    if (value && value.length > max) {
                        fieldErrors.push(`${field} musí mít maximálně ${max} znaků`);
                    }
                } else if (rule === 'numeric' && value && isNaN(parseFloat(value))) {
                    fieldErrors.push(`${field} musí být číslo`);
                }
            }

            if (fieldErrors.length > 0) {
                errors[field] = fieldErrors;
            }
        }

        return {
            isValid: Object.keys(errors).length === 0,
            errors
        };
    }

    static isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    static displayErrors(errors) {
        // Clear previous errors
        document.querySelectorAll('.form-error').forEach(el => el.remove());

        // Display new errors
        for (const [field, fieldErrors] of Object.entries(errors)) {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error text-danger text-sm mt-1';
                errorDiv.textContent = fieldErrors[0];
                input.parentNode.appendChild(errorDiv);
                input.classList.add('border-danger');
            }
        }
    }
}

/**
 * Currency Formatter
 */
class CurrencyFormatter {
    static format(amount, currency = 'CZK') {
        const formatter = new Intl.NumberFormat('cs-CZ', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 2
        });
        return formatter.format(amount);
    }

    static parseAmount(input) {
        return parseFloat(input.value.replace(/[^\d,-]/g, '').replace(',', '.'));
    }

    static inputFormatter(input) {
        input.addEventListener('blur', () => {
            const amount = CurrencyFormatter.parseAmount(input);
            if (!isNaN(amount)) {
                input.value = CurrencyFormatter.format(amount);
            }
        });
    }
}

/**
 * Budget Control - Main JavaScript
 * Handles UI interactions and dynamic functionality
 */

/**
 * API Helper
 */
class API {
    static async post(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    static async get(url) {
        try {
            const response = await fetch(url);
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }
}

/**
 * Notifications
 */
class Notification {
    static show(message, type = 'info', duration = 3000) {
        const container = document.getElementById('notification-container');
        if (!container) {
            const div = document.createElement('div');
            div.id = 'notification-container';
            div.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000;';
            document.body.appendChild(div);
        }

        const notification = document.createElement('div');
        notification.className = `alert alert-${type} animate-slideIn`;
        notification.textContent = message;
        document.getElementById('notification-container').appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, duration);
    }

    static success(message) {
        this.show(message, 'success');
    }

    static error(message) {
        this.show(message, 'danger');
    }

    static warning(message) {
        this.show(message, 'warning');
    }

    static info(message) {
        this.show(message, 'info');
    }
}

/**
 * Modal Handler
 */
class Modal {
    constructor(element) {
        this.element = element;
        this.setupListeners();
    }

    setupListeners() {
        const closeBtn = this.element.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }

        this.element.addEventListener('click', (e) => {
            if (e.target === this.element) {
                this.close();
            }
        });
    }

    open() {
        this.element.classList.add('active');
        this.element.style.display = 'flex';
    }

    close() {
        this.element.classList.remove('active');
        this.element.style.display = 'none';
    }

    static openById(modalId) {
        const modal = new Modal(document.getElementById(modalId));
        modal.open();
    }

    static closeById(modalId) {
        const modal = new Modal(document.getElementById(modalId));
        modal.close();
    }
}

/**
 * Table Handler
 */
class Table {
    static sortByColumn(tableId, columnIndex) {
        const table = document.getElementById(tableId);
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const isAscending = table.dataset.sortOrder !== 'asc';
        rows.sort((a, b) => {
            const aVal = a.children[columnIndex].textContent;
            const bVal = b.children[columnIndex].textContent;

            if (isNaN(parseFloat(aVal)) || isNaN(parseFloat(bVal))) {
                return isAscending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            }

            return isAscending
                ? parseFloat(aVal) - parseFloat(bVal)
                : parseFloat(bVal) - parseFloat(aVal);
        });

        rows.forEach(row => tbody.appendChild(row));
        table.dataset.sortOrder = isAscending ? 'asc' : 'desc';
    }

    static filterByColumn(tableId, columnIndex, searchTerm) {
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const cellText = row.children[columnIndex].textContent.toLowerCase();
            row.style.display = cellText.includes(searchTerm.toLowerCase()) ? '' : 'none';
        });
    }
}

/**
 * Number Input Formatter
 */
class NumberInput {
    static init(selector) {
        document.querySelectorAll(selector).forEach(input => {
            input.addEventListener('blur', () => {
                if (input.value) {
                    const num = parseFloat(input.value);
                    input.value = new Intl.NumberFormat('cs-CZ', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(num);
                }
            });

            input.addEventListener('focus', () => {
                const num = parseFloat(input.value.replace(/[^\d,-]/g, '').replace(',', '.'));
                if (!isNaN(num)) {
                    input.value = num;
                }
            });
        });
    }
}

/**
 * Date Helper
 */
class DateHelper {
    static formatDate(date, format = 'dd.mm.yyyy') {
        if (typeof date === 'string') {
            date = new Date(date);
        }

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        return format
            .replace('dd', day)
            .replace('mm', month)
            .replace('yyyy', year);
    }

    static getMonthName(monthIndex, locale = 'cs') {
        const names = {
            cs: ['leden', 'únor', 'březen', 'duben', 'květen', 'červen',
                 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'],
            en: ['January', 'February', 'March', 'April', 'May', 'June',
                 'July', 'August', 'September', 'October', 'November', 'December']
        };
        return names[locale]?.[monthIndex] || '';
    }
}

/**
 * Mobile Menu Handler
 */
class MobileMenu {
    constructor() {
        this.sidebar = document.querySelector('nav');
        this.overlay = document.getElementById('sidebar-overlay');
        this.toggleBtn = document.getElementById('sidebar-toggle');
        this.isOpen = false;
        this.lastFocusedElement = null;

        this.init();
    }

    init() {
        if (!this.toggleBtn || !this.sidebar || !this.overlay) return;

        // Toggle button click
        this.toggleBtn.addEventListener('click', () => this.toggle());

        // Overlay click to close
        this.overlay.addEventListener('click', () => this.close());

        // Close on navigation link click (mobile only)
        if (window.innerWidth <= 768) {
            this.sidebar.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => this.close());
            });
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
                e.preventDefault();
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && this.isOpen) {
                this.close();
            }
        });

        // Focus trap for accessibility
        this.sidebar.addEventListener('keydown', (e) => {
            if (!this.isOpen) return;

            const focusableElements = this.sidebar.querySelectorAll(
                'a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    // Shift + Tab
                    if (document.activeElement === firstElement) {
                        lastElement.focus();
                        e.preventDefault();
                    }
                } else {
                    // Tab
                    if (document.activeElement === lastElement) {
                        firstElement.focus();
                        e.preventDefault();
                    }
                }
            }
        });
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        this.lastFocusedElement = document.activeElement;

        this.sidebar.classList.add('sidebar-open');
        this.overlay.classList.add('active');
        this.overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('sidebar-open');

        // Update button aria attributes
        if (this.toggleBtn) {
            this.toggleBtn.setAttribute('aria-label', 'Zavřít menu');
            this.toggleBtn.setAttribute('aria-expanded', 'true');
            this.toggleBtn.innerHTML = `
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            `;
        }

        // Focus first menu item for accessibility
        const firstMenuItem = this.sidebar.querySelector('a[href]');
        if (firstMenuItem) {
            setTimeout(() => firstMenuItem.focus(), 100);
        }
    }

    close() {
        this.isOpen = false;

        this.sidebar.classList.remove('sidebar-open');
        this.overlay.classList.remove('active');
        this.overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('sidebar-open');

        // Update button aria attributes
        if (this.toggleBtn) {
            this.toggleBtn.setAttribute('aria-label', 'Otevřít menu');
            this.toggleBtn.setAttribute('aria-expanded', 'false');
            this.toggleBtn.innerHTML = `
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            `;
        }

        // Return focus to the toggle button
        if (this.lastFocusedElement) {
            setTimeout(() => this.lastFocusedElement.focus(), 100);
        }
    }
}

/**
 * Initialization Functions
 */
function initializeNavigation() {
    // Navigation initialization handled by MobileMenu class
}

function initializeModals() {
    // Modal initialization handled by Modal class
}

function initializeForms() {
    // Form initialization handled by FormValidator class
}

function initializeCharts() {
    // Chart initialization handled in individual view files
}

function initializeTooltips() {
    // Tooltip initialization - placeholder for future implementation
}

function initializeTheme() {
    // Theme initialization handled by ThemeManager class
}

function initializeLoadingStates() {
    // Loading states initialization - placeholder for future implementation
}

/**
 * Initialization
 */
document.addEventListener('DOMContentLoaded', () => {
    // Initialize theme
    new ThemeManager();

    // Initialize mobile menu
    new MobileMenu();

    // Initialize number inputs
    NumberInput.init('input[type="number"]');

    // Setup form validation
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', (e) => {
            const rules = JSON.parse(form.dataset.validate);
            const validation = FormValidator.validate(form, rules);

            if (!validation.isValid) {
                e.preventDefault();
                FormValidator.displayErrors(validation.errors);
            }
        });
    });

    // Setup modals
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            Modal.openById(btn.dataset.modal);
        });
    });

    // Close modals on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(modal => {
                new Modal(modal).close();
            });
        }
    });
});

// Export for use in HTML
window.FormValidator = FormValidator;
window.API = API;
window.Notification = Notification;
window.Modal = Modal;
window.Table = Table;
window.CurrencyFormatter = CurrencyFormatter;
window.DateHelper = DateHelper;
