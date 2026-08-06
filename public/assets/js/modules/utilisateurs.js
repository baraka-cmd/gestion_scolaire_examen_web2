/* ============================================================
   Fichier : public/assets/js/modules/utilisateurs.js
   Rôle    : Gestion du module utilisateurs (liste, ajout, modification)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM — LISTE
    // ============================================================
    const tbodyUsers         = document.getElementById('tbodyUsers');
    const searchInput        = document.getElementById('searchUsers');
    const filterRole         = document.getElementById('filterRole');
    const filterStatut       = document.getElementById('filterStatut');
    const btnResetFilters    = document.getElementById('btnResetFilters');
    const paginationContainer = document.getElementById('paginationUsers');
    const totalUsersEl       = document.getElementById('totalUsers');
    const flashMessage       = document.getElementById('flashMessage');

    // Éléments du formulaire (pour la modale)
    const userNom            = document.getElementById('user_nom');
    const userPrenom         = document.getElementById('user_prenom');
    const userEmail          = document.getElementById('user_email');
    const userTelephone      = document.getElementById('user_telephone');
    const userLogin          = document.getElementById('user_login');
    const userRoleId         = document.getElementById('user_role_id');
    const userPassword       = document.getElementById('user_password');
    const passwordSection    = document.getElementById('passwordSection');

    let currentPage = 1;
    let currentUserId = null;

    // ============================================================
    // 2. FONCTIONS UTILITAIRES
    // ============================================================
    function setButtonLoading(button) {
        if (!button) return;
        button.disabled = true;
        const spinner = button.querySelector('.spinner');
        if (spinner) spinner.style.display = 'inline-block';
        const textSpan = button.querySelector('span');
        if (textSpan) textSpan.textContent = 'Chargement...';
    }

    function resetButtonLoading(button) {
        if (!button) return;
        button.disabled = false;
        const spinner = button.querySelector('.spinner');
        if (spinner) spinner.style.display = 'none';
        const textSpan = button.querySelector('span');
        if (textSpan) textSpan.textContent = 'Enregistrer';
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================================
    // 3. CHARGEMENT DE LA LISTE
    // ============================================================
    function loadUsers(page = 1) {
        currentPage = page;

        if (tbodyUsers) {
            tbodyUsers.innerHTML = `
                <tr><td colspan="6">
                    <div style="text-align:center;padding:30px;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;color:var(--color-primary);"></i>
                        <p style="margin-top:10px;color:var(--color-gray-500);">Chargement...</p>
                    </div>
                </td></tr>`;
        }

        const formData = new FormData();
        formData.append('action', 'liste');
        formData.append('page', page);
        formData.append('recherche', searchInput ? searchInput.value.trim() : '');
        formData.append('role', filterRole ? filterRole.value : '');
        formData.append('statut', filterStatut ? filterStatut.value : '');

        fetch('/gestion_scolaire/utilisateurs', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTable(data.utilisateurs);
                renderPagination(data);
                if (totalUsersEl) totalUsersEl.textContent = data.total;
            } else {
                showFlash('error', data.message);
            }
        })
        .catch(err => {
            console.error('Erreur chargement utilisateurs:', err);
            showFlash('error', 'Erreur de connexion au serveur.');
        });
    }

    function renderTable(utilisateurs) {
        if (!tbodyUsers) return;

        if (!utilisateurs || utilisateurs.length === 0) {
            tbodyUsers.innerHTML = `
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fa-solid fa-users-slash"></i>
                            <p>Aucun utilisateur trouvé</p>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        const colors = ['blue', 'green', 'orange', 'purple', 'red', 'cyan'];
        tbodyUsers.innerHTML = utilisateurs.map((user, index) => `
            <tr>
               
                <td>
                    <div class="user-cell">
                        ${user.photo 
                            ? `<img src="/gestion_scolaire/${escapeHtml(user.photo)}" alt="Photo" class="user-avatar">`
                            : `<div class="user-avatar-placeholder ${colors[index % colors.length]}">${escapeHtml(user.initials)}</div>`
                        }
                        <div class="user-info">
                            <span class="user-name">
                                <a href="/gestion_scolaire/utilisateurs/fiche/${user.id}">${escapeHtml(user.prenom)} ${escapeHtml(user.nom)}</a>
                            </span>
                            <span class="user-email">${escapeHtml(user.email)}</span>
                        </div>
                    </div>
                </td>
                <td><span class="role-badge ${escapeHtml(user.role_nom).toLowerCase()}"><i class="fa-solid fa-circle"></i> ${escapeHtml(user.role_nom)}</span></td>
                <td>${escapeHtml(user.login)}</td>
                <td>${escapeHtml(user.derniere_connexion)}</td>
                <td>
                    <label class="status-toggle" onclick="window.toggleUserStatus(${user.id}, this)" title="${user.est_actif ? 'Désactiver' : 'Activer'}">
                        <input type="checkbox" ${user.est_actif ? 'checked' : ''} ${user.id === currentUserId ? 'disabled' : ''}>
                        <span class="status-toggle-slider"></span>
                    </label>
                </td>
                <td>
                    <div class="actions-cell">
                        <button class="btn-icon btn-icon-view" onclick="window.viewUser(${user.id})" title="Voir la fiche">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button class="btn-icon btn-icon-edit" onclick="window.editUser(${user.id})" title="Modifier">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn-icon btn-icon-lock" onclick="window.resetPassword(${user.id})" title="Réinitialiser mot de passe">
                            <i class="fa-solid fa-key"></i>
                        </button>
                        <button class="btn-icon btn-icon-delete" onclick="window.deleteUser(${user.id}, '${escapeHtml(user.prenom)} ${escapeHtml(user.nom)}')" title="Supprimer">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(data) {
        if (!paginationContainer) return;

        const totalPages = data.pages;
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let html = '';
        html += `<button class="pagination-btn" onclick="window.loadUsers(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-left"></i>
        </button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="window.loadUsers(${i})">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }

        html += `<button class="pagination-btn" onclick="window.loadUsers(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-right"></i>
        </button>`;

        paginationContainer.innerHTML = html;
    }

    // ============================================================
    // 4. FILTRES ET RECHERCHE
    // ============================================================
    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => loadUsers(1), 400));
    }
    if (filterRole) {
        filterRole.addEventListener('change', () => {
            loadUsers(1);
        });
    }
    if (filterStatut) {
        filterStatut.addEventListener('change', () => {
            loadUsers(1);
        });
    }
    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterRole) filterRole.value = '';
            if (filterStatut) filterStatut.value = '';
            loadUsers(1);
        });
    }

    // ============================================================
    // 5. TOGGLE STATUT
    // ============================================================
    window.toggleUserStatus = function(id, toggle) {
        const checkbox = toggle.querySelector('input');
        if (checkbox.disabled) return;

        const formData = new FormData();
        formData.append('action', 'toggleStatut');
        formData.append('id', id);

        fetch('/gestion_scolaire/utilisateurs', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showFlash('success', data.message);
            } else {
                checkbox.checked = !checkbox.checked;
                showFlash('error', data.message);
            }
        })
        .catch(() => {
            checkbox.checked = !checkbox.checked;
            showFlash('error', 'Erreur de connexion.');
        });
    };

    // ============================================================
    // 6. RÉINITIALISATION MOT DE PASSE
    // ============================================================
    window.resetPassword = function(id) {
        const newPassword = prompt('Nouveau mot de passe (min. 8 caractères) :');
        if (newPassword === null) return;
        if (newPassword.length < 8) {
            showFlash('warning', 'Minimum 8 caractères requis.');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'changerMotDePasse');
        formData.append('id', id);
        formData.append('password', newPassword);

        fetch('/gestion_scolaire/utilisateurs', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            showFlash(data.success ? 'success' : 'error', data.message);
        })
        .catch(() => showFlash('error', 'Erreur de connexion.'));
    };

    // ============================================================
    // 7. VOIR FICHE / ÉDITER / SUPPRIMER
    // ============================================================
    window.viewUser = function(id) {
        window.location.href = `/gestion_scolaire/utilisateurs/fiche/${id}`;
    };

    window.editUser = function(id) {
        window.location.href = `/gestion_scolaire/utilisateurs/modifier/${id}`;
    };

    window.deleteUser = function(id, nom) {
        if (!confirm(`Êtes-vous sûr de vouloir supprimer "${nom}" ?\n\nCette action est irréversible.`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'supprimer');
        formData.append('id', id);

        fetch('/gestion_scolaire/utilisateurs', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showFlash('success', data.message);
                loadUsers(currentPage);
            } else {
                showFlash('error', data.message);
            }
        })
        .catch(() => showFlash('error', 'Erreur de connexion.'));
    };

    // ============================================================
    // 8. VALIDATION FORMULAIRE (MODALE)
    // ============================================================
    function validateUserForm() {
        clearFormErrors();
        let valid = true;

        const nom = userNom?.value.trim();
        const prenom = userPrenom?.value.trim();
        const email = userEmail?.value.trim();
        const login = userLogin?.value.trim();
        const roleId = userRoleId?.value;
        const password = userPassword?.value;

        if (!nom || nom.length < 2) {
            showFieldError('user_nom', 'Minimum 2 caractères.');
            valid = false;
        }
        if (!prenom || prenom.length < 2) {
            showFieldError('user_prenom', 'Minimum 2 caractères.');
            valid = false;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showFieldError('user_email', 'Email invalide.');
            valid = false;
        }
        if (!login || login.length < 3) {
            showFieldError('user_login', 'Minimum 3 caractères.');
            valid = false;
        }
        if (!roleId) {
            showFieldError('user_role_id', 'Sélectionnez un rôle.');
            valid = false;
        }
        if (!currentUserId) {
            if (!password || password.length < 8) {
                showFieldError('user_password', 'Minimum 8 caractères.');
                valid = false;
            }
        }

        if (!valid) {
            const firstError = document.querySelector('.is-invalid');
            if (firstError) firstError.focus();
        }

        return valid;
    }

    function clearFormErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (field) field.classList.add('is-invalid');
        const feedback = document.getElementById(fieldId + 'Error');
        if (feedback) feedback.textContent = message;
    }

    // ============================================================
    // 9. SAUVEGARDE FORMULAIRE (MODALE)
    // ============================================================
    if (typeof btnSaveUser !== 'undefined' && btnSaveUser && typeof formUser !== 'undefined' && formUser) {
        btnSaveUser.addEventListener('click', async () => {
            if (!validateUserForm()) return;

            setButtonLoading(btnSaveUser);
            const formData = new FormData(formUser);
            formData.append('action', currentUserId ? 'modifier' : 'creer');
            if (currentUserId) formData.append('id', currentUserId);

            try {
                const res = await fetch('/gestion_scolaire/utilisateurs', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data.success) {
                    showFlash('success', data.message);
                    closeModal();
                    loadUsers(currentPage);
                } else {
                    showFlash('error', data.message);
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            showFieldError('user_' + field, data.errors[field]);
                        });
                    }
                }
            } catch (err) {
                showFlash('error', 'Erreur de connexion.');
            } finally {
                resetButtonLoading(btnSaveUser);
            }
        });
    }

    // ============================================================
    // 10. MODALE
    // ============================================================
    function openModal() {
        if (typeof modalUser !== 'undefined' && modalUser) {
            modalUser.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal() {
        if (typeof modalUser !== 'undefined' && modalUser) {
            modalUser.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    window.closeModal = closeModal;

    if (typeof btnCloseModal !== 'undefined' && btnCloseModal) {
        btnCloseModal.addEventListener('click', closeModal);
    }
    if (typeof btnCancelModal !== 'undefined' && btnCancelModal) {
        btnCancelModal.addEventListener('click', closeModal);
    }
    if (typeof modalUser !== 'undefined' && modalUser) {
        modalUser.addEventListener('click', function(e) {
            if (e.target === modalUser) closeModal();
        });
    }

    // ============================================================
    // 11. MESSAGES FLASH
    // ============================================================
    function showFlash(type, message) {
        if (!flashMessage) return;
        const icons = { 
            success: 'fa-circle-check', 
            error: 'fa-circle-exclamation', 
            warning: 'fa-triangle-exclamation', 
            info: 'fa-circle-info' 
        };
        flashMessage.innerHTML = `
            <div class="alert alert-${type} alert-dismissible">
                <i class="fa-solid ${icons[type] || icons.info}"></i>
                <span>${escapeHtml(message)}</span>
                <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
            </div>`;
        flashMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ============================================================
    // 12. INITIALISATION
    // ============================================================
    if (tbodyUsers) {
        loadUsers(1);
    }

    // Exposer loadUsers globalement pour la pagination
    window.loadUsers = loadUsers;

    console.log('%cModule Utilisateurs%c initialisé avec succès.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});