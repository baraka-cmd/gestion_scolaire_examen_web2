<?php
/**
 * Fichier : public/views/modules/classes/ajouter.php
 * Rôle    : Formulaire d'ajout d'une nouvelle classe
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('classes.creer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ClasseController.php';
$classeController = new ClasseController();

// Récupérer les données pour les formulaires
$anneesScolaires = $classeController->getAnneesScolaires();
$enseignantsDisponibles = $classeController->getEnseignantsDisponibles();

// Récupérer l'année active
$anneeActiveId = $db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1") ?? 1;

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];
$oldValues      = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldValues = [
        'nom'               => post('nom', ''),
        'niveau'            => post('niveau', ''),
        'salle'             => post('salle', ''),
        'capacite_max'      => post('capacite_max', '50'),
        'annee_scolaire_id' => post('annee_scolaire_id', $anneeActiveId),
        'description'       => post('description', ''),
        'titulaire_id'      => post('titulaire_id', ''),
    ];

    $nom          = $oldValues['nom'];
    $niveau       = (int) $oldValues['niveau'];
    $salle        = $oldValues['salle'];
    $capaciteMax  = (int) $oldValues['capacite_max'];
    $anneeId      = (int) $oldValues['annee_scolaire_id'];
    $description  = $oldValues['description'];
    $titulaireId  = (int) $oldValues['titulaire_id'];

    // Validation
    if (isBlank($nom)) {
        $errors['nom'] = 'Le nom de la classe est obligatoire.';
    } elseif (mb_strlen($nom) < 2) {
        $errors['nom'] = 'Minimum 2 caractères.';
    } elseif (mb_strlen($nom) > 50) {
        $errors['nom'] = 'Maximum 50 caractères.';
    } else {
        $existe = $db->fetchColumn(
            "SELECT COUNT(*) FROM classes WHERE nom = :nom AND annee_scolaire_id = :annee",
            ['nom' => trim($nom), 'annee' => $anneeId]
        );
        if ($existe > 0) $errors['nom'] = 'Cette classe existe déjà pour cette année scolaire.';
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
            "SELECT COUNT(*) FROM classes WHERE salle = :salle AND annee_scolaire_id = :annee",
            ['salle' => mb_strtoupper(trim($salle)), 'annee' => $anneeId]
        );
        if ($salleExistante > 0) $errors['salle'] = 'Cette salle est déjà attribuée pour cette année.';
    }

    if ($capaciteMax < 1 || $capaciteMax > 200) {
        $errors['capacite_max'] = 'Capacité invalide (1-200 élèves).';
    }

    if ($anneeId <= 0) {
        $errors['annee_scolaire_id'] = 'L\'année scolaire est obligatoire.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $classeId = $db->insert(
                "INSERT INTO classes (nom, niveau, salle, capacite_max, annee_scolaire_id, description, est_actif)
                 VALUES (:nom, :niveau, :salle, :capacite, :annee, :description, 1)",
                [
                    'nom'         => formatNom($nom),
                    'niveau'      => $niveau,
                    'salle'       => mb_strtoupper(trim($salle)),
                    'capacite'    => $capaciteMax,
                    'annee'       => $anneeId,
                    'description' => $description ?: null
                ]
            );

            // Assigner le titulaire si fourni
            if ($titulaireId > 0) {
                // Libérer l'enseignant de son ancienne classe éventuelle
                $db->update("UPDATE enseignants SET classe_id = NULL WHERE id = :eid", ['eid' => $titulaireId]);
                // Assigner à la nouvelle classe
                $db->update("UPDATE enseignants SET classe_id = :classe WHERE id = :eid", ['classe' => $classeId, 'eid' => $titulaireId]);
            }

            $db->commit();

            logActivity('Création', 'classes', $classeId, "Classe créée : {$nom}");

            setFlash('success', 'Classe créée avec succès.');
            redirect('/gestion_scolaire/classes/detail/' . $classeId);

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Une erreur est survenue lors de la création de la classe.';
            error_log('Erreur création classe: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Ajouter une classe';
$pageIcon  = 'fa-chalkboard';
$pageDescription = 'Créer une nouvelle classe';
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
    <span class="current">Ajouter</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-chalkboard text-primary me-2"></i>Ajouter une classe
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Créez une nouvelle classe et assignez-lui un enseignant titulaire.
        </p>
    </div>
    <a href="/gestion_scolaire/classes" class="btn btn-outline-primary btn-sm">
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

<div class="classe-form-card">
    <div class="classe-form-header">
        <div class="classe-form-icon">
            <i class="fa-solid fa-chalkboard"></i>
        </div>
        <div>
            <h5>Nouvelle classe</h5>
            <p>Remplissez les informations pour créer une classe</p>
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
                               value="<?= e($oldValues['nom'] ?? '') ?>"
                               placeholder="Ex: 1ère Primaire, 6ème Secondaire..." required maxlength="50" autofocus>
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
                            <option value="<?= $i ?>" <?= ($oldValues['niveau'] ?? '') == $i ? 'selected' : '' ?>>
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
                               value="<?= e($oldValues['salle'] ?? '') ?>"
                               placeholder="Ex: Salle A1, B2..." required maxlength="20">
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
                               value="<?= e($oldValues['capacite_max'] ?? '50') ?>"
                               min="1" max="200" required>
                    </div>
                    <?php if (isset($errors['capacite_max'])): ?>
                        <span class="invalid-feedback"><?= e($errors['capacite_max']) ?></span>
                    <?php endif; ?>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> Entre 1 et 200 élèves
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
                        <option value="<?= $annee['id'] ?>" <?= ($oldValues['annee_scolaire_id'] ?? $anneeActiveId) == $annee['id'] ? 'selected' : '' ?>>
                            <?= e($annee['libelle']) ?> <?= $annee['est_active'] ? '(Année active)' : '' ?>
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
                    <i class="fa-solid fa-chalkboard-user"></i> Titulaire <span style="font-weight:400;color:var(--color-gray-400);">(optionnel)</span>
                </label>
                <select id="titulaire_id" name="titulaire_id" class="form-control">
                    <option value="">— Aucun titulaire pour le moment —</option>
                    <?php if (!empty($enseignantsDisponibles)): ?>
                        <optgroup label="Enseignants disponibles">
                            <?php foreach ($enseignantsDisponibles as $ens): ?>
                                <option value="<?= $ens['id'] ?>" <?= ($oldValues['titulaire_id'] ?? '') == $ens['id'] ? 'selected' : '' ?>>
                                    <?= e($ens['prenom'] . ' ' . $ens['nom']) ?> — <?= e($ens['matricule']) ?> <?= $ens['specialite'] ? '(' . e($ens['specialite']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
                <small class="form-help">
                    <i class="fa-solid fa-info-circle"></i> Vous pourrez assigner ou changer le titulaire plus tard.
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
                          placeholder="Informations complémentaires sur cette classe..."><?= e($oldValues['description'] ?? '') ?></textarea>
            </div>
        </form>
    </div>
    <div class="classe-form-footer">
        <a href="/gestion_scolaire/classes" class="btn btn-outline-primary">
            <i class="fa-solid fa-xmark"></i> Annuler
        </a>
        <button type="submit" form="formAddClasse" class="btn btn-primary">
            <i class="fa-solid fa-check"></i> Créer la classe
        </button>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>