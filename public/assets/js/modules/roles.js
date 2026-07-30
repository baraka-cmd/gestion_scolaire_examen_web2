/* ============================================================
   Fichier : public/assets/js/modules/roles.js
   Rôle    : Gestion des rôles et permissions
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const btnAddRole           = document.getElementById('btnAddRole');
    const modalAddRole         = document.getElementById('modalAddRole');
    const btnCloseAddRole      = document.getElementById('btnCloseAddRole');
    const btnCancelAddRole     = document.getElementById('btnCancelAddRole');
    const btnSaveRole          = document.getElementById('btnSaveRole');
    const formAddRole          = document.getElementById('formAddRole');

    const modalPermissions     = document.getElementById('modalManagePermissions');
    const btnClosePermissions  = document.getElementById('btnClosePermissions');
    const btnCancelPermissions = document.getElementById('btnCancelPermissions');
    const btnSavePermissions   = document.getElementById('btnSavePermissions');
    const permissionsContainer = document.getElementById('permissionsContainer');
    const manageRoleName       = document.getElementById('manageRoleName');

    const flashMessage         = document.getElementById('flashMessage');

    let currentRoleId = null;

    // ============================================================
    // 2. OUVERTURE/FERMETURE MODALE AJOUT RÔLE
    // ============================================================
    if (btnAddRole && modalAddRole) {
        btnAddRole.addEventListener('click', () => {
            openModal(modalAddRole);
            resetForm(formAddRole);
            document.getElementById('add_nom').focus();
        });
    }

    if (btnCloseAddRole) btnCloseAddRole.addEventListener('click', () => closeModal(modalAddRole));
    if (btnCancelAddRole) btnCancelAddRole.addEventListener('click', () => closeModal(modalAddRole));

    // Fermer au clic en dehors
    if (modalAddRole) {
        modalAddRole.addEventListener('click', function(e) {
            if (e.target === modalAddRole) closeModal(modalAddRole);
        });
    }

    // ============================================================
    // 3. CRÉATION D'UN RÔLE
    // ============================================================
    if (btnSaveRole) {
        btnSaveRole.addEventListener('click', async () => {
            const nom = document.getElementById('add_nom').value.trim();
            const description = document.getElementById('add_description').value.trim();
            const niveau = document.getElementById('add_niveau').value;

            // Validation simple
            let hasError = false;
            clearErrors();

            if (nom === '') {
                showFieldError('add_nom', 'Le nom du rôle est obligatoire.');
                hasError = true;
            } else if (nom.length < 2) {
                showFieldError('add_nom', 'Minimum 2 caractères.');
                hasError = true;
            }

            if (description === '') {
                showFieldError('add_description', 'La description est obligatoire.');
                hasError = true;
            }

            if (hasError) return;

            setButtonLoading(btnSaveRole);

            try {
                const formData = new FormData();
                formData.append('action', 'createRole');
                formData.append('nom', nom);
                formData.append('description', description);
                formData.append('niveau_acces', niveau);

                const response = await fetch('/gestion_scolaire/roles', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.success) {
                    showFlash('success', data.message);
                    closeModal(modalAddRole);
                    setTimeout(() => location.reload(), 800);
                } else {
                    showFlash('error', data.message);
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            showFieldError('add_' + field, data.errors[field]);
                        });
                    }
                }
            } catch (error) {
                showFlash('error', 'Erreur de connexion au serveur.');
                console.error('Erreur:', error);
            } finally {
                resetButtonLoading(btnSaveRole);
            }
        });
    }

    // ============================================================
    // 4. OUVERTURE MODALE PERMISSIONS
    // ============================================================
    document.querySelectorAll('.btnManageRole').forEach(btn => {
        btn.addEventListener('click', async function() {
            const roleId = this.dataset.id;
            currentRoleId = roleId;

            openModal(modalPermissions);
            permissionsContainer.innerHTML = `
                <div style="text-align:center;padding:30px;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;color:var(--color-primary);"></i>
                    <p style="margin-top:10px;color:var(--color-gray-500);">Chargement des permissions...</p>
                </div>`;

            try {
                const formData = new FormData();
                formData.append('action', 'getRole');
                formData.append('role_id', roleId);

                const response = await fetch('/gestion_scolaire/roles', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.success) {
                    manageRoleName.textContent = data.role.nom;
                    renderPermissions(data.allPermissions, data.permissions);
                } else {
                    permissionsContainer.innerHTML = `<p class="text-error">${data.message}</p>`;
                }
            } catch (error) {
                permissionsContainer.innerHTML = '<p class="text-error">Erreur de chargement.</p>';
                console.error('Erreur:', error);
            }
        });
    });

    if (btnClosePermissions) btnClosePermissions.addEventListener('click', () => closeModal(modalPermissions));
    if (btnCancelPermissions) btnCancelPermissions.addEventListener('click', () => closeModal(modalPermissions));

    if (modalPermissions) {
        modalPermissions.addEventListener('click', function(e) {
            if (e.target === modalPermissions) closeModal(modalPermissions);
        });
    }

    // ============================================================
    // 5. RENDU DES PERMISSIONS
    // ============================================================
    function renderPermissions(allPermissions, rolePermissions) {
        const rolePermIds = rolePermissions.map(p => p.id);
        let html = '';

        // Actions rapides
        html += `
            <div class="quick-actions">
                <button type="button" class="quick-action-btn" onclick="selectAllPermissions()">
                    <i class="fa-solid fa-check-double"></i> Tout sélectionner
                </button>
                <button type="button" class="quick-action-btn danger" onclick="deselectAllPermissions()">
                    <i class="fa-solid fa-xmark"></i> Tout désélectionner
                </button>
            </div>`;

        // Stats
        const totalPerms = allPermissions.reduce((acc, m) => acc + m.permissions.length, 0);
        const grantedPerms = allPermissions.reduce((acc, m) => acc + m.permissions.filter(p => rolePermIds.includes(p.id)).length, 0);

        html += `
            <div class="permission-stats">
                <div class="permission-stat">
                    <div class="permission-stat-value granted">${grantedPerms}</div>
                    <div class="permission-stat-label">Permissions accordées</div>
                </div>
                <div class="permission-stat">
                    <div class="permission-stat-value denied">${totalPerms - grantedPerms}</div>
                    <div class="permission-stat-label">Permissions refusées</div>
                </div>
                <div class="permission-stat">
                    <div class="permission-stat-value">${totalPerms}</div>
                    <div class="permission-stat-label">Total permissions</div>
                </div>
            </div>`;

        // Modules de permissions
        const moduleIcons = {
            'dashboard': 'fa-gauge-high blue',
            'utilisateurs': 'fa-users blue',
            'roles': 'fa-user-shield purple',
            'eleves': 'fa-graduation-cap green',
            'enseignants': 'fa-chalkboard-user orange',
            'parents': 'fa-people-roof cyan',
            'classes': 'fa-chalkboard blue',
            'matieres': 'fa-book-open green',
            'inscriptions': 'fa-file-pen orange',
            'evaluations': 'fa-clipboard-list blue',
            'notes': 'fa-pen-to-square green',
            'resultats': 'fa-square-poll-vertical purple',
            'bulletins': 'fa-file-invoice orange',
            'emploi': 'fa-calendar-days cyan',
            'absences': 'fa-calendar-xmark red',
            'retards': 'fa-clock orange',
            'discipline': 'fa-gavel red',
            'paiements': 'fa-money-bill-wave green',
            'cantine': 'fa-utensils orange',
            'transport': 'fa-bus blue',
            'calendrier': 'fa-calendar purple',
            'messages': 'fa-envelope cyan',
            'documents': 'fa-folder-open blue',
            'rapports': 'fa-chart-bar green',
            'parametres': 'fa-gear purple',
            'sauvegardes': 'fa-database blue',
            'logs': 'fa-clock-rotate-left red'
        };

        allPermissions.forEach(module => {
            const iconClass = moduleIcons[module.module] || 'fa-circle blue';
            const [icon, color] = iconClass.split(' ');
            const selectedCount = module.permissions.filter(p => rolePermIds.includes(p.id)).length;

            html += `
                <div class="permission-module open">
                    <div class="permission-module-header" onclick="toggleModule(this)">
                        <div class="module-header-left">
                            <div class="module-icon ${color}">
                                <i class="fa-solid ${icon}"></i>
                            </div>
                            <span class="module-name">${module.module}</span>
                            <span class="module-count">(${selectedCount}/${module.permissions.length})</span>
                        </div>
                        <div class="module-actions">
                            <button type="button" class="btn-module-action" onclick="event.stopPropagation();selectModulePermissions(this)" title="Tout sélectionner">
                                <i class="fa-solid fa-check"></i> Tout
                            </button>
                            <button type="button" class="btn-module-action" onclick="event.stopPropagation();deselectModulePermissions(this)" title="Tout désélectionner">
                                <i class="fa-solid fa-xmark"></i> Aucun
                            </button>
                            <i class="fa-solid fa-chevron-down module-toggle"></i>
                        </div>
                    </div>
                    <div class="permission-module-body">
                        <div class="permission-list">`;

            module.permissions.forEach(perm => {
                const isSelected = rolePermIds.includes(perm.id);
                html += `
                    <div class="permission-item ${isSelected ? 'selected' : ''}" data-perm-id="${perm.id}" onclick="togglePermission(this)">
                        <div class="permission-checkbox">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div class="permission-info">
                            <div class="permission-code">${escapeHtml(perm.code)}</div>
                            <div class="permission-label">${escapeHtml(perm.libelle)}</div>
                        </div>
                    </div>`;
            });

            html += `
                        </div>
                    </div>
                </div>`;
        });

        permissionsContainer.innerHTML = html;
    }

    // ============================================================
    // 6. SAUVEGARDE DES PERMISSIONS
    // ============================================================
    if (btnSavePermissions) {
    btnSavePermissions.addEventListener('click', async () => {
        const selectedPerms = [];
        document.querySelectorAll('.permission-item.selected').forEach(item => {
            selectedPerms.push(item.dataset.permId);
        });

        console.log('Permissions sélectionnées:', selectedPerms);
        console.log('Role ID:', currentRoleId);

        setButtonLoading(btnSavePermissions);

        try {
            const formData = new FormData();
            formData.append('action', 'updatePermissions');
            formData.append('role_id', currentRoleId);
            formData.append('permissions', JSON.stringify(selectedPerms));

            const response = await fetch('/gestion_scolaire/roles', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            console.log('Status:', response.status);
            
            const data = await response.json();
            console.log('Réponse:', data);

            if (data.success) {
                if (modalPermissions) {
                    modalPermissions.classList.remove('show');
                    document.body.style.overflow = '';
                }
                showFlash('success', data.message);
                setTimeout(() => location.reload(), 800);
            } else {
                showFlash('error', data.message);
            }
        } catch (error) {
            console.error('Erreur:', error);
            showFlash('error', 'Erreur de connexion: ' + error.message);
        } finally {
            resetButtonLoading(btnSavePermissions);
        }
    });
}

    // ============================================================
    // 7. SUPPRESSION D'UN RÔLE
    // ============================================================
    document.querySelectorAll('.btnDeleteRole').forEach(btn => {
        btn.addEventListener('click', function() {
            const roleId = this.dataset.id;
            const roleNom = this.dataset.nom;

            showConfirmDialog(
                `Êtes-vous sûr de vouloir supprimer le rôle <strong>"${roleNom}"</strong> ?<br><small>Cette action est irréversible.</small>`,
                async (confirmed) => {
                    if (!confirmed) return;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'deleteRole');
                        formData.append('role_id', roleId);

                        const response = await fetch('/gestion_scolaire/roles', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });

                        const data = await response.json();

                        if (data.success) {
                            showFlash('success', data.message);
                            setTimeout(() => location.reload(), 800);
                        } else {
                            showFlash('error', data.message);
                        }
                    } catch (error) {
                        showFlash('error', 'Erreur de connexion au serveur.');
                    }
                },
                { confirmText: 'Supprimer', confirmClass: 'btn-danger', iconColor: 'var(--color-error)' }
            );
        });
    });

    // ============================================================
    // 8. FONCTIONS UTILITAIRES
    // ============================================================
    function openModal(modal) {
        if (modal) modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (modal) modal.classList.remove('show');
        document.body.style.overflow = '';
    }

    function resetForm(form) {
        if (form) form.reset();
        clearErrors();
    }

    function clearErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (field) field.classList.add('is-invalid');
        const feedback = document.getElementById(fieldId + 'Error');
        if (feedback) feedback.textContent = message;
    }

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

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================================
    // 9. FONCTIONS GLOBALES POUR LES PERMISSIONS
    // ============================================================
    window.toggleModule = function(header) {
        const module = header.parentElement;
        module.classList.toggle('open');
    };

    window.togglePermission = function(item) {
        item.classList.toggle('selected');
        updateModuleCount(item.closest('.permission-module'));
        updateStats();
    };

    window.selectAllPermissions = function() {
        document.querySelectorAll('.permission-item').forEach(item => item.classList.add('selected'));
        document.querySelectorAll('.permission-module').forEach(mod => updateModuleCount(mod));
        updateStats();
    };

    window.deselectAllPermissions = function() {
        document.querySelectorAll('.permission-item').forEach(item => item.classList.remove('selected'));
        document.querySelectorAll('.permission-module').forEach(mod => updateModuleCount(mod));
        updateStats();
    };

    window.selectModulePermissions = function(btn) {
        const module = btn.closest('.permission-module');
        module.querySelectorAll('.permission-item').forEach(item => item.classList.add('selected'));
        updateModuleCount(module);
        updateStats();
    };

    window.deselectModulePermissions = function(btn) {
        const module = btn.closest('.permission-module');
        module.querySelectorAll('.permission-item').forEach(item => item.classList.remove('selected'));
        updateModuleCount(module);
        updateStats();
    };

    function updateModuleCount(module) {
        const total = module.querySelectorAll('.permission-item').length;
        const selected = module.querySelectorAll('.permission-item.selected').length;
        const countEl = module.querySelector('.module-count');
        if (countEl) countEl.textContent = `(${selected}/${total})`;
    }

    function updateStats() {
        const total = document.querySelectorAll('.permission-item').length;
        const granted = document.querySelectorAll('.permission-item.selected').length;
        const denied = total - granted;

        const grantedEl = document.querySelector('.permission-stat-value.granted');
        const deniedEl = document.querySelector('.permission-stat-value.denied');

        if (grantedEl) grantedEl.textContent = granted;
        if (deniedEl) deniedEl.textContent = denied;
    }

    // ============================================================
    // 10. INITIALISATION
    // ============================================================
    console.log('%cModule Rôles%c initialisé avec succès.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});