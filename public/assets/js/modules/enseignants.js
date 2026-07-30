/* ============================================================
   Fichier : public/assets/js/modules/enseignants.js
   Rôle    : Gestion du module enseignants (liste, filtres, actions)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const tbodyEnseignants    = document.getElementById('tbodyEnseignants');
    const searchInput         = document.getElementById('searchEnseignants');
    const filterSpecialite    = document.getElementById('filterSpecialite');
    const filterStatut        = document.getElementById('filterStatut');
    const btnResetFilters     = document.getElementById('btnResetFilters');
    const paginationContainer = document.getElementById('paginationEnseignants');
    const totalEnseignantsEl  = document.getElementById('totalEnseignants');
    const btnAddEnseignant    = document.getElementById('btnAddEnseignant');
    const flashMessage        = document.getElementById('flashMessage');

    let currentPage = 1;

    // ============================================================
    // 2. CHARGEMENT DE LA LISTE
    // ============================================================
    function loadEnseignants(page = 1) {
        currentPage = page;

        if (tbodyEnseignants) {
            tbodyEnseignants.innerHTML = `
                <tr><td colspan="9">
                    <div style="text-align:center;padding:30px;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;color:var(--color-primary);"></i>
                        <p style="margin-top:10px;color:var(--color-gray-500);">Chargement des enseignants...</p>
                    </div>
                </td></tr>`;
        }

        const formData = new FormData();
        formData.append('action', 'liste');
        formData.append('page', page);
        formData.append('recherche', searchInput?.value || '');
        formData.append('specialite', filterSpecialite?.value || '');
        formData.append('statut', filterStatut?.value || '');

        fetch('/gestion_scolaire/enseignants', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTable(data.enseignants);
                renderPagination(data);
                if (totalEnseignantsEl) totalEnseignantsEl.textContent = data.total;
            } else {
                showFlash('error', data.message);
            }
        })
        .catch(err => {
            console.error('Erreur chargement enseignants:', err);
            showFlash('error', 'Erreur de connexion au serveur.');
        });
    }

    // ============================================================
    // 3. RENDU TABLEAU
    // ============================================================
    function renderTable(enseignants) {
        if (!tbodyEnseignants) return;

        if (enseignants.length === 0) {
            tbodyEnseignants.innerHTML = `
                <tr><td colspan="9">
                    <div class="empty-state">
                        <i class="fa-solid fa-chalkboard-user"></i>
                        <p>Aucun enseignant trouvé</p>
                    </div>
                </td></tr>`;
            return;
        }

        const colors = ['blue', 'green', 'orange', 'purple', 'pink', 'cyan'];

        tbodyEnseignants.innerHTML = enseignants.map((ens, index) => `
            <tr>
                <td>
                    <div class="enseignant-cell">
                        <div class="enseignant-avatar-placeholder ${colors[index % colors.length]}">
                            ${escapeHtml(ens.prenom.charAt(0))}${escapeHtml(ens.nom.charAt(0))}
                        </div>
                        <div class="enseignant-info">
                            <span class="enseignant-name">
                                <a href="/gestion_scolaire/enseignants/fiche/${ens.id}">${escapeHtml(ens.prenom)} ${escapeHtml(ens.nom)}</a>
                            </span>
                            <span class="enseignant-matricule">${escapeHtml(ens.matricule)}</span>
                        </div>
                    </div>
                </td>
                <td>${escapeHtml(ens.matricule)}</td>
                <td>
                    ${ens.specialite 
                        ? `<span class="specialite-badge"><i class="fa-solid fa-graduation-cap"></i> ${escapeHtml(ens.specialite)}</span>` 
                        : '<span class="text-muted">—</span>'}
                </td>
                <td>${escapeHtml(ens.telephone || '—')}</td>
                <td>${escapeHtml(ens.email || '—')}</td>
                <td>
                    ${ens.classe_nom 
                        ? `<span class="classe-badge assignee"><i class="fa-solid fa-chalkboard"></i> ${escapeHtml(ens.classe_nom)}</span>` 
                        : '<span class="classe-badge non-assignee"><i class="fa-solid fa-ban"></i> Aucune</span>'}
                </td>
                <td><span class="anciennete-badge"><i class="fa-solid fa-calendar"></i> ${escapeHtml(ens.date_embauche_format || '—')}</span></td>
                <td>
                    ${ens.est_actif == 1 
                        ? '<span class="badge badge-success">Actif</span>' 
                        : '<span class="badge badge-danger">Inactif</span>'}
                </td>
                <td>
                    <div class="actions-cell">
                        <button class="btn-icon btn-icon-view" onclick="viewEnseignant(${ens.id})" title="Voir la fiche">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button class="btn-icon btn-icon-edit" onclick="editEnseignant(${ens.id})" title="Modifier">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn-icon btn-icon-delete" onclick="deleteEnseignant(${ens.id}, '${escapeHtml(ens.prenom)} ${escapeHtml(ens.nom)}')" title="Désactiver">
                            <i class="fa-solid fa-user-slash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // ============================================================
    // 4. PAGINATION
    // ============================================================
    function renderPagination(data) {
        if (!paginationContainer) return;
        const totalPages = data.pages;
        if (totalPages <= 1) { paginationContainer.innerHTML = ''; return; }

        let html = '';
        html += `<button class="pagination-btn" onclick="loadEnseignants(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-left"></i></button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="loadEnseignants(${i})">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }

        html += `<button class="pagination-btn" onclick="loadEnseignants(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-right"></i></button>`;

        paginationContainer.innerHTML = html;
    }

    // ============================================================
    // 5. FILTRES
    // ============================================================
    if (searchInput) searchInput.addEventListener('input', debounce(() => loadEnseignants(1), 400));
    if (filterSpecialite) filterSpecialite.addEventListener('change', () => loadEnseignants(1));
    if (filterStatut) filterStatut.addEventListener('change', () => loadEnseignants(1));

    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterSpecialite) filterSpecialite.value = '';
            if (filterStatut) filterStatut.value = '';
            loadEnseignants(1);
        });
    }

    // ============================================================
    // 6. NAVIGATION
    // ============================================================
    if (btnAddEnseignant) {
        btnAddEnseignant.addEventListener('click', () => {
            window.location.href = '/gestion_scolaire/enseignants/ajouter';
        });
    }

    window.viewEnseignant = function(id) {
        window.location.href = '/gestion_scolaire/enseignants/fiche/' + id;
    };

    window.editEnseignant = function(id) {
        window.location.href = '/gestion_scolaire/enseignants/modifier/' + id;
    };

    // ============================================================
    // 7. SUPPRESSION (DÉSACTIVATION)
    // ============================================================
    window.deleteEnseignant = function(id, nom) {
        showConfirmDialog(
            `Êtes-vous sûr de vouloir désactiver <strong>"${nom}"</strong> ?<br>
             <small>L'enseignant sera marqué comme inactif. Sa classe sera libérée.</small>`,
            async (confirmed) => {
                if (!confirmed) return;

                const formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('id', id);

                try {
                    const res = await fetch('/gestion_scolaire/enseignants', {
                        method: 'POST', body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    showFlash(data.success ? 'success' : 'error', data.message);
                    if (data.success) loadEnseignants(currentPage);
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            },
            { confirmText: 'Désactiver', confirmClass: 'btn-danger', iconColor: 'var(--color-error)' }
        );
    };

    // ============================================================
    // 8. MESSAGES FLASH
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
    // 9. INITIALISATION
    // ============================================================
    if (tbodyEnseignants) loadEnseignants(1);
    window.loadEnseignants = loadEnseignants;

    console.log('%cModule Enseignants%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});