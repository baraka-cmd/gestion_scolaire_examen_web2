<?php
/**
 * Fichier : src/controllers/ParentController.php
 * Rôle    : Gestion complète des parents/tuteurs (CRUD + liaison élèves)
 */

defined('ACCESS') or die('Accès interdit');

class ParentController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Point d'entrée pour les requêtes AJAX
     */
    public function handleAjax(): void
    {
        if (!can('parents.consulter')) {
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
            case 'getParent':
                $this->getParent();
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
            case 'getEnfants':
                $this->getEnfants();
                break;
            case 'lierEleve':
                $this->lierEleve();
                break;
            case 'delierEleve':
                $this->delierEleve();
                break;
            case 'getElevesDisponibles':
                $this->getElevesDisponibles();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    /**
     * Liste paginée des parents
     */
    private function getListe(): void
    {
        $page       = max(1, (int) post('page', 1));
        $recherche  = post('recherche', '');
        $lien       = post('lien', '');
        $ordre      = post('ordre', 'nom');
        $direction  = post('direction', 'ASC');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        if (!empty($recherche)) {
            $where[] = "(p.nom LIKE :r1 OR p.prenom LIKE :r2 OR p.telephone_principal LIKE :r3 OR p.email LIKE :r4)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
            $params['r4'] = "%{$recherche}%";
        }

        if (!empty($lien)) {
            $where[] = "p.lien = :lien";
            $params['lien'] = $lien;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $colonnesAutorisees = ['id', 'nom', 'prenom', 'lien', 'telephone_principal', 'email', 'nb_enfants'];
        if (!in_array($ordre, $colonnesAutorisees)) $ordre = 'nom';
        if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) $direction = 'ASC';

        $orderMap = ['nb_enfants' => 'nb_enfants'];
        $orderCol = $orderMap[$ordre] ?? 'p.' . $ordre;

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM parents_tuteurs p {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;
        $parents = $this->db->fetchAll(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM eleve_parent ep WHERE ep.parent_id = p.id) AS nb_enfants,
                    (SELECT GROUP_CONCAT(CONCAT(e.prenom, ' ', e.nom) SEPARATOR ', ')
                     FROM eleve_parent ep 
                     INNER JOIN eleves e ON ep.eleve_id = e.id 
                     WHERE ep.parent_id = p.id AND e.est_actif = 1) AS enfants_noms
             FROM parents_tuteurs p
             {$whereClause}
             ORDER BY {$orderCol} {$direction}
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        jsonResponse(true, 'Liste récupérée.', [
            'parents' => $parents,
            'total'   => $total,
            'page'    => $page,
            'pages'   => ceil($total / $parPage)
        ]);
    }

    /**
     * Récupère un parent par ID avec ses enfants
     */
    private function getParent(): void
    {
        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $parent = $this->db->fetchOne(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM eleve_parent ep WHERE ep.parent_id = p.id) AS nb_enfants
             FROM parents_tuteurs p
             WHERE p.id = :id",
            ['id' => $id]
        );

        if (!$parent) jsonResponse(false, 'Parent introuvable.', [], 404);

        // Récupérer les enfants
        $enfants = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe,
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                    TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age,
                    c.nom AS classe_nom, c.niveau AS classe_niveau,
                    ep.est_tuteur_principal
             FROM eleve_parent ep
             INNER JOIN eleves e ON ep.eleve_id = e.id AND e.est_actif = 1
             LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             LEFT JOIN classes c ON i.classe_id = c.id
             WHERE ep.parent_id = :pid
             ORDER BY ep.est_tuteur_principal DESC, e.nom",
            ['pid' => $id]
        );

        jsonResponse(true, 'Parent récupéré.', [
            'parent'  => $parent,
            'enfants' => $enfants
        ]);
    }

    /**
     * Récupère les enfants d'un parent
     */
    private function getEnfants(): void
    {
        $parentId = (int) post('parent_id', 0);
        if ($parentId <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $enfants = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe,
                    c.nom AS classe_nom,
                    ep.est_tuteur_principal
             FROM eleve_parent ep
             INNER JOIN eleves e ON ep.eleve_id = e.id AND e.est_actif = 1
             LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             LEFT JOIN classes c ON i.classe_id = c.id
             WHERE ep.parent_id = :pid
             ORDER BY e.nom",
            ['pid' => $parentId]
        );

        jsonResponse(true, 'Enfants récupérés.', ['enfants' => $enfants]);
    }

    /**
     * Récupère les élèves disponibles (non liés à ce parent)
     */
    private function getElevesDisponibles(): void
    {
        $parentId = (int) post('parent_id', 0);

        if ($parentId > 0) {
            $eleves = $this->db->fetchAll(
                "SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe,
                        c.nom AS classe_nom
                 FROM eleves e
                 LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
                 LEFT JOIN classes c ON i.classe_id = c.id
                 WHERE e.est_actif = 1
                 AND e.id NOT IN (SELECT eleve_id FROM eleve_parent WHERE parent_id = :pid)
                 ORDER BY e.nom",
                ['pid' => $parentId]
            );
        } else {
            $eleves = $this->db->fetchAll(
                "SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe,
                        c.nom AS classe_nom
                 FROM eleves e
                 LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
                 LEFT JOIN classes c ON i.classe_id = c.id
                 WHERE e.est_actif = 1
                 ORDER BY e.nom"
            );
        }

        jsonResponse(true, 'Élèves disponibles.', ['eleves' => $eleves]);
    }

    /**
     * Crée un nouveau parent
     */
    private function creer(): void
    {
        requirePermission('parents.creer');

        $nom              = post('nom', '');
        $prenom           = post('prenom', '');
        $lien             = post('lien', '');
        $profession       = post('profession', '');
        $telephone        = post('telephone_principal', '');
        $telephone2       = post('telephone_secondaire', '');
        $email            = post('email', '');
        $adresse          = post('adresse', '');
        $estUrgence       = (int) post('est_contact_urgence', 1);
        $creerCompte      = (int) post('creer_compte', 0);
        $elevesLies       = post('eleves_lies', '');

        // Validation
        $errors = [];

        if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
        elseif (mb_strlen($nom) < 2) $errors['nom'] = 'Minimum 2 caractères.';

        if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
        elseif (mb_strlen($prenom) < 2) $errors['prenom'] = 'Minimum 2 caractères.';

        if (isBlank($telephone)) $errors['telephone_principal'] = 'Le téléphone est obligatoire.';
        elseif (!isValidPhone($telephone)) $errors['telephone_principal'] = 'Format invalide.';
        else {
            $telExiste = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM parents_tuteurs WHERE telephone_principal = :tel",
                ['tel' => $telephone]
            );
            if ($telExiste > 0) $errors['telephone_principal'] = 'Ce numéro est déjà utilisé.';
        }

        if (!isBlank($email) && !isValidEmail($email)) {
            $errors['email'] = 'Email invalide.';
        }

        if (!isBlank($telephone2) && !isValidPhone($telephone2)) {
            $errors['telephone_secondaire'] = 'Format invalide.';
        }

        if (isBlank($lien)) $errors['lien'] = 'Le lien de parenté est obligatoire.';

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->beginTransaction();

            $parentId = $this->db->insert(
                "INSERT INTO parents_tuteurs (nom, prenom, lien, profession, telephone_principal, 
                     telephone_secondaire, email, adresse, est_contact_urgence)
                 VALUES (:nom, :prenom, :lien, :prof, :tel1, :tel2, :email, :adresse, :urgence)",
                [
                    'nom'      => formatNom($nom),
                    'prenom'   => formatNom($prenom),
                    'lien'     => $lien,
                    'prof'     => $profession ?: null,
                    'tel1'     => $telephone,
                    'tel2'     => $telephone2 ?: null,
                    'email'    => $email ?: null,
                    'adresse'  => $adresse ?: null,
                    'urgence'  => $estUrgence
                ]
            );

            // Créer un compte utilisateur parent si demandé
            if ($creerCompte && !empty($email)) {
                $login = $this->generateLogin($nom, $prenom);
                $hashedPassword = password_hash('Parent@2025', PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

                $userId = $this->db->insert(
                    "INSERT INTO utilisateurs (nom, prenom, email, login, mot_de_passe, role_id, est_actif)
                     VALUES (:nom, :prenom, :email, :login, :pass, 6, 1)",
                    [
                        'nom'    => formatNom($nom),
                        'prenom' => formatNom($prenom),
                        'email'  => $email,
                        'login'  => $login,
                        'pass'   => $hashedPassword
                    ]
                );

                $this->db->update(
                    "UPDATE parents_tuteurs SET utilisateur_id = :uid WHERE id = :pid",
                    ['uid' => $userId, 'pid' => $parentId]
                );
            }

            // Lier les élèves
            if (!empty($elevesLies)) {
                $elevesArray = is_array($elevesLies) ? $elevesLies : explode(',', $elevesLies);
                foreach ($elevesArray as $index => $eleveId) {
                    $eleveId = (int) trim($eleveId);
                    if ($eleveId > 0) {
                        $existe = $this->db->fetchColumn(
                            "SELECT COUNT(*) FROM eleve_parent WHERE eleve_id = :eid AND parent_id = :pid",
                            ['eid' => $eleveId, 'pid' => $parentId]
                        );
                        if ($existe == 0) {
                            $this->db->query(
                                "INSERT INTO eleve_parent (eleve_id, parent_id, est_tuteur_principal) 
                                 VALUES (:eid, :pid, :principal)",
                                ['eid' => $eleveId, 'pid' => $parentId, 'principal' => $index === 0 ? 1 : 0]
                            );
                        }
                    }
                }
            }

            $this->db->commit();

            logActivity('Création', 'parents_tuteurs', $parentId, "Parent créé : {$nom} {$prenom}");

            jsonResponse(true, 'Parent créé avec succès.', ['id' => $parentId]);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur création parent: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la création du parent.');
        }
    }

    /**
     * Modifie un parent existant
     */
    private function modifier(): void
    {
        requirePermission('parents.modifier');

        $id               = (int) post('id', 0);
        $nom              = post('nom', '');
        $prenom           = post('prenom', '');
        $lien             = post('lien', '');
        $profession       = post('profession', '');
        $telephone        = post('telephone_principal', '');
        $telephone2       = post('telephone_secondaire', '');
        $email            = post('email', '');
        $adresse          = post('adresse', '');
        $estUrgence       = (int) post('est_contact_urgence', 1);

        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $existant = $this->db->fetchOne("SELECT id FROM parents_tuteurs WHERE id = :id", ['id' => $id]);
        if (!$existant) jsonResponse(false, 'Parent introuvable.', [], 404);

        $errors = [];

        if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
        if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
        if (isBlank($telephone)) $errors['telephone_principal'] = 'Le téléphone est obligatoire.';
        elseif (!isValidPhone($telephone)) $errors['telephone_principal'] = 'Format invalide.';
        else {
            $telExiste = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM parents_tuteurs WHERE telephone_principal = :tel AND id != :id",
                ['tel' => $telephone, 'id' => $id]
            );
            if ($telExiste > 0) $errors['telephone_principal'] = 'Ce numéro est déjà utilisé.';
        }

        if (!isBlank($email) && !isValidEmail($email)) $errors['email'] = 'Email invalide.';
        if (!isBlank($telephone2) && !isValidPhone($telephone2)) $errors['telephone_secondaire'] = 'Format invalide.';
        if (isBlank($lien)) $errors['lien'] = 'Le lien est obligatoire.';

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->update(
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
                    'id'       => $id
                ]
            );

            logActivity('Modification', 'parents_tuteurs', $id, "Parent modifié : {$nom} {$prenom}");

            jsonResponse(true, 'Parent modifié avec succès.');

        } catch (Exception $e) {
            error_log('Erreur modification parent: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la modification.');
        }
    }

    /**
     * Supprime un parent (vérifie les enfants liés)
     */
    private function supprimer(): void
    {
        requirePermission('parents.supprimer');

        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $parent = $this->db->fetchOne("SELECT id, nom, prenom FROM parents_tuteurs WHERE id = :id", ['id' => $id]);
        if (!$parent) jsonResponse(false, 'Parent introuvable.', [], 404);

        // Vérifier les enfants liés
        $nbEnfants = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM eleve_parent WHERE parent_id = :pid",
            ['pid' => $id]
        );

        if ($nbEnfants > 0) {
            jsonResponse(false, "Impossible de supprimer : {$nbEnfants} enfant(s) lié(s). Dissociez-les d'abord.", [], 409);
        }

        try {
            $this->db->delete("DELETE FROM parents_tuteurs WHERE id = :id", ['id' => $id]);

            logActivity('Suppression', 'parents_tuteurs', $id, "Parent supprimé : {$parent['nom']} {$parent['prenom']}");

            jsonResponse(true, 'Parent supprimé avec succès.');

        } catch (Exception $e) {
            error_log('Erreur suppression parent: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la suppression.');
        }
    }

    /**
     * Lie un élève à un parent
     */
    private function lierEleve(): void
    {
        $parentId = (int) post('parent_id', 0);
        $eleveId  = (int) post('eleve_id', 0);
        $principal = (int) post('est_tuteur_principal', 0);

        if ($parentId <= 0 || $eleveId <= 0) jsonResponse(false, 'Paramètres invalides.', [], 400);

        $existe = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM eleve_parent WHERE eleve_id = :eid AND parent_id = :pid",
            ['eid' => $eleveId, 'pid' => $parentId]
        );

        if ($existe > 0) {
            jsonResponse(false, 'Cet élève est déjà lié à ce parent.', [], 409);
        }

        // Si principal, retirer le statut principal des autres
        if ($principal) {
            $this->db->update(
                "UPDATE eleve_parent SET est_tuteur_principal = 0 WHERE parent_id = :pid",
                ['pid' => $parentId]
            );
        }

        $this->db->query(
            "INSERT INTO eleve_parent (eleve_id, parent_id, est_tuteur_principal) VALUES (:eid, :pid, :principal)",
            ['eid' => $eleveId, 'pid' => $parentId, 'principal' => $principal]
        );

        logActivity('Liaison', 'eleve_parent', null, "Élève {$eleveId} lié au parent {$parentId}");

        jsonResponse(true, 'Élève lié avec succès.');
    }

    /**
     * Délie un élève d'un parent
     */
    private function delierEleve(): void
    {
        $parentId = (int) post('parent_id', 0);
        $eleveId  = (int) post('eleve_id', 0);

        if ($parentId <= 0 || $eleveId <= 0) jsonResponse(false, 'Paramètres invalides.', [], 400);

        // Vérifier que ce n'est pas le seul parent de l'élève
        $nbParents = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM eleve_parent WHERE eleve_id = :eid",
            ['eid' => $eleveId]
        );

        if ($nbParents <= 1) {
            jsonResponse(false, 'Impossible de dissocier : l\'élève doit avoir au moins un parent.', [], 409);
        }

        $this->db->delete(
            "DELETE FROM eleve_parent WHERE eleve_id = :eid AND parent_id = :pid",
            ['eid' => $eleveId, 'pid' => $parentId]
        );

        logActivity('Dissociation', 'eleve_parent', null, "Élève {$eleveId} dissocié du parent {$parentId}");

        jsonResponse(true, 'Élève dissocié avec succès.');
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Génère un login unique pour le compte parent
     */
    private function generateLogin(string $nom, string $prenom): string
    {
        $base = 'parent.' . slugify($prenom) . '.' . slugify($nom);
        $login = $base;
        $i = 1;

        while ($this->db->fetchColumn("SELECT COUNT(*) FROM utilisateurs WHERE login = :login", ['login' => $login]) > 0) {
            $login = $base . $i;
            $i++;
        }

        return $login;
    }

    // ============================================================
    // MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère les liens de parenté disponibles
     */
    public function getLiens(): array
    {
        return ['Père', 'Mère', 'Tuteur', 'Tutrice', 'Grand-père', 'Grand-mère', 'Oncle', 'Tante', 'Frère', 'Sœur', 'Autre'];
    }

    /**
     * Récupère un parent complet par ID
     */
    public function getParentDetail(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT p.*,
                    (SELECT COUNT(*) FROM eleve_parent ep WHERE ep.parent_id = p.id) AS nb_enfants
             FROM parents_tuteurs p
             WHERE p.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Récupère les enfants d'un parent
     */
    public function getEnfantsByParent(int $parentId): array
    {
        return $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe,
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                    TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age,
                    c.nom AS classe_nom, c.niveau AS classe_niveau,
                    ep.est_tuteur_principal
             FROM eleve_parent ep
             INNER JOIN eleves e ON ep.eleve_id = e.id AND e.est_actif = 1
             LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             LEFT JOIN classes c ON i.classe_id = c.id
             WHERE ep.parent_id = :pid
             ORDER BY ep.est_tuteur_principal DESC, e.nom",
            ['pid' => $parentId]
        );
    }
}