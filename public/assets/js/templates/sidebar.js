/* ============================================================
   Fichier : public/assets/js/templates/sidebar.js
   Rôle    : Gestion de la barre latérale de navigation
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const sidebar          = document.getElementById('sidebar');
    const toggleBtn        = document.getElementById('btn-toggle-sidebar');
    const overlay          = document.getElementById('sidebar-overlay');
    const mainWrapper      = document.getElementById('main-wrapper');
    const menuItems        = document.querySelectorAll('.menu-item.has-submenu');
    const menuLinks        = document.querySelectorAll('.menu-link');
    const sidebarMenu      = document.querySelector('.sidebar-menu');

    // Si pas de sidebar, sortir
    if (!sidebar) return;

    // ============================================================
    // 2. OUVERTURE/FERMETURE SIDEBAR (MOBILE)
    // ============================================================
    function openSidebar() {
        sidebar.classList.add('show');
        if (overlay) overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
        document.body.style.touchAction = 'none';

        // Focus pour l'accessibilité
        const firstLink = sidebar.querySelector('.menu-link');
        if (firstLink) setTimeout(() => firstLink.focus(), 300);
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
        document.body.style.touchAction = '';

        // Rendre le focus au bouton toggle
        if (toggleBtn) toggleBtn.focus();
    }

    function toggleSidebar() {
        if (sidebar.classList.contains('show')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    // Événement sur le bouton toggle
    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Fermer avec l'overlay
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Fermer avec la touche Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // Fermer au clic en dehors du sidebar (mobile)
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 992 &&
            sidebar.classList.contains('show') &&
            !sidebar.contains(e.target) &&
            e.target !== toggleBtn &&
            !toggleBtn?.contains(e.target)) {
            closeSidebar();
        }
    });

    // Fermer le sidebar si on redimensionne au-dessus du breakpoint
    window.addEventListener('resize', () => {
        if (window.innerWidth > 992 && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });

    // ============================================================
// 3. GESTION DES SOUS-MENUS
// ============================================================
menuItems.forEach(item => {
    const link = item.querySelector('.menu-link');
    const submenu = item.querySelector('.submenu');

    if (!link || !submenu) return;

    link.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        // Vérifier si ce sous-menu est déjà ouvert
        const isOpen = item.classList.contains('open');

        // FERMER TOUS les sous-menus
        document.querySelectorAll('.menu-item.has-submenu.open').forEach(openItem => {
            const openSubmenu = openItem.querySelector('.submenu');
            if (openSubmenu) collapseSubmenu(openSubmenu);
            openItem.classList.remove('open');
        });

        // Si ce menu n'était pas ouvert, l'ouvrir
        if (!isOpen) {
            item.classList.add('open');
            expandSubmenu(submenu);
        }
    });
});

function expandSubmenu(submenu) {
    const links = submenu.querySelectorAll('.menu-link');
    const totalHeight = Array.from(links).reduce((acc, link) => acc + link.offsetHeight, 0);
    submenu.style.maxHeight = totalHeight + 'px';
}

function collapseSubmenu(submenu) {
    submenu.style.maxHeight = '0px';
}

    // ============================================================
    // 4. ACTIVER LE LIEN COURANT
    // ============================================================
    function activateCurrentLink() {
        const currentPath = window.location.pathname;
        let currentLink = null;

        menuLinks.forEach(link => {
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href) && href !== '#') {
                currentLink = link;
            }
        });

        if (currentLink) {
            // Ajouter la classe active
            currentLink.classList.add('active');

            // Ouvrir le parent si dans un sous-menu
            const parentItem = currentLink.closest('.menu-item.has-submenu');
            if (parentItem) {
                parentItem.classList.add('open');
                const submenu = parentItem.querySelector('.submenu');
                if (submenu) {
                    expandSubmenu(submenu);
                }
            }

            // Scroller pour rendre visible
            setTimeout(() => {
                currentLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 200);
        }
    }

    activateCurrentLink();

    // ============================================================
    // 5. SAUVEGARDE DE L'ÉTAT DES SOUS-MENUS (localStorage)
    // ============================================================
    function saveMenuState() {
        const openMenus = [];
        document.querySelectorAll('.menu-item.has-submenu.open').forEach(item => {
            const link = item.querySelector('.menu-link');
            if (link) {
                openMenus.push(link.textContent.trim());
            }
        });
        localStorage.setItem('sidebar_open_menus', JSON.stringify(openMenus));
    }

    function restoreMenuState() {
        const saved = localStorage.getItem('sidebar_open_menus');
        if (!saved) return;

        try {
            const openMenus = JSON.parse(saved);
            openMenus.forEach(menuText => {
                document.querySelectorAll('.menu-item.has-submenu').forEach(item => {
                    const link = item.querySelector('.menu-link');
                    if (link && link.textContent.trim() === menuText) {
                        item.classList.add('open');
                        const submenu = item.querySelector('.submenu');
                        if (submenu) expandSubmenu(submenu);
                    }
                });
            });
        } catch (e) {
            // Ignorer les erreurs de parsing
        }
    }

    // Sauvegarder avant de quitter la page
    window.addEventListener('beforeunload', saveMenuState);

    // Restaurer au chargement
    restoreMenuState();

    // ============================================================
    // 6. COMPORTEMENT AU SURVOL (DESKTOP UNIQUEMENT)
    // ============================================================
    if (window.innerWidth > 992) {
        menuItems.forEach(item => {
            const link = item.querySelector('.menu-link');

            item.addEventListener('mouseenter', () => {
                if (!item.classList.contains('open')) {
                    item.classList.add('hover-preview');
                }
            });

            item.addEventListener('mouseleave', () => {
                item.classList.remove('hover-preview');
            });
        });
    }

    // ============================================================
    // 7. RACCOURCIS CLAVIER
    // ============================================================
    document.addEventListener('keydown', (e) => {
        // Ctrl+B : toggle sidebar
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            toggleSidebar();
        }
    });

    // ============================================================
    // 8. GESTION DU SCROLL
    // ============================================================
    if (sidebarMenu) {
        // Empêcher le scroll de la page quand on scroll dans le sidebar
        sidebarMenu.addEventListener('wheel', (e) => {
            const { scrollTop, scrollHeight, clientHeight } = sidebarMenu;
            const isAtTop = scrollTop === 0;
            const isAtBottom = scrollTop + clientHeight >= scrollHeight - 1;

            if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
                e.preventDefault();
            }
        }, { passive: false });
    }

    // ============================================================
    // 9. TOOLTIP SUR LE SIDEBAR COLLAPSÉ (optionnel)
    // ============================================================
    function initSidebarTooltips() {
        if (window.innerWidth <= 992) return;

        menuLinks.forEach(link => {
            const text = link.querySelector('.link-text');
            if (text) {
                link.setAttribute('title', text.textContent.trim());
            }
        });
    }

    initSidebarTooltips();

    // ============================================================
    // 10. INITIALISATION FINALE
    // ============================================================
    console.log('%cSidebar%c initialisée avec succès.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});