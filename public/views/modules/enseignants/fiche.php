<?php
/**
 * Fichier : public/views/modules/enseignants/fiche.php
 * Rôle    : Fiche détaillée d'un enseignant
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('enseignants.consulter');

global $db;

// Récupérer l'ID
$enseignantId = (int) ($_GET['id'] ?? 0);
if ($enseignantId <= 0) {
    setFlash('error', 'ID enseignant invalide.');
    redirect('/gestion_scolaire/enseignants');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EnseignantController.php';
$enseignantController = new EnseignantController();

// Récupérer l'enseignant
$enseignant = $enseignantController->getEnseignantDetail($enseignantId);
if (!$enseignant) {
    setFlash('error', 'Enseignant introuvable.');
    redirect('/gestion_scolaire/enseignants');
}

// Récupérer les élèves de sa classe
$eleves = $enseignantController->getElevesClasse($enseignantId);

// Récupérer l'emploi du temps
$emploiDuTemps = [];
if ($enseignant['classe_id']) {
    $emploiDuTemps = $db->fetchAll(
        "SELECT edt.jour, edt.heure_debut, edt.heure_fin, m.libelle AS matiere
         FROM emploi_du_temps edt
         INNER JOIN matieres m ON edt.matiere_id = m.id
         WHERE edt.classe_id = :cid
         ORDER BY FIELD(edt.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), edt.heure_debut",
        ['cid' => $enseignant['classe_id']]
    );
}

$pageTitle = $enseignant['prenom'] . ' ' . $enseignant['nom'] . ' — Fiche enseignant';
$pageIcon  = 'fa-chalkboard-user';
$pageDescription = 'Fiche détaillée de l\'enseignant';
$extraCSS = ['modules/enseignants.css', 'modules/fiche_enseignant.css'];
$extraJS  = ['modules/fiche_enseignant.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/enseignants">Enseignants</a>
    <span class="separator">/</span>
    <span class="current"><?= e($enseignant['prenom'] . ' ' . $enseignant['nom']) ?></span>
</nav>

<!-- ============================================================
     HERO HEADER
     ============================================================ -->
<div class="enseignant-hero">
    <div class="enseignant-hero-avatar">
        <?= mb_strtoupper(mb_substr($enseignant['prenom'], 0, 1) . mb_substr($enseignant['nom'], 0, 1)) ?>
    </div>
    <div class="enseignant-hero-info">
        <h3 class="enseignant-hero-name"><?= e($enseignant['prenom'] . ' ' . $enseignant['nom']) ?></h3>
        <div class="enseignant-hero-meta">
            <span><i class="fa-solid fa-id-card"></i> <?= e($enseignant['matricule']) ?></span>
            <span><i class="fa-solid fa-<?= $enseignant['sexe'] === 'M' ? 'mars' : 'venus' ?>"></i> <?= $enseignant['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span>
            <span><i class="fa-solid fa-cake-candles"></i> <?= $enseignant['age'] ?> ans</span>
            <span><i class="fa-solid fa-briefcase"></i> <?= $enseignant['anciennete'] ?> an(s) d'ancienneté</span>
        </div>
        <div class="enseignant-hero-badges">
            <?php if ($enseignant['specialite']): ?>
                <span class="hero-badge"><i class="fa-solid fa-star"></i> <?= e($enseignant['specialite']) ?></span>
            <?php endif; ?>
            <?php if ($enseignant['classe_nom']): ?>
                <span class="hero-badge success"><i class="fa-solid fa-chalkboard"></i> Titulaire : <?= e($enseignant['classe_nom']) ?></span>
            <?php else: ?>
                <span class="hero-badge warning"><i class="fa-solid fa-ban"></i> Aucune classe</span>
            <?php endif; ?>
            <span class="hero-badge <?= $enseignant['est_actif'] ? 'success' : 'danger' ?>">
                <i class="fa-solid fa-<?= $enseignant['est_actif'] ? 'check-circle' : 'times-circle' ?>"></i>
                <?= $enseignant['est_actif'] ? 'Actif' : 'Inactif' ?>
            </span>
        </div>
    </div>
    <div class="enseignant-hero-actions">
        <?php if (can('enseignants.modifier')): ?>
            <a href="/gestion_scolaire/enseignants/modifier/<?= $enseignantId ?>" class="btn btn-sm btn-outline-primary" style="color:#fff;border-color:rgba(255,255,255,0.5);">
                <i class="fa-solid fa-pen-to-square"></i> Modifier
            </a>
        <?php endif; ?>
        <button type="button" class="btn-print-fiche" id="btnPrintFiche">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <a href="/gestion_scolaire/enseignants" class="btn btn-sm btn-outline-primary" style="color:#fff;border-color:rgba(255,255,255,0.5);">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<!-- ============================================================
     ONGLETS
     ============================================================ -->
<div class="enseignant-tabs">
    <button class="enseignant-tab active" data-tab="infos">
        <i class="fa-solid fa-id-card"></i> <span>Informations</span>
    </button>
    <button class="enseignant-tab" data-tab="classe">
        <i class="fa-solid fa-users"></i> <span>Ma classe</span>
        <span class="tab-count"><?= count($eleves) ?></span>
    </button>
    <button class="enseignant-tab" data-tab="emploi">
        <i class="fa-solid fa-calendar-days"></i> <span>Emploi du temps</span>
    </button>
</div>

<!-- ============================================================
     PANNEAU : INFORMATIONS
     ============================================================ -->
<div class="enseignant-panel active" id="panel-infos">
    <div class="enseignant-info-grid">
        <!-- Identité -->
        <div class="enseignant-info-card">
            <div class="enseignant-info-card-header">
                <div class="enseignant-info-card-icon blue"><i class="fa-solid fa-id-card"></i></div>
                <span class="enseignant-info-card-title">Identité</span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Nom complet</span>
                <span class="enseignant-info-value"><?= e($enseignant['prenom'] . ' ' . $enseignant['nom']) ?></span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Matricule</span>
                <span class="enseignant-info-value"><?= e($enseignant['matricule']) ?></span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Date de naissance</span>
                <span class="enseignant-info-value"><?= e($enseignant['date_naissance_format']) ?> (<?= $enseignant['age'] ?> ans)</span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Sexe</span>
                <span class="enseignant-info-value"><?= $enseignant['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span>
            </div>
        </div>

        <!-- Contact -->
        <div class="enseignant-info-card">
            <div class="enseignant-info-card-header">
                <div class="enseignant-info-card-icon green"><i class="fa-solid fa-address-book"></i></div>
                <span class="enseignant-info-card-title">Contact</span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Téléphone</span>
                <span class="enseignant-info-value" id="telephoneEnseignant"><?= e($enseignant['telephone'] ?: '—') ?></span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Email</span>
                <span class="enseignant-info-value"><?= e($enseignant['email'] ?: '—') ?></span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Adresse</span>
                <span class="enseignant-info-value <?= empty($enseignant['adresse']) ? 'empty' : '' ?>"><?= e($enseignant['adresse'] ?: 'Non renseignée') ?></span>
            </div>
        </div>

        <!-- Carrière -->
        <div class="enseignant-info-card">
            <div class="enseignant-info-card-header">
                <div class="enseignant-info-card-icon orange"><i class="fa-solid fa-graduation-cap"></i></div>
                <span class="enseignant-info-card-title">Carrière</span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Diplôme</span>
                <span class="enseignant-info-value <?= empty($enseignant['diplome']) ? 'empty' : '' ?>"><?= e($enseignant['diplome'] ?: 'Non renseigné') ?></span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Spécialité</span>
                <span class="enseignant-info-value <?= empty($enseignant['specialite']) ? 'empty' : '' ?>"><?= e($enseignant['specialite'] ?: 'Non renseignée') ?></span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Date d'embauche</span>
                <span class="enseignant-info-value"><?= e($enseignant['date_embauche_format'] ?: '—') ?> (<?= $enseignant['anciennete'] ?> ans)</span>
            </div>
            <div class="enseignant-info-field">
                <span class="enseignant-info-label">Compte utilisateur</span>
                <span class="enseignant-info-value">
                    <?php if ($enseignant['compte_login']): ?>
                        <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> <?= e($enseignant['compte_login']) ?></span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Aucun compte</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     PANNEAU : MA CLASSE
     ============================================================ -->
<div class="enseignant-panel" id="panel-classe">
    <?php if ($enseignant['classe_nom']): ?>
        <div class="classe-titulaire-card">
            <div class="classe-titulaire-header">
                <i class="fa-solid fa-chalkboard"></i> Classe titulaire : <?= e($enseignant['classe_nom']) ?>
            </div>
            <div class="classe-titulaire-grid">
                <div class="classe-titulaire-stat">
                    <div class="classe-titulaire-stat-value"><?= $enseignant['classe_niveau'] ?>ème</div>
                    <div class="classe-titulaire-stat-label">Niveau</div>
                </div>
                <div class="classe-titulaire-stat">
                    <div class="classe-titulaire-stat-value"><?= e($enseignant['classe_salle']) ?></div>
                    <div class="classe-titulaire-stat-label">Salle</div>
                </div>
                <div class="classe-titulaire-stat">
                    <div class="classe-titulaire-stat-value"><?= $enseignant['nb_eleves'] ?> / <?= $enseignant['classe_capacite'] ?></div>
                    <div class="classe-titulaire-stat-label">Élèves</div>
                </div>
            </div>
        </div>

        <?php if (!empty($eleves)): ?>
            <div class="data-table-card">
                <div class="data-table-header">
                    <span class="data-table-title"><i class="fa-solid fa-users"></i> Liste des élèves</span>
                    <span class="data-table-count"><?= count($eleves) ?> élève(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Matricule</th><th>Élève</th><th>Sexe</th><th>Né(e) le</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($eleves as $eleve): ?>
                                <tr>
                                    <td><span class="badge badge-primary"><?= e($eleve['matricule']) ?></span></td>
                                    <td><strong><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></strong></td>
                                    <td><?= $eleve['sexe'] === 'M' ? '<span style="color:#3b82f6;"><i class="fa-solid fa-mars"></i> M</span>' : '<span style="color:#ec4899;"><i class="fa-solid fa-venus"></i> F</span>' ?></td>
                                    <td><?= e($eleve['date_naissance_format']) ?></td>
                                    <td><a href="/gestion_scolaire/eleves/fiche/<?= $eleve['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-classe-card">
            <i class="fa-solid fa-chalkboard"></i>
            <p><strong>Aucune classe assignée</strong></p>
            <p style="font-size:0.85rem;margin-top:4px;">Cet enseignant n'est pas encore titulaire d'une classe.</p>
        </div>
    <?php endif; ?>
</div>

<!-- ============================================================
     PANNEAU : EMPLOI DU TEMPS
     ============================================================ -->
<div class="enseignant-panel" id="panel-emploi">
    <?php if (!empty($emploiDuTemps)): ?>
        <?php
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $currentJour = '';
        ?>
        <div class="data-table-card">
            <div class="data-table-header">
                <span class="data-table-title"><i class="fa-solid fa-calendar-days"></i> Emploi du temps — <?= e($enseignant['classe_nom']) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Jour</th><th>Heure début</th><th>Heure fin</th><th>Matière</th></tr></thead>
                    <tbody>
                        <?php foreach ($emploiDuTemps as $creneau): ?>
                            <tr>
                                <td>
                                    <?php if ($currentJour !== $creneau['jour']): ?>
                                        <strong><?= e($creneau['jour']) ?></strong>
                                        <?php $currentJour = $creneau['jour']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($creneau['heure_debut']) ?></td>
                                <td><?= e($creneau['heure_fin']) ?></td>
                                <td><span class="badge badge-info"><?= e($creneau['matiere']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="no-classe-card">
            <i class="fa-solid fa-calendar-days"></i>
            <p><strong>Aucun emploi du temps</strong></p>
            <p style="font-size:0.85rem;margin-top:4px;">L'emploi du temps n'a pas encore été configuré pour cette classe.</p>
        </div>
    <?php endif; ?>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>