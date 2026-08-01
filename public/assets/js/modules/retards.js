/* ============================================================
   Fichier : public/assets/js/modules/retards.js
   Rôle    : Gestion interactive des retards
   Système : École primaire congolaise
   Version : 1.0
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const modalRetard            = document.getElementById('modalRetard');
    const modalJustifierRetard   = document.getElementById('modalJustifierRetard');
    const btnSaveRetard          = document.getElementById('btnSaveRetard');
    const btnSaveJustification   = document.getElementById('btnSaveJustificationRetard');
    const tableRetards           = document.getElementById('tableRetards');

    // ============================================================
    // 2. OUVERTURE MODALE RETARD
    // ============================================================
    window.ouvrirModalRetard = function(eleveId, eleveNom, heureArrivee, motif) {
        if (!modalRetard) return;

        document.getElementById('retard_eleve_id').value = eleveId;
        document.getElementById('retard_eleve_nom').value = eleveNom;
        document.getElementById('modalRetardTitre').textContent = heureArrivee ? 'Modifier le retard' : 'Enregistrer un retard';

        if (heureArrivee) {
            document.getElementById('retard_heure_arrivee').value = heureArrivee.substring(0, 5);
        } else {
            document.getElementById('retard_heure_arrivee').value = '';
        }

        document.getElementById('retard_motif').value = motif || '';
        document.getElementById('retard_motif_select').value = '';
        document.getElementById('retard_est_justifie').checked = false;
        document.getElementById('retardAlerte').style.display = 'none';

        modalRetard.classList.add('show');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('retard_heure_arrivee').focus(), 300);
    };

    window.closeModalRetard = function() {
        if (!modalRetard) return;
        modalRetard.classList.remove('show');
        document.body.style.overflow = '';
        document.getElementById('formRetard').reset();
    };

    // ============================================================
    // 3. OUVERTURE MODALE JUSTIFICATION
    // ============================================================
    window.ouvrirModalJustifier = function(eleveId) {
        if (!modalJustifierRetard) return;

        document.getElementById('justifier_retard_eleve_id').value = eleveId;
        modalJustifierRetard.classList.add('show');
        document.body.style.overflow = 'hidden';
    };

    window.closeModalJustifierRetard = function() {
        if (!modalJustifierRetard) return;
        modalJustifierRetard.classList.remove('show');
        document.body.style.overflow = '';
        document.getElementById('formJustifierRetard').reset();
    };

    // ============================================================
    // 4. FERMETURE AU CLIC EXTÉRIEUR
    // ============================================================
    [modalRetard, modalJustifierRetard].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    if (modal === modalRetard) closeModalRetard();
                    else closeModalJustifierRetard();
                }
            });
        }
    });

    // ============================================================
    // 5. REMPLIR LE MOTIF DEPUIS LE SELECT
    // ============================================================
    window.remplirMotifRetard = function(valeur) {
        if (valeur && valeur !== 'Autre') {
            const motifInput = document.getElementById('retard_motif');
            if (motifInput) motifInput.value = valeur;
        }
    };

    // ============================================================
    // 6. SAUVEGARDER LE RETARD
    // ============================================================
    if (btnSaveRetard) {
        btnSaveRetard.addEventListener('click', async function() {
            const eleveId = document.getElementById('retard_eleve_id').value;
            const heureArrivee = document.getElementById('retard_heure_arrivee').value;

            if (!eleveId || !heureArrivee) {
                showToast('Veuillez remplir tous les champs obligatoires.', 'warning');
                return;
            }

            const formData = new FormData(document.getElementById('formRetard'));
            formData.append('action', 'enregistrerRetard');

            btnSaveRetard.disabled = true;
            btnSaveRetard.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';

            try {
                const res = await fetch('/gestion_scolaire/retards', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data.success) {
                    let msg = data.message;
                    if (data.alerte) msg += ' ' + data.alerte;
                    showToast(msg, 'success');
                    closeModalRetard();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (e) {
                showToast('Erreur de connexion.', 'error');
            } finally {
                btnSaveRetard.disabled = false;
                btnSaveRetard.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer';
            }
        });
    }

    // ============================================================
    // 7. SAUVEGARDER LA JUSTIFICATION
    // ============================================================
    if (btnSaveJustification) {
        btnSaveJustification.addEventListener('click', async function() {
            const eleveId = document.getElementById('justifier_retard_eleve_id').value;
            const motif = document.querySelector('#formJustifierRetard select[name="motif"]').value;

            if (!motif) {
                showToast('Veuillez sélectionner un motif.', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'justifierRetard');
            formData.append('eleve_id', eleveId);
            formData.append('date_retard', dateRetard);
            formData.append('motif', motif);

            btnSaveJustification.disabled = true;
            btnSaveJustification.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sauvegarde...';

            try {
                const res = await fetch('/gestion_scolaire/retards', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    closeModalJustifierRetard();
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (e) {
                showToast('Erreur de connexion.', 'error');
            } finally {
                btnSaveJustification.disabled = false;
                btnSaveJustification.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer';
            }
        });
    }

    // ============================================================
    // 8. SUPPRIMER UN RETARD
    // ============================================================
    window.supprimerRetard = function(eleveId) {
        showConfirmDialog(
            'Voulez-vous supprimer ce retard ?',
            async function(confirmed) {
                if (confirmed) {
                    const formData = new FormData();
                    formData.append('action', 'supprimerRetard');
                    formData.append('eleve_id', eleveId);
                    formData.append('date_retard', dateRetard);

                    try {
                        const res = await fetch('/gestion_scolaire/retards', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => location.reload(), 600);
                        } else {
                            showToast(data.message, 'error');
                        }
                    } catch (e) {
                        showToast('Erreur de connexion.', 'error');
                    }
                }
            },
            {
                title: 'Supprimer le retard',
                confirmText: 'Supprimer',
                confirmClass: 'btn-danger',
                icon: 'fa-trash',
                iconColor: '#ef4444'
            }
        );
    };

    // ============================================================
    // 9. RACCOURCI CLAVIER
    // ============================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (modalRetard && modalRetard.classList.contains('show')) closeModalRetard();
            if (modalJustifierRetard && modalJustifierRetard.classList.contains('show')) closeModalJustifierRetard();
        }
    });

    // ============================================================
    // 10. INITIALISATION
    // ============================================================
    console.log('%cRetards%c initialisé avec succès.',
        'color: #f59e0b; font-weight: 700;', 'color: #64748b;');
});