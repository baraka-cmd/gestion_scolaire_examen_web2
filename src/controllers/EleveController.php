<?php
/**
 * Fichier : src/controllers/EleveController.php
 * Rôle    : Gestion complète des élèves (CRUD + import/export)
 */

defined('ACCESS') or die('Accès interdit');

class EleveController
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
        if (!can('eleves.consulter')) {
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
            case 'getEleve':
                $this->getEleve();
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
            case 'getParents':
                $this->getParents();
                break;
            case 'importer':
                $this->importer();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    /**
     * Liste paginée avec filtres
     */
    private function getListe(): void
    {
        $page       = max(1, (int) post('page', 1));
        $recherche  = post('recherche', '');
        $classeId   = (int) post('classe_id', 0);
        $sexe       = post('sexe', '');
        $statut     = post('statut', '');
        $ordre      = post('ordre', 'nom');
        $direction  = post('direction', 'ASC');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        // Année active par défaut
        $where[] = "i.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)";

        if (!empty($recherche)) {
            $where[] = "(e.nom LIKE :r1 OR e.prenom LIKE :r2 OR e.matricule LIKE :r3)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
        }

        if ($classeId > 0) {
            $where[] = "i.classe_id = :classe";
            $params['classe'] = $classeId;
        }

        if (!empty($sexe)) {
            $where[] = "e.sexe = :sexe";
            $params['sexe'] = $sexe;
        }

        if ($statut !== '') {
            $where[] = "e.est_actif = :statut";
            $params['statut'] = (int) $statut;
        } else {
            $where[] = "e.est_actif = 1"; // Actifs par défaut
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Colonnes autorisées pour le tri
        $colonnesAutorisees = ['id', 'nom', 'prenom', 'matricule', 'date_naissance', 'sexe', 'classe_nom'];
        if (!in_array($ordre, $colonnesAutorisees)) $ordre = 'nom';
        if (!in_array(strtoupper($direction), ['ASC', 'DESC'])) $direction = 'ASC';

        // Adapter le ORDER BY
        $orderMap = ['classe_nom' => 'c.nom'];
        $orderCol = $orderMap[$ordre] ?? 'e.' . $ordre;

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM eleves e
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;
        $eleves = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.sexe, e.photo,
                    e.telephone, e.est_actif, e.date_inscription,
                    c.nom AS classe_nom, c.id AS classe_id,
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                    TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age
             FROM eleves e
             INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             INNER JOIN classes c ON i.classe_id = c.id
             {$whereClause}
             ORDER BY {$orderCol} {$direction}
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        jsonResponse(true, 'Liste récupérée.', [
            'eleves' => $eleves,
            'total'  => $total,
            'page'   => $page,
            'pages'  => ceil($total / $parPage)
        ]);
    }

    /**
     * Récupère un élève par ID avec toutes ses infos
     */
    private function getEleve(): void
    {
        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $eleve = $this->db->fetchOne(
            "SELECT e.*,
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                    TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age,
                    c.nom AS classe_nom, c.id AS classe_id, c.niveau AS classe_niveau,
                    i.date_inscription AS date_inscription_classe,
                    i.statut AS statut_inscription,
                    CONCAT(COALESCE(ens.prenom, ''), ' ', COALESCE(ens.nom, '')) AS titulaire_nom
             FROM eleves e
             LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             LEFT JOIN classes c ON i.classe_id = c.id
             LEFT JOIN enseignants ens ON c.id = ens.classe_id AND ens.est_actif = 1
             WHERE e.id = :id",
            ['id' => $id]
        );

        if (!$eleve) jsonResponse(false, 'Élève introuvable.', [], 404);

        // Récupérer les parents
        $parents = $this->db->fetchAll(
            "SELECT pt.id, pt.nom, pt.prenom, pt.lien, pt.telephone_principal, pt.email, pt.profession,
                    ep.est_tuteur_principal
             FROM parents_tuteurs pt
             INNER JOIN eleve_parent ep ON pt.id = ep.parent_id
             WHERE ep.eleve_id = :eid
             ORDER BY ep.est_tuteur_principal DESC",
            ['eid' => $id]
        );

        // Récupérer les dernières notes
        $notes = $this->db->fetchAll(
            "SELECT n.valeur, ev.type_evaluation, ev.date_evaluation, m.libelle AS matiere,
                    t.libelle AS trimestre
             FROM notes n
             INNER JOIN evaluations ev ON n.evaluation_id = ev.id
             INNER JOIN matieres m ON ev.matiere_id = m.id
             INNER JOIN trimestres t ON ev.trimestre_id = t.id
             WHERE n.eleve_id = :eid
             ORDER BY ev.date_evaluation DESC
             LIMIT 20",
            ['eid' => $id]
        );

        // Récupérer les absences récentes
        $absences = $this->db->fetchAll(
            "SELECT date_absence, type_absence, est_justifiee, motif
             FROM absences
             WHERE eleve_id = :eid
             ORDER BY date_absence DESC
             LIMIT 10",
            ['eid' => $id]
        );

        // Récupérer les paiements
        $paiements = $this->db->fetchAll(
            "SELECT p.montant_total, p.montant_paye, p.statut, p.date_paiement, p.mode_paiement,
                    f.libelle AS frais_libelle
             FROM paiements p
             INNER JOIN frais_scolaires f ON p.frais_scolaire_id = f.id
             WHERE p.eleve_id = :eid
             ORDER BY p.date_paiement DESC",
            ['eid' => $id]
        );

        jsonResponse(true, 'Élève récupéré.', [
            'eleve'     => $eleve,
            'parents'   => $parents,
            'notes'     => $notes,
            'absences'  => $absences,
            'paiements' => $paiements
        ]);
    }

    /**
     * Crée un nouvel élève + inscription
     */
    private function creer(): void
    {
        requirePermission('eleves.creer');

        $nom              = post('nom', '');
        $prenom           = post('prenom', '');
        $dateNaissance    = post('date_naissance', '');
        $lieuNaissance    = post('lieu_naissance', '');
        $sexe             = post('sexe', '');
        $adresse          = post('adresse', '');
        $telephone        = post('telephone', '');
        $email            = post('email', '');
        $groupeSanguin    = post('groupe_sanguin', '');
        $allergies        = post('allergies', '');
        $maladies         = post('maladies', '');
        $classeId         = (int) post('classe_id', 0);
        $parent1Nom       = post('parent1_nom', '');
        $parent1Prenom    = post('parent1_prenom', '');
        $parent1Lien      = post('parent1_lien', '');
        $parent1Tel       = post('parent1_telephone', '');
        $parent1Email     = post('parent1_email', '');
        $parent1Prof      = post('parent1_profession', '');
        $parent2Nom       = post('parent2_nom', '');
        $parent2Prenom    = post('parent2_prenom', '');
        $parent2Lien      = post('parent2_lien', '');
        $parent2Tel       = post('parent2_telephone', '');

        // Validation
        $errors = [];

        if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
        elseif (mb_strlen($nom) < 2) $errors['nom'] = 'Minimum 2 caractères.';

        if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
        elseif (mb_strlen($prenom) < 2) $errors['prenom'] = 'Minimum 2 caractères.';

        if (isBlank($dateNaissance)) $errors['date_naissance'] = 'La date de naissance est obligatoire.';
        elseif (!isValidDate($dateNaissance)) $errors['date_naissance'] = 'Date invalide.';
        elseif (calculerAge($dateNaissance) > 25) $errors['date_naissance'] = 'Âge maximum : 25 ans.';
        elseif (calculerAge($dateNaissance) < 3) $errors['date_naissance'] = 'Âge minimum : 3 ans.';

        if (!in_array($sexe, ['M', 'F'])) $errors['sexe'] = 'Sexe invalide.';

        if (!isBlank($email) && !isValidEmail($email)) $errors['email'] = 'Email invalide.';

        if (!isBlank($telephone) && !isValidPhone($telephone)) $errors['telephone'] = 'Téléphone invalide.';

        if ($classeId <= 0) $errors['classe_id'] = 'La classe est obligatoire.';
        else {
            // Vérifier la capacité de la classe
            $capacite = (int) $this->db->fetchColumn(
                "SELECT capacite_max FROM classes WHERE id = :cid",
                ['cid' => $classeId]
            );
            $nbEleves = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
                ['cid' => $classeId]
            );
            if ($nbEleves >= $capacite) {
                $errors['classe_id'] = "Classe complète ({$capacite} élèves maximum).";
            }
        }

        // Vérifier les doublons (même nom, prénom, date de naissance)
        if (empty($errors)) {
            $doublon = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM eleves WHERE nom = :nom AND prenom = :prenom AND date_naissance = :dn",
                ['nom' => formatNom($nom), 'prenom' => formatNom($prenom), 'dn' => $dateNaissance]
            );
            if ($doublon > 0) $errors['doublon'] = 'Un élève avec le même nom, prénom et date de naissance existe déjà.';
        }

        // Validation parent 1 (obligatoire)
        if (isBlank($parent1Nom)) $errors['parent1_nom'] = 'Le nom du parent/tuteur est obligatoire.';
        if (isBlank($parent1Prenom)) $errors['parent1_prenom'] = 'Le prénom est obligatoire.';
        if (isBlank($parent1Tel)) $errors['parent1_telephone'] = 'Le téléphone est obligatoire.';
        if (isBlank($parent1Lien)) $errors['parent1_lien'] = 'Le lien est obligatoire.';

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->beginTransaction();

            // Générer le matricule
            $matricule = $this->generateMatricule();

            // Insérer l'élève
            $eleveId = $this->db->insert(
                "INSERT INTO eleves (matricule, nom, prenom, date_naissance, lieu_naissance, sexe, 
                     adresse, telephone, email, groupe_sanguin, allergies, maladies, date_inscription, est_actif)
                 VALUES (:matricule, :nom, :prenom, :dn, :ln, :sexe, :adresse, :tel, :email, 
                         :gs, :allergies, :maladies, CURDATE(), 1)",
                [
                    'matricule'  => $matricule,
                    'nom'        => formatNom($nom),
                    'prenom'     => formatNom($prenom),
                    'dn'         => $dateNaissance,
                    'ln'         => $lieuNaissance ?: null,
                    'sexe'       => $sexe,
                    'adresse'    => $adresse ?: null,
                    'tel'        => $telephone ?: null,
                    'email'      => $email ?: null,
                    'gs'         => $groupeSanguin ?: null,
                    'allergies'  => $allergies ?: null,
                    'maladies'   => $maladies ?: null
                ]
            );

            // Inscrire dans la classe
            $anneeActive = $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
            $this->db->query(
                "INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire_id, date_inscription, statut)
                 VALUES (:eid, :cid, :aid, CURDATE(), 'Actif')",
                ['eid' => $eleveId, 'cid' => $classeId, 'aid' => $anneeActive]
            );

            // Gérer le parent 1
            $parent1Id = $this->creerOuTrouverParent(
                $parent1Nom, $parent1Prenom, $parent1Lien, $parent1Tel, $parent1Email, $parent1Prof
            );
            $this->lierParent($eleveId, $parent1Id, true);

            // Gérer le parent 2 (optionnel)
            if (!isBlank($parent2Nom) && !isBlank($parent2Tel)) {
                $parent2Id = $this->creerOuTrouverParent(
                    $parent2Nom, $parent2Prenom, $parent2Lien, $parent2Tel
                );
                $this->lierParent($eleveId, $parent2Id, false);
            }

            $this->db->commit();

            logActivity('Création', 'eleves', $eleveId, "Élève créé : {$nom} {$prenom}");

            jsonResponse(true, 'Élève créé avec succès.', ['id' => $eleveId, 'matricule' => $matricule]);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur création élève: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la création de l\'élève.');
        }
    }

    /**
     * Modifie un élève existant
     */
    private function modifier(): void
    {
        requirePermission('eleves.modifier');

        $id               = (int) post('id', 0);
        $nom              = post('nom', '');
        $prenom           = post('prenom', '');
        $dateNaissance    = post('date_naissance', '');
        $lieuNaissance    = post('lieu_naissance', '');
        $sexe             = post('sexe', '');
        $adresse          = post('adresse', '');
        $telephone        = post('telephone', '');
        $email            = post('email', '');
        $groupeSanguin    = post('groupe_sanguin', '');
        $allergies        = post('allergies', '');
        $maladies         = post('maladies', '');

        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $existant = $this->db->fetchOne("SELECT id FROM eleves WHERE id = :id", ['id' => $id]);
        if (!$existant) jsonResponse(false, 'Élève introuvable.', [], 404);

        $errors = [];

        if (isBlank($nom)) $errors['nom'] = 'Le nom est obligatoire.';
        if (isBlank($prenom)) $errors['prenom'] = 'Le prénom est obligatoire.';
        if (isBlank($dateNaissance)) $errors['date_naissance'] = 'La date de naissance est obligatoire.';
        elseif (!isValidDate($dateNaissance)) $errors['date_naissance'] = 'Date invalide.';
        if (!in_array($sexe, ['M', 'F'])) $errors['sexe'] = 'Sexe invalide.';

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->update(
                "UPDATE eleves 
                 SET nom = :nom, prenom = :prenom, date_naissance = :dn, lieu_naissance = :ln,
                     sexe = :sexe, adresse = :adresse, telephone = :tel, email = :email,
                     groupe_sanguin = :gs, allergies = :allergies, maladies = :maladies
                 WHERE id = :id",
                [
                    'nom'        => formatNom($nom),
                    'prenom'     => formatNom($prenom),
                    'dn'         => $dateNaissance,
                    'ln'         => $lieuNaissance ?: null,
                    'sexe'       => $sexe,
                    'adresse'    => $adresse ?: null,
                    'tel'        => $telephone ?: null,
                    'email'      => $email ?: null,
                    'gs'         => $groupeSanguin ?: null,
                    'allergies'  => $allergies ?: null,
                    'maladies'   => $maladies ?: null,
                    'id'         => $id
                ]
            );

            logActivity('Modification', 'eleves', $id, "Élève modifié : {$nom} {$prenom}");

            jsonResponse(true, 'Élève modifié avec succès.');

        } catch (Exception $e) {
            error_log('Erreur modification élève: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la modification.');
        }
    }

    /**
     * Désactive un élève (soft delete)
     */
    private function supprimer(): void
    {
        requirePermission('eleves.supprimer');

        $id = (int) post('id', 0);
        $motif = post('motif', '');

        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $eleve = $this->db->fetchOne("SELECT id, nom, prenom FROM eleves WHERE id = :id", ['id' => $id]);
        if (!$eleve) jsonResponse(false, 'Élève introuvable.', [], 404);

        try {
            // Désactiver l'élève
            $this->db->update("UPDATE eleves SET est_actif = 0 WHERE id = :id", ['id' => $id]);

            // Mettre fin à l'inscription active
            $this->db->update(
                "UPDATE inscriptions SET statut = :statut, motif_statut = :motif, date_changement_statut = CURDATE()
                 WHERE eleve_id = :eid AND statut = 'Actif'",
                ['statut' => 'Abandon', 'motif' => $motif ?: 'Désactivation', 'eid' => $id]
            );

            logActivity('Suppression', 'eleves', $id, "Élève désactivé : {$eleve['nom']} {$eleve['prenom']}");

            jsonResponse(true, 'Élève désactivé avec succès.');

        } catch (Exception $e) {
            error_log('Erreur suppression élève: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la désactivation.');
        }
    }

    /**
     * Change la classe d'un élève
     */
    private function changerClasse(): void
    {
        requirePermission('inscriptions.modifier');

        $eleveId       = (int) post('eleve_id', 0);
        $ancienneClasseId = (int) post('ancienne_classe_id', 0);
        $nouvelleClasseId  = (int) post('nouvelle_classe_id', 0);
        $motif         = post('motif', '');

        if ($eleveId <= 0 || $nouvelleClasseId <= 0) {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }

        // Vérifier la capacité
        $capacite = (int) $this->db->fetchColumn(
            "SELECT capacite_max FROM classes WHERE id = :cid", ['cid' => $nouvelleClasseId]
        );
        $nbEleves = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
            ['cid' => $nouvelleClasseId]
        );
        if ($nbEleves >= $capacite) {
            jsonResponse(false, "Classe complète ({$capacite} élèves max).", [], 409);
        }

        try {
            $this->db->beginTransaction();

            // Terminer l'ancienne inscription
            $this->db->update(
                "UPDATE inscriptions SET statut = 'Transféré', motif_statut = :motif, date_changement_statut = CURDATE()
                 WHERE eleve_id = :eid AND classe_id = :cid AND statut = 'Actif'",
                ['motif' => $motif ?: 'Changement de classe', 'eid' => $eleveId, 'cid' => $ancienneClasseId]
            );

            // Créer la nouvelle inscription
            $anneeActive = $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
            $this->db->query(
                "INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire_id, date_inscription, statut)
                 VALUES (:eid, :cid, :aid, CURDATE(), 'Actif')",
                ['eid' => $eleveId, 'cid' => $nouvelleClasseId, 'aid' => $anneeActive]
            );

            $this->db->commit();

            logActivity('Modification', 'eleves', $eleveId, "Changement de classe");

            jsonResponse(true, 'Classe changée avec succès.');

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur changement classe: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du changement de classe.');
        }
    }

    /**
     * Récupère les parents d'un élève
     */
    private function getParents(): void
    {
        $eleveId = (int) post('eleve_id', 0);
        if ($eleveId <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $parents = $this->db->fetchAll(
            "SELECT pt.*, ep.est_tuteur_principal
             FROM parents_tuteurs pt
             INNER JOIN eleve_parent ep ON pt.id = ep.parent_id
             WHERE ep.eleve_id = :eid",
            ['eid' => $eleveId]
        );

        jsonResponse(true, 'Parents récupérés.', ['parents' => $parents]);
    }

    /**
     * Import CSV d'élèves
     */
    private function importer(): void
    {
        requirePermission('eleves.importer');

        if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Fichier invalide ou manquant.', [], 400);
        }

        $file = $_FILES['fichier'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($extension !== 'csv') {
            jsonResponse(false, 'Seuls les fichiers CSV sont acceptés.', [], 400);
        }

        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) jsonResponse(false, 'Impossible de lire le fichier.', [], 500);

        $ligne = 0;
        $importes = 0;
        $erreurs = [];
        $anneeActive = $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

        // Lire l'en-tête
        $headers = fgetcsv($handle, 0, ';');
        if (!$headers) {
            fclose($handle);
            jsonResponse(false, 'Fichier CSV vide.', [], 400);
        }

        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            $ligne++;
            if (count($data) < 4) {
                $erreurs[] = "Ligne {$ligne} : données insuffisantes.";
                continue;
            }

            $row = array_combine($headers, $data);
            if (!$row) {
                $erreurs[] = "Ligne {$ligne} : format invalide.";
                continue;
            }

            $nom           = trim($row['nom'] ?? '');
            $prenom        = trim($row['prenom'] ?? '');
            $dateNaissance = trim($row['date_naissance'] ?? '');
            $sexe          = strtoupper(trim($row['sexe'] ?? ''));
            $classeNom     = trim($row['classe'] ?? '');
            $parentNom     = trim($row['parent_nom'] ?? '');
            $parentTel     = trim($row['parent_telephone'] ?? '');

            // Validation
            $rowErrors = [];
            if (empty($nom)) $rowErrors[] = 'Nom manquant';
            if (empty($prenom)) $rowErrors[] = 'Prénom manquant';
            if (empty($dateNaissance) || !isValidDate($dateNaissance)) $rowErrors[] = 'Date invalide';
            if (!in_array($sexe, ['M', 'F'])) $rowErrors[] = 'Sexe invalide';

            if (!empty($rowErrors)) {
                $erreurs[] = "Ligne {$ligne} : " . implode(', ', $rowErrors);
                continue;
            }

            try {
                $this->db->beginTransaction();

                $matricule = $this->generateMatricule();

                $eleveId = $this->db->insert(
                    "INSERT INTO eleves (matricule, nom, prenom, date_naissance, sexe, date_inscription, est_actif)
                     VALUES (:mat, :nom, :prenom, :dn, :sexe, CURDATE(), 1)",
                    ['mat' => $matricule, 'nom' => formatNom($nom), 'prenom' => formatNom($prenom), 'dn' => $dateNaissance, 'sexe' => $sexe]
                );

                // Trouver la classe
                $classeId = $this->db->fetchColumn(
                    "SELECT id FROM classes WHERE nom = :nom AND annee_scolaire_id = :aid",
                    ['nom' => $classeNom, 'aid' => $anneeActive]
                );

                if ($classeId) {
                    $this->db->query(
                        "INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire_id, date_inscription, statut)
                         VALUES (:eid, :cid, :aid, CURDATE(), 'Actif')",
                        ['eid' => $eleveId, 'cid' => $classeId, 'aid' => $anneeActive]
                    );
                }

                // Parent
                if (!empty($parentNom) && !empty($parentTel)) {
                    $parentId = $this->creerOuTrouverParent($parentNom, '', 'Père/Mère', $parentTel);
                    $this->lierParent($eleveId, $parentId, true);
                }

                $this->db->commit();
                $importes++;

            } catch (Exception $e) {
                $this->db->rollback();
                $erreurs[] = "Ligne {$ligne} : erreur - {$e->getMessage()}";
            }
        }

        fclose($handle);

        jsonResponse(true, "Import terminé : {$importes} élève(s) importé(s).", [
            'importes' => $importes,
            'erreurs'  => $erreurs
        ]);
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Génère un matricule unique
     */
    private function generateMatricule(): string
    {
        $annee = date('y');
        $prefix = "EL-{$annee}-";
        $lastId = (int) $this->db->fetchColumn("SELECT MAX(id) FROM eleves") + 1;
        return $prefix . str_pad($lastId, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Crée un parent ou le retrouve s'il existe déjà
     */
    private function creerOuTrouverParent(string $nom, string $prenom, string $lien, string $tel, ?string $email = null, ?string $profession = null): int
    {
        // Chercher par téléphone
        $parent = $this->db->fetchOne(
            "SELECT id FROM parents_tuteurs WHERE telephone_principal = :tel LIMIT 1",
            ['tel' => $tel]
        );

        if ($parent) return (int) $parent['id'];

        // Créer le parent
        return $this->db->insert(
            "INSERT INTO parents_tuteurs (nom, prenom, lien, profession, telephone_principal, email, est_contact_urgence)
             VALUES (:nom, :prenom, :lien, :prof, :tel, :email, 1)",
            [
                'nom'   => formatNom($nom),
                'prenom'=> formatNom($prenom),
                'lien'  => $lien ?: 'Tuteur',
                'prof'  => $profession ?: null,
                'tel'   => $tel,
                'email' => $email ?: null
            ]
        );
    }

    /**
     * Lie un parent à un élève
     */
    private function lierParent(int $eleveId, int $parentId, bool $estPrincipal): void
    {
        $existe = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM eleve_parent WHERE eleve_id = :eid AND parent_id = :pid",
            ['eid' => $eleveId, 'pid' => $parentId]
        );

        if ($existe == 0) {
            $this->db->query(
                "INSERT INTO eleve_parent (eleve_id, parent_id, est_tuteur_principal) VALUES (:eid, :pid, :principal)",
                ['eid' => $eleveId, 'pid' => $parentId, 'principal' => $estPrincipal ? 1 : 0]
            );
        }
    }

    // ============================================================
    // MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère toutes les classes pour les formulaires
     */
    public function getClasses(): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.nom, c.niveau
             FROM classes c
             WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau"
        );
    }

    /**
     * Récupère un élève complet par ID
     */
    public function getEleveDetail(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT e.*, 
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                    TIMESTAMPDIFF(YEAR, e.date_naissance, CURDATE()) AS age,
                    c.nom AS classe_nom, c.id AS classe_id, c.niveau AS classe_niveau,
                    i.date_inscription AS date_inscription_classe,
                    i.statut AS statut_inscription,
                    CONCAT(COALESCE(ens.prenom, ''), ' ', COALESCE(ens.nom, '')) AS titulaire_nom
             FROM eleves e
             LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
             LEFT JOIN classes c ON i.classe_id = c.id
             LEFT JOIN enseignants ens ON c.id = ens.classe_id AND ens.est_actif = 1
             WHERE e.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Récupère les parents d'un élève
     */
    public function getParentsEleve(int $eleveId): array
    {
        return $this->db->fetchAll(
            "SELECT pt.*, ep.est_tuteur_principal
             FROM parents_tuteurs pt
             INNER JOIN eleve_parent ep ON pt.id = ep.parent_id
             WHERE ep.eleve_id = :eid
             ORDER BY ep.est_tuteur_principal DESC",
            ['eid' => $eleveId]
        );
    }

    /**
     * Récupère l'historique des inscriptions
     */
    public function getHistoriqueInscriptions(int $eleveId): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, c.nom AS classe_nom, a.libelle AS annee_libelle
             FROM inscriptions i
             INNER JOIN classes c ON i.classe_id = c.id
             INNER JOIN annees_scolaires a ON i.annee_scolaire_id = a.id
             WHERE i.eleve_id = :eid
             ORDER BY i.date_inscription DESC",
            ['eid' => $eleveId]
        );
    }
}