<?php
/**
 * Fichier : public/views/parent/modules/absences.php
 * Rôle    : Consultation des absences — Vue Parent (UNIQUEMENT absences, sans retards)
 * Système : École primaire congolaise — Espace Parent
 * Version : 1.1
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('absences.consulter');

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

// Récupérer TOUTES les absences (sans limite)
$absences = $db->fetchAll(
    "SELECT a.id, a.date_absence, a.type_absence, a.est_justifiee, a.motif,
            DATE_FORMAT(a.date_absence, '%d/%m/%Y') AS date_format,
            DAYNAME(a.date_absence) AS jour_semaine
     FROM absences a
     WHERE a.eleve_id = :eid AND a.type_absence = 'Absence'
     ORDER BY a.date_absence DESC",
    ['eid' => $selectedEnfant['id']]
);

// Statistiques globales
$totalAbsences = count($absences);
$absencesJustifiees = count(array_filter($absences, fn($a) => $a['est_justifiee']));
$absencesNonJustifiees = $totalAbsences - $absencesJustifiees;
$tauxJustification = $totalAbsences > 0 ? round(($absencesJustifiees / $totalAbsences) * 100) : 0;

// Absences par mois (6 derniers mois)
$absencesParMois = [];
for ($i = 5; $i >= 0; $i--) {
    $mois = date('Y-m', strtotime("-{$i} months"));
    $count = count(array_filter($absences, fn($a) => substr($a['date_absence'], 0, 7) === $mois));
    $absencesParMois[] = [
        'mois' => $mois,
        'label' => moisEnFrancais((int) substr($mois, 5, 2)) . ' ' . substr($mois, 0, 4),
        'count' => $count
    ];
}

// Absences par jour de la semaine
$joursSemaine = ['Lundi' => 0, 'Mardi' => 0, 'Mercredi' => 0, 'Jeudi' => 0, 'Vendredi' => 0, 'Samedi' => 0];
foreach ($absences as $abs) {
    $jour = match($abs['jour_semaine'] ?? '') {
        'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche', default => ''
    };
    if ($jour && isset($joursSemaine[$jour])) {
        $joursSemaine[$jour]++;
    }
}

// Motifs les plus fréquents
$motifs = [];
foreach ($absences as $abs) {
    if (!empty($abs['motif'])) {
        $motif = $abs['motif'];
        if (!isset($motifs[$motif])) $motifs[$motif] = 0;
        $motifs[$motif]++;
    }
}
arsort($motifs);

$pageTitle = 'Absences — ' . e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']);
$pageIcon  = 'fa-calendar-xmark';
$pageDescription = 'Suivi des absences de votre enfant';
$extraCSS = ['modules/absences.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/parent/enfants">Mes enfants</a>
    <span class="separator">/</span>
    <span class="current">Absences — <?= e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']) ?></span>
</nav>

<!-- EN-TÊTE -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-calendar-xmark"></i> Absences — <?= e($selectedEnfant['prenom'] . ' ' . $selectedEnfant['nom']) ?></h4>
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
        <div class="stat-icon red"><i class="fa-solid fa-calendar-xmark"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $totalAbsences ?></div>
            <div class="stat-label">Total absences</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-check-circle"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $absencesJustifiees ?></div>
            <div class="stat-label">Absences justifiées</div>
            <?php if ($totalAbsences > 0): ?>
                <span class="stat-change up"><?= $tauxJustification ?>% de justification</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $absencesNonJustifiees ?></div>
            <div class="stat-label">Non justifiées</div>
            <?php if ($absencesNonJustifiees > 0): ?>
                <span class="stat-change down">À justifier</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-chart-simple"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $totalAbsences > 0 ? round(($absencesJustifiees / $totalAbsences) * 100) : 100 ?>%</div>
            <div class="stat-label">Taux de régularisation</div>
        </div>
    </div>
</div>

<!-- ============================================================
     GRAPHIQUES
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
                $maxCount = max(array_column($absencesParMois, 'count')) ?: 1;
                foreach ($absencesParMois as $moisData): 
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
        </div>
    </div>

    <!-- Répartition par jour -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fa-solid fa-calendar-week"></i> Absences par jour de la semaine</h5>
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
     MOTIFS FRÉQUENTS
     ============================================================ -->
<?php if (!empty($motifs)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fa-solid fa-tags"></i> Motifs d'absence les plus fréquents</h5>
    </div>
    <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:10px;">
            <?php 
            $maxMotif = max($motifs);
            foreach ($motifs as $motif => $count): 
                $size = 0.75 + ($count / $maxMotif) * 0.4;
            ?>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:10px 16px;display:flex;align-items:center;gap:8px;font-size:<?= $size ?>rem;">
                    <span class="badge badge-danger" style="font-size:0.7rem;"><?= $count ?>×</span>
                    <span style="font-weight:500;color:#475569;"><?= e($motif) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     LISTE COMPLÈTE DES ABSENCES
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon red"><i class="fa-solid fa-list-check"></i></div>
            <div>
                <div class="card-header-title">Historique complet des absences</div>
                <div class="card-header-subtitle"><?= $totalAbsences ?> absence(s) enregistrée(s)</div>
            </div>
        </div>
        <div>
            <?php if ($totalAbsences >= 15): ?>
                <span class="badge badge-danger"><i class="fa-solid fa-exclamation-triangle"></i> Situation critique</span>
            <?php elseif ($totalAbsences >= 8): ?>
                <span class="badge badge-warning"><i class="fa-solid fa-exclamation-circle"></i> À surveiller</span>
            <?php elseif ($totalAbsences >= 3): ?>
                <span class="badge badge-info"><i class="fa-solid fa-info-circle"></i> Situation acceptable</span>
            <?php elseif ($totalAbsences > 0): ?>
                <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Situation normale</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body no-padding">
        <?php if (empty($absences)): ?>
            <div class="empty-state" style="padding:50px;">
                <i class="fa-solid fa-check-circle" style="color:#10b981;font-size:3rem;"></i>
                <p style="font-size:1.1rem;font-weight:600;color:#10b981;margin-top:12px;">Aucune absence !</p>
                <p style="font-size:0.85rem;color:#64748b;">Votre enfant n'a aucune absence enregistrée. Continuez comme ça !</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Jour</th>
                            <th>Statut</th>
                            <th>Justification</th>
                            <th>Motif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($absences as $abs): 
                            $jourSemaine = match($abs['jour_semaine'] ?? '') {
                                'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
                                'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi',
                                'Sunday' => 'Dimanche', default => ''
                            };
                        ?>
                            <tr class="<?= !$abs['est_justifiee'] ? 'table-warning' : '' ?>">
                                <td><strong><?= e($abs['date_format']) ?></strong></td>
                                <td><?= $jourSemaine ?></td>
                                <td>
                                    <span class="badge badge-danger">
                                        <i class="fa-solid fa-user-xmark"></i> Absent
                                    </span>
                                </td>
                                <td>
                                    <?php if ($abs['est_justifiee']): ?>
                                        <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Justifiée</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fa-solid fa-exclamation-circle"></i> Non justifiée</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($abs['motif'] ?? '—') ?></td>
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
<?php if ($totalAbsences > 0): ?>
<div class="info-card-custom mt-4">
    <div class="info-card-custom-header">
        <div class="info-card-custom-icon orange">
            <i class="fa-solid fa-lightbulb"></i>
        </div>
        <h6>Informations importantes</h6>
    </div>
    <div class="info-card-custom-body">
        <ul class="info-list">
            <li><strong>Absence justifiée :</strong> Fournissez un document (certificat médical, lettre parentale) dans les 48h suivant le retour en classe.</li>
            <li><strong>Absence non justifiée :</strong> Après 3 absences non justifiées, l'école peut convoquer les parents.</li>
            <li><strong>Absences prolongées :</strong> Au-delà de 15 jours d'absence non justifiée, l'élève peut être signalé aux autorités compétentes.</li>
            <?php if ($absencesNonJustifiees > 0): ?>
                <li><strong style="color:#ef4444;">⚠️ Action requise :</strong> <?= $absencesNonJustifiees ?> absence(s) non justifiée(s). Veuillez contacter l'enseignant titulaire pour les régulariser.</li>
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
    <a href="/gestion_scolaire/parent/retards?enfant_id=<?= $selectedEnfant['id'] ?>" class="btn btn-outline-primary">
        <i class="fa-solid fa-clock"></i> Voir les retards
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