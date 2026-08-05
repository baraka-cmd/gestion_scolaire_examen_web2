<?php
/**
 * Fichier : public/views/modules/enseignants/ma_classe.php
 * Rôle    : Liste des élèves de la classe de l'enseignant connecté
 * Système : École primaire congolaise — Espace Enseignant
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

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EleveController.php';
$eleveController = new EleveController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $eleveController->handleAjax();
}

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

// Récupérer les classes pour le filtre (une seule : la sienne)
$classes = $eleveController->getClasses();

// Statistiques pour la classe uniquement
$totalActifs = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
    ['cid' => $classeId]
);
$totalGarcons = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions i 
     INNER JOIN eleves e ON i.eleve_id = e.id 
     WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.sexe = 'M'",
    ['cid' => $classeId]
);
$totalFilles = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions i 
     INNER JOIN eleves e ON i.eleve_id = e.id 
     WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.sexe = 'F'",
    ['cid' => $classeId]
);
$nouvellesInscriptions = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND MONTH(date_inscription) = MONTH(CURDATE()) AND YEAR(date_inscription) = YEAR(CURDATE())",
    ['cid' => $classeId]
);

// Statistiques d'absences du mois pour cette classe
$absencesMois = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM absences a
     INNER JOIN inscriptions i ON a.eleve_id = i.eleve_id
     WHERE i.classe_id = :cid AND a.type_absence = 'Absence'
     AND MONTH(a.date_absence) = MONTH(CURDATE()) AND YEAR(a.date_absence) = YEAR(CURDATE())",
    ['cid' => $classeId]
);

$retardsMois = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM retards r
     INNER JOIN inscriptions i ON r.eleve_id = i.eleve_id
     WHERE i.classe_id = :cid
     AND MONTH(r.date_retard) = MONTH(CURDATE()) AND YEAR(r.date_retard) = YEAR(CURDATE())",
    ['cid' => $classeId]
);

// Notes saisies ce trimestre
$trimestreActif = (int) $db->fetchColumn("SELECT trimestre_actif_id FROM parametres_ecole LIMIT 1");
$notesSaisies = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM notes n
     INNER JOIN evaluations ev ON n.evaluation_id = ev.id
     WHERE ev.classe_id = :cid AND ev.trimestre_id = :tid",
    ['cid' => $classeId, 'tid' => $trimestreActif]
);

$totalSousMatieres = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM classe_matieres cm WHERE cm.classe_id = :cid AND cm.est_active = 1",
    ['cid' => $classeId]
);
$nbEvaluationsCrees = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM evaluations WHERE classe_id = :cid AND trimestre_id = :tid",
    ['cid' => $classeId, 'tid' => $trimestreActif]
);
$notesAttendues = $totalActifs * $totalSousMatieres * $nbEvaluationsCrees;

// Métadonnées
$pageTitle = 'Mes élèves — ' . e($maClasse['nom']);
$pageIcon  = 'fa-users';
$pageDescription = 'Classe : ' . e($maClasse['nom']) . ' — ' . e($maClasse['annee_libelle']) . ' — ' . $totalActifs . ' élève(s)';
$extraCSS = ['modules/eleves.css'];
$extraJS  = ['modules/eleves.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DU MODULE
     ============================================================ -->
<div class="eleves-header">
    <div class="eleves-header-left">
        <h4>
            <i class="fa-solid fa-users"></i> Mes élèves — <?= e($maClasse['nom']) ?>
        </h4>
        <p>
            <i class="fa-solid fa-chalkboard"></i> <?= e($maClasse['nom']) ?> (Niveau <?= $maClasse['niveau'] ?>) — 
            <i class="fa-solid fa-door-open"></i> Salle <?= e($maClasse['salle'] ?? '—') ?> — 
            <i class="fa-solid fa-calendar-alt"></i> <?= e($maClasse['annee_libelle']) ?>
        </p>
    </div>
    <div class="eleves-header-right">
        <a href="/gestion_scolaire/eleves/ajouter" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user-plus"></i> Nouvel élève
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnExport">
            <i class="fa-solid fa-file-export"></i> Exporter
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer la liste
        </button>
    </div>
</div>

<!-- ============================================================
     RÉSUMÉ DES EFFECTIFS
     ============================================================ -->
<div class="stats-grid mb-4">
    <div class="stat-card students">
        <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalActifs, 0, ',', ' ') ?></div>
            <div class="stat-label">Total élèves</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);"><i class="fa-solid fa-mars"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalGarcons, 0, ',', ' ') ?></div>
            <div class="stat-label">Garçons</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#ec4899,#f472b6);"><i class="fa-solid fa-venus"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalFilles, 0, ',', ' ') ?></div>
            <div class="stat-label">Filles</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#ef4444,#f87171);"><i class="fa-solid fa-calendar-xmark"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $absencesMois ?></div>
            <div class="stat-label">Absences ce mois</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);"><i class="fa-solid fa-clock"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $retardsMois ?></div>
            <div class="stat-label">Retards ce mois</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#34d399);"><i class="fa-solid fa-clipboard-check"></i></div>
        <div class="stat-content">
            <div class="stat-value"><?= $notesSaisies ?></div>
            <div class="stat-label">Notes saisies</div>
            <span class="stat-change neutral">Sur ~<?= $notesAttendues ?> attendues</span>
        </div>
    </div>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     BARRE DE FILTRES (classe pré-sélectionnée)
     ============================================================ -->
<div class="eleves-filters">
    <div class="filter-group">
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchEleves" class="search-input" 
                   placeholder="Rechercher par nom, prénom ou matricule...">
        </div>
    </div>
    <div class="filter-group">
        <label for="filterClasse"><i class="fa-solid fa-chalkboard"></i> Classe</label>
        <select id="filterClasse" disabled style="background:#f1f5f9;cursor:not-allowed;">
            <option value="<?= $maClasse['id'] ?>" selected><?= e($maClasse['nom']) ?> (Niv. <?= $maClasse['niveau'] ?>)</option>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterSexe"><i class="fa-solid fa-venus-mars"></i> Sexe</label>
        <select id="filterSexe">
            <option value="">Tous</option>
            <option value="M">Garçons</option>
            <option value="F">Filles</option>
        </select>
    </div>
    <div class="filter-group">
        <label for="filterStatut"><i class="fa-solid fa-toggle-on"></i> Statut</label>
        <select id="filterStatut">
            <option value="">Actifs uniquement</option>
            <option value="1">Actifs</option>
            <option value="0">Inactifs</option>
        </select>
    </div>
    <button type="button" class="btn-reset-filters" id="btnResetFilters">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
    </button>
</div>

<!-- ============================================================
     TABLEAU DES ÉLÈVES
     ============================================================ -->
<div class="eleves-table-card">
    <div class="table-header-info">
        <span><strong id="totalEleves">—</strong> élève(s) trouvé(s)</span>
        <span style="font-size:0.75rem;color:var(--color-gray-400);">
            <i class="fa-solid fa-info-circle"></i> Cliquez sur le nom pour voir la fiche complète de l'élève
        </span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Élève</th>
                    <th>Matricule</th>
                    <th>Sexe</th>
                    <th>Né(e) le</th>
                    <th>Âge</th>
                    <th>Téléphone</th>
                    <th>Statut</th>
                    <th style="width:140px;">Actions</th>
                </tr>
            </thead>
            <tbody id="tbodyEleves">
                <!-- Chargé dynamiquement par AJAX -->
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination" id="paginationEleves"></div>
    </div>
</div>

<!-- ============================================================
     SCRIPT : Forcer le filtre sur la classe de l'enseignant
     ============================================================ -->
<script>
// Surcharger la fonction de chargement pour forcer le filtre classe
var enseignantClasseId = <?= $classeId ?>;

// Intercepter le chargement initial pour forcer le filtre
document.addEventListener('DOMContentLoaded', function() {
    // Forcer le filtre classe
    var filterClasse = document.getElementById('filterClasse');
    if (filterClasse && enseignantClasseId > 0) {
        filterClasse.value = enseignantClasseId;
    }
    
    // Déclencher le chargement avec le filtre forcé
    if (typeof chargerEleves === 'function') {
        chargerEleves(1);
    }
});

// Surcharger getFiltres si la fonction existe
var originalGetFiltres = window.getFiltres;
window.getFiltres = function() {
    var filtres = originalGetFiltres ? originalGetFiltres() : {};
    filtres.classe_id = enseignantClasseId;
    return filtres;
};
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>