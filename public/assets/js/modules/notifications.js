/* ============================================================
   Fichier : public/assets/js/modules/notifications.js
   Rôle    : Gestion interactive des notifications & envois
   Système : École primaire congolaise — Communication
   Version : 2.0 (Corrigé & Optimisé)
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const btnMarquerToutesLues = document.getElementById('btnMarquerToutesLues');
    const btnEnvoyerNotification = document.getElementById('btnEnvoyerNotification');
    const selectDestinataire = document.getElementById('destinataire_type');
    const selectClasse = document.getElementById('classe_id');
    const selectParent = document.getElementById('parent_id');

    // ============================================================
    // 2. MARQUER UNE NOTIFICATION COMME LUE
    // ============================================================
    window.marquerLue = function(notificationId, element) {
        const item = element ? element.closest('.notification-item') : null;
        if (item && item.dataset.lu === '1') return;

        const formData = new FormData();
        formData.append('action', 'marquerLue');
        formData.append('notification_id', notificationId);

        fetch('/gestion_scolaire/notifications', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => {
            if (!res.ok) throw new Error(`Erreur HTTP: ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (data.success && item) {
                item.classList.remove('non-lue');
                item.dataset.lu = '1';
                const badge = item.querySelector('.notification-badge-non-lue');
                if (badge) {
                    badge.style.opacity = '0';
                    badge.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => badge.remove(), 300);
                }
                const content = item.querySelector('.notification-content');
                if (content) content.removeAttribute('onclick');
                
                updateHeaderBadge();
            }
        })
        .catch(err => console.error('Erreur marquage lu:', err));
    };

    // ============================================================
    // 3. TOUT MARQUER COMME LU
    // ============================================================
    if (btnMarquerToutesLues) {
        btnMarquerToutesLues.addEventListener('click', function(e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('action', 'marquerToutesLues');

            this.disabled = true;
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement...';

            fetch('/gestion_scolaire/notifications', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (typeof showToast === 'function') showToast(data.message, 'success');
                    document.querySelectorAll('.notification-item.non-lue').forEach(item => {
                        item.classList.remove('non-lue');
                        item.dataset.lu = '1';
                        const badge = item.querySelector('.notification-badge-non-lue');
                        if (badge) badge.remove();
                        const content = item.querySelector('.notification-content');
                        if (content) content.removeAttribute('onclick');
                    });
                    updateHeaderBadge();
                } else {
                    if (typeof showToast === 'function') showToast(data.message || 'Erreur lors du traitement', 'error');
                }
            })
            .catch(err => {
                console.error('Erreur réseau:', err);
                if (typeof showToast === 'function') showToast('Erreur de connexion au serveur.', 'error');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = originalText;
            });
        });
    }

    // ============================================================
    // 4. SUPPRIMER UNE NOTIFICATION
    // ============================================================
    function attacherSuppression() {
        document.querySelectorAll('.btn-supprimer-notif').forEach(btn => {
            btn.removeEventListener('click', handleSuppression);
            btn.addEventListener('click', handleSuppression);
        });
    }

    function handleSuppression(e) {
        e.stopPropagation();
        e.preventDefault();
        const notificationId = this.dataset.id;
        const item = this.closest('.notification-item');

        if (!notificationId) return;

        const formData = new FormData();
        formData.append('action', 'supprimer');
        formData.append('notification_id', notificationId);

        fetch('/gestion_scolaire/notifications', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (item) {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(30px)';
                    item.style.transition = 'all 0.3s ease';
                    setTimeout(() => {
                        item.remove();
                        const remaining = document.querySelectorAll('.notification-item');
                        if (remaining.length === 0) {
                            const listContainer = document.getElementById('notificationsList');
                            if (listContainer) {
                                listContainer.innerHTML = 
                                    '<div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);"><i class="fa-solid fa-bell-slash"></i><p>Vous n\'avez aucune notification.</p></div>';
                            }
                        }
                    }, 300);
                }
                updateHeaderBadge();
            } else {
                if (typeof showToast === 'function') showToast(data.message || 'Échec de suppression', 'error');
            }
        })
        .catch(err => console.error('Erreur suppression:', err));
    }

    // ============================================================
    // 5. FILTRES DE RECHERCHE ET LISTING
    // ============================================================
    window.getFiltres = function() {
        return {
            type: document.getElementById('filterType')?.value || '',
            lu: document.getElementById('filterLu')?.value || ''
        };
    };

    window.appliquerFiltres = function() {
        if (typeof window.chargerNotifications === 'function') {
            window.chargerNotifications(1);
        }
    };

    window.reinitialiserFiltres = function() {
        const filterType = document.getElementById('filterType');
        const filterLu = document.getElementById('filterLu');
        if (filterType) filterType.value = '';
        if (filterLu) filterLu.value = '';
        window.appliquerFiltres();
    };

    // ============================================================
    // 6. CHARGEMENT & AFFICHAGE DES NOTIFICATIONS (AJAX)
    // ============================================================
    window.chargerNotifications = function(page) {
        const formData = new FormData();
        formData.append('action', 'getListe');
        formData.append('page', page || 1);

        const filtres = window.getFiltres();
        Object.keys(filtres).forEach(key => {
            if (filtres[key] !== '') formData.append(key, filtres[key]);
        });

        fetch('/gestion_scolaire/notifications', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.afficherNotifications(data);
            }
        })
        .catch(err => console.error('Erreur chargement notifications:', err));
    };

    window.afficherNotifications = function(data) {
        const container = document.getElementById('notificationsList');
        if (!container) return;

        if (!data.notifications || data.notifications.length === 0) {
            container.innerHTML = '<div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);"><i class="fa-solid fa-bell-slash"></i><p>Aucune notification trouvée.</p></div>';
            const pagWrapper = document.getElementById('paginationWrapper');
            if (pagWrapper) pagWrapper.style.display = 'none';
            return;
        }

        const typeIcons = {
            'Alerte': 'fa-circle-exclamation',
            'Urgence': 'fa-triangle-exclamation',
            'Rappel': 'fa-clock',
            'Succès': 'fa-circle-check',
            'Information': 'fa-circle-info'
        };
        const typeColors = {
            'Alerte': '#f59e0b',
            'Urgence': '#ef4444',
            'Rappel': '#3b82f6',
            'Succès': '#10b981',
            'Information': '#64748b'
        };

        container.innerHTML = data.notifications.map(n => {
            const icon = typeIcons[n.type_notification] || 'fa-circle-info';
            const color = typeColors[n.type_notification] || '#64748b';
            const lienHtml = n.lien ? 
                `<a href="${window.escapeHtml(n.lien)}" class="btn btn-sm btn-outline-primary" title="Voir"><i class="fa-solid fa-arrow-right"></i></a>` : '';

            return `
            <div class="notification-item ${!n.est_lue ? 'non-lue' : ''}" data-id="${n.id}" data-lu="${n.est_lue}" style="animation: fadeInUp 0.3s ease;">
                <div class="notification-icon" style="background:${color}15;color:${color};">
                    <i class="fa-solid ${icon}"></i>
                </div>
                <div class="notification-content" ${!n.est_lue ? `onclick="marquerLue(${n.id}, this)"` : ''}>
                    <div class="notification-header">
                        <span class="notification-titre">${window.escapeHtml(n.titre)}</span>
                        ${!n.est_lue ? '<span class="notification-badge-non-lue">Nouveau</span>' : ''}
                    </div>
                    <p class="notification-message">${window.escapeHtml(n.message).replace(/\n/g, '<br>')}</p>
                    <div class="notification-meta">
                        <span class="notification-date"><i class="fa-regular fa-clock"></i> ${n.date_format} à ${n.heure_format}</span>
                        ${n.est_lue && n.date_lecture_format ? `<span class="notification-date-lecture"><i class="fa-solid fa-check"></i> Lu le ${n.date_lecture_format}</span>` : ''}
                        <span class="notification-type-badge" style="background:${color}20;color:${color};">${n.type_notification}</span>
                    </div>
                </div>
                <div class="notification-actions">
                    ${lienHtml}
                    <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-notif" data-id="${n.id}" title="Supprimer">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>`;
        }).join('');

        const paginationWrapper = document.getElementById('paginationWrapper');
        if (paginationWrapper) {
            if (data.pages > 1) {
                paginationWrapper.style.display = 'flex';
                let html = '';
                for (let i = 1; i <= data.pages; i++) {
                    html += `<a href="#" onclick="chargerNotifications(${i}); return false;" class="${i === data.page ? 'active' : ''}">${i}</a>`;
                }
                const pagElem = document.getElementById('paginationNotifications');
                const pagInfo = document.getElementById('paginationInfo');
                if (pagElem) pagElem.innerHTML = html;
                if (pagInfo) pagInfo.textContent = `Page ${data.page} / ${data.pages} — ${data.total} notification(s)`;
            } else {
                paginationWrapper.style.display = 'none';
            }
        }

        attacherSuppression();
    };

    // ============================================================
    // 7. GESTION DES DESTINATAIRES & SÉLECTIONS DYNAMIQUES
    // ============================================================
    window.changerDestinataire = function() {
        const typeSelect = document.getElementById('destinataire_type');
        if (!typeSelect) return;

        const type = typeSelect.value;
        const groupeClasse = document.getElementById('groupeClasse');
        const groupeParent = document.getElementById('groupeParent');
        const selectClasseElem = document.getElementById('classe_id');
        const selectParentElem = document.getElementById('parent_id');
        const aideClasse = document.getElementById('aideClasse');
        const resume = document.getElementById('resumeDestinataires');
        const resumeTexte = document.getElementById('resumeDestinatairesTexte');

        if (groupeClasse) groupeClasse.style.display = 'none';
        if (groupeParent) groupeParent.style.display = 'none';
        if (resume) resume.style.display = 'none';

        if (selectClasseElem) selectClasseElem.required = false;
        if (selectParentElem) selectParentElem.required = false;

        if (type === 'tous') {
            if (resume) resume.style.display = 'block';
            if (resumeTexte) {
                resumeTexte.textContent = 'Tous les parents enregistrés recevront cette notification.';
            }
        } 
        else if (type === 'classe') {
            if (groupeClasse) groupeClasse.style.display = 'block';
            if (selectClasseElem) selectClasseElem.required = true;
            if (aideClasse) {
                aideClasse.innerHTML = '<i class="fa-solid fa-info-circle"></i> La notification sera envoyée à tous les parents des élèves de cette classe.';
            }
            if (selectParentElem) {
                selectParentElem.innerHTML = '<option value="">— Sélectionnez d\'abord une classe —</option>';
            }
            if (selectClasseElem && selectClasseElem.value) {
                const classeNom = selectClasseElem.selectedOptions[0]?.text || '';
                if (resume) resume.style.display = 'block';
                if (resumeTexte) resumeTexte.textContent = `Les parents des élèves de ${classeNom} recevront cette notification.`;
            }
        } 
        else if (type === 'parent') {
            if (groupeClasse) groupeClasse.style.display = 'block';
            if (groupeParent) groupeParent.style.display = 'block';

            if (selectClasseElem) selectClasseElem.required = true;
            if (selectParentElem) selectParentElem.required = true;

            if (aideClasse) {
                aideClasse.innerHTML = '<i class="fa-solid fa-info-circle"></i> Choisissez la classe pour filtrer la liste des parents.';
            }
            
            if (selectClasseElem && selectClasseElem.value) {
                window.auChangementClasse();
            }
        }
    };

    window.auChangementClasse = function() {
        const typeSelect = document.getElementById('destinataire_type');
        const selectClasseElem = document.getElementById('classe_id');
        const selectParentElem = document.getElementById('parent_id');
        const resume = document.getElementById('resumeDestinataires');
        const resumeTexte = document.getElementById('resumeDestinatairesTexte');

        if (!typeSelect || !selectClasseElem) return;

        const type = typeSelect.value;
        const classeId = selectClasseElem.value;

        if (type === 'classe') {
            if (classeId) {
                const classeNom = selectClasseElem.selectedOptions[0]?.text || '';
                if (resume) resume.style.display = 'block';
                if (resumeTexte) resumeTexte.textContent = `Les parents des élèves de ${classeNom} recevront cette notification.`;
            } else {
                if (resume) resume.style.display = 'none';
            }
        }

        if (type === 'parent' && selectParentElem) {
            if (!classeId) {
                selectParentElem.innerHTML = '<option value="">— Sélectionnez d\'abord une classe —</option>';
                return;
            }

            selectParentElem.innerHTML = '<option value="">Chargement des parents...</option>';

            const formData = new FormData();
            formData.append('action', 'getParentsByClasse');
            formData.append('classe_id', classeId);

            fetch('/gestion_scolaire/notifications', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => {
                if (!res.ok) throw new Error(`Erreur serveur (${res.status})`);
                return res.json();
            })
            .then(data => {
                selectParentElem.innerHTML = '<option value="">— Sélectionnez un parent —</option>';
                const parentsList = data.parents || data.data || [];
                if (data.success && Array.isArray(parentsList) && parentsList.length > 0) {
                    parentsList.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.id;
                        const parentNom = p.nom_parent || (p.prenom ? `${p.prenom} ${p.nom}` : p.nom);
                        const detailEleve = p.nom_eleve ? ` (Élève: ${p.nom_eleve})` : (p.telephone_principal ? ` — ${p.telephone_principal}` : '');
                        opt.textContent = `${parentNom}${detailEleve}`;
                        selectParentElem.appendChild(opt);
                    });
                } else {
                    selectParentElem.innerHTML = '<option value="">Aucun parent trouvé dans cette classe</option>';
                }
            })
            .catch(err => {
                console.error('Erreur chargement parents:', err);
                selectParentElem.innerHTML = '<option value="">Erreur de chargement des parents</option>';
            });
        }
    };

    window.chargerParentsClasse = window.auChangementClasse;

    // ============================================================
    // 8. ENVOI DE LA NOTIFICATION (FORMULAIRE PRINCIPAL)
    // ============================================================
    // ============================================================
// 8. ENVOI DE LA NOTIFICATION (FORMULAIRE PRINCIPAL) - CORRIGÉ
// ============================================================
if (btnEnvoyerNotification) {
    let isSending = false;

    btnEnvoyerNotification.addEventListener('click', async function(e) {
        e.preventDefault();

        if (isSending) {
            console.log('Envoi déjà en cours, ignoré.');
            return;
        }

        console.log('=== DÉBUT ENVOI NOTIFICATION ===');

        const destinataireType = selectDestinataire?.value || '';
        const titre = document.getElementById('titre')?.value?.trim() || '';
        const message = document.getElementById('message')?.value?.trim() || '';
        const typeNotif = document.getElementById('type_notification')?.value || 'Information';
        const classeId = selectClasse?.value || '';
        const parentId = selectParent?.value || '';
        const checkboxEmail = document.getElementById('envoyer_email');
        const envoyerEmail = (checkboxEmail && checkboxEmail.checked) ? '1' : '0';

        console.log('Données du formulaire :', {
            destinataireType,
            titre,
            message,
            typeNotif,
            classeId,
            parentId,
            envoyerEmail
        });

        // Validations
        if (!destinataireType) { 
            console.error('Validation échouée : destinataire_type manquant');
            if (typeof showToast === 'function') showToast('Veuillez sélectionner un type de destinataire.', 'warning'); 
            return; 
        }
        if (!titre) { 
            console.error('Validation échouée : titre manquant');
            if (typeof showToast === 'function') showToast('Le titre est obligatoire.', 'warning'); 
            return; 
        }
        if (!message) { 
            console.error('Validation échouée : message manquant');
            if (typeof showToast === 'function') showToast('Le message est obligatoire.', 'warning'); 
            return; 
        }

        if (destinataireType === 'classe' && !classeId) {
            console.error('Validation échouée : classe_id manquant');
            if (typeof showToast === 'function') showToast('Veuillez sélectionner une classe.', 'warning'); 
            return; 
        }

        if (destinataireType === 'parent' && !parentId) {
            console.error('Validation échouée : parent_id manquant');
            if (typeof showToast === 'function') showToast('Veuillez sélectionner un parent.', 'warning'); 
            return; 
        }

        // Désactiver le bouton
        isSending = true;
        this.disabled = true;
        const originalButtonContent = this.innerHTML;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Envoi en cours...';

        // Construire FormData manuellement
        const formData = new FormData();
        formData.append('action', 'envoyer');
        formData.append('destinataire_type', destinataireType);
        formData.append('titre', titre);
        formData.append('message', message);
        formData.append('type_notification', typeNotif);
        formData.append('envoyer_email', envoyerEmail);
        
        if (classeId) {
            formData.append('classe_id', classeId);
        }
        if (parentId) {
            formData.append('parent_id', parentId);
        }

        console.log('FormData construit :');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }

        try {
            console.log('Envoi fetch vers /gestion_scolaire/notifications/envoyer...');
            
            const res = await fetch('/gestion_scolaire/notifications/envoyer', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            console.log('Réponse reçue, status:', res.status);

            const textResponse = await res.text();
            console.log('Réponse brute du serveur :', textResponse);
            
            let data;
            
            try {
                data = JSON.parse(textResponse);
                console.log('Données parsées :', data);
            } catch (parseError) {
                console.error('Erreur de parsing JSON :', parseError);
                console.error('Réponse non-JSON du serveur :', textResponse);
                throw new Error('Erreur de réponse du serveur. La réponse n\'est pas au format JSON.');
            }

            if (data.success) {
                console.log('✅ Notification envoyée avec succès !');
                if (typeof showToast === 'function') {
                    showToast(data.message, 'success');
                } else {
                    alert(data.message);
                }
                setTimeout(() => {
                    console.log('Redirection vers la liste des notifications...');
                    window.location.href = '/gestion_scolaire/notifications';
                }, 1200);
            } else {
                console.error('❌ Échec de l\'envoi :', data.message);
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Erreur lors de l\'envoi.', 'error');
                } else {
                    alert(data.message || 'Erreur lors de l\'envoi.');
                }
            }
        } catch (err) {
            console.error('❌ Erreur durant l\'envoi:', err);
            if (typeof showToast === 'function') {
                showToast('Erreur lors du traitement de la requête : ' + err.message, 'error');
            } else {
                alert('Erreur réseau ou serveur : ' + err.message);
            }
        } finally {
            console.log('=== FIN ENVOI NOTIFICATION ===');
            isSending = false;
            this.disabled = false;
            this.innerHTML = originalButtonContent;
        }
    });
}

    // ============================================================
    // 9. METTRE À JOUR LE BADGE DU HEADER
    // ============================================================
    function updateHeaderBadge() {
        const formData = new FormData();
        formData.append('action', 'getCount');

        fetch('/gestion_scolaire/notifications', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const badge = document.querySelector('.navbar-action-btn[title="Notifications"] .badge-count');
                if (badge) {
                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        })
        .catch(err => console.error('Erreur badge header:', err));
    }

    // ============================================================
    // 10. FONCTIONS UTILITAIRES
    // ============================================================
    window.escapeHtml = function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    // ============================================================
    // 11. INITIALISATION GENERALE
    // ============================================================
    attacherSuppression();
    updateHeaderBadge();

    // Initialisation synchrone si un destinataire est pré-sélectionné
    if (selectDestinataire && selectDestinataire.value) {
        window.changerDestinataire();
    }

    console.log('%c[Notifications Module]%c Initialisé et prêt.', 
        'color: #2563eb; font-weight: bold;', 'color: #475569;');
});
