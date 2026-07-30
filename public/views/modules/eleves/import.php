<?php
/**
 * Fichier : public/views/modules/eleves/import.php
 * Rôle    : Import CSV d'élèves avec drag & drop, guide et résultats
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('eleves.importer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'EleveController.php';
$eleveController = new EleveController();

// Récupérer les classes pour le mapping
$classes = $eleveController->getClasses();

// Messages
$successMessage = '';
$errorMessage   = '';
$importResults  = null;

// Traitement de l'import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichier_csv'])) {
    $file = $_FILES['fichier_csv'];
    $classeParDefaut = (int) post('classe_par_defaut', 0);

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Erreur lors du téléchargement du fichier. Code : ' . $file['error'];
    } else {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            $errorMessage = 'Seuls les fichiers CSV sont acceptés.';
        } else {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                $errorMessage = 'Impossible de lire le fichier.';
            } else {
                $ligne = 0;
                $importes = 0;
                $ignores = 0;
                $erreurs = [];
                $anneeActive = $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

                // Détecter le délimiteur (forcer le point-virgule si le fichier a un BOM UTF-8)
                $firstLine = fgets($handle);
                // Supprimer le BOM UTF-8 si présent
                $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
                rewind($handle);
                $delimiter = str_contains($firstLine, ';') ? ';' : ',';

                // Lire l'en-tête
                $headers = fgetcsv($handle, 0, $delimiter);
                // Nettoyer le BOM sur le premier header
                if ($headers && count($headers) > 0) {
                    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
                }
                if (!$headers) {
                    $errorMessage = 'Fichier CSV vide ou invalide.';
                    fclose($handle);
                } else {
                    // Normaliser les en-têtes
                    $headers = array_map(function($h) {
                        return trim(mb_strtolower(str_replace([' ', '-'], '_', $h)));
                    }, $headers);

                    // Colonnes obligatoires
                    $requiredCols = ['nom', 'prenom', 'date_naissance', 'sexe'];
                    $missingCols = array_diff($requiredCols, $headers);

                    if (!empty($missingCols)) {
                        $errorMessage = '<i class="fa-solid fa-triangle-exclamation"></i> Colonnes obligatoires manquantes : <strong>' . 
                                       implode(', ', $missingCols) . '</strong>.<br><br>' .
                                       'Colonnes trouvées : <code>' . implode(', ', $headers) . '</code><br><br>' .
                                       '<strong>Format attendu :</strong> <code>nom; prenom; date_naissance; sexe; classe; parent_nom; parent_telephone</code>';
                        fclose($handle);
                    } else {
                        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                            $ligne++;

                            if (count($data) < 4 || empty(trim(implode('', $data)))) {
                                $ignores++;
                                continue;
                            }

                            $row = array_combine($headers, array_pad($data, count($headers), ''));
                            if (!$row) {
                                $erreurs[] = ['ligne' => $ligne, 'message' => 'Format de ligne invalide'];
                                continue;
                            }

                            $nom           = trim($row['nom'] ?? '');
                            $prenom        = trim($row['prenom'] ?? '');
                            $dateNaissance = trim($row['date_naissance'] ?? '');
                            $sexe          = strtoupper(trim($row['sexe'] ?? ''));
                            $classeNom     = trim($row['classe'] ?? '');
                            $parentNom     = trim($row['parent_nom'] ?? '');
                            $parentTel     = trim($row['parent_telephone'] ?? '');

                            // Validation
                            $rowErrors = [];
                            if (empty($nom)) $rowErrors[] = 'Nom manquant';
                            if (empty($prenom)) $rowErrors[] = 'Prénom manquant';
                            if (empty($dateNaissance)) $rowErrors[] = 'Date de naissance manquante';
                            if (!in_array($sexe, ['M', 'F'])) $rowErrors[] = 'Sexe invalide (M/F attendu)';

                            if (!empty($rowErrors)) {
                                $erreurs[] = ['ligne' => $ligne, 'message' => implode(', ', $rowErrors)];
                                continue;
                            }

                            // Convertir la date
                            $dateObj = date_parse($dateNaissance);
                            if (!$dateObj || !checkdate($dateObj['month'], $dateObj['day'], $dateObj['year'])) {
                                $erreurs[] = ['ligne' => $ligne, 'message' => "Date invalide : {$dateNaissance}"];
                                continue;
                            }
                            $dateFormatee = sprintf('%04d-%02d-%02d', $dateObj['year'], $dateObj['month'], $dateObj['day']);

                            // Vérifier doublon
                            $doublon = $db->fetchColumn(
                                "SELECT COUNT(*) FROM eleves WHERE nom = :nom AND prenom = :prenom AND date_naissance = :dn",
                                ['nom' => formatNom($nom), 'prenom' => formatNom($prenom), 'dn' => $dateFormatee]
                            );
                            if ($doublon > 0) {
                                $erreurs[] = ['ligne' => $ligne, 'message' => "Doublon : {$nom} {$prenom} existe déjà"];
                                continue;
                            }

                            try {
                                $db->beginTransaction();

                                // Générer matricule
                                $matricule = 'EL-' . date('y') . '-' . str_pad(((int) $db->fetchColumn("SELECT MAX(id) FROM eleves") + 1), 5, '0', STR_PAD_LEFT);

                                // Insérer l'élève
                                $eleveId = $db->insert(
                                    "INSERT INTO eleves (matricule, nom, prenom, date_naissance, sexe, date_inscription, est_actif)
                                     VALUES (:mat, :nom, :prenom, :dn, :sexe, CURDATE(), 1)",
                                    [
                                        'mat'   => $matricule,
                                        'nom'   => formatNom($nom),
                                        'prenom'=> formatNom($prenom),
                                        'dn'    => $dateFormatee,
                                        'sexe'  => $sexe
                                    ]
                                );

                                // Trouver ou utiliser la classe par défaut
                                $classeId = null;
                                if (!empty($classeNom)) {
                                    $classeId = $db->fetchColumn(
                                        "SELECT id FROM classes WHERE nom = :nom AND annee_scolaire_id = :aid",
                                        ['nom' => $classeNom, 'aid' => $anneeActive]
                                    );
                                }
                                if (!$classeId && $classeParDefaut > 0) {
                                    $classeId = $classeParDefaut;
                                }

                                if ($classeId) {
                                    $db->query(
                                        "INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire_id, date_inscription, statut)
                                         VALUES (:eid, :cid, :aid, CURDATE(), 'Actif')",
                                        ['eid' => $eleveId, 'cid' => $classeId, 'aid' => $anneeActive]
                                    );
                                }

                                // Parent
                                if (!empty($parentNom) && !empty($parentTel)) {
                                    $parentExistant = $db->fetchOne(
                                        "SELECT id FROM parents_tuteurs WHERE telephone_principal = :tel LIMIT 1",
                                        ['tel' => $parentTel]
                                    );
                                    $parentId = $parentExistant ? (int) $parentExistant['id'] : $db->insert(
                                        "INSERT INTO parents_tuteurs (nom, prenom, lien, telephone_principal, est_contact_urgence)
                                         VALUES (:nom, '', 'Tuteur', :tel, 1)",
                                        ['nom' => formatNom($parentNom), 'tel' => $parentTel]
                                    );

                                    $dejaLie = $db->fetchColumn(
                                        "SELECT COUNT(*) FROM eleve_parent WHERE eleve_id = :eid AND parent_id = :pid",
                                        ['eid' => $eleveId, 'pid' => $parentId]
                                    );
                                    if ($dejaLie == 0) {
                                        $db->query(
                                            "INSERT INTO eleve_parent (eleve_id, parent_id, est_tuteur_principal) VALUES (:eid, :pid, 1)",
                                            ['eid' => $eleveId, 'pid' => $parentId]
                                        );
                                    }
                                }

                                $db->commit();
                                $importes++;

                            } catch (Exception $e) {
                                $db->rollback();
                                $erreurs[] = ['ligne' => $ligne, 'message' => 'Erreur technique : ' . $e->getMessage()];
                            }
                        }

                        fclose($handle);

                        $importResults = [
                            'total'    => $ligne,
                            'importes' => $importes,
                            'ignores'  => $ignores,
                            'erreurs'  => $erreurs
                        ];

                        if ($importes > 0) {
                            logActivity('Import', 'eleves', null, "Import CSV : {$importes} élève(s) importé(s)");
                            $successMessage = "{$importes} élève(s) importé(s) avec succès !";
                        }
                    }
                }
            }
        }
    }
}

$pageTitle = 'Importer des élèves';
$pageIcon  = 'fa-file-import';
$pageDescription = 'Importez des élèves depuis un fichier CSV';
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
    <span class="current">Importer</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-file-import text-primary me-2"></i>Importer des élèves
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            <i class="fa-solid fa-info-circle me-1"></i> Importez un fichier CSV contenant la liste des élèves à inscrire.
        </p>
    </div>
    <a href="/gestion_scolaire/eleves" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Retour à la liste
    </a>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-error alert-dismissible">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= $errorMessage ?></span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<?php if (!empty($successMessage)): ?>
    <div class="alert alert-success alert-dismissible">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= $successMessage ?></span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<div class="import-grid">
    <!-- ============================================================
         ZONE D'IMPORT
         ============================================================ -->
    <div class="import-card">
        <div class="import-card-header">
            <div class="import-card-icon">
                <i class="fa-solid fa-cloud-arrow-up"></i>
            </div>
            <div>
                <h5>Télécharger un fichier CSV</h5>
                <p>Sélectionnez votre fichier à importer</p>
            </div>
        </div>
        <div class="import-card-body">
            <form method="POST" action="" enctype="multipart/form-data" id="formImport">
                <!-- Zone de drop -->
                <div class="dropzone" id="dropZone">
                    <div class="dropzone-icon">
                        <i class="fa-solid fa-file-csv"></i>
                    </div>
                    <h5 class="dropzone-title">Glissez-déposez votre fichier ici</h5>
                    <p class="dropzone-text">ou cliquez pour sélectionner un fichier CSV</p>
                    <input type="file" name="fichier_csv" id="fichier_csv" accept=".csv" style="display:none;">
                    <button type="button" class="btn btn-outline-primary" id="btnBrowse">
                        <i class="fa-solid fa-folder-open"></i> Parcourir les fichiers
                    </button>
                    <div class="dropzone-file-info" id="fileInfo">
                        <i class="fa-solid fa-file-csv"></i>
                        <span id="fileName"></span>
                        <span class="file-size" id="fileSize"></span>
                    </div>
                </div>

                <!-- Classe par défaut -->
                <div class="classe-selector">
                    <label for="classe_par_defaut" class="classe-selector-label">
                        <i class="fa-solid fa-chalkboard"></i> Classe par défaut
                        <span class="optional">(si non spécifiée dans le CSV)</span>
                    </label>
                    <select id="classe_par_defaut" name="classe_par_defaut" class="form-control">
                        <option value="">— Aucune classe (élève non inscrit) —</option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?= $classe['id'] ?>">
                                <i class="fa-solid fa-chalkboard"></i> <?= e($classe['nom']) ?> — Niveau <?= $classe['niveau'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="import-card-footer">
            <button type="submit" form="formImport" class="btn btn-primary btn-lg" id="btnImport" disabled>
                <i class="fa-solid fa-file-import"></i> Lancer l'importation
            </button>
        </div>
    </div>

    <!-- ============================================================
         GUIDE D'IMPORT
         ============================================================ -->
    <div class="guide-card">
        <div class="guide-card-header">
            <div class="guide-card-icon">
                <i class="fa-solid fa-book"></i>
            </div>
            <h5>Guide d'importation</h5>
        </div>
        <div class="guide-card-body">
            <div class="guide-section">
                <h6 class="guide-section-title">
                    <i class="fa-solid fa-file-csv"></i> Format du fichier
                </h6>
                <p style="font-size:0.82rem;color:var(--color-gray-600);margin-bottom:12px;">
                    Le fichier doit être au format <strong>CSV</strong> avec séparateur <strong>point-virgule (;)</strong> ou <strong>virgule (,)</strong>.
                </p>
            </div>

            <div class="guide-section">
                <h6 class="guide-section-title">
                    <i class="fa-solid fa-circle-exclamation"></i> Colonnes obligatoires
                </h6>
                <table class="guide-table">
                    <thead>
                        <tr><th>Colonne</th><th>Description</th><th>Exemple</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>nom</td><td>Nom de famille</td><td>LUKUSA</td></tr>
                        <tr><td>prenom</td><td>Prénom</td><td>Emmanuel</td></tr>
                        <tr><td>date_naissance</td><td>Date (AAAA-MM-JJ)</td><td>2018-02-14</td></tr>
                        <tr><td>sexe</td><td>M ou F</td><td>M</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="guide-section">
                <h6 class="guide-section-title">
                    <i class="fa-solid fa-circle-check"></i> Colonnes optionnelles
                </h6>
                <table class="guide-table">
                    <thead>
                        <tr><th>Colonne</th><th>Description</th><th>Exemple</th></tr></thead>
                    <tbody>
                        <tr><td>classe</td><td>Nom de la classe</td><td>1ère Primaire</td></tr>
                        <tr><td>parent_nom</td><td>Nom du parent</td><td>LUKUSA Jean</td></tr>
                        <tr><td>parent_telephone</td><td>Téléphone</td><td>+243810000001</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Conseils -->
            <div class="tips-box">
                <div class="tips-header">
                    <i class="fa-solid fa-lightbulb"></i> Conseils importants
                </div>
                <ul class="tips-list">
                    <li><i class="fa-solid fa-check"></i> Vérifiez que les dates sont au format <strong>AAAA-MM-JJ</strong> ou <strong>JJ/MM/AAAA</strong></li>
                    <li><i class="fa-solid fa-check"></i> Le sexe doit être <strong>M</strong> (Masculin) ou <strong>F</strong> (Féminin)</li>
                    <li><i class="fa-solid fa-check"></i> Les doublons (même nom + prénom + date de naissance) sont automatiquement ignorés</li>
                    <li><i class="fa-solid fa-check"></i> Si la classe n'est pas trouvée, l'élève sera inscrit dans la classe par défaut</li>
                    <li><i class="fa-solid fa-check"></i> Les numéros de téléphone doivent être au format international</li>
                </ul>
            </div>

            <!-- Bouton modèle -->
            <div style="text-align:center;margin-top:20px;">
                <button type="button" class="btn-template" onclick="downloadTemplate()">
                    <i class="fa-solid fa-download"></i> Télécharger un modèle CSV
                </button>
                <p style="font-size:0.72rem;color:var(--color-gray-400);margin-top:6px;">
                    <i class="fa-solid fa-info-circle"></i> Utilisez ce modèle pour préparer votre fichier
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     RÉSULTATS DE L'IMPORT
     ============================================================ -->
<?php if ($importResults): ?>
<div class="results-card" id="importResults">
    <div class="results-header">
        <div class="results-icon <?= $importResults['importes'] > 0 ? 'success' : 'error' ?>">
            <i class="fa-solid fa-<?= $importResults['importes'] > 0 ? 'check-circle' : 'circle-exclamation' ?>"></i>
        </div>
        <div>
            <h5>Résultats de l'importation</h5>
            <p><?= $importResults['total'] ?> ligne(s) traitée(s) au total</p>
        </div>
    </div>
    <div class="results-body">
        <!-- Stats -->
        <div class="import-stats">
            <div class="import-stat-card success">
                <div class="import-stat-icon">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div>
                    <div class="import-stat-value"><?= $importResults['importes'] ?></div>
                    <div class="import-stat-label">Élève(s) importé(s)</div>
                </div>
            </div>
            <div class="import-stat-card info">
                <div class="import-stat-icon">
                    <i class="fa-solid fa-minus"></i>
                </div>
                <div>
                    <div class="import-stat-value"><?= $importResults['ignores'] ?></div>
                    <div class="import-stat-label">Ligne(s) ignorée(s)</div>
                </div>
            </div>
            <div class="import-stat-card <?= count($importResults['erreurs']) > 0 ? 'danger' : 'success' ?>">
                <div class="import-stat-icon">
                    <i class="fa-solid fa-<?= count($importResults['erreurs']) > 0 ? 'times' : 'check' ?>"></i>
                </div>
                <div>
                    <div class="import-stat-value"><?= count($importResults['erreurs']) ?></div>
                    <div class="import-stat-label">Erreur(s)</div>
                </div>
            </div>
        </div>

        <!-- Erreurs détaillées -->
        <?php if (!empty($importResults['erreurs'])): ?>
            <div class="errors-table-wrapper">
                <h6 class="errors-table-title">
                    <i class="fa-solid fa-list-ul"></i> Détail des erreurs
                </h6>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:100px;">Ligne</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($importResults['erreurs'] as $err): ?>
                                <tr>
                                    <td><span class="error-row-badge">Ligne <?= $err['ligne'] ?></span></td>
                                    <td><?= e($err['message']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
// ============================================================
// GESTION DU FICHIER
// ============================================================
const dropZone   = document.getElementById('dropZone');
const fileInput  = document.getElementById('fichier_csv');
const btnBrowse  = document.getElementById('btnBrowse');
const btnImport  = document.getElementById('btnImport');
const fileInfo   = document.getElementById('fileInfo');
const fileName   = document.getElementById('fileName');
const fileSize   = document.getElementById('fileSize');

if (btnBrowse) {
    btnBrowse.addEventListener('click', (e) => {
        e.stopPropagation();
        fileInput.click();
    });
}

if (dropZone) {
    dropZone.addEventListener('click', (e) => {
        if (e.target !== btnBrowse && !btnBrowse.contains(e.target)) {
            fileInput.click();
        }
    });

    // Drag & drop
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            updateFileInfo();
        }
    });
}

if (fileInput) {
    fileInput.addEventListener('change', updateFileInfo);
}

function updateFileInfo() {
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        fileName.textContent = file.name;
        fileSize.textContent = '(' + formatFileSize(file.size) + ')';
        fileInfo.classList.add('show');
        if (btnImport) btnImport.disabled = false;
    } else {
        fileInfo.classList.remove('show');
        if (btnImport) btnImport.disabled = true;
    }
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / 1048576).toFixed(1) + ' Mo';
}

// ============================================================
// TÉLÉCHARGEMENT DU MODÈLE
// ============================================================
function downloadTemplate() {
    const template = '\uFEFFnom;prenom;date_naissance;sexe;classe;parent_nom;parent_telephone\n' +
                     'LUKUSA;Emmanuel;2018-02-14;M;1ère Primaire;LUKUSA Jean;+243810000001\n' +
                     'KABONGO;Grâce;2018-05-20;F;1ère Primaire;KABONGO Martin;+243810000003\n' +
                     'MULONDA;Daniel;2018-08-10;M;1ère Primaire;MULONDA Pierre;+243810000005\n' +
                     'TSHIBOLA;Esther;2018-11-25;F;2ème Primaire;TSHIBOLA André;+243810000007\n' +
                     'KANKU;Joseph;2017-09-05;M;3ème Primaire;KANKU Jacques;+243810000009';

    const blob = new Blob([template], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'modele_import_eleves.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>