/* ============================================================
   Fichier : public/assets/js/templates/header.js
   Rôle    : Gestion de la barre de navigation supérieure
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const navbarTop        = document.getElementById('navbar-top');
    const userDropdown     = document.getElementById('userDropdown');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    const navbarSearch     = document.getElementById('navbarSearch');
    const navbarSearchInput = document.getElementById('navbarSearchInput');
    const notificationBtns = document.querySelectorAll('.navbar-action-btn[title]');
    const currentDateTime  = document.getElementById('currentDateTime');

    // Si pas de navbar, sortir
    if (!navbarTop) return;

    // ============================================================
    // 2. EFFET SCROLL SUR LA NAVBAR
    // ============================================================
    function handleNavbarScroll() {
        if (window.scrollY > 10) {
            navbarTop.classList.add('scrolled');
        } else {
            navbarTop.classList.remove('scrolled');
        }
    }

    window.addEventListener('scroll', handleNavbarScroll, { passive: true });
    handleNavbarScroll(); // Vérifier l'état initial

    // ============================================================
    // 3. DROPDOWN UTILISATEUR
    // ============================================================
    if (userDropdown && userDropdownMenu) {
        const trigger = userDropdown.querySelector('.user-dropdown-trigger');

        if (trigger) {
            // Ouverture/fermeture au clic
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                toggleUserDropdown();
            });

            // Accessibilité clavier
            trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleUserDropdown();
                }
                if (e.key === 'Escape') {
                    closeUserDropdown();
                    trigger.focus();
                }
            });
        }

        // Fermer au clic en dehors
        document.addEventListener('click', (e) => {
            if (!userDropdown.contains(e.target)) {
                closeUserDropdown();
            }
        });

        // Fermer avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && userDropdownMenu.classList.contains('show')) {
                closeUserDropdown();
                if (trigger) trigger.focus();
            }
        });

        // Navigation dans le dropdown au clavier
        userDropdownMenu.addEventListener('keydown', (e) => {
            const links = userDropdownMenu.querySelectorAll('a');
            const active = document.activeElement;
            const currentIndex = Array.from(links).indexOf(active);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const next = currentIndex < links.length - 1 ? links[currentIndex + 1] : links[0];
                next.focus();
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prev = currentIndex > 0 ? links[currentIndex - 1] : links[links.length - 1];
                prev.focus();
            }
            if (e.key === 'Escape') {
                closeUserDropdown();
                if (trigger) trigger.focus();
            }
        });
    }

    function toggleUserDropdown() {
        const trigger = userDropdown.querySelector('.user-dropdown-trigger');
        const isOpen = userDropdownMenu.classList.contains('show');

        // Fermer tous les autres dropdowns
        closeAllDropdowns();

        if (!isOpen) {
            userDropdownMenu.classList.add('show');
            if (trigger) trigger.classList.add('open');
            // Focus sur le premier lien
            const firstLink = userDropdownMenu.querySelector('a');
            if (firstLink) setTimeout(() => firstLink.focus(), 100);
        } else {
            closeUserDropdown();
        }
    }

    function closeUserDropdown() {
        const trigger = userDropdown.querySelector('.user-dropdown-trigger');
        userDropdownMenu.classList.remove('show');
        if (trigger) trigger.classList.remove('open');
    }

    function closeAllDropdowns() {
        if (userDropdownMenu) userDropdownMenu.classList.remove('show');
        const allTriggers = document.querySelectorAll('.user-dropdown-trigger');
        allTriggers.forEach(t => t.classList.remove('open'));
    }

    // ============================================================
    // 4. RECHERCHE GLOBALE
    // ============================================================
    if (navbarSearchInput) {
        // Focus avec Ctrl+K
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                navbarSearchInput.focus();
            }
        });

        // Soumission de la recherche
        navbarSearchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const query = navbarSearchInput.value.trim();
                if (query.length > 0) {
                    // Rediriger vers la recherche globale
                    window.location.href = `/gestion_scolaire/recherche?q=${encodeURIComponent(query)}`;
                }
            }
            if (e.key === 'Escape') {
                navbarSearchInput.value = '';
                navbarSearchInput.blur();
            }
        });

        // Placeholder dynamique
        const placeholders = [
            'Rechercher un élève...',
            'Rechercher un enseignant...',
            'Rechercher une classe...',
            'Rechercher...'
        ];
        let placeholderIndex = 0;

        setInterval(() => {
            if (document.activeElement !== navbarSearchInput && navbarSearchInput.value === '') {
                placeholderIndex = (placeholderIndex + 1) % placeholders.length;
                navbarSearchInput.setAttribute('placeholder', placeholders[placeholderIndex]);
            }
        }, 3000);
    }

    // ============================================================
    // 5. BOUTONS DE NOTIFICATION
    // ============================================================
    notificationBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const title = this.getAttribute('title');
            const url = this.dataset.url;

            if (url) {
                window.location.href = url;
            } else if (title === 'Notifications') {
                window.location.href = '/gestion_scolaire/notifications';
            } else if (title === 'Messages') {
                window.location.href = '/gestion_scolaire/messages';
            }
        });

        // Effet ripple au clic
        btn.addEventListener('mousedown', function(e) {
            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(44, 62, 144, 0.2);
                width: 20px;
                height: 20px;
                left: ${e.offsetX - 10}px;
                top: ${e.offsetY - 10}px;
                animation: rippleEffect 0.6s ease-out;
                pointer-events: none;
            `;
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });

    // ============================================================
    // 6. DATE ET HEURE EN TEMPS RÉEL
    // ============================================================
    if (currentDateTime) {
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const dateStr = now.toLocaleDateString('fr-FR', options);
            const timeStr = now.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });

            currentDateTime.innerHTML = `
                <i class="fa-regular fa-calendar"></i>
                <span>${dateStr}</span>
                <span style="font-weight:600;color:var(--color-primary);">${timeStr}</span>
            `;
        }

        updateDateTime();
        setInterval(updateDateTime, 30000); // Mise à jour toutes les 30 secondes
    }

    // ============================================================
    // 7. GESTION DES NOTIFICATIONS EN TEMPS RÉEL (POLLING)
    // ============================================================
    let notificationPolling = null;

    function startNotificationPolling() {
        // Vérifier les nouvelles notifications toutes les 60 secondes
        notificationPolling = setInterval(checkNewNotifications, 60000);
    }

    function stopNotificationPolling() {
        if (notificationPolling) {
            clearInterval(notificationPolling);
            notificationPolling = null;
        }
    }

    async function checkNewNotifications() {
        try {
            const response = await fetch('/gestion_scolaire/api/notifications/count', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) return;

            const data = await response.json();
            updateNotificationBadges(data);
        } catch (error) {
            // Silencieux : ne pas perturber l'utilisateur
        }
    }

    function updateNotificationBadges(data) {
        // Mettre à jour le badge de notifications
        const notifBadge = document.querySelector('.navbar-action-btn[title="Notifications"] .badge-count');
        if (notifBadge) {
            if (data.notifications > 0) {
                notifBadge.textContent = data.notifications > 99 ? '99+' : data.notifications;
                notifBadge.style.display = 'flex';
            } else {
                notifBadge.style.display = 'none';
            }
        }

        // Mettre à jour le badge de messages
        const msgBadge = document.querySelector('.navbar-action-btn[title="Messages"] .badge-count');
        if (msgBadge) {
            if (data.messages > 0) {
                msgBadge.textContent = data.messages > 99 ? '99+' : data.messages;
                msgBadge.style.display = 'flex';
            } else {
                msgBadge.style.display = 'none';
            }
        }
    }

    // Démarrer le polling
    startNotificationPolling();

    // Arrêter quand la page est masquée
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopNotificationPolling();
        } else {
            startNotificationPolling();
            checkNewNotifications(); // Vérifier immédiatement
        }
    });

    // ============================================================
    // 8. RACCOURCIS CLAVIER GLOBAUX
    // ============================================================
    document.addEventListener('keydown', (e) => {
        // Ctrl+K : focus recherche
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (navbarSearchInput) navbarSearchInput.focus();
        }

        // Ctrl+Shift+N : notifications
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'N') {
            e.preventDefault();
            window.location.href = '/gestion_scolaire/notifications';
        }

        // Ctrl+Shift+M : messages
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'M') {
            e.preventDefault();
            window.location.href = '/gestion_scolaire/messages';
        }
    });

    // ============================================================
    // 9. INITIALISATION FINALE
    // ============================================================
    console.log('%cHeader%c initialisé avec succès.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});