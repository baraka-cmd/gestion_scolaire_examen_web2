<?php
/**
 * Fichier : public/views/modules/annees/modifier.php
 * Rôle    : Formulaire de modification d'une année scolaire
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('parametres.modifier');

global $db;

// Récupérer l'ID
$anneeId = (int) ($_GET['id'] ?? 0);
if ($anneeId <= 0) {
    setFlash('error', 'ID année invalide.');
    redirect('/gestion_scolaire/annees-scolaires');
}

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'AnneeController.php';
$anneeController = new AnneeController();

// Récupérer l'année
$annee = $anneeController->getAnneeById($anneeId);
if (!$annee) {
    setFlash('error', 'Année introuvable.');
    redirect('/gestion_scolaire/annees-scolaires');
}

// Vérifier si l'année est clôturée
if ($annee['est_cloturee']) {
    setFlash('error', 'Impossible de modifier une année clôturée.');
    redirect('/gestion_scolaire/annees-scolaires');
}

// Messages
$errorMessage = '';
$errors = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle   = post('libelle', '');
    $dateDebut = post('date_debut', '');
    $dateFin   = post('date_fin', '');

    if (isBlank($libelle)) $errors['libelle'] = 'Le libellé est obligatoire.';
    elseif (!preg_match('/^\d{4}-\d{4}$/', $libelle)) $errors['libelle'] = 'Format invalide (ex: 2026-2027).';
    else {
        $existe = $db->fetchColumn(
            "SELECT COUNT(*) FROM annees_scolaires WHERE libelle = :libelle AND id != :id",
            ['libelle' => $libelle, 'id' => $anneeId]
        );
        if ($existe > 0) $errors['libelle'] = 'Cette année scolaire existe déjà.';
    }

    if (isBlank($dateDebut)) $errors['date_debut'] = 'Date obligatoire.';
    if (isBlank($dateFin)) $errors['date_fin'] = 'Date obligatoire.';
    if (!isBlank($dateDebut) && !isBlank($dateFin) && $dateDebut >= $dateFin) {
        $errors['date_fin'] = 'La date de fin doit être après la date de début.';
    }

    // Vérifier le chevauchement avec d'autres années
    if (!isBlank($dateDebut) && !isBlank($dateFin) && empty($errors)) {
        $chevauche = $db->fetchColumn(
            "SELECT COUNT(*) FROM annees_scolaires 
             WHERE id != :id AND (
                 (date_debut BETWEEN :debut AND :fin) 
                 OR (date_fin BETWEEN :debut2 AND :fin2)
                 OR (date_debut <= :debut3 AND date_fin >= :fin3)
             )",
            [
                'id' => $anneeId,
                'debut' => $dateDebut, 'fin' => $dateFin,
                'debut2' => $dateDebut, 'fin2' => $dateFin,
                'debut3' => $dateDebut, 'fin3' => $dateFin
            ]
        );
        if ($chevauche > 0) $errors['date_debut'] = 'Cette période chevauche une autre année scolaire.';
    }

    if (empty($errors)) {
        try {
            $db->update(
                "UPDATE annees_scolaires SET libelle = :libelle, date_debut = :debut, date_fin = :fin WHERE id = :id",
                ['libelle' => $libelle, 'debut' => $dateDebut, 'fin' => $dateFin, 'id' => $anneeId]
            );

            logActivity('Modification', 'annees_scolaires', $anneeId, "Année modifiée : {$libelle}");
            setFlash('success', 'Année scolaire modifiée avec succès.');
            redirect('/gestion_scolaire/annees-scolaires');

        } catch (Exception $e) {
            $errorMessage = 'Erreur lors de la modification.';
            error_log('Erreur modification année: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Modifier — ' . $annee['libelle'];
$pageIcon  = 'fa-pen-to-square';
$pageDescription = 'Modifier les informations de l\'année scolaire';
$extraCSS = ['modules/annees.css', 'modules/ajouter_annee.css'];
$extraJS  = ['modules/ajouter_annee.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/annees-scolaires">Années scolaires</a>
    <span class="separator">/</span>
    <span class="current"><?= e($annee['libelle']) ?></span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Modifier l'année scolaire
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Modification de <strong><?= e($annee['libelle']) ?></strong>
            — Statut : 
            <?php if ($annee['est_active']): ?>
                <span class="badge badge-success">Active</span>
            <?php else: ?>
                <span class="badge badge-secondary">Inactive</span>
            <?php endif; ?>
        </p>
    </div>
    <a href="/gestion_scolaire/annees-scolaires" class="btn btn-outline-primary btn-sm">
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

<div class="add-annee-grid">
    <!-- ============================================================
         FORMULAIRE
         ============================================================ -->
    <div class="annee-form-card">
        <div class="annee-form-header">
            <div class="annee-form-icon">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div>
                <h5>Modifier : <?= e($annee['libelle']) ?></h5>
                <p>Ajustez les dates de la période académique</p>
            </div>
        </div>
        <div class="annee-form-body">
            <form method="POST" action="" autocomplete="off" novalidate id="formAnnee">

                <!-- Libellé -->
                <div class="form-group">
                    <label for="libelle" class="form-label">
                        <i class="fa-solid fa-tag"></i> Libellé <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-calendar-alt"></i></span>
                        <input type="text" id="libelle" name="libelle" 
                               class="form-control <?= isset($errors['libelle']) ? 'is-invalid' : '' ?>"
                               value="<?= e($_POST['libelle'] ?? $annee['libelle']) ?>" 
                               required maxlength="9" autofocus>
                    </div>
                    <?php if (isset($errors['libelle'])): ?>
                        <span class="invalid-feedback"><?= e($errors['libelle']) ?></span>
                    <?php endif; ?>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> Format : <strong>AAAA-AAAA</strong> (ex: 2026-2027)
                    </small>
                </div>

                <div class="form-row">
                    <!-- Date début -->
                    <div class="form-group">
                        <label for="date_debut" class="form-label">
                            <i class="fa-solid fa-play"></i> Date de début <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon"><i class="fa-solid fa-calendar"></i></span>
                            <input type="date" id="date_debut" name="date_debut" 
                                   class="form-control <?= isset($errors['date_debut']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($_POST['date_debut'] ?? $annee['date_debut']) ?>" required>
                        </div>
                        <?php if (isset($errors['date_debut'])): ?>
                            <span class="invalid-feedback"><?= e($errors['date_debut']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Date fin -->
                    <div class="form-group">
                        <label for="date_fin" class="form-label">
                            <i class="fa-solid fa-stop"></i> Date de fin <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <span class="input-icon"><i class="fa-solid fa-calendar"></i></span>
                            <input type="date" id="date_fin" name="date_fin" 
                                   class="form-control <?= isset($errors['date_fin']) ? 'is-invalid' : '' ?>"
                                   value="<?= e($_POST['date_fin'] ?? $annee['date_fin']) ?>" required>
                        </div>
                        <?php if (isset($errors['date_fin'])): ?>
                            <span class="invalid-feedback"><?= e($errors['date_fin']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="annee-form-footer">
            <a href="/gestion_scolaire/annees-scolaires" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="submit" form="formAnnee" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Enregistrer les modifications
            </button>
        </div>
    </div>

    <!-- ============================================================
         INFORMATIONS
         ============================================================ -->
    <div>
        <!-- État actuel -->
        <div class="info-card-custom">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon blue">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <h6>État actuel</h6>
            </div>
            <div class="info-card-custom-body">
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.82rem;color:var(--color-gray-600);">Statut</span>
                        <?php if ($annee['est_active']): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Active</span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><i class="fa-solid fa-circle"></i> Inactive</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.82rem;color:var(--color-gray-600);">Clôturée</span>
                        <?php if ($annee['est_cloturee']): ?>
                            <span class="badge badge-warning"><i class="fa-solid fa-lock"></i> Oui</span>
                        <?php else: ?>
                            <span class="badge badge-success"><i class="fa-solid fa-unlock"></i> Non</span>
                        <?php endif; ?>
                    </div>
                    <hr style="border-color:var(--border-color);margin:4px 0;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.82rem;color:var(--color-gray-600);">Classes liées</span>
                        <strong><?= $annee['nb_classes'] ?? 0 ?></strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:0.82rem;color:var(--color-gray-600);">Inscriptions</span>
                        <strong><?= $annee['nb_inscriptions'] ?? 0 ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attention -->
        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon orange">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h6>Attention</h6>
            </div>
            <div class="info-card-custom-body">
                <ul class="info-list">
                    <li>Modifier les dates <strong>ne met pas à jour</strong> les trimestres existants.</li>
                    <li>Si des <strong>classes</strong> ou <strong>inscriptions</strong> sont liées, elles ne seront pas affectées.</li>
                    <li>Une année <strong>active</strong> peut être modifiée, mais cela peut affecter les données en cours.</li>
                    <li>Pour <strong>clôturer</strong> définitivement cette année, utilisez le bouton sur la liste.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>