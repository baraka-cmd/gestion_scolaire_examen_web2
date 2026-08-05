<?php
/**
 * Fichier : public/views/enseignant/modules/bulletins.php
 * Rôle    : Consultation du bulletin officiel congolais — Vue Enseignant
 * Système : Bulletin avec 6 périodes, 3 examens, 3 trimestres, total annuel
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

// ✅ CORRECT (4 niveaux)
require_once __DIR__ . '/../../../../src/config/app.php';

requireLogin();
requirePermission('bulletins.consulter');

global $db;

require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ResultatController.php';
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'BulletinController.php';
$resultatController = new ResultatController();
$bulletinController = new BulletinController();

// Récupérer la classe de l'enseignant connecté
$enseignantId = $_SESSION['utilisateur_id'] ?? 0;
$classeId = (int) $db->fetchColumn(
    "SELECT e.classe_id FROM enseignants e WHERE e.utilisateur_id = :uid AND e.est_actif = 1 LIMIT 1",
    ['uid' => $enseignantId]
);

if ($classeId <= 0) {
    setFlash('warning', 'Aucune classe ne vous est assignée. Contactez l\'administration.');
    redirect('/gestion_scolaire/dashboard');
}

// Récupérer les infos de la classe
$maClasse = $db->fetchOne(
    "SELECT c.id, c.nom, c.niveau, c.salle, c.capacite_max,
            a.libelle AS annee_libelle
     FROM classes c
     INNER JOIN annees_scolaires a ON c.annee_scolaire_id = a.id
     WHERE c.id = :id",
    ['id' => $classeId]
);

if (!$maClasse) {
    setFlash('error', 'Classe introuvable.');
    redirect('/gestion_scolaire/dashboard');
}

$eleveId = (int) ($_GET['eleve_id'] ?? 0);
$anneeId = (int) ($_GET['annee_id'] ?? 0);
if ($anneeId <= 0) $anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

$ecole = $db->fetchOne("SELECT * FROM parametres_ecole LIMIT 1");
$annees = $resultatController->getAnneesScolaires();

// Récupérer les élèves de SA classe uniquement
$eleves = $db->fetchAll(
    "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe
     FROM inscriptions i INNER JOIN eleves e ON i.eleve_id = e.id
     WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1 ORDER BY e.nom, e.prenom",
    ['cid' => $classeId]
);

$bulletin = null;
if ($eleveId > 0) {
    $bulletin = $resultatController->getResultatsCompletsEleve($eleveId, $anneeId);
}

$anneeScolaire = $db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $anneeId]);

$degre = '';
if ($bulletin && $bulletin['eleve']['classe_niveau'] == 6) $degre = 'DEGRE TERMINAL (6 ANNÉE)';
elseif ($bulletin && $bulletin['eleve']['classe_niveau'] >= 1 && $bulletin['eleve']['classe_niveau'] <= 2) $degre = 'DEGRÉ ÉLÉMENTAIRE (' . $bulletin['eleve']['classe_niveau'] . ' ANNÉE)';
elseif ($bulletin && $bulletin['eleve']['classe_niveau'] >= 3 && $bulletin['eleve']['classe_niveau'] <= 4) $degre = 'DEGRÉ MOYEN (' . $bulletin['eleve']['classe_niveau'] . ' ANNÉE)';
elseif ($bulletin && $bulletin['eleve']['classe_niveau'] >= 5) $degre = 'DEGRÉ TERMINAL (' . $bulletin['eleve']['classe_niveau'] . ' ANNÉE)';

$pageTitle = 'Bulletins — ' . e($maClasse['nom']);
$pageIcon  = 'fa-file-invoice';
$pageDescription = 'Classe : ' . e($maClasse['nom']) . ' — ' . ($bulletin ? 'Bulletin de ' . e($bulletin['eleve']['prenom'] . ' ' . $bulletin['eleve']['nom']) : 'Consultez les bulletins de vos élèves');
$extraCSS = ['modules/bulletins.css'];
$extraJS  = ['modules/bulletins.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Bulletins — <?= e($maClasse['nom']) ?></span>
</nav>

<!-- EN-TÊTE -->
<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-file-invoice"></i> Bulletins — <?= e($maClasse['nom']) ?></h4>
        <p>
            <i class="fa-solid fa-chalkboard"></i> <?= e($maClasse['nom']) ?> (Niveau <?= $maClasse['niveau'] ?>) — 
            <i class="fa-solid fa-users"></i> <?= count($eleves) ?> élève(s) — 
            <i class="fa-solid fa-calendar-alt"></i> <?= e($maClasse['annee_libelle']) ?>
        </p>
    </div>
    <div class="resultats-header-right">
        <a href="/gestion_scolaire/bulletins/generer?classe_id=<?= $classeId ?>&annee_id=<?= $anneeId ?>" class="btn btn-success btn-sm">
            <i class="fa-solid fa-file-invoice"></i> Générer les bulletins
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<!-- SÉLECTEURS -->
<div class="bulletin-selectors no-print">
    <div class="bulletin-selector-card">
        <div class="bulletin-selector-header"><i class="fa-solid fa-filter"></i> Sélectionner un élève de votre classe</div>
        <div class="bulletin-selector-body">
            <form method="GET" action="" class="bulletin-selector-form">
                <input type="hidden" name="annee_id" value="<?= $anneeId ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe</label>
                        <select name="classe_id" class="form-control" disabled style="background:#f1f5f9;cursor:not-allowed;">
                            <option value="<?= $maClasse['id'] ?>" selected><?= e($maClasse['nom']) ?> (Niv. <?= $maClasse['niveau'] ?>)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-graduation-cap"></i> Élève</label>
                        <select name="eleve_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Sélectionnez un élève —</option>
                            <?php foreach ($eleves as $el): ?>
                                <option value="<?= $el['id'] ?>" <?= $eleveId === (int) $el['id'] ? 'selected' : '' ?>><?= e($el['prenom'] . ' ' . $el['nom']) ?> — <?= e($el['matricule']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-calendar-alt"></i> Année</label>
                        <select name="annee_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $anneeId === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($bulletin): ?>
<!-- ============================================================
     BULLETIN OFFICIEL
     ============================================================ -->
<div class="bulletin-document" id="bulletinDocument">

    <!-- EN-TÊTE RDC -->
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
            <div class="rdc-eleve-ligne"><span class="rdc-label">CLASSE :</span><span class="rdc-valeur"><?= $bulletin['eleve']['classe_niveau'] ?>, ANNÉE <?= e($anneeScolaire['libelle'] ?? '') ?></span></div>
            <div class="rdc-eleve-ligne"><span class="rdc-label">N° PERM. :</span><span class="rdc-valeur"><?= e($bulletin['eleve']['numero_permanent'] ?? $bulletin['eleve']['matricule']) ?></span></div>
        </div>
        <div class="rdc-titre-bulletin">BULLETIN DE L'ÉLÈVE : <?= e($degre) ?></div>
        <div class="rdc-annee-scolaire">ANNÉE SCOLAIRE <?= e($anneeScolaire['libelle'] ?? '______________________') ?></div>
    </div>

    <!-- TABLEAU DES NOTES -->
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
                    <th class="col-m">M</th><th class="col-o">O</th>
                    <th class="col-m">M</th><th class="col-o">O</th>
                    <th class="col-m">M</th><th class="col-o">O</th>
                    <th class="col-m">M</th><th class="col-o">O</th>
                    
                    <th class="col-m">M</th><th class="col-o">O</th>
                    <th class="col-m">M</th><th class="col-o">O</th>
                    <th class="col-m">M</th><th class="col-o">O</th>
                    <th class="col-m">M</th><th class="col-o">O</th>
                    
                    <th class="col-m">M</th><th class="col-o">O</th>
                    <th class="col-m">M</th><th class="col-o">O</th>
                    <th class="col-m">M</th><th class="col-o">O</th>
                    <th class="col-m">M</th><th class="col-o">O</th>
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
                    <tr class="groupe-row">
                        <td class="col-branches groupe-nom" colspan="28"><strong><?= e(mb_strtoupper($groupe['libelle'])) ?></strong></td>
                    </tr>
                    <?php foreach ($groupe['sous_matieres'] as $sm): ?>
                        <tr class="sous-matiere-row">
                            <td class="col-branches"><?= e($sm['libelle']) ?></td>
                            
                            <!-- T1 -->
                            <td class="col-m"><?= $sm['T1']['p1']['max'] > 0 ? $sm['T1']['p1']['max'] : '' ?></td>
                            <td class="col-o"><?= $sm['T1']['p1']['obt'] > 0 ? $sm['T1']['p1']['obt'] : '—' ?></td>
                            <td class="col-m"><?= $sm['T1']['p2']['max'] > 0 ? $sm['T1']['p2']['max'] : '' ?></td>
                            <td class="col-o"><?= $sm['T1']['p2']['obt'] > 0 ? $sm['T1']['p2']['obt'] : '—' ?></td>
                            <td class="col-m"><?= $sm['T1']['ex']['max'] > 0 ? $sm['T1']['ex']['max'] : '' ?></td>
                            <td class="col-o"><?= $sm['T1']['ex']['obt'] > 0 ? $sm['T1']['ex']['obt'] : '—' ?></td>
                            <td class="col-m"><?= $sm['T1']['total_max'] > 0 ? $sm['T1']['total_max'] : '' ?></td>
                            <td class="col-o col-total"><strong><?= $sm['T1']['total_obt'] > 0 ? $sm['T1']['total_obt'] : '—' ?></strong></td>
                            
                            <!-- T2 -->
                            <td class="col-m"><?= $sm['T2']['p1']['max'] > 0 ? $sm['T2']['p1']['max'] : '' ?></td>
                            <td class="col-o"><?= $sm['T2']['p1']['obt'] > 0 ? $sm['T2']['p1']['obt'] : '—' ?></td>
                            <td class="col-m"><?= $sm['T2']['p2']['max'] > 0 ? $sm['T2']['p2']['max'] : '' ?></td>
                            <td class="col-o"><?= $sm['T2']['p2']['obt'] > 0 ? $sm['T2']['p2']['obt'] : '—' ?></td>
                            <td class="col-m"><?= $sm['T2']['ex']['max'] > 0 ? $sm['T2']['ex']['max'] : '' ?></td>
                            <td class="col-o"><?= $sm['T2']['ex']['obt'] > 0 ? $sm['T2']['ex']['obt'] : '—' ?></td>
                            <td class="col-m"><?= $sm['T2']['total_max'] > 0 ? $sm['T2']['total_max'] : '' ?></td>
                            <td class="col-o col-total"><strong><?= $sm['T2']['total_obt'] > 0 ? $sm['T2']['total_obt'] : '—' ?></strong></td>
                            
                            <!-- T3 -->
                            <td class="col-m"><?= $sm['T3']['p1']['max'] > 0 ? $sm['T3']['p1']['max'] : '' ?></td>
                            <td class="col-o"><?= $sm['T3']['p1']['obt'] > 0 ? $sm['T3']['p1']['obt'] : '—' ?></td>
                            <td class="col-m"><?= $sm['T3']['p2']['max'] > 0 ? $sm['T3']['p2']['max'] : '' ?></td>
                            <td class="col-o"><?= $sm['T3']['p2']['obt'] > 0 ? $sm['T3']['p2']['obt'] : '—' ?></td>
                            <td class="col-m"><?= $sm['T3']['ex']['max'] > 0 ? $sm['T3']['ex']['max'] : '' ?></td>
                            <td class="col-o"><?= $sm['T3']['ex']['obt'] > 0 ? $sm['T3']['ex']['obt'] : '—' ?></td>
                            <td class="col-m"><?= $sm['T3']['total_max'] > 0 ? $sm['T3']['total_max'] : '' ?></td>
                            <td class="col-o col-total"><strong><?= $sm['T3']['total_obt'] > 0 ? $sm['T3']['total_obt'] : '—' ?></strong></td>
                            
                            <!-- Annuel -->
                            <td class="col-m"><?= $sm['max_annuel'] > 0 ? $sm['max_annuel'] : '' ?></td>
                            <td class="col-o col-total"><strong><?= $sm['annuel']['obt'] > 0 ? $sm['annuel']['obt'] : '—' ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="sous-total-row">
                        <td class="col-branches"><strong>SOUS-TOTAL</strong></td>
                        <?php 
                        $st = $groupe['sous_total'];
                        foreach (['T1','T2','T3'] as $tk):
                            foreach (['p1','p2','ex','total'] as $pk): 
                                $val = $st[$tk][$pk];
                        ?>
                            <td class="col-m"><?= $val['max'] > 0 ? $val['max'] : '' ?></td>
                            <td class="col-o"><strong><?= $val['obt'] > 0 ? $val['obt'] : '—' ?></strong></td>
                        <?php endforeach; endforeach; ?>
                        <td class="col-m"><?= $st['annuel']['max'] > 0 ? $st['annuel']['max'] : '' ?></td>
                        <td class="col-o"><strong><?= $st['annuel']['obt'] > 0 ? $st['annuel']['obt'] : '—' ?></strong></td>
                    </tr>
                <?php endforeach; ?>

                <!-- TOTAL GÉNÉRAL -->
                <tr class="total-general-row">
                    <td class="col-branches"><strong>TOTAL GÉNÉRAL</strong></td>
                    <?php 
                    $tg = $bulletin['totaux'];
                    foreach (['T1','T2','T3'] as $tk):
                        foreach (['p1','p2','ex','total'] as $pk): 
                            $val = $tg[$tk][$pk];
                    ?>
                        <td class="col-m"><?= $val['max'] > 0 ? $val['max'] : '' ?></td>
                        <td class="col-o"><strong><?= $val['obt'] > 0 ? $val['obt'] : '' ?></strong></td>
                    <?php endforeach; endforeach; ?>
                    <td class="col-m"><?= $tg['annuel']['max'] > 0 ? $tg['annuel']['max'] : '' ?></td>
                    <td class="col-o"><strong><?= $tg['annuel']['obt'] > 0 ? $tg['annuel']['obt'] : '' ?></strong></td>
                </tr>

                <!-- POURCENTAGES -->
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

                <!-- MENTION -->
                <tr class="mention-row">
                    <td class="col-branches"><strong>MENTION</strong></td>
                    <td colspan="8" class="text-center mention-value"><?= $bulletin['pourcentages']['T1']['mention'] ?></td>
                    <td colspan="8" class="text-center mention-value"><?= $bulletin['pourcentages']['T2']['mention'] ?></td>
                    <td colspan="8" class="text-center mention-value"><?= $bulletin['pourcentages']['T3']['mention'] ?></td>
                    <td colspan="2" class="text-center mention-value"><?= $bulletin['pourcentages']['annuel']['mention'] ?></td>
                </tr>

                <!-- DÉCISION -->
                <tr class="decision-row">
                    <td class="col-branches"><strong>DÉCISION</strong></td>
                    <td colspan="8" class="text-center decision-value <?= $bulletin['pourcentages']['T1']['decision'] === 'Admis' ? 'admis' : 'ajourne' ?>"><?= $bulletin['pourcentages']['T1']['decision'] ?></td>
                    <td colspan="8" class="text-center decision-value <?= $bulletin['pourcentages']['T2']['decision'] === 'Admis' ? 'admis' : 'ajourne' ?>"><?= $bulletin['pourcentages']['T2']['decision'] ?></td>
                    <td colspan="8" class="text-center decision-value <?= $bulletin['pourcentages']['T3']['decision'] === 'Admis' ? 'admis' : 'ajourne' ?>"><?= $bulletin['pourcentages']['T3']['decision'] ?></td>
                    <td colspan="2" class="text-center decision-value <?= $bulletin['pourcentages']['annuel']['decision'] === 'Admis' ? 'admis' : 'ajourne' ?>"><?= $bulletin['pourcentages']['annuel']['decision'] ?></td>
                </tr>

                <!-- RANG -->
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

    <!-- SIGNATURES -->
    <div class="bulletin-signatures">
        <div class="signature-block"><div class="signature-line"></div><span>L'Enseignant Titulaire</span></div>
        <div class="signature-block"><div class="signature-line"></div><span>Le Directeur</span></div>
        <div class="signature-block"><div class="signature-line"></div><span>Le Parent / Tuteur</span></div>
    </div>

    <!-- ACTIONS -->
    <div class="bulletin-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer</button>
        <a href="/gestion_scolaire/bulletins/generer?classe_id=<?= $classeId ?>&annee_id=<?= $anneeId ?>" class="btn btn-success"><i class="fa-solid fa-file-invoice"></i> Générer la classe</a>
        <a href="/gestion_scolaire/enseignant/bulletins" class="btn btn-outline-primary"><i class="fa-solid fa-arrow-left"></i> Retour</a>
    </div>
</div>

<?php elseif (!empty($eleves)): ?>
    <div class="empty-state" style="padding:40px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-file-invoice"></i>
        <p>Sélectionnez un élève de votre classe pour afficher son bulletin.</p>
    </div>
<?php else: ?>
    <div class="empty-state" style="padding:40px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-users-slash"></i>
        <p>Aucun élève trouvé dans votre classe.</p>
    </div>
<?php endif; ?>

<?php require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php'; ?>