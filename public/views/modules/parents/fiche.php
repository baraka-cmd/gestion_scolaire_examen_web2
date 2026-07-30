<?php
/**
 * Fichier : public/views/modules/parents/fiche.php
 * Rôle    : Fiche détaillée d'un parent/tuteur avec ses enfants
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('parents.consulter');

global $db;

// Récupérer l'ID
$parentId = (int) ($_GET['id'] ?? 0);
if ($parentId <= 0) {
    setFlash('error', 'ID parent invalide.');
    redirect('/gestion_scolaire/parents');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ParentController.php';
$parentController = new ParentController();

// Récupérer le parent
$parent = $parentController->getParentDetail($parentId);
if (!$parent) {
    setFlash('error', 'Parent introuvable.');
    redirect('/gestion_scolaire/parents');
}

// Récupérer les enfants
$enfants = $parentController->getEnfantsByParent($parentId);

// Récupérer les élèves disponibles pour liaison
$elevesDisponibles = $db->fetchAll(
    "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
            c.nom AS classe_nom
     FROM eleves e
     LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
     LEFT JOIN classes c ON i.classe_id = c.id
     WHERE e.est_actif = 1
     AND e.id NOT IN (SELECT eleve_id FROM eleve_parent WHERE parent_id = :pid)
     ORDER BY e.nom",
    ['pid' => $parentId]
);

$pageTitle = $parent['prenom'] . ' ' . $parent['nom'] . ' — Fiche parent';
$pageIcon  = 'fa-people-roof';
$pageDescription = 'Fiche détaillée du parent/tuteur';
$extraCSS = ['modules/parents.css', 'modules/fiche_parent.css'];
$extraJS  = ['modules/fiche_parent.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/parents">Parents</a>
    <span class="separator">/</span>
    <span class="current"><?= e($parent['prenom'] . ' ' . $parent['nom']) ?></span>
</nav>

<!-- ============================================================
     HERO HEADER
     ============================================================ -->
<div class="parent-hero">
    <div class="parent-hero-avatar">
        <?= mb_strtoupper(mb_substr($parent['prenom'], 0, 1) . mb_substr($parent['nom'], 0, 1)) ?>
    </div>
    <div class="parent-hero-info">
        <h3 class="parent-hero-name"><?= e($parent['prenom'] . ' ' . $parent['nom']) ?></h3>
        <span class="parent-hero-lien">
            <i class="fa-solid fa-link"></i> <?= e($parent['lien']) ?>
        </span>
        <div class="parent-hero-meta">
            <span id="telephoneParent">
                <i class="fa-solid fa-phone"></i> <?= e($parent['telephone_principal']) ?>
            </span>
            <?php if (!empty($parent['telephone_secondaire'])): ?>
                <span><i class="fa-solid fa-phone"></i> <?= e($parent['telephone_secondaire']) ?></span>
            <?php endif; ?>
            <?php if (!empty($parent['email'])): ?>
                <span><i class="fa-solid fa-envelope"></i> <?= e($parent['email']) ?></span>
            <?php endif; ?>
            <span><i class="fa-solid fa-children"></i> <?= $parent['nb_enfants'] ?> enfant(s)</span>
        </div>
    </div>
    <div class="parent-hero-actions">
        <?php if (can('parents.modifier')): ?>
            <a href="/gestion_scolaire/parents/modifier/<?= $parentId ?>" class="btn btn-sm btn-outline-primary" style="color:#fff;border-color:rgba(255,255,255,0.5);">
                <i class="fa-solid fa-pen-to-square"></i> Modifier
            </a>
        <?php endif; ?>
        <button type="button" class="btn-print-fiche" id="btnPrintFiche">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <a href="/gestion_scolaire/parents" class="btn btn-sm btn-outline-primary" style="color:#fff;border-color:rgba(255,255,255,0.5);">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<!-- ============================================================
     ONGLETS
     ============================================================ -->
<div class="parent-tabs">
    <button class="parent-tab active" data-tab="infos">
        <i class="fa-solid fa-id-card"></i> <span>Informations</span>
    </button>
    <button class="parent-tab" data-tab="enfants">
        <i class="fa-solid fa-children"></i> <span>Enfants</span>
        <span class="tab-count"><?= count($enfants) ?></span>
    </button>
</div>

<!-- ============================================================
     PANNEAU : INFORMATIONS
     ============================================================ -->
<div class="parent-panel active" id="panel-infos">
    <div class="parent-info-grid">
        <!-- Identité -->
        <div class="parent-info-card">
            <div class="parent-info-card-header">
                <div class="parent-info-card-icon blue"><i class="fa-solid fa-id-card"></i></div>
                <span class="parent-info-card-title">Identité</span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Nom complet</span>
                <span class="parent-info-value"><?= e($parent['prenom'] . ' ' . $parent['nom']) ?></span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Lien de parenté</span>
                <span class="parent-info-value"><?= e($parent['lien']) ?></span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Profession</span>
                <span class="parent-info-value <?= empty($parent['profession']) ? 'empty' : '' ?>"><?= e($parent['profession'] ?: 'Non renseignée') ?></span>
            </div>
        </div>

        <!-- Contact -->
        <div class="parent-info-card">
            <div class="parent-info-card-header">
                <div class="parent-info-card-icon green"><i class="fa-solid fa-address-book"></i></div>
                <span class="parent-info-card-title">Contact</span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Téléphone principal</span>
                <span class="parent-info-value"><i class="fa-solid fa-phone"></i> <?= e($parent['telephone_principal']) ?></span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Téléphone secondaire</span>
                <span class="parent-info-value <?= empty($parent['telephone_secondaire']) ? 'empty' : '' ?>"><?= e($parent['telephone_secondaire'] ?: 'Non renseigné') ?></span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Email</span>
                <span class="parent-info-value <?= empty($parent['email']) ? 'empty' : '' ?>"><?= e($parent['email'] ?: 'Non renseigné') ?></span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Adresse</span>
                <span class="parent-info-value <?= empty($parent['adresse']) ? 'empty' : '' ?>"><?= e($parent['adresse'] ?: 'Non renseignée') ?></span>
            </div>
        </div>

        <!-- Statut -->
        <div class="parent-info-card">
            <div class="parent-info-card-header">
                <div class="parent-info-card-icon orange"><i class="fa-solid fa-circle-info"></i></div>
                <span class="parent-info-card-title">Statut</span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Type de contact</span>
                <span class="parent-info-value">
                    <?php if ($parent['est_contact_urgence']): ?>
                        <span class="badge badge-success"><i class="fa-solid fa-phone-volume"></i> Contact d'urgence prioritaire</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Contact secondaire</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Compte utilisateur</span>
                <span class="parent-info-value">
                    <?php if ($parent['utilisateur_id']): ?>
                        <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Compte lié</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Aucun compte</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="parent-info-field">
                <span class="parent-info-label">Enfants liés</span>
                <span class="parent-info-value"><strong><?= $parent['nb_enfants'] ?></strong> enfant(s)</span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     PANNEAU : ENFANTS
     ============================================================ -->
<div class="parent-panel" id="panel-enfants">
    <?php if (empty($enfants)): ?>
        <div class="empty-state" style="padding:40px;">
            <i class="fa-solid fa-children"></i>
            <p>Aucun enfant lié à ce parent</p>
        </div>
    <?php else: ?>
        <div class="enfants-container">
            <?php foreach ($enfants as $index => $enfant): ?>
                <div class="enfant-detail-card <?= $enfant['est_tuteur_principal'] ? 'principal' : 'secondaire' ?>">
                    <div class="enfant-card-top">
                        <div class="enfant-card-identity">
                            <div class="enfant-card-avatar <?= ['blue', 'green', 'orange', 'purple', 'pink'][$index % 5] ?>">
                                <?= mb_strtoupper(mb_substr($enfant['prenom'], 0, 1) . mb_substr($enfant['nom'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="enfant-card-name"><?= e($enfant['prenom'] . ' ' . $enfant['nom']) ?></div>
                                <div class="enfant-card-matricule"><?= e($enfant['matricule']) ?></div>
                            </div>
                        </div>
                        <span class="enfant-card-role <?= $enfant['est_tuteur_principal'] ? 'principal' : 'secondaire' ?>">
                            <?= $enfant['est_tuteur_principal'] ? 'Principal' : 'Secondaire' ?>
                        </span>
                    </div>
                    <div class="enfant-card-details">
                        <div class="enfant-detail-row">
                            <i class="fa-solid fa-cake-candles"></i>
                            <span>Né(e) le <strong><?= e($enfant['date_naissance_format']) ?></strong> (<?= $enfant['age'] ?> ans)</span>
                        </div>
                        <div class="enfant-detail-row">
                            <i class="fa-solid fa-<?= $enfant['sexe'] === 'M' ? 'mars' : 'venus' ?>"></i>
                            <span><?= $enfant['sexe'] === 'M' ? 'Garçon' : 'Fille' ?></span>
                        </div>
                        <div class="enfant-detail-row">
                            <i class="fa-solid fa-school"></i>
                            <span>Classe : <strong><?= e($enfant['classe_nom'] ?? 'Non inscrit') ?></strong></span>
                        </div>
                    </div>
                    <div class="enfant-card-actions">
                        <a href="/gestion_scolaire/eleves/fiche/<?= $enfant['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fa-solid fa-eye"></i> Voir fiche
                        </a>
                        <button class="btn btn-sm btn-outline-danger btn-dissocier" 
                                data-parent-id="<?= $parentId ?>" 
                                data-eleve-id="<?= $enfant['id'] ?>" 
                                data-eleve-nom="<?= e($enfant['prenom'] . ' ' . $enfant['nom']) ?>"
                                title="Dissocier">
                            <i class="fa-solid fa-link-slash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

        <!-- Section : Lier un enfant -->
    <?php if (!empty($elevesDisponibles) && can('parents.modifier')): ?>
        <div class="lier-enfant-card mt-4">
            <div class="lier-enfant-header">
                <i class="fa-solid fa-link"></i> Lier un nouvel enfant
            </div>
            <form id="formLierEleve" class="lier-enfant-form-box">
                <input type="hidden" name="parent_id" value="<?= $parentId ?>">

                <!-- Champ de recherche de l'élève -->
                <div class="form-group mb-3">
                    <div class="search-wrapper">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input type="text" id="searchEleveLier" class="form-control" placeholder="Rechercher un élève par nom, prénom, matricule..." autocomplete="off">
                    </div>
                </div>

                <!-- Liste des élèves sélectionnables -->
                <div class="eleves-selection-list mb-3" id="elevesLierContainer">
                    <?php foreach ($elevesDisponibles as $eleve): ?>
                        <label class="eleve-lier-item">
                            <input type="radio" name="eleve_id" value="<?= $eleve['id'] ?>" required>
                            <div class="eleve-lier-info">
                                <div class="eleve-lier-name"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></div>
                                <div class="eleve-lier-detail">
                                    <span class="badge-subtle"><?= e($eleve['matricule']) ?></span>
                                    <span><?= e($eleve['classe_nom'] ?? 'Non inscrit') ?></span>
                                    <span><?= $eleve['sexe'] === 'M' ? 'Garçon' : 'Fille' ?></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Action & Option Tuteur -->
                <div class="lier-enfant-footer">
                    <label class="checkbox-wrapper" style="margin-bottom:0;">
                        <input type="checkbox" name="est_tuteur_principal" id="est_tuteur_principal" value="1">
                        <span class="checkbox-custom"><i class="fa-solid fa-check"></i></span>
                        <span class="checkbox-label">Définir comme tuteur principal</span>
                    </label>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-link"></i> Lier l'élève sélectionné
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>