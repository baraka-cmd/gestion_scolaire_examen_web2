<?php
/**
 * Fichier : public/views/modules/classes/modifier.php
 * Rôle    : Formulaire de modification d'une classe
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('classes.modifier');

global $db;

// Récupérer l'ID depuis l'URL
$classeId = (int) ($_GET['id'] ?? 0);

if ($classeId <= 0) {
    setFlash('error', 'ID classe invalide.');
    redirect('/gestion_scolaire/classes');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ClasseController.php';
$classeController = new ClasseController();

// Récupérer les informations de la classe
$classe = $classeController->getClasseDetail($classeId);

if (!$classe) {
    setFlash('error', 'Classe introuvable.');
    redirect('/gestion_scolaire/classes');
}

// Récupérer les données pour les formulaires
$anneesScolaires = $classeController->getAnneesScolaires();
$enseignantsDisponibles = $classeController->getEnseignantsDisponibles($classeId);

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom          = post('nom', '');
    $niveau       = (int) post('niveau', 0);
    $salle        = post('salle', '');
    $capaciteMax  = (int) post('capacite_max', 50);
    $anneeId      = (int) post('annee_scolaire_id', 0);
    $description  = post('description', '');
    $titulaireId  = (int) post('titulaire_id', 0);

    // Validation
    if (isBlank($nom)) {
        $errors['nom'] = 'Le nom de la classe est obligatoire.';
    } elseif (mb_strlen($nom) < 2) {
        $errors['nom'] = 'Minimum 2 caractères.';
    } elseif (mb_strlen($nom) > 50) {
        $errors['nom'] = 'Maximum 50 caractères.';
    } else {
        $existe = $db->fetchColumn(
            "SELECT COUNT(*) FROM classes WHERE nom = :nom AND annee_scolaire_id = :annee AND id != :id",
            ['nom' => trim($nom), 'annee' => $anneeId, 'id' => $classeId]
        );
        if ($existe > 0) $errors['nom'] = 'Ce nom existe déjà pour cette année scolaire.';
    }

    if ($niveau < 1 || $niveau > 12) {
        $errors['niveau'] = 'Niveau invalide (1-12).';
    }

    if (isBlank($salle)) {
        $errors['salle'] = 'La salle est obligatoire.';
    } elseif (mb_strlen($salle) > 20) {
        $errors['salle'] = 'Maximum 20 caractères.';
    } else {
        $salleExistante = $db->fetchColumn(
            "SELECT COUNT(*) FROM classes WHERE salle = :salle AND annee_scolaire_id = :annee AND id != :id",
            ['salle' => mb_strtoupper(trim($salle)), 'annee' => $anneeId, 'id' => $classeId]
        );
        if ($salleExistante > 0) $errors['salle'] = 'Cette salle est déjà attribuée.';
    }

    if ($capaciteMax < 1 || $capaciteMax > 200) {
        $errors['capacite_max'] = 'Capacité invalide (1-200 élèves).';
    } else {
        // Vérifier que la capacité n'est pas inférieure au nombre d'élèves actuels
        $nbEleves = (int) $db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
            ['cid' => $classeId]
        );
        if ($capaciteMax < $nbEleves) {
            $errors['capacite_max'] = "Capacité insuffisante ({$nbEleves} élèves actuellement inscrits).";
        }
    }

    if ($anneeId <= 0) {
        $errors['annee_scolaire_id'] = 'L\'année scolaire est obligatoire.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $db->update(
                "UPDATE classes 
                 SET nom = :nom, niveau = :niveau, salle = :salle, capacite_max = :capacite,
                     annee_scolaire_id = :annee, description = :description
                 WHERE id = :id",
                [
                    'nom'         => formatNom($nom),
                    'niveau'      => $niveau,
                    'salle'       => mb_strtoupper(trim($salle)),
                    'capacite'    => $capaciteMax,
                    'annee'       => $anneeId,
                    'description' => $description ?: null,
                    'id'          => $classeId
                ]
            );

            // Gérer le changement de titulaire
            if ($titulaireId > 0) {
                // Libérer l'ancien titulaire
                $db->update(
                    "UPDATE enseignants SET classe_id = NULL WHERE classe_id = :cid AND id != :eid",
                    ['cid' => $classeId, 'eid' => $titulaireId]
                );
                // Libérer le nouvel enseignant de son ancienne classe
                $db->update(
                    "UPDATE enseignants SET classe_id = NULL WHERE id = :eid",
                    ['eid' => $titulaireId]
                );
                // Assigner le nouveau titulaire
                $db->update(
                    "UPDATE enseignants SET classe_id = :cid WHERE id = :eid",
                    ['cid' => $classeId, 'eid' => $titulaireId]
                );
            } else {
                // Libérer le titulaire actuel
                $db->update(
                    "UPDATE enseignants SET classe_id = NULL WHERE classe_id = :cid",
                    ['cid' => $classeId]
                );
            }

            $db->commit();

            logActivity('Modification', 'classes', $classeId, "Classe modifiée : {$nom}");

            setFlash('success', 'Classe modifiée avec succès.');
            redirect('/gestion_scolaire/classes/detail/' . $classeId);

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Une erreur est survenue lors de la modification.';
            error_log('Erreur modification classe: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Modifier — ' . $classe['nom'];
$pageIcon  = 'fa-chalkboard';
$pageDescription = 'Modifier les informations de la classe';
$extraCSS = ['modules/classes.css', 'modules/ajouter_classe.css'];
$extraJS  = ['modules/ajouter_classe.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/classes">Classes</a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/classes/detail/<?= $classeId ?>"><?= e($classe['nom']) ?></a>
    <span class="separator">/</span>
    <span class="current">Modifier</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier la classe
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Modification de <strong><?= e($classe['nom']) ?></strong> — <?= e($classe['annee_libelle']) ?>
        </p>
    </div>
    <div class="d-flex gap-sm">
        <a href="/gestion_scolaire/classes/detail/<?= $classeId ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-eye"></i> Voir détails
        </a>
        <a href="/gestion_scolaire/classes" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-list"></i> Liste
        </a>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-error alert-dismissible">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= e($errorMessage) ?></span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
<?php endif; ?>

<div class="classe-form-card">
    <div class="classe-form-header">
        <div class="classe-form-icon">
            <i class="fa-solid fa-chalkboard"></i>
        </div>
        <div>
            <h5>Modifier : <?= e($classe['nom']) ?></h5>
            <p>Année <?= e($classe['annee_libelle']) ?> — <?= $classe['nb_eleves'] ?> élève(s) inscrit(s)</p>
        </div>
    </div>
    <div class="classe-form-body">
        <form method="POST" action="" autocomplete="off" novalidate id="formAddClasse">

            <!-- Section Informations générales -->
            <p class="form-section-title">
                <i class="fa-solid fa-info-circle"></i> Informations générales
            </p>
            <div class="form-row">
                <div class="form-group">
                    <label for="nom" class="form-label">
                        <i class="fa-solid fa-tag"></i> Nom de la classe <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-chalkboard"></i></span>
                        <input type="text" id="nom" name="nom"
                               class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>"
                               value="<?= e($_POST['nom'] ?? $classe['nom']) ?>"
                               placeholder="Ex: 1ère Primaire" required maxlength="50" autofocus>
                    </div>
                    <?php if (isset($errors['nom'])): ?>
                        <span class="invalid-feedback"><?= e($errors['nom']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="niveau" class="form-label">
                        <i class="fa-solid fa-layer-group"></i> Niveau <span class="required">*</span>
                    </label>
                    <select id="niveau" name="niveau" class="form-control <?= isset($errors['niveau']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez —</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i ?>" <?= ($_POST['niveau'] ?? $classe['niveau']) == $i ? 'selected' : '' ?>>
                                <?= $i ?>ème Primaire
                            </option>
                        <?php endfor; ?>
                    </select>
                    <?php if (isset($errors['niveau'])): ?>
                        <span class="invalid-feedback"><?= e($errors['niveau']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="form-divider">

            <!-- Section Organisation -->
            <p class="form-section-title">
                <i class="fa-solid fa-door-open"></i> Organisation
            </p>
            <div class="form-row">
                <div class="form-group">
                    <label for="salle" class="form-label">
                        <i class="fa-solid fa-door-closed"></i> Salle <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <input type="text" id="salle" name="salle"
                               class="form-control <?= isset($errors['salle']) ? 'is-invalid' : '' ?>"
                               value="<?= e($_POST['salle'] ?? $classe['salle']) ?>"
                               placeholder="Ex: Salle A1" required maxlength="20">
                    </div>
                    <?php if (isset($errors['salle'])): ?>
                        <span class="invalid-feedback"><?= e($errors['salle']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="capacite_max" class="form-label">
                        <i class="fa-solid fa-users"></i> Capacité maximale <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-user-group"></i></span>
                        <input type="number" id="capacite_max" name="capacite_max"
                               class="form-control <?= isset($errors['capacite_max']) ? 'is-invalid' : '' ?>"
                               value="<?= e($_POST['capacite_max'] ?? $classe['capacite_max']) ?>"
                               min="1" max="200" required>
                    </div>
                    <?php if (isset($errors['capacite_max'])): ?>
                        <span class="invalid-feedback"><?= e($errors['capacite_max']) ?></span>
                    <?php endif; ?>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> Actuellement <strong><?= $classe['nb_eleves'] ?></strong> élève(s) inscrit(s)
                    </small>
                </div>
            </div>

            <hr class="form-divider">

            <!-- Section Année scolaire -->
            <p class="form-section-title">
                <i class="fa-solid fa-calendar-alt"></i> Année scolaire
            </p>
            <div class="form-group">
                <label for="annee_scolaire_id" class="form-label">
                    <i class="fa-solid fa-calendar-check"></i> Année scolaire <span class="required">*</span>
                </label>
                <select id="annee_scolaire_id" name="annee_scolaire_id" class="form-control <?= isset($errors['annee_scolaire_id']) ? 'is-invalid' : '' ?>" required>
                    <option value="">— Sélectionnez une année —</option>
                    <?php foreach ($anneesScolaires as $annee): ?>
                        <option value="<?= $annee['id'] ?>" <?= ($_POST['annee_scolaire_id'] ?? $classe['annee_scolaire_id']) == $annee['id'] ? 'selected' : '' ?>>
                            <?= e($annee['libelle']) ?> <?= $annee['est_active'] ? '(Active)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['annee_scolaire_id'])): ?>
                    <span class="invalid-feedback"><?= e($errors['annee_scolaire_id']) ?></span>
                <?php endif; ?>
            </div>

            <hr class="form-divider">

            <!-- Section Titulaire -->
            <p class="form-section-title">
                <i class="fa-solid fa-user-tie"></i> Enseignant titulaire
            </p>
            <div class="form-group">
                <label for="titulaire_id" class="form-label">
                    <i class="fa-solid fa-chalkboard-user"></i> Titulaire
                </label>
                <select id="titulaire_id" name="titulaire_id" class="form-control">
                    <option value="">— Aucun titulaire —</option>
                    <?php if (!empty($enseignantsDisponibles)): ?>
                        <optgroup label="Enseignants disponibles">
                            <?php foreach ($enseignantsDisponibles as $ens): ?>
                                <option value="<?= $ens['id'] ?>" <?= ($_POST['titulaire_id'] ?? $classe['titulaire_id']) == $ens['id'] ? 'selected' : '' ?>>
                                    <?= e($ens['prenom'] . ' ' . $ens['nom']) ?> — <?= e($ens['matricule']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
                <small class="form-help">
                    <i class="fa-solid fa-info-circle"></i> 
                    <?php if ($classe['titulaire_id']): ?>
                        Titulaire actuel : <strong><?= e($classe['titulaire_nom']) ?></strong>
                    <?php else: ?>
                        Aucun titulaire assigné actuellement.
                    <?php endif; ?>
                </small>
            </div>

            <hr class="form-divider">

            <!-- Section Description -->
            <p class="form-section-title">
                <i class="fa-solid fa-align-left"></i> Description <span style="font-weight:400;color:var(--color-gray-400);">(optionnelle)</span>
            </p>
            <div class="form-group">
                <label for="description" class="form-label">
                    <i class="fa-solid fa-pencil"></i> Notes supplémentaires
                </label>
                <textarea id="description" name="description" class="form-control" rows="3"
                          placeholder="Informations complémentaires..."><?= e($_POST['description'] ?? $classe['description'] ?? '') ?></textarea>
            </div>

            <!-- Informations complémentaires -->
            <hr class="form-divider">
            <p class="form-section-title">
                <i class="fa-solid fa-circle-info"></i> Informations actuelles
            </p>
            <div class="profile-grid">
                <div class="profile-field">
                    <span class="profile-field-label">Année scolaire</span>
                    <span class="profile-field-value"><?= e($classe['annee_libelle']) ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Élèves inscrits</span>
                    <span class="profile-field-value"><strong><?= $classe['nb_eleves'] ?></strong> / <?= $classe['capacite_max'] ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Titulaire actuel</span>
                    <span class="profile-field-value"><?= e($classe['titulaire_nom'] ?: 'Aucun') ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Statut</span>
                    <span class="profile-field-value">
                        <?php if ($classe['est_actif']): ?>
                            <span class="badge badge-success">Active</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </form>
    </div>
    <div class="classe-form-footer">
        <a href="/gestion_scolaire/classes/detail/<?= $classeId ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-xmark"></i> Annuler
        </a>
        <button type="submit" form="formAddClasse" class="btn btn-primary">
            <i class="fa-solid fa-save"></i> Enregistrer les modifications
        </button>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>