<?php
/**
 * Fichier : public/views/modules/roles/liste.php
 * Rôle    : Interface de gestion des rôles et permissions
 */

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';

// Vérifier l'authentification
requireLogin();

// Vérifier la permission
requirePermission('roles.consulter');

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'RoleController.php';
$roleController = new RoleController();

// Traitement AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAjax()) {
    $roleController->handleAjax();
}

// Récupérer les rôles
$roles = $roleController->index();

// Métadonnées de la page
$pageTitle = 'Gestion des rôles';
$pageIcon  = 'fa-user-shield';
$pageDescription = 'Gérez les rôles et leurs permissions d\'accès';

// CSS et JS additionnels
$extraCSS = ['modules/roles.css'];
$extraJS  = ['modules/roles.js'];

// Charger le header
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- ============================================================
     EN-TÊTE DE LA PAGE
     ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-weight:600;color:var(--color-dark);margin-bottom:4px;">
            <i class="fa-solid fa-user-shield text-primary me-2"></i>Gestion des rôles
        </h4>
        <p style="font-size:0.82rem;color:var(--color-gray-500);margin:0;">
            Gérez les rôles utilisateurs et configurez leurs permissions d'accès aux différents modules.
        </p>
    </div>
    <?php if (can('roles.creer')): ?>
    <button type="button" class="btn btn-primary" id="btnAddRole">
        <i class="fa-solid fa-plus"></i> Nouveau rôle
    </button>
    <?php endif; ?>
</div>

<!-- ============================================================
     MESSAGE FLASH
     ============================================================ -->
<div id="flashMessage"></div>

<!-- ============================================================
     TABLEAU DES RÔLES
     ============================================================ -->
<div class="card">
    <div class="card-header">
        <h5><i class="fa-solid fa-list"></i> Liste des rôles</h5>
        <span class="text-muted" style="font-size:0.78rem;">
            <?= count($roles) ?> rôle(s) configuré(s)
        </span>
    </div>
    <div class="card-body no-padding">
        <div class="table-responsive">
            <table class="table" id="tableRoles">
                <thead>
                    <tr>
                        <th>Rôle</th>
                        <th>Description</th>
                        <th>Niveau d'accès</th>
                        <th>Permissions</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roles)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fa-solid fa-user-shield"></i>
                                    <p>Aucun rôle trouvé</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="table-avatar-placeholder" style="background:<?= $role['id'] <= 6 ? '#2c3e90' : '#8b5cf6' ?>">
                                            <i class="fa-solid <?= $role['id'] <= 6 ? 'fa-shield-halved' : 'fa-user-gear' ?>" style="font-size:0.7rem;"></i>
                                        </div>
                                        <div class="user-info">
                                            <span class="user-name"><?= e($role['nom']) ?></span>
                                            <?php if ($role['id'] <= 6): ?>
                                                <span class="user-detail">Rôle système</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($role['description']) ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="flex:1;max-width:100px;height:6px;background:#e2e8f0;border-radius:10px;overflow:hidden;">
                                            <div style="width:<?= ($role['niveau_acces'] / 10) * 100 ?>%;height:100%;background:<?= $role['niveau_acces'] >= 8 ? '#10b981' : ($role['niveau_acces'] >= 5 ? '#f59e0b' : '#ef4444') ?>;border-radius:10px;"></div>
                                        </div>
                                        <span style="font-weight:600;font-size:0.8rem;"><?= $role['niveau_acces'] ?>/10</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?= $role['nb_permissions'] ?> permissions</span>
                                </td>
                                <td>
                                    <?php if ($role['est_actif']): ?>
                                        <span class="badge badge-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-sm">
                                        <?php if (can('roles.modifier')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary btnManageRole" data-id="<?= $role['id'] ?>" title="Gérer les permissions">
                                            <i class="fa-solid fa-key"></i> Permissions
                                        </button>
                                        <?php endif; ?>
                                        <?php if (can('roles.supprimer') && $role['id'] > 6): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger btnDeleteRole" data-id="<?= $role['id'] ?>" data-nom="<?= e($role['nom']) ?>" title="Supprimer ce rôle">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     MODALE : AJOUTER UN RÔLE
     ============================================================ -->
<?php if (can('roles.creer')): ?>
<div class="modal-overlay" id="modalAddRole">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h5><i class="fa-solid fa-plus text-primary me-2"></i>Nouveau rôle</h5>
            <button type="button" class="modal-close" id="btnCloseAddRole">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formAddRole" autocomplete="off">
                <div class="form-group">
                    <label for="add_nom" class="form-label">
                        <i class="fa-solid fa-tag"></i> Nom du rôle <span class="required">*</span>
                    </label>
                    <input type="text" id="add_nom" name="nom" class="form-control" placeholder="Ex: Superviseur, Caissier..." required maxlength="50">
                    <span class="invalid-feedback" id="add_nomError"></span>
                </div>
                <div class="form-group">
                    <label for="add_description" class="form-label">
                        <i class="fa-solid fa-align-left"></i> Description <span class="required">*</span>
                    </label>
                    <textarea id="add_description" name="description" class="form-control" placeholder="Décrivez le rôle..." rows="3" required></textarea>
                    <span class="invalid-feedback" id="add_descriptionError"></span>
                </div>
                <div class="form-group">
                    <label for="add_niveau" class="form-label">
                        <i class="fa-solid fa-layer-group"></i> Niveau d'accès (1-10)
                    </label>
                    <select id="add_niveau" name="niveau_acces" class="form-control">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>" <?= $i === 5 ? 'selected' : '' ?>><?= $i ?> — <?= $i >= 8 ? 'Élevé' : ($i >= 5 ? 'Moyen' : 'Bas') ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-primary" id="btnCancelAddRole">
                <i class="fa-solid fa-xmark"></i> Annuler
            </button>
            <button type="button" class="btn btn-primary" id="btnSaveRole">
                <i class="fa-solid fa-check"></i> Créer le rôle
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     MODALE : GÉRER LES PERMISSIONS
     ============================================================ -->
<?php if (can('roles.modifier')): ?>
<div class="modal-overlay" id="modalManagePermissions">
    <div class="modal-content" style="max-width:750px;max-height:85vh;">
        <div class="modal-header">
            <h5>
                <i class="fa-solid fa-key text-warning me-2"></i>
                Gérer les permissions — <span id="manageRoleName">...</span>
            </h5>
            <button type="button" class="modal-close" id="btnClosePermissions">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y:auto;max-height:60vh;">
            <div id="permissionsContainer">
                <!-- Chargé dynamiquement -->
                <div style="text-align:center;padding:30px;">
                    <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem;color:var(--color-primary);"></i>
                    <p style="margin-top:10px;color:var(--color-gray-500);">Chargement des permissions...</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-primary" id="btnCancelPermissions">
                <i class="fa-solid fa-xmark"></i> Annuler
            </button>
            <button type="button" class="btn btn-primary" id="btnSavePermissions">
                <i class="fa-solid fa-save"></i> Enregistrer les permissions
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Charger le footer
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>