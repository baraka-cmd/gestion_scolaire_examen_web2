/* ============================================================
   Fichier : public/assets/js/templates/footer.js
   Rôle    : Gestion du pied de page
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // 1. ÉLÉMENTS DU DOM
    // ============================================================
    const footer         = document.getElementById('main-footer');
    const socialLinks    = document.querySelectorAll('.footer-social-link');
    const footerLinks    = document.querySelectorAll('.footer-link');
    const backToTopBtn   = document.getElementById('backToTop');

    // Si pas de footer, sortir
    if (!footer) return;

    // ============================================================
    // 2. BOUTON RETOUR EN HAUT
    // ============================================================
    function createBackToTopButton() {
        // Vérifier si le bouton existe déjà
        if (document.getElementById('backToTop')) return;

        const btn = document.createElement('button');
        btn.id = 'backToTop';
        btn.innerHTML = '<i class="fa-solid fa-chevron-up"></i>';
        btn.setAttribute('aria-label', 'Retour en haut de la page');
        btn.setAttribute('title', 'Retour en haut');
        btn.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--color-primary);
            color: var(--color-white);
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(44, 62, 144, 0.35);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transform: translateY(20px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        `;

        document.body.appendChild(btn);

        // Afficher/masquer selon le scroll
        window.addEventListener('scroll', () => {
            if (window.scrollY > 400) {
                btn.style.opacity = '1';
                btn.style.visibility = 'visible';
                btn.style.transform = 'translateY(0)';
            } else {
                btn.style.opacity = '0';
                btn.style.visibility = 'hidden';
                btn.style.transform = 'translateY(20px)';
            }
        }, { passive: true });

        // Remonter en haut au clic
        btn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Effet hover
        btn.addEventListener('mouseenter', () => {
            btn.style.transform = 'translateY(-3px)';
            btn.style.boxShadow = '0 8px 24px rgba(44, 62, 144, 0.45)';
        });

        btn.addEventListener('mouseleave', () => {
            if (window.scrollY > 400) {
                btn.style.transform = 'translateY(0)';
                btn.style.boxShadow = '0 4px 16px rgba(44, 62, 144, 0.35)';
            }
        });

        // Effet clic
        btn.addEventListener('mousedown', () => {
            btn.style.transform = 'scale(0.9)';
        });

        btn.addEventListener('mouseup', () => {
            btn.style.transform = 'scale(1)';
        });
    }

    createBackToTopButton();

    // ============================================================
    // 3. LIENS RÉSEAUX SOCIAUX — CONFIRMATION EXTERNE
    // ============================================================
    socialLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');

            // Si c'est un lien externe
            if (href && (href.startsWith('http://') || href.startsWith('https://'))) {
                e.preventDefault();
                
                // Ouvrir dans un nouvel onglet
                window.open(href, '_blank', 'noopener,noreferrer');
            }
        });

        // Tooltip natif au survol
        link.addEventListener('mouseenter', function() {
            const title = this.getAttribute('title');
            if (title) {
                this.setAttribute('data-tooltip', title);
            }
        });
    });

    // ============================================================
    // 4. LIENS DU FOOTER — SMOOTH SCROLL POUR LES ANCRES
    // ============================================================
    footerLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');

            // Si c'est une ancre interne
            if (href && href.startsWith('#')) {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    // Mettre à jour l'URL sans rechargement
                    history.pushState(null, '', href);
                }
            }
        });
    });

    // ============================================================
    // 5. ANNÉE DYNAMIQUE DANS LE COPYRIGHT
    // ============================================================
    const yearSpans = document.querySelectorAll('.footer-year');
    if (yearSpans.length > 0) {
        const currentYear = new Date().getFullYear();
        yearSpans.forEach(span => {
            span.textContent = currentYear;
        });
    }

    // ============================================================
    // 6. AFFICHAGE/MASQUAGE DU FOOTER AU SCROLL
    // ============================================================
    let lastScrollY = 0;
    let footerVisible = true;

    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;

        // Si on est en bas de la page, toujours afficher
        if (currentScrollY + windowHeight >= documentHeight - 10) {
            if (!footerVisible) {
                footer.style.transform = 'translateY(0)';
                footer.style.opacity = '1';
                footerVisible = true;
            }
            return;
        }

        // Masquer après 300px de scroll vers le bas
        if (currentScrollY > lastScrollY && currentScrollY > 300) {
            if (footerVisible) {
                footer.style.transform = 'translateY(100%)';
                footer.style.opacity = '0';
                footerVisible = false;
            }
        } else {
            if (!footerVisible) {
                footer.style.transform = 'translateY(0)';
                footer.style.opacity = '1';
                footerVisible = true;
            }
        }

        lastScrollY = currentScrollY;
    }, { passive: true });

    // ============================================================
    // 7. MODE SOMBRE (DÉTECTION)
    // ============================================================
    function detectColorScheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            footer.classList.add('dark-mode-aware');
        }
    }

    detectColorScheme();

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', detectColorScheme);

    // ============================================================
    // 8. INITIALISATION FINALE
    // ============================================================
    console.log('%cFooter%c initialisé avec succès.',
        'color: #2c3e90; font-weight: 600;', 'color: #64748b;');
});