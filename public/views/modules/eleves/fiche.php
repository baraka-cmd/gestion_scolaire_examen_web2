<?php
/**
 * Fichier : public/views/modules/eleves/fiche.php
 * Rôle    : Fiche détaillée d'un élève
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('eleves.consulter');

global $db;

// Récupérer l'ID
$eleveId = (int) ($_GET['id'] ?? 0);
if ($eleveId <= 0) {
    setFlash('error', 'ID élève invalide.');
    redirect('/gestion_scolaire/eleves');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EleveController.php';
$eleveController = new EleveController();

// Récupérer l'élève
$eleve = $eleveController->getEleveDetail($eleveId);
if (!$eleve) {
    setFlash('error', 'Élève introuvable.');
    redirect('/gestion_scolaire/eleves');
}

// Récupérer les parents
$parents = $eleveController->getParentsEleve($eleveId);

// Récupérer l'historique des inscriptions
$historique = $eleveController->getHistoriqueInscriptions($eleveId);

// Récupérer les dernières notes
$notes = $db->fetchAll(
    "SELECT n.valeur, ev.type_evaluation, DATE_FORMAT(ev.date_evaluation, '%d/%m/%Y') AS date_eval, 
            m.libelle AS matiere, t.libelle AS trimestre
     FROM notes n
     INNER JOIN evaluations ev ON n.evaluation_id = ev.id
     INNER JOIN matieres m ON ev.matiere_id = m.id
     INNER JOIN trimestres t ON ev.trimestre_id = t.id
     WHERE n.eleve_id = :eid
     ORDER BY ev.date_evaluation DESC
     LIMIT 15",
    ['eid' => $eleveId]
);

// Récupérer les absences récentes
$absences = $db->fetchAll(
    "SELECT DATE_FORMAT(date_absence, '%d/%m/%Y') AS date_abs, type_absence, est_justifiee, motif
     FROM absences WHERE eleve_id = :eid ORDER BY date_absence DESC LIMIT 10",
    ['eid' => $eleveId]
);

// Récupérer les paiements
$paiements = $db->fetchAll(
    "SELECT p.montant_total, p.montant_paye, p.statut, DATE_FORMAT(p.date_paiement, '%d/%m/%Y') AS date_pmt,
            p.mode_paiement, f.libelle AS frais
     FROM paiements p
     INNER JOIN frais_scolaires f ON p.frais_scolaire_id = f.id
     WHERE p.eleve_id = :eid ORDER BY p.date_paiement DESC",
    ['eid' => $eleveId]
);

$pageTitle = $eleve['prenom'] . ' ' . $eleve['nom'] . ' — Fiche élève';
$pageIcon  = 'fa-graduation-cap';
$pageDescription = 'Fiche détaillée de l\'élève';
$extraCSS = ['modules/fiche_eleve.css'];
$extraJS  = ['modules/fiche_eleve.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/eleves">Élèves</a>
    <span class="separator">/</span>
    <span class="current"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></span>
</nav>

<!-- ============================================================
     HERO HEADER
     ============================================================ -->
<div class="eleve-profile-hero">
    <div class="profile-avatar-container">
        <div class="profile-avatar-placeholder">
            <?= mb_strtoupper(mb_substr($eleve['prenom'], 0, 1) . mb_substr($eleve['nom'], 0, 1)) ?>
        </div>
        <div class="profile-status-dot <?= $eleve['est_actif'] ? '' : 'inactive' ?>"></div>
    </div>
    <div class="profile-info">
        <h3 class="profile-name"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></h3>
        <div class="profile-meta">
            <span class="profile-meta-item">
                <i class="fa-solid fa-id-card"></i> <?= e($eleve['matricule']) ?>
            </span>
            <span class="profile-meta-item">
                <i class="fa-solid fa-<?= $eleve['sexe'] === 'M' ? 'mars' : 'venus' ?>"></i>
                <?= $eleve['sexe'] === 'M' ? 'Garçon' : 'Fille' ?>
            </span>
            <span class="profile-meta-item">
                <i class="fa-solid fa-cake-candles"></i> <?= $eleve['age'] ?> an(s)
            </span>
        </div>
        <div class="profile-badges">
            <?php if ($eleve['classe_nom']): ?>
                <span class="profile-badge success">
                    <i class="fa-solid fa-school"></i> <?= e($eleve['classe_nom']) ?>
                </span>
            <?php endif; ?>
            <span class="profile-badge <?= $eleve['est_actif'] ? 'success' : 'danger' ?>">
                <i class="fa-solid fa-<?= $eleve['est_actif'] ? 'check-circle' : 'times-circle' ?>"></i>
                <?= $eleve['est_actif'] ? 'Actif' : 'Inactif' ?>
            </span>
        </div>
    </div>
    <div class="profile-actions">
        <?php if (can('eleves.modifier')): ?>
            <a href="/gestion_scolaire/eleves/modifier/<?= $eleveId ?>" class="btn btn-sm btn-outline-primary" style="color:#fff;border-color:rgba(255,255,255,0.5);">
                <i class="fa-solid fa-pen-to-square"></i> Modifier
            </a>
        <?php endif; ?>
        <button type="button" class="btn-print-fiche" id="btnPrintFiche">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
        <a href="/gestion_scolaire/eleves" class="btn btn-sm btn-outline-primary" style="color:#fff;border-color:rgba(255,255,255,0.5);">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<!-- ============================================================
     ONGLETS
     ============================================================ -->
<div class="profile-tabs">
    <button class="profile-tab active" data-tab="infos">
        <i class="fa-solid fa-id-card"></i> <span>Infos</span>
    </button>
    <button class="profile-tab" data-tab="notes">
        <i class="fa-solid fa-chart-bar"></i> <span>Notes</span>
        <span class="tab-count"><?= count($notes) ?></span>
    </button>
    <button class="profile-tab" data-tab="absences">
        <i class="fa-solid fa-calendar-xmark"></i> <span>Absences</span>
        <span class="tab-count"><?= count($absences) ?></span>
    </button>
    <button class="profile-tab" data-tab="paiements">
        <i class="fa-solid fa-money-bill-wave"></i> <span>Paiements</span>
        <span class="tab-count"><?= count($paiements) ?></span>
    </button>
    <?php if (count($historique) > 1): ?>
    <button class="profile-tab" data-tab="historique">
        <i class="fa-solid fa-clock-rotate-left"></i> <span>Historique</span>
    </button>
    <?php endif; ?>
</div>

<!-- ============================================================
     PANNEAU : INFOS
     ============================================================ -->
<div class="profile-panel active" id="panel-infos">
    <div class="info-grid">
        <!-- Identité -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon blue"><i class="fa-solid fa-id-card"></i></div>
                <span class="info-card-title">Identité</span>
            </div>
            <div class="info-field"><span class="info-field-label">Nom complet</span><span class="info-field-value"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></span></div>
            <div class="info-field"><span class="info-field-label">Date de naissance</span><span class="info-field-value"><?= e($eleve['date_naissance_format']) ?> (<?= $eleve['age'] ?> ans)</span></div>
            <div class="info-field"><span class="info-field-label">Lieu de naissance</span><span class="info-field-value <?= empty($eleve['lieu_naissance']) ? 'empty' : '' ?>"><?= e($eleve['lieu_naissance'] ?: 'Non renseigné') ?></span></div>
            <div class="info-field"><span class="info-field-label">Sexe</span><span class="info-field-value"><?= $eleve['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></span></div>
            <div class="info-field"><span class="info-field-label">Groupe sanguin</span><span class="info-field-value <?= empty($eleve['groupe_sanguin']) ? 'empty' : '' ?>"><?= e($eleve['groupe_sanguin'] ?: 'Non renseigné') ?></span></div>
        </div>

        <!-- Contact -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon green"><i class="fa-solid fa-address-book"></i></div>
                <span class="info-card-title">Contact</span>
            </div>
            <div class="info-field"><span class="info-field-label">Téléphone</span><span class="info-field-value <?= empty($eleve['telephone']) ? 'empty' : '' ?>"><?= e($eleve['telephone'] ?: 'Non renseigné') ?></span></div>
            <div class="info-field"><span class="info-field-label">Email</span><span class="info-field-value <?= empty($eleve['email']) ? 'empty' : '' ?>"><?= e($eleve['email'] ?: 'Non renseigné') ?></span></div>
            <div class="info-field"><span class="info-field-label">Adresse</span><span class="info-field-value <?= empty($eleve['adresse']) ? 'empty' : '' ?>"><?= e($eleve['adresse'] ?: 'Non renseignée') ?></span></div>
        </div>

        <!-- Scolarité -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon orange"><i class="fa-solid fa-school"></i></div>
                <span class="info-card-title">Scolarité</span>
            </div>
            <div class="info-field"><span class="info-field-label">Classe</span><span class="info-field-value"><?= $eleve['classe_nom'] ? '<strong>' . e($eleve['classe_nom']) . '</strong> (Niv. ' . $eleve['classe_niveau'] . ')' : '<span class="badge badge-danger">Non inscrit</span>' ?></span></div>
            <div class="info-field"><span class="info-field-label">Titulaire</span><span class="info-field-value <?= empty($eleve['titulaire_nom']) ? 'empty' : '' ?>"><?= e($eleve['titulaire_nom'] ?: 'Aucun') ?></span></div>
            <div class="info-field"><span class="info-field-label">Date d'inscription</span><span class="info-field-value"><?= e($eleve['date_inscription_classe'] ? formatDate($eleve['date_inscription_classe']) : '—') ?></span></div>
            <div class="info-field"><span class="info-field-label">Statut</span><span class="info-field-value"><?= badgeStatut($eleve['statut_inscription'] ?? 'Actif') ?></span></div>
        </div>

        <!-- Santé -->
        <div class="info-card">
            <div class="info-card-header">
                <div class="info-card-icon red"><i class="fa-solid fa-notes-medical"></i></div>
                <span class="info-card-title">Santé</span>
            </div>
            <div class="info-field"><span class="info-field-label">Allergies</span><span class="info-field-value <?= empty($eleve['allergies']) ? 'empty' : '' ?>"><?= e($eleve['allergies'] ?: 'Aucune') ?></span></div>
            <div class="info-field"><span class="info-field-label">Maladies / Conditions</span><span class="info-field-value <?= empty($eleve['maladies']) ? 'empty' : '' ?>"><?= e($eleve['maladies'] ?: 'Aucune') ?></span></div>
        </div>
    </div>

    <!-- Parents -->
    <div class="data-table-card mt-4">
        <div class="data-table-header">
            <span class="data-table-title"><i class="fa-solid fa-people-roof"></i> Parents / Tuteurs</span>
            <span class="data-table-count"><?= count($parents) ?> contact(s)</span>
        </div>
        <div style="padding:20px;">
            <?php if (empty($parents)): ?>
                <div class="empty-state"><i class="fa-solid fa-user-slash"></i><p>Aucun parent enregistré</p></div>
            <?php else: ?>
                <div class="parents-container">
                    <?php foreach ($parents as $parent): ?>
                        <div class="parent-detail-card <?= $parent['est_tuteur_principal'] ? 'principal' : 'secondaire' ?>">
                            <div class="parent-card-top">
                                <span class="parent-card-name"><?= e($parent['prenom'] . ' ' . $parent['nom']) ?></span>
                                <span class="parent-card-role <?= $parent['est_tuteur_principal'] ? 'principal' : 'secondaire' ?>">
                                    <?= $parent['est_tuteur_principal'] ? 'Principal' : 'Secondaire' ?>
                                </span>
                            </div>
                            <div class="parent-card-details">
                                <div class="parent-detail-row"><i class="fa-solid fa-link"></i> <span>Lien : <strong><?= e($parent['lien']) ?></strong></span></div>
                                <div class="parent-detail-row"><i class="fa-solid fa-phone"></i> <span><?= e($parent['telephone_principal']) ?></span></div>
                                <?php if (!empty($parent['email'])): ?>
                                    <div class="parent-detail-row"><i class="fa-solid fa-envelope"></i> <span><?= e($parent['email']) ?></span></div>
                                <?php endif; ?>
                                <?php if (!empty($parent['profession'])): ?>
                                    <div class="parent-detail-row"><i class="fa-solid fa-briefcase"></i> <span><?= e($parent['profession']) ?></span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     PANNEAU : NOTES
     ============================================================ -->
<div class="profile-panel" id="panel-notes">
    <div class="data-table-card">
        <div class="data-table-header">
            <span class="data-table-title"><i class="fa-solid fa-chart-bar"></i> Dernières notes</span>
            <span class="data-table-count"><?= count($notes) ?> note(s)</span>
        </div>
        <?php if (empty($notes)): ?>
            <div class="empty-state" style="padding:30px;"><i class="fa-solid fa-chart-simple"></i><p>Aucune note enregistrée</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Matière</th><th>Type</th><th>Note</th><th>Date</th><th>Trimestre</th></tr></thead>
                    <tbody>
                        <?php foreach ($notes as $note): ?>
                            <tr>
                                <td><?= e($note['matiere']) ?></td>
                                <td><span class="badge badge-info"><?= e($note['type_evaluation']) ?></span></td>
                                <td>
                                    <span class="note-value <?= $note['valeur'] >= 16 ? 'excellent' : ($note['valeur'] >= 12 ? 'good' : ($note['valeur'] >= 10 ? 'average' : 'low')) ?>">
                                        <?= number_format($note['valeur'], 1) ?>/20
                                    </span>
                                </td>
                                <td><?= e($note['date_eval']) ?></td>
                                <td><?= e($note['trimestre']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     PANNEAU : ABSENCES
     ============================================================ -->
<div class="profile-panel" id="panel-absences">
    <div class="data-table-card">
        <div class="data-table-header">
            <span class="data-table-title"><i class="fa-solid fa-calendar-xmark"></i> Absences récentes</span>
            <span class="data-table-count"><?= count($absences) ?> absence(s)</span>
        </div>
        <?php if (empty($absences)): ?>
            <div class="empty-state" style="padding:30px;"><i class="fa-solid fa-check-circle"></i><p>Aucune absence</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Type</th><th>Justifiée</th><th>Motif</th></tr></thead>
                    <tbody>
                        <?php foreach ($absences as $abs): ?>
                            <tr>
                                <td><?= e($abs['date_abs']) ?></td>
                                <td><?= e($abs['type_absence']) ?></td>
                                <td><?= $abs['est_justifiee'] ? '<span class="badge badge-success">Oui</span>' : '<span class="badge badge-danger">Non</span>' ?></td>
                                <td><?= e($abs['motif'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     PANNEAU : PAIEMENTS
     ============================================================ -->
<div class="profile-panel" id="panel-paiements">
    <div class="data-table-card">
        <div class="data-table-header">
            <span class="data-table-title"><i class="fa-solid fa-money-bill-wave"></i> Paiements</span>
            <span class="data-table-count"><?= count($paiements) ?> transaction(s)</span>
        </div>
        <?php if (empty($paiements)): ?>
            <div class="empty-state" style="padding:30px;"><i class="fa-solid fa-inbox"></i><p>Aucun paiement enregistré</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Frais</th><th>Montant</th><th>Payé</th><th>Statut</th><th>Date</th><th>Mode</th></tr></thead>
                    <tbody>
                        <?php foreach ($paiements as $p): ?>
                            <tr>
                                <td><?= e($p['frais']) ?></td>
                                <td>$ <?= number_format($p['montant_total'], 2) ?></td>
                                <td>$ <?= number_format($p['montant_paye'], 2) ?></td>
                                <td><?= badgeStatut($p['statut']) ?></td>
                                <td><?= e($p['date_pmt']) ?></td>
                                <td><?= e($p['mode_paiement']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     PANNEAU : HISTORIQUE
     ============================================================ -->
<?php if (count($historique) > 1): ?>
<div class="profile-panel" id="panel-historique">
    <div class="data-table-card">
        <div class="data-table-header">
            <span class="data-table-title"><i class="fa-solid fa-clock-rotate-left"></i> Historique des inscriptions</span>
        </div>
        <div style="padding:20px;">
            <div class="timeline">
                <?php foreach ($historique as $index => $h): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot <?= $index === 0 ? 'current' : 'past' ?>"></div>
                        <div class="timeline-content">
                            <div class="timeline-title"><?= e($h['classe_nom']) ?> — <?= e($h['annee_libelle']) ?></div>
                            <div class="timeline-date">
                                <i class="fa-regular fa-calendar"></i> <?= e(formatDate($h['date_inscription'])) ?>
                            </div>
                            <div class="timeline-badge"><?= badgeStatut($h['statut']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>