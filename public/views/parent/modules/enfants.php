<?php
/**
 * Fichier : public/views/parent/modules/enfants.php
 * Rôle    : Dashboard parent — Vue complète des enfants avec stats, absences, notes
 * Système : École primaire congolaise — Espace Parent
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('eleves.consulter');

global $db;

// Récupérer le parent connecté
$userId = $_SESSION['utilisateur_id'] ?? 0;
$parentId = (int) $db->fetchColumn(
    "SELECT pt.id FROM parents_tuteurs pt WHERE pt.utilisateur_id = :uid LIMIT 1",
    ['uid' => $userId]
);

if ($parentId <= 0) {
    setFlash('warning', 'Aucun profil parent trouvé. Contactez l\'administration.');
    redirect('/gestion_scolaire/dashboard');
}

// Récupérer les infos du parent
$parent = $db->fetchOne(
    "SELECT pt.*, u.email AS compte_email, u.login
     FROM parents_tuteurs pt
     INNER JOIN utilisateurs u ON pt.utilisateur_id = u.id
     WHERE pt.id = :id",
    ['id' => $parentId]
);

// Récupérer les enfants du parent
$enfants = $db->fetchAll(
    "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe, e.date_naissance, e.photo,
            DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
            TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age,
            c.nom AS classe_nom, c.niveau AS classe_niveau, c.id AS classe_id,
            CONCAT(ens.prenom, ' ', ens.nom) AS titulaire_nom,
            ep.est_tuteur_principal
     FROM eleve_parent ep
     INNER JOIN eleves e ON ep.eleve_id = e.id AND e.est_actif = 1
     LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
     LEFT JOIN classes c ON i.classe_id = c.id
     LEFT JOIN enseignants ens ON c.id = ens.classe_id AND ens.est_actif = 1
     WHERE ep.parent_id = :pid
     ORDER BY ep.est_tuteur_principal DESC, e.nom",
    ['pid' => $parentId]
);

if (empty($enfants)) {
    setFlash('info', 'Aucun enfant n\'est actuellement lié à votre compte.');
    redirect('/gestion_scolaire/dashboard');
}

// Sélectionner l'enfant actif (par défaut le premier)
$selectedEnfantId = (int) ($_GET['enfant_id'] ?? $enfants[0]['id']);
$selectedEnfant = null;
foreach ($enfants as $enf) {
    if ($enf['id'] === $selectedEnfantId) {
        $selectedEnfant = $enf;
        break;
    }
}
if (!$selectedEnfant) $selectedEnfant = $enfants[0];

// Récupérer les absences récentes de l'enfant sélectionné
$absences = $db->fetchAll(
    "SELECT a.date_absence, a.type_absence, a.est_justifiee, a.motif,
            DATE_FORMAT(a.date_absence, '%d/%m/%Y') AS date_format
     FROM absences a
     WHERE a.eleve_id = :eid AND a.type_absence = 'Absence'
     ORDER BY a.date_absence DESC
     LIMIT 10",
    ['eid' => $selectedEnfant['id']]
);

// Récupérer les retards récents
$retards = $db->fetchAll(
    "SELECT r.date_retard, r.heure_arrivee, r.heure_prevue, r.duree_retard, r.est_justifie, r.motif,
            DATE_FORMAT(r.date_retard, '%d/%m/%Y') AS date_format,
            TIME_FORMAT(r.heure_arrivee, '%H:%i') AS heure_arrivee_format
     FROM retards r
     WHERE r.eleve_id = :eid
     ORDER BY r.date_retard DESC
     LIMIT 5",
    ['eid' => $selectedEnfant['id']]
);

// Récupérer les notes récentes
$notes = $db->fetchAll(
    "SELECT n.valeur, ev.type_evaluation, DATE_FORMAT(ev.date_evaluation, '%d/%m/%Y') AS date_eval,
            m.libelle AS matiere, t.libelle AS trimestre
     FROM notes n
     INNER JOIN evaluations ev ON n.evaluation_id = ev.id
     INNER JOIN matieres m ON ev.groupe_id = m.id
     INNER JOIN trimestres t ON ev.trimestre_id = t.id
     WHERE n.eleve_id = :eid AND n.absence = 0
     ORDER BY ev.date_evaluation DESC
     LIMIT 15",
    ['eid' => $selectedEnfant['id']]
);

// Récupérer les paiements
$paiements = $db->fetchAll(
    "SELECT p.montant_total, p.montant_paye, p.statut, p.mode_paiement,
            DATE_FORMAT(p.date_paiement, '%d/%m/%Y') AS date_format,
            f.libelle AS frais_libelle
     FROM paiements p
     INNER JOIN frais_scolaires f ON p.frais_scolaire_id = f.id
     WHERE p.eleve_id = :eid
     ORDER BY p.date_paiement DESC
     LIMIT 5",
    ['eid' => $selectedEnfant['id']]
);

// Récupérer la dernière moyenne
$derniereMoyenne = $db->fetchColumn(
    "SELECT moyenne_generale FROM resultats_trimestriels 
     WHERE eleve_id = :eid ORDER BY id DESC LIMIT 1",
    ['eid' => $selectedEnfant['id']]
);

// Statistiques
$totalAbsences = count($absences);
$absencesNonJustifiees = count(array_filter($absences, fn($a) => !$a['est_justifiee']));
$totalRetards = count($retards);
$totalPaiements = count($paiements);
$totalImpayes = count(array_filter($paiements, fn($p) => $p['statut'] !== 'Payé'));

$pageTitle = 'Mes enfants — ' . e($parent['prenom'] . ' ' . $parent['nom']);
$pageIcon  = 'fa-users';
$pageDescription = count($enfants) . ' enfant(s) suivi(s)';
$extraCSS = ['modules/eleves.css', 'modules/fiche_eleve.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Mes enfants</span>
</nav>

<!-- ============================================================
     EN-TÊTE
     ============================================================ -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-users"></i> Mes enfants</h4>
        <p>
            <i class="fa-solid fa-user"></i> <?= e($parent['prenom'] . ' ' . $parent['nom']) ?> — 
            <i class="fa-solid fa-children"></i> <?= count($enfants) ?> enfant(s) suivi(s)
        </p>
    </div>
    <div class="resultats-header-right">
        <?php if (count($enfants) > 1): ?>
        <select id="enfantSelector" class="form-control" style="width:250px;" onchange="changerEnfant(this.value)">
            <?php foreach ($enfants as $enf): ?>
                <option value="<?= $enf['id'] ?>" <?= $selectedEnfantId === (int) $enf['id'] ? 'selected' : '' ?>>
                    <?= e($enf['prenom'] . ' ' . $enf['nom']) ?> — <?= e($enf['classe_nom'] ?? 'Non inscrit') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     CARTE ENFANT SÉLECTIONNÉ
     ============================================================ -->
<div class="profile-card mb-4">
    <div class="profile-header">
        <div class="profile-avatar-placeholder-large" style="background:linear-gradient(135deg,<?= $selectedEnfant['sexe'] === 'M' ? '#2c3e90,#4a6cf7' : '#8b5cf6,#a78bfa' ?>);">
            <?= mb_strtoupper(mb_substr($selectedEnfant['prenom'], 0, 1) . mb_substr($selectedEnfant['nom'], 0, 1)) ?>
        </div>
        <div class="profile-header-info">
            <h3><?= e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']) ?></h3>
            <p>
                <span class="badge badge-primary"><?= e($selectedEnfant['matricule']) ?></span>
                <span class="badge badge-info ms-2"><?= $selectedEnfant['sexe'] === 'M' ? 'Garçon' : 'Fille' ?></span>
                <span class="ms-2"><?= e($selectedEnfant['date_naissance_format']) ?> — <?= $selectedEnfant['age'] ?> ans</span>
            </p>
            <p>
                <i class="fa-solid fa-chalkboard"></i> 
                <strong><?= e($selectedEnfant['classe_nom'] ?? 'Non inscrit') ?></strong>
                <?php if ($selectedEnfant['classe_niveau']): ?>
                    (Niveau <?= $selectedEnfant['classe_niveau'] ?>)
                <?php endif; ?>
                <?php if ($selectedEnfant['titulaire_nom']): ?>
                    <span class="ms-2">|</span>
                    <i class="fa-solid fa-chalkboard-user"></i> <?= e($selectedEnfant['titulaire_nom']) ?>
                <?php endif; ?>
            </p>
            <?php if ($selectedEnfant['est_tuteur_principal']): ?>
                <span class="badge badge-warning"><i class="fa-solid fa-star"></i> Tuteur principal</span>
            <?php endif; ?>
        </div>
        <div class="profile-header-actions">
            <a href="/gestion_scolaire/parent/bulletins?enfant_id=<?= $selectedEnfant['id'] ?>" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-file-invoice"></i> Bulletin
            </a>
            <a href="/gestion_scolaire/parent/absences?enfant_id=<?= $selectedEnfant['id'] ?>" class="btn btn-outline-primary btn-sm" style="color:#fff;border-color:rgba(255,255,255,0.5);">
                <i class="fa-solid fa-calendar-xmark"></i> Absences
            </a>
        </div>
    </div>
</div>

<!-- ============================================================
     STATISTIQUES DE L'ENFANT
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-chart-line"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $derniereMoyenne ? number_format($derniereMoyenne, 1) . '/20' : '—' ?></div>
            <div class="stat-label">Dernière moyenne</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-calendar-xmark"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $totalAbsences ?></div>
            <div class="stat-label">Absences</div>
            <?php if ($absencesNonJustifiees > 0): ?>
                <span class="stat-change down"><?= $absencesNonJustifiees ?> non justifiée(s)</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $totalRetards ?></div>
            <div class="stat-label">Retards</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $totalImpayes > 0 ? '$ ' . number_format(array_sum(array_column(array_filter($paiements, fn($p) => $p['statut'] !== 'Payé'), 'montant_total')) - array_sum(array_column(array_filter($paiements, fn($p) => $p['statut'] !== 'Payé'), 'montant_paye')), 0, ',', ' ') : '0' ?></div>
            <div class="stat-label">Reste à payer</div>
            <?php if ($totalImpayes > 0): ?>
                <span class="stat-change down"><?= $totalImpayes ?> impayé(s)</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     GRILLE : NOTES + ABSENCES
     ============================================================ -->
<div class="bottom-grid">
    <!-- Notes récentes -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon blue"><i class="fa-solid fa-pen-to-square"></i></div>
                <div>
                    <div class="card-header-title">Dernières notes</div>
                    <div class="card-header-subtitle">15 dernières évaluations</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Matière</th><th>Type</th><th>Trimestre</th><th>Note</th></tr></thead>
                    <tbody>
                        <?php if (empty($notes)): ?>
                            <tr><td colspan="5"><div class="empty-state"><i class="fa-solid fa-pen-to-square"></i><p>Aucune note disponible</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($notes as $note): ?>
                                <tr>
                                    <td><?= e($note['date_eval']) ?></td>
                                    <td><?= e($note['matiere']) ?></td>
                                    <td><span class="badge badge-primary"><?= e($note['type_evaluation']) ?></span></td>
                                    <td><?= e($note['trimestre']) ?></td>
                                    <td><strong><?= number_format($note['valeur'], 1) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Absences récentes -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon red"><i class="fa-solid fa-calendar-xmark"></i></div>
                <div>
                    <div class="card-header-title">Absences récentes</div>
                    <div class="card-header-subtitle">10 dernières absences</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Justifiée</th><th>Motif</th></tr></thead>
                    <tbody>
                        <?php if (empty($absences)): ?>
                            <tr><td colspan="3"><div class="empty-state"><i class="fa-solid fa-check-circle" style="color:#10b981;"></i><p>Aucune absence — Excellent !</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($absences as $abs): ?>
                                <tr>
                                    <td><strong><?= e($abs['date_format']) ?></strong></td>
                                    <td>
                                        <?php if ($abs['est_justifiee']): ?>
                                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Justifiée</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><i class="fa-solid fa-xmark"></i> Non justifiée</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($abs['motif'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     RETARDS + PAIEMENTS
     ============================================================ -->
<div class="bottom-grid mt-4">
    <!-- Retards -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon orange"><i class="fa-solid fa-clock"></i></div>
                <div>
                    <div class="card-header-title">Retards</div>
                    <div class="card-header-subtitle">5 derniers retards</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Arrivée</th><th>Durée</th><th>Justifié</th></tr></thead>
                    <tbody>
                        <?php if (empty($retards)): ?>
                            <tr><td colspan="4"><div class="empty-state"><i class="fa-solid fa-check-circle" style="color:#10b981;"></i><p>Aucun retard — Excellent !</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($retards as $ret): ?>
                                <tr>
                                    <td><strong><?= e($ret['date_format']) ?></strong></td>
                                    <td><?= e($ret['heure_arrivee_format']) ?></td>
                                    <td><span class="badge badge-warning"><?= $ret['duree_retard'] ?> min</span></td>
                                    <td>
                                        <?php if ($ret['est_justifie']): ?>
                                            <span class="badge badge-success">Oui</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Non</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Paiements -->
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon green"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div>
                    <div class="card-header-title">Paiements</div>
                    <div class="card-header-subtitle">Derniers paiements</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Frais</th><th>Montant</th><th>Statut</th></tr></thead>
                    <tbody>
                        <?php if (empty($paiements)): ?>
                            <tr><td colspan="4"><div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Aucun paiement enregistré</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($paiements as $p): ?>
                                <tr>
                                    <td><?= e($p['date_format']) ?></td>
                                    <td><?= e($p['frais_libelle']) ?></td>
                                    <td>
                                        <strong>$ <?= number_format($p['montant_paye'], 2, ',', ' ') ?></strong>
                                        <?php if ($p['montant_total'] > $p['montant_paye']): ?>
                                            <small class="text-danger">/ $ <?= number_format($p['montant_total'], 2, ',', ' ') ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $p['statut'] === 'Payé' ? 'badge-success' : ($p['statut'] === 'Partiel' ? 'badge-warning' : 'badge-danger') ?>">
                                            <?= e($p['statut']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ACTIONS RAPIDES
     ============================================================ -->
<div class="d-flex gap-sm justify-content-center mt-4">
    <a href="/gestion_scolaire/parent/bulletins?enfant_id=<?= $selectedEnfant['id'] ?>" class="btn btn-primary">
        <i class="fa-solid fa-file-invoice"></i> Voir le bulletin
    </a>
    <a href="/gestion_scolaire/parent/absences?enfant_id=<?= $selectedEnfant['id'] ?>" class="btn btn-outline-primary">
        <i class="fa-solid fa-calendar-xmark"></i> Voir toutes les absences
    </a>
    <a href="/gestion_scolaire/parent/paiements?enfant_id=<?= $selectedEnfant['id'] ?>" class="btn btn-outline-primary">
        <i class="fa-solid fa-money-bill-wave"></i> Voir les paiements
    </a>
</div>

<script>
function changerEnfant(enfantId) {
    var url = new URL(window.location.href);
    url.searchParams.set('enfant_id', enfantId);
    window.location.href = url.toString();
}
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';