<?php
/**
 * Fichier : public/views/dashboard/enseignant.php
 * Rôle    : Tableau de bord — Enseignant
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../src/config/app.php';
requireLogin();

if (!hasRole(['Enseignant', 'Admin', 'Directeur'])) {
    redirect('/gestion_scolaire/dashboard');
}

$enseignantId = $_SESSION['utilisateur_id'] ?? 0;

$maClasse = $db->fetchOne(
    "SELECT c.id, c.nom, c.niveau, c.salle, c.capacite_max
     FROM classes c
     INNER JOIN enseignants e ON c.id = e.classe_id
     WHERE e.utilisateur_id = :uid AND c.annee_scolaire_id = (
         SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1
     )",
    ['uid' => $enseignantId]
);

$classeId = $maClasse['id'] ?? 0;
$classeNom = $maClasse['nom'] ?? 'Aucune classe assignée';

$totalEleves = 0;
$presencesJour = 0;
$absencesJour = 0;
$tauxPresence = 0;
$notesSaisies = 0;
$notesAttendues = 0;
$trimestreActif = 0;
$presenceMois = [];
$garcons = 0;
$filles = 0;

if ($classeId > 0) {
    $totalEleves = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
        ['cid' => $classeId]
    );

    $presencesJour = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM absences a
         INNER JOIN inscriptions i ON a.eleve_id = i.eleve_id
         WHERE i.classe_id = :cid AND a.date_absence = CURDATE() AND a.type_absence = 'Présent'",
        ['cid' => $classeId]
    );

    $absencesJour = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM absences a
         INNER JOIN inscriptions i ON a.eleve_id = i.eleve_id
         WHERE i.classe_id = :cid AND a.date_absence = CURDATE() AND a.type_absence = 'Absence'",
        ['cid' => $classeId]
    );

    $tauxPresence = $totalEleves > 0 ? round(($presencesJour / $totalEleves) * 100, 1) : 0;

    $trimestreActif = (int) $db->fetchColumn("SELECT trimestre_actif_id FROM parametres_ecole LIMIT 1");

    $notesSaisies = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM notes n
         INNER JOIN evaluations ev ON n.evaluation_id = ev.id
         WHERE ev.classe_id = :cid AND ev.trimestre_id = :tid",
        ['cid' => $classeId, 'tid' => $trimestreActif]
    );

    $notesAttendues = $totalEleves * 9 * 3;

    // Données présence du mois
    for ($i = 1; $i <= date('d'); $i++) {
        $date = date('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT);
        $presents = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM absences a
             INNER JOIN inscriptions i ON a.eleve_id = i.eleve_id
             WHERE i.classe_id = :cid AND a.date_absence = :date AND a.type_absence = 'Présent'",
            ['cid' => $classeId, 'date' => $date]
        );
        $presenceMois[] = $presents;
    }

    // Filles/Garçons
    $garcons = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM inscriptions i INNER JOIN eleves e ON i.eleve_id = e.id WHERE i.classe_id = :cid AND e.sexe = 'M' AND i.statut = 'Actif'",
        ['cid' => $classeId]
    );
    $filles = (int) $db->fetchColumn(
        "SELECT COUNT(*) FROM inscriptions i INNER JOIN eleves e ON i.eleve_id = e.id WHERE i.classe_id = :cid AND e.sexe = 'F' AND i.statut = 'Actif'",
        ['cid' => $classeId]
    );
}

// Dernières absences
$dernieresAbsences = $db->fetchAll(
    "SELECT e.id, e.nom, e.prenom, a.date_absence, a.type_absence, a.est_justifiee, a.motif
     FROM absences a
     INNER JOIN inscriptions i ON a.eleve_id = i.eleve_id
     INNER JOIN eleves e ON a.eleve_id = e.id
     WHERE i.classe_id = :cid AND a.type_absence = 'Absence'
     ORDER BY a.date_absence DESC
     LIMIT 5",
    ['cid' => $classeId]
);

// Prochains examens
$prochainsExamens = $db->fetchAll(
    "SELECT ev.type_evaluation, ev.date_evaluation, m.libelle as matiere
     FROM evaluations ev
     INNER JOIN matieres m ON ev.groupe_id = m.id
     WHERE ev.classe_id = :cid AND ev.date_evaluation >= CURDATE()
     ORDER BY ev.date_evaluation ASC
     LIMIT 5",
    ['cid' => $classeId]
);

// Élèves en difficulté
$elevesDifficulte = $db->fetchAll(
    "SELECT e.id, e.nom, e.prenom, r.moyenne_generale
     FROM resultats_trimestriels r
     INNER JOIN inscriptions i ON r.eleve_id = i.eleve_id
     INNER JOIN eleves e ON r.eleve_id = e.id
     WHERE i.classe_id = :cid AND r.trimestre_id = :tid AND r.moyenne_generale < 10
     ORDER BY r.moyenne_generale ASC
     LIMIT 5",
    ['cid' => $classeId, 'tid' => $trimestreActif]
);

// Prochains anniversaires
$prochainsAnniversaires = $db->fetchAll(
    "SELECT e.id, e.nom, e.prenom, DATE_FORMAT(e.date_naissance, '%d/%m') as date_naissance,
            TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) + 1 as age
     FROM eleves e
     INNER JOIN inscriptions i ON e.id = i.eleve_id
     WHERE i.classe_id = :cid AND i.statut = 'Actif'
     AND DATE_FORMAT(e.date_naissance, '%m-%d') 
         BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') 
         AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')
     ORDER BY DATE_FORMAT(e.date_naissance, '%m-%d')
     LIMIT 5",
    ['cid' => $classeId]
);

// Métadonnées
$pageTitle = 'Tableau de bord — ' . $classeNom;
$pageIcon  = 'fa-gauge-high';
$pageDescription = 'Enseignant — ' . $classeNom;
$extraCSS = ['dashboard/dashboard.css'];
$extraJS  = ['dashboard/dashboard.js'];

// Script inline pour les graphiques (sera exécuté APRÈS footer.php qui charge Chart.js)
$inlineJS = "
document.addEventListener('DOMContentLoaded', function() {
    var ctx1 = document.getElementById('chartPresenceMois');
    if (ctx1) {
        new Chart(ctx1.getContext('2d'), {
            type: 'bar',
            data: {
                labels: Array.from({length: " . date('d') . "}, (_, i) => i + 1),
                datasets: [{
                    label: 'Élèves présents',
                    data: " . json_encode($presenceMois) . ",
                    backgroundColor: '#3b82f6',
                    borderRadius: 6,
                    maxBarThickness: 30,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: {$totalEleves}, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    var ctx2 = document.getElementById('chartSexeClasse');
    if (ctx2) {
        new Chart(ctx2.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Garçons', 'Filles'],
                datasets: [{
                    data: [{$garcons}, {$filles}],
                    backgroundColor: ['#3b82f6', '#ec4899'],
                    borderWidth: 3,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
                }
            }
        });
    }
});
";

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- En-tête -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-chalkboard-user text-primary me-2"></i>Ma classe — <?= e($classeNom) ?>
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            <i class="fa-solid fa-door-open me-1"></i> Salle <?= e($maClasse['salle'] ?? '—') ?> 
            <span class="mx-2">|</span>
            <i class="fa-solid fa-users me-1"></i> <?= $totalEleves ?> élève(s)
        </p>
    </div>
    <div>
        <a href="/gestion_scolaire/absences/marquer" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-calendar-check"></i> Marquer les présences
        </a>
        <a href="/gestion_scolaire/notes/saisie" class="btn btn-success btn-sm ms-2">
            <i class="fa-solid fa-pen-to-square"></i> Saisir des notes
        </a>
    </div>
</div>

<!-- Cartes statistiques -->
<div class="stats-grid">
    <div class="stat-card students">
        <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $totalEleves ?></div>
            <div class="stat-label">Élèves dans ma classe</div>
        </div>
    </div>
    <div class="stat-card attendance">
        <div class="stat-icon cyan"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $tauxPresence ?>%</div>
            <div class="stat-label">Taux de présence aujourd'hui</div>
            <span class="stat-change <?= $tauxPresence >= 80 ? 'up' : 'down' ?>">
                <i class="fa-solid fa-<?= $tauxPresence >= 80 ? 'arrow-up' : 'arrow-down' ?>"></i>
                <?= $presencesJour ?> présents / <?= $absencesJour ?> absents
            </span>
        </div>
    </div>
    <div class="stat-card teachers">
        <div class="stat-icon green"><i class="fa-solid fa-clipboard-check"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $notesSaisies ?></div>
            <div class="stat-label">Notes saisies ce trimestre</div>
            <span class="stat-change neutral">
                <i class="fa-solid fa-chart-bar"></i> Sur ~<?= $notesAttendues ?> attendues
            </span>
        </div>
    </div>
    <div class="stat-card messages">
        <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= count($elevesDifficulte) ?></div>
            <div class="stat-label">Élèves en difficulté</div>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon cyan"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <div class="card-header-title">Présence ce mois</div>
                    <div class="card-header-subtitle">Évolution quotidienne</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container"><canvas id="chartPresenceMois"></canvas></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon purple"><i class="fa-solid fa-venus-mars"></i></div>
                <div>
                    <div class="card-header-title">Ma classe — Garçons / Filles</div>
                    <div class="card-header-subtitle">Répartition des élèves</div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-container"><canvas id="chartSexeClasse"></canvas></div>
        </div>
    </div>
</div>

<!-- Tableaux -->
<div class="bottom-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon red"><i class="fa-solid fa-calendar-xmark"></i></div>
                <div>
                    <div class="card-header-title">Dernières absences</div>
                    <div class="card-header-subtitle">5 dernières</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Élève</th><th>Date</th><th>Justifiée</th><th>Motif</th></tr></thead>
                    <tbody>
                        <?php if (empty($dernieresAbsences)): ?>
                            <tr><td colspan="4"><div class="empty-state"><i class="fa-solid fa-check-circle"></i><p>Aucune absence récente</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($dernieresAbsences as $abs): ?>
                                <tr>
                                    <td><strong><?= e($abs['prenom'] . ' ' . $abs['nom']) ?></strong></td>
                                    <td><?= formatDate($abs['date_absence'], 'd/m/Y') ?></td>
                                    <td><?= $abs['est_justifiee'] ? '<span class="badge badge-success">Justifiée</span>' : '<span class="badge badge-danger">Non justifiée</span>' ?></td>
                                    <td><?= e($abs['motif'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon orange"><i class="fa-solid fa-file-pen"></i></div>
                <div>
                    <div class="card-header-title">Prochains examens</div>
                    <div class="card-header-subtitle">À venir</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Matière</th><th>Type</th></tr></thead>
                    <tbody>
                        <?php if (empty($prochainsExamens)): ?>
                            <tr><td colspan="3"><div class="empty-state"><i class="fa-solid fa-calendar"></i><p>Aucun examen programmé</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($prochainsExamens as $exam): ?>
                                <tr>
                                    <td><strong><?= formatDate($exam['date_evaluation'], 'd/m/Y') ?></strong></td>
                                    <td><?= e($exam['matiere']) ?></td>
                                    <td><span class="badge badge-primary"><?= e($exam['type_evaluation']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="bottom-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon red"><i class="fa-solid fa-user-graduate"></i></div>
                <div>
                    <div class="card-header-title">Élèves en difficulté</div>
                    <div class="card-header-subtitle">Moyenne < 10/20</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Élève</th><th>Moyenne</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (empty($elevesDifficulte)): ?>
                            <tr><td colspan="3"><div class="empty-state"><i class="fa-solid fa-thumbs-up"></i><p>Tous les élèves ont la moyenne</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($elevesDifficulte as $eleve): ?>
                                <tr>
                                    <td><strong><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></strong></td>
                                    <td><span class="badge badge-danger"><?= number_format($eleve['moyenne_generale'], 1) ?>/20</span></td>
                                    <td><a href="/gestion_scolaire/eleves/fiche/<?= $eleve['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye"></i> Fiche</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <div class="card-header-icon orange"><i class="fa-solid fa-cake-candles"></i></div>
                <div>
                    <div class="card-header-title">Prochains anniversaires</div>
                    <div class="card-header-subtitle">Dans les 7 jours</div>
                </div>
            </div>
        </div>
        <div class="card-body no-padding">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Élève</th><th>Date</th><th>Âge</th></tr></thead>
                    <tbody>
                        <?php if (empty($prochainsAnniversaires)): ?>
                            <tr><td colspan="3"><div class="empty-state"><i class="fa-solid fa-cake-candles"></i><p>Aucun anniversaire proche</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($prochainsAnniversaires as $anniv): ?>
                                <tr>
                                    <td><strong><?= e($anniv['prenom'] . ' ' . $anniv['nom']) ?></strong></td>
                                    <td><?= e($anniv['date_naissance']) ?></td>
                                    <td><span class="badge badge-info"><?= $anniv['age'] ?> ans</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>