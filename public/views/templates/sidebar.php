<?php
/**
 * Fichier : public/views/templates/sidebar.php
 * Rôle    : Menu de navigation latéral dynamique selon le rôle et les permissions
 */

defined('ACCESS') or die('Accès interdit');

// Initialiser Security
$security = Security::getInstance();

// Récupérer le rôle de l'utilisateur connecté
$role = $_SESSION['utilisateur_role'] ?? '';

// URL de base pour les liens
$baseUrl = '/gestion_scolaire';

// Fonction pour générer un lien de menu (sans vérification de permission)
function menuLink($url, $icon, $label, $permission = null, $badge = null)
{
    $class = '';
    $badgeHtml = '';
    if ($badge) {
        $badgeClass = is_numeric($badge) ? '' : ' ' . $badge;
        $badgeHtml = "<span class=\"menu-badge{$badgeClass}\">{$badge}</span>";
    }
    return "<li><a href=\"{$url}\" class=\"menu-link {$class}\"><i class=\"fa-solid {$icon}\"></i><span class=\"link-text\">{$label}</span>{$badgeHtml}</a></li>";
}

// Fonction pour un sous-menu (sans vérification de permission)
function submenuLink($url, $icon, $label, $permission = null)
{
    return "<li><a href=\"{$url}\" class=\"menu-link\"><i class=\"fa-solid {$icon}\"></i><span class=\"link-text\">{$label}</span></a></li>";
}

?>

<!-- ============================================================
     MENU ADMIN / DIRECTEUR
     ============================================================ -->
<?php if ($role === 'Admin' || $role === 'Directeur'): ?>
    
    <div class="menu-section">
        <span class="menu-title">Principal</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/dashboard', 'fa-gauge-high', 'Tableau de bord', 'dashboard.consulter') ?>
        </ul>
    </div>

    <?php if (can('utilisateurs.consulter') || can('roles.consulter') || can('classes.consulter') || can('matieres.consulter')): ?>
    <div class="menu-section">
        <span class="menu-title">Administration</span>
        <ul class="menu-list">
            <?php if (can('utilisateurs.consulter') || can('roles.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-users-gear"></i>
                    <span class="link-text">Utilisateurs</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/utilisateurs', 'fa-list', 'Tous les utilisateurs', 'utilisateurs.consulter') ?>
                    <?= submenuLink($baseUrl . '/utilisateurs/ajouter', 'fa-plus', 'Ajouter un utilisateur', 'utilisateurs.creer') ?>
                    <?= submenuLink($baseUrl . '/roles', 'fa-user-shield', 'Gérer les rôles', 'roles.consulter') ?>
                </ul>
            </li>
            <?php endif; ?>
            <?= menuLink($baseUrl . '/classes', 'fa-chalkboard', 'Classes', 'classes.consulter') ?>
            <?= menuLink($baseUrl . '/matieres', 'fa-book-open', 'Matières', 'matieres.consulter') ?>
            <?= menuLink($baseUrl . '/annees-scolaires', 'fa-calendar-alt', 'Années scolaires', 'parametres.consulter') ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (can('eleves.consulter') || can('parents.consulter') || can('enseignants.consulter') || can('inscriptions.consulter')): ?>
    <div class="menu-section">
        <span class="menu-title">Vie scolaire</span>
        <ul class="menu-list">
            <?php if (can('eleves.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span class="link-text">Élèves</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/eleves', 'fa-list', 'Tous les élèves', 'eleves.consulter') ?>
                    <?= submenuLink($baseUrl . '/eleves/ajouter', 'fa-plus', 'Ajouter un élève', 'eleves.creer') ?>
                    <?= submenuLink($baseUrl . '/eleves/import', 'fa-file-import', 'Importer', 'eleves.importer') ?>
                    <?= submenuLink($baseUrl . '/eleves/export', 'fa-file-export', 'Exporter', 'eleves.exporter') ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?php if (can('parents.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-people-roof"></i>
                    <span class="link-text">Parents</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/parents', 'fa-list', 'Tous les parents', 'parents.consulter') ?>
                    <?= submenuLink($baseUrl . '/parents/ajouter', 'fa-plus', 'Ajouter un parent', 'parents.creer') ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?php if (can('enseignants.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span class="link-text">Enseignants</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/enseignants', 'fa-list', 'Tous les enseignants', 'enseignants.consulter') ?>
                    <?= submenuLink($baseUrl . '/enseignants/ajouter', 'fa-plus', 'Ajouter un enseignant', 'enseignants.creer') ?>
                    <?= submenuLink($baseUrl . '/enseignants/affectations', 'fa-link', 'Affectations', 'enseignants.modifier') ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?= menuLink($baseUrl . '/inscriptions', 'fa-file-pen', 'Inscriptions', 'inscriptions.consulter') ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (can('notes.consulter') || can('resultats.consulter') || can('bulletins.consulter')): ?>
        <div class="menu-section">
            <span class="menu-title">Pédagogie</span>
            <ul class="menu-list">
                <?php if (can('notes.consulter')): ?>
                <li class="menu-item has-submenu">
                    <a href="#" class="menu-link">
                        <i class="fa-solid fa-pen-to-square"></i>
                        <span class="link-text">Notes</span>
                        <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <?= submenuLink($baseUrl . '/notes', 'fa-list', 'Toutes les évaluations', 'notes.consulter') ?>
                        <?= submenuLink($baseUrl . '/notes/ajouter', 'fa-plus', 'Nouvelle évaluation', 'notes.creer') ?>
                    </ul>
                </li>
                <?php endif; ?>
                
                <?php if (can('resultats.consulter')): ?>
                <li class="menu-item has-submenu">
                    <a href="#" class="menu-link">
                        <i class="fa-solid fa-square-poll-vertical"></i>
                        <span class="link-text">Résultats</span>
                        <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <?= submenuLink($baseUrl . '/resultats', 'fa-list', 'Consulter', 'resultats.consulter') ?>
                        <?= submenuLink($baseUrl . '/resultats/proces-verbaux', 'fa-file-contract', 'Procès-verbaux', 'resultats.exporter') ?>
                        <?= submenuLink($baseUrl . '/resultats/proclamation', 'fa-list-ol', 'Proclamation', 'resultats.consulter') ?>
                    </ul>
                </li>
                <?php endif; ?>
                
                <?php if (can('bulletins.consulter')): ?>
                <li class="menu-item has-submenu">
                    <a href="#" class="menu-link">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span class="link-text">Bulletins</span>
                        <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                    </a>
                    <ul class="submenu">
                        <?= submenuLink($baseUrl . '/bulletins', 'fa-list', 'Consulter', 'bulletins.consulter') ?>
                        <?= submenuLink($baseUrl . '/bulletins/generer', 'fa-gear', 'Générer', 'bulletins.generer') ?>
                        <?= submenuLink($baseUrl . '/bulletins/imprimer', 'fa-print', 'Imprimer', 'bulletins.imprimer') ?>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </div>
   
    <?php endif; ?>

    <?php if (can('emploi.consulter') || can('absences.consulter') || can('retards.consulter') || can('discipline.consulter')): ?>
    <div class="menu-section">
        <span class="menu-title">Présences & Discipline</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/emploi-du-temps', 'fa-calendar-days', 'Emploi du temps', 'emploi.consulter') ?>
            
            <?php if (can('absences.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <span class="link-text">Absences</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/absences', 'fa-list', 'Liste des absences', 'absences.consulter') ?>
                    <?= submenuLink($baseUrl . '/absences/marquer', 'fa-check', 'Marquer présence', 'absences.marquer') ?>
                    <?= submenuLink($baseUrl . '/absences/justifier', 'fa-file-medical', 'Justifier', 'absences.justifier') ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?= menuLink($baseUrl . '/retards', 'fa-clock', 'Retards', 'retards.consulter') ?>
            
            <?php if (can('discipline.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-gavel"></i>
                    <span class="link-text">Discipline</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/discipline', 'fa-list', 'Incidents', 'discipline.consulter') ?>
                    <?= submenuLink($baseUrl . '/discipline/ajouter', 'fa-plus', 'Signaler un incident', 'discipline.signaler') ?>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (can('paiements.consulter')): ?>
    <div class="menu-section">
        <span class="menu-title">Finances</span>
        <ul class="menu-list">
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span class="link-text">Paiements</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/paiements', 'fa-list', 'Tous les paiements', 'paiements.consulter') ?>
                    <?= submenuLink($baseUrl . '/paiements/enregistrer', 'fa-plus', 'Enregistrer', 'paiements.enregistrer') ?>
                    <?= submenuLink($baseUrl . '/paiements/impayes', 'fa-exclamation-circle', 'Impayés', 'paiements.consulter') ?>
                    <?= submenuLink($baseUrl . '/paiements/factures', 'fa-receipt', 'Factures', 'paiements.factures') ?>
                </ul>
            </li>
            <?= menuLink($baseUrl . '/frais-scolaires', 'fa-tags', 'Frais scolaires', 'paiements.consulter') ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (can('cantine.consulter') || can('transport.consulter')): ?>
    <div class="menu-section">
        <span class="menu-title">Services</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/cantine', 'fa-utensils', 'Cantine', 'cantine.consulter') ?>
            <?= menuLink($baseUrl . '/transport', 'fa-bus', 'Transport', 'transport.consulter') ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (can('messages.consulter') || can('calendrier.consulter') || can('documents.consulter')): ?>
    <div class="menu-section">
        <span class="menu-title">Communication</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/messages', 'fa-envelope', 'Messages', 'messages.consulter', '3') ?>
            <?= menuLink($baseUrl . '/calendrier', 'fa-calendar', 'Calendrier', 'calendrier.consulter') ?>
            <?= menuLink($baseUrl . '/documents', 'fa-folder-open', 'Documents', 'documents.consulter') ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (can('rapports.consulter')): ?>
    <div class="menu-section">
        <span class="menu-title">Rapports</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/rapports/effectifs', 'fa-chart-bar', 'Effectifs', 'rapports.consulter') ?>
            <?= menuLink($baseUrl . '/rapports/statistiques', 'fa-chart-pie', 'Statistiques', 'rapports.consulter') ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (can('parametres.consulter') || can('sauvegardes.consulter') || can('logs.consulter')): ?>
    <div class="menu-section">
        <span class="menu-title">Système</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/parametres', 'fa-gear', 'Paramètres', 'parametres.consulter') ?>
            <?= menuLink($baseUrl . '/sauvegardes', 'fa-database', 'Sauvegardes', 'sauvegardes.consulter') ?>
            <?= menuLink($baseUrl . '/logs', 'fa-clock-rotate-left', 'Journaux', 'logs.consulter') ?>
        </ul>
    </div>
    <?php endif; ?>

<!-- ============================================================
     MENU SECRÉTAIRE
     ============================================================ -->
<?php elseif ($role === 'Secrétaire'): ?>
    
    <div class="menu-section">
        <span class="menu-title">Principal</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/dashboard', 'fa-gauge-high', 'Tableau de bord', 'dashboard.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Vie scolaire</span>
        <ul class="menu-list">
            <?php if (can('eleves.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span class="link-text">Élèves</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/eleves', 'fa-list', 'Tous les élèves', 'eleves.consulter') ?>
                    <?= submenuLink($baseUrl . '/eleves/ajouter', 'fa-plus', 'Ajouter un élève', 'eleves.creer') ?>
                    <?= submenuLink($baseUrl . '/eleves/import', 'fa-file-import', 'Importer', 'eleves.importer') ?>
                    <?= submenuLink($baseUrl . '/eleves/export', 'fa-file-export', 'Exporter', 'eleves.exporter') ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?= menuLink($baseUrl . '/parents', 'fa-people-roof', 'Parents', 'parents.consulter') ?>
            <?= menuLink($baseUrl . '/enseignants', 'fa-chalkboard-user', 'Enseignants', 'enseignants.consulter') ?>
            <?= menuLink($baseUrl . '/inscriptions', 'fa-file-pen', 'Inscriptions', 'inscriptions.consulter') ?>
            <?= menuLink($baseUrl . '/classes', 'fa-chalkboard', 'Classes', 'classes.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Organisation</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/emploi-du-temps', 'fa-calendar-days', 'Emploi du temps', 'emploi.consulter') ?>
            
            <?php if (can('absences.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <span class="link-text">Absences</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/absences', 'fa-list', 'Liste des absences', 'absences.consulter') ?>
                    <?= submenuLink($baseUrl . '/absences/marquer', 'fa-check', 'Marquer présence', 'absences.marquer') ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?= menuLink($baseUrl . '/retards', 'fa-clock', 'Retards', 'retards.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Finances</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/paiements', 'fa-money-bill-wave', 'Paiements', 'paiements.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Communication</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/messages', 'fa-envelope', 'Messages', 'messages.consulter') ?>
            <?= menuLink($baseUrl . '/calendrier', 'fa-calendar', 'Calendrier', 'calendrier.consulter') ?>
        </ul>
    </div>

<!-- ============================================================
     MENU ENSEIGNANT
     ============================================================ -->
<?php elseif ($role === 'Enseignant'): ?>
    
    <div class="menu-section">
        <span class="menu-title">Principal</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/dashboard', 'fa-gauge-high', 'Tableau de bord', 'dashboard.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Ma classe</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/ma-classe/eleves', 'fa-graduation-cap', 'Mes élèves', 'eleves.consulter') ?>
            <?= menuLink($baseUrl . '/emploi-du-temps', 'fa-calendar-days', 'Emploi du temps', 'emploi.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Pédagogie</span>
        <ul class="menu-list">
            <?php if (can('evaluations.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span class="link-text">Évaluations</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/evaluations', 'fa-list', 'Toutes les évaluations', 'evaluations.consulter') ?>
                    <?= submenuLink($baseUrl . '/evaluations/ajouter', 'fa-plus', 'Créer une évaluation', 'evaluations.creer') ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?php if (can('notes.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span class="link-text">Notes</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/notes', 'fa-pencil', 'Saisir des notes', 'notes.saisir') ?>
                    <?= submenuLink($baseUrl . '/notes/consulter', 'fa-magnifying-glass', 'Consulter', 'notes.consulter') ?>
                </ul>
            </li>
            <?php endif; ?>
            
            <?= menuLink($baseUrl . '/resultats', 'fa-square-poll-vertical', 'Résultats', 'resultats.consulter') ?>
            
            <?php if (can('bulletins.consulter')): ?>
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span class="link-text">Bulletins</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/bulletins', 'fa-list', 'Consulter', 'bulletins.consulter') ?>
                    <?= submenuLink($baseUrl . '/bulletins/generer', 'fa-gear', 'Générer', 'bulletins.generer') ?>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Présences</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/absences/marquer', 'fa-calendar-check', 'Marquer absences', 'absences.marquer') ?>
            <?= menuLink($baseUrl . '/absences', 'fa-calendar-xmark', 'Liste absences', 'absences.consulter') ?>
            <?= menuLink($baseUrl . '/retards', 'fa-clock', 'Retards', 'retards.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Communication</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/messages', 'fa-envelope', 'Messages', 'messages.consulter') ?>
        </ul>
    </div>

<!-- ============================================================
     MENU COMPTABLE
     ============================================================ -->
<?php elseif ($role === 'Comptable'): ?>
    
    <div class="menu-section">
        <span class="menu-title">Principal</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/dashboard', 'fa-gauge-high', 'Tableau de bord', 'dashboard.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Finances</span>
        <ul class="menu-list">
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span class="link-text">Paiements</span>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/paiements', 'fa-list', 'Tous les paiements', 'paiements.consulter') ?>
                    <?= submenuLink($baseUrl . '/paiements/enregistrer', 'fa-plus', 'Enregistrer', 'paiements.enregistrer') ?>
                    <?= submenuLink($baseUrl . '/paiements/impayes', 'fa-exclamation-circle', 'Impayés', 'paiements.consulter') ?>
                    <?= submenuLink($baseUrl . '/paiements/factures', 'fa-receipt', 'Factures', 'paiements.factures') ?>
                </ul>
            </li>
            <?= menuLink($baseUrl . '/frais-scolaires', 'fa-tags', 'Frais scolaires', 'paiements.modifier') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Services</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/cantine', 'fa-utensils', 'Cantine', 'cantine.consulter') ?>
            <?= menuLink($baseUrl . '/transport', 'fa-bus', 'Transport', 'transport.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Rapports</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/rapports/financiers', 'fa-chart-pie', 'Rapports financiers', 'rapports.consulter') ?>
        </ul>
    </div>

<!-- ============================================================
     MENU PARENT
     ============================================================ -->
<?php elseif ($role === 'Parent'): ?>
    
    <div class="menu-section">
        <span class="menu-title">Principal</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/dashboard', 'fa-gauge-high', 'Tableau de bord', 'dashboard.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Mes enfants</span>
        <ul class="menu-list">
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span class="link-text">Mes enfants</span>
                    <i class="fa-solid fa-chevron-down submenu-arrow"></i>
                </a>
                <ul class="submenu">
                    <?= submenuLink($baseUrl . '/mes-enfants', 'fa-list', 'Voir mes enfants', 'eleves.consulter') ?>
                    <?= submenuLink($baseUrl . '/notes/consulter', 'fa-magnifying-glass', 'Consulter les notes', 'notes.consulter') ?>
                    <?= submenuLink($baseUrl . '/bulletins', 'fa-file-invoice', 'Bulletins', 'bulletins.consulter') ?>
                </ul>
            </li>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Vie scolaire</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/absences', 'fa-calendar-xmark', 'Absences', 'absences.consulter') ?>
            <?= menuLink($baseUrl . '/retards', 'fa-clock', 'Retards', 'retards.consulter') ?>
            <?= menuLink($baseUrl . '/discipline', 'fa-gavel', 'Discipline', 'discipline.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Finances</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/paiements', 'fa-money-bill-wave', 'Paiements', 'paiements.consulter') ?>
        </ul>
    </div>

    <div class="menu-section">
        <span class="menu-title">Communication</span>
        <ul class="menu-list">
            <?= menuLink($baseUrl . '/messages', 'fa-envelope', 'Messages', 'messages.consulter') ?>
            <?= menuLink($baseUrl . '/calendrier', 'fa-calendar', 'Calendrier', 'calendrier.consulter') ?>
        </ul>
    </div>

<?php endif; ?>

<!-- Pied du sidebar : Déconnexion -->
<div class="sidebar-footer">
    <a href="/gestion_scolaire/logout" class="menu-link">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span class="link-text">Déconnexion</span>
    </a>
</div>