<?php
/**
 * Fichier : src/controllers/UtilisateurController.php
 * Rôle    : Gestion complète des utilisateurs (CRUD)
 */

defined('ACCESS') or die('Accès interdit');

class UtilisateurController
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
        if (!can('utilisateurs.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'liste':
                $this->getListe();
                break;
            case 'getUtilisateur':
                $this->getUtilisateur();
                break;
            case 'creer':
                $this->creer();
                break;
            case 'modifier':
                $this->modifier();
                break;
            case 'supprimer':
                $this->supprimer();
                break;
            case 'toggleStatut':
                $this->toggleStatut();
                break;
            case 'changerMotDePasse':
                $this->changerMotDePasse();
                break;
            case 'verifierEmail':
                $this->verifierEmail();
                break;
            case 'verifierLogin':
                $this->verifierLogin();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    /**
     * Liste paginée avec recherche et filtres
     */
    private function getListe(): void
    {
        $page       = max(1, (int) post('page', 1));
        $recherche  = post('recherche', '');
        $role       = post('role', '');
        $statut     = post('statut', '');
        $ordre      = post('ordre', 'nom');
        $direction  = post('direction', 'ASC');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        if (!empty($recherche)) {
            $where[] = "(u.nom LIKE :recherche1 OR u.prenom LIKE :recherche2 OR u.email LIKE :recherche3 OR u.login LIKE :recherche4)";
            $params['recherche1'] = "%{$recherche}%";
            $params['recherche2'] = "%{$recherche}%";
            $params['recherche3'] = "%{$recherche}%";
            $params['recherche4'] = "%{$recherche}%";
        }

        if (!empty($role)) {
            $where[] = "u.role_id = :role";
            $params['role'] = (int) $role;
        }

        if ($statut !== '') {
            $where[] = "u.est_actif = :statut";
            $params['statut'] = (int) $statut;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Colonnes autorisées pour le tri
        $colonnesAutorisees = ['id', 'nom', 'prenom', 'email', 'login', 'role_nom', 'derniere_connexion', 'est_actif'];
        if (!in_array($ordre, $colonnesAutorisees)) $ordre = 'nom';
        if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) $direction = 'ASC';

        // Nombre total
        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM utilisateurs u
             INNER JOIN roles r ON u.role_id = r.id
             {$whereClause}",
            $params
        );

        // Liste paginée
        $offset = ($page - 1) * $parPage;
        $utilisateurs = $this->db->fetchAll(
            "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.login, u.photo,
                    u.est_actif, u.derniere_connexion, u.created_at,
                    r.nom AS role_nom, r.id AS role_id
             FROM utilisateurs u
             INNER JOIN roles r ON u.role_id = r.id
             {$whereClause}
             ORDER BY {$ordre} {$direction}
             LIMIT :limit OFFSET :offset",
            array_merge($params, [
                'limit' => $parPage,
                'offset' => $offset
            ])
        );

        // Formater les données
        foreach ($utilisateurs as &$user) {
            $user['derniere_connexion'] = $user['derniere_connexion'] 
                ? formatDateTime($user['derniere_connexion']) 
                : 'Jamais';
            $user['date_creation'] = formatDate($user['created_at']);
            $user['initials'] = mb_strtoupper(mb_substr($user['prenom'], 0, 1) . mb_substr($user['nom'], 0, 1));
        }

        jsonResponse(true, 'Liste récupérée.', [
            'utilisateurs' => $utilisateurs,
            'total'        => $total,
            'page'         => $page,
            'par_page'     => $parPage,
            'pages'        => ceil($total / $parPage)
        ]);
    }

    /**
     * Récupère un utilisateur par son ID
     */
    private function getUtilisateur(): void
    {
        $id = (int) post('id', 0);

        if ($id <= 0) {
            jsonResponse(false, 'ID invalide.', [], 400);
        }

        $utilisateur = $this->db->fetchOne(
            "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.login, u.photo,
                    u.est_actif, u.derniere_connexion, u.created_at,
                    u.role_id, r.nom AS role_nom
             FROM utilisateurs u
             INNER JOIN roles r ON u.role_id = r.id
             WHERE u.id = :id",
            ['id' => $id]
        );

        if (!$utilisateur) {
            jsonResponse(false, 'Utilisateur introuvable.', [], 404);
        }

        $utilisateur['derniere_connexion'] = $utilisateur['derniere_connexion'] 
            ? formatDateTime($utilisateur['derniere_connexion']) 
            : 'Jamais';
        $utilisateur['date_creation'] = formatDate($utilisateur['created_at']);

        // Historique des connexions
        $historique = $this->db->fetchAll(
            "SELECT action, description, date_action
             FROM logs_activites
             WHERE utilisateur_id = :uid AND action IN ('Connexion', 'Tentative échouée', 'Déconnexion')
             ORDER BY date_action DESC
             LIMIT 20",
            ['uid' => $id]
        );

        jsonResponse(true, 'Utilisateur récupéré.', [
            'utilisateur' => $utilisateur,
            'historique'  => $historique
        ]);
    }

    /**
     * Crée un nouvel utilisateur
     */
    private function creer(): void
    {
        requirePermission('utilisateurs.creer');

        $nom       = post('nom', '');
        $prenom    = post('prenom', '');
        $email     = post('email', '');
        $telephone = post('telephone', '');
        $login     = post('login', '');
        $password  = $_POST['password'] ?? '';
        $roleId    = (int) post('role_id', 0);

        // Validation
        $errors = [];

        if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
        elseif (mb_strlen($nom) < 2) $errors['nom'] = 'Minimum 2 caractères.';
        elseif (mb_strlen($nom) > 100) $errors['nom'] = 'Maximum 100 caractères.';

        if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
        elseif (mb_strlen($prenom) < 2) $errors['prenom'] = 'Minimum 2 caractères.';
        elseif (mb_strlen($prenom) > 100) $errors['prenom'] = 'Maximum 100 caractères.';

        if (isBlank($email)) $errors['email'] = 'L\'email est obligatoire.';
        elseif (!isValidEmail($email)) $errors['email'] = 'Email invalide.';
        elseif ($this->emailExiste($email)) $errors['email'] = 'Cet email est déjà utilisé.';

        if (!isBlank($telephone) && !isValidPhone($telephone)) {
            $errors['telephone'] = 'Numéro invalide.';
        }

        if (isBlank($login)) $errors['login'] = 'Le login est obligatoire.';
        elseif (mb_strlen($login) < 3) $errors['login'] = 'Minimum 3 caractères.';
        elseif (mb_strlen($login) > 50) $errors['login'] = 'Maximum 50 caractères.';
        elseif (!preg_match('/^[a-zA-Z0-9._-]+$/', $login)) $errors['login'] = 'Caractères non autorisés.';
        elseif ($this->loginExiste($login)) $errors['login'] = 'Ce login est déjà utilisé.';

        if (isBlank($password)) $errors['password'] = 'Le mot de passe est obligatoire.';
        elseif (mb_strlen($password) < 8) $errors['password'] = 'Minimum 8 caractères.';
        elseif (!preg_match('/[A-Z]/', $password)) $errors['password'] = 'Au moins une majuscule.';
        elseif (!preg_match('/[a-z]/', $password)) $errors['password'] = 'Au moins une minuscule.';
        elseif (!preg_match('/[0-9]/', $password)) $errors['password'] = 'Au moins un chiffre.';
        elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'\"\\|,.<>\/?~`]/', $password)) $errors['password'] = 'Au moins un caractère spécial.';

        if ($roleId <= 0) $errors['role_id'] = 'Le rôle est obligatoire.';

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

            $userId = $this->db->insert(
                "INSERT INTO utilisateurs (nom, prenom, email, telephone, login, mot_de_passe, role_id, est_actif)
                 VALUES (:nom, :prenom, :email, :telephone, :login, :password, :role_id, 1)",
                [
                    'nom'       => formatNom($nom),
                    'prenom'    => formatNom($prenom),
                    'email'     => mb_strtolower(trim($email)),
                    'telephone' => $telephone ?: null,
                    'login'     => mb_strtolower(trim($login)),
                    'password'  => $hashedPassword,
                    'role_id'   => $roleId
                ]
            );

            $this->db->commit();

            logActivity('Création', 'utilisateurs', $userId, "Création de l'utilisateur {$nom} {$prenom}");

            jsonResponse(true, 'Utilisateur créé avec succès.', ['id' => $userId]);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur création utilisateur: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la création de l\'utilisateur.');
        }
    }

    /**
     * Modifie un utilisateur existant
     */
    private function modifier(): void
    {
        requirePermission('utilisateurs.modifier');

        $id        = (int) post('id', 0);
        $nom       = post('nom', '');
        $prenom    = post('prenom', '');
        $email     = post('email', '');
        $telephone = post('telephone', '');
        $login     = post('login', '');
        $roleId    = (int) post('role_id', 0);

        if ($id <= 0) {
            jsonResponse(false, 'ID invalide.', [], 400);
        }

        // Vérifier que l'utilisateur existe
        $existant = $this->db->fetchOne("SELECT id FROM utilisateurs WHERE id = :id", ['id' => $id]);
        if (!$existant) {
            jsonResponse(false, 'Utilisateur introuvable.', [], 404);
        }

        // Validation
        $errors = [];

        if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
        elseif (mb_strlen($nom) < 2) $errors['nom'] = 'Minimum 2 caractères.';

        if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
        elseif (mb_strlen($prenom) < 2) $errors['prenom'] = 'Minimum 2 caractères.';

        if (isBlank($email)) $errors['email'] = 'L\'email est obligatoire.';
        elseif (!isValidEmail($email)) $errors['email'] = 'Email invalide.';
        elseif ($this->emailExiste($email, $id)) $errors['email'] = 'Cet email est déjà utilisé.';

        if (!isBlank($telephone) && !isValidPhone($telephone)) {
            $errors['telephone'] = 'Numéro invalide.';
        }

        if (isBlank($login)) $errors['login'] = 'Le login est obligatoire.';
        elseif ($this->loginExiste($login, $id)) $errors['login'] = 'Ce login est déjà utilisé.';

        if ($roleId <= 0) $errors['role_id'] = 'Le rôle est obligatoire.';

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->update(
                "UPDATE utilisateurs 
                 SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone,
                     login = :login, role_id = :role_id
                 WHERE id = :id",
                [
                    'nom'       => formatNom($nom),
                    'prenom'    => formatNom($prenom),
                    'email'     => mb_strtolower(trim($email)),
                    'telephone' => $telephone ?: null,
                    'login'     => mb_strtolower(trim($login)),
                    'role_id'   => $roleId,
                    'id'        => $id
                ]
            );

            logActivity('Modification', 'utilisateurs', $id, "Modification de l'utilisateur {$nom} {$prenom}");

            jsonResponse(true, 'Utilisateur modifié avec succès.');

        } catch (Exception $e) {
            error_log('Erreur modification utilisateur: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la modification.');
        }
    }

    /**
     * Supprime un utilisateur
     */
    private function supprimer(): void
    {
        requirePermission('utilisateurs.supprimer');

        $id = (int) post('id', 0);

        if ($id <= 0) {
            jsonResponse(false, 'ID invalide.', [], 400);
        }

        // Empêcher la suppression de son propre compte
        if ($id === (int) ($_SESSION['utilisateur_id'] ?? 0)) {
            jsonResponse(false, 'Vous ne pouvez pas supprimer votre propre compte.', [], 403);
        }

        // Vérifier que l'utilisateur existe
        $utilisateur = $this->db->fetchOne(
            "SELECT id, nom, prenom FROM utilisateurs WHERE id = :id",
            ['id' => $id]
        );

        if (!$utilisateur) {
            jsonResponse(false, 'Utilisateur introuvable.', [], 404);
        }

        try {
            $this->db->delete("DELETE FROM utilisateurs WHERE id = :id", ['id' => $id]);

            logActivity('Suppression', 'utilisateurs', $id, 
                "Suppression de l'utilisateur {$utilisateur['nom']} {$utilisateur['prenom']}");

            jsonResponse(true, 'Utilisateur supprimé avec succès.');

        } catch (Exception $e) {
            error_log('Erreur suppression utilisateur: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la suppression. Vérifiez les dépendances.');
        }
    }

    /**
     * Active/Désactive un utilisateur
     */
    private function toggleStatut(): void
    {
        requirePermission('utilisateurs.activer');

        $id = (int) post('id', 0);

        if ($id <= 0) {
            jsonResponse(false, 'ID invalide.', [], 400);
        }

        if ($id === (int) ($_SESSION['utilisateur_id'] ?? 0)) {
            jsonResponse(false, 'Vous ne pouvez pas désactiver votre propre compte.', [], 403);
        }

        $utilisateur = $this->db->fetchOne(
            "SELECT id, est_actif, nom, prenom FROM utilisateurs WHERE id = :id",
            ['id' => $id]
        );

        if (!$utilisateur) {
            jsonResponse(false, 'Utilisateur introuvable.', [], 404);
        }

        $nouveauStatut = $utilisateur['est_actif'] ? 0 : 1;

        $this->db->update(
            "UPDATE utilisateurs SET est_actif = :statut WHERE id = :id",
            ['statut' => $nouveauStatut, 'id' => $id]
        );

        $action = $nouveauStatut ? 'Activé' : 'Désactivé';
        logActivity($action, 'utilisateurs', $id, 
            "{$action} : {$utilisateur['nom']} {$utilisateur['prenom']}");

        jsonResponse(true, "Utilisateur {$action} avec succès.", ['est_actif' => $nouveauStatut]);
    }

    /**
     * Change le mot de passe (par un admin)
     */
    private function changerMotDePasse(): void
    {
        $id = (int) post('id', 0);
        $password = $_POST['password'] ?? '';

        if ($id <= 0) {
            jsonResponse(false, 'ID invalide.', [], 400);
        }

        // Seul l'utilisateur lui-même ou un admin peut changer le mot de passe
        if ($id !== (int) ($_SESSION['utilisateur_id'] ?? 0) && !isAdmin()) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (isBlank($password)) {
            jsonResponse(false, 'Le mot de passe est obligatoire.', [], 422);
        }
        if (mb_strlen($password) < 8) {
            jsonResponse(false, 'Minimum 8 caractères.', [], 422);
        }

        $hashedPassword = password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

        $this->db->update(
            "UPDATE utilisateurs SET mot_de_passe = :password WHERE id = :id",
            ['password' => $hashedPassword, 'id' => $id]
        );

        logActivity('Réinitialisation mot de passe', 'utilisateurs', $id);

        jsonResponse(true, 'Mot de passe modifié avec succès.');
    }

    /**
     * Vérifie si un email existe déjà
     */
    private function verifierEmail(): void
    {
        $email = post('email', '');
        $excludeId = (int) post('exclude_id', 0);

        if (empty($email)) {
            jsonResponse(false, 'Email requis.', [], 400);
        }

        $existe = $this->emailExiste($email, $excludeId > 0 ? $excludeId : null);

        jsonResponse(true, 'Vérification terminée.', ['existe' => $existe]);
    }

    /**
     * Vérifie si un login existe déjà
     */
    private function verifierLogin(): void
    {
        $login = post('login', '');
        $excludeId = (int) post('exclude_id', 0);

        if (empty($login)) {
            jsonResponse(false, 'Login requis.', [], 400);
        }

        $existe = $this->loginExiste($login, $excludeId > 0 ? $excludeId : null);

        jsonResponse(true, 'Vérification terminée.', ['existe' => $existe]);
    }

    /**
     * Récupère tous les rôles (pour les formulaires)
     */
    public function getRoles(): array
    {
        return $this->db->fetchAll(
            "SELECT id, nom, description FROM roles WHERE est_actif = 1 ORDER BY niveau_acces DESC"
        );
    }

    /**
     * Récupère les logs d'un utilisateur
     */
    public function getLogs(int $userId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT action, table_concernee, description, date_action, adresse_ip
             FROM logs_activites
             WHERE utilisateur_id = :uid
             ORDER BY date_action DESC
             LIMIT :limit",
            ['uid' => $userId, 'limit' => $limit]
        );
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    private function emailExiste(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM utilisateurs WHERE email = :email";
        $params = ['email' => mb_strtolower(trim($email))];

        if ($excludeId) {
            $sql .= " AND id != :exclude";
            $params['exclude'] = $excludeId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    private function loginExiste(string $login, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM utilisateurs WHERE login = :login";
        $params = ['login' => mb_strtolower(trim($login))];

        if ($excludeId) {
            $sql .= " AND id != :exclude";
            $params['exclude'] = $excludeId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }
}
