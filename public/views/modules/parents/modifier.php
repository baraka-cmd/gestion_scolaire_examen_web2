<?php
/**
 * Fichier : public/views/modules/parents/modifier.php
 * Rôle    : Formulaire de modification d'un parent/tuteur
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('parents.modifier');

global $db;

// Récupérer l'ID
$parentId = (int) ($_GET['id'] ?? 0);
if ($parentId <= 0) {
    setFlash('error', 'ID parent invalide.');
    redirect('/gestion_scolaire/parents');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'ParentController.php';
$parentController = new ParentController();

// Récupérer le parent
$parent = $parentController->getParentDetail($parentId);
if (!$parent) {
    setFlash('error', 'Parent introuvable.');
    redirect('/gestion_scolaire/parents');
}

// Récupérer les enfants liés
$enfantsLies = $parentController->getEnfantsByParent($parentId);

// Récupérer les élèves disponibles (non liés à ce parent)
$elevesDisponibles = $db->fetchAll(
    "SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe,
            c.nom AS classe_nom
     FROM eleves e
     LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
     LEFT JOIN classes c ON i.classe_id = c.id
     WHERE e.est_actif = 1
     AND e.id NOT IN (SELECT eleve_id FROM eleve_parent WHERE parent_id = :pid)
     ORDER BY e.nom, e.prenom",
    ['pid' => $parentId]
);

// Liens de parenté
$liens = $parentController->getLiens();

// Messages
$errorMessage = '';
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom              = post('nom', '');
    $prenom           = post('prenom', '');
    $lien             = post('lien', '');
    $profession       = post('profession', '');
    $telephone        = post('telephone_principal', '');
    $telephone2       = post('telephone_secondaire', '');
    $email            = post('email', '');
    $adresse          = post('adresse', '');
    $estUrgence       = (int) post('est_contact_urgence', 1);
    $elevesLies       = $_POST['eleves_lies'] ?? [];

    // Validation
    if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
    elseif (mb_strlen($nom) < 2) $errors['nom'] = 'Minimum 2 caractères.';

    if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
    elseif (mb_strlen($prenom) < 2) $errors['prenom'] = 'Minimum 2 caractères.';

    if (isBlank($telephone)) $errors['telephone_principal'] = 'Le téléphone est obligatoire.';
    elseif (!isValidPhone($telephone)) $errors['telephone_principal'] = 'Format invalide.';
    else {
        $telExiste = $db->fetchColumn(
            "SELECT COUNT(*) FROM parents_tuteurs WHERE telephone_principal = :tel AND id != :id",
            ['tel' => $telephone, 'id' => $parentId]
        );
        if ($telExiste > 0) $errors['telephone_principal'] = 'Ce numéro est déjà utilisé.';
    }

    if (!isBlank($email) && !isValidEmail($email)) $errors['email'] = 'Email invalide.';
    if (!isBlank($telephone2) && !isValidPhone($telephone2)) $errors['telephone_secondaire'] = 'Format invalide.';
    if (isBlank($lien)) $errors['lien'] = 'Le lien est obligatoire.';

    if (empty($errors)) {
        try {
            $db->update(
                "UPDATE parents_tuteurs 
                 SET nom = :nom, prenom = :prenom, lien = :lien, profession = :prof,
                     telephone_principal = :tel1, telephone_secondaire = :tel2,
                     email = :email, adresse = :adresse, est_contact_urgence = :urgence
                 WHERE id = :id",
                [
                    'nom'      => formatNom($nom),
                    'prenom'   => formatNom($prenom),
                    'lien'     => $lien,
                    'prof'     => $profession ?: null,
                    'tel1'     => $telephone,
                    'tel2'     => $telephone2 ?: null,
                    'email'    => $email ?: null,
                    'adresse'  => $adresse ?: null,
                    'urgence'  => $estUrgence,
                    'id'       => $parentId
                ]
            );

            // Mettre à jour les liaisons élèves
            // Supprimer les anciennes liaisons
            $db->delete("DELETE FROM eleve_parent WHERE parent_id = :pid", ['pid' => $parentId]);

            // Recréer les liaisons
            if (!empty($elevesLies)) {
                foreach ($elevesLies as $index => $eleveId) {
                    $eleveId = (int) trim($eleveId);
                    if ($eleveId > 0) {
                        $db->query(
                            "INSERT INTO eleve_parent (eleve_id, parent_id, est_tuteur_principal) 
                             VALUES (:eid, :pid, :principal)",
                            ['eid' => $eleveId, 'pid' => $parentId, 'principal' => $index === 0 ? 1 : 0]
                        );
                    }
                }
            }

            logActivity('Modification', 'parents_tuteurs', $parentId, "Parent modifié : {$nom} {$prenom}");
            setFlash('success', 'Parent modifié avec succès.');
            redirect('/gestion_scolaire/parents/fiche/' . $parentId);

        } catch (Exception $e) {
            $errorMessage = 'Erreur lors de la modification.';
            error_log('Erreur modification parent: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Modifier — ' . $parent['prenom'] . ' ' . $parent['nom'];
$pageIcon  = 'fa-pen-to-square';
$pageDescription = 'Modifier les informations du parent';
$extraCSS = ['modules/parents.css', 'modules/ajouter_parent.css'];
$extraJS  = ['modules/ajouter_parent.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/parents">Parents</a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/parents/fiche/<?= $parentId ?>"><?= e($parent['prenom'] . ' ' . $parent['nom']) ?></a>
    <span class="separator">/</span>
    <span class="current">Modifier</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier le parent
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            <strong><?= e($parent['prenom'] . ' ' . $parent['nom']) ?></strong> — 
            <?= e($parent['telephone_principal']) ?> — 
            <?= $parent['nb_enfants'] ?> enfant(s) lié(s)
        </p>
    </div>
    <div class="d-flex gap-sm">
        <a href="/gestion_scolaire/parents/fiche/<?= $parentId ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-eye"></i> Voir fiche
        </a>
        <a href="/gestion_scolaire/parents" class="btn btn-outline-primary btn-sm">
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

<form method="POST" action="" autocomplete="off" novalidate id="formAddParent">

    <!-- SECTION 1 : IDENTITÉ -->
    <div class="parent-form-card mb-4">
        <div class="parent-form-header">
            <div class="parent-form-icon"><i class="fa-solid fa-id-card"></i></div>
            <div><h5>Identité du parent</h5><p>Informations personnelles</p></div>
        </div>
        <div class="parent-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="nom" class="form-label"><i class="fa-solid fa-user"></i> Nom <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="nom" name="nom" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['nom'] ?? $parent['nom']) ?>" required maxlength="100" autofocus>
                    </div>
                    <?php if (isset($errors['nom'])): ?><span class="invalid-feedback"><?= e($errors['nom']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="prenom" class="form-label"><i class="fa-solid fa-user"></i> Prénom <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" id="prenom" name="prenom" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['prenom'] ?? $parent['prenom']) ?>" required maxlength="100">
                    </div>
                    <?php if (isset($errors['prenom'])): ?><span class="invalid-feedback"><?= e($errors['prenom']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="lien" class="form-label"><i class="fa-solid fa-link"></i> Lien de parenté <span class="required">*</span></label>
                    <select id="lien" name="lien" class="form-control <?= isset($errors['lien']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez —</option>
                        <?php foreach ($liens as $l): ?>
                            <option value="<?= $l ?>" <?= ($_POST['lien'] ?? $parent['lien']) === $l ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['lien'])): ?><span class="invalid-feedback"><?= e($errors['lien']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="profession" class="form-label"><i class="fa-solid fa-briefcase"></i> Profession</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-briefcase"></i></span>
                        <input type="text" id="profession" name="profession" class="form-control" value="<?= e($_POST['profession'] ?? $parent['profession'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 2 : COORDONNÉES -->
    <div class="parent-form-card mb-4">
        <div class="parent-form-header">
            <div class="parent-form-icon"><i class="fa-solid fa-address-book"></i></div>
            <div><h5>Coordonnées</h5><p>Informations de contact</p></div>
        </div>
        <div class="parent-form-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="telephone_principal" class="form-label"><i class="fa-solid fa-phone"></i> Téléphone principal <span class="required">*</span></label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="tel" id="telephone_principal" name="telephone_principal" class="form-control <?= isset($errors['telephone_principal']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['telephone_principal'] ?? $parent['telephone_principal']) ?>" required maxlength="20">
                    </div>
                    <?php if (isset($errors['telephone_principal'])): ?><span class="invalid-feedback"><?= e($errors['telephone_principal']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="telephone_secondaire" class="form-label"><i class="fa-solid fa-phone"></i> Téléphone secondaire</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-mobile-screen"></i></span>
                        <input type="tel" id="telephone_secondaire" name="telephone_secondaire" class="form-control <?= isset($errors['telephone_secondaire']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['telephone_secondaire'] ?? $parent['telephone_secondaire'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['telephone_secondaire'])): ?><span class="invalid-feedback"><?= e($errors['telephone_secondaire']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email" class="form-label"><i class="fa-solid fa-envelope"></i> Email</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-at"></i></span>
                        <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e($_POST['email'] ?? $parent['email'] ?? '') ?>">
                    </div>
                    <?php if (isset($errors['email'])): ?><span class="invalid-feedback"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="adresse" class="form-label"><i class="fa-solid fa-house"></i> Adresse</label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                        <input type="text" id="adresse" name="adresse" class="form-control" value="<?= e($_POST['adresse'] ?? $parent['adresse'] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="est_contact_urgence" class="form-label"><i class="fa-solid fa-phone-volume"></i> Type de contact</label>
                <select id="est_contact_urgence" name="est_contact_urgence" class="form-control">
                    <option value="1" <?= ($_POST['est_contact_urgence'] ?? $parent['est_contact_urgence']) == 1 ? 'selected' : '' ?>>Contact d'urgence prioritaire</option>
                    <option value="0" <?= ($_POST['est_contact_urgence'] ?? $parent['est_contact_urgence']) == 0 ? 'selected' : '' ?>>Contact secondaire</option>
                </select>
            </div>
        </div>
    </div>

    <!-- SECTION 3 : ENFANTS LIÉS -->
    <div class="parent-form-card mb-4">
        <div class="parent-form-header">
            <div class="parent-form-icon"><i class="fa-solid fa-children"></i></div>
            <div>
                <h5>Enfants liés</h5>
                <p><?= count($enfantsLies) ?> enfant(s) actuellement lié(s)</p>
            </div>
        </div>
        <div class="parent-form-body">
            <!-- Barre de recherche avec icône -->
            <div class="form-group mb-3">
                <div class="search-wrapper">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="searchEleves" class="form-control" placeholder="Rechercher un élève par nom, prénom ou matricule..." autocomplete="off">
                </div>
            </div>

            <!-- Liste dynamique des élèves -->
            <div class="eleves-selection" id="elevesContainer">
                <!-- Enfants actuellement liés -->
                <?php if (!empty($enfantsLies)): ?>
                    <div class="eleves-group-title text-success">
                        <i class="fa-solid fa-check-circle"></i> Enfants actuellement liés
                    </div>
                    <?php foreach ($enfantsLies as $enfant): ?>
                        <label class="eleves-selection-item selected">
                            <input type="checkbox" name="eleves_lies[]" value="<?= $enfant['id'] ?>" checked>
                            <div class="eleves-selection-info">
                                <div class="eleves-selection-name"><?= e($enfant['prenom'] . ' ' . $enfant['nom']) ?></div>
                                <div class="eleves-selection-detail">
                                    <span class="badge-subtle"><?= e($enfant['matricule']) ?></span>
                                    <span><?= e($enfant['classe_nom'] ?? 'Non inscrit') ?></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Autres élèves disponibles -->
                <?php if (!empty($elevesDisponibles)): ?>
                    <div class="eleves-group-title text-primary mt-2">
                        <i class="fa-solid fa-plus-circle"></i> Autres élèves disponibles
                    </div>
                    <?php foreach ($elevesDisponibles as $eleve): ?>
                        <label class="eleves-selection-item">
                            <input type="checkbox" name="eleves_lies[]" value="<?= $eleve['id'] ?>">
                            <div class="eleves-selection-info">
                                <div class="eleves-selection-name"><?= e($eleve['prenom'] . ' ' . $eleve['nom']) ?></div>
                                <div class="eleves-selection-detail">
                                    <span class="badge-subtle"><?= e($eleve['matricule']) ?></span>
                                    <span><?= e($eleve['classe_nom'] ?? 'Non inscrit') ?></span>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (empty($enfantsLies) && empty($elevesDisponibles)): ?>
                    <div class="eleves-selection-empty">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <p>Aucun élève disponible dans la base de données</p>
                    </div>
                <?php endif; ?>
            </div>

            <small class="form-help mt-2">
                <i class="fa-solid fa-info-circle"></i> Cochez/décochez un enfant pour modifier l'association avec ce parent. Le premier coché sera désigné comme tuteur principal.
            </small>
        </div>
    </div>

    <!-- Boutons -->
    <div class="d-flex justify-content-end gap-sm mb-4">
        <a href="/gestion_scolaire/parents/fiche/<?= $parentId ?>" class="btn btn-outline-primary">
            <i class="fa-solid fa-xmark"></i> Annuler
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-save"></i> Enregistrer les modifications
        </button>
    </div>
</form>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>
