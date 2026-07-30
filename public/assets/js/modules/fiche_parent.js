/* ============================================================
   Fichier : public/assets/js/modules/fiche_parent.js
   Rôle    : Gestion de la fiche parent (onglets, liaison, dissociation)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const parentTabs      = document.querySelectorAll('.parent-tab');
    const parentPanels    = document.querySelectorAll('.parent-panel');
    const btnPrintFiche   = document.getElementById('btnPrintFiche');
    const formLier        = document.getElementById('formLierEleve');
    const btnDissocier    = document.querySelectorAll('.btn-dissocier');

    if (parentTabs.length === 0) return;

    // ============================================================
    // 2. GESTION DES ONGLETS
    // ============================================================
    function switchTab(tabId) {
        parentTabs.forEach(tab => tab.classList.remove('active'));
        parentPanels.forEach(panel => panel.classList.remove('active'));

        const activeTab = document.querySelector(`[data-tab="${tabId}"]`);
        if (activeTab) activeTab.classList.add('active');

        const activePanel = document.getElementById(`panel-${tabId}`);
        if (activePanel) activePanel.classList.add('active');

        try {
            localStorage.setItem('fiche_parent_active_tab', tabId);
        } catch (e) {}
    }

    parentTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            if (tabId) switchTab(tabId);
        });

        tab.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // Restaurer l'onglet actif
    try {
        const savedTab = localStorage.getItem('fiche_parent_active_tab');
        if (savedTab && document.querySelector(`[data-tab="${savedTab}"]`)) {
            switchTab(savedTab);
        }
    } catch (e) {}

    // ============================================================
    // 3. IMPRESSION
    // ============================================================
    if (btnPrintFiche) {
        btnPrintFiche.addEventListener('click', () => {
            parentPanels.forEach(panel => panel.classList.add('active'));
            window.print();
            const activeTab = document.querySelector('.parent-tab.active');
            if (activeTab) switchTab(activeTab.dataset.tab);
        });
    }

    // ============================================================
    // 4. COPIER LE TÉLÉPHONE
    // ============================================================
    const phoneEl = document.getElementById('telephoneParent');
    if (phoneEl) {
        phoneEl.addEventListener('click', async () => {
            const phone = phoneEl.textContent.trim();
            try {
                await navigator.clipboard.writeText(phone);
                showToast('Numéro copié !', 'success');
            } catch (err) {
                const input = document.createElement('input');
                input.value = phone;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                showToast('Numéro copié !', 'success');
            }
        });

        phoneEl.style.cursor = 'pointer';
        phoneEl.title = 'Cliquez pour copier le numéro';
    }

        // ============================================================
    // 5. LIER UN ENFANT & RECHERCHE EN TEMPS RÉEL
    // ============================================================
    const searchEleveLier = document.getElementById('searchEleveLier');
    const elevesLierContainer = document.getElementById('elevesLierContainer');

    // 5.1 Filtrage en temps réel
    if (searchEleveLier && elevesLierContainer) {
        searchEleveLier.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const items = elevesLierContainer.querySelectorAll('.eleve-lier-item');
            let visibleCount = 0;

            items.forEach(item => {
                const name = item.querySelector('.eleve-lier-name')?.textContent || '';
                const detail = item.querySelector('.eleve-lier-detail')?.textContent || '';
                const fullText = (name + ' ' + detail).toLowerCase();

                if (query === '' || fullText.includes(query)) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Gérer l'affichage du message "aucun résultat"
            const oldMsg = elevesLierContainer.querySelector('.no-results-msg');
            if (oldMsg) oldMsg.remove();

            if (visibleCount === 0 && query !== '') {
                const msg = document.createElement('div');
                msg.className = 'no-results-msg empty-state';
                msg.style.padding = '15px';
                msg.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i><p style="margin:5px 0 0 0;font-size:0.85rem;">Aucun élève trouvé</p>';
                elevesLierContainer.appendChild(msg);
            }
        });

        // Highlight visuel de l'élément sélectionné
        elevesLierContainer.addEventListener('change', function(e) {
            if (e.target.type === 'radio') {
                elevesLierContainer.querySelectorAll('.eleve-lier-item').forEach(item => {
                    item.classList.remove('selected');
                });
                const selectedItem = e.target.closest('.eleve-lier-item');
                if (selectedItem) selectedItem.classList.add('selected');
            }
        });
    }

    // 5.2 Soumission Ajax
    if (formLier) {
        formLier.addEventListener('submit', async function(e) {
            e.preventDefault();

            const selectedRadio = this.querySelector('input[name="eleve_id"]:checked');
            if (!selectedRadio) {
                showToast('Veuillez sélectionner un élève dans la liste.', 'warning');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const formData = new FormData(this);
            formData.append('action', 'lierEleve');

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Liaison en cours...';
            }

            try {
                const res = await fetch('/gestion_scolaire/parents', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data.success) {
                    showToast('Enfant lié avec succès !', 'success');
                    setTimeout(() => location.reload(), 600);
                } else {
                    showToast(data.message || 'Une erreur est survenue.', 'error');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-link"></i> Lier l\'élève sélectionné';
                    }
                }
            } catch (err) {
                showToast('Erreur de connexion avec le serveur.', 'error');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-link"></i> Lier l\'élève sélectionné';
                }
            }
        });
    }


    // ============================================================
    // 6. DISSOCIER UN ENFANT
    // ============================================================
    btnDissocier.forEach(btn => {
        btn.addEventListener('click', function() {
            const eleveId = this.dataset.eleveId;
            const eleveNom = this.dataset.eleveNom || 'cet enfant';

            showConfirmDialog(
                `Voulez-vous dissocier <strong>"${escapeHtml(eleveNom)}"</strong> de ce parent ?<br>
                 <small>L'enfant doit garder au moins un parent dans le système.</small>`,
                async (confirmed) => {
                    if (!confirmed) return;

                    const formData = new FormData();
                    formData.append('action', 'delierEleve');
                    formData.append('parent_id', this.dataset.parentId);
                    formData.append('eleve_id', eleveId);

                    try {
                        const res = await fetch('/gestion_scolaire/parents', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();

                        if (data.success) {
                            showToast('Enfant dissocié avec succès.', 'success');
                            setTimeout(() => location.reload(), 800);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (err) {
                        showToast('Erreur de connexion.', 'error');
                    }
                },
                { confirmText: 'Dissocier', confirmClass: 'btn-danger', iconColor: 'var(--color-error)' }
            );
        });
    });

    // ============================================================
    // 7. TOAST
    // ============================================================
    function showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================================
    // 8. INITIALISATION
    // ============================================================
    console.log('%cFiche Parent%c initialisée.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});