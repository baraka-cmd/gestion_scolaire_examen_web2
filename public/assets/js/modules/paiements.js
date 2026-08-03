/* ============================================================
   Fichier : public/assets/js/modules/paiements.js
   Rôle    : Gestion interactive des paiements
   Système : École primaire congolaise
   Version : 1.0
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. RECHERCHE ÉLÈVE (enregistrer.php)
    // ============================================================
    const rechercheEleve = document.getElementById('rechercheEleve');
    const resultatsRecherche = document.getElementById('resultatsRecherche');
    let searchTimeout;

    if (rechercheEleve && resultatsRecherche) {
        rechercheEleve.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                resultatsRecherche.innerHTML = '';
                resultatsRecherche.classList.remove('show');
                return;
            }

            searchTimeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('action', 'rechercherEleve');
                formData.append('recherche', query);

                fetch('/gestion_scolaire/paiements', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.eleves && data.eleves.length > 0) {
                        resultatsRecherche.innerHTML = data.eleves.map(e => `
                            <div class="resultat-eleve-item" onclick="selectionnerEleve(${e.id}, '${escapeHtml(e.prenom + ' ' + e.nom)}', '${escapeHtml(e.matricule)}', '${escapeHtml(e.classe_nom)}', '${e.sexe}')">
                                <div class="resultat-eleve-avatar ${e.sexe === 'M' ? 'male' : 'female'}">
                                    ${(e.prenom.charAt(0) + e.nom.charAt(0)).toUpperCase()}
                                </div>
                                <div class="resultat-eleve-info">
                                    <strong>${escapeHtml(e.prenom + ' ' + e.nom)}</strong>
                                    <span>${escapeHtml(e.matricule)} — ${escapeHtml(e.classe_nom)}</span>
                                </div>
                            </div>
                        `).join('');
                        resultatsRecherche.classList.add('show');
                    } else {
                        resultatsRecherche.innerHTML = '<p style="padding:12px;color:var(--color-gray-500);text-align:center;">Aucun élève trouvé.</p>';
                        resultatsRecherche.classList.add('show');
                    }
                })
                .catch(() => {
                    resultatsRecherche.innerHTML = '<p style="padding:12px;color:var(--color-error);text-align:center;">Erreur de recherche.</p>';
                    resultatsRecherche.classList.add('show');
                });
            }, 300);
        });

        // Fermer les résultats au clic extérieur
        document.addEventListener('click', function(e) {
            if (resultatsRecherche && rechercheEleve && 
                !resultatsRecherche.contains(e.target) && 
                e.target !== rechercheEleve) {
                resultatsRecherche.classList.remove('show');
            }
        });
    }

    // ============================================================
    // 2. SÉLECTIONNER UN ÉLÈVE
    // ============================================================
    window.selectionnerEleve = function(id, nom, matricule, classe, sexe) {
        const eleveIdInput = document.getElementById('eleve_id');
        const rechercheInput = document.getElementById('rechercheEleve');
        const sectionFrais = document.getElementById('sectionFrais');
        const historiqueEleve = document.getElementById('historiqueEleve');
        const resultatsContainer = document.getElementById('resultatsRecherche');

        if (eleveIdInput) eleveIdInput.value = id;
        if (rechercheInput) rechercheInput.value = nom + ' (' + matricule + ')';
        if (resultatsContainer) resultatsContainer.classList.remove('show');
        if (sectionFrais) sectionFrais.style.display = 'block';
        if (historiqueEleve) historiqueEleve.style.display = 'block';

        // Remplacer la zone de recherche par la carte élève trouvé
        const container = document.querySelector('.recherche-eleve-container');
        if (container) {
            container.innerHTML = `
                <div class="eleve-trouve-card">
                    <div class="eleve-trouve-avatar ${sexe === 'M' ? 'male' : 'female'}">
                        ${nom.charAt(0).toUpperCase() + (nom.split(' ')[1] ? nom.split(' ')[1].charAt(0).toUpperCase() : '')}
                    </div>
                    <div class="eleve-trouve-info">
                        <strong>${escapeHtml(nom)}</strong>
                        <span>${escapeHtml(matricule)} — ${escapeHtml(classe)}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="changerEleve()">
                        <i class="fa-solid fa-rotate"></i> Changer
                    </button>
                </div>
            `;
        }

        // Charger l'historique
        chargerHistoriqueEleve(id, nom);

        // Réinitialiser le formulaire
        const fraisSelect = document.getElementById('frais_scolaire_id');
        const montantAttendu = document.getElementById('montantAttendu');
        const sectionMontant = document.getElementById('sectionMontant');
        const btnEnregistrer = document.getElementById('btnEnregistrerPaiement');

        if (fraisSelect) fraisSelect.value = '';
        if (montantAttendu) montantAttendu.style.display = 'none';
        if (sectionMontant) sectionMontant.style.display = 'none';
        if (btnEnregistrer) btnEnregistrer.style.display = 'none';
    };

    // ============================================================
    // 3. CHANGER D'ÉLÈVE
    // ============================================================
    window.changerEleve = function() {
        const container = document.querySelector('.recherche-eleve-container');
        if (container) {
            container.innerHTML = `
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="rechercheEleve" class="form-control" 
                           placeholder="Rechercher par nom, prénom ou matricule..."
                           autocomplete="off">
                </div>
                <div id="resultatsRecherche" class="resultats-recherche"></div>
                <small class="form-help">
                    <i class="fa-solid fa-info-circle"></i> Tapez au moins 2 caractères pour lancer la recherche
                </small>
            `;
        }

        document.getElementById('eleve_id').value = '';
        document.getElementById('sectionFrais').style.display = 'none';
        document.getElementById('sectionMontant').style.display = 'none';
        document.getElementById('historiqueEleve').style.display = 'none';
        document.getElementById('btnEnregistrerPaiement').style.display = 'none';

        // Réattacher l'événement de recherche
        const newRechercheInput = document.getElementById('rechercheEleve');
        const newResultats = document.getElementById('resultatsRecherche');
        if (newRechercheInput && newResultats) {
            newRechercheInput.addEventListener('keyup', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                if (query.length < 2) {
                    newResultats.innerHTML = '';
                    newResultats.classList.remove('show');
                    return;
                }
                searchTimeout = setTimeout(() => {
                    const formData = new FormData();
                    formData.append('action', 'rechercherEleve');
                    formData.append('recherche', query);
                    fetch('/gestion_scolaire/paiements', {
                        method: 'POST', body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.eleves && data.eleves.length > 0) {
                            newResultats.innerHTML = data.eleves.map(e => `
                                <div class="resultat-eleve-item" onclick="selectionnerEleve(${e.id}, '${escapeHtml(e.prenom + ' ' + e.nom)}', '${escapeHtml(e.matricule)}', '${escapeHtml(e.classe_nom)}', '${e.sexe}')">
                                    <div class="resultat-eleve-avatar ${e.sexe === 'M' ? 'male' : 'female'}">${(e.prenom.charAt(0) + e.nom.charAt(0)).toUpperCase()}</div>
                                    <div class="resultat-eleve-info"><strong>${escapeHtml(e.prenom + ' ' + e.nom)}</strong><span>${escapeHtml(e.matricule)} — ${escapeHtml(e.classe_nom)}</span></div>
                                </div>
                            `).join('');
                            newResultats.classList.add('show');
                        }
                    });
                }, 300);
            });
        }
    };

    // ============================================================
    // 4. METTRE À JOUR LE MONTANT
    // ============================================================
    const fraisSelect = document.getElementById('frais_scolaire_id');
    if (fraisSelect) {
        fraisSelect.addEventListener('change', function() {
            const option = this.selectedOptions[0];
            const montantAttendu = document.getElementById('montantAttendu');
            const montantAttenduValeur = document.getElementById('montantAttenduValeur');
            const sectionMontant = document.getElementById('sectionMontant');
            const btnEnregistrer = document.getElementById('btnEnregistrerPaiement');
            const montantPayeInput = document.getElementById('montant_paye');

            if (option && option.dataset.montant) {
                const montant = parseFloat(option.dataset.montant);
                if (montantAttenduValeur) {
                    montantAttenduValeur.textContent = '$ ' + montant.toLocaleString('fr-FR', { minimumFractionDigits: 2 });
                }
                if (montantAttendu) montantAttendu.style.display = 'block';
                if (sectionMontant) sectionMontant.style.display = 'block';
                if (btnEnregistrer) btnEnregistrer.style.display = 'inline-flex';
                if (montantPayeInput) {
                    montantPayeInput.value = montant;
                    montantPayeInput.setAttribute('max', montant);
                }
            }
        });
    }

    // ============================================================
    // 5. CHARGER HISTORIQUE ÉLÈVE
    // ============================================================
    function chargerHistoriqueEleve(eleveId, nom) {
        const container = document.getElementById('historiqueEleveContent');
        if (!container) return;

        container.innerHTML = '<p style="text-align:center;padding:12px;"><i class="fa-solid fa-spinner fa-spin"></i> Chargement...</p>';

        const formData = new FormData();
        formData.append('action', 'getListePaiements');
        formData.append('recherche', nom);
        formData.append('page', 1);

        fetch('/gestion_scolaire/paiements', {
            method: 'POST', body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.paiements && data.paiements.length > 0) {
                const paiementsFiltres = data.paiements.filter(p => p.eleve_id == eleveId);
                if (paiementsFiltres.length > 0) {
                    container.innerHTML = paiementsFiltres.map(p => {
                        const statutClass = p.statut === 'Payé' ? 'badge-success' : (p.statut === 'Partiel' ? 'badge-warning' : 'badge-danger');
                        return `
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;">
                                <div>
                                    <span style="font-size:0.82rem;font-weight:500;">${escapeHtml(p.frais_libelle)}</span>
                                    <br><small class="text-muted">${p.date_format} — ${escapeHtml(p.mode_paiement)}</small>
                                </div>
                                <div style="text-align:right;">
                                    <span class="badge ${statutClass}">${p.statut}</span>
                                    <strong style="margin-left:8px;">$ ${parseFloat(p.montant_paye).toLocaleString('fr-FR')}</strong>
                                    ${parseFloat(p.montant_total) - parseFloat(p.montant_paye) > 0 ? 
                                        `<br><small class="text-danger">Reste : $ ${(parseFloat(p.montant_total) - parseFloat(p.montant_paye)).toLocaleString('fr-FR')}</small>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = '<p style="font-size:0.82rem;color:var(--color-gray-500);text-align:center;padding:12px;">Aucun paiement trouvé pour cet élève.</p>';
                }
            } else {
                container.innerHTML = '<p style="font-size:0.82rem;color:var(--color-gray-500);text-align:center;padding:12px;">Aucun paiement trouvé.</p>';
            }
        })
        .catch(() => {
            container.innerHTML = '<p style="font-size:0.82rem;color:var(--color-error);text-align:center;padding:12px;">Erreur de chargement.</p>';
        });
    }

    // ============================================================
    // 6. ENREGISTRER LE PAIEMENT
    // ============================================================
    // Dans public/assets/js/modules/paiements.js

const btnEnregistrerPaiement = document.getElementById('btnEnregistrerPaiement');
if (btnEnregistrerPaiement) {
    let isSubmitting = false;

    btnEnregistrerPaiement.addEventListener('click', async function(e) {
        e.preventDefault();

        // Verrouillage contre les doubles clics rapides
        if (isSubmitting) return;

        const eleveId = document.getElementById('eleve_id')?.value;
        const fraisId = document.getElementById('frais_scolaire_id')?.value;
        const montantPaye = document.getElementById('montant_paye')?.value;

        if (!eleveId) { showToast('Veuillez sélectionner un élève.', 'warning'); return; }
        if (!fraisId) { showToast('Veuillez sélectionner un type de frais.', 'warning'); return; }
        if (!montantPaye || parseFloat(montantPaye) <= 0) { showToast('Veuillez saisir un montant valide.', 'warning'); return; }

        isSubmitting = true;
        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enregistrement...';

        const formData = new FormData(document.getElementById('formPaiement'));
        formData.append('action', 'enregistrerPaiement');

        try {
            const res = await fetch('/gestion_scolaire/paiements', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            if (data.success) {
                document.getElementById('modalRecuContent').innerHTML = `
                    <div style="text-align:center;padding:20px;">
                        <i class="fa-solid fa-circle-check" style="font-size:3rem;color:#10b981;"></i>
                        <h5 style="margin-top:16px;color:var(--color-dark);">Paiement enregistré avec succès !</h5>
                        <p style="color:var(--color-gray-600);">Reçu N° <strong>${data.numero_recu}</strong></p>
                        <p>Statut : <strong style="color:${data.statut === 'Payé' ? '#10b981' : '#f59e0b'};">${data.statut}</strong></p>
                        <p style="font-size:0.8rem;color:var(--color-gray-400);">Montant : <strong>$ ${parseFloat(montantPaye).toLocaleString('fr-FR')}</strong></p>
                    </div>
                `;
                document.getElementById('btnVoirRecu').href = '/gestion_scolaire/paiements/recu?paiement_id=' + data.paiement_id;
                document.getElementById('modalRecu').classList.add('show');
                document.body.style.overflow = 'hidden';
            } else {
                showToast(data.message, 'error');
            }
        } catch (e) {
            showToast('Erreur de connexion.', 'error');
        } finally {
            isSubmitting = false;
            this.disabled = false;
            this.innerHTML = '<i class="fa-solid fa-save"></i> Enregistrer le paiement';
        }
    });
}


    // ============================================================
    // 7. MODALE REÇU
    // ============================================================
    const modalRecu = document.getElementById('modalRecu');
    if (modalRecu) {
        window.closeModalRecu = function() {
            modalRecu.classList.remove('show');
            document.body.style.overflow = '';
        };

        modalRecu.addEventListener('click', function(e) {
            if (e.target === modalRecu) closeModalRecu();
        });
    }

    // ============================================================
    // 8. FILTRES LISTE PAIEMENTS
    // ============================================================
    window.appliquerFiltres = function() {
        if (typeof chargerPaiements === 'function') chargerPaiements(1);
        if (typeof chargerImpayes === 'function') chargerImpayes();
    };

    window.reinitialiserFiltres = function() {
        ['filterClasse', 'filterStatut', 'filterDateDebut', 'filterDateFin', 'filterRecherche'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        appliquerFiltres();
    };

    // ============================================================
    // 9. FONCTIONS GLOBALES
    // ============================================================
    window.escapeHtml = function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    // ============================================================
    // 10. INITIALISATION
    // ============================================================
    console.log('%cPaiements%c initialisé avec succès.',
        'color: #2c3e90; font-weight: 700;', 'color: #64748b;');
});