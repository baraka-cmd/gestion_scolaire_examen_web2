<?php
/**
 * Fichier : src/controllers/ClasseController.php
 * Rôle    : Gestion complète des classes (CRUD)
 */

defined('ACCESS') or die('Accès interdit');

class ClasseController
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
        if (!can('classes.consulter')) {
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
            case 'getClasse':
                $this->getClasse();
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
            case 'getEleves':
                $this->getEleves();
                break;
            case 'changerTitulaire':
                $this->changerTitulaire();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    /**
     * Liste paginée des classes
     */
    private function getListe(): void
    {
        $page       = max(1, (int) post('page', 1));
        $recherche  = post('recherche', '');
        $anneeId    = (int) post('annee_id', 0);
        $ordre      = post('ordre', 'niveau');
        $direction  = post('direction', 'ASC');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        if (!empty($recherche)) {
            $where[] = "(c.nom LIKE :recherche1 OR c.salle LIKE :recherche2)";
            $params['recherche1'] = "%{$recherche}%";
            $params['recherche2'] = "%{$recherche}%";
        }

        if ($anneeId > 0) {
            $where[] = "c.annee_scolaire_id = :annee";
            $params['annee'] = $anneeId;
        } else {
            // Par défaut, année active
            $where[] = "c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $colonnesAutorisees = ['id', 'nom', 'niveau', 'salle', 'capacite_max', 'nb_eleves'];
        if (!in_array($ordre, $colonnesAutorisees)) $ordre = 'niveau';
        if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) $direction = 'ASC';

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM classes c {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;
        $classes = $this->db->fetchAll(
            "SELECT c.*, 
                    COALESCE(e.nom, '—') AS titulaire_nom,
                    COALESCE(e.prenom, '') AS titulaire_prenom,
                    e.id AS titulaire_id,
                    (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id AND i.statut = 'Actif') AS nb_eleves,
                    a.libelle AS annee_libelle
             FROM classes c
             LEFT JOIN enseignants e ON c.id = e.classe_id AND e.est_actif = 1
             INNER JOIN annees_scolaires a ON c.annee_scolaire_id = a.id
             {$whereClause}
             ORDER BY {$ordre} {$direction}
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        jsonResponse(true, 'Liste récupérée.', [
            'classes' => $classes,
            'total'   => $total,
            'page'    => $page,
            'pages'   => ceil($total / $parPage)
        ]);
    }

    /**
     * Récupère une classe par son ID
     */
    private function getClasse(): void
    {
        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $classe = $this->db->fetchOne(
            "SELECT c.*, 
                    e.id AS titulaire_id,
                    CONCAT(e.prenom, ' ', e.nom) AS titulaire_nom,
                    a.libelle AS annee_libelle,
                    (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id AND i.statut = 'Actif') AS nb_eleves
             FROM classes c
             LEFT JOIN enseignants e ON c.id = e.classe_id AND e.est_actif = 1
             INNER JOIN annees_scolaires a ON c.annee_scolaire_id = a.id
             WHERE c.id = :id",
            ['id' => $id]
        );

        if (!$classe) jsonResponse(false, 'Classe introuvable.', [], 404);

        // Récupérer les enseignants disponibles
        $enseignants = $this->db->fetchAll(
            "SELECT id, matricule, nom, prenom, specialite 
             FROM enseignants 
             WHERE est_actif = 1 AND (classe_id IS NULL OR classe_id = :classe_id)
             ORDER BY nom",
            ['classe_id' => $id]
        );

        jsonResponse(true, 'Classe récupérée.', [
            'classe'      => $classe,
            'enseignants' => $enseignants
        ]);
    }

    /**
     * Crée une nouvelle classe
     */
    private function creer(): void
    {
        requirePermission('classes.creer');

        $nom          = post('nom', '');
        $niveau       = (int) post('niveau', 0);
        $salle        = post('salle', '');
        $capaciteMax  = (int) post('capacite_max', 50);
        $anneeId      = (int) post('annee_scolaire_id', 0);
        $description  = post('description', '');
        $titulaireId  = (int) post('titulaire_id', 0);

        $errors = [];

        if (isBlank($nom)) {
            $errors['nom'] = 'Le nom de la classe est obligatoire.';
        } elseif (mb_strlen($nom) < 2) {
            $errors['nom'] = 'Minimum 2 caractères.';
        } elseif (mb_strlen($nom) > 50) {
            $errors['nom'] = 'Maximum 50 caractères.';
        } else {
            // Vérifier l'unicité pour la même année
            $existe = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM classes WHERE nom = :nom AND annee_scolaire_id = :annee",
                ['nom' => trim($nom), 'annee' => $anneeId]
            );
            if ($existe > 0) $errors['nom'] = 'Cette classe existe déjà pour cette année scolaire.';
        }

        if ($niveau < 1 || $niveau > 12) {
            $errors['niveau'] = 'Niveau invalide (1-12).';
        }

        if (isBlank($salle)) {
            $errors['salle'] = 'La salle est obligatoire.';
        } elseif (mb_strlen($salle) > 20) {
            $errors['salle'] = 'Maximum 20 caractères.';
        } else {
            // Vérifier que la salle n'est pas déjà utilisée pour cette année
            $salleExistante = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM classes WHERE salle = :salle AND annee_scolaire_id = :annee",
                ['salle' => trim($salle), 'annee' => $anneeId]
            );
            if ($salleExistante > 0) $errors['salle'] = 'Cette salle est déjà attribuée pour cette année.';
        }

        if ($capaciteMax < 1 || $capaciteMax > 200) {
            $errors['capacite_max'] = 'Capacité invalide (1-200).';
        }

        if ($anneeId <= 0) {
            $errors['annee_scolaire_id'] = 'L\'année scolaire est obligatoire.';
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->beginTransaction();

            $classeId = $this->db->insert(
                "INSERT INTO classes (nom, niveau, salle, capacite_max, annee_scolaire_id, description, est_actif)
                 VALUES (:nom, :niveau, :salle, :capacite, :annee, :description, 1)",
                [
                    'nom'         => formatNom($nom),
                    'niveau'      => $niveau,
                    'salle'       => mb_strtoupper(trim($salle)),
                    'capacite'    => $capaciteMax,
                    'annee'       => $anneeId,
                    'description' => $description ?: null
                ]
            );

            // Assigner le titulaire si fourni
            if ($titulaireId > 0) {
                // Vérifier que l'enseignant n'est pas déjà titulaire d'une autre classe
                $dejaTitulaire = $this->db->fetchColumn(
                    "SELECT classe_id FROM enseignants WHERE id = :eid",
                    ['eid' => $titulaireId]
                );
                if ($dejaTitulaire) {
                    $this->db->update(
                        "UPDATE enseignants SET classe_id = NULL WHERE id = :eid",
                        ['eid' => $titulaireId]
                    );
                }
                $this->db->update(
                    "UPDATE enseignants SET classe_id = :classe WHERE id = :eid",
                    ['classe' => $classeId, 'eid' => $titulaireId]
                );
            }

            $this->db->commit();

            logActivity('Création', 'classes', $classeId, "Classe créée : {$nom}");

            jsonResponse(true, 'Classe créée avec succès.', ['id' => $classeId]);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur création classe: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la création de la classe.');
        }
    }

    /**
     * Modifie une classe existante
     */
    private function modifier(): void
    {
        requirePermission('classes.modifier');

        $id           = (int) post('id', 0);
        $nom          = post('nom', '');
        $niveau       = (int) post('niveau', 0);
        $salle        = post('salle', '');
        $capaciteMax  = (int) post('capacite_max', 50);
        $anneeId      = (int) post('annee_scolaire_id', 0);
        $description  = post('description', '');
        $titulaireId  = (int) post('titulaire_id', 0);

        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        // Vérifier que la classe existe
        $classe = $this->db->fetchOne("SELECT id, nom FROM classes WHERE id = :id", ['id' => $id]);
        if (!$classe) jsonResponse(false, 'Classe introuvable.', [], 404);

        $errors = [];

        if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
        elseif (mb_strlen($nom) < 2) $errors['nom'] = 'Minimum 2 caractères.';
        else {
            $existe = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM classes WHERE nom = :nom AND annee_scolaire_id = :annee AND id != :id",
                ['nom' => trim($nom), 'annee' => $anneeId, 'id' => $id]
            );
            if ($existe > 0) $errors['nom'] = 'Ce nom existe déjà pour cette année.';
        }

        if ($niveau < 1 || $niveau > 12) $errors['niveau'] = 'Niveau invalide (1-12).';
        if (isBlank($salle)) $errors['salle'] = 'La salle est obligatoire.';
        else {
            $salleExistante = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM classes WHERE salle = :salle AND annee_scolaire_id = :annee AND id != :id",
                ['salle' => trim($salle), 'annee' => $anneeId, 'id' => $id]
            );
            if ($salleExistante > 0) $errors['salle'] = 'Cette salle est déjà attribuée.';
        }

        if ($capaciteMax < 1 || $capaciteMax > 200) $errors['capacite_max'] = 'Capacité invalide (1-200).';
        if ($anneeId <= 0) $errors['annee_scolaire_id'] = 'L\'année scolaire est obligatoire.';

        // Vérifier que la capacité n'est pas inférieure au nombre d'élèves actuels
        $nbEleves = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
            ['cid' => $id]
        );
        if ($capaciteMax < $nbEleves) {
            $errors['capacite_max'] = "Capacité insuffisante ({$nbEleves} élèves inscrits).";
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->beginTransaction();

            $this->db->update(
                "UPDATE classes 
                 SET nom = :nom, niveau = :niveau, salle = :salle, capacite_max = :capacite,
                     annee_scolaire_id = :annee, description = :description
                 WHERE id = :id",
                [
                    'nom'         => formatNom($nom),
                    'niveau'      => $niveau,
                    'salle'       => mb_strtoupper(trim($salle)),
                    'capacite'    => $capaciteMax,
                    'annee'       => $anneeId,
                    'description' => $description ?: null,
                    'id'          => $id
                ]
            );

            // Gérer le changement de titulaire
            if ($titulaireId > 0) {
                // Libérer l'ancien titulaire
                $this->db->update(
                    "UPDATE enseignants SET classe_id = NULL WHERE classe_id = :cid AND id != :eid",
                    ['cid' => $id, 'eid' => $titulaireId]
                );
                // Assigner le nouveau
                $this->db->update(
                    "UPDATE enseignants SET classe_id = :cid WHERE id = :eid",
                    ['cid' => $id, 'eid' => $titulaireId]
                );
            } else {
                // Libérer le titulaire actuel
                $this->db->update(
                    "UPDATE enseignants SET classe_id = NULL WHERE classe_id = :cid",
                    ['cid' => $id]
                );
            }

            $this->db->commit();

            logActivity('Modification', 'classes', $id, "Classe modifiée : {$nom}");

            jsonResponse(true, 'Classe modifiée avec succès.');

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur modification classe: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la modification.');
        }
    }

    /**
     * Supprime une classe
     */
    private function supprimer(): void
    {
        requirePermission('classes.supprimer');

        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $classe = $this->db->fetchOne("SELECT id, nom FROM classes WHERE id = :id", ['id' => $id]);
        if (!$classe) jsonResponse(false, 'Classe introuvable.', [], 404);

        // Vérifier les élèves inscrits
        $nbEleves = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid",
            ['cid' => $id]
        );
        if ($nbEleves > 0) {
            jsonResponse(false, "Impossible de supprimer cette classe : {$nbEleves} élève(s) y sont inscrits.", [], 409);
        }

        // Vérifier les évaluations
        $nbEvaluations = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM evaluations WHERE classe_id = :cid",
            ['cid' => $id]
        );
        if ($nbEvaluations > 0) {
            jsonResponse(false, "Impossible de supprimer cette classe : des évaluations y sont liées.", [], 409);
        }

        try {
            // Libérer le titulaire
            $this->db->update("UPDATE enseignants SET classe_id = NULL WHERE classe_id = :cid", ['cid' => $id]);

            // Supprimer l'emploi du temps
            $this->db->delete("DELETE FROM emploi_du_temps WHERE classe_id = :cid", ['cid' => $id]);

            // Supprimer la classe
            $this->db->delete("DELETE FROM classes WHERE id = :id", ['id' => $id]);

            logActivity('Suppression', 'classes', $id, "Classe supprimée : {$classe['nom']}");

            jsonResponse(true, 'Classe supprimée avec succès.');

        } catch (Exception $e) {
            error_log('Erreur suppression classe: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la suppression.');
        }
    }

    /**
     * Récupère la liste des élèves d'une classe
     */
    private function getEleves(): void
    {
        $classeId = (int) post('classe_id', 0);
        if ($classeId <= 0) jsonResponse(false, 'ID classe invalide.', [], 400);

        $eleves = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe, e.date_naissance,
                    i.date_inscription, i.statut
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1
             ORDER BY e.nom, e.prenom",
            ['cid' => $classeId]
        );

        jsonResponse(true, 'Élèves récupérés.', ['eleves' => $eleves]);
    }

    /**
     * Change le titulaire d'une classe
     */
    private function changerTitulaire(): void
    {
        requirePermission('classes.modifier');

        $classeId    = (int) post('classe_id', 0);
        $titulaireId = (int) post('titulaire_id', 0);

        if ($classeId <= 0) jsonResponse(false, 'ID classe invalide.', [], 400);

        try {
            if ($titulaireId > 0) {
                // Libérer l'ancien
                $this->db->update("UPDATE enseignants SET classe_id = NULL WHERE classe_id = :cid", ['cid' => $classeId]);
                // Libérer l'enseignant de son ancienne classe
                $this->db->update("UPDATE enseignants SET classe_id = NULL WHERE id = :eid", ['eid' => $titulaireId]);
                // Assigner
                $this->db->update("UPDATE enseignants SET classe_id = :cid WHERE id = :eid", ['cid' => $classeId, 'eid' => $titulaireId]);
            } else {
                $this->db->update("UPDATE enseignants SET classe_id = NULL WHERE classe_id = :cid", ['cid' => $classeId]);
            }

            logActivity('Modification', 'classes', $classeId, 'Changement de titulaire');
            jsonResponse(true, 'Titulaire mis à jour avec succès.');

        } catch (Exception $e) {
            error_log('Erreur changement titulaire: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du changement de titulaire.');
        }
    }

    // ============================================================
    // MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère toutes les années scolaires (pour les formulaires)
     */
    public function getAnneesScolaires(): array
    {
        return $this->db->fetchAll(
            "SELECT id, libelle, est_active FROM annees_scolaires ORDER BY date_debut DESC"
        );
    }

    /**
     * Récupère les enseignants disponibles (pour les formulaires)
     */
    public function getEnseignantsDisponibles(?int $classeId = null): array
    {
        $sql = "SELECT id, matricule, nom, prenom, specialite, classe_id
                FROM enseignants 
                WHERE est_actif = 1";
        $params = [];

        if ($classeId) {
            $sql .= " AND (classe_id IS NULL OR classe_id = :classe_id)";
            $params['classe_id'] = $classeId;
        } else {
            $sql .= " AND classe_id IS NULL";
        }

        $sql .= " ORDER BY nom";
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Récupère une classe complète par ID (pour la vue détail)
     */
    public function getClasseDetail(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT c.*, 
                    CONCAT(COALESCE(e.prenom, ''), ' ', COALESCE(e.nom, '')) AS titulaire_nom,
                    e.id AS titulaire_id, e.matricule AS titulaire_matricule,
                    e.telephone AS titulaire_telephone, e.email AS titulaire_email,
                    a.libelle AS annee_libelle,
                    (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id AND i.statut = 'Actif') AS nb_eleves
             FROM classes c
             LEFT JOIN enseignants e ON c.id = e.classe_id AND e.est_actif = 1
             INNER JOIN annees_scolaires a ON c.annee_scolaire_id = a.id
             WHERE c.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Récupère les élèves d'une classe
     */
    public function getElevesByClasse(int $classeId): array
    {
        return $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe, 
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance,
                    DATE_FORMAT(i.date_inscription, '%d/%m/%Y') AS date_inscription,
                    i.statut
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.classe_id = :cid AND i.statut = 'Actif' AND e.est_actif = 1
             ORDER BY e.nom, e.prenom",
            ['cid' => $classeId]
        );
    }
}