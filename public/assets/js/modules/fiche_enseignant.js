/* ============================================================
   Fichier : public/assets/js/modules/fiche_enseignant.js
   Rôle    : Gestion de la fiche enseignant (onglets, interactions)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const enseignantTabs     = document.querySelectorAll('.enseignant-tab');
    const enseignantPanels   = document.querySelectorAll('.enseignant-panel');
    const btnPrintFiche      = document.getElementById('btnPrintFiche');

    if (enseignantTabs.length === 0) return;

    // ============================================================
    // 2. GESTION DES ONGLETS
    // ============================================================
    function switchTab(tabId) {
        enseignantTabs.forEach(tab => tab.classList.remove('active'));
        enseignantPanels.forEach(panel => panel.classList.remove('active'));

        const activeTab = document.querySelector(`[data-tab="${tabId}"]`);
        if (activeTab) activeTab.classList.add('active');

        const activePanel = document.getElementById(`panel-${tabId}`);
        if (activePanel) activePanel.classList.add('active');

        try {
            localStorage.setItem('fiche_enseignant_active_tab', tabId);
        } catch (e) {}
    }

    enseignantTabs.forEach(tab => {
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
        const savedTab = localStorage.getItem('fiche_enseignant_active_tab');
        if (savedTab && document.querySelector(`[data-tab="${savedTab}"]`)) {
            switchTab(savedTab);
        }
    } catch (e) {}

    // ============================================================
    // 3. IMPRESSION
    // ============================================================
    if (btnPrintFiche) {
        btnPrintFiche.addEventListener('click', () => {
            enseignantPanels.forEach(panel => panel.classList.add('active'));
            window.print();
            const activeTab = document.querySelector('.enseignant-tab.active');
            if (activeTab) switchTab(activeTab.dataset.tab);
        });
    }

    // ============================================================
    // 4. COPIER LE TÉLÉPHONE
    // ============================================================
    const phoneEl = document.getElementById('telephoneEnseignant');
    if (phoneEl) {
        phoneEl.addEventListener('click', async () => {
            const phone = phoneEl.textContent.trim();
            if (phone === '—') return;

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
    // 5. COPIER LE MATRICULE
    // ============================================================
    const matriculeEl = document.querySelector('.enseignant-hero-meta span:first-child');
    if (matriculeEl) {
        matriculeEl.addEventListener('click', async () => {
            const text = matriculeEl.textContent.trim();
            try {
                await navigator.clipboard.writeText(text);
                showToast('Matricule copié !', 'success');
            } catch (err) {
                const input = document.createElement('input');
                input.value = text;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                showToast('Matricule copié !', 'success');
            }
        });

        matriculeEl.style.cursor = 'pointer';
        matriculeEl.title = 'Cliquez pour copier le matricule';
    }

    // ============================================================
    // 6. CONFIRMATION CHANGEMENT DE CLASSE
    // ============================================================
    const btnChangerClasse = document.getElementById('btnChangerClasse');
    if (btnChangerClasse) {
        btnChangerClasse.addEventListener('click', () => {
            const enseignantId = btnChangerClasse.dataset.enseignantId;
            const classeActuelle = btnChangerClasse.dataset.classeActuelle || 'Aucune';

            // Charger les classes disponibles
            const formData = new FormData();
            formData.append('action', 'getClassesDisponibles');
            formData.append('exclude_enseignant_id', enseignantId);

            fetch('/gestion_scolaire/enseignants', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    showToast(data.message, 'error');
                    return;
                }

                let options = '<option value="">— Aucune classe —</option>';
                data.classes.forEach(c => {
                    options += `<option value="${c.id}">${c.nom} — Niv. ${c.niveau} (${c.salle})</option>`;
                });

                showConfirmDialog(
                    `Changer la classe titulaire ?<br>
                     <small>Classe actuelle : <strong>${escapeHtml(classeActuelle)}</strong></small><br><br>
                     <label for="nouvelleClasse" style="font-size:0.8rem;">Nouvelle classe :</label>
                     <select id="nouvelleClasse" class="form-control" style="margin-top:6px;">${options}</select>`,
                    async (confirmed) => {
                        if (!confirmed) return;

                        const nouvelleClasse = document.getElementById('nouvelleClasse')?.value || '';

                        const fd = new FormData();
                        fd.append('action', 'changerClasse');
                        fd.append('enseignant_id', enseignantId);
                        fd.append('classe_id', nouvelleClasse);

                        try {
                            const res = await fetch('/gestion_scolaire/enseignants', {
                                method: 'POST', body: fd,
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            const result = await res.json();
                            showToast(result.message, result.success ? 'success' : 'error');
                            if (result.success) setTimeout(() => location.reload(), 800);
                        } catch (err) {
                            showToast('Erreur de connexion.', 'error');
                        }
                    },
                    { confirmText: 'Changer', confirmClass: 'btn-primary' }
                );
            })
            .catch(() => showToast('Erreur de connexion.', 'error'));
        });
    }

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
    console.log('%cFiche Enseignant%c initialisée.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});