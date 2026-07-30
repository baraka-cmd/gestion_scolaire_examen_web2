/* ============================================================
   Fichier : public/assets/js/modules/classes.js
   Rôle    : Gestion du module classes (liste, ajout, modification)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const classesContainer   = document.getElementById('classesContainer');
    const tableClasses       = document.getElementById('tableClasses');
    const tbodyClasses       = document.getElementById('tbodyClasses');
    const searchInput        = document.getElementById('searchClasses');
    const filterAnnee        = document.getElementById('filterAnnee');
    const btnResetFilters    = document.getElementById('btnResetFilters');
    const paginationContainer = document.getElementById('paginationClasses');
    const totalClassesEl     = document.getElementById('totalClasses');
    const btnAddClasse       = document.getElementById('btnAddClasse');
    const flashMessage       = document.getElementById('flashMessage');
    const viewMode           = document.getElementById('viewMode');

    let currentPage = 1;
    let currentView = 'cards'; // 'cards' ou 'table'

    // ============================================================
    // 2. CHARGEMENT DE LA LISTE
    // ============================================================
    function loadClasses(page = 1) {
        currentPage = page;

        if (classesContainer) {
            classesContainer.innerHTML = `
                <div class="skeleton-classe-card"></div>
                <div class="skeleton-classe-card"></div>
                <div class="skeleton-classe-card"></div>`;
        }
        if (tbodyClasses) {
            tbodyClasses.innerHTML = `
                <tr><td colspan="7"><div style="text-align:center;padding:30px;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;color:var(--color-primary);"></i>
                    <p style="margin-top:10px;color:var(--color-gray-500);">Chargement...</p>
                </div></td></tr>`;
        }

        const formData = new FormData();
        formData.append('action', 'liste');
        formData.append('page', page);
        formData.append('recherche', searchInput?.value || '');
        formData.append('annee_id', filterAnnee?.value || '');

        fetch('/gestion_scolaire/classes', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (classesContainer) renderCards(data.classes);
                if (tbodyClasses) renderTable(data.classes);
                renderPagination(data);
                if (totalClassesEl) totalClassesEl.textContent = data.total;
            } else {
                showFlash('error', data.message);
            }
        })
        .catch(err => {
            console.error('Erreur chargement classes:', err);
            showFlash('error', 'Erreur de connexion au serveur.');
        });
    }

    // ============================================================
    // 3. RENDU CARTES
    // ============================================================
    function renderCards(classes) {
        if (!classesContainer) return;

        if (classes.length === 0) {
            classesContainer.innerHTML = `
                <div style="grid-column:1/-1;">
                    <div class="empty-state">
                        <i class="fa-solid fa-chalkboard"></i>
                        <p>Aucune classe trouvée</p>
                    </div>
                </div>`;
            return;
        }

        classesContainer.innerHTML = classes.map(classe => {
            const tauxRemplissage = classe.capacite_max > 0 ? Math.round((classe.nb_eleves / classe.capacite_max) * 100) : 0;
            const barColor = tauxRemplissage >= 90 ? 'high' : tauxRemplissage >= 70 ? 'medium' : 'low';
            const titulaire = classe.titulaire_nom && classe.titulaire_nom !== '—' 
                ? `${classe.titulaire_prenom} ${classe.titulaire_nom}` 
                : 'Aucun titulaire';

            return `
                <div class="classe-card" onclick="window.location.href='/gestion_scolaire/classes/detail/${classe.id}'">
                    <div class="classe-card-header">
                        <div class="classe-card-icon">
                            <i class="fa-solid fa-chalkboard"></i>
                        </div>
                        <div class="classe-card-title">
                            <h5>${escapeHtml(classe.nom)}</h5>
                            <span>Niveau ${classe.niveau} — ${escapeHtml(classe.annee_libelle)}</span>
                        </div>
                    </div>
                    <div class="classe-card-body">
                        <div class="classe-info-row">
                            <i class="fa-solid fa-door-open"></i>
                            <span>Salle <strong>${escapeHtml(classe.salle || '—')}</strong></span>
                        </div>
                        <div class="classe-info-row">
                            <i class="fa-solid fa-user-tie"></i>
                            <span>Titulaire : <strong>${escapeHtml(titulaire)}</strong></span>
                        </div>
                        <div class="classe-info-row">
                            <i class="fa-solid fa-users"></i>
                            <span>Effectif : <strong>${classe.nb_eleves} / ${classe.capacite_max}</strong></span>
                        </div>
                        <div class="capacity-bar mt-2">
                            <div class="capacity-bar-track">
                                <div class="capacity-bar-fill ${barColor}" style="width:${tauxRemplissage}%;"></div>
                            </div>
                            <span class="capacity-bar-text">${tauxRemplissage}%</span>
                        </div>
                    </div>
                    <div class="classe-card-footer">
                        <span class="classe-badge ${classe.nb_eleves >= classe.capacite_max ? 'danger' : classe.nb_eleves >= classe.capacite_max * 0.8 ? 'warning' : 'success'}">
                            <i class="fa-solid fa-${classe.nb_eleves >= classe.capacite_max ? 'circle-exclamation' : classe.nb_eleves >= classe.capacite_max * 0.8 ? 'triangle-exclamation' : 'circle-check'}"></i>
                            ${classe.nb_eleves >= classe.capacite_max ? 'Complet' : classe.nb_eleves >= classe.capacite_max * 0.8 ? 'Presque complet' : 'Places disponibles'}
                        </span>
                        <div class="d-flex gap-sm">
                            <button class="btn-icon btn-icon-edit" onclick="event.stopPropagation();editClasse(${classe.id})" title="Modifier">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="btn-icon btn-icon-delete" onclick="event.stopPropagation();deleteClasse(${classe.id}, '${escapeHtml(classe.nom)}')" title="Supprimer">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    // ============================================================
    // 4. RENDU TABLEAU
    // ============================================================
    function renderTable(classes) {
        if (!tbodyClasses) return;

        if (classes.length === 0) {
            tbodyClasses.innerHTML = `
                <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-chalkboard"></i><p>Aucune classe trouvée</p></div></td></tr>`;
            return;
        }

        tbodyClasses.innerHTML = classes.map(classe => {
            const tauxRemplissage = classe.capacite_max > 0 ? Math.round((classe.nb_eleves / classe.capacite_max) * 100) : 0;
            const barColor = tauxRemplissage >= 90 ? 'high' : tauxRemplissage >= 70 ? 'medium' : 'low';
            const titulaire = classe.titulaire_nom && classe.titulaire_nom !== '—' 
                ? `${classe.titulaire_prenom} ${classe.titulaire_nom}` 
                : '—';

            return `
                <tr>
                    <td><span class="classe-niveau-badge n${classe.niveau}">${classe.niveau}</span></td>
                    <td>
                        <strong><a href="/gestion_scolaire/classes/detail/${classe.id}" style="color:var(--color-dark);text-decoration:none;">${escapeHtml(classe.nom)}</a></strong>
                        <br><small class="text-muted">${escapeHtml(classe.annee_libelle)}</small>
                    </td>
                    <td>${escapeHtml(classe.salle || '—')}</td>
                    <td>${escapeHtml(titulaire)}</td>
                    <td>
                        <div class="capacity-bar">
                            <div class="capacity-bar-track">
                                <div class="capacity-bar-fill ${barColor}" style="width:${tauxRemplissage}%;"></div>
                            </div>
                            <span class="capacity-bar-text">${classe.nb_eleves}/${classe.capacite_max}</span>
                        </div>
                    </td>
                    <td>
                        ${classe.est_actif 
                            ? '<span class="badge badge-success">Actif</span>' 
                            : '<span class="badge badge-danger">Inactif</span>'}
                    </td>
                    <td>
                        <div class="actions-cell">
                            <button class="btn-icon btn-icon-view" onclick="window.location.href='/gestion_scolaire/classes/detail/${classe.id}'" title="Voir">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="btn-icon btn-icon-edit" onclick="editClasse(${classe.id})" title="Modifier">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="btn-icon btn-icon-delete" onclick="deleteClasse(${classe.id}, '${escapeHtml(classe.nom)}')" title="Supprimer">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }).join('');
    }

    // ============================================================
    // 5. PAGINATION
    // ============================================================
    function renderPagination(data) {
        if (!paginationContainer) return;
        const totalPages = data.pages;
        if (totalPages <= 1) { paginationContainer.innerHTML = ''; return; }

        let html = '';
        html += `<button class="pagination-btn" onclick="loadClasses(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="loadClasses(${i})">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }
        html += `<button class="pagination-btn" onclick="loadClasses(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
        paginationContainer.innerHTML = html;
    }

    // ============================================================
    // 6. FILTRES ET RECHERCHE
    // ============================================================
    if (searchInput) searchInput.addEventListener('input', debounce(() => loadClasses(1), 400));
    if (filterAnnee) filterAnnee.addEventListener('change', () => loadClasses(1));
    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterAnnee) filterAnnee.value = '';
            loadClasses(1);
        });
    }

    // ============================================================
    // 7. BOUTON AJOUT
    // ============================================================
    if (btnAddClasse) {
        btnAddClasse.addEventListener('click', () => {
            window.location.href = '/gestion_scolaire/classes/ajouter';
        });
    }

    // ============================================================
    // 8. ÉDITION
    // ============================================================
    window.editClasse = function(id) {
        window.location.href = '/gestion_scolaire/classes/modifier/' + id;
    };

    // ============================================================
    // 9. SUPPRESSION
    // ============================================================
    window.deleteClasse = function(id, nom) {
        showConfirmDialog(
            `Êtes-vous sûr de vouloir supprimer la classe <strong>"${nom}"</strong> ?<br><small>Cette action est irréversible.</small>`,
            async (confirmed) => {
                if (!confirmed) return;
                const formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('id', id);
                try {
                    const res = await fetch('/gestion_scolaire/classes', {
                        method: 'POST', body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    showFlash(data.success ? 'success' : 'error', data.message);
                    if (data.success) loadClasses(currentPage);
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            },
            { confirmText: 'Supprimer', confirmClass: 'btn-danger', iconColor: 'var(--color-error)' }
        );
    };

    // ============================================================
    // 10. CHANGEMENT DE VUE (cartes/tableau)
    // ============================================================
    if (viewMode) {
        viewMode.addEventListener('change', function() {
            currentView = this.value;
            if (currentView === 'table') {
                if (classesContainer) classesContainer.style.display = 'none';
                if (tableClasses) tableClasses.style.display = 'block';
            } else {
                if (classesContainer) classesContainer.style.display = 'grid';
                if (tableClasses) tableClasses.style.display = 'none';
            }
        });
    }

    // ============================================================
    // 11. MESSAGES FLASH
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
    // 12. INITIALISATION
    // ============================================================
    if (classesContainer || tbodyClasses) loadClasses(1);
    window.loadClasses = loadClasses;

    console.log('%cModule Classes%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});