/* ============================================================
   Fichier : public/assets/js/modules/eleves.js
   Rôle    : Gestion du module élèves (liste, filtres, actions)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const tbodyEleves        = document.getElementById('tbodyEleves');
    const searchInput        = document.getElementById('searchEleves');
    const filterClasse       = document.getElementById('filterClasse');
    const filterSexe         = document.getElementById('filterSexe');
    const filterStatut       = document.getElementById('filterStatut');
    const btnResetFilters    = document.getElementById('btnResetFilters');
    const paginationContainer = document.getElementById('paginationEleves');
    const totalElevesEl      = document.getElementById('totalEleves');
    const btnAddEleve        = document.getElementById('btnAddEleve');
    const btnImport          = document.getElementById('btnImport');
    const btnExport          = document.getElementById('btnExport');
    const flashMessage       = document.getElementById('flashMessage');

    let currentPage = 1;
    let currentFilters = {};

    // ============================================================
    // 2. CHARGEMENT DE LA LISTE
    // ============================================================
    function loadEleves(page = 1) {
        currentPage = page;

        if (tbodyEleves) {
            tbodyEleves.innerHTML = `
                <tr><td colspan="9">
                    <div style="text-align:center;padding:30px;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;color:var(--color-primary);"></i>
                        <p style="margin-top:10px;color:var(--color-gray-500);">Chargement des élèves...</p>
                    </div>
                </td></tr>`;
        }

        const formData = new FormData();
        formData.append('action', 'liste');
        formData.append('page', page);
        formData.append('recherche', searchInput?.value || '');
        formData.append('classe_id', filterClasse?.value || '');
        formData.append('sexe', filterSexe?.value || '');
        formData.append('statut', filterStatut?.value || '');

        currentFilters = {
            recherche: searchInput?.value || '',
            classe_id: filterClasse?.value || '',
            sexe: filterSexe?.value || '',
            statut: filterStatut?.value || ''
        };

        fetch('/gestion_scolaire/eleves', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTable(data.eleves);
                renderPagination(data);
                if (totalElevesEl) totalElevesEl.textContent = data.total;
            } else {
                showFlash('error', data.message);
            }
        })
        .catch(err => {
            console.error('Erreur chargement élèves:', err);
            showFlash('error', 'Erreur de connexion au serveur.');
        });
    }

    // ============================================================
    // 3. RENDU TABLEAU
    // ============================================================
    function renderTable(eleves) {
        if (!tbodyEleves) return;

        if (eleves.length === 0) {
            tbodyEleves.innerHTML = `
                <tr>
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <p>Aucun élève trouvé</p>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        const colors = ['blue', 'green', 'orange', 'purple', 'pink', 'cyan', 'red'];

        tbodyEleves.innerHTML = eleves.map((eleve, index) => `
            <tr>
                <td>
                    <div class="eleve-cell">
                        <div class="eleve-avatar-placeholder ${colors[index % colors.length]}">
                            ${escapeHtml(eleve.prenom.charAt(0))}${escapeHtml(eleve.nom.charAt(0))}
                        </div>
                        <div class="eleve-info">
                            <span class="eleve-name">
                                <a href="/gestion_scolaire/eleves/fiche/${eleve.id}">${escapeHtml(eleve.prenom)} ${escapeHtml(eleve.nom)}</a>
                            </span>
                            <span class="eleve-matricule">${escapeHtml(eleve.matricule)}</span>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-primary">${escapeHtml(eleve.matricule)}</span></td>
                <td>
                    <span class="sexe-badge ${eleve.sexe === 'M' ? 'masculin' : 'feminin'}">
                        <i class="fa-solid fa-${eleve.sexe === 'M' ? 'mars' : 'venus'}"></i>
                        ${eleve.sexe === 'M' ? 'Garçon' : 'Fille'}
                    </span>
                </td>
                <td>${escapeHtml(eleve.date_naissance_format || '—')}</td>
                <td><span class="age-badge">${eleve.age || '—'} an(s)</span></td>
                <td>${escapeHtml(eleve.classe_nom || '—')}</td>
                <td>${escapeHtml(eleve.telephone || '—')}</td>
                <td>
                    ${eleve.est_actif == 1 
                        ? '<span class="badge badge-success">Actif</span>' 
                        : '<span class="badge badge-danger">Inactif</span>'}
                </td>
                <td>
                    <div class="actions-cell">
                        <button class="btn-icon btn-icon-view" onclick="viewEleve(${eleve.id})" title="Voir la fiche">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <button class="btn-icon btn-icon-edit" onclick="editEleve(${eleve.id})" title="Modifier">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn-icon btn-icon-delete" onclick="deleteEleve(${eleve.id}, '${escapeHtml(eleve.prenom)} ${escapeHtml(eleve.nom)}')" title="Désactiver">
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
        html += `<button class="pagination-btn" onclick="loadEleves(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-left"></i>
        </button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="loadEleves(${i})">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }

        html += `<button class="pagination-btn" onclick="loadEleves(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-right"></i>
        </button>`;

        paginationContainer.innerHTML = html;
    }

    // ============================================================
    // 5. FILTRES ET RECHERCHE
    // ============================================================
    if (searchInput) searchInput.addEventListener('input', debounce(() => loadEleves(1), 400));
    if (filterClasse) filterClasse.addEventListener('change', () => loadEleves(1));
    if (filterSexe) filterSexe.addEventListener('change', () => loadEleves(1));
    if (filterStatut) filterStatut.addEventListener('change', () => loadEleves(1));

    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterClasse) filterClasse.value = '';
            if (filterSexe) filterSexe.value = '';
            if (filterStatut) filterStatut.value = '';
            loadEleves(1);
        });
    }

    // ============================================================
    // 6. BOUTONS D'ACTION
    // ============================================================
    if (btnAddEleve) {
        btnAddEleve.addEventListener('click', () => {
            window.location.href = '/gestion_scolaire/eleves/ajouter';
        });
    }

    if (btnImport) {
        btnImport.addEventListener('click', () => {
            window.location.href = '/gestion_scolaire/eleves/import';
        });
    }

    if (btnExport) {
        btnExport.addEventListener('click', () => {
            exportEleves();
        });
    }

    // ============================================================
    // 7. NAVIGATION
    // ============================================================
    window.viewEleve = function(id) {
        window.location.href = '/gestion_scolaire/eleves/fiche/' + id;
    };

    window.editEleve = function(id) {
        window.location.href = '/gestion_scolaire/eleves/modifier/' + id;
    };

    // ============================================================
    // 8. SUPPRESSION (DÉSACTIVATION)
    // ============================================================
    window.deleteEleve = function(id, nom) {
        showConfirmDialog(
            `Êtes-vous sûr de vouloir désactiver l'élève <strong>"${nom}"</strong> ?<br>
             <small>L'élève sera marqué comme inactif et son inscription sera terminée.</small><br><br>
             <label for="motifSuppression" style="font-size:0.8rem;">Motif :</label>
             <input type="text" id="motifSuppression" class="form-control" placeholder="Raison de la désactivation..." style="margin-top:6px;">`,
            async (confirmed) => {
                if (!confirmed) return;

                const motif = document.getElementById('motifSuppression')?.value || '';

                const formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('id', id);
                formData.append('motif', motif);

                try {
                    const res = await fetch('/gestion_scolaire/eleves', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();

                    if (data.success) {
                        showFlash('success', data.message);
                        loadEleves(currentPage);
                    } else {
                        showFlash('error', data.message);
                    }
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            },
            { confirmText: 'Désactiver', confirmClass: 'btn-danger', iconColor: 'var(--color-error)' }
        );
    };

    // ============================================================
    // 9. EXPORT
    // ============================================================
    function exportEleves() {
        const params = new URLSearchParams(currentFilters);
        window.open('/gestion_scolaire/eleves/export?' + params.toString(), '_blank');
    }

    // ============================================================
    // 10. MESSAGES FLASH
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
    // 11. INITIALISATION
    // ============================================================
    if (tbodyEleves) loadEleves(1);
    window.loadEleves = loadEleves;

    console.log('%cModule Élèves%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});