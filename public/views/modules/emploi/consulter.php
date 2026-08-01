<?php
/**
 * Fichier : public/views/modules/emploi/consulter.php
 * Rôle    : Consultation et gestion de l'emploi du temps hebdomadaire
 * Système : École primaire congolaise — Horaire statique par classe
 * Version : 1.0
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EmploiController.php';
$emploiController = new EmploiController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $emploiController->handleAjax();
}

// Récupérer les données pour les filtres
$classes = $emploiController->getClassesActives();
$annees = $emploiController->getAnneesScolaires();

// Paramètres
$classeId = (int) ($_GET['classe_id'] ?? 0);
$anneeId  = (int) ($_GET['annee_id'] ?? 0);

if ($anneeId <= 0) {
    $anneeId = (int) $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
}

// Déterminer les permissions
$peutModifier = false;
$isAdmin = hasRole(['Admin', 'Directeur']);
$isEnseignant = hasRole('Enseignant');

if ($isAdmin || ($isEnseignant && $classeId > 0 && $emploiController->estTitulaire($classeId))) {
    $peutModifier = can('emploi.modifier');
}

// Récupérer l'emploi du temps
$creneaux = [];
$classe = null;
if ($classeId > 0) {
    $classe = $db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeId]);
    $creneaux = $emploiController->getEmploiByClasse($classeId, $anneeId);
}

// Organiser les créneaux par jour
$jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$grille = [];
$horaires = [];

foreach ($jours as $jour) {
    $grille[$jour] = [];
}

foreach ($creneaux as $c) {
    $grille[$c['jour']][] = $c;
    $key = $c['heure_debut'] . '-' . $c['heure_fin'];
    if (!isset($horaires[$key])) {
        $horaires[$key] = ['heure_debut' => $c['heure_debut'], 'heure_fin' => $c['heure_fin']];
    }
}

// Trier les horaires
usort($horaires, function($a, $b) {
    return strcmp($a['heure_debut'], $b['heure_debut']);
});

// Récupérer les sous-matières et enseignants pour le formulaire
$matieres = [];
$enseignants = [];
if ($peutModifier && $classeId > 0) {
    $matieres = $db->fetchAll(
        "SELECT m.id, m.libelle, m.code, p.libelle AS groupe_libelle
         FROM matieres m
         INNER JOIN matieres p ON m.parent_id = p.id
         INNER JOIN classe_matieres cm ON m.id = cm.matiere_id AND cm.classe_id = :cid AND cm.est_active = 1
         WHERE m.est_groupe = 0 AND m.est_actif = 1
         ORDER BY p.id, m.libelle",
        ['cid' => $classeId]
    );
    $enseignants = $db->fetchAll("SELECT id, matricule, nom, prenom, specialite FROM enseignants WHERE est_actif = 1 ORDER BY nom");
}

$pageTitle = 'Emploi du temps';
$pageIcon  = 'fa-calendar-days';
$pageDescription = $classe ? 'Emploi du temps — ' . e($classe['nom']) : 'Consultez l\'emploi du temps hebdomadaire';
$extraCSS = ['modules/emploi.css'];
$extraJS  = ['modules/emploi.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <span class="current">Emploi du temps</span>
</nav>

<div class="resultats-header">
    <div class="resultats-header-left">
        <h4><i class="fa-solid fa-calendar-days"></i> Emploi du temps</h4>
        <p>Consultez l'emploi du temps hebdomadaire par classe.</p>
    </div>
    <div class="resultats-header-right">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
        </button>
    </div>
</div>

<div id="flashMessage"></div>

<!-- SÉLECTEURS -->
<div class="resultats-selectors no-print">
    <div class="resultats-selector-card">
        <div class="resultats-selector-header"><i class="fa-solid fa-filter"></i> Sélectionner une classe</div>
        <div class="resultats-selector-body">
            <form method="GET" action="" class="resultats-selector-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-chalkboard"></i> Classe</label>
                        <select name="classe_id" class="form-control" onchange="this.form.submit()">
                            <option value="">— Sélectionnez une classe —</option>
                            <?php foreach ($classes as $cl): ?>
                                <option value="<?= $cl['id'] ?>" <?= $classeId === (int) $cl['id'] ? 'selected' : '' ?>><?= e($cl['nom']) ?> (Niv. <?= $cl['niveau'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-calendar"></i> Année</label>
                        <select name="annee_id" class="form-control" onchange="this.form.submit()">
                            <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= $anneeId === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['libelle']) ?> <?= $a['est_active'] ? '(Active)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($classeId <= 0): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-calendar-days"></i>
        <p>Sélectionnez une classe pour afficher son emploi du temps.</p>
    </div>

<?php elseif (empty($creneaux) && !$peutModifier): ?>
    <div class="empty-state" style="padding:60px 20px;background:#fff;border-radius:16px;border:1px solid var(--border-color);">
        <i class="fa-solid fa-calendar-days"></i>
        <p>Aucun emploi du temps n'a été créé pour cette classe.</p>
    </div>

<?php else: ?>
    <!-- BARRE D'OUTILS -->
    <?php if ($peutModifier): ?>
    <div class="emploi-toolbar no-print">
        <div class="emploi-toolbar-left">
            <span class="emploi-info">
                <i class="fa-solid fa-chalkboard"></i> <?= e($classe['nom']) ?> (Niv. <?= $classe['niveau'] ?>)
            </span>
            <span class="emploi-info">
                <i class="fa-solid fa-door-open"></i> Salle <?= e($classe['salle'] ?? '—') ?>
            </span>
            <span class="emploi-info">
                <i class="fa-solid fa-list"></i> <?= count($creneaux) ?> créneau(x)
            </span>
        </div>
        <div class="emploi-toolbar-right">
            <?php if (!empty($creneaux)): ?>
            <button type="button" class="btn btn-outline-danger btn-sm" id="btnSupprimerTout">
                <i class="fa-solid fa-trash"></i> Vider l'emploi du temps
            </button>
            <?php endif; ?>
            <button type="button" class="btn btn-primary btn-sm" id="btnAjouterHoraire">
                <i class="fa-solid fa-plus"></i> Ajouter un horaire
            </button>
            <button type="button" class="btn btn-success btn-sm" id="btnAjouterCreneau">
                <i class="fa-solid fa-plus"></i> Ajouter un créneau
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- GRILLE EMPLOI DU TEMPS -->
    <div class="emploi-grid-card" id="emploiGridCard">
        <?php if (empty($creneaux) && $peutModifier): ?>
            <div class="emploi-vide-initial">
                <div class="emploi-vide-icon"><i class="fa-solid fa-calendar-plus"></i></div>
                <h5>Créer l'emploi du temps</h5>
                <p>Ajoutez des horaires puis remplissez les créneaux pour chaque jour.</p>
                <div class="d-flex gap-sm justify-content-center mt-3">
                    <button type="button" class="btn btn-primary" id="btnAjouterHoraireBis">
                        <i class="fa-solid fa-plus"></i> Ajouter un horaire
                    </button>
                    <button type="button" class="btn btn-success" id="btnAjouterCreneauBis">
                        <i class="fa-solid fa-plus"></i> Ajouter un créneau
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="emploi-table" id="emploiTable">
                    <thead>
                        <tr>
                            <th class="col-horaire">Horaire</th>
                            <?php foreach ($jours as $jour): ?>
                                <th class="col-jour <?= $jour === 'Samedi' ? 'samedi' : '' ?>"><?= $jour ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($horaires)): ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding:40px;color:var(--color-gray-400);">
                                    <i class="fa-solid fa-clock"></i> Aucun horaire défini
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($horaires as $horaire): ?>
                                <tr>
                                    <td class="col-horaire">
                                        <div class="horaire-cell">
                                            <span class="horaire-debut"><?= substr($horaire['heure_debut'], 0, 5) ?></span>
                                            <span class="horaire-separateur">—</span>
                                            <span class="horaire-fin"><?= substr($horaire['heure_fin'], 0, 5) ?></span>
                                            <?php if ($peutModifier): ?>
                                            <button class="btn-supprimer-horaire" title="Supprimer cet horaire" 
                                                    onclick="supprimerHoraire('<?= $horaire['heure_debut'] ?>', '<?= $horaire['heure_fin'] ?>')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php foreach ($jours as $jour): 
                                        $creneauJour = array_filter($grille[$jour] ?? [], function($c) use ($horaire) {
                                            return $c['heure_debut'] === $horaire['heure_debut'] && $c['heure_fin'] === $horaire['heure_fin'];
                                        });
                                        $creneau = !empty($creneauJour) ? array_shift($creneauJour) : null;
                                    ?>
                                        <td class="col-jour <?= $jour === 'Samedi' ? 'samedi' : '' ?>">
                                            <?php if ($creneau): ?>
                                                <div class="creneau-cell <?= $creneau['type_creneau'] ?? 'cours' ?>"
                                                     data-creneau-id="<?= $creneau['id'] ?>"
                                                     data-jour="<?= $jour ?>"
                                                     data-debut="<?= $creneau['heure_debut'] ?>"
                                                     data-fin="<?= $creneau['heure_fin'] ?>"
                                                     data-matiere="<?= $creneau['matiere_id'] ?>"
                                                     data-enseignant="<?= $creneau['enseignant_id'] ?? '' ?>"
                                                     data-type="<?= $creneau['type_creneau'] ?? 'cours' ?>"
                                                     data-libelle="<?= e($creneau['libelle'] ?? '') ?>"
                                                     <?= $peutModifier ? 'onclick="editerCreneau(this)" style="cursor:pointer;"' : '' ?>>
                                                    <?php if (($creneau['type_creneau'] ?? 'cours') === 'recréation'): ?>
                                                        <span class="creneau-libelle"><?= e($creneau['libelle'] ?: 'RÉCRÉATION') ?></span>
                                                    <?php elseif (($creneau['type_creneau'] ?? 'cours') === 'cernafor'): ?>
                                                        <span class="creneau-libelle"><?= e($creneau['libelle'] ?: 'CERNAFOR') ?></span>
                                                    <?php else: ?>
                                                        <span class="creneau-matiere"><?= e($creneau['matiere_libelle']) ?></span>
                                                        <?php if (!empty($creneau['enseignant_nom'])): ?>
                                                            <span class="creneau-enseignant"><?= e($creneau['enseignant_nom']) ?></span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                    <?php if ($peutModifier): ?>
                                                    <button class="btn-supprimer-creneau" title="Supprimer" 
                                                            onclick="event.stopPropagation(); supprimerCreneau(<?= $creneau['id'] ?>)">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($peutModifier): ?>
                                                <div class="creneau-vide" 
                                                     onclick="ajouterCreneau('<?= $jour ?>', '<?= $horaire['heure_debut'] ?>', '<?= $horaire['heure_fin'] ?>')"
                                                     title="Cliquez pour ajouter un cours">
                                                    <i class="fa-solid fa-plus"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="creneau-vide">—</div>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- ============================================================
     MODALE : AJOUTER/MODIFIER UN CRÉNEAU
     ============================================================ -->
<?php if ($peutModifier): ?>
<div class="modal-overlay" id="modalCreneau">
    <div class="modal-content" style="max-width:550px;">
        <div class="modal-header">
            <h5><i class="fa-solid fa-clock"></i> <span id="modalCreneauTitre">Ajouter un créneau</span></h5>
            <button type="button" class="modal-close" id="btnCloseCreneau">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formCreneau" autocomplete="off">
                <input type="hidden" id="creneau_id" name="creneau_id" value="">
                <input type="hidden" id="creneau_classe_id" name="classe_id" value="<?= $classeId ?>">
                <input type="hidden" id="creneau_annee_id" name="annee_id" value="<?= $anneeId ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-calendar-day"></i> Jour <span class="required">*</span></label>
                        <select id="creneau_jour" name="jour" class="form-control" required>
                            <option value="">— Jour —</option>
                            <?php foreach ($jours as $j): ?>
                                <option value="<?= $j ?>"><?= $j ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-tag"></i> Type</label>
                        <select id="creneau_type" name="type_creneau" class="form-control">
                            <option value="cours">Cours</option>
                            <option value="recréation">Récréation / Pause</option>
                            <option value="cernafor">CERNAFOR</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-play"></i> Heure début <span class="required">*</span></label>
                        <input type="time" id="creneau_debut" name="heure_debut" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-stop"></i> Heure fin <span class="required">*</span></label>
                        <input type="time" id="creneau_fin" name="heure_fin" class="form-control" required>
                    </div>
                </div>

                <div id="creneauChampsCours">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-book-open"></i> Sous-matière</label>
                        <select id="creneau_matiere" name="matiere_id" class="form-control">
                            <option value="">— Sous-matière —</option>
                            <?php foreach ($matieres as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['libelle']) ?> (<?= e($m['groupe_libelle']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-chalkboard-user"></i> Enseignant</label>
                        <select id="creneau_enseignant" name="enseignant_id" class="form-control">
                            <option value="">— Enseignant —</option>
                            <?php foreach ($enseignants as $ens): ?>
                                <option value="<?= $ens['id'] ?>"><?= e($ens['prenom'] . ' ' . $ens['nom']) ?> (<?= e($ens['specialite']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="creneauChampsLibelle" style="display:none;">
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-pen"></i> Libellé</label>
                        <input type="text" id="creneau_libelle" name="libelle" class="form-control" placeholder="Ex: Récréation, CERNAFOR, Pause déjeuner...">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-primary" id="btnCancelCreneau"><i class="fa-solid fa-xmark"></i> Annuler</button>
            <button type="button" class="btn btn-primary" id="btnSaveCreneau"><i class="fa-solid fa-save"></i> Enregistrer</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- SCRIPT -->
<script>
var classeId = <?= $classeId ?>;
var anneeId = <?= $anneeId ?>;
var peutModifier = <?= $peutModifier ? 'true' : 'false' ?>;
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';