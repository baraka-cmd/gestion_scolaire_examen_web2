<?php
/**
 * Fichier : src/controllers/EnseignantController.php
 * Rôle    : Gestion complète des enseignants (CRUD + affectations)
 */

defined('ACCESS') or die('Accès interdit');

class EnseignantController
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
        if (!can('enseignants.consulter')) {
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
            case 'getEnseignant':
                $this->getEnseignant();
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
            case 'changerClasse':
                $this->changerClasse();
                break;
            case 'getClassesDisponibles':
                $this->getClassesDisponibles();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    /**
     * Liste paginée des enseignants
     */
    private function getListe(): void
    {
        $page       = max(1, (int) post('page', 1));
        $recherche  = post('recherche', '');
        $specialite = post('specialite', '');
        $statut     = post('statut', '');
        $ordre      = post('ordre', 'nom');
        $direction  = post('direction', 'ASC');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        if (!empty($recherche)) {
            $where[] = "(e.nom LIKE :r1 OR e.prenom LIKE :r2 OR e.matricule LIKE :r3 OR e.email LIKE :r4 OR e.specialite LIKE :r5)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
            $params['r4'] = "%{$recherche}%";
            $params['r5'] = "%{$recherche}%";
        }

        if (!empty($specialite)) {
            $where[] = "e.specialite = :specialite";
            $params['specialite'] = $specialite;
        }

        if ($statut !== '') {
            $where[] = "e.est_actif = :statut";
            $params['statut'] = (int) $statut;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $colonnesAutorisees = ['id', 'nom', 'prenom', 'matricule', 'specialite', 'classe_nom', 'date_embauche'];
        if (!in_array($ordre, $colonnesAutorisees)) $ordre = 'nom';
        if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) $direction = 'ASC';

        $orderMap = ['classe_nom' => 'c.nom'];
        $orderCol = $orderMap[$ordre] ?? 'e.' . $ordre;

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM enseignants e LEFT JOIN classes c ON e.classe_id = c.id {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;
        $enseignants = $this->db->fetchAll(
            "SELECT e.*, c.nom AS classe_nom, c.niveau AS classe_niveau,
                    DATE_FORMAT(e.date_embauche, '%d/%m/%Y') AS date_embauche_format,
                    (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id AND i.statut = 'Actif') AS nb_eleves
             FROM enseignants e
             LEFT JOIN classes c ON e.classe_id = c.id
             {$whereClause}
             ORDER BY {$orderCol} {$direction}
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        jsonResponse(true, 'Liste récupérée.', [
            'enseignants' => $enseignants,
            'total'       => $total,
            'page'        => $page,
            'pages'       => ceil($total / $parPage)
        ]);
    }

    /**
     * Récupère un enseignant par ID avec détails
     */
    private function getEnseignant(): void
    {
        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $enseignant = $this->db->fetchOne(
            "SELECT e.*, 
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                    DATE_FORMAT(e.date_embauche, '%d/%m/%Y') AS date_embauche_format,
                    TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age,
                    TIMESTAMPDIFF(YEAR, e.date_embauche, CURDATE()) AS anciennete,
                    c.nom AS classe_nom, c.niveau AS classe_niveau, c.salle AS classe_salle,
                    c.capacite_max AS classe_capacite,
                    (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id AND i.statut = 'Actif') AS nb_eleves,
                    u.login AS compte_login, u.email AS compte_email
             FROM enseignants e
             LEFT JOIN classes c ON e.classe_id = c.id
             LEFT JOIN utilisateurs u ON e.utilisateur_id = u.id
             WHERE e.id = :id",
            ['id' => $id]
        );

        if (!$enseignant) jsonResponse(false, 'Enseignant introuvable.', [], 404);

        // Récupérer les élèves de sa classe
        $eleves = [];
        if ($enseignant['classe_id']) {
            $eleves = $this->db->fetchAll(
                "SELECT e2.id, e2.matricule, e2.nom, e2.prenom, e2.sexe,
                        DATE_FORMAT(e2.date_naissance, '%d/%m/%Y') AS date_naissance_format
                 FROM inscriptions i
                 INNER JOIN eleves e2 ON i.eleve_id = e2.id
                 WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e2.est_actif = 1
                 ORDER BY e2.nom",
                ['cid' => $enseignant['classe_id']]
            );
        }

        // Récupérer l'emploi du temps
        $emploiDuTemps = [];
        if ($enseignant['classe_id']) {
            $emploiDuTemps = $this->db->fetchAll(
                "SELECT edt.jour, edt.heure_debut, edt.heure_fin, m.libelle AS matiere
                 FROM emploi_du_temps edt
                 INNER JOIN matieres m ON edt.matiere_id = m.id
                 WHERE edt.classe_id = :cid
                 ORDER BY FIELD(edt.jour, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'), edt.heure_debut",
                ['cid' => $enseignant['classe_id']]
            );
        }

        jsonResponse(true, 'Enseignant récupéré.', [
            'enseignant'     => $enseignant,
            'eleves'         => $eleves,
            'emploiDuTemps'  => $emploiDuTemps
        ]);
    }

    /**
     * Crée un nouvel enseignant
     */
    private function creer(): void
    {
        requirePermission('enseignants.creer');

        $nom              = post('nom', '');
        $prenom           = post('prenom', '');
        $dateNaissance    = post('date_naissance', '');
        $sexe             = post('sexe', '');
        $adresse          = post('adresse', '');
        $telephone        = post('telephone', '');
        $email            = post('email', '');
        $diplome          = post('diplome', '');
        $specialite       = post('specialite', '');
        $dateEmbauche     = post('date_embauche', '');
        $classeId         = (int) post('classe_id', 0);
        $creerCompte      = (int) post('creer_compte', 0);

        $errors = [];

        if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
        elseif (mb_strlen($nom) < 2) $errors['nom'] = 'Minimum 2 caractères.';

        if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
        elseif (mb_strlen($prenom) < 2) $errors['prenom'] = 'Minimum 2 caractères.';

        if (isBlank($dateNaissance)) $errors['date_naissance'] = 'La date de naissance est obligatoire.';
        elseif (!isValidDate($dateNaissance)) $errors['date_naissance'] = 'Date invalide.';

        if (!in_array($sexe, ['M', 'F'])) $errors['sexe'] = 'Sexe invalide.';

        if (!isBlank($telephone) && !isValidPhone($telephone)) $errors['telephone'] = 'Format invalide.';
        else {
            $telExiste = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM enseignants WHERE telephone = :tel",
                ['tel' => $telephone]
            );
            if ($telExiste > 0) $errors['telephone'] = 'Ce numéro est déjà utilisé.';
        }

        if (!isBlank($email)) {
            if (!isValidEmail($email)) $errors['email'] = 'Email invalide.';
            else {
                $emailExiste = $this->db->fetchColumn(
                    "SELECT COUNT(*) FROM enseignants WHERE email = :email",
                    ['email' => $email]
                );
                if ($emailExiste > 0) $errors['email'] = 'Cet email est déjà utilisé.';
            }
        }

        // Vérifier la classe (une seule classe par enseignant)
        if ($classeId > 0) {
            $classeDejaPrise = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM enseignants WHERE classe_id = :cid AND est_actif = 1",
                ['cid' => $classeId]
            );
            if ($classeDejaPrise > 0) $errors['classe_id'] = 'Cette classe a déjà un titulaire.';
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->beginTransaction();

            // Générer le matricule
            $matricule = 'ENS-' . date('y') . '-' . str_pad(((int) $this->db->fetchColumn("SELECT MAX(id) FROM enseignants") + 1), 3, '0', STR_PAD_LEFT);

            // Insérer l'enseignant
            $enseignantId = $this->db->insert(
                "INSERT INTO enseignants (matricule, nom, prenom, date_naissance, sexe, adresse, telephone, 
                     email, diplome, specialite, date_embauche, classe_id, est_actif)
                 VALUES (:mat, :nom, :prenom, :dn, :sexe, :adr, :tel, :email, :diplome, :spe, :de, :cid, 1)",
                [
                    'mat'     => $matricule,
                    'nom'     => formatNom($nom),
                    'prenom'  => formatNom($prenom),
                    'dn'      => $dateNaissance,
                    'sexe'    => $sexe,
                    'adr'     => $adresse ?: null,
                    'tel'     => $telephone ?: null,
                    'email'   => $email ?: null,
                    'diplome' => $diplome ?: null,
                    'spe'     => $specialite ?: null,
                    'de'      => $dateEmbauche ?: date('Y-m-d'),
                    'cid'     => $classeId > 0 ? $classeId : null
                ]
            );

            // Créer un compte utilisateur si demandé
            if ($creerCompte && !empty($email)) {
                $login = 'ens.' . slugify($prenom) . '.' . slugify($nom);
                // Vérifier unicité login
                $i = 1;
                $baseLogin = $login;
                while ($this->db->fetchColumn("SELECT COUNT(*) FROM utilisateurs WHERE login = :login", ['login' => $login]) > 0) {
                    $login = $baseLogin . $i;
                    $i++;
                }

                $hashedPassword = password_hash('Enseignant@2025', PASSWORD_ALGO, ['cost' => PASSWORD_COST]);

                $userId = $this->db->insert(
                    "INSERT INTO utilisateurs (nom, prenom, email, login, mot_de_passe, role_id, est_actif)
                     VALUES (:nom, :prenom, :email, :login, :pass, 4, 1)",
                    [
                        'nom'    => formatNom($nom),
                        'prenom' => formatNom($prenom),
                        'email'  => $email,
                        'login'  => $login,
                        'pass'   => $hashedPassword
                    ]
                );

                $this->db->update(
                    "UPDATE enseignants SET utilisateur_id = :uid WHERE id = :eid",
                    ['uid' => $userId, 'eid' => $enseignantId]
                );
            }

            $this->db->commit();

            logActivity('Création', 'enseignants', $enseignantId, "Enseignant créé : {$nom} {$prenom}");

            $msg = 'Enseignant créé avec succès.';
            if ($creerCompte && !empty($email)) {
                $msg .= " Compte créé (login : {$login}, mot de passe : Enseignant@2025).";
            }

            jsonResponse(true, $msg, ['id' => $enseignantId, 'matricule' => $matricule]);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur création enseignant: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la création de l\'enseignant.');
        }
    }

    /**
     * Modifie un enseignant existant
     */
    private function modifier(): void
    {
        requirePermission('enseignants.modifier');

        $id               = (int) post('id', 0);
        $nom              = post('nom', '');
        $prenom           = post('prenom', '');
        $dateNaissance    = post('date_naissance', '');
        $sexe             = post('sexe', '');
        $adresse          = post('adresse', '');
        $telephone        = post('telephone', '');
        $email            = post('email', '');
        $diplome          = post('diplome', '');
        $specialite       = post('specialite', '');
        $dateEmbauche     = post('date_embauche', '');
        $classeId         = (int) post('classe_id', 0);

        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $existant = $this->db->fetchOne("SELECT id FROM enseignants WHERE id = :id", ['id' => $id]);
        if (!$existant) jsonResponse(false, 'Enseignant introuvable.', [], 404);

        $errors = [];

        if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
        if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
        if (isBlank($dateNaissance)) $errors['date_naissance'] = 'Date obligatoire.';
        if (!in_array($sexe, ['M', 'F'])) $errors['sexe'] = 'Sexe invalide.';

        if (!isBlank($telephone) && !isValidPhone($telephone)) $errors['telephone'] = 'Format invalide.';
        else {
            $telExiste = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM enseignants WHERE telephone = :tel AND id != :id",
                ['tel' => $telephone, 'id' => $id]
            );
            if ($telExiste > 0) $errors['telephone'] = 'Ce numéro est déjà utilisé.';
        }

        if (!isBlank($email) && !isValidEmail($email)) $errors['email'] = 'Email invalide.';
        else {
            $emailExiste = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM enseignants WHERE email = :email AND id != :id",
                ['email' => $email, 'id' => $id]
            );
            if ($emailExiste > 0) $errors['email'] = 'Cet email est déjà utilisé.';
        }

        // Vérifier la classe
        if ($classeId > 0) {
            $classeDejaPrise = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM enseignants WHERE classe_id = :cid AND id != :eid AND est_actif = 1",
                ['cid' => $classeId, 'eid' => $id]
            );
            if ($classeDejaPrise > 0) $errors['classe_id'] = 'Cette classe a déjà un autre titulaire.';
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->update(
                "UPDATE enseignants 
                 SET nom = :nom, prenom = :prenom, date_naissance = :dn, sexe = :sexe,
                     adresse = :adr, telephone = :tel, email = :email,
                     diplome = :diplome, specialite = :spe, date_embauche = :de, classe_id = :cid
                 WHERE id = :id",
                [
                    'nom'     => formatNom($nom),
                    'prenom'  => formatNom($prenom),
                    'dn'      => $dateNaissance,
                    'sexe'    => $sexe,
                    'adr'     => $adresse ?: null,
                    'tel'     => $telephone ?: null,
                    'email'   => $email ?: null,
                    'diplome' => $diplome ?: null,
                    'spe'     => $specialite ?: null,
                    'de'      => $dateEmbauche ?: null,
                    'cid'     => $classeId > 0 ? $classeId : null,
                    'id'      => $id
                ]
            );

            logActivity('Modification', 'enseignants', $id, "Enseignant modifié : {$nom} {$prenom}");

            jsonResponse(true, 'Enseignant modifié avec succès.');

        } catch (Exception $e) {
            error_log('Erreur modification enseignant: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la modification.');
        }
    }

    /**
     * Désactive un enseignant
     */
    private function supprimer(): void
    {
        requirePermission('enseignants.supprimer');

        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $enseignant = $this->db->fetchOne("SELECT id, nom, prenom, classe_id FROM enseignants WHERE id = :id", ['id' => $id]);
        if (!$enseignant) jsonResponse(false, 'Enseignant introuvable.', [], 404);

        // Vérifier si sa classe a des élèves
        if ($enseignant['classe_id']) {
            $nbEleves = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
                ['cid' => $enseignant['classe_id']]
            );
            if ($nbEleves > 0) {
                jsonResponse(false, "Impossible de désactiver : sa classe a {$nbEleves} élève(s). Réassignez la classe d'abord.", [], 409);
            }
        }

        try {
            // Libérer la classe
            $this->db->update("UPDATE enseignants SET classe_id = NULL WHERE id = :id", ['id' => $id]);
            // Désactiver
            $this->db->update("UPDATE enseignants SET est_actif = 0 WHERE id = :id", ['id' => $id]);
            // Désactiver le compte utilisateur
            $this->db->update("UPDATE utilisateurs SET est_actif = 0 WHERE id = (SELECT utilisateur_id FROM enseignants WHERE id = :id)", ['id' => $id]);

            logActivity('Suppression', 'enseignants', $id, "Enseignant désactivé : {$enseignant['nom']} {$enseignant['prenom']}");

            jsonResponse(true, 'Enseignant désactivé avec succès.');

        } catch (Exception $e) {
            error_log('Erreur suppression enseignant: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la désactivation.');
        }
    }

    /**
     * Change rapidement la classe d'un enseignant
     */
    private function changerClasse(): void
    {
        requirePermission('enseignants.modifier');

        $enseignantId = (int) post('enseignant_id', 0);
        $classeId     = (int) post('classe_id', 0);

        if ($enseignantId <= 0) jsonResponse(false, 'ID enseignant invalide.', [], 400);

        try {
            if ($classeId > 0) {
                // Vérifier que la classe n'a pas déjà un titulaire
                $dejaTitulaire = $this->db->fetchColumn(
                    "SELECT id FROM enseignants WHERE classe_id = :cid AND id != :eid AND est_actif = 1",
                    ['cid' => $classeId, 'eid' => $enseignantId]
                );
                if ($dejaTitulaire) {
                    jsonResponse(false, 'Cette classe a déjà un titulaire.', [], 409);
                }
            }

            $this->db->update(
                "UPDATE enseignants SET classe_id = :cid WHERE id = :eid",
                ['cid' => $classeId > 0 ? $classeId : null, 'eid' => $enseignantId]
            );

            logActivity('Modification', 'enseignants', $enseignantId, 'Changement de classe');
            jsonResponse(true, 'Classe modifiée avec succès.');

        } catch (Exception $e) {
            error_log('Erreur changement classe: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du changement de classe.');
        }
    }

    /**
     * Récupère les classes disponibles (sans titulaire)
     */
    private function getClassesDisponibles(): void
    {
        $excludeEnseignantId = (int) post('exclude_enseignant_id', 0);

        $sql = "SELECT c.id, c.nom, c.niveau, c.salle
                FROM classes c
                WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
                AND c.id NOT IN (SELECT COALESCE(classe_id, 0) FROM enseignants WHERE est_actif = 1 AND id != :eid)
                ORDER BY c.niveau";

        $classes = $this->db->fetchAll($sql, ['eid' => $excludeEnseignantId]);

        jsonResponse(true, 'Classes disponibles.', ['classes' => $classes]);
    }

    // ============================================================
    // MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère un enseignant complet par ID
     */
    public function getEnseignantDetail(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT e.*, 
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                    DATE_FORMAT(e.date_embauche, '%d/%m/%Y') AS date_embauche_format,
                    TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age,
                    TIMESTAMPDIFF(YEAR, e.date_embauche, CURDATE()) AS anciennete,
                    c.nom AS classe_nom, c.niveau AS classe_niveau, c.salle AS classe_salle,
                    c.capacite_max AS classe_capacite,
                    (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id AND i.statut = 'Actif') AS nb_eleves,
                    u.login AS compte_login
             FROM enseignants e
             LEFT JOIN classes c ON e.classe_id = c.id
             LEFT JOIN utilisateurs u ON e.utilisateur_id = u.id
             WHERE e.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Récupère les élèves de la classe d'un enseignant
     */
    public function getElevesClasse(int $enseignantId): array
    {
        $classeId = $this->db->fetchColumn("SELECT classe_id FROM enseignants WHERE id = :id", ['id' => $enseignantId]);

        if (!$classeId) return [];

        return $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe,
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1
             ORDER BY e.nom",
            ['cid' => $classeId]
        );
    }

    /**
     * Récupère toutes les classes (pour formulaires)
     */
    public function getClassesActives(): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.nom, c.niveau, c.salle,
                    (SELECT COUNT(*) FROM enseignants e WHERE e.classe_id = c.id AND e.est_actif = 1) AS a_titulaire
             FROM classes c
             WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau"
        );
    }

    /**
     * Récupère les spécialités distinctes (pour filtres)
     */
    public function getSpecialites(): array
    {
        $result = $this->db->fetchAll(
            "SELECT DISTINCT specialite FROM enseignants WHERE specialite IS NOT NULL AND specialite != '' ORDER BY specialite"
        );
        return array_column($result, 'specialite');
    }
}