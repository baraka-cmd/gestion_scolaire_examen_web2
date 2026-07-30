<?php
/**
 * Fichier : public/views/modules/annees/ajouter.php
 * Rôle    : Formulaire d'ajout d'une année scolaire
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('parametres.modifier');

global $db;

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];
$oldValues      = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldValues = [
        'libelle'           => post('libelle', ''),
        'date_debut'        => post('date_debut', ''),
        'date_fin'          => post('date_fin', ''),
        'creer_trimestres'  => post('creer_trimestres', '1'),
        'creer_classes'     => post('creer_classes', '0'),
    ];

    $libelle          = $oldValues['libelle'];
    $dateDebut        = $oldValues['date_debut'];
    $dateFin          = $oldValues['date_fin'];
    $creerTrimestres  = (int) $oldValues['creer_trimestres'];
    $creerClasses     = (int) $oldValues['creer_classes'];

    // Validation
    if (isBlank($libelle)) {
        $errors['libelle'] = 'Le libellé est obligatoire.';
    } elseif (!preg_match('/^\d{4}-\d{4}$/', $libelle)) {
        $errors['libelle'] = 'Format invalide (ex: 2026-2027).';
    } else {
        $existe = $db->fetchColumn(
            "SELECT COUNT(*) FROM annees_scolaires WHERE libelle = :libelle",
            ['libelle' => $libelle]
        );
        if ($existe > 0) $errors['libelle'] = 'Cette année scolaire existe déjà.';
    }

    if (isBlank($dateDebut)) {
        $errors['date_debut'] = 'La date de début est obligatoire.';
    } elseif (!isValidDate($dateDebut)) {
        $errors['date_debut'] = 'Date invalide.';
    }

    if (isBlank($dateFin)) {
        $errors['date_fin'] = 'La date de fin est obligatoire.';
    } elseif (!isValidDate($dateFin)) {
        $errors['date_fin'] = 'Date invalide.';
    }

    if (!isBlank($dateDebut) && !isBlank($dateFin) && $dateDebut >= $dateFin) {
        $errors['date_fin'] = 'La date de fin doit être après la date de début.';
    }

    // Vérifier le chevauchement avec d'autres années
    if (!isBlank($dateDebut) && !isBlank($dateFin) && empty($errors)) {
        $chevauche = $db->fetchColumn(
            "SELECT COUNT(*) FROM annees_scolaires 
             WHERE (date_debut BETWEEN :debut AND :fin) 
                OR (date_fin BETWEEN :debut2 AND :fin2)
                OR (date_debut <= :debut3 AND date_fin >= :fin3)",
            [
                'debut' => $dateDebut, 'fin' => $dateFin,
                'debut2' => $dateDebut, 'fin2' => $dateFin,
                'debut3' => $dateDebut, 'fin3' => $dateFin
            ]
        );
        if ($chevauche > 0) {
            $errors['date_debut'] = 'Cette période chevauche une année scolaire existante.';
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Créer l'année (désactivée par défaut)
            $anneeId = $db->insert(
                "INSERT INTO annees_scolaires (libelle, date_debut, date_fin, est_active, est_cloturee)
                 VALUES (:libelle, :debut, :fin, 0, 0)",
                [
                    'libelle' => $libelle,
                    'debut'   => $dateDebut,
                    'fin'     => $dateFin
                ]
            );

            // Créer les 3 trimestres automatiquement
            if ($creerTrimestres) {
                $debut = new DateTime($dateDebut);
                $fin   = new DateTime($dateFin);
                $dureeTotale = $debut->diff($fin)->days;
                $dureeTrimestre = floor($dureeTotale / 3);

                $trimestresData = [
                    ['numero' => 1, 'libelle' => '1er Trimestre'],
                    ['numero' => 2, 'libelle' => '2ème Trimestre'],
                    ['numero' => 3, 'libelle' => '3ème Trimestre']
                ];

                foreach ($trimestresData as $i => $trim) {
                    $tDebut = clone $debut;
                    $tDebut->modify('+' . ($i * $dureeTrimestre) . ' days');

                    $tFin = clone $tDebut;
                    if ($i < 2) {
                        $tFin->modify('+' . ($dureeTrimestre - 1) . ' days');
                    } else {
                        $tFin = clone $fin;
                    }
                    if ($tFin > $fin) $tFin = clone $fin;

                    $trimestreId = $db->insert(
                        "INSERT INTO trimestres (annee_scolaire_id, numero, libelle, date_debut, date_fin, est_actif)
                         VALUES (:aid, :num, :lib, :debut, :fin, :actif)",
                        [
                            'aid'   => $anneeId,
                            'num'   => $trim['numero'],
                            'lib'   => $trim['libelle'],
                            'debut' => $tDebut->format('Y-m-d'),
                            'fin'   => $tFin->format('Y-m-d'),
                            'actif' => $i === 0 ? 1 : 0
                        ]
                    );
                }
            }

            // Créer les 6 classes automatiquement si demandé
            if ($creerClasses) {
                $nomsClasses = [
                    ['nom' => '1ère Primaire', 'niveau' => 1, 'salle' => 'Salle A1'],
                    ['nom' => '2ème Primaire', 'niveau' => 2, 'salle' => 'Salle A2'],
                    ['nom' => '3ème Primaire', 'niveau' => 3, 'salle' => 'Salle A3'],
                    ['nom' => '4ème Primaire', 'niveau' => 4, 'salle' => 'Salle B1'],
                    ['nom' => '5ème Primaire', 'niveau' => 5, 'salle' => 'Salle B2'],
                    ['nom' => '6ème Primaire', 'niveau' => 6, 'salle' => 'Salle B3']
                ];

                foreach ($nomsClasses as $classe) {
                    $db->insert(
                        "INSERT INTO classes (nom, niveau, annee_scolaire_id, capacite_max, salle, est_actif)
                         VALUES (:nom, :niveau, :aid, 50, :salle, 1)",
                        [
                            'nom'    => $classe['nom'],
                            'niveau' => $classe['niveau'],
                            'aid'    => $anneeId,
                            'salle'  => $classe['salle']
                        ]
                    );
                }
            }

            $db->commit();

            logActivity('Création', 'annees_scolaires', $anneeId, "Année créée : {$libelle}");

            $msg = 'Année scolaire créée avec succès.';
            if ($creerTrimestres) $msg .= ' Les 3 trimestres ont été générés automatiquement.';
            if ($creerClasses) $msg .= ' Les 6 classes ont été créées.';
            
            setFlash('success', $msg);
            redirect('/gestion_scolaire/annees-scolaires');

        } catch (Exception $e) {
            $db->rollback();
            $errorMessage = 'Une erreur est survenue lors de la création.';
            error_log('Erreur création année: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Ajouter une année scolaire';
$pageIcon  = 'fa-plus';
$pageDescription = 'Créer une nouvelle période académique';
$extraCSS = ['modules/annees.css', 'modules/ajouter_annee.css'];
$extraJS  = ['modules/ajouter_annee.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/annees-scolaires">Années scolaires</a>
    <span class="separator">/</span>
    <span class="current">Ajouter</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-plus-circle text-primary me-2"></i>Ajouter une année scolaire
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Créez une nouvelle période académique avec ses trimestres.
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
                <i class="fa-solid fa-calendar-plus"></i>
            </div>
            <div>
                <h5>Nouvelle année scolaire</h5>
                <p>Définissez la période académique</p>
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
                               value="<?= e($oldValues['libelle'] ?? '') ?>" 
                               placeholder="Ex: 2026-2027" 
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
                                   value="<?= e($oldValues['date_debut'] ?? '') ?>" required>
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
                                   value="<?= e($oldValues['date_fin'] ?? '') ?>" required>
                        </div>
                        <?php if (isset($errors['date_fin'])): ?>
                            <span class="invalid-feedback"><?= e($errors['date_fin']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <hr class="form-divider">

                <!-- Options -->
                <p class="form-section-title">
                    <i class="fa-solid fa-gear"></i> Options de création
                </p>

                <div class="form-group">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="creer_trimestres" id="creer_trimestres" value="1" 
                               <?= ($oldValues['creer_trimestres'] ?? '1') === '1' ? 'checked' : '' ?>>
                        <span class="checkbox-custom"><i class="fa-solid fa-check"></i></span>
                        <span class="checkbox-label">Créer automatiquement les 3 trimestres</span>
                    </label>
                    <small class="form-help" style="margin-left:30px;">
                        <i class="fa-solid fa-info-circle"></i> Les trimestres seront répartis équitablement sur la période. Le 1er trimestre sera activé par défaut.
                    </small>
                </div>

                <div class="form-group">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="creer_classes" id="creer_classes" value="1" 
                               <?= ($oldValues['creer_classes'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <span class="checkbox-custom"><i class="fa-solid fa-check"></i></span>
                        <span class="checkbox-label">Créer automatiquement les 6 classes (1ère à 6ème Primaire)</span>
                    </label>
                    <small class="form-help" style="margin-left:30px;">
                        <i class="fa-solid fa-info-circle"></i> Les classes seront créées avec une capacité de 50 élèves et des salles prédéfinies.
                    </small>
                </div>
            </form>
        </div>
        <div class="annee-form-footer">
            <a href="/gestion_scolaire/annees-scolaires" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="submit" form="formAnnee" class="btn btn-primary">
                <i class="fa-solid fa-calendar-plus"></i> Créer l'année scolaire
            </button>
        </div>
    </div>

    <!-- ============================================================
         INFORMATIONS
         ============================================================ -->
    <div>
        <!-- Aperçu des trimestres -->
        <div class="info-card-custom">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon blue">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <h6>Aperçu des trimestres</h6>
            </div>
            <div class="info-card-custom-body">
                <p style="font-size:0.82rem;color:var(--color-gray-600);margin-bottom:12px;">
                    Si vous cochez l'option, les 3 trimestres seront automatiquement créés et répartis sur la période :
                </p>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f0fdf4;border-radius:8px;border:1px solid #a7f3d0;">
                        <span class="badge badge-success" style="font-size:0.7rem;">Actif</span>
                        <span style="font-size:0.82rem;"><strong>1er Trimestre</strong></span>
                        <span style="font-size:0.72rem;color:var(--color-gray-500);">(activé par défaut)</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
                        <span class="badge badge-secondary" style="font-size:0.7rem;">Inactif</span>
                        <span style="font-size:0.82rem;"><strong>2ème Trimestre</strong></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
                        <span class="badge badge-secondary" style="font-size:0.7rem;">Inactif</span>
                        <span style="font-size:0.82rem;"><strong>3ème Trimestre</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Règles métier -->
        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon orange">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <h6>Règles de gestion</h6>
            </div>
            <div class="info-card-custom-body">
                <ul style="font-size:0.82rem;color:var(--color-gray-600);padding-left:18px;display:flex;flex-direction:column;gap:8px;">
                    <li><strong>Une seule année active</strong> à la fois. Activer une année désactive automatiquement les autres.</li>
                    <li><strong>Pas de chevauchement</strong> : deux années ne peuvent pas se superposer.</li>
                    <li><strong>Clôture</strong> : une année clôturée ne peut plus être modifiée.</li>
                    <li><strong>Suppression</strong> impossible si des élèves sont inscrits.</li>
                    <li>L'année créée sera <strong>inactive</strong> par défaut. Vous devrez l'activer manuellement.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>