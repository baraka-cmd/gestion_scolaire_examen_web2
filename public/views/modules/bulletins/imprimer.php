<?php
/**
 * Fichier : public/views/modules/bulletins/imprimer.php
 * Rôle    : Impression des bulletins — Individuelle ou massive avec navigation
 * Système : Bulletin officiel congolais (EPST/RDC) — Annuel complet
 * Version : 4.0 — Navigation entre bulletins + impression sélective
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('bulletins.imprimer');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ResultatController.php';
$resultatController = new ResultatController();

$classes = $resultatController->getClassesActives();
$annees = $resultatController->getAnneesScolaires();

$classeId = (int) ($_GET['classe_id'] ?? 0);
$anneeId = (int) ($_GET['annee_id'] ?? 0);
$eleveId = (int) ($_GET['eleve_id'] ?? 0);
$mode = $_GET['mode'] ?? '';

if ($anneeId <= 0) $anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

$ecole = $db->fetchOne("SELECT * FROM parametres_ecole LIMIT 1");
$annee = $db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $anneeId]);

// Récupérer les élèves d'une classe
$eleves = [];
if ($classeId > 0) {
    $eleves = $db->fetchAll(
        "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe
         FROM inscriptions i
         INNER JOIN eleves e ON i.eleve_id = e.id
         WHERE i.classe_id = :cid AND i.annee_scolaire_id = :aid AND i.statut = 'Actif' AND e.est_actif = 1
         ORDER BY e.nom, e.prenom",
        ['cid' => $classeId, 'aid' => $anneeId]
    );
}

// Déterminer l'élève à afficher
$currentIndex = 0;
$bulletin = null;

if ($mode === 'massif' && !empty($eleves)) {
    // Si un élève spécifique est demandé, le trouver
    if ($eleveId > 0) {
        foreach ($eleves as $i => $el) {
            if ((int) $el['id'] === $eleveId) {
                $currentIndex = $i;
                break;
            }
        }
    }
    $eleveActuel = $eleves[$currentIndex];
    $bulletin = $resultatController->getResultatsCompletsEleve($eleveActuel['id'], $anneeId);
} elseif ($eleveId > 0) {
    $bulletin = $resultatController->getResultatsCompletsEleve($eleveId, $anneeId);
}

// Élèves précédent et suivant
$prevEleve = $currentIndex > 0 ? $eleves[$currentIndex - 1] : null;
$nextEleve = $currentIndex < count($eleves) - 1 ? $eleves[$currentIndex + 1] : null;

$degre = '';
if ($bulletin && $bulletin['eleve']['classe_niveau'] == 6) $degre = 'DEGRE TERMINAL (6 ANNÉE)';
elseif ($bulletin && $bulletin['eleve']['classe_niveau'] >= 1 && $bulletin['eleve']['classe_niveau'] <= 2) $degre = 'DEGRÉ ÉLÉMENTAIRE (' . $bulletin['eleve']['classe_niveau'] . ' ANNÉE)';
elseif ($bulletin && $bulletin['eleve']['classe_niveau'] >= 3 && $bulletin['eleve']['classe_niveau'] <= 4) $degre = 'DEGRÉ MOYEN (' . $bulletin['eleve']['classe_niveau'] . ' ANNÉE)';
elseif ($bulletin && $bulletin['eleve']['classe_niveau'] >= 5) $degre = 'DEGRÉ TERMINAL (' . $bulletin['eleve']['classe_niveau'] . ' ANNÉE)';

$pageTitle = 'Imprimer les bulletins';
$pageIcon  = 'fa-print';
$pageDescription = 'Impression des bulletins annuels';
$extraCSS = ['modules/bulletins.css'];
$extraJS  = ['modules/bulletins.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/bulletins">Bulletins</a>
    <span class="separator">/</span>
    <span class="current">Imprimer</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-print"></i> Imprimer les bulletins</h4>
        <p>Imprimez un bulletin individuel ou tous les bulletins d'une classe.</p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/bulletins" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-arrow-left"></i> Retour</a>
    </div>
</div>

<div id="flashMessage"></div>

<!-- OPTIONS D'IMPRESSION -->
<div class="imprimer-options no-print">
    <!-- INDIVIDUEL -->
    <div class="imprimer-option-card">
        <div class="imprimer-option-icon blue"><i class="fa-solid fa-user"></i></div>
        <div class="imprimer-option-content">
            <h5>Impression individuelle</h5>
            <p>Imprimer le bulletin annuel complet d'un seul élève</p>
            <form method="GET" action="" class="imprimer-option-form">
                <input type="hidden" name="mode" value="individuel">
                <input type="hidden" name="annee_id" value="<?= $anneeId ?>">
                <div class="form-row">
                    <div class="form-group">
                        <select name="classe_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Classe —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] ? 'selected' : '' ?>><?= e($cl['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="eleve_id" class="form-control">
                            <option value="">— Élève —</option>
                            <?php foreach ($eleves as $el): ?>
                                <option value="<?= $el['id'] ?>" <?= $eleveId === (int) $el['id'] ? 'selected' : '' ?>><?= e($el['prenom'] . ' ' . $el['nom']) ?> — <?= e($el['matricule']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-print"></i> Afficher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MASSIF -->
    <div class="imprimer-option-card">
        <div class="imprimer-option-icon green"><i class="fa-solid fa-users"></i></div>
        <div class="imprimer-option-content">
            <h5>Impression massive</h5>
            <p>Parcourir et imprimer les bulletins annuels de tous les élèves d'une classe</p>
            <form method="GET" action="" class="imprimer-option-form">
                <input type="hidden" name="mode" value="massif">
                <input type="hidden" name="annee_id" value="<?= $anneeId ?>">
                <div class="form-row">
                    <div class="form-group">
                        <select name="classe_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Classe —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] && $mode === 'massif' ? 'selected' : '' ?>><?= e($cl['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm"><i class="fa-solid fa-users"></i> Afficher la classe</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- BULLETIN INDIVIDUEL -->
<?php if ($bulletin && $mode === 'individuel'): ?>
<div class="no-print" style="text-align:center;margin-bottom:20px;">
    <button type="button" class="btn btn-primary btn-lg" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer ce bulletin</button>
    <button type="button" class="btn btn-outline-primary btn-lg ms-2" onclick="window.history.back()"><i class="fa-solid fa-arrow-left"></i> Retour</button>
</div>

<?= renderBulletinComplet($bulletin, $ecole, $annee, $degre) ?>

<!-- IMPRESSION MASSIVE AVEC NAVIGATION -->
<?php elseif ($mode === 'massif' && !empty($eleves) && $bulletin): ?>
<div class="no-print" style="display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:20px;flex-wrap:wrap;">
    <?php if ($prevEleve): ?>
        <a href="?mode=massif&classe_id=<?= $classeId ?>&annee_id=<?= $anneeId ?>&eleve_id=<?= $prevEleve['id'] ?>" class="btn btn-outline-primary btn-lg">
            <i class="fa-solid fa-chevron-left"></i> <?= e($prevEleve['prenom']) ?>
        </a>
    <?php else: ?>
        <button class="btn btn-outline-primary btn-lg" disabled><i class="fa-solid fa-chevron-left"></i> Précédent</button>
    <?php endif; ?>

    <div style="text-align:center;">
        <span style="font-size:0.9rem;font-weight:600;color:var(--color-dark);">
            Bulletin <?= $currentIndex + 1 ?> / <?= count($eleves) ?>
        </span>
        <br>
        <span style="font-size:0.78rem;color:var(--color-gray-500);">
            <?= e($bulletin['eleve']['prenom'] . ' ' . $bulletin['eleve']['nom']) ?> — 
            <?= e($bulletin['eleve']['matricule']) ?>
        </span>
    </div>

    <?php if ($nextEleve): ?>
        <a href="?mode=massif&classe_id=<?= $classeId ?>&annee_id=<?= $anneeId ?>&eleve_id=<?= $nextEleve['id'] ?>" class="btn btn-outline-primary btn-lg">
            <?= e($nextEleve['prenom']) ?> <i class="fa-solid fa-chevron-right"></i>
        </a>
    <?php else: ?>
        <button class="btn btn-outline-primary btn-lg" disabled>Suivant <i class="fa-solid fa-chevron-right"></i></button>
    <?php endif; ?>
</div>

<div class="no-print" style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
    <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer ce bulletin</button>
    <button type="button" class="btn btn-success" onclick="window.location.href='?mode=massif&classe_id=<?= $classeId ?>&annee_id=<?= $anneeId ?>&print_all=1'">
        <i class="fa-solid fa-print"></i> Imprimer tous les bulletins
    </button>
    <select class="form-control" style="width:auto;min-width:200px;" onchange="if(this.value) window.location.href='?mode=massif&classe_id=<?= $classeId ?>&annee_id=<?= $anneeId ?>&eleve_id='+this.value">
        <option value="">— Aller à un élève —</option>
        <?php foreach ($eleves as $i => $el): ?>
            <option value="<?= $el['id'] ?>" <?= (int) $el['id'] === (int) $bulletin['eleve']['id'] ? 'selected' : '' ?>>
                <?= $i + 1 ?>. <?= e($el['prenom'] . ' ' . $el['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<?= renderBulletinComplet($bulletin, $ecole, $annee, $degre) ?>

<!-- IMPRESSION MASSIVE — TOUS -->
<?php elseif ($mode === 'massif' && isset($_GET['print_all']) && !empty($eleves)): ?>
<div class="no-print" style="text-align:center;margin-bottom:20px;">
    <button type="button" class="btn btn-primary btn-lg" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer tous les bulletins (<?= count($eleves) ?>)</button>
    <a href="?mode=massif&classe_id=<?= $classeId ?>&annee_id=<?= $anneeId ?>" class="btn btn-outline-primary btn-lg ms-2"><i class="fa-solid fa-arrow-left"></i> Retour</a>
</div>

<div class="resultats-info-bar">
    <div class="resultats-info-item"><i class="fa-solid fa-chalkboard"></i><span>Classe : <strong><?= e($db->fetchColumn("SELECT nom FROM classes WHERE id = :id", ['id' => $classeId])) ?></strong></span></div>
    <div class="resultats-info-item"><i class="fa-solid fa-users"></i><span><strong><?= count($eleves) ?></strong> élève(s)</span></div>
    <div class="resultats-info-item"><i class="fa-solid fa-calendar-alt"></i><span>Année : <strong><?= e($annee['libelle'] ?? '') ?></strong></span></div>
</div>

<?php foreach ($eleves as $el): 
    $b = $resultatController->getResultatsCompletsEleve($el['id'], $anneeId);
    if (empty($b)) continue;
?>
<div class="bulletin-document" style="page-break-after: always;">
    <?= renderBulletinComplet($b, $ecole, $annee, $degre) ?>
</div>
<?php endforeach; ?>

<?php elseif ($classeId <= 0): ?>
<div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
    <i class="fa-solid fa-print"></i>
    <p>Choisissez un mode d'impression ci-dessus.</p>
</div>
<?php endif; ?>

<?php
// Fonction pour afficher le bulletin complet
function renderBulletinComplet($bulletin, $ecole, $annee, $degre) {
    ob_start();
?>
<div class="bulletin-rdc-header">
    <div class="rdc-pays"><?= e($ecole['pays'] ?? 'RÉPUBLIQUE DÉMOCRATIQUE DU CONGO') ?></div>
    <div class="rdc-ministere"><?= e($ecole['ministere'] ?? "MINISTÈRE DE L'ENSEIGNEMENT PRIMAIRE, SECONDAIRE ET PROFESSIONNEL") ?></div>
    <div class="rdc-id">N° ID. <?= e($bulletin['eleve']['numero_permanent'] ?? $bulletin['eleve']['matricule']) ?></div>
    <div class="rdc-ecole">
        <div class="rdc-ecole-ligne"><span class="rdc-label">ÉCOLE :</span><span class="rdc-valeur"><?= e($ecole['nom_ecole'] ?? '___________________________') ?></span></div>
        <div class="rdc-ecole-ligne"><span class="rdc-label">VILLE :</span><span class="rdc-valeur"><?= e($ecole['ville'] ?? '___________________________') ?></span></div>
        <div class="rdc-ecole-ligne"><span class="rdc-label">COMMUNE/TER :</span><span class="rdc-valeur"><?= e($ecole['commune_territoire'] ?? '___________________________') ?></span></div>
        <div class="rdc-ecole-ligne"><span class="rdc-label">CODE :</span><span class="rdc-valeur"><?= e($ecole['code_etablissement'] ?? '___________________________') ?></span></div>
    </div>
    <div class="rdc-eleve">
        <div class="rdc-eleve-ligne"><span class="rdc-label">ÉLÈVE :</span><span class="rdc-valeur"><?= e(mb_strtoupper($bulletin['eleve']['nom'] . ', ' . $bulletin['eleve']['prenom'])) ?></span></div>
        <div class="rdc-eleve-ligne"><span class="rdc-label">SEXE :</span><span class="rdc-valeur"><?= $bulletin['eleve']['sexe'] === 'M' ? 'M.' : 'F.' ?></span></div>
        <div class="rdc-eleve-ligne"><span class="rdc-label">NE(E) A :</span><span class="rdc-valeur"><?= e(mb_strtoupper($bulletin['eleve']['lieu_naissance'] ?? '___________________________')) ?>, le <?= e($bulletin['eleve']['date_naissance_format'] ?? '') ?></span></div>
        <div class="rdc-eleve-ligne"><span class="rdc-label">CLASSE :</span><span class="rdc-valeur"><?= $bulletin['eleve']['classe_niveau'] ?>, ANNÉE <?= e($annee['libelle'] ?? '') ?></span></div>
        <div class="rdc-eleve-ligne"><span class="rdc-label">N° PERM. :</span><span class="rdc-valeur"><?= e($bulletin['eleve']['numero_permanent'] ?? $bulletin['eleve']['matricule']) ?></span></div>
    </div>
    <div class="rdc-titre-bulletin">BULLETIN DE L'ÉLÈVE : <?= e($degre) ?></div>
    <div class="rdc-annee-scolaire">ANNÉE SCOLAIRE <?= e($annee['libelle'] ?? '______________________') ?></div>
</div>

<div class="bulletin-table-wrapper">
    <table class="bulletin-table">
        <thead>
            <tr>
                <th rowspan="2" class="col-branches">BRANCHES</th>
                <th colspan="8" class="col-trimestre trimestre-1">1er TRIMESTRE</th>
                <th colspan="8" class="col-trimestre trimestre-2">2ème TRIMESTRE</th>
                <th colspan="8" class="col-trimestre trimestre-3">3ème TRIMESTRE</th>
                <th colspan="2" rowspan="2" class="col-annuel">TOTAL ANNUEL</th>
            </tr>
            <tr>
                <th class="col-m">M</th><th class="col-o">O</th><th class="col-m">M</th><th class="col-o">O</th><th class="col-m">M</th><th class="col-o">O</th><th class="col-m">M</th><th class="col-o">O</th>
                <th class="col-m">M</th><th class="col-o">O</th><th class="col-m">M</th><th class="col-o">O</th><th class="col-m">M</th><th class="col-o">O</th><th class="col-m">M</th><th class="col-o">O</th>
                <th class="col-m">M</th><th class="col-o">O</th><th class="col-m">M</th><th class="col-o">O</th><th class="col-m">M</th><th class="col-o">O</th><th class="col-m">M</th><th class="col-o">O</th>
            </tr>
            <tr class="sub-header">
                <th></th>
                <th colspan="2">P1</th><th colspan="2">P2</th><th colspan="2">EX1</th><th colspan="2">T1</th>
                <th colspan="2">P3</th><th colspan="2">P4</th><th colspan="2">EX2</th><th colspan="2">T2</th>
                <th colspan="2">P5</th><th colspan="2">P6</th><th colspan="2">EX3</th><th colspan="2">T3</th>
                <th>MAX</th><th>OBT</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bulletin['groupes'] as $groupe): ?>
                <tr class="groupe-row"><td class="col-branches groupe-nom" colspan="28"><strong><?= e(mb_strtoupper($groupe['libelle'])) ?></strong></td></tr>
                <?php foreach ($groupe['sous_matieres'] as $sm): ?>
                    <tr class="sous-matiere-row">
                        <td class="col-branches"><?= e($sm['libelle']) ?></td>
                        <td class="col-m"><?= $sm['T1']['p1']['max'] ?></td><td class="col-o"><?= $sm['T1']['p1']['obt'] > 0 ? $sm['T1']['p1']['obt'] : '—' ?></td>
                        <td class="col-m"><?= $sm['T1']['p2']['max'] ?></td><td class="col-o"><?= $sm['T1']['p2']['obt'] > 0 ? $sm['T1']['p2']['obt'] : '—' ?></td>
                        <td class="col-m"><?= $sm['T1']['ex']['max'] ?></td><td class="col-o"><?= $sm['T1']['ex']['obt'] > 0 ? $sm['T1']['ex']['obt'] : '—' ?></td>
                        <td class="col-m"><?= $sm['T1']['total_max'] ?></td><td class="col-o col-total"><strong><?= $sm['T1']['total_obt'] > 0 ? $sm['T1']['total_obt'] : '—' ?></strong></td>
                        <td class="col-m"><?= $sm['T2']['p1']['max'] ?></td><td class="col-o"><?= $sm['T2']['p1']['obt'] > 0 ? $sm['T2']['p1']['obt'] : '—' ?></td>
                        <td class="col-m"><?= $sm['T2']['p2']['max'] ?></td><td class="col-o"><?= $sm['T2']['p2']['obt'] > 0 ? $sm['T2']['p2']['obt'] : '—' ?></td>
                        <td class="col-m"><?= $sm['T2']['ex']['max'] ?></td><td class="col-o"><?= $sm['T2']['ex']['obt'] > 0 ? $sm['T2']['ex']['obt'] : '—' ?></td>
                        <td class="col-m"><?= $sm['T2']['total_max'] ?></td><td class="col-o col-total"><strong><?= $sm['T2']['total_obt'] > 0 ? $sm['T2']['total_obt'] : '—' ?></strong></td>
                        <td class="col-m"><?= $sm['T3']['p1']['max'] ?></td><td class="col-o"><?= $sm['T3']['p1']['obt'] > 0 ? $sm['T3']['p1']['obt'] : '—' ?></td>
                        <td class="col-m"><?= $sm['T3']['p2']['max'] ?></td><td class="col-o"><?= $sm['T3']['p2']['obt'] > 0 ? $sm['T3']['p2']['obt'] : '—' ?></td>
                        <td class="col-m"><?= $sm['T3']['ex']['max'] ?></td><td class="col-o"><?= $sm['T3']['ex']['obt'] > 0 ? $sm['T3']['ex']['obt'] : '—' ?></td>
                        <td class="col-m"><?= $sm['T3']['total_max'] ?></td><td class="col-o col-total"><strong><?= $sm['T3']['total_obt'] > 0 ? $sm['T3']['total_obt'] : '—' ?></strong></td>
                        <td class="col-m"><?= $sm['max_annuel'] ?></td><td class="col-o col-total"><strong><?= $sm['annuel']['obt'] > 0 ? $sm['annuel']['obt'] : '—' ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="sous-total-row">
                    <td class="col-branches"><strong>SOUS-TOTAL</strong></td>
                    <?php $st = $groupe['sous_total'];
                    foreach (['T1','T2','T3'] as $tk):
                        foreach (['p1','p2','ex','total'] as $pk): $val = $st[$tk][$pk]; ?>
                            <td class="col-m"><?= $val['max'] ?></td><td class="col-o"><strong><?= $val['obt'] > 0 ? $val['obt'] : '—' ?></strong></td>
                    <?php endforeach; endforeach; ?>
                    <td class="col-m"><?= $st['annuel']['max'] ?></td><td class="col-o"><strong><?= $st['annuel']['obt'] > 0 ? $st['annuel']['obt'] : '—' ?></strong></td>
                </tr>
            <?php endforeach; ?>
            <tr class="total-general-row">
                <td class="col-branches"><strong>TOTAL GÉNÉRAL</strong></td>
                <?php $tg = $bulletin['totaux'];
                foreach (['T1','T2','T3'] as $tk):
                    foreach (['p1','p2','ex','total'] as $pk): $val = $tg[$tk][$pk]; ?>
                        <td class="col-m"><?= $val['max'] ?></td><td class="col-o"><strong><?= $val['obt'] ?></strong></td>
                <?php endforeach; endforeach; ?>
                <td class="col-m"><?= $tg['annuel']['max'] ?></td><td class="col-o"><strong><?= $tg['annuel']['obt'] ?></strong></td>
            </tr>
            <tr class="pourcentage-row">
                <td class="col-branches"><strong>POURCENTAGE</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T1']['p1'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T1']['p2'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T1']['ex'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T1']['trimestre'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T2']['p1'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T2']['p2'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T2']['ex'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T2']['trimestre'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T3']['p1'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T3']['p2'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T3']['ex'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['T3']['trimestre'] ?>%</strong></td>
                <td colspan="2" class="text-center"><strong><?= $bulletin['pourcentages']['annuel']['pct'] ?>%</strong></td>
            </tr>
            <tr class="mention-row">
                <td class="col-branches"><strong>MENTION</strong></td>
                <td colspan="8" class="text-center"><?= $bulletin['pourcentages']['T1']['mention'] ?></td>
                <td colspan="8" class="text-center"><?= $bulletin['pourcentages']['T2']['mention'] ?></td>
                <td colspan="8" class="text-center"><?= $bulletin['pourcentages']['T3']['mention'] ?></td>
                <td colspan="2" class="text-center"><?= $bulletin['pourcentages']['annuel']['mention'] ?></td>
            </tr>
            <tr class="decision-row">
                <td class="col-branches"><strong>DÉCISION</strong></td>
                <td colspan="8" class="text-center decision-value <?= $bulletin['pourcentages']['T1']['decision'] === 'Admis' ? 'admis' : 'ajourne' ?>"><?= $bulletin['pourcentages']['T1']['decision'] ?></td>
                <td colspan="8" class="text-center decision-value <?= $bulletin['pourcentages']['T2']['decision'] === 'Admis' ? 'admis' : 'ajourne' ?>"><?= $bulletin['pourcentages']['T2']['decision'] ?></td>
                <td colspan="8" class="text-center decision-value <?= $bulletin['pourcentages']['T3']['decision'] === 'Admis' ? 'admis' : 'ajourne' ?>"><?= $bulletin['pourcentages']['T3']['decision'] ?></td>
                <td colspan="2" class="text-center decision-value <?= $bulletin['pourcentages']['annuel']['decision'] === 'Admis' ? 'admis' : 'ajourne' ?>"><?= $bulletin['pourcentages']['annuel']['decision'] ?></td>
            </tr>
            <tr class="rang-row">
                <td class="col-branches"><strong>RANG</strong></td>
                <td colspan="8" class="text-center"><?= $bulletin['rangs']['T1'] ? $bulletin['rangs']['T1']['rang'] . 'ème / ' . $bulletin['rangs']['T1']['effectif_classe'] : '—' ?></td>
                <td colspan="8" class="text-center"><?= $bulletin['rangs']['T2'] ? $bulletin['rangs']['T2']['rang'] . 'ème / ' . $bulletin['rangs']['T2']['effectif_classe'] : '—' ?></td>
                <td colspan="8" class="text-center"><?= $bulletin['rangs']['T3'] ? $bulletin['rangs']['T3']['rang'] . 'ème / ' . $bulletin['rangs']['T3']['effectif_classe'] : '—' ?></td>
                <td colspan="2" class="text-center">—</td>
            </tr>
        </tbody>
    </table>
</div>
<div class="bulletin-signatures">
    <div class="signature-block"><div class="signature-line"></div><span>L'Enseignant Titulaire</span></div>
    <div class="signature-block"><div class="signature-line"></div><span>Le Directeur</span></div>
    <div class="signature-block"><div class="signature-line"></div><span>Le Parent / Tuteur</span></div>
</div>
<?php
    return ob_get_clean();
}
?>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>