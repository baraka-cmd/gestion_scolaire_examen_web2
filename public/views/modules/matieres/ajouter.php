<?php
/**
 * Fichier : public/views/modules/matieres/ajouter.php
 * Rôle    : Formulaire d'ajout d'une matière (groupe ou sous-matière)
 * Système : Bulletin congolais
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('matieres.creer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'MatiereController.php';
$matiereController = new MatiereController();

// Récupérer les groupes pour le choix du parent
$groupes = $matiereController->getGroupes();

// Récupérer les classes actives pour l'association
$classesActives = $db->fetchAll(
    "SELECT id, nom, niveau, salle FROM classes 
     WHERE annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
     ORDER BY niveau"
);

// Paramètres globaux
$params = $db->fetchOne("SELECT * FROM parametres_ecole LIMIT 1");

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];
$oldValues      = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldValues = [
        'code'        => post('code', ''),
        'libelle'     => post('libelle', ''),
        'description' => post('description', ''),
        'maxima'      => post('maxima', '20'),
        'est_groupe'  => post('est_groupe', '0'),
        'parent_id'   => post('parent_id', ''),
    ];

    $code        = $oldValues['code'];
    $libelle     = $oldValues['libelle'];
    $description = $oldValues['description'];
    $maxima      = (int) $oldValues['maxima'];
    $estGroupe   = (int) $oldValues['est_groupe'];
    $parentId    = (int) $oldValues['parent_id'];
    $classesSelectionnees = $_POST['classes'] ?? [];

    // Validation
    if (isBlank($libelle)) {
        $errors['libelle'] = 'Le libellé est obligatoire.';
    } elseif (mb_strlen($libelle) < 2) {
        $errors['libelle'] = 'Minimum 2 caractères.';
    } elseif (mb_strlen($libelle) > 150) {
        $errors['libelle'] = 'Maximum 150 caractères.';
    } else {
        $existe = $db->fetchColumn(
            "SELECT COUNT(*) FROM matieres WHERE libelle = :libelle AND est_actif = 1",
            ['libelle' => trim($libelle)]
        );
        if ($existe > 0) $errors['libelle'] = 'Cette matière existe déjà.';
    }

    if (!isBlank($code)) {
        if (mb_strlen($code) > 20) {
            $errors['code'] = 'Maximum 20 caractères.';
        } else {
            $codeExiste = $db->fetchColumn(
                "SELECT COUNT(*) FROM matieres WHERE code = :code",
                ['code' => mb_strtoupper(trim($code))]
            );
            if ($codeExiste > 0) $errors['code'] = 'Ce code est déjà utilisé.';
        }
    }

    // Maxima obligatoire uniquement pour les sous-matières
    if (!$estGroupe) {
        if ($maxima < 1 || $maxima > 1000) {
            $errors['maxima'] = 'Le maxima doit être entre 1 et 1000.';
        }
    }

    // Si sous-matière, vérifier le parent
    if (!$estGroupe && $parentId <= 0) {
        $errors['parent_id'] = 'Veuillez sélectionner le groupe parent.';
    } elseif (!$estGroupe && $parentId > 0) {
        $parentExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM matieres WHERE id = :id AND est_groupe = 1 AND est_actif = 1",
            ['id' => $parentId]
        );
        if ($parentExiste == 0) $errors['parent_id'] = 'Groupe parent invalide.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Si groupe, maxima = NULL
            $maximaValue = $estGroupe ? null : $maxima;

            $matiereId = $db->insert(
                "INSERT INTO matieres (code, libelle, description, maxima, coefficient, est_groupe, parent_id, est_actif)
                 VALUES (:code, :libelle, :desc, :maxima, 1, :est_groupe, :parent_id, 1)",
                [
                    'code'       => !empty($code) ? mb_strtoupper(trim($code)) : null,
                    'libelle'    => formatNom($libelle),
                    'desc'       => $description ?: null,
                    'maxima'     => $maximaValue,
                    'est_groupe' => $estGroupe,
                    'parent_id'  => $estGroupe ? null : ($parentId > 0 ? $parentId : null)
                ]
            );

            // Si sous-matière, associer aux classes sélectionnées
            if (!$estGroupe && !empty($classesSelectionnees)) {
                foreach ($classesSelectionnees as $classeId) {
                    $classeId = (int) $classeId;
                    if ($classeId > 0) {
                        $existe = $db->fetchColumn(
                            "SELECT COUNT(*) FROM classe_matieres WHERE matiere_id = :mid AND classe_id = :cid",
                            ['mid' => $matiereId, 'cid' => $classeId]
                        );
                        if ($existe == 0) {
                            $db->query(
                                "INSERT INTO classe_matieres (matiere_id, classe_id, maxima_override, est_active) 
                                 VALUES (:mid, :cid, NULL, 1)",
                                ['mid' => $matiereId, 'cid' => $classeId]
                            );
                        }
                    }
                }
            }

            $db->commit();

            logActivity('Création', 'matieres', $matiereId, "Matière créée : {$libelle}");

            $typeMsg = $estGroupe ? 'Groupe' : 'Sous-matière';
            setFlash('success', "{$typeMsg} créé(e) avec succès.");
            redirect('/gestion_scolaire/matieres');

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Une erreur est survenue lors de la création.';
            error_log('Erreur création matière: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Ajouter une matière';
$pageIcon  = 'fa-plus';
$pageDescription = 'Créer un groupe ou une sous-matière avec son maxima';
$extraCSS = ['modules/matieres.css', 'modules/ajouter_matiere.css'];
$extraJS  = ['modules/ajouter_matiere.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/matieres">Matières</a>
    <span class="separator">/</span>
    <span class="current">Ajouter</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-plus-circle text-primary me-2"></i>Ajouter une matière
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Créez un groupe de matières ou une sous-matière avec son maxima.
        </p>
    </div>
    <a href="/gestion_scolaire/matieres" class="btn btn-outline-primary btn-sm">
        <i class="fa-solid fa-arrow-left"></i> Retour à la liste
    </a>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-error alert-dismissible">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($errorMessage) ?></span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<div class="add-matiere-grid">
    <!-- ============================================================
         FORMULAIRE
         ============================================================ -->
    <div class="matiere-form-card">
        <div class="matiere-form-header">
            <div class="matiere-form-icon">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <div>
                <h5>Nouvelle matière</h5>
                <p>Définissez le type, le libellé et le maxima</p>
            </div>
        </div>
        <div class="matiere-form-body">
            <form method="POST" action="" autocomplete="off" novalidate id="formAddMatiere">

                <!-- Type de matière -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-layer-group"></i> Type de matière <span class="required">*</span>
                    </label>
                    <div style="display:flex;gap:12px;">
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="est_groupe" value="1" id="type_groupe" 
                                   <?= ($oldValues['est_groupe'] ?? '') === '1' ? 'checked' : '' ?> 
                                   style="display:none;">
                            <div id="cardGroupe" style="padding:14px;border:2px solid var(--border-color);border-radius:12px;text-align:center;transition:all 0.3s ease;">
                                <i class="fa-solid fa-layer-group" style="font-size:1.5rem;color:var(--color-primary);display:block;margin-bottom:6px;"></i>
                                <strong style="font-size:0.85rem;">Groupe</strong>
                                <p style="font-size:0.7rem;color:var(--color-gray-500);margin:4px 0 0;">Ex: Français, Math...</p>
                            </div>
                        </label>
                        <label style="flex:1;cursor:pointer;">
                            <input type="radio" name="est_groupe" value="0" id="type_sous_matiere" 
                                   <?= ($oldValues['est_groupe'] ?? '') === '0' ? 'checked' : '' ?> 
                                   style="display:none;">
                            <div id="cardSousMatiere" style="padding:14px;border:2px solid var(--border-color);border-radius:12px;text-align:center;transition:all 0.3s ease;">
                                <i class="fa-solid fa-list-ul" style="font-size:1.5rem;color:#10b981;display:block;margin-bottom:6px;"></i>
                                <strong style="font-size:0.85rem;">Sous-matière</strong>
                                <p style="font-size:0.7rem;color:var(--color-gray-500);margin:4px 0 0;">Ex: Grammaire, Lecture...</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Groupe parent (affiché si sous-matière) -->
                <div class="form-group" id="parentGroup" style="display:<?= ($oldValues['est_groupe'] ?? '') === '0' ? 'block' : 'none' ?>;">
                    <label for="parent_id" class="form-label">
                        <i class="fa-solid fa-sitemap"></i> Groupe parent <span class="required">*</span>
                    </label>
                    <select id="parent_id" name="parent_id" class="form-control <?= isset($errors['parent_id']) ? 'is-invalid' : '' ?>">
                        <option value="">— Sélectionnez le groupe —</option>
                        <?php foreach ($groupes as $groupe): ?>
                            <option value="<?= $groupe['id'] ?>" <?= ($oldValues['parent_id'] ?? '') == $groupe['id'] ? 'selected' : '' ?>>
                                <?= e($groupe['libelle']) ?> <?= $groupe['code'] ? '(' . e($groupe['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['parent_id'])): ?>
                        <span class="invalid-feedback"><?= e($errors['parent_id']) ?></span>
                    <?php endif; ?>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> Le groupe auquel appartient cette sous-matière.
                    </small>
                </div>

                <hr class="form-divider">

                <!-- Libellé -->
                <div class="form-group">
                    <label for="libelle" class="form-label">
                        <i class="fa-solid fa-tag"></i> Libellé <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-book-open"></i></span>
                        <input type="text" id="libelle" name="libelle" 
                               class="form-control <?= isset($errors['libelle']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldValues['libelle'] ?? '') ?>" 
                               placeholder="Ex: Grammaire/Conjugaison/Analyse..." 
                               required maxlength="150" autofocus>
                    </div>
                    <?php if (isset($errors['libelle'])): ?>
                        <span class="invalid-feedback"><?= e($errors['libelle']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Code -->
                <div class="form-group">
                    <label for="code" class="form-label">
                        <i class="fa-solid fa-qrcode"></i> Code <span style="font-weight:400;color:var(--color-gray-400);">(optionnel)</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-hashtag"></i></span>
                        <input type="text" id="code" name="code" 
                               class="form-control <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldValues['code'] ?? '') ?>" 
                               placeholder="Ex: FRAN-GCA, MATH-NO..." 
                               maxlength="20" style="text-transform:uppercase;">
                    </div>
                    <?php if (isset($errors['code'])): ?>
                        <span class="invalid-feedback"><?= e($errors['code']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Maxima (affiché uniquement si sous-matière) -->
                <div class="form-group" id="maximaGroup" style="display:<?= ($oldValues['est_groupe'] ?? '') === '0' ? 'block' : 'none' ?>;">
                    <label for="maxima" class="form-label">
                        <i class="fa-solid fa-weight-scale"></i> Maxima <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-chart-simple"></i></span>
                        <input type="number" id="maxima" name="maxima" 
                               class="form-control <?= isset($errors['maxima']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldValues['maxima'] ?? '20') ?>" 
                               min="1" max="1000">
                    </div>
                    <?php if (isset($errors['maxima'])): ?>
                        <span class="invalid-feedback"><?= e($errors['maxima']) ?></span>
                    <?php endif; ?>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> 
                        <strong>Note maximale pour UNE période</strong> (ex: 20, 25, 50, 100). 
                        Le total trimestriel = Maxima × 3 (P1 + P2 + Examen).
                    </small>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="form-label">
                        <i class="fa-solid fa-align-left"></i> Description <span style="font-weight:400;color:var(--color-gray-400);">(optionnelle)</span>
                    </label>
                    <textarea id="description" name="description" class="form-control" rows="2"
                              placeholder="Description courte de la matière..."><?= e($oldValues['description'] ?? '') ?></textarea>
                </div>

                <!-- Classes (affiché uniquement si sous-matière) -->
                <div class="form-group" id="classesGroup" style="display:<?= ($oldValues['est_groupe'] ?? '') === '0' ? 'block' : 'none' ?>;">
                    <label class="form-label">
                        <i class="fa-solid fa-chalkboard"></i> Associer aux classes
                    </label>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;padding:12px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;max-height:200px;overflow-y:auto;">
                        <?php if (empty($classesActives)): ?>
                            <p style="font-size:0.8rem;color:var(--color-gray-500);">Aucune classe disponible.</p>
                        <?php else: ?>
                            <?php foreach ($classesActives as $classe): 
                                $checked = isset($_POST['classes']) && in_array($classe['id'], $_POST['classes']) ? 'checked' : '';
                            ?>
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:6px 12px;background:<?= $checked ? '#d1fae5' : '#fff' ?>;border-radius:8px;border:1px solid <?= $checked ? '#10b981' : '#e2e8f0' ?>;transition:all 0.2s ease;">
                                    <input type="checkbox" name="classes[]" value="<?= $classe['id'] ?>" <?= $checked ?>>
                                    <span style="font-size:0.8rem;"><?= e($classe['nom']) ?> (Niv. <?= $classe['niveau'] ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> Sélectionnez les classes où cette sous-matière sera enseignée. Vous pourrez modifier les maxima par classe plus tard.
                    </small>
                </div>
            </form>
        </div>
        <div class="matiere-form-footer">
            <a href="/gestion_scolaire/matieres" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="submit" form="formAddMatiere" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Créer la matière
            </button>
        </div>
    </div>

    <!-- ============================================================
         INFORMATIONS
         ============================================================ -->
    <div>
        <!-- Comprendre le maxima -->
        <div class="info-card-custom">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon blue">
                    <i class="fa-solid fa-calculator"></i>
                </div>
                <h6>Comprendre le Maxima</h6>
            </div>
            <div class="info-card-custom-body">
                <p style="font-size:0.82rem;color:var(--color-gray-600);margin-bottom:16px;">
                    Le <strong>maxima</strong> est la note maximale pour <strong>UNE période</strong> (P1 ou P2 ou Examen).
                    Il s'applique uniquement aux <strong>sous-matières</strong>.
                </p>
                <div class="formula-box">
                    <strong>Total Trimestre = Maxima × 3</strong>
                    <div style="font-size:0.78rem;color:#475569;margin-top:4px;">(P1 + P2 + Examen)</div>
                </div>
                <div class="formula-details mt-3">
                    <div class="formula-item">
                        <span class="formula-dot period1"></span>
                        <span><strong>P1</strong> : note sur Maxima</span>
                    </div>
                    <div class="formula-item">
                        <span class="formula-dot period2"></span>
                        <span><strong>P2</strong> : note sur Maxima</span>
                    </div>
                    <div class="formula-item">
                        <span class="formula-dot exam"></span>
                        <span><strong>Examen</strong> : note sur Maxima</span>
                    </div>
                </div>
                <hr style="margin:16px 0;border-color:var(--border-color);">
                <p style="font-size:0.78rem;color:var(--color-gray-500);">
                    <i class="fa-solid fa-lightbulb text-warning"></i> 
                    <strong>Exemple :</strong> Grammaire Max=20 → P1 sur 20, P2 sur 20, Examen sur 20 → Total sur 60.
                </p>
            </div>
        </div>

        <!-- Structure du bulletin -->
        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon green">
                    <i class="fa-solid fa-sitemap"></i>
                </div>
                <h6>Structure du bulletin</h6>
            </div>
            <div class="info-card-custom-body">
                <div style="font-size:0.8rem;color:var(--color-gray-600);line-height:1.8;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                        <span style="font-weight:700;color:#2c3e90;">📁 Groupe</span>
                        <span style="color:var(--color-gray-400);">→</span>
                        <span>ex: FRANÇAIS (pas de maxima)</span>
                    </div>
                    <div style="padding-left:20px;border-left:2px solid #e2e8f0;margin-left:8px;">
                        <div style="margin-bottom:4px;">
                            <span style="font-weight:600;">📄 Sous-matière</span>
                            <span style="color:var(--color-gray-400);">→</span>
                            <span>Grammaire (Max: 20)</span>
                        </div>
                        <div style="margin-bottom:4px;">
                            <span style="font-weight:600;">📄 Sous-matière</span>
                            <span style="color:var(--color-gray-400);">→</span>
                            <span>Lecture (Max: 10)</span>
                        </div>
                    </div>
                </div>
                <hr style="margin:16px 0;border-color:var(--border-color);">
                <p style="font-size:0.75rem;color:var(--color-gray-500);">
                    <i class="fa-solid fa-info-circle"></i> Le <strong>Groupe</strong> organise les sous-matières sans avoir de notes. 
                    Seules les <strong>sous-matières</strong> reçoivent des notes et ont un maxima.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>