/* ============================================================
   Fichier : public/assets/js/common/main.js
   Rôle    : Fonctions globales du système
   ============================================================ */

'use strict';

// ============================================================
// 1. SÉLECTEURS RAPIDES
// ============================================================
const $ = (selector, parent = document) => parent.querySelector(selector);
const $$ = (selector, parent = document) => [...parent.querySelectorAll(selector)];

// ============================================================
// 2. GESTION DU SIDEBAR
// ============================================================
function initSidebar() {
    const sidebar = $('#sidebar');
    const toggleBtn = $('#btn-toggle-sidebar');
    const overlay = $('#sidebar-overlay');
    const mainWrapper = $('#main-wrapper');

    if (!sidebar || !toggleBtn) return;

    function openSidebar() {
        sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    toggleBtn.addEventListener('click', () => {
        if (sidebar.classList.contains('show')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Fermer avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // Fermer au clic en dehors
    document.addEventListener('click', (e) => {
        if (sidebar.classList.contains('show') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
            closeSidebar();
        }
    });

    // Ajuster au redimensionnement
    window.addEventListener('resize', () => {
        if (window.innerWidth > 992 && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });
}

// ============================================================
// 3. GESTION DU MENU UTILISATEUR (DROPDOWN)
// ============================================================
function initUserDropdown() {
    const dropdown = $('.user-dropdown');
    const menu = $('.user-menu');

    if (!dropdown || !menu) return;

    dropdown.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = menu.classList.contains('show');
        closeAllDropdowns();
        if (!isOpen) {
            menu.classList.add('show');
            dropdown.classList.add('open');
        }
    });

    document.addEventListener('click', () => {
        closeAllDropdowns();
    });

    menu.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    function closeAllDropdowns() {
        menu.classList.remove('show');
        dropdown.classList.remove('open');
    }
}

// ============================================================
// 4. ALERTES AUTO-FERMANTES
// ============================================================
function initAlerts() {
    $$('.alert-dismissible').forEach(alert => {
        if (alert.classList.contains('alert-success') || alert.classList.contains('alert-info')) {
            setTimeout(() => {
                fadeOutAndRemove(alert);
            }, 5000);
        }
    });

    $$('.alert .btn-close').forEach(btn => {
        btn.addEventListener('click', function () {
            const alert = this.closest('.alert');
            if (alert) fadeOutAndRemove(alert);
        });
    });
}

function fadeOutAndRemove(element) {
    element.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    element.style.opacity = '0';
    element.style.transform = 'translateY(-10px)';
    setTimeout(() => {
        if (element.parentNode) element.parentNode.removeChild(element);
    }, 300);
}

// ============================================================
// 5. CONFIRMATION DE SUPPRESSION (MODALE)
// ============================================================
function showConfirmDialog(message, callback, options = {}) {
    const {
        title = 'Confirmation',
        confirmText = 'Supprimer',
        cancelText = 'Annuler',
        icon = 'fa-triangle-exclamation',
        iconColor = 'var(--color-warning)',
        confirmClass = 'btn-danger'
    } = options;

    // Supprimer l'ancienne modale
    const oldModal = $('.confirm-modal');
    if (oldModal) oldModal.remove();

    const modal = document.createElement('div');
    modal.className = 'modal-overlay confirm-modal show';
    modal.innerHTML = `
        <div class="modal-content" style="max-width:460px;">
            <div class="modal-header">
                <h5><i class="fa-solid ${icon}" style="color:${iconColor};margin-right:8px;"></i>${title}</h5>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p style="color:var(--color-gray-600);font-size:0.88rem;">${message}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary btn-cancel">
                    <i class="fa-solid fa-xmark"></i> ${cancelText}
                </button>
                <button type="button" class="btn ${confirmClass} btn-confirm">
                    <i class="fa-solid fa-trash"></i> ${confirmText}
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    const closeModal = () => {
        modal.style.opacity = '0';
        modal.style.transition = 'opacity 0.2s ease';
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = '';
        }, 200);
    };

    modal.querySelector('.modal-close').addEventListener('click', () => {
        closeModal();
        callback(false);
    });

    modal.querySelector('.btn-cancel').addEventListener('click', () => {
        closeModal();
        callback(false);
    });

    modal.querySelector('.btn-confirm').addEventListener('click', () => {
        closeModal();
        callback(true);
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
            callback(false);
        }
    });

    // Focus sur Annuler
    setTimeout(() => modal.querySelector('.btn-cancel').focus(), 100);
}

// ============================================================
// 6. NOTIFICATIONS TOAST
// ============================================================
function showToast(message, type = 'info', duration = 4000) {
    const container = getToastContainer();

    const icons = {
        success: 'fa-circle-check',
        error: 'fa-circle-xmark',
        warning: 'fa-triangle-exclamation',
        info: 'fa-circle-info'
    };

    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };

    const toast = document.createElement('div');
    toast.className = 'toast-item';
    toast.style.cssText = `
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--color-white);
        color: var(--color-dark);
        padding: 14px 18px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        border-left: 4px solid ${colors[type] || colors.info};
        margin-bottom: 10px;
        font-size: 0.85rem;
        font-weight: 500;
        animation: toastSlideIn 0.35s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        max-width: 420px;
        pointer-events: auto;
    `;

    toast.innerHTML = `
        <i class="fa-solid ${icons[type] || icons.info}" style="color: ${colors[type] || colors.info}; font-size: 1.1rem; flex-shrink:0;"></i>
        <span style="flex:1;">${message}</span>
        <button type="button" class="toast-close" style="background:none;border:none;color:var(--color-gray-400);cursor:pointer;padding:0;font-size:0.9rem;flex-shrink:0;">
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;

    container.appendChild(toast);

    // Animation d'entrée
    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(0)';
        toast.style.opacity = '1';
    });

    const closeToast = () => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 300);
    };

    toast.querySelector('.toast-close').addEventListener('click', closeToast);

    if (duration > 0) {
        setTimeout(closeToast, duration);
    }
}

function getToastContainer() {
    let container = $('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            pointer-events: none;
        `;
        document.body.appendChild(container);
    }
    return container;
}

// ============================================================
// 7. VALIDATION DE FORMULAIRES
// ============================================================
function validateField(field) {
    if (field.type === 'hidden' || field.disabled || field.readOnly) return true;

    const value = field.value.trim();
    let errorMessage = '';
    const feedbackEl = field.parentElement.parentElement.querySelector('.invalid-feedback') ||
                       field.parentElement.querySelector('.invalid-feedback');

    // Réinitialiser
    field.classList.remove('is-invalid', 'is-valid');

    // Champ requis
    if (field.hasAttribute('required') && value === '') {
        errorMessage = 'Ce champ est obligatoire.';
    }

    // Longueur minimale
    if (!errorMessage && field.hasAttribute('minlength') && value.length < parseInt(field.getAttribute('minlength'))) {
        errorMessage = `Minimum ${field.getAttribute('minlength')} caractères requis.`;
    }

    // Longueur maximale
    if (!errorMessage && field.hasAttribute('maxlength') && value.length > parseInt(field.getAttribute('maxlength'))) {
        errorMessage = `Maximum ${field.getAttribute('maxlength')} caractères autorisés.`;
    }

    // Email
    if (!errorMessage && field.type === 'email' && value !== '') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            errorMessage = 'Veuillez entrer une adresse email valide.';
        }
    }

    // Téléphone
    if (!errorMessage && field.dataset.type === 'phone' && value !== '') {
        const phoneRegex = /^(\+243|0)?[0-9]{9}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            errorMessage = 'Numéro de téléphone invalide.';
        }
    }

    // Nombre
    if (!errorMessage && (field.type === 'number' || field.dataset.type === 'number') && value !== '') {
        const num = parseFloat(value);
        if (isNaN(num)) {
            errorMessage = 'Veuillez entrer un nombre valide.';
        } else {
            const min = field.hasAttribute('min') ? parseFloat(field.getAttribute('min')) : null;
            const max = field.hasAttribute('max') ? parseFloat(field.getAttribute('max')) : null;
            if (min !== null && num < min) errorMessage = `La valeur minimum est ${min}.`;
            if (!errorMessage && max !== null && num > max) errorMessage = `La valeur maximum est ${max}.`;
        }
    }

    // Afficher ou masquer l'erreur
    if (errorMessage) {
        field.classList.add('is-invalid');
        if (feedbackEl) feedbackEl.textContent = errorMessage;
        return false;
    } else if (value !== '' || field.hasAttribute('required')) {
        field.classList.add('is-valid');
    }

    if (feedbackEl) feedbackEl.textContent = '';
    return true;
}

function validateForm(form) {
    let isValid = true;
    const fields = form.querySelectorAll('input, select, textarea');

    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });

    // Scroll vers la première erreur
    if (!isValid) {
        const firstError = form.querySelector('.is-invalid');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstError.focus();
        }
    }

    return isValid;
}

// ============================================================
// 8. RECHERCHE DYNAMIQUE DANS LES TABLEAUX
// ============================================================
function initTableSearch() {
    const searchInput = $('.table-search');
    if (!searchInput) return;

    searchInput.addEventListener('keyup', function () {
        const query = this.value.toLowerCase().trim();
        const table = this.closest('.card')?.querySelector('table tbody');

        if (!table) return;

        const rows = $$('tr', table);
        let visibleCount = 0;

        rows.forEach(row => {
            if (row.classList.contains('no-results')) return;
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(query);
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        updateNoResults(table, rows, visibleCount);
    });

    function updateNoResults(table, rows, visibleCount) {
        let noResultRow = table.querySelector('.no-results');

        if (visibleCount === 0 && rows.length > 0) {
            if (!noResultRow) {
                noResultRow = document.createElement('tr');
                noResultRow.className = 'no-results';
                const colCount = rows[0]?.children.length || 1;
                noResultRow.innerHTML = `
                    <td colspan="${colCount}" style="text-align:center;padding:30px;color:var(--color-gray-400);">
                        <i class="fa-solid fa-search" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                        Aucun résultat trouvé
                    </td>`;
                table.appendChild(noResultRow);
            }
        } else {
            if (noResultRow) noResultRow.remove();
        }
    }
}

// ============================================================
// 9. TOGGLE MOT DE PASSE
// ============================================================
function initPasswordToggles() {
    $$('.toggle-password').forEach(btn => {
        btn.addEventListener('click', function () {
            const input = this.parentElement.querySelector('input');
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            const icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            }
        });
    });
}

// ============================================================
// 10. APERÇU D'IMAGE AVANT UPLOAD
// ============================================================
function initImagePreview() {
    $$('.input-file-image').forEach(input => {
        input.addEventListener('change', function () {
            const file = this.files[0];
            const previewSelector = this.dataset.preview;
            if (!previewSelector) return;

            const preview = $(previewSelector);
            if (!preview || !file) return;

            if (!file.type.startsWith('image/')) {
                showToast('Veuillez sélectionner une image valide.', 'warning');
                this.value = '';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                showToast('L\'image ne doit pas dépasser 5 Mo.', 'error');
                this.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    });
}

// ============================================================
// 11. BOUTONS AVEC ÉTAT DE CHARGEMENT
// ============================================================
function setButtonLoading(button) {
    button.classList.add('loading');
    button.disabled = true;
    button.dataset.originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Chargement...';
}

function resetButtonLoading(button) {
    button.classList.remove('loading');
    button.disabled = false;
    if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
        delete button.dataset.originalHtml;
    }
}

// ============================================================
// 12. GESTION DES FORMULAIRES AJAX
// ============================================================
async function submitFormAjax(form, url, options = {}) {
    const {
        onSuccess = null,
        onError = null,
        button = null,
        method = 'POST'
    } = options;

    const formData = new FormData(form);

    if (button) setButtonLoading(button);

    try {
        const response = await fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (button) resetButtonLoading(button);

        if (data.success) {
            if (onSuccess) onSuccess(data);
        } else {
            if (onError) onError(data);
            showToast(data.message || 'Une erreur est survenue.', 'error');
        }

        return data;
    } catch (error) {
        if (button) resetButtonLoading(button);
        showToast('Erreur de connexion au serveur.', 'error');
        console.error('Erreur AJAX:', error);

        if (onError) onError({ success: false, message: 'Erreur de connexion.' });
    }
}

// ============================================================
// 13. FORMATAGE DE DATE RELATIVE
// ============================================================
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    const intervals = [
        { label: 'an', seconds: 31536000 },
        { label: 'mois', seconds: 2592000 },
        { label: 'semaine', seconds: 604800 },
        { label: 'jour', seconds: 86400 },
        { label: 'heure', seconds: 3600 },
        { label: 'minute', seconds: 60 }
    ];

    for (const interval of intervals) {
        const count = Math.floor(seconds / interval.seconds);
        if (count >= 1) {
            return `Il y a ${count} ${interval.label}${count > 1 ? 's' : ''}`;
        }
    }

    return 'À l\'instant';
}

// ============================================================
// 14. DEBOUNCE (optimisation performance)
// ============================================================
function debounce(func, wait = 300) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// ============================================================
// 15. COOKIES
// ============================================================
function setCookie(name, value, days = 7) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${encodeURIComponent(value)};expires=${date.toUTCString()};path=/;SameSite=Lax`;
}

function getCookie(name) {
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const [key, value] = cookie.trim().split('=');
        if (key === name) return decodeURIComponent(value);
    }
    return null;
}

function deleteCookie(name) {
    document.cookie = `${name}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/`;
}

// ============================================================
// 16. INITIALISATION GLOBALE
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initUserDropdown();
    initAlerts();
    initTableSearch();
    initPasswordToggles();
    initImagePreview();

    // Activer les formulaires avec validation
    $$('form[data-validate="true"]').forEach(form => {
        form.setAttribute('novalidate', true);

        form.addEventListener('submit', function (e) {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });

        // Validation en temps réel sur blur
        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('blur', () => validateField(field));
            field.addEventListener('input', debounce(() => {
                if (field.classList.contains('is-invalid') || field.classList.contains('is-valid')) {
                    validateField(field);
                }
            }, 300));
        });
    });

    // Mettre à jour la date/heure
    const dateTimeEl = $('.current-datetime');
    if (dateTimeEl) {
        const updateDateTime = () => {
            const now = new Date();
            dateTimeEl.textContent = now.toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };
        updateDateTime();
        setInterval(updateDateTime, 30000);
    }

    console.log(
        `%cGestion Scolaire v1.0%c — Initialisé avec succès.`,
        'color: #2c3e90; font-weight: 700; font-size: 13px;',
        'color: #64748b; font-size: 12px;'
    );
});

// ============================================================
// 17. EXPOSER LES FONCTIONS GLOBALEMENT
// ============================================================
window.$ = $;
window.$$ = $$;
window.showToast = showToast;
window.showConfirmDialog = showConfirmDialog;
window.validateForm = validateForm;
window.validateField = validateField;
window.setButtonLoading = setButtonLoading;
window.resetButtonLoading = resetButtonLoading;
window.submitFormAjax = submitFormAjax;
window.timeAgo = timeAgo;
window.debounce = debounce;
window.setCookie = setCookie;
window.getCookie = getCookie;
window.deleteCookie = deleteCookie;