<?php
/**
 * Fichier : src/core/Security.php
 * Rôle    : Gestion de la sécurité, permissions et accès
 */

defined('ACCESS') or die('Accès interdit');

class Security
{
    private static ?Security $instance = null;
    private Database $db;
    private ?array $userPermissions = null;

    /**
     * Constructeur privé (Singleton)
     */
    private function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Instance unique
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ============================================================
    // VÉRIFICATION DES PERMISSIONS
    // ============================================================

    /**
     * Vérifie si l'utilisateur connecté a une permission spécifique
     */
    public function can(string $permissionCode): bool
    {
        // Admin a toujours toutes les permissions
        if (isAdmin()) {
            return true;
        }

        // Charger les permissions si pas encore fait
        if ($this->userPermissions === null) {
            $this->loadUserPermissions();
        }

        // Vérifier la permission
        return in_array($permissionCode, $this->userPermissions ?? []);
    }

    /**
     * Vérifie plusieurs permissions (ET logique)
     */
    public function canAll(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->can($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Vérifie au moins une permission (OU logique)
     */
    public function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Exige une permission — redirige si non autorisé
     */
    public function requirePermission(string $permissionCode): void
    {
        if (!$this->can($permissionCode)) {
            $this->denyAccess('Vous n\'avez pas la permission nécessaire : ' . $permissionCode);
        }
    }

    /**
     * Exige plusieurs permissions (ET)
     */
    public function requireAllPermissions(array $permissions): void
    {
        if (!$this->canAll($permissions)) {
            $this->denyAccess('Permissions insuffisantes.');
        }
    }

    /**
     * Exige au moins une permission (OU)
     */
    public function requireAnyPermission(array $permissions): void
    {
        if (!$this->canAny($permissions)) {
            $this->denyAccess('Permissions insuffisantes.');
        }
    }

    /**
     * Vérifie l'accès à un module
     */
    public function canAccessModule(string $module): bool
    {
        return $this->can($module . '.consulter');
    }

    /**
     * Vérifie si peut créer dans un module
     */
    public function canCreate(string $module): bool
    {
        return $this->can($module . '.creer');
    }

    /**
     * Vérifie si peut modifier dans un module
     */
    public function canEdit(string $module): bool
    {
        return $this->can($module . '.modifier');
    }

    /**
     * Vérifie si peut supprimer dans un module
     */
    public function canDelete(string $module): bool
    {
        return $this->can($module . '.supprimer');
    }

    // ============================================================
    // GESTION DES RÔLES
    // ============================================================

    /**
     * Récupère toutes les permissions d'un rôle
     */
    public function getRolePermissions(int $roleId): array
    {
        return $this->db->fetchAll(
            "SELECT p.id, p.code, p.libelle, p.module, p.action, p.description
             FROM permissions p
             INNER JOIN role_permission rp ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id
             ORDER BY p.module, p.action",
            ['role_id' => $roleId]
        );
    }

    /**
     * Récupère toutes les permissions groupées par module
     */
    public function getAllPermissionsGrouped(): array
    {
        $permissions = $this->db->fetchAll(
            "SELECT id, code, libelle, module, action, description
             FROM permissions
             ORDER BY module, action"
        );

        $grouped = [];
        foreach ($permissions as $perm) {
            $module = $perm['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [
                    'module' => $module,
                    'permissions' => []
                ];
            }
            $grouped[$module]['permissions'][] = $perm;
        }

        return array_values($grouped);
    }

    /**
     * Récupère tous les rôles avec leur nombre de permissions
     */
    public function getAllRoles(): array
    {
        return $this->db->fetchAll(
            "SELECT r.id, r.nom, r.description, r.niveau_acces, r.est_actif,
                    COUNT(rp.id) as nb_permissions
             FROM roles r
             LEFT JOIN role_permission rp ON r.id = rp.role_id
             GROUP BY r.id, r.nom, r.description, r.niveau_acces, r.est_actif
             ORDER BY r.niveau_acces DESC"
        );
    }

    /**
     * Récupère un rôle par son ID
     */
    public function getRoleById(int $roleId): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, nom, description, niveau_acces, est_actif
             FROM roles WHERE id = :id",
            ['id' => $roleId]
        );
    }

    /**
     * Assigne une permission à un rôle
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        try {
            $this->db->query(
                "INSERT IGNORE INTO role_permission (role_id, permission_id) VALUES (:role_id, :perm_id)",
                ['role_id' => $roleId, 'perm_id' => $permissionId]
            );
            return true;
        } catch (Exception $e) {
            error_log('Erreur assignPermission: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Révoque une permission d'un rôle
     */
    public function revokePermission(int $roleId, int $permissionId): bool
    {
        try {
            $this->db->query(
                "DELETE FROM role_permission WHERE role_id = :role_id AND permission_id = :perm_id",
                ['role_id' => $roleId, 'perm_id' => $permissionId]
            );
            return true;
        } catch (Exception $e) {
            error_log('Erreur revokePermission: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour toutes les permissions d'un rôle
     */
    public function updateRolePermissions(int $roleId, array $permissionIds): bool
    {
        try {
            // Supprimer toutes les permissions actuelles
            $this->db->query(
                "DELETE FROM role_permission WHERE role_id = :role_id",
                ['role_id' => $roleId]
            );

            // Insérer les nouvelles permissions
            if (!empty($permissionIds)) {
                foreach ($permissionIds as $permId) {
                    $this->db->query(
                        "INSERT INTO role_permission (role_id, permission_id) VALUES (:role_id, :perm_id)",
                        ['role_id' => $roleId, 'perm_id' => $permId]
                    );
                }
            }

            return true;
        } catch (Exception $e) {
            error_log('Erreur updateRolePermissions: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée un nouveau rôle
     */
    public function createRole(string $nom, string $description, int $niveauAcces = 1): ?int
    {
        try {
            return $this->db->insert(
                "INSERT INTO roles (nom, description, niveau_acces, est_actif) VALUES (:nom, :desc, :niveau, 1)",
                ['nom' => $nom, 'desc' => $description, 'niveau' => $niveauAcces]
            );
        } catch (Exception $e) {
            error_log('Erreur createRole: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Supprime un rôle
     */
    public function deleteRole(int $roleId): bool
    {
        // Empêcher la suppression des rôles systèmes (1 à 6)
        if ($roleId <= 6) {
            return false;
        }

        try {
            $this->db->query("DELETE FROM roles WHERE id = :id", ['id' => $roleId]);
            return true;
        } catch (Exception $e) {
            error_log('Erreur deleteRole: ' . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Charge les permissions de l'utilisateur connecté
     */
    private function loadUserPermissions(): void
    {
        $roleId = $_SESSION['utilisateur_role_id'] ?? 0;

        if ($roleId === 0) {
            $this->userPermissions = [];
            return;
        }

        // Admin (role_id = 1) a tout
        if ((int) $roleId === 1) {
            $this->userPermissions = ['*']; // Wildcard = tout
            return;
        }

        $this->userPermissions = $this->db->fetchAll(
            "SELECT p.code
             FROM permissions p
             INNER JOIN role_permission rp ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id",
            ['role_id' => $roleId]
        );

        // Extraire uniquement les codes
        $this->userPermissions = array_column($this->userPermissions, 'code');
    }

    /**
     * Rafraîchit le cache des permissions
     */
    public function refreshPermissions(): void
    {
        $this->userPermissions = null;
        $this->loadUserPermissions();
    }

    /**
     * Refuse l'accès et redirige
     */
    private function denyAccess(string $message = 'Accès refusé.'): void
    {
        if (isAjax()) {
            jsonResponse(false, $message, [], 403);
        }

        // Journaliser la tentative
        logActivity('Accès refusé', null, null, $message);

        setFlash('error', $message);
        redirect(BASE_URL . 'views/dashboard/');
    }

    // ============================================================
    // SÉCURITÉ GÉNÉRALE
    // ============================================================

    /**
     * Nettoie une valeur contre XSS
     */
    public static function cleanXSS(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Vérifie et régénère le token CSRF si nécessaire
     */
    public function validateCsrf(): void
    {
        $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!verifyCsrfToken($token)) {
            if (isAjax()) {
                jsonResponse(false, 'Token CSRF invalide. Veuillez rafraîchir la page.', [], 419);
            }
            setFlash('error', 'Session expirée. Veuillez réessayer.');
            redirect(BASE_URL . 'views/auth/login.php?session_expired=1');
        }
    }

    /**
     * Vérifie le timeout de session
     */
    public function checkSessionTimeout(): void
    {
        if (!isLoggedIn()) return;

        $sessionTime = $_SESSION['session_time'] ?? 0;
        if (time() - $sessionTime > SESSION_TIMEOUT) {
            // Session expirée
            $_SESSION = [];
            session_destroy();

            if (isAjax()) {
                jsonResponse(false, 'Session expirée.', [], 401);
            }

            redirect(BASE_URL . 'views/auth/login.php?session_expired=1');
        }

        // Rafraîchir le timestamp
        $_SESSION['session_time'] = time();
    }

    /**
     * Vérifie l'intégrité de la session (anti-hijacking)
     */
    public function validateSessionIntegrity(): void
    {
        if (!isLoggedIn()) return;

        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessionIp = $_SESSION['session_ip'] ?? '';
        $sessionAgent = $_SESSION['session_agent'] ?? '';

        // Stocker au premier chargement
        if (empty($sessionIp)) {
            $_SESSION['session_ip'] = $currentIp;
            $_SESSION['session_agent'] = $currentAgent;
            return;
        }

        // Vérifier l'IP (désactivé pour les connexions mobiles qui changent d'IP)
        // if ($currentIp !== $sessionIp) {
        //     $this->destroySession('Changement d\'adresse IP détecté.');
        // }

        // Vérifier le User-Agent
        if ($currentAgent !== $sessionAgent) {
            $this->destroySession('Changement de navigateur détecté.');
        }
    }

    /**
     * Détruit la session pour raison de sécurité
     */
    private function destroySession(string $reason): void
    {
        logActivity('Session détruite', 'utilisateurs', $_SESSION['utilisateur_id'] ?? null, $reason);

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        redirect(BASE_URL . 'views/auth/login.php?session_expired=1');
    }
}
