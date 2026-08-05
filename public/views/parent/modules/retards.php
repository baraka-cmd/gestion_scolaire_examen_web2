<?php
/**
 * Fichier : public/views/parent/modules/retards.php
 * Rôle    : Consultation des retards — Vue Parent
 * Système : École primaire congolaise — Espace Parent
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('retards.consulter');

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

// Récupérer les enfants du parent
$enfants = $db->fetchAll(
    "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
            c.nom AS classe_nom, c.niveau AS classe_niveau
     FROM eleve_parent ep
     INNER JOIN eleves e ON ep.eleve_id = e.id AND e.est_actif = 1
     LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
     LEFT JOIN classes c ON i.classe_id = c.id
     WHERE ep.parent_id = :pid
     ORDER BY ep.est_tuteur_principal DESC, e.nom",
    ['pid' => $parentId]
);

if (empty($enfants)) {
    setFlash('info', 'Aucun enfant n\'est actuellement lié à votre compte.');
    redirect('/gestion_scolaire/parent/enfants');
}

// Sélectionner l'enfant
$eleveId = (int) ($_GET['enfant_id'] ?? $enfants[0]['id']);
$enfantAutorise = false;
$selectedEnfant = null;
foreach ($enfants as $enf) {
    if ($enf['id'] === $eleveId) {
        $enfantAutorise = true;
        $selectedEnfant = $enf;
        break;
    }
}
if (!$enfantAutorise) $selectedEnfant = $enfants[0];

// Récupérer tous les retards
$retards = $db->fetchAll(
    "SELECT r.id, r.date_retard, r.heure_arrivee, r.heure_prevue, r.duree_retard, r.est_justifie, r.motif,
            DATE_FORMAT(r.date_retard, '%d/%m/%Y') AS date_format,
            DAYNAME(r.date_retard) AS jour_semaine,
            TIME_FORMAT(r.heure_arrivee, '%H:%i') AS heure_arrivee_format,
            TIME_FORMAT(r.heure_prevue, '%H:%i') AS heure_prevue_format
     FROM retards r
     WHERE r.eleve_id = :eid
     ORDER BY r.date_retard DESC",
    ['eid' => $selectedEnfant['id']]
);

// Statistiques
$totalRetards = count($retards);
$retardsJustifies = count(array_filter($retards, fn($r) => $r['est_justifie']));
$retardsNonJustifies = $totalRetards - $retardsJustifies;

// Durée totale et moyenne
$dureeTotale = array_sum(array_column($retards, 'duree_retard'));
$dureeMoyenne = $totalRetards > 0 ? round($dureeTotale / $totalRetards, 1) : 0;

// Retard le plus long
$retardMax = !empty($retards) ? max(array_column($retards, 'duree_retard')) : 0;

// Retards par mois (6 derniers mois)
$retardsParMois = [];
for ($i = 5; $i >= 0; $i--) {
    $mois = date('Y-m', strtotime("-{$i} months"));
    $count = count(array_filter($retards, fn($r) => substr($r['date_retard'], 0, 7) === $mois));
    $dureeMois = array_sum(array_column(
        array_filter($retards, fn($r) => substr($r['date_retard'], 0, 7) === $mois),
        'duree_retard'
    ));
    $retardsParMois[] = [
        'mois' => $mois,
        'label' => moisEnFrancais((int) substr($mois, 5, 2)) . ' ' . substr($mois, 0, 4),
        'count' => $count,
        'duree' => $dureeMois
    ];
}

// Répartition par jour de la semaine
$joursSemaine = ['Lundi' => 0, 'Mardi' => 0, 'Mercredi' => 0, 'Jeudi' => 0, 'Vendredi' => 0, 'Samedi' => 0];
foreach ($retards as $ret) {
    $jour = match($ret['jour_semaine'] ?? '') {
        'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche', default => ''
    };
    if ($jour && isset($joursSemaine[$jour])) {
        $joursSemaine[$jour]++;
    }
}

$pageTitle = 'Retards — ' . e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']);
$pageIcon  = 'fa-clock';
$pageDescription = 'Suivi des retards';
$extraCSS = ['modules/retards.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/parent/enfants">Mes enfants</a>
    <span class="separator">/</span>
    <span class="current">Retards — <?= e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']) ?></span>
</nav>

<!-- EN-TÊTE -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-clock"></i> Retards — <?= e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']) ?></h4>
        <p>
            <i class="fa-solid fa-graduation-cap"></i> <?= e($selectedEnfant['matricule']) ?> — 
            <i class="fa-solid fa-chalkboard"></i> <?= e($selectedEnfant['classe_nom'] ?? 'Non inscrit') ?>
        </p>
    </div>
    <div class="resultats-header-right">
        <?php if (count($enfants) > 1): ?>
        <select id="enfantSelector" class="form-control" style="width:250px;" onchange="changerEnfant(this.value)">
            <?php foreach ($enfants as $enf): ?>
                <option value="<?= $enf['id'] ?>" <?= $selectedEnfant['id'] === (int) $enf['id'] ? 'selected' : '' ?>>
                    <?= e($enf['prenom'] . ' ' . $enf['nom']) ?> — <?= e($enf['classe_nom'] ?? 'Non inscrit') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <a href="/gestion_scolaire/parent/enfants" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<!-- ============================================================
     STATISTIQUES
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $totalRetards ?></div>
            <div class="stat-label">Total retards</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $retardsJustifies ?></div>
            <div class="stat-label">Retards justifiés</div>
            <?php if ($totalRetards > 0): ?>
                <span class="stat-change up"><?= round(($retardsJustifies / $totalRetards) * 100) ?>%</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $retardsNonJustifies ?></div>
            <div class="stat-label">Non justifiés</div>
            <?php if ($retardsNonJustifies > 0): ?>
                <span class="stat-change down">À justifier</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-stopwatch"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $dureeMoyenne ?> min</div>
            <div class="stat-label">Durée moyenne</div>
            <span class="stat-change neutral">Max : <?= $retardMax ?> min</span>
        </div>
    </div>
</div>

<!-- ============================================================
     RÉSUMÉ VISUEL
     ============================================================ -->
<div class="bottom-grid mb-4">
    <!-- Évolution mensuelle -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-chart-bar"></i> Évolution mensuelle (6 derniers mois)</h5>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:flex-end;gap:12px;height:180px;padding:10px 0;">
                <?php 
                $maxCount = max(array_column($retardsParMois, 'count')) ?: 1;
                foreach ($retardsParMois as $moisData): 
                    $height = ($moisData['count'] / $maxCount) * 100;
                    $barColor = $moisData['count'] > 3 ? '#ef4444' : ($moisData['count'] > 1 ? '#f59e0b' : '#10b981');
                ?>
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:6px;">
                        <span style="font-size:0.8rem;font-weight:700;color:<?= $barColor ?>;"><?= $moisData['count'] ?></span>
                        <div style="width:100%;height:<?= $height ?>%;background:<?= $barColor ?>;border-radius:8px 8px 0 0;min-height:4px;transition:height 0.5s ease;"></div>
                        <span style="font-size:0.6rem;color:#64748b;text-align:center;line-height:1.2;"><?= $moisData['label'] ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($dureeTotale > 0): ?>
                <div style="text-align:center;margin-top:12px;font-size:0.78rem;color:#64748b;">
                    <i class="fa-solid fa-clock"></i> Durée totale des retards : <strong><?= $dureeTotale ?> minutes</strong> 
                    (<?= round($dureeTotale / 60, 1) ?> heure(s))
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Répartition par jour -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-calendar-week"></i> Retards par jour de la semaine</h5>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:10px;">
                <?php 
                $maxJour = max($joursSemaine) ?: 1;
                foreach ($joursSemaine as $jour => $count): 
                    $width = ($count / $maxJour) * 100;
                    $barColor = $count > 2 ? '#ef4444' : ($count > 0 ? '#f59e0b' : '#e2e8f0');
                ?>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="width:70px;font-size:0.78rem;font-weight:500;color:#475569;text-align:right;"><?= $jour ?></span>
                        <div style="flex:1;height:24px;background:#f1f5f9;border-radius:12px;overflow:hidden;">
                            <div style="width:<?= $width ?>%;height:100%;background:<?= $barColor ?>;border-radius:12px;display:flex;align-items:center;justify-content:flex-end;padding-right:8px;transition:width 0.5s ease;">
                                <?php if ($count > 0): ?>
                                    <span style="font-size:0.7rem;font-weight:700;color:#fff;"><?= $count ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     LISTE DES RETARDS
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon orange"><i class="fa-solid fa-list-check"></i></div>
            <div>
                <div class="card-header-title">Historique complet des retards</div>
                <div class="card-header-subtitle"><?= $totalRetards ?> retard(s) enregistré(s)</div>
            </div>
        </div>
        <div>
            <?php if ($totalRetards >= 10): ?>
                <span class="badge badge-danger"><i class="fa-solid fa-exclamation-triangle"></i> Situation préoccupante</span>
            <?php elseif ($totalRetards >= 5): ?>
                <span class="badge badge-warning"><i class="fa-solid fa-exclamation-circle"></i> À surveiller</span>
            <?php elseif ($totalRetards > 0): ?>
                <span class="badge badge-info"><i class="fa-solid fa-info-circle"></i> Situation acceptable</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body no-padding">
        <?php if (empty($retards)): ?>
            <div class="empty-state" style="padding:40px;">
                <i class="fa-solid fa-check-circle" style="color:#10b981;font-size:2.5rem;"></i>
                <p style="font-size:1rem;font-weight:600;color:#10b981;">Aucun retard !</p>
                <p style="font-size:0.85rem;color:#64748b;">Votre enfant est toujours arrivé à l'heure. Félicitations pour cette ponctualité exemplaire !</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Jour</th>
                            <th>Heure prévue</th>
                            <th>Heure arrivée</th>
                            <th>Durée</th>
                            <th>Justifié</th>
                            <th>Motif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($retards as $ret): 
                            $jourSemaine = match($ret['jour_semaine'] ?? '') {
                                'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
                                'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi',
                                'Sunday' => 'Dimanche', default => ''
                            };
                            $graviteClass = $ret['duree_retard'] > 30 ? 'table-danger' : ($ret['duree_retard'] > 15 ? 'table-warning' : '');
                        ?>
                            <tr class="<?= $graviteClass ?>">
                                <td><strong><?= e($ret['date_format']) ?></strong></td>
                                <td><?= $jourSemaine ?></td>
                                <td><?= e($ret['heure_prevue_format']) ?></td>
                                <td><strong style="color:#f59e0b;"><?= e($ret['heure_arrivee_format']) ?></strong></td>
                                <td>
                                    <span class="badge <?= $ret['duree_retard'] > 30 ? 'badge-danger' : ($ret['duree_retard'] > 15 ? 'badge-warning' : 'badge-info') ?>">
                                        <i class="fa-solid fa-clock"></i> <?= $ret['duree_retard'] ?> min
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ret['est_justifie']): ?>
                                        <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Justifié</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fa-solid fa-exclamation-circle"></i> Non justifié</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($ret['motif'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     CONSEILS AUX PARENTS
     ============================================================ -->
<?php if ($totalRetards > 0): ?>
<div class="info-card-custom mt-4">
    <div class="info-card-custom-header">
        <div class="info-card-custom-icon orange">
            <i class="fa-solid fa-lightbulb"></i>
        </div>
        <h6>Conseils pour améliorer la ponctualité</h6>
    </div>
    <div class="info-card-custom-body">
        <ul class="info-list">
            <li><strong>Routine du soir :</strong> Préparez les affaires scolaires la veille (cartable, uniforme, goûter).</li>
            <li><strong>Heure de coucher :</strong> Assurez-vous que votre enfant se couche à une heure raisonnable.</li>
            <li><strong>Réveil anticipé :</strong> Prévoyez 15-20 minutes de marge le matin pour les imprévus.</li>
            <li><strong>Transport :</strong> Vérifiez les horaires de transport ou organisez un covoiturage avec d'autres parents.</li>
            <li><strong>Communication :</strong> En cas de retard inévitable, prévenez l'école dès que possible.</li>
            <?php if ($retardsNonJustifies > 0): ?>
                <li><strong style="color:#ef4444;">Action requise :</strong> <?= $retardsNonJustifies ?> retard(s) non justifié(s). Veuillez contacter l'enseignant pour les justifier.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     ACTIONS
     ============================================================ -->
<div class="d-flex gap-sm justify-content-center mt-4">
    <a href="/gestion_scolaire/parent/enfants" class="btn btn-outline-primary">
        <i class="fa-solid fa-arrow-left"></i> Retour au tableau de bord
    </a>
    <a href="/gestion_scolaire/parent/absences?enfant_id=<?= $selectedEnfant['id'] ?>" class="btn btn-outline-primary">
        <i class="fa-solid fa-calendar-xmark"></i> Voir les absences
    </a>
    <a href="/gestion_scolaire/parent/bulletins?enfant_id=<?= $selectedEnfant['id'] ?>" class="btn btn-outline-primary">
        <i class="fa-solid fa-file-invoice"></i> Voir le bulletin
    </a>
</div>

<script>
function changerEnfant(enfantId) {
    var url = new URL(window.location.href);
    url.searchParams.set('enfant_id', enfantId);
    window.location.href = url.toString();
}
</script>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>