<?php
/**
 * Fichier : public/views/modules/utilisateurs/fiche.php
 * Rôle    : Fiche détaillée d'un utilisateur
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('ACCESS') or define('ACCESS', true);

require_once __DIR__ . '/../../../../src/config/app.php';
requireLogin();

requirePermission('utilisateurs.consulter');
global $db;

// Charger le contrôleur
require_once SRC_PATH . 'controllers' . DIRECTORY_SEPARATOR . 'UtilisateurController.php';
$userController = new UtilisateurController();

// Récupérer l'ID depuis l'URL
$userId = (int) ($_GET['id'] ?? 0);

if ($userId <= 0) {
    setFlash('error', 'ID utilisateur invalide.');
    redirect('/gestion_scolaire/utilisateurs');
}

// Récupérer les informations de l'utilisateur
$utilisateur = $db->fetchOne(
    "SELECT u.*, r.nom AS role_nom, r.niveau_acces
     FROM utilisateurs u
     INNER JOIN roles r ON u.role_id = r.id
     WHERE u.id = :id",
    ['id' => $userId]
);

if (!$utilisateur) {
    setFlash('error', 'Utilisateur introuvable.');
    redirect('/gestion_scolaire/utilisateurs');
}

// Récupérer l'historique des connexions
$historique = $db->fetchAll(
    "SELECT action, description, table_concernee, date_action, adresse_ip, navigateur
     FROM logs_activites
     WHERE utilisateur_id = :uid
     ORDER BY date_action DESC
     LIMIT 30",
    ['uid' => $userId]
);

// Récupérer les statistiques de l'utilisateur
$statsUser = [
    'total_connexions' => 0,
    'total_modifications' => 0,
    'derniere_activite' => 'Jamais',
];

$statsUser['total_connexions'] = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM logs_activites WHERE utilisateur_id = :uid AND action = 'Connexion'",
    ['uid' => $userId]
);
$statsUser['total_modifications'] = (int) $db->fetchColumn(
    "SELECT COUNT(*) FROM logs_activites WHERE utilisateur_id = :uid AND action IN ('Création', 'Modification', 'Suppression')",
    ['uid' => $userId]
);

$lastActivity = $db->fetchColumn(
    "SELECT date_action FROM logs_activites WHERE utilisateur_id = :uid ORDER BY date_action DESC LIMIT 1",
    ['uid' => $userId]
);
if ($lastActivity) {
    $statsUser['derniere_activite'] = formatDateTime($lastActivity);
}

$pageTitle = 'Fiche utilisateur — ' . $utilisateur['prenom'] . ' ' . $utilisateur['nom'];
$pageIcon  = 'fa-user';
$pageDescription = 'Détails du compte utilisateur';

$extraCSS = ['modules/eleves.css', 'modules/fiche_eleve.css'];
$extraJS  = ['modules/fiche_eleve.js'];

require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'header.php';
?>

<!-- Fil d'Ariane -->
<nav class="breadcrumb">
    <a href="/gestion_scolaire/dashboard"><i class="fa-solid fa-house"></i></a>
    <span class="separator">/</span>
    <a href="/gestion_scolaire/utilisateurs">Utilisateurs</a>
    <span class="separator">/</span>
    <span class="current"><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></span>
</nav>

<!-- En-tête du profil -->
<div class="profile-card">
    <div class="profile-header">
        <div class="profile-avatar-placeholder-large">
            <?php if (!empty($utilisateur['photo']) && file_exists(UPLOADS_PATH . $utilisateur['photo'])): ?>
                <img src="<?= ASSETS_URL . $utilisateur['photo'] ?>" alt="Photo" class="profile-avatar-large">
            <?php else: ?>
                <?= mb_strtoupper(mb_substr($utilisateur['prenom'], 0, 1) . mb_substr($utilisateur['nom'], 0, 1)) ?>
            <?php endif; ?>
        </div>
        <div class="profile-header-info">
            <h3><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></h3>
            <p>
                <span class="role-badge <?= strtolower($utilisateur['role_nom']) ?>">
                    <i class="fa-solid fa-circle"></i> <?= e($utilisateur['role_nom']) ?>
                </span>
            </p>
            <p>
                <i class="fa-solid fa-envelope"></i> <?= e($utilisateur['email']) ?>
                <?php if (!empty($utilisateur['telephone'])): ?>
                    <span class="mx-2">|</span>
                    <i class="fa-solid fa-phone"></i> <?= e($utilisateur['telephone']) ?>
                <?php endif; ?>
            </p>
            <p>
                <?php if ($utilisateur['est_actif']): ?>
                    <span class="badge badge-success"><i class="fa-solid fa-check-circle"></i> Compte actif</span>
                <?php else: ?>
                    <span class="badge badge-danger"><i class="fa-solid fa-times-circle"></i> Compte inactif</span>
                <?php endif; ?>
                <span class="ms-2" style="font-size:0.8rem;opacity:0.7;">
                    <i class="fa-solid fa-clock"></i> Créé le <?= formatDate($utilisateur['created_at'], 'd/m/Y à H:i') ?>
                </span>
            </p>
        </div>
        <div class="profile-header-actions">
            <?php if (can('utilisateurs.modifier')): ?>
                <a href="/gestion_scolaire/utilisateurs/modifier/<?= $userId ?>" class="btn btn-sm btn-outline-primary" style="color:#fff;border-color:rgba(255,255,255,0.5);">
                    <i class="fa-solid fa-pen-to-square"></i> Modifier
                </a>
            <?php endif; ?>
            <a href="/gestion_scolaire/utilisateurs" class="btn btn-sm btn-outline-primary" style="color:#fff;border-color:rgba(255,255,255,0.5);">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="profile-body">
        <!-- Informations générales -->
        <div class="profile-section">
            <h5 class="profile-section-title">
                <i class="fa-solid fa-id-card"></i> Informations générales
            </h5>
            <div class="profile-grid">
                <div class="profile-field">
                    <span class="profile-field-label">Nom complet</span>
                    <span class="profile-field-value"><?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Login</span>
                    <span class="profile-field-value"><?= e($utilisateur['login']) ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Email</span>
                    <span class="profile-field-value"><?= e($utilisateur['email']) ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Téléphone</span>
                    <span class="profile-field-value"><?= e($utilisateur['telephone'] ?: '—') ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Rôle</span>
                    <span class="profile-field-value">
                        <span class="role-badge <?= strtolower($utilisateur['role_nom']) ?>">
                            <i class="fa-solid fa-circle"></i> <?= e($utilisateur['role_nom']) ?>
                        </span>
                    </span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Niveau d'accès</span>
                    <span class="profile-field-value"><?= $utilisateur['niveau_acces'] ?>/10</span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Dernière connexion</span>
                    <span class="profile-field-value"><?= $utilisateur['derniere_connexion'] ? formatDateTime($utilisateur['derniere_connexion']) : 'Jamais' ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Dernière IP</span>
                    <span class="profile-field-value"><?= e($utilisateur['derniere_ip'] ?: '—') ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Date de création</span>
                    <span class="profile-field-value"><?= formatDate($utilisateur['created_at'], 'd/m/Y à H:i') ?></span>
                </div>
                <div class="profile-field">
                    <span class="profile-field-label">Statut</span>
                    <span class="profile-field-value">
                        <?php if ($utilisateur['est_actif']): ?>
                            <span class="badge badge-success">Actif</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inactif</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="profile-section">
            <h5 class="profile-section-title">
                <i class="fa-solid fa-chart-simple"></i> Statistiques d'activité
            </h5>
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-right-to-bracket"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $statsUser['total_connexions'] ?></div>
                        <div class="stat-label">Connexions</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-pen-to-square"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?= $statsUser['total_modifications'] ?></div>
                        <div class="stat-label">Actions effectuées</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-content">
                        <div class="stat-value" style="font-size:0.9rem;"><?= $statsUser['derniere_activite'] ?></div>
                        <div class="stat-label">Dernière activité</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historique d'activité -->
        <div class="profile-section">
            <h5 class="profile-section-title">
                <i class="fa-solid fa-clock-rotate-left"></i> Historique d'activité (30 dernières actions)
            </h5>
            <?php if (empty($historique)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <p>Aucune activité enregistrée</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Adresse IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historique as $log): ?>
                                <tr>
                                    <td style="white-space:nowrap;"><?= formatDateTime($log['date_action']) ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match($log['action']) {
                                            'Connexion' => 'success',
                                            'Déconnexion' => 'secondary',
                                            'Tentative échouée' => 'danger',
                                            'Création' => 'info',
                                            'Modification' => 'warning',
                                            'Suppression' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge badge-<?= $badgeClass ?>"><?= e($log['action']) ?></span>
                                    </td>
                                    <td><?= e($log['description'] ?? '—') ?></td>
                                    <td><small class="text-muted"><?= e($log['adresse_ip'] ?? '—') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="d-flex gap-sm mt-4 mb-4">
    <?php if (can('utilisateurs.modifier')): ?>
        <a href="/gestion_scolaire/utilisateurs/modifier/<?= $userId ?>" class="btn btn-primary">
            <i class="fa-solid fa-pen-to-square"></i> Modifier cet utilisateur
        </a>
        <?php if ($userId !== (int) ($_SESSION['utilisateur_id'] ?? 0)): ?>
            <?php if (can('utilisateurs.supprimer')): ?>
                <button type="button" class="btn btn-danger" onclick="deleteUser(<?= $userId ?>, '<?= e($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?>')">
                    <i class="fa-solid fa-trash"></i> Supprimer cet utilisateur
                </button>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
    <a href="/gestion_scolaire/utilisateurs" class="btn btn-outline-primary">
        <i class="fa-solid fa-list"></i> Retour à la liste
    </a>
</div>



<?php
require_once VIEWS_PATH . 'templates' . DIRECTORY_SEPARATOR . 'footer.php';
?>