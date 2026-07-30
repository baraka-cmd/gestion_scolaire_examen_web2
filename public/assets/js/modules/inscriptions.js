/* ============================================================
   Fichier : public/assets/js/modules/inscriptions.js
   Rôle    : Gestion du module des inscriptions
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM — LISTE
    // ============================================================
    const tbodyInscriptions    = document.getElementById('tbodyInscriptions');
    const searchInput          = document.getElementById('searchInscriptions');
    const filterClasse         = document.getElementById('filterClasse');
    const filterAnnee          = document.getElementById('filterAnnee');
    const filterStatut         = document.getElementById('filterStatut');
    const btnResetFilters      = document.getElementById('btnResetFilters');
    const paginationContainer  = document.getElementById('paginationInscriptions');
    const totalInscriptionsEl  = document.getElementById('totalInscriptions');
    const flashMessage         = document.getElementById('flashMessage');

    // Réinscription
    const btnReinscrire        = document.getElementById('btnReinscrire');
    const selectAllCheckbox    = document.getElementById('selectAll');
    const summaryPasse         = document.getElementById('summaryPasse');
    const summaryRedouble      = document.getElementById('summaryRedouble');
    const summaryExclu         = document.getElementById('summaryExclu');

    let currentPage = 1;

    // ============================================================
    // 2. CHARGEMENT DE LA LISTE
    // ============================================================
    function loadInscriptions(page = 1) {
        currentPage = page;

        if (tbodyInscriptions) {
            tbodyInscriptions.innerHTML = `
                <tr><td colspan="9">
                    <div style="text-align:center;padding:30px;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;color:var(--color-primary);"></i>
                        <p style="margin-top:10px;color:var(--color-gray-500);">Chargement...</p>
                    </div>
                </td></tr>`;
        }

        const formData = new FormData();
        formData.append('action', 'liste');
        formData.append('page', page);
        formData.append('recherche', searchInput?.value || '');
        formData.append('classe_id', filterClasse?.value || '');
        formData.append('annee_id', filterAnnee?.value || '');
        formData.append('statut', filterStatut?.value || '');

        fetch('/gestion_scolaire/inscriptions', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTable(data.inscriptions);
                renderPagination(data);
                if (totalInscriptionsEl) totalInscriptionsEl.textContent = data.total;
            } else {
                showFlash('error', data.message);
            }
        })
        .catch(err => {
            console.error('Erreur:', err);
            showFlash('error', 'Erreur de connexion.');
        });
    }

    function renderTable(inscriptions) {
        if (!tbodyInscriptions) return;
        if (inscriptions.length === 0) {
            tbodyInscriptions.innerHTML = '<tr><td colspan="9"><div class="empty-state"><i class="fa-solid fa-file-pen"></i><p>Aucune inscription trouvée</p></div></td></tr>';
            return;
        }

        const statutIcons = { 'Actif': 'fa-circle-check', 'Redoublant': 'fa-rotate', 'Transféré': 'fa-arrow-right-arrow-left', 'Exclu': 'fa-ban', 'Diplômé': 'fa-graduation-cap', 'Abandon': 'fa-circle-xmark' };

        tbodyInscriptions.innerHTML = inscriptions.map(i => `
            <tr>
                <td><span class="badge badge-primary">${escapeHtml(i.matricule)}</span></td>
                <td><strong>${escapeHtml(i.prenom)} ${escapeHtml(i.nom)}</strong></td>
                <td><span class="badge badge-info">${escapeHtml(i.classe_nom)}</span></td>
                <td>${escapeHtml(i.annee_libelle)}</td>
                <td>${escapeHtml(i.date_inscription_format)}</td>
                <td><span class="statut-badge ${i.statut.toLowerCase()}"><i class="fa-solid ${statutIcons[i.statut] || 'fa-circle'}"></i> ${escapeHtml(i.statut)}</span></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn-icon btn-icon-view" onclick="viewHistorique(${i.eleve_id})" title="Historique"><i class="fa-solid fa-clock-rotate-left"></i></button>
                        ${i.statut === 'Actif' ? `<button class="btn-icon btn-icon-edit" onclick="changeClasse(${i.eleve_id}, ${i.classe_id})" title="Changer de classe"><i class="fa-solid fa-arrow-right-arrow-left"></i></button>` : ''}
                        ${i.statut === 'Actif' ? `<button class="btn-icon btn-icon-delete" onclick="desinscrire(${i.id})" title="Désinscrire"><i class="fa-solid fa-user-slash"></i></button>` : ''}
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function renderPagination(data) {
        if (!paginationContainer) return;
        const totalPages = data.pages;
        if (totalPages <= 1) { paginationContainer.innerHTML = ''; return; }
        let html = `<button class="pagination-btn" onclick="loadInscriptions(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left"></i></button>`;
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="loadInscriptions(${i})">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }
        html += `<button class="pagination-btn" onclick="loadInscriptions(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right"></i></button>`;
        paginationContainer.innerHTML = html;
    }

    // ============================================================
    // 3. FILTRES
    // ============================================================
    if (searchInput) searchInput.addEventListener('input', debounce(() => loadInscriptions(1), 400));
    if (filterClasse) filterClasse.addEventListener('change', () => loadInscriptions(1));
    if (filterAnnee) filterAnnee.addEventListener('change', () => loadInscriptions(1));
    if (filterStatut) filterStatut.addEventListener('change', () => loadInscriptions(1));
    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterClasse) filterClasse.value = '';
            if (filterAnnee) filterAnnee.value = '';
            if (filterStatut) filterStatut.value = '';
            loadInscriptions(1);
        });
    }

    // ============================================================
    // 4. SÉLECTIONNER TOUT (RÉINSCRIPTION)
    // ============================================================
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.eleve-checkbox').forEach(cb => {
                const row = cb.closest('.eleve-reinscription-row');
                if (row && !row.classList.contains('exclu')) {
                    cb.checked = this.checked;
                }
            });
            updateSummary();
        });
    }

    // Déléguer le changement des checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('eleve-checkbox')) {
            updateSummary();
        }
        if (e.target.classList.contains('decision-select')) {
            updateSummary();
            updateRowStyle(e.target);
        }
    });

    function updateSummary() {
        let passe = 0, redouble = 0, exclu = 0;
        document.querySelectorAll('.eleve-checkbox:checked').forEach(cb => {
            const row = cb.closest('.eleve-reinscription-row');
            const select = row?.querySelector('.decision-select');
            if (select) {
                const val = select.value;
                if (val === 'Passe') passe++;
                else if (val === 'Redouble') redouble++;
                else if (val === 'Exclu') exclu++;
            }
        });
        if (summaryPasse) summaryPasse.textContent = passe;
        if (summaryRedouble) summaryRedouble.textContent = redouble;
        if (summaryExclu) summaryExclu.textContent = exclu;
    }

    function updateRowStyle(select) {
        const row = select.closest('.eleve-reinscription-row');
        if (row) {
            row.classList.remove('passe', 'redouble', 'exclu');
            const val = select.value;
            if (val === 'Passe') row.classList.add('passe');
            else if (val === 'Redouble') row.classList.add('redouble');
            else if (val === 'Exclu') row.classList.add('exclu');
        }
    }

    // ============================================================
    // 5. RÉINSCRIPTION MASSIVE
    // ============================================================
    if (btnReinscrire) {
        btnReinscrire.addEventListener('click', async () => {
            const selected = [];
            document.querySelectorAll('.eleve-checkbox:checked').forEach(cb => {
                const row = cb.closest('.eleve-reinscription-row');
                selected.push({
                    eleve_id: cb.value,
                    nouvelle_classe_id: row.dataset.classeDestination || row.querySelector('.classe-destination-select')?.value,
                    decision: row.querySelector('.decision-select')?.value || 'Passe',
                    ancienne_inscription_id: row.dataset.inscriptionId
                });
            });

            if (selected.length === 0) {
                showToast('Veuillez sélectionner au moins un élève.', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'reinscrireMassivement');
            formData.append('eleves', JSON.stringify(selected));

            setButtonLoading(btnReinscrire);

            try {
                const res = await fetch('/gestion_scolaire/inscriptions', {
                    method: 'POST', body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                showFlash(data.success ? 'success' : 'error', data.message);
                if (data.success) setTimeout(() => location.reload(), 1500);
            } catch (err) {
                showFlash('error', 'Erreur de connexion.');
            } finally {
                resetButtonLoading(btnReinscrire);
            }
        });
    }

    // ============================================================
    // 6. CHANGEMENT DE CLASSE
    // ============================================================
    window.changeClasse = function(eleveId, ancienneClasseId) {
        showConfirmDialog(
            `Changer la classe de cet élève ?<br><br>
             <label for="nouvelleClasseId" style="font-size:0.8rem;">Nouvelle classe :</label>
             <select id="nouvelleClasseId" class="form-control" style="margin-top:4px;"></select>
             <label for="motifChangement" style="font-size:0.8rem;margin-top:8px;display:block;">Motif :</label>
             <input type="text" id="motifChangement" class="form-control" value="Changement de classe" style="margin-top:4px;">`,
            async (confirmed) => {
                if (!confirmed) return;
                const nouvelleClasseId = document.getElementById('nouvelleClasseId')?.value;
                const motif = document.getElementById('motifChangement')?.value || 'Changement de classe';
                if (!nouvelleClasseId) { showToast('Sélectionnez une classe.', 'warning'); return; }

                const formData = new FormData();
                formData.append('action', 'changerClasse');
                formData.append('eleve_id', eleveId);
                formData.append('ancienne_classe_id', ancienneClasseId);
                formData.append('nouvelle_classe_id', nouvelleClasseId);
                formData.append('motif', motif);

                try {
                    const res = await fetch('/gestion_scolaire/inscriptions', {
                        method: 'POST', body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    showFlash(data.success ? 'success' : 'error', data.message);
                    if (data.success) setTimeout(() => location.reload(), 800);
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            }
        );

        // Charger les classes disponibles
        fetch('/gestion_scolaire/inscriptions', {
            method: 'POST',
            body: (() => { const f = new FormData(); f.append('action', 'getClassesDisponibles'); return f; })(),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('nouvelleClasseId');
                if (select) {
                    select.innerHTML = '<option value="">— Sélectionnez —</option>' +
                        data.classes.map(c => `<option value="${c.id}">${c.nom} (${c.nb_inscrits}/${c.capacite_max})</option>`).join('');
                }
            }
        });
    };

    // ============================================================
    // 7. DÉSINSCRIPTION
    // ============================================================
    window.desinscrire = function(inscriptionId) {
        showConfirmDialog(
            `Motif de désinscription :<br><br>
             <select id="statutDesinscription" class="form-control">
                 <option value="Abandon">Abandon</option>
                 <option value="Exclu">Exclusion</option>
                 <option value="Diplômé">Diplômé</option>
             </select>
             <label for="motifDesinscription" style="font-size:0.8rem;margin-top:8px;display:block;">Motif détaillé :</label>
             <input type="text" id="motifDesinscription" class="form-control" placeholder="Raison..." style="margin-top:4px;">`,
            async (confirmed) => {
                if (!confirmed) return;
                const statut = document.getElementById('statutDesinscription')?.value || 'Abandon';
                const motif = document.getElementById('motifDesinscription')?.value || '';
                if (!motif) { showToast('Le motif est obligatoire.', 'warning'); return; }

                const formData = new FormData();
                formData.append('action', 'desinscrire');
                formData.append('inscription_id', inscriptionId);
                formData.append('statut', statut);
                formData.append('motif', motif);

                try {
                    const res = await fetch('/gestion_scolaire/inscriptions', {
                        method: 'POST', body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    showFlash(data.success ? 'success' : 'error', data.message);
                    if (data.success) setTimeout(() => location.reload(), 800);
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            }
        );
    };

    // ============================================================
    // 8. HISTORIQUE
    // ============================================================
    window.viewHistorique = function(eleveId) {
        window.location.href = '/gestion_scolaire/inscriptions/historique/' + eleveId;
    };

    // ============================================================
    // 9. MESSAGES
    // ============================================================
    function showFlash(type, message) {
        if (!flashMessage) return;
        const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
        flashMessage.innerHTML = `<div class="alert alert-${type} alert-dismissible"><i class="fa-solid ${icons[type] || icons.info}"></i><span>${escapeHtml(message)}</span><button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button></div>`;
        flashMessage.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showToast(message, type) {
        if (typeof window.showToast === 'function') window.showToast(message, type);
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
    if (tbodyInscriptions) loadInscriptions(1);
    window.loadInscriptions = loadInscriptions;

    // Initialiser le résumé si présent
    updateSummary();

    console.log('%cModule Inscriptions%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});