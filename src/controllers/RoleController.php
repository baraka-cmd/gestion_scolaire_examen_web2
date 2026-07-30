<?php
/**
 * Fichier : src/controllers/RoleController.php
 * Rôle    : Gestion des rôles et permissions
 */

defined('ACCESS') or die('Accès interdit');

class RoleController
{
    private Database $db;
    private Security $security;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->security = Security::getInstance();
    }

    /**
     * Point d'entrée pour les requêtes AJAX
     */
    public function handleAjax(): void
    {
        // Vérifier la permission
        if (!can('roles.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'getRole':
                $this->getRole();
                break;
            case 'updatePermissions':
                $this->updatePermissions();
                break;
            case 'createRole':
                $this->createRole();
                break;
            case 'deleteRole':
                $this->deleteRole();
                break;
            case 'assignPermission':
                $this->assignPermission();
                break;
            case 'revokePermission':
                $this->revokePermission();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    /**
     * Affiche la liste des rôles
     */
    public function index(): array
    {
        requirePermission('roles.consulter');

        return $this->security->getAllRoles();
    }

    /**
     * Récupère les détails d'un rôle et ses permissions
     */
    public function getRole(): void
    {
        requirePermission('roles.consulter');

        $roleId = (int) post('role_id', 0);

        if ($roleId <= 0) {
            jsonResponse(false, 'ID du rôle invalide.', [], 400);
        }

        $role = $this->security->getRoleById($roleId);
        if (!$role) {
            jsonResponse(false, 'Rôle introuvable.', [], 404);
        }

        $permissions = $this->security->getRolePermissions($roleId);
        $allPermissions = $this->security->getAllPermissionsGrouped();

        jsonResponse(true, 'Rôle récupéré.', [
            'role' => $role,
            'permissions' => $permissions,
            'allPermissions' => $allPermissions
        ]);
    }

    /**
     * Met à jour les permissions d'un rôle
     */
    public function updatePermissions(): void
{
    requirePermission('roles.modifier');

    $roleId = (int) post('role_id', 0);
    $permissionIds = post('permissions', '');

    if ($roleId <= 0) {
        jsonResponse(false, 'ID du rôle invalide.', [], 400);
    }

    // Décoder le JSON
    $permissionIds = json_decode($permissionIds, true);
    
    if (!is_array($permissionIds)) {
        $permissionIds = [];
    }
    
    // Convertir en entiers
    $permissionIds = array_map('intval', $permissionIds);
    
    // Filtrer les valeurs invalides
    $permissionIds = array_filter($permissionIds, function($id) { return $id > 0; });

    $success = $this->security->updateRolePermissions($roleId, $permissionIds);

    if ($success) {
        logActivity('Modification', 'roles', $roleId, 'Permissions mises à jour');
        jsonResponse(true, 'Permissions mises à jour avec succès.');
    } else {
        jsonResponse(false, 'Erreur lors de la mise à jour des permissions.');
    }
}

    /**
     * Crée un nouveau rôle
     */
    public function createRole(): void
    {
        requirePermission('roles.creer');

        $nom = post('nom', '');
        $description = post('description', '');
        $niveauAcces = (int) post('niveau_acces', 1);

        // Validation
        $errors = [];

        if (isBlank($nom)) {
            $errors['nom'] = 'Le nom du rôle est obligatoire.';
        } elseif (mb_strlen($nom) < 2) {
            $errors['nom'] = 'Minimum 2 caractères.';
        } elseif (mb_strlen($nom) > 50) {
            $errors['nom'] = 'Maximum 50 caractères.';
        } else {
            // Vérifier l'unicité
            $exists = $this->db->exists(
                "SELECT id FROM roles WHERE nom = :nom",
                ['nom' => $nom]
            );
            if ($exists) {
                $errors['nom'] = 'Ce rôle existe déjà.';
            }
        }

        if (isBlank($description)) {
            $errors['description'] = 'La description est obligatoire.';
        }

        if ($niveauAcces < 1 || $niveauAcces > 10) {
            $errors['niveau_acces'] = 'Niveau d\'accès entre 1 et 10.';
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        $roleId = $this->security->createRole($nom, $description, $niveauAcces);

        if ($roleId) {
            logActivity('Création', 'roles', $roleId, "Rôle créé : {$nom}");
            jsonResponse(true, 'Rôle créé avec succès.', ['role_id' => $roleId]);
        } else {
            jsonResponse(false, 'Erreur lors de la création du rôle.');
        }
    }

    /**
     * Supprime un rôle
     */
    public function deleteRole(): void
    {
        requirePermission('roles.supprimer');

        $roleId = (int) post('role_id', 0);

        if ($roleId <= 0) {
            jsonResponse(false, 'ID du rôle invalide.', [], 400);
        }

        // Empêcher la suppression des rôles systèmes
        if ($roleId <= 6) {
            jsonResponse(false, 'Impossible de supprimer un rôle système.', [], 403);
        }

        // Vérifier si des utilisateurs utilisent ce rôle
        $usersCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM utilisateurs WHERE role_id = :role_id",
            ['role_id' => $roleId]
        );

        if ($usersCount > 0) {
            jsonResponse(false, "Impossible de supprimer ce rôle : {$usersCount} utilisateur(s) l'utilisent.", [], 409);
        }

        $success = $this->security->deleteRole($roleId);

        if ($success) {
            logActivity('Suppression', 'roles', $roleId, 'Rôle supprimé');
            jsonResponse(true, 'Rôle supprimé avec succès.');
        } else {
            jsonResponse(false, 'Erreur lors de la suppression du rôle.');
        }
    }

    /**
     * Assigne une permission à un rôle
     */
    public function assignPermission(): void
    {
        requirePermission('roles.modifier');

        $roleId = (int) post('role_id', 0);
        $permissionId = (int) post('permission_id', 0);

        if ($roleId <= 0 || $permissionId <= 0) {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }

        $success = $this->security->assignPermission($roleId, $permissionId);

        if ($success) {
            jsonResponse(true, 'Permission assignée.');
        } else {
            jsonResponse(false, 'Erreur lors de l\'assignation.');
        }
    }

    /**
     * Révoque une permission d'un rôle
     */
    public function revokePermission(): void
    {
        requirePermission('roles.modifier');

        $roleId = (int) post('role_id', 0);
        $permissionId = (int) post('permission_id', 0);

        if ($roleId <= 0 || $permissionId <= 0) {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }

        $success = $this->security->revokePermission($roleId, $permissionId);

        if ($success) {
            jsonResponse(true, 'Permission révoquée.');
        } else {
            jsonResponse(false, 'Erreur lors de la révocation.');
        }
    }

    /**
     * Récupère toutes les permissions groupées par module
     */
    public function getAllPermissions(): array
    {
        return $this->security->getAllPermissionsGrouped();
    }

    /**
     * Récupère les permissions d'un rôle spécifique
     */
    public function getPermissionsForRole(int $roleId): array
    {
        $allPermissions = $this->security->getAllPermissionsGrouped();
        $rolePermissions = $this->security->getRolePermissions($roleId);
        $rolePermIds = array_column($rolePermissions, 'id');

        // Ajouter un flag "assigned" à chaque permission
        foreach ($allPermissions as &$module) {
            foreach ($module['permissions'] as &$perm) {
                $perm['assigned'] = in_array($perm['id'], $rolePermIds);
            }
        }

        return $allPermissions;
    }
}