<?php
/**
 * Fichier : public/views/modules/eleves/export.php
 * Rôle    : Export CSV/Excel des élèves avec filtres
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('eleves.exporter');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EleveController.php';
$eleveController = new EleveController();

// Récupérer les classes pour le filtre
$classes = $eleveController->getClasses();

// Récupérer les filtres depuis l'URL
$classeId  = (int) ($_GET['classe_id'] ?? 0);
$sexe      = $_GET['sexe'] ?? '';
$statut    = $_GET['statut'] ?? '1';
$recherche = $_GET['recherche'] ?? '';
$format    = $_GET['format'] ?? 'csv';

// Construire la requête
$where = [];
$params = [];

$where[] = "i.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)";

if (!empty($recherche)) {
    $where[] = "(e.nom LIKE :r1 OR e.prenom LIKE :r2 OR e.matricule LIKE :r3)";
    $params['r1'] = "%{$recherche}%";
    $params['r2'] = "%{$recherche}%";
    $params['r3'] = "%{$recherche}%";
}

if ($classeId > 0) {
    $where[] = "i.classe_id = :classe";
    $params['classe'] = $classeId;
}

if (!empty($sexe)) {
    $where[] = "e.sexe = :sexe";
    $params['sexe'] = $sexe;
}

if ($statut !== '') {
    $where[] = "e.est_actif = :statut";
    $params['statut'] = (int) $statut;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Récupérer les élèves
$eleves = $db->fetchAll(
    "SELECT e.matricule, e.nom, e.prenom, 
            DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance,
            e.sexe, e.lieu_naissance, e.adresse, e.telephone, e.email,
            e.groupe_sanguin, e.allergies, e.maladies,
            DATE_FORMAT(e.date_inscription, '%d/%m/%Y') AS date_inscription,
            CASE WHEN e.est_actif = 1 THEN 'Actif' ELSE 'Inactif' END AS statut,
            c.nom AS classe_nom,
            GROUP_CONCAT(CONCAT(pt.nom, ' ', pt.prenom, ' (', pt.lien, ' - ', pt.telephone_principal, ')') SEPARATOR ' | ') AS parents
     FROM eleves e
     INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
     INNER JOIN classes c ON i.classe_id = c.id
     LEFT JOIN eleve_parent ep ON e.id = ep.eleve_id
     LEFT JOIN parents_tuteurs pt ON ep.parent_id = pt.id
     {$whereClause}
     GROUP BY e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe, 
              e.lieu_naissance, e.adresse, e.telephone, e.email,
              e.groupe_sanguin, e.allergies, e.maladies, e.date_inscription, 
              e.est_actif, c.nom
     ORDER BY c.nom, e.nom, e.prenom",
    $params
);

// Si export demandé
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    if ($format === 'csv') {
        exportCSV($eleves);
    } elseif ($format === 'excel') {
        exportExcel($eleves);
    }
}

// Si aperçu demandé (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'preview') {
    header('Content-Type: application/json; charset=utf-8');
    
    // Formater les données pour le JS
    $dataFormatted = array_map(function($e) {
        return [
            'matricule' => $e['matricule'],
            'nom'       => $e['nom'],
            'prenom'    => $e['prenom'],
            'classe'    => $e['classe_nom'],
            'sexe'      => $e['sexe'] === 'M' ? 'Masculin' : 'Féminin',
            'statut'    => $e['statut']
        ];
    }, $eleves);

    echo json_encode([
        'success' => true,
        'data'    => $dataFormatted,
        'total'   => count($dataFormatted)
    ]);
    exit;
}

/**
 * Export au format CSV
 */
function exportCSV(array $eleves): void
{
    $filename = 'export_eleves_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM UTF-8 pour Excel
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // En-têtes
    fputcsv($output, [
        'Matricule', 'Nom', 'Prénom', 'Date de naissance', 'Sexe',
        'Lieu de naissance', 'Adresse', 'Téléphone', 'Email',
        'Groupe sanguin', 'Allergies', 'Maladies',
        'Date inscription', 'Statut', 'Classe', 'Parents'
    ], ';');

    // Données
    foreach ($eleves as $eleve) {
        fputcsv($output, [
            $eleve['matricule'],
            $eleve['nom'],
            $eleve['prenom'],
            $eleve['date_naissance'],
            $eleve['sexe'] === 'M' ? 'Masculin' : 'Féminin',
            $eleve['lieu_naissance'] ?? '',
            $eleve['adresse'] ?? '',
            "'" . ($eleve['telephone'] ?? ''),
            $eleve['email'] ?? '',
            $eleve['groupe_sanguin'] ?? '',
            $eleve['allergies'] ?? '',
            $eleve['maladies'] ?? '',
            $eleve['date_inscription'],
            $eleve['statut'],
            $eleve['classe_nom'],
            "'" . ($eleve['parents'] ?? '')
        ], ';');
    }

    fclose($output);
    exit;
}

/**
 * Export au format Excel (CSV avec délimiteur tabulation)
 */
function exportExcel(array $eleves): void
{
    $filename = 'export_eleves_' . date('Y-m-d_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";

    echo '<table border="1">';
    echo '<tr style="background-color:#2c3e90;color:#fff;font-weight:bold;">';
    echo '<th>Matricule</th><th>Nom</th><th>Prénom</th><th>Date naissance</th><th>Sexe</th>';
    echo '<th>Lieu naissance</th><th>Adresse</th><th>Téléphone</th><th>Email</th>';
    echo '<th>Groupe sanguin</th><th>Allergies</th><th>Maladies</th>';
    echo '<th>Date inscription</th><th>Statut</th><th>Classe</th><th>Parents</th>';
    echo '</tr>';

    foreach ($eleves as $eleve) {
        echo '<tr>';
        echo '<td>' . e($eleve['matricule']) . '</td>';
        echo '<td>' . e($eleve['nom']) . '</td>';
        echo '<td>' . e($eleve['prenom']) . '</td>';
        echo '<td>' . e($eleve['date_naissance']) . '</td>';
        echo '<td>' . ($eleve['sexe'] === 'M' ? 'Masculin' : 'Féminin') . '</td>';
        echo '<td>' . e($eleve['lieu_naissance'] ?? '') . '</td>';
        echo '<td>' . e($eleve['adresse'] ?? '') . '</td>';
        echo '<td style="vnd.ms-excel.numberformat:@">' . e($eleve['telephone'] ?? '') . '</td>';
        echo '<td>' . e($eleve['email'] ?? '') . '</td>';
        echo '<td>' . e($eleve['groupe_sanguin'] ?? '') . '</td>';
        echo '<td>' . e($eleve['allergies'] ?? '') . '</td>';
        echo '<td>' . e($eleve['maladies'] ?? '') . '</td>';
        echo '<td>' . e($eleve['date_inscription']) . '</td>';
        echo '<td>' . e($eleve['statut']) . '</td>';
        echo '<td>' . e($eleve['classe_nom']) . '</td>';
        echo '<td style="mso-number-format:\@;">' . e($eleve['parents'] ?? '') . '</td>';
        echo '</tr>';
    }

    echo '</table>';
    exit;
}

$pageTitle = 'Exporter les élèves';
$pageIcon  = 'fa-file-export';
$pageDescription = 'Exportez la liste des élèves au format CSV ou Excel';
$extraCSS = ['modules/import_eleve.css'];
$extraJS  = [];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/eleves">Élèves</a>
    <span class="separator">/</span>
    <span class="current">Exporter</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-file-export text-primary me-2"></i>Exporter les élèves
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            <i class="fa-solid fa-info-circle me-1"></i> Filtrez et exportez la liste des élèves.
        </p>
    </div>
    <a href="/gestion_scolaire/eleves" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Retour à la liste
    </a>
</div>

<div class="import-grid">
    <!-- ============================================================
         FILTRES D'EXPORT
         ============================================================ -->
    <div class="import-card">
        <div class="import-card-header">
            <div class="import-card-icon">
                <i class="fa-solid fa-filter"></i>
            </div>
            <div>
                <h5>Filtres d'export</h5>
                <p>Sélectionnez les critères</p>
            </div>
        </div>
        <div class="import-card-body">
            <form method="GET" action="" id="formExport">
                <input type="hidden" name="action" value="export">

                <div class="form-group">
                    <label for="recherche" class="form-label">
                        <i class="fa-solid fa-magnifying-glass"></i> Recherche
                    </label>
                    <input type="text" id="recherche" name="recherche" class="form-control"
                           value="<?= e($recherche) ?>" placeholder="Nom, prénom ou matricule...">
                </div>

                <div class="form-group">
                    <label for="classe_id" class="form-label">
                        <i class="fa-solid fa-chalkboard"></i> Classe
                    </label>
                    <select id="classe_id" name="classe_id" class="form-control">
                        <option value="">Toutes les classes</option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?= $classe['id'] ?>" <?= $classeId === (int) $classe['id'] ? 'selected' : '' ?>>
                                <?= e($classe['nom']) ?> — Niveau <?= $classe['niveau'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sexe" class="form-label">
                            <i class="fa-solid fa-venus-mars"></i> Sexe
                        </label>
                        <select id="sexe" name="sexe" class="form-control">
                            <option value="">Tous</option>
                            <option value="M" <?= $sexe === 'M' ? 'selected' : '' ?>>Garçons</option>
                            <option value="F" <?= $sexe === 'F' ? 'selected' : '' ?>>Filles</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="statut" class="form-label">
                            <i class="fa-solid fa-toggle-on"></i> Statut
                        </label>
                        <select id="statut" name="statut" class="form-control">
                            <option value="1" <?= $statut === '1' ? 'selected' : '' ?>>Actifs</option>
                            <option value="0" <?= $statut === '0' ? 'selected' : '' ?>>Inactifs</option>
                            <option value="" <?= $statut === '' ? 'selected' : '' ?>>Tous</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="format" class="form-label">
                        <i class="fa-solid fa-file"></i> Format d'export
                    </label>
                    <select id="format" name="format" class="form-control">
                        <option value="csv" <?= $format === 'csv' ? 'selected' : '' ?>>
                            <i class="fa-solid fa-file-csv"></i> CSV (séparateur point-virgule)
                        </option>
                        <option value="excel" <?= $format === 'excel' ? 'selected' : '' ?>>
                            <i class="fa-solid fa-file-excel"></i> Excel (.xls)
                        </option>
                    </select>
                </div>
            </form>
        </div>
        <div class="import-card-footer">
            <button type="button" class="btn btn-outline-primary" id="btnPreview">
                <i class="fa-solid fa-eye"></i> Aperçu
            </button>
            <button type="button" class="btn btn-primary" id="btnExport">
                <i class="fa-solid fa-download"></i> Télécharger l'export
            </button>
        </div>
    </div>

    <!-- ============================================================
         GUIDE D'EXPORT
         ============================================================ -->
    <div class="guide-card">
        <div class="guide-card-header">
            <div class="guide-card-icon">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <h5>Informations d'export</h5>
        </div>
        <div class="guide-card-body">
            <div class="guide-section">
                <h6 class="guide-section-title">
                    <i class="fa-solid fa-file-csv"></i> Format CSV
                </h6>
                <p style="font-size:0.82rem;color:var(--color-gray-600);margin-bottom:12px;">
                    Fichier texte avec séparateur <strong>point-virgule (;)</strong>. Compatible avec Excel, Google Sheets et LibreOffice Calc.
                </p>
            </div>

            <div class="guide-section">
                <h6 class="guide-section-title">
                    <i class="fa-solid fa-file-excel"></i> Format Excel
                </h6>
                <p style="font-size:0.82rem;color:var(--color-gray-600);margin-bottom:12px;">
                    Fichier <strong>.xls</strong> avec mise en forme (tableau avec bordures). Ouverture directe dans Microsoft Excel.
                </p>
            </div>

            <div class="guide-section">
                <h6 class="guide-section-title">
                    <i class="fa-solid fa-list-check"></i> Colonnes exportées
                </h6>
                <table class="guide-table">
                    <thead>
                        <tr><th>#</th><th>Colonne</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>1</td><td>Matricule</td></tr>
                        <tr><td>2</td><td>Nom</td></tr>
                        <tr><td>3</td><td>Prénom</td></tr>
                        <tr><td>4</td><td>Date de naissance</td></tr>
                        <tr><td>5</td><td>Sexe</td></tr>
                        <tr><td>6</td><td>Lieu de naissance</td></tr>
                        <tr><td>7</td><td>Adresse</td></tr>
                        <tr><td>8</td><td>Téléphone</td></tr>
                        <tr><td>9</td><td>Email</td></tr>
                        <tr><td>10</td><td>Groupe sanguin</td></tr>
                        <tr><td>11</td><td>Allergies</td></tr>
                        <tr><td>12</td><td>Maladies</td></tr>
                        <tr><td>13</td><td>Date inscription</td></tr>
                        <tr><td>14</td><td>Statut</td></tr>
                        <tr><td>15</td><td>Classe</td></tr>
                        <tr><td>16</td><td>Parents</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Résumé -->
            <div class="tips-box">
                <div class="tips-header">
                    <i class="fa-solid fa-lightbulb"></i> Conseils
                </div>
                <ul class="tips-list">
                    <li><i class="fa-solid fa-check"></i> Utilisez les filtres pour affiner votre export</li>
                    <li><i class="fa-solid fa-check"></i> Le format <strong>CSV</strong> est recommandé pour l'import dans d'autres logiciels</li>
                    <li><i class="fa-solid fa-check"></i> Le format <strong>Excel</strong> est idéal pour une consultation directe</li>
                    <li><i class="fa-solid fa-check"></i> Les données des parents sont regroupées dans une seule colonne</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     APERÇU DES DONNÉES
     ============================================================ -->
<div class="card mt-4" id="previewCard" style="display:none;">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-header-icon blue">
                <i class="fa-solid fa-table-list"></i>
            </div>
            <div>
                <div class="card-header-title">Aperçu des données</div>
                <div class="card-header-subtitle">
                    <span id="previewCount">0</span> élève(s) trouvé(s) avec ces filtres
                </div>
            </div>
        </div>
    </div>
    <div class="card-body no-padding">
        <div class="table-responsive">
            <table class="table" id="previewTable">
                <thead>
                    <tr>
                        <th>Matricule</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Classe</th>
                        <th>Sexe</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody id="previewBody">
                    <!-- Rempli par JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// ============================================================
// APERÇU DES DONNÉES
// ============================================================
const btnPreview = document.getElementById('btnPreview');
const previewCard = document.getElementById('previewCard');

if (btnPreview) {
    btnPreview.addEventListener('click', () => {
        const formData = new FormData(document.getElementById('formExport'));
        formData.delete('action');
        formData.append('action', 'preview');

        const params = new URLSearchParams(formData);
        
        fetch('/gestion_scolaire/eleves/export?' + params.toString())
            .then(res => res.json())
            .then(data => {
                const previewBody = document.getElementById('previewBody');
                previewBody.innerHTML = '';
                
                if (data.success && data.data.length > 0) {
                    data.data.forEach(row => {
                        previewBody.innerHTML += 
                            '<tr>' +
                                '<td><span class="badge badge-primary">' + escapeHtml(row.matricule) + '</span></td>' +
                                '<td><strong>' + escapeHtml(row.nom) + '</strong></td>' +
                                '<td>' + escapeHtml(row.prenom) + '</td>' +
                                '<td>' + escapeHtml(row.classe || '—') + '</td>' +
                                '<td>' + escapeHtml(row.sexe) + '</td>' +
                                '<td>' + (row.statut === 'Actif' 
                                    ? '<span class="badge badge-success">Actif</span>' 
                                    : '<span class="badge badge-danger">Inactif</span>') + '</td>' +
                            '</tr>';
                    });
                    document.getElementById('previewCount').textContent = data.total;
                } else {
                    previewBody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Aucun élève trouvé</p></div></td></tr>';
                    document.getElementById('previewCount').textContent = '0';
                }
                
                previewCard.style.display = 'block';
                previewCard.scrollIntoView({ behavior: 'smooth' });
            })
            .catch(err => {
                console.error('Erreur aperçu:', err);
                showToast('Erreur lors du chargement de l\'aperçu.', 'error');
            });
    });
}

// ============================================================
// BOUTON EXPORT
// ============================================================
const btnExport = document.getElementById('btnExport');

if (btnExport) {
    btnExport.addEventListener('click', () => {
        const formData = new FormData(document.getElementById('formExport'));
        formData.append('action', 'export');
        
        const params = new URLSearchParams(formData);
        window.location.href = '/gestion_scolaire/eleves/export?' + params.toString();
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type) {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    }
}
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>