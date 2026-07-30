/* ============================================================
   Fichier : public/assets/js/modules/fiche_eleve.js
   Rôle    : Gestion de la fiche élève (onglets, interactions)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const profileTabs     = document.querySelectorAll('.profile-tab');
    const profilePanels   = document.querySelectorAll('.profile-panel');
    const btnPrintFiche   = document.getElementById('btnPrintFiche');

    if (profileTabs.length === 0) return;

    // ============================================================
    // 2. GESTION DES ONGLETS
    // ============================================================
    function switchTab(tabId) {
        // Désactiver tous les onglets
        profileTabs.forEach(tab => tab.classList.remove('active'));
        // Masquer tous les panneaux
        profilePanels.forEach(panel => panel.classList.remove('active'));

        // Activer l'onglet cliqué
        const activeTab = document.querySelector(`[data-tab="${tabId}"]`);
        if (activeTab) activeTab.classList.add('active');

        // Afficher le panneau correspondant
        const activePanel = document.getElementById(`panel-${tabId}`);
        if (activePanel) activePanel.classList.add('active');

        // Sauvegarder l'onglet actif dans le localStorage
        try {
            localStorage.setItem('fiche_eleve_active_tab', tabId);
        } catch (e) {}
    }

    profileTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            if (tabId) switchTab(tabId);
        });

        // Accessibilité clavier
        tab.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });

    // ============================================================
    // 3. RESTAURER L'ONGLET ACTIF
    // ============================================================
    try {
        const savedTab = localStorage.getItem('fiche_eleve_active_tab');
        if (savedTab && document.querySelector(`[data-tab="${savedTab}"]`)) {
            switchTab(savedTab);
        }
    } catch (e) {}

    // ============================================================
    // 4. IMPRESSION DE LA FICHE
    // ============================================================
    if (btnPrintFiche) {
        btnPrintFiche.addEventListener('click', () => {
            // Afficher tous les panneaux avant impression
            profilePanels.forEach(panel => panel.classList.add('active'));
            window.print();
            // Restaurer l'onglet actif après impression
            const activeTab = document.querySelector('.profile-tab.active');
            if (activeTab) {
                switchTab(activeTab.dataset.tab);
            }
        });
    }

    // ============================================================
    // 5. COPIER LE MATRICULE
    // ============================================================
    const matriculeEl = document.getElementById('matriculeEleve');
    if (matriculeEl) {
        matriculeEl.addEventListener('click', async () => {
            const matricule = matriculeEl.textContent.trim();
            try {
                await navigator.clipboard.writeText(matricule);
                showToast('Matricule copié !', 'success');
            } catch (err) {
                // Fallback
                const input = document.createElement('input');
                input.value = matricule;
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
            const eleveId = btnChangerClasse.dataset.eleveId;
            const classeActuelle = btnChangerClasse.dataset.classeActuelle || '';

            showConfirmDialog(
                `Changer la classe de l'élève ?<br>
                 <small>Classe actuelle : <strong>${escapeHtml(classeActuelle)}</strong></small><br><br>
                 <label for="nouvelleClasse" style="font-size:0.8rem;">Nouvelle classe :</label>
                 <select id="nouvelleClasse" class="form-control" style="margin-top:6px;">
                     <option value="">— Sélectionnez —</option>
                 </select>
                 <label for="motifChangement" style="font-size:0.8rem;margin-top:8px;display:block;">Motif :</label>
                 <input type="text" id="motifChangement" class="form-control" placeholder="Raison du changement..." style="margin-top:4px;">`,
                async (confirmed) => {
                    if (!confirmed) return;

                    const nouvelleClasse = document.getElementById('nouvelleClasse')?.value;
                    const motif = document.getElementById('motifChangement')?.value || '';

                    if (!nouvelleClasse) {
                        showToast('Veuillez sélectionner une classe.', 'warning');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('action', 'changerClasse');
                    formData.append('eleve_id', eleveId);
                    formData.append('nouvelle_classe_id', nouvelleClasse);
                    formData.append('motif', motif);

                    try {
                        const res = await fetch('/gestion_scolaire/eleves', {
                            method: 'POST', body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        showToast(data.message, data.success ? 'success' : 'error');
                        if (data.success) setTimeout(() => location.reload(), 1000);
                    } catch (err) {
                        showToast('Erreur de connexion.', 'error');
                    }
                },
                { confirmText: 'Changer', confirmClass: 'btn-primary' }
            );
        });
    }

    // ============================================================
    // 7. TOAST NOTIFICATION
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
// 9. SUPPRESSION (DÉSACTIVATION) D'UN ÉLÈVE — exposée globalement
// ============================================================
window.deleteEleveFromFiche = function(id, nom) {
    showConfirmDialog(
        'Êtes-vous sûr de vouloir désactiver <strong>"' + nom + '"</strong> ?<br><small>L\'élève sera marqué comme inactif.</small>',
        async (confirmed) => {
            if (!confirmed) return;
            const formData = new FormData();
            formData.append('action', 'supprimer');
            formData.append('id', id);
            try {
                const res = await fetch('/gestion_scolaire/eleves', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = '/gestion_scolaire/eleves';
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Erreur de connexion.', 'error');
            }
        },
        { confirmText: 'Désactiver', confirmClass: 'btn-danger', iconColor: 'var(--color-error)' }
    );
};

    // ============================================================
    // 8. INITIALISATION
    // ============================================================
    console.log('%cFiche Élève%c initialisée.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});