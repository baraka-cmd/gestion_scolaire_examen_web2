/* ============================================================
   Fichier : public/assets/js/modules/parents.js
   Rôle    : Gestion du module parents (liste, filtres, actions)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const tbodyParents       = document.getElementById('tbodyParents');
    const searchInput        = document.getElementById('searchParents');
    const filterLien         = document.getElementById('filterLien');
    const btnResetFilters    = document.getElementById('btnResetFilters');
    const paginationContainer = document.getElementById('paginationParents');
    const totalParentsEl     = document.getElementById('totalParents');
    const btnAddParent       = document.getElementById('btnAddParent');
    const flashMessage       = document.getElementById('flashMessage');

    let currentPage = 1;

    // ============================================================
    // 2. CHARGEMENT DE LA LISTE
    // ============================================================
    function loadParents(page = 1) {
        currentPage = page;

        if (tbodyParents) {
            tbodyParents.innerHTML = `
                <tr><td colspan="8">
                    <div style="text-align:center;padding:30px;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;color:var(--color-primary);"></i>
                        <p style="margin-top:10px;color:var(--color-gray-500);">Chargement des parents...</p>
                    </div>
                </td></tr>`;
        }

        const formData = new FormData();
        formData.append('action', 'liste');
        formData.append('page', page);
        formData.append('recherche', searchInput?.value || '');
        formData.append('lien', filterLien?.value || '');

        fetch('/gestion_scolaire/parents', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderTable(data.parents);
                renderPagination(data);
                if (totalParentsEl) totalParentsEl.textContent = data.total;
            } else {
                showFlash('error', data.message);
            }
        })
        .catch(err => {
            console.error('Erreur chargement parents:', err);
            showFlash('error', 'Erreur de connexion au serveur.');
        });
    }

    // ============================================================
    // 3. RENDU TABLEAU
    // ============================================================
    function renderTable(parents) {
        if (!tbodyParents) return;

        if (parents.length === 0) {
            tbodyParents.innerHTML = `
                <tr><td colspan="8">
                    <div class="empty-state">
                        <i class="fa-solid fa-people-roof"></i>
                        <p>Aucun parent trouvé</p>
                    </div>
                </td></tr>`;
            return;
        }

        const colors = ['blue', 'green', 'orange', 'purple', 'pink', 'cyan'];
        const lienIcons = {
            'Père': 'fa-mars', 'Mère': 'fa-venus', 'Tuteur': 'fa-user-tie',
            'Tutrice': 'fa-user-tie', 'Grand-père': 'fa-person-cane',
            'Grand-mère': 'fa-person-cane', 'Oncle': 'fa-user', 'Tante': 'fa-user',
            'Frère': 'fa-user', 'Sœur': 'fa-user', 'Autre': 'fa-user'
        };

        tbodyParents.innerHTML = parents.map((parent, index) => {
            const lienClass = ['Père', 'Mère'].includes(parent.lien) ? parent.lien.toLowerCase() :
                            ['Tuteur', 'Tutrice'].includes(parent.lien) ? parent.lien.toLowerCase() : 'autre';
            const icon = lienIcons[parent.lien] || 'fa-user';

            return `
                <tr>
                    <td>
                        <div class="parent-cell">
                            <div class="parent-avatar-placeholder ${colors[index % colors.length]}">
                                ${escapeHtml(parent.prenom.charAt(0))}${escapeHtml(parent.nom.charAt(0))}
                            </div>
                            <div class="parent-info">
                                <span class="parent-name">
                                    <a href="/gestion_scolaire/parents/fiche/${parent.id}">${escapeHtml(parent.prenom)} ${escapeHtml(parent.nom)}</a>
                                </span>
                                <span class="parent-phone">${escapeHtml(parent.telephone_principal || '—')}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="lien-badge ${lienClass}">
                            <i class="fa-solid ${icon}"></i> ${escapeHtml(parent.lien)}
                        </span>
                    </td>
                    <td>${escapeHtml(parent.telephone_principal || '—')}</td>
                    <td>${escapeHtml(parent.email || '—')}</td>
                    <td>
                        <span class="enfants-count ${parent.nb_enfants > 1 ? 'multiple' : ''}">
                            <i class="fa-solid fa-children"></i> ${parent.nb_enfants || 0} enfant(s)
                        </span>
                    </td>
                    <td>${escapeHtml(parent.profession || '—')}</td>
                    <td>
                        ${parent.est_contact_urgence == 1 
                            ? '<span class="badge badge-success"><i class="fa-solid fa-phone"></i> Urgence</span>' 
                            : '<span class="badge badge-secondary">Secondaire</span>'}
                    </td>
                    <td>
                        <div class="actions-cell">
                            <button class="btn-icon btn-icon-view" onclick="viewParent(${parent.id})" title="Voir la fiche">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="btn-icon btn-icon-edit" onclick="editParent(${parent.id})" title="Modifier">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button class="btn-icon btn-icon-delete" onclick="deleteParent(${parent.id}, '${escapeHtml(parent.prenom)} ${escapeHtml(parent.nom)}')" title="Supprimer">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }).join('');
    }

    // ============================================================
    // 4. PAGINATION
    // ============================================================
    function renderPagination(data) {
        if (!paginationContainer) return;
        const totalPages = data.pages;
        if (totalPages <= 1) { paginationContainer.innerHTML = ''; return; }

        let html = '';
        html += `<button class="pagination-btn" onclick="loadParents(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-left"></i></button>`;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="loadParents(${i})">${i}</button>`;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="pagination-ellipsis">...</span>`;
            }
        }

        html += `<button class="pagination-btn" onclick="loadParents(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            <i class="fa-solid fa-chevron-right"></i></button>`;

        paginationContainer.innerHTML = html;
    }

    // ============================================================
    // 5. FILTRES
    // ============================================================
    if (searchInput) searchInput.addEventListener('input', debounce(() => loadParents(1), 400));
    if (filterLien) filterLien.addEventListener('change', () => loadParents(1));

    if (btnResetFilters) {
        btnResetFilters.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (filterLien) filterLien.value = '';
            loadParents(1);
        });
    }

    // ============================================================
    // 6. NAVIGATION
    // ============================================================
    if (btnAddParent) {
        btnAddParent.addEventListener('click', () => {
            window.location.href = '/gestion_scolaire/parents/ajouter';
        });
    }

    window.viewParent = function(id) {
        window.location.href = '/gestion_scolaire/parents/fiche/' + id;
    };

    window.editParent = function(id) {
        window.location.href = '/gestion_scolaire/parents/modifier/' + id;
    };

    // ============================================================
    // 7. SUPPRESSION
    // ============================================================
    window.deleteParent = function(id, nom) {
        showConfirmDialog(
            `Êtes-vous sûr de vouloir supprimer <strong>"${nom}"</strong> ?<br>
             <small>Si des enfants sont liés, la suppression sera impossible.</small>`,
            async (confirmed) => {
                if (!confirmed) return;

                const formData = new FormData();
                formData.append('action', 'supprimer');
                formData.append('id', id);

                try {
                    const res = await fetch('/gestion_scolaire/parents', {
                        method: 'POST', body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    showFlash(data.success ? 'success' : 'error', data.message);
                    if (data.success) loadParents(currentPage);
                } catch (err) {
                    showFlash('error', 'Erreur de connexion.');
                }
            },
            { confirmText: 'Supprimer', confirmClass: 'btn-danger', iconColor: 'var(--color-error)' }
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
    if (tbodyParents) loadParents(1);
    window.loadParents = loadParents;

    console.log('%cModule Parents%c initialisé.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});