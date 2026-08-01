<?php
/**
 * Fichier : public/views/modules/evaluations/ajouter.php
 * Rôle    : Formulaire de création d'une évaluation par groupe
 * Système : Bulletin congolais — Groupes → Sous-matières
 * Version : 2.0 — Refonte (groupe_id au lieu de matiere_id)
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();
requirePermission('evaluations.creer');

global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'NoteController.php';
$noteController = new NoteController();

// Récupérer les classes, trimestres et groupes
$classes = $noteController->getClassesActives();
$trimestres = $noteController->getTrimestresActifs();
$trimestreActif = $noteController->getTrimestreActif();
$groupes = $noteController->getGroupesActifs();

// Messages
$successMessage = '';
$errorMessage   = '';
$errors         = [];
$oldValues      = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldValues = [
        'classe_id'       => post('classe_id', ''),
        'trimestre_id'    => post('trimestre_id', ''),
        'groupe_id'       => post('groupe_id', ''),
        'type_evaluation' => post('type_evaluation', ''),
        'date_evaluation' => post('date_evaluation', ''),
    ];

    $classeId    = (int) $oldValues['classe_id'];
    $trimestreId = (int) $oldValues['trimestre_id'];
    $groupeId    = (int) $oldValues['groupe_id'];
    $type        = $oldValues['type_evaluation'];
    $dateEval    = $oldValues['date_evaluation'];

    // Validation
    if ($classeId <= 0) $errors['classe_id'] = 'Veuillez sélectionner une classe.';
    if ($trimestreId <= 0) $errors['trimestre_id'] = 'Veuillez sélectionner un trimestre.';
    if ($groupeId <= 0) $errors['groupe_id'] = 'Veuillez sélectionner un groupe de matières.';
    if (!in_array($type, ['Période 1', 'Période 2', 'Examen'])) {
        $errors['type_evaluation'] = 'Veuillez sélectionner un type d\'évaluation.';
    }
    if (isBlank($dateEval)) {
        $errors['date_evaluation'] = 'La date est obligatoire.';
    } elseif (!isValidDate($dateEval)) {
        $errors['date_evaluation'] = 'Date invalide.';
    }

    // Vérifier l'unicité
    if ($classeId > 0 && $trimestreId > 0 && $groupeId > 0 && !empty($type)) {
        $existe = $db->fetchColumn(
            "SELECT COUNT(*) FROM evaluations 
             WHERE trimestre_id = :tid AND classe_id = :cid AND groupe_id = :gid AND type_evaluation = :type",
            ['tid' => $trimestreId, 'cid' => $classeId, 'gid' => $groupeId, 'type' => $type]
        );
        if ($existe > 0) {
            $errors['type_evaluation'] = 'Cette évaluation existe déjà pour ce groupe, cette classe et ce trimestre.';
        }
    }

    // Vérifier la cohérence de la date avec le trimestre
    if ($trimestreId > 0 && !isBlank($dateEval)) {
        $trimestre = $db->fetchOne("SELECT date_debut, date_fin FROM trimestres WHERE id = :id", ['id' => $trimestreId]);
        if ($trimestre) {
            if ($dateEval < $trimestre['date_debut'] || $dateEval > $trimestre['date_fin']) {
                $errors['date_evaluation'] = 'La date doit être comprise dans le trimestre (' 
                    . formatDate($trimestre['date_debut'], 'd/m/Y') . ' - ' 
                    . formatDate($trimestre['date_fin'], 'd/m/Y') . ').';
            }
        }
    }

    if (empty($errors)) {
        try {
            $evaluationId = $db->insert(
                "INSERT INTO evaluations (trimestre_id, groupe_id, classe_id, type_evaluation, date_evaluation)
                 VALUES (:tid, :gid, :cid, :type, :date_eval)",
                [
                    'tid'       => $trimestreId,
                    'gid'       => $groupeId,
                    'cid'       => $classeId,
                    'type'      => $type,
                    'date_eval' => $dateEval
                ]
            );

            logActivity('Création', 'evaluations', $evaluationId, "Évaluation créée : {$type}");

            setFlash('success', 'Évaluation créée avec succès.');
            redirect('/gestion_scolaire/notes');

        } catch (Exception $e) {
            $errorMessage = 'Une erreur est survenue lors de la création.';
            error_log('Erreur création évaluation: ' . $e->getMessage());
        }
    }
}

$pageTitle = 'Créer une évaluation';
$pageIcon  = 'fa-plus';
$pageDescription = 'Créer une nouvelle période d\'évaluation pour un groupe de matières';
$extraCSS = ['modules/evaluations.css', 'modules/ajouter_note.css'];
$extraJS  = ['modules/ajouter_note.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/notes">Évaluations</a>
    <span class="separator">/</span>
    <span class="current">Créer</span>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-plus-circle text-primary me-2"></i>Créer une évaluation
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Créez une nouvelle période d'évaluation pour un groupe de matières (Période 1, Période 2 ou Examen).
        </p>
    </div>
    <a href="/gestion_scolaire/notes" class="btn btn-outline-primary btn-sm">
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

<div class="add-eval-grid">
    <!-- ============================================================
         FORMULAIRE
         ============================================================ -->
    <div class="eval-form-card">
        <div class="eval-form-header">
            <div class="eval-form-icon">
                <i class="fa-solid fa-clipboard-list"></i>
            </div>
            <div>
                <h5>Nouvelle évaluation</h5>
                <p>Définissez le groupe, la classe, le type et la date</p>
            </div>
        </div>
        <div class="eval-form-body">
            <form method="POST" action="" autocomplete="off" novalidate id="formEvaluation">

                <!-- Classe -->
                <div class="form-group">
                    <label for="classe_id" class="form-label">
                        <i class="fa-solid fa-chalkboard"></i> Classe <span class="required">*</span>
                    </label>
                    <select id="classe_id" name="classe_id" class="form-control <?= isset($errors['classe_id']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez une classe —</option>
                        <?php foreach ($classes as $classe): ?>
                            <option value="<?= $classe['id'] ?>" <?= ($oldValues['classe_id'] ?? '') == $classe['id'] ? 'selected' : '' ?>>
                                <?= e($classe['nom']) ?> — Niveau <?= $classe['niveau'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['classe_id'])): ?>
                        <span class="invalid-feedback"><?= e($errors['classe_id']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Trimestre -->
                <div class="form-group">
                    <label for="trimestre_id" class="form-label">
                        <i class="fa-solid fa-calendar-alt"></i> Trimestre <span class="required">*</span>
                    </label>
                    <select id="trimestre_id" name="trimestre_id" class="form-control <?= isset($errors['trimestre_id']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez un trimestre —</option>
                        <?php foreach ($trimestres as $trimestre): ?>
                            <option value="<?= $trimestre['id'] ?>" 
                                <?= ($oldValues['trimestre_id'] ?? ($trimestreActif ? $trimestreActif['id'] : '')) == $trimestre['id'] ? 'selected' : '' ?>
                                data-debut="<?= $trimestre['date_debut'] ?>"
                                data-fin="<?= $trimestre['date_fin'] ?>">
                                <?= e($trimestre['libelle']) ?> 
                                (<?= formatDate($trimestre['date_debut'], 'd/m/Y') ?> - <?= formatDate($trimestre['date_fin'], 'd/m/Y') ?>)
                                <?= $trimestre['est_actif'] ? ' [Actif]' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['trimestre_id'])): ?>
                        <span class="invalid-feedback"><?= e($errors['trimestre_id']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Groupe de matières -->
                <div class="form-group">
                    <label for="groupe_id" class="form-label">
                        <i class="fa-solid fa-layer-group"></i> Groupe de matières <span class="required">*</span>
                    </label>
                    <select id="groupe_id" name="groupe_id" class="form-control <?= isset($errors['groupe_id']) ? 'is-invalid' : '' ?>" required>
                        <option value="">— Sélectionnez un groupe —</option>
                        <?php foreach ($groupes as $groupe): ?>
                            <option value="<?= $groupe['id'] ?>" <?= ($oldValues['groupe_id'] ?? '') == $groupe['id'] ? 'selected' : '' ?>>
                                <?= e($groupe['libelle']) ?> 
                                <?= $groupe['code'] ? '(' . e($groupe['code']) . ')' : '' ?>
                                — <?= $groupe['nb_sous_matieres'] ?> sous-matière(s)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['groupe_id'])): ?>
                        <span class="invalid-feedback"><?= e($errors['groupe_id']) ?></span>
                    <?php endif; ?>
                    <small class="form-help">
                        <i class="fa-solid fa-info-circle"></i> Toutes les sous-matières de ce groupe seront évaluées en même temps.
                    </small>
                </div>

                <!-- Type d'évaluation -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fa-solid fa-tag"></i> Type d'évaluation <span class="required">*</span>
                    </label>
                    <div class="eval-type-selector">
                        <label class="eval-type-card" id="cardPeriode1">
                            <input type="radio" name="type_evaluation" value="Période 1" 
                                   <?= ($oldValues['type_evaluation'] ?? '') === 'Période 1' ? 'checked' : '' ?> style="display:none;">
                            <div class="eval-type-content">
                                <div class="eval-type-icon periode1"><i class="fa-solid fa-1"></i></div>
                                <div class="eval-type-text">
                                    <strong>Période 1</strong>
                                    <span>Première période d'évaluation</span>
                                </div>
                            </div>
                        </label>
                        <label class="eval-type-card" id="cardPeriode2">
                            <input type="radio" name="type_evaluation" value="Période 2" 
                                   <?= ($oldValues['type_evaluation'] ?? '') === 'Période 2' ? 'checked' : '' ?> style="display:none;">
                            <div class="eval-type-content">
                                <div class="eval-type-icon periode2"><i class="fa-solid fa-2"></i></div>
                                <div class="eval-type-text">
                                    <strong>Période 2</strong>
                                    <span>Deuxième période d'évaluation</span>
                                </div>
                            </div>
                        </label>
                        <label class="eval-type-card" id="cardExamen">
                            <input type="radio" name="type_evaluation" value="Examen" 
                                   <?= ($oldValues['type_evaluation'] ?? '') === 'Examen' ? 'checked' : '' ?> style="display:none;">
                            <div class="eval-type-content">
                                <div class="eval-type-icon examen"><i class="fa-solid fa-file-pen"></i></div>
                                <div class="eval-type-text">
                                    <strong>Examen</strong>
                                    <span>Examen de fin de trimestre</span>
                                </div>
                            </div>
                        </label>
                    </div>
                    <?php if (isset($errors['type_evaluation'])): ?>
                        <span class="invalid-feedback"><?= e($errors['type_evaluation']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Date -->
                <div class="form-group">
                    <label for="date_evaluation" class="form-label">
                        <i class="fa-solid fa-calendar"></i> Date de l'évaluation <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <span class="input-icon"><i class="fa-solid fa-calendar"></i></span>
                        <input type="date" id="date_evaluation" name="date_evaluation" 
                               class="form-control <?= isset($errors['date_evaluation']) ? 'is-invalid' : '' ?>"
                               value="<?= e($oldValues['date_evaluation'] ?? '') ?>" required>
                    </div>
                    <?php if (isset($errors['date_evaluation'])): ?>
                        <span class="invalid-feedback"><?= e($errors['date_evaluation']) ?></span>
                    <?php endif; ?>
                    <small class="form-help" id="dateHelp">
                        <i class="fa-solid fa-info-circle"></i> La date doit être comprise dans le trimestre sélectionné.
                    </small>
                </div>
            </form>
        </div>
        <div class="eval-form-footer">
            <a href="/gestion_scolaire/notes" class="btn btn-outline-primary">
                <i class="fa-solid fa-xmark"></i> Annuler
            </a>
            <button type="submit" form="formEvaluation" class="btn btn-primary">
                <i class="fa-solid fa-save"></i> Créer l'évaluation
            </button>
        </div>
    </div>

    <!-- ============================================================
         INFORMATIONS
         ============================================================ -->
    <div>
        <!-- Évaluations existantes -->
        <div class="info-card-custom" id="existingEvalsCard">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon blue">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <h6>Évaluations existantes</h6>
            </div>
            <div class="info-card-custom-body">
                <div id="existingEvals">
                    <p style="font-size:0.82rem;color:var(--color-gray-500);">
                        Sélectionnez une classe et un trimestre pour voir les évaluations déjà créées.
                    </p>
                </div>
            </div>
        </div>

        <!-- Guide -->
        <div class="info-card-custom mt-4">
            <div class="info-card-custom-header">
                <div class="info-card-custom-icon orange">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <h6>Rappel</h6>
            </div>
            <div class="info-card-custom-body">
                <ul class="info-list">
                    <li>Une évaluation couvre <strong>toutes les sous-matières</strong> d'un groupe.</li>
                    <li>Chaque trimestre comporte <strong>3 évaluations par groupe</strong>.</li>
                    <li>Une fois créée, vous pourrez <strong>saisir les notes</strong> de toutes les sous-matières.</li>
                    <li><strong>Total Trimestre = P1 + P2 + Examen</strong></li>
                    <li>Utilisez le bouton <strong>"Générer"</strong> sur la liste pour créer automatiquement les évaluations manquantes.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
// Mettre à jour les évaluations existantes
document.getElementById('classe_id').addEventListener('change', loadExistingEvals);
document.getElementById('trimestre_id').addEventListener('change', loadExistingEvals);

function loadExistingEvals() {
    const classeId = document.getElementById('classe_id').value;
    const trimestreId = document.getElementById('trimestre_id').value;
    const container = document.getElementById('existingEvals');

    if (!classeId || !trimestreId) {
        container.innerHTML = '<p style="font-size:0.82rem;color:var(--color-gray-500);">Sélectionnez une classe et un trimestre.</p>';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'liste');
    formData.append('classe_id', classeId);
    formData.append('trimestre_id', trimestreId);

    fetch('/gestion_scolaire/notes', {
        method: 'POST', body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.evaluations.length > 0) {
            const typesExistants = data.evaluations.map(e => e.type_evaluation);

            container.innerHTML = data.evaluations.map(e => {
                const badgeClass = e.type_evaluation === 'Période 1' ? 'info' : 
                                  (e.type_evaluation === 'Période 2' ? 'warning' : 'danger');
                return `
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;">
                        <span style="font-size:0.82rem;">
                            <span class="badge badge-${badgeClass}" style="font-size:0.7rem;">${e.type_evaluation}</span>
                            <span style="margin-left:8px;font-size:0.78rem;color:var(--color-gray-600);">${e.groupe_libelle}</span>
                        </span>
                        <span style="font-size:0.72rem;color:var(--color-gray-400);">${e.date_evaluation}</span>
                    </div>
                `;
            }).join('');

            // Désactiver les types déjà créés
            document.querySelectorAll('.eval-type-card').forEach(card => {
                const input = card.querySelector('input');
                if (input && typesExistants.includes(input.value)) {
                    card.style.opacity = '0.4';
                    card.style.pointerEvents = 'none';
                    card.title = 'Déjà créé';
                } else {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                    card.title = '';
                }
            });
        } else {
            container.innerHTML = '<p style="font-size:0.82rem;color:var(--color-gray-500);">Aucune évaluation créée pour cette sélection.</p>';
            // Réactiver tous les types
            document.querySelectorAll('.eval-type-card').forEach(card => {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                card.title = '';
            });
        }
    });
}

// Charger au démarrage
loadExistingEvals();
</script>

<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';