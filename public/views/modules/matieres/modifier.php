<?php
/**
 * Fichier : public/views/modules/matieres/modifier.php
 * Rôle    : Formulaire de modification d'une matière (groupe ou sous-matière)
 * Système : Bulletin congolais
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('matieres.modifier');

global $db;

// Récupérer l'ID
$matiereId = (int) ($_GET['id'] ?? 0);
if ($matiereId <= 0) {
    setFlash('error', 'ID matière invalide.');
    redirect('/gestion_scolaire/matieres');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'MatiereController.php';
$matiereController = new MatiereController();

// Récupérer la matière
$matiere = $matiereController->getMatiereDetail($matiereId);
if (!$matiere) {
    setFlash('error', 'Matière introuvable.');
    redirect('/gestion_scolaire/matieres');
}

// Récupérer les groupes pour le parent (si sous-matière)
$groupes = $matiereController->getGroupes();

// Récupérer les classes actives
$classesActives = $db->fetchAll(
    "SELECT c.id, c.nom, c.niveau, c.salle,
            cm.est_active, cm.maxima_override
     FROM classes c
     LEFT JOIN classe_matieres cm ON c.id = cm.classe_id AND cm.matiere_id = :mid
     WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
     ORDER BY c.niveau",
    ['mid' => $matiereId]
);

// Paramètres globaux
$params = $db->fetchOne("SELECT * FROM parametres_ecole LIMIT 1");

// Messages
$errorMessage = '';
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code        = post('code', '');
    $libelle     = post('libelle', '');
    $description = post('description', '');
    $maxima      = (int) post('maxima', 20);
    $parentId    = (int) post('parent_id', 0);
    $classesSelectionnees = $_POST['classes'] ?? [];

    if (isBlank($libelle)) $errors['libelle'] = 'Le libellé est obligatoire.';
    elseif (mb_strlen($libelle) < 2) $errors['libelle'] = 'Minimum 2 caractères.';
    else {
        $existe = $db->fetchColumn(
            "SELECT COUNT(*) FROM matieres WHERE libelle = :libelle AND id != :id AND est_actif = 1",
            ['libelle' => trim($libelle), 'id' => $matiereId]
        );
        if ($existe > 0) $errors['libelle'] = 'Cette matière existe déjà.';
    }

    if (!isBlank($code)) {
        $codeExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM matieres WHERE code = :code AND id != :id",
            ['code' => mb_strtoupper(trim($code)), 'id' => $matiereId]
        );
        if ($codeExiste > 0) $errors['code'] = 'Ce code est déjà utilisé.';
    }

    // Maxima obligatoire uniquement pour les sous-matières
    if (!$matiere['est_groupe']) {
        if ($maxima < 1 || $maxima > 1000) {
            $errors['maxima'] = 'Le maxima doit être entre 1 et 1000.';
        }
    }

    // Si sous-matière, vérifier le parent
    if (!$matiere['est_groupe'] && $parentId <= 0) {
        $errors['parent_id'] = 'Veuillez sélectionner le groupe parent.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $updateData = [
                'code'    => !empty($code) ? mb_strtoupper(trim($code)) : null,
                'libelle' => formatNom($libelle),
                'desc'    => $description ?: null,
                'maxima'  => $matiere['est_groupe'] ? null : $maxima,
                'id'      => $matiereId
            ];

            $sql = "UPDATE matieres 
                    SET code = :code, libelle = :libelle, description = :desc, maxima = :maxima";

            if (!$matiere['est_groupe'] && $parentId > 0) {
                $sql .= ", parent_id = :parent_id";
                $updateData['parent_id'] = $parentId;
            }

            $sql .= " WHERE id = :id";

            $db->update($sql, $updateData);

            // Si sous-matière, mettre à jour les associations aux classes
            if (!$matiere['est_groupe']) {
                // Récupérer les associations actuelles
                $associationsActuelles = $db->fetchAll(
                    "SELECT classe_id FROM classe_matieres WHERE matiere_id = :mid",
                    ['mid' => $matiereId]
                );
                $classesActuelles = array_column($associationsActuelles, 'classe_id');

                // Classes à ajouter (nouvelles sélections)
                foreach ($classesSelectionnees as $classeId) {
                    $classeId = (int) $classeId;
                    if ($classeId > 0 && !in_array($classeId, $classesActuelles)) {
                        $db->query(
                            "INSERT INTO classe_matieres (matiere_id, classe_id, maxima_override, est_active) 
                             VALUES (:mid, :cid, NULL, 1)",
                            ['mid' => $matiereId, 'cid' => $classeId]
                        );
                    } elseif ($classeId > 0 && in_array($classeId, $classesActuelles)) {
                        // Réactiver si désactivée
                        $db->update(
                            "UPDATE classe_matieres SET est_active = 1 WHERE matiere_id = :mid AND classe_id = :cid",
                            ['mid' => $matiereId, 'cid' => $classeId]
                        );
                    }
                }

                // Classes à désactiver (décochées)
                foreach ($classesActuelles as $classeId) {
                    if (!in_array($classeId, $classesSelectionnees)) {
                        // Vérifier si des évaluations existent
                        $nbEvaluations = (int) $db->fetchColumn(
                            "SELECT COUNT(*) FROM evaluations WHERE matiere_id = :mid AND classe_id = :cid",
                            ['mid' => $matiereId, 'cid' => $classeId]
                        );
                        if ($nbEvaluations > 0) {
                            // Désactiver plutôt que supprimer
                            $db->update(
                                "UPDATE classe_matieres SET est_active = 0 WHERE matiere_id = :mid AND classe_id = :cid",
                                ['mid' => $matiereId, 'cid' => $classeId]
                            );
                        } else {
                            $db->delete(
                                "DELETE FROM classe_matieres WHERE matiere_id = :mid AND classe_id = :cid",
                                ['mid' => $matiereId, 'cid' => $classeId]
                            );
                        }
                    }
                }
            }

            $db->commit();

            logActivity('Modification', 'matieres', $matiereId, "Matière modifiée : {$libelle}");
            setFlash('success', 'Matière modifiée avec succès.');
            redirect('/gestion_scolaire/matieres');

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Erreur lors de la modification.';
            error_log('Erreur modification matière: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Modifier — ' . $matiere['libelle'];
$pageIcon  = 'fa-pen-to-square';
$pageDescription = 'Modifier les informations de la matière';
$extraCSS = ['modules/matieres.css', 'modules/ajouter_matiere.css'];
$extraJS  = ['modules/ajouter_matiere.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/matieres">Matières</a>
    <span class="separator">/</span>
    <span class="current"><?= e($matiere['libelle']) ?></span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier la matière
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Modification de <strong><?= e($matiere['libelle']) ?></strong>
            — Type : <strong><?= $matiere['est_groupe'] ? 'Groupe' : 'Sous-matière' ?></strong>
            <?php if ($matiere['parent_libelle']): ?>
                — Groupe parent : <strong><?= e($matiere['parent_libelle']) ?></strong>
            <?php endif; ?>
            <?php if (!$matiere['est_groupe']): ?>
                — Maxima : <strong><?= $matiere['maxima'] ?></strong>
            <?php endif; ?>
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
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
                <h5>Modifier : <?= e($matiere['libelle']) ?></h5>
                <p>
                    <?php if ($matiere['est_groupe']): ?>
                        <span class="badge badge-primary">Groupe</span>
                    <?php else: ?>
                        <span class="badge badge-success">Sous-matière</span>
                    <?php endif; ?>
                    <?= $matiere['code'] ? ' — Code : ' . e($matiere['code']) : '' ?>
                </p>
            </div>
        </div>
        <div class="matiere-form-body">
            <form method="POST" action="" autocomplete="off" novalidate id="formAddMatiere">

                <!-- Type de matière (lecture seule) -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-layer-group"></i> Type de matière
                    </label>
                    <div style="display:flex;gap:12px;">
                        <div style="flex:1;padding:14px;border:2px solid <?= $matiere['est_groupe'] ? '#2c3e90' : 'var(--border-color)' ?>;border-radius:12px;text-align:center;background:<?= $matiere['est_groupe'] ? '#e8eaf6' : '#f8fafc' ?>;opacity:<?= $matiere['est_groupe'] ? '1' : '0.5' ?>;">
                            <i class="fa-solid fa-layer-group" style="font-size:1.5rem;color:<?= $matiere['est_groupe'] ? '#2c3e90' : '#94a3b8' ?>;display:block;margin-bottom:6px;"></i>
                            <strong style="font-size:0.85rem;">Groupe</strong>
                            <p style="font-size:0.7rem;color:var(--color-gray-500);margin:4px 0 0;">Pas de maxima, pas de notes</p>
                        </div>
                        <div style="flex:1;padding:14px;border:2px solid <?= !$matiere['est_groupe'] ? '#10b981' : 'var(--border-color)' ?>;border-radius:12px;text-align:center;background:<?= !$matiere['est_groupe'] ? '#f0fdf4' : '#f8fafc' ?>;opacity:<?= !$matiere['est_groupe'] ? '1' : '0.5' ?>;">
                            <i class="fa-solid fa-list-ul" style="font-size:1.5rem;color:<?= !$matiere['est_groupe'] ? '#10b981' : '#94a3b8' ?>;display:block;margin-bottom:6px;"></i>
                            <strong style="font-size:0.85rem;">Sous-matière</strong>
                            <p style="font-size:0.7rem;color:var(--color-gray-500);margin:4px 0 0;">Avec maxima et notes</p>
                        </div>
                    </div>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> Le type ne peut pas être modifié.
                    </small>
                </div>

                <!-- Groupe parent (affiché uniquement si sous-matière) -->
                <?php if (!$matiere['est_groupe']): ?>
                <div class="form-group" id="parentGroup">
                    <label for="parent_id" class="form-label">
                        <i class="fa-solid fa-sitemap"></i> Groupe parent <span class="required">*</span>
                    </label>
                    <select id="parent_id" name="parent_id" class="form-control <?= isset($errors['parent_id']) ? 'is-invalid' : '' ?>">
                        <option value="">— Sélectionnez le groupe —</option>
                        <?php foreach ($groupes as $groupe): ?>
                            <option value="<?= $groupe['id'] ?>" <?= ($_POST['parent_id'] ?? $matiere['parent_id']) == $groupe['id'] ? 'selected' : '' ?>>
                                <?= e($groupe['libelle']) ?> <?= $groupe['code'] ? '(' . e($groupe['code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['parent_id'])): ?>
                        <span class="invalid-feedback"><?= e($errors['parent_id']) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

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
                               value="<?= e($_POST['libelle'] ?? $matiere['libelle']) ?>" 
                               required maxlength="150" autofocus>
                    </div>
                    <?php if (isset($errors['libelle'])): ?>
                        <span class="invalid-feedback"><?= e($errors['libelle']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Code -->
                <div class="form-group">
                    <label for="code" class="form-label">
                        <i class="fa-solid fa-qrcode"></i> Code
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-hashtag"></i></span>
                        <input type="text" id="code" name="code" 
                               class="form-control <?= isset($errors['code']) ? 'is-invalid' : '' ?>"
                               value="<?= e($_POST['code'] ?? $matiere['code'] ?? '') ?>" 
                               maxlength="20" style="text-transform:uppercase;">
                    </div>
                    <?php if (isset($errors['code'])): ?>
                        <span class="invalid-feedback"><?= e($errors['code']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Maxima (affiché uniquement si sous-matière) -->
                <?php if (!$matiere['est_groupe']): ?>
                <div class="form-group" id="maximaGroup">
                    <label for="maxima" class="form-label">
                        <i class="fa-solid fa-weight-scale"></i> Maxima <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-chart-simple"></i></span>
                        <input type="number" id="maxima" name="maxima" 
                               class="form-control <?= isset($errors['maxima']) ? 'is-invalid' : '' ?>"
                               value="<?= e($_POST['maxima'] ?? $matiere['maxima']) ?>" 
                               min="1" max="1000">
                    </div>
                    <?php if (isset($errors['maxima'])): ?>
                        <span class="invalid-feedback"><?= e($errors['maxima']) ?></span>
                    <?php endif; ?>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> 
                        <strong>Note maximale pour UNE période.</strong> Total trimestre = Maxima × 3.
                    </small>
                </div>
                <?php endif; ?>

                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="form-label">
                        <i class="fa-solid fa-align-left"></i> Description
                    </label>
                    <textarea id="description" name="description" class="form-control" rows="2"
                              placeholder="Description courte..."><?= e($_POST['description'] ?? $matiere['description'] ?? '') ?></textarea>
                </div>

                <!-- Classes (affiché uniquement si sous-matière) -->
                <?php if (!$matiere['est_groupe']): ?>
                <div class="form-group" id="classesGroup">
                    <label class="form-label">
                        <i class="fa-solid fa-chalkboard"></i> Associer aux classes
                    </label>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;padding:12px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;max-height:200px;overflow-y:auto;">
                        <?php if (empty($classesActives)): ?>
                            <p style="font-size:0.8rem;color:var(--color-gray-500);">Aucune classe disponible.</p>
                        <?php else: ?>
                            <?php foreach ($classesActives as $classe): 
                                $checked = isset($_POST['classes']) 
                                    ? in_array($classe['id'], $_POST['classes']) 
                                    : ($classe['est_active'] == 1);
                                $bgColor = $checked ? '#d1fae5' : '#fff';
                                $borderColor = $checked ? '#10b981' : '#e2e8f0';
                            ?>
                                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:6px 12px;background:<?= $bgColor ?>;border-radius:8px;border:1px solid <?= $borderColor ?>;transition:all 0.2s ease;">
                                    <input type="checkbox" name="classes[]" value="<?= $classe['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                    <span style="font-size:0.8rem;">
                                        <?= e($classe['nom']) ?> (Niv. <?= $classe['niveau'] ?>)
                                        <?php if ($classe['maxima_override']): ?>
                                            — Max: <?= $classe['maxima_override'] ?>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> Décochez une classe pour la désactiver. Les évaluations existantes seront conservées.
                    </small>
                </div>
                <?php endif; ?>
            </form>
        </div>
        <div class="matiere-form-footer">
            <a href="/gestion_scolaire/matieres" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="submit" form="formAddMatiere" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Enregistrer les modifications
            </button>
        </div>
    </div>

    <!-- ============================================================
         INFORMATIONS
         ============================================================ -->
    <div>
        <!-- Impact du changement -->
        <?php if (!$matiere['est_groupe']): ?>
        <div class="info-card-custom">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon orange">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h6>Impact des modifications</h6>
            </div>
            <div class="info-card-custom-body">
                <p style="font-size:0.82rem;color:var(--color-gray-600);margin-bottom:12px;">
                    <i class="fa-solid fa-info-circle"></i> Modifier le <strong>maxima</strong> affectera le calcul des moyennes 
                    pour <strong>toutes les classes</strong> utilisant cette sous-matière.
                </p>
                <div style="background:#fef3c7;padding:12px;border-radius:8px;font-size:0.78rem;color:#92400e;">
                    <strong><i class="fa-solid fa-exclamation-triangle"></i> Attention :</strong> 
                    Si des notes ont déjà été saisies avec l'ancien maxima, elles ne seront pas recalculées automatiquement.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Associations actuelles -->
        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon green">
                    <i class="fa-solid fa-link"></i>
                </div>
                <h6><?= $matiere['est_groupe'] ? 'Sous-matières du groupe' : 'Associations aux classes' ?></h6>
            </div>
            <div class="info-card-custom-body">
                <?php if ($matiere['est_groupe']): ?>
                    <?php
                    $sousMatieres = $matiereController->getSousMatieresByGroupe($matiereId);
                    if (empty($sousMatieres)):
                    ?>
                        <p style="font-size:0.82rem;color:var(--color-gray-500);">Aucune sous-matière dans ce groupe.</p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:6px;">
                            <?php foreach ($sousMatieres as $sm): ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:#f8fafc;border-radius:8px;">
                                    <span style="font-size:0.82rem;"><?= e($sm['libelle']) ?></span>
                                    <span class="badge badge-info">Max: <?= $sm['maxima'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php
                    $classesLiees = array_filter($classesActives, fn($c) => $c['est_active'] == 1);
                    if (empty($classesLiees)):
                    ?>
                        <p style="font-size:0.82rem;color:var(--color-gray-500);">Cette sous-matière n'est associée à aucune classe.</p>
                    <?php else: ?>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            <?php foreach ($classesLiees as $classe): ?>
                                <span class="badge badge-info" style="font-size:0.75rem;padding:6px 12px;">
                                    <i class="fa-solid fa-chalkboard"></i> 
                                    <?= e($classe['nom']) ?> (Max: <?= $classe['maxima_override'] ?? $matiere['maxima'] ?>)
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>