/* ============================================================
   Fichier : public/assets/js/modules/matieres.js
   Rôle    : Gestion hiérarchique des matières (Groupes → Sous-matières)
   Système : Bulletin congolais
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const matieresContainer   = document.getElementById('matieresContainer');
    const searchInput         = document.getElementById('searchMatieres');
    const btnResetFilters     = document.getElementById('btnResetFilters');
    const paginationContainer = document.getElementById('paginationMatieres');
    const flashMessage        = document.getElementById('flashMessage');

    // Modale
    const modalClasses        = document.getElementById('modalClasses');
    const modalMatiereName    = document.getElementById('modalMatiereName');
    const modalMaximaGlobal   = document.getElementById('modalMaximaGlobal');
    const modalClassesList    = document.getElementById('modalClassesList');
    const btnCloseModal       = document.getElementById('btnCloseModal');
    const btnCancelModal      = document.getElementById('btnCancelModal');

    let currentPage = 1;
    let currentMatiereId = null;

    // ============================================================
    // 2. CHARGEMENT DE LA LISTE HIÉRARCHIQUE
    // ============================================================
    function loadMatieres(page = 1) {
        currentPage = page;

        if (matieresContainer) {
            matieresContainer.innerHTML = `
                <div style="text-align:center;padding:40px;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;color:var(--color-primary);"></i>
                    <p style="margin-top:12px;color:var(--color-gray-500);">Chargement des matières...</p>
                </div>`;
        }

        const formData = new FormData();
        formData.append('action', 'liste');
        formData.append('page', page);
        formData.append('recherche', searchInput?.value || '');

        fetch('/gestion_scolaire/matieres', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderGroupes(data.groupes);
                renderPagination(data);
            } else {
                showFlash('error', data.message);
            }
        })
        .catch(err => {
            console.error('Erreur chargement matières:', err);
            showFlash('error', 'Erreur de connexion au serveur.');
        });
    }

    // ============================================================
    // 3. RENDU HIÉRARCHIQUE : GROUPES → SOUS-MATIÈRES
    // ============================================================
    function renderGroupes(groupes) {
        if (!matieresContainer) return;

        if (groupes.length === 0) {
            matieresContainer.innerHTML = `
                <div class="empty-state" style="padding:40px;">
                    <i class="fa-solid fa-book-open"></i>
                    <p>Aucune matière trouvée</p>
                </div>`;
            return;
        }

        const colors = ['blue', 'green', 'orange', 'purple', 'pink', 'cyan', 'red', 'indigo', 'teal'];
        const icons  = ['fa-book', 'fa-calculator', 'fa-flask', 'fa-palette', 'fa-landmark', 'fa-language', 'fa-star', 'fa-heart', 'fa-globe'];

        let html = '';

        groupes.forEach((groupe, gIndex) => {
            const color = colors[gIndex % colors.length];
            const icon  = icons[gIndex % icons.length];

            html += `
            <div class="groupe-card mb-4" style="background:#fff;border-radius:16px;border:1px solid var(--border-color);box-shadow:var(--shadow-xs);overflow:hidden;">
                <!-- En-tête du groupe -->
                <div style="background:linear-gradient(135deg,#2c3e90,#3b4fa0);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                    <div style="display:flex;align-items:center;gap:12px;color:#fff;">
                        <div style="width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                            <i class="fa-solid ${icon}"></i>
                        </div>
                        <div>
                            <strong style="font-size:1rem;">${escapeHtml(groupe.libelle)}</strong>
                            <span style="display:block;font-size:0.72rem;opacity:0.75;">
                                ${groupe.code ? escapeHtml(groupe.code) : ''} — ${groupe.nb_sous_matieres} sous-matière(s)
                            </span>
                        </div>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <button class="btn btn-sm btn-outline-primary" style="color:#fff;border-color:rgba(255,255,255,0.4);" 
                                onclick="editMatiere(${groupe.id})" title="Modifier le groupe">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                    </div>
                </div>
                <!-- Sous-matières -->
                <div style="padding:16px 24px;">
                    ${groupe.sous_matieres.length === 0 ? `
                        <div class="empty-state" style="padding:20px;">
                            <i class="fa-solid fa-list-ul"></i>
                            <p>Aucune sous-matière</p>
                        </div>
                    ` : `
                        <div style="display:flex;flex-direction:column;gap:8px;">
                            ${groupe.sous_matieres.map(sm => {
                                const classesBadges = sm.classes && sm.classes.length > 0 
                                    ? sm.classes.map(c => `
                                        <span class="badge badge-info" style="font-size:0.68rem;margin:2px;" 
                                              title="Maxima: ${c.maxima_final}">
                                            ${escapeHtml(c.nom)} (${c.maxima_final})
                                        </span>`).join('')
                                    : '<span class="badge badge-secondary" style="font-size:0.68rem;">Aucune classe</span>';

                                return `
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;flex-wrap:wrap;gap:8px;">
                                    <div style="display:flex;align-items:center;gap:10px;flex:1;min-width:200px;">
                                        <div style="width:32px;height:32px;border-radius:8px;background:${sm.nb_classes > 0 ? '#d1fae5' : '#f1f5f9'};display:flex;align-items:center;justify-content:center;font-size:0.75rem;flex-shrink:0;">
                                            <i class="fa-solid fa-circle-${sm.nb_classes > 0 ? 'check text-success' : 'text-muted'}"></i>
                                        </div>
                                        <div>
                                            <strong style="font-size:0.85rem;color:var(--color-dark);">${escapeHtml(sm.libelle)}</strong>
                                            ${sm.code ? `<span style="font-size:0.7rem;color:var(--color-gray-400);margin-left:6px;">${escapeHtml(sm.code)}</span>` : ''}
                                            <div style="margin-top:2px;">
                                                <span class="badge badge-primary" style="font-size:0.68rem;">
                                                    <i class="fa-solid fa-weight-scale"></i> Max: ${sm.maxima}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                        <div style="max-width:300px;display:flex;flex-wrap:wrap;gap:3px;">
                                            ${classesBadges}
                                        </div>
                                        <div style="display:flex;gap:4px;">
                                            <button class="btn-icon btn-icon-view" style="width:30px;height:30px;" 
                                                    onclick="openClassesModal(${sm.id}, '${escapeHtml(sm.libelle)}')" 
                                                    title="Gérer les classes">
                                                <i class="fa-solid fa-link"></i>
                                            </button>
                                            <button class="btn-icon btn-icon-edit" style="width:30px;height:30px;" 
                                                    onclick="editMatiere(${sm.id})" title="Modifier">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button class="btn-icon btn-icon-delete" style="width:30px;height:30px;" 
                                                    onclick="deleteMatiere(${sm.id}, '${escapeHtml(sm.libelle)}')" title="Supprimer">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>`;
                            }).join('')}
                        </div>
                    `}
                </div>
            </div>`;
        });

        matieresContainer.innerHTML = html;
    }

    // ============================================================
    // 4. PAGINATION
    // ============================================================
    function renderPagination(data) {
        if (!paginationContainer) return;
        const totalPages = data.pages;
        if (totalPages <= 1) { paginationContainer.innerHTML = ''; return; }

        let html = '';
        html += `<button class="pagination-btn" onclick="loadMatieres(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-left"></i></button>`;
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="loadMatieres(${i})">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }
        html += `<button class="pagination-btn" onclick="loadMatieres(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-right"></i></button>`;
        paginationContainer.innerHTML = html;
    }

    // ============================================================
    // 5. RECHERCHE
    // ============================================================
    if (searchInput) searchInput.addEventListener('input', debounce(() => loadMatieres(1), 400));
    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            loadMatieres(1);
        });
    }

    // ============================================================
    // 6. NAVIGATION
    // ============================================================
    window.editMatiere = function(id) {
        window.location.href = '/gestion_scolaire/matieres/modifier/' + id;
    };

    // ============================================================
    // 7. SUPPRESSION
    // ============================================================
    window.deleteMatiere = function(id, nom) {
        showConfirmDialog(
            `Êtes-vous sûr de vouloir supprimer <strong>"${escapeHtml(nom)}"</strong> ?<br>
             <small>Cette action est irréversible si aucune évaluation n'est liée.</small>`,
            async (confirmed) => {
                if (!confirmed) return;
                const formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('id', id);
                try {
                    const res = await fetch('/gestion_scolaire/matieres', {
                        method: 'POST', body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    showFlash(data.success ? 'success' : 'error', data.message);
                    if (data.success) loadMatieres(currentPage);
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            },
            { confirmText: 'Supprimer', confirmClass: 'btn-danger', iconColor: 'var(--color-error)' }
        );
    };

    // ============================================================
    // 8. MODALE : GESTION DES CLASSES
    // ============================================================
    window.openClassesModal = function(matiereId, matiereNom) {
        currentMatiereId = matiereId;
        if (modalMatiereName) modalMatiereName.textContent = matiereNom;

        const formData = new FormData();
        formData.append('action', 'getClasses');
        formData.append('matiere_id', matiereId);

        fetch('/gestion_scolaire/matieres', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (modalMaximaGlobal) modalMaximaGlobal.textContent = data.maxima_global;
                renderClassesModal(data.classes, data.maxima_global);
                if (modalClasses) modalClasses.classList.add('show');
                document.body.style.overflow = 'hidden';
            } else {
                showFlash('error', data.message);
            }
        })
        .catch(() => showFlash('error', 'Erreur de connexion.'));
    };

    function renderClassesModal(classes, maximaGlobal) {
        if (!modalClassesList) return;

        if (classes.length === 0) {
            modalClassesList.innerHTML = '<div class="empty-state"><i class="fa-solid fa-chalkboard"></i><p>Aucune classe</p></div>';
            return;
        }

        const levelColors = ['n1', 'n2', 'n3', 'n4', 'n5', 'n6'];

        modalClassesList.innerHTML = classes.map(c => `
            <div class="classe-association-item ${c.est_active == 1 ? '' : 'inactive'}" 
                 style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:#f8fafc;border-radius:10px;margin-bottom:8px;border:1px solid #e2e8f0;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="classe-niveau-badge ${levelColors[c.niveau - 1] || 'n1'}">${c.niveau}</div>
                    <div>
                        <div style="font-weight:600;font-size:0.85rem;">${escapeHtml(c.nom)}</div>
                        <div style="font-size:0.72rem;color:var(--color-gray-400);">Salle ${escapeHtml(c.salle || '—')}</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <label style="font-size:0.72rem;color:var(--color-gray-500);">Maxima:</label>
                        <input type="number" 
                               class="coeff-input" 
                               value="${c.maxima_override !== null ? c.maxima_override : maximaGlobal}" 
                               min="1" max="1000"
                               onchange="updateMaxima(${currentMatiereId}, ${c.id}, this.value)"
                               style="width:65px;padding:5px 8px;text-align:center;font-size:0.82rem;font-weight:600;border:1.5px solid var(--border-color);border-radius:6px;">
                    </div>
                    <label class="association-toggle" onclick="toggleAssociation(${currentMatiereId}, ${c.id}, this)" 
                           title="${c.est_active == 1 ? 'Désactiver' : 'Activer'}">
                        <input type="checkbox" ${c.est_active == 1 ? 'checked' : ''}>
                        <span class="association-toggle-slider"></span>
                    </label>
                </div>
            </div>
        `).join('');
    }

    window.updateMaxima = function(matiereId, classeId, maxima) {
        const formData = new FormData();
        formData.append('action', 'updateMaxima');
        formData.append('matiere_id', matiereId);
        formData.append('classe_id', classeId);
        formData.append('maxima_override', maxima);

        fetch('/gestion_scolaire/matieres', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) showFlash('error', data.message);
        })
        .catch(() => showFlash('error', 'Erreur de connexion.'));
    };

    window.toggleAssociation = function(matiereId, classeId, toggle) {
        const checkbox = toggle.querySelector('input');
        const item = toggle.closest('.classe-association-item');
        const isActive = checkbox.checked;

        const formData = new FormData();
        formData.append('matiere_id', matiereId);
        formData.append('classe_id', classeId);
        formData.append('action', isActive ? 'associerClasse' : 'dissocierClasse');

        fetch('/gestion_scolaire/matieres', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (item) item.classList.toggle('inactive', !isActive);
            } else {
                checkbox.checked = !isActive;
                if (item) item.classList.toggle('inactive', isActive);
                showFlash('error', data.message);
            }
        })
        .catch(() => {
            checkbox.checked = !isActive;
            showFlash('error', 'Erreur de connexion.');
        });
    };

    // Fermer la modale
    function closeModal() {
        if (modalClasses) modalClasses.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (btnCloseModal) btnCloseModal.addEventListener('click', closeModal);
    if (btnCancelModal) btnCancelModal.addEventListener('click', closeModal);
    if (modalClasses) {
        modalClasses.addEventListener('click', function(e) {
            if (e.target === modalClasses) closeModal();
        });
    }

    // ============================================================
    // 9. MESSAGES FLASH
    // ============================================================
    function showFlash(type, message) {
        if (!flashMessage) return;
        const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
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
    // 10. INITIALISATION
    // ============================================================
    if (matieresContainer) loadMatieres(1);
    window.loadMatieres = loadMatieres;

    console.log('%cModule Matières (Bulletin Congolais)%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});