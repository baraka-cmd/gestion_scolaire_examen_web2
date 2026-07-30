<?php
/**
 * Fichier : public/views/modules/classes/detail.php
 * Rôle    : Fiche détaillée d'une classe avec liste des élèves
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('classes.consulter');

global $db;

// Récupérer l'ID depuis l'URL
$classeId = (int) ($_GET['id'] ?? 0);

if ($classeId <= 0) {
    setFlash('error', 'ID classe invalide.');
    redirect('/gestion_scolaire/classes');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ClasseController.php';
$classeController = new ClasseController();

// Récupérer les détails de la classe
$classe = $classeController->getClasseDetail($classeId);

if (!$classe) {
    setFlash('error', 'Classe introuvable.');
    redirect('/gestion_scolaire/classes');
}

// Récupérer les élèves de la classe
$eleves = $classeController->getElevesByClasse($classeId);

// Statistiques
$nbGarcons = 0;
$nbFilles = 0;
foreach ($eleves as $eleve) {
    if ($eleve['sexe'] === 'M') $nbGarcons++;
    else $nbFilles++;
}

$tauxRemplissage = $classe['capacite_max'] > 0 ? round(($classe['nb_eleves'] / $classe['capacite_max']) * 100) : 0;
$placesRestantes = $classe['capacite_max'] - $classe['nb_eleves'];

$pageTitle = $classe['nom'] . ' — Détails';
$pageIcon  = 'fa-chalkboard';
$pageDescription = 'Classe de ' . $classe['nom'];
$extraCSS = ['modules/classes.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/classes">Classes</a>
    <span class="separator">/</span>
    <span class="current"><?= e($classe['nom']) ?></span>
</nav>

<!-- En-tête -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-chalkboard text-primary me-2"></i><?= e($classe['nom']) ?>
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            <i class="fa-solid fa-calendar-alt me-1"></i> <?= e($classe['annee_libelle']) ?>
            <span class="mx-2">|</span>
            <i class="fa-solid fa-door-open me-1"></i> Salle <?= e($classe['salle'] ?? '—') ?>
            <span class="mx-2">|</span>
            <i class="fa-solid fa-layer-group me-1"></i> Niveau <?= $classe['niveau'] ?>
        </p>
    </div>
    <div class="d-flex gap-sm">
        <?php if (can('classes.modifier')): ?>
            <a href="/gestion_scolaire/classes/modifier/<?= $classeId ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-pen-to-square"></i> Modifier
            </a>
        <?php endif; ?>
        <a href="/gestion_scolaire/classes" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<!-- Statistiques -->
<div class="classe-detail-grid">
    <div class="detail-stat">
        <div class="detail-stat-value"><?= $classe['nb_eleves'] ?></div>
        <div class="detail-stat-label">Élèves inscrits</div>
    </div>
    <div class="detail-stat">
        <div class="detail-stat-value"><?= $classe['capacite_max'] ?></div>
        <div class="detail-stat-label">Capacité maximale</div>
    </div>
    <div class="detail-stat">
        <div class="detail-stat-value"><?= $placesRestantes ?></div>
        <div class="detail-stat-label">Places restantes</div>
    </div>
    <div class="detail-stat">
        <div class="detail-stat-value"><?= $tauxRemplissage ?>%</div>
        <div class="detail-stat-label">Taux de remplissage</div>
    </div>
</div>

<!-- Barre de progression -->
<div style="margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
        <span style="font-size:0.78rem;font-weight:500;color:var(--color-gray-600);">Remplissage</span>
        <span style="font-size:0.78rem;font-weight:600;color:<?= $tauxRemplissage >= 90 ? '#ef4444' : ($tauxRemplissage >= 70 ? '#f59e0b' : '#10b981') ?>;">
            <?= $classe['nb_eleves'] ?> / <?= $classe['capacite_max'] ?>
        </span>
    </div>
    <div style="width:100%;height:10px;background:#e2e8f0;border-radius:10px;overflow:hidden;">
        <div style="width:<?= $tauxRemplissage ?>%;height:100%;background:<?= $tauxRemplissage >= 90 ? '#ef4444' : ($tauxRemplissage >= 70 ? '#f59e0b' : '#10b981') ?>;border-radius:10px;transition:width 0.5s ease;"></div>
    </div>
</div>

<!-- Ligne 1 : Titulaire + Répartition -->
<div class="bottom-grid">
    <!-- Titulaire -->
    <?php if ($classe['titulaire_id']): ?>
        <div class="titulaire-card">
            <div class="titulaire-avatar">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div class="titulaire-info">
                <strong><?= e($classe['titulaire_nom']) ?></strong>
                <span><?= e($classe['titulaire_matricule']) ?></span>
                <?php if ($classe['titulaire_telephone'] || $classe['titulaire_email']): ?>
                    <p>
                        <?php if ($classe['titulaire_telephone']): ?>
                            <i class="fa-solid fa-phone"></i> <?= e($classe['titulaire_telephone']) ?>
                        <?php endif; ?>
                        <?php if ($classe['titulaire_telephone'] && $classe['titulaire_email']): ?>
                            <span class="mx-2">|</span>
                        <?php endif; ?>
                        <?php if ($classe['titulaire_email']): ?>
                            <i class="fa-solid fa-envelope"></i> <?= e($classe['titulaire_email']) ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="titulaire-empty">
            <i class="fa-solid fa-user-slash"></i>
            <div>
                <p><strong>Aucun titulaire assigné</strong></p>
                <p style="font-size:0.75rem;"><?= can('classes.modifier') ? '<a href="/gestion_scolaire/classes/modifier/' . $classeId . '">Assigner un titulaire</a>' : '' ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Répartition filles/garçons -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-venus-mars"></i> Répartition par sexe</h5>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:center;gap:24px;justify-content:center;padding:10px 0;">
                <div style="text-align:center;">
                    <div style="font-size:2rem;font-weight:700;color:#3b82f6;"><?= $nbGarcons ?></div>
                    <div style="font-size:0.78rem;color:var(--color-gray-500);">
                        <i class="fa-solid fa-male"></i> Garçons
                    </div>
                </div>
                <div style="width:1px;height:50px;background:var(--border-color);"></div>
                <div style="text-align:center;">
                    <div style="font-size:2rem;font-weight:700;color:#ec4899;"><?= $nbFilles ?></div>
                    <div style="font-size:0.78rem;color:var(--color-gray-500);">
                        <i class="fa-solid fa-female"></i> Filles
                    </div>
                </div>
            </div>
            <?php if ($classe['nb_eleves'] > 0): ?>
                <div style="width:100%;height:8px;background:#f1f5f9;border-radius:10px;overflow:hidden;display:flex;">
                    <div style="width:<?= $nbGarcons > 0 ? round(($nbGarcons / $classe['nb_eleves']) * 100) : 0 ?>%;height:100%;background:#3b82f6;transition:width 0.5s;"></div>
                    <div style="width:<?= $nbFilles > 0 ? round(($nbFilles / $classe['nb_eleves']) * 100) : 0 ?>%;height:100%;background:#ec4899;transition:width 0.5s;"></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Description -->
<?php if (!empty($classe['description'])): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fa-solid fa-align-left"></i> Description</h5>
        </div>
        <div class="card-body">
            <p style="font-size:0.88rem;color:var(--color-gray-600);margin:0;"><?= nl2br(e($classe['description'])) ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- Liste des élèves -->
<div class="card">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon blue">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="card-header-title">Élèves de la classe</div>
                <div class="card-header-subtitle"><?= count($eleves) ?> élève(s) inscrit(s)</div>
            </div>
        </div>
        <div class="card-header-actions">
            <span class="badge badge-info"><?= $nbGarcons ?> G</span>
            <span class="badge badge-primary ms-1"><?= $nbFilles ?> F</span>
        </div>
    </div>
    <div class="card-body no-padding">
        <?php if (empty($eleves)): ?>
            <div class="empty-state" style="padding:40px;">
                <i class="fa-solid fa-users-slash"></i>
                <p>Aucun élève inscrit dans cette classe</p>
                <?php if (can('eleves.creer')): ?>
                    <a href="/gestion_scolaire/eleves/ajouter" class="btn btn-primary btn-sm mt-3">
                        <i class="fa-solid fa-user-plus"></i> Ajouter un élève
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Matricule</th>
                            <th>Élève</th>
                            <th>Sexe</th>
                            <th>Date de naissance</th>
                            <th>Date inscription</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eleves as $index => $eleve): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><span class="badge badge-primary"><?= e($eleve['matricule']) ?></span></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar-placeholder <?= $eleve['sexe'] === 'M' ? 'blue' : 'purple' ?>">
                                            <?= mb_strtoupper(mb_substr($eleve['prenom'], 0, 1) . mb_substr($eleve['nom'], 0, 1)) ?>
                                        </div>
                                        <div class="user-info">
                                            <span class="user-name"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($eleve['sexe'] === 'M'): ?>
                                        <span style="color:#3b82f6;"><i class="fa-solid fa-mars"></i> M</span>
                                    <?php else: ?>
                                        <span style="color:#ec4899;"><i class="fa-solid fa-venus"></i> F</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($eleve['date_naissance']) ?></td>
                                <td><?= e($eleve['date_inscription']) ?></td>
                                <td><span class="badge badge-success"><?= e($eleve['statut']) ?></span></td>
                                <td>
                                    <a href="/gestion_scolaire/eleves/fiche/<?= $eleve['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-eye"></i> Fiche
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>