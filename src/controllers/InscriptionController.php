<?php
/**
 * Fichier : src/controllers/InscriptionController.php
 * Rôle    : Gestion complète des inscriptions (CRUD + réinscription massive)
 */

defined('ACCESS') or die('Accès interdit');

class InscriptionController
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
        if (!can('inscriptions.consulter')) {
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
            case 'getInscription':
                $this->getInscription();
                break;
            case 'inscrire':
                $this->inscrire();
                break;
            case 'changerClasse':
                $this->changerClasse();
                break;
            case 'desinscrire':
                $this->desinscrire();
                break;
            case 'getElevesPourReinscription':
                $this->getElevesPourReinscription();
                break;
            case 'reinscrireMassivement':
                $this->reinscrireMassivement();
                break;
            case 'getHistorique':
                $this->getHistorique();
                break;
            case 'getClassesDisponibles':
                $this->getClassesDisponibles();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // LISTE DES INSCRIPTIONS
    // ============================================================

    private function getListe(): void
    {
        $page      = max(1, (int) post('page', 1));
        $recherche = post('recherche', '');
        $classeId  = (int) post('classe_id', 0);
        $anneeId   = (int) post('annee_id', 0);
        $statut    = post('statut', '');
        $parPage   = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        // Par défaut, année active
        if ($anneeId > 0) {
            $where[] = "i.annee_scolaire_id = :annee";
            $params['annee'] = $anneeId;
        } else {
            $where[] = "i.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)";
        }

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

        if (!empty($statut)) {
            $where[] = "i.statut = :statut";
            $params['statut'] = $statut;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             INNER JOIN classes c ON i.classe_id = c.id
             {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;
        $inscriptions = $this->db->fetchAll(
            "SELECT i.*, e.matricule, e.nom, e.prenom, e.sexe,
                    DATE_FORMAT(e.date_naissance, '%d/%m/%Y') AS date_naissance_format,
                    c.nom AS classe_nom, c.niveau AS classe_niveau,
                    a.libelle AS annee_libelle,
                    DATE_FORMAT(i.date_inscription, '%d/%m/%Y') AS date_inscription_format
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             INNER JOIN classes c ON i.classe_id = c.id
             INNER JOIN annees_scolaires a ON i.annee_scolaire_id = a.id
             {$whereClause}
             ORDER BY i.date_inscription DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        jsonResponse(true, 'Liste récupérée.', [
            'inscriptions' => $inscriptions,
            'total'        => $total,
            'page'         => $page,
            'pages'        => ceil($total / $parPage)
        ]);
    }

    // ============================================================
    // INSCRIPTION INDIVIDUELLE
    // ============================================================

    private function inscrire(): void
    {
        requirePermission('inscriptions.creer');

        $eleveId  = (int) post('eleve_id', 0);
        $classeId = (int) post('classe_id', 0);
        $statut   = post('statut', 'Actif');

        $errors = [];

        if ($eleveId <= 0) $errors['eleve_id'] = 'Veuillez sélectionner un élève.';
        if ($classeId <= 0) $errors['classe_id'] = 'Veuillez sélectionner une classe.';

        // Vérifier que l'élève existe
        $eleve = $this->db->fetchOne("SELECT id, nom, prenom FROM eleves WHERE id = :id AND est_actif = 1", ['id' => $eleveId]);
        if (!$eleve) $errors['eleve_id'] = 'Élève introuvable ou inactif.';

        // Vérifier la capacité de la classe
        if ($classeId > 0) {
            $capacite = (int) $this->db->fetchColumn("SELECT capacite_max FROM classes WHERE id = :id", ['id' => $classeId]);
            $nbInscrits = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
                ['cid' => $classeId]
            );
            if ($nbInscrits >= $capacite) {
                $errors['classe_id'] = "Classe complète ({$capacite} élèves maximum).";
            }
        }

        // Vérifier que l'élève n'est pas déjà inscrit dans une classe active cette année
        $anneeActive = $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
        $dejaInscrit = $this->db->fetchOne(
            "SELECT i.id, c.nom FROM inscriptions i
             INNER JOIN classes c ON i.classe_id = c.id
             WHERE i.eleve_id = :eid AND i.annee_scolaire_id = :aid AND i.statut = 'Actif'",
            ['eid' => $eleveId, 'aid' => $anneeActive]
        );
        if ($dejaInscrit) {
            $errors['eleve_id'] = "Cet élève est déjà inscrit en {$dejaInscrit['nom']} pour cette année.";
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $inscriptionId = $this->db->insert(
                "INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire_id, date_inscription, statut)
                 VALUES (:eid, :cid, :aid, CURDATE(), :statut)",
                ['eid' => $eleveId, 'cid' => $classeId, 'aid' => $anneeActive, 'statut' => $statut]
            );

            logActivity('Création', 'inscriptions', $inscriptionId,
                "Inscription : {$eleve['nom']} {$eleve['prenom']}");

            jsonResponse(true, 'Élève inscrit avec succès.', ['id' => $inscriptionId]);

        } catch (Exception $e) {
            error_log('Erreur inscription: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de l\'inscription.');
        }
    }

    // ============================================================
    // CHANGEMENT DE CLASSE
    // ============================================================

    private function changerClasse(): void
    {
        requirePermission('inscriptions.modifier');

        $eleveId          = (int) post('eleve_id', 0);
        $ancienneClasseId = (int) post('ancienne_classe_id', 0);
        $nouvelleClasseId = (int) post('nouvelle_classe_id', 0);
        $motif            = post('motif', 'Changement de classe');

        if ($eleveId <= 0 || $nouvelleClasseId <= 0) {
            jsonResponse(false, 'Paramètres invalides.', [], 400);
        }

        // Vérifier la capacité
        $capacite = (int) $this->db->fetchColumn("SELECT capacite_max FROM classes WHERE id = :id", ['id' => $nouvelleClasseId]);
        $nbInscrits = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
            ['cid' => $nouvelleClasseId]
        );
        if ($nbInscrits >= $capacite) {
            jsonResponse(false, "Classe complète ({$capacite} élèves max).", [], 409);
        }

        try {
            $this->db->beginTransaction();

            $anneeActive = $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

            // Terminer l'ancienne inscription
            $this->db->update(
                "UPDATE inscriptions SET statut = 'Transféré', motif_statut = :motif, date_changement_statut = CURDATE()
                 WHERE eleve_id = :eid AND classe_id = :cid AND annee_scolaire_id = :aid AND statut = 'Actif'",
                ['motif' => $motif, 'eid' => $eleveId, 'cid' => $ancienneClasseId, 'aid' => $anneeActive]
            );

            // Créer la nouvelle inscription
            $this->db->insert(
                "INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire_id, date_inscription, statut)
                 VALUES (:eid, :cid, :aid, CURDATE(), 'Actif')",
                ['eid' => $eleveId, 'cid' => $nouvelleClasseId, 'aid' => $anneeActive]
            );

            $this->db->commit();

            logActivity('Modification', 'inscriptions', null, "Changement de classe élève {$eleveId}");

            jsonResponse(true, 'Changement de classe effectué avec succès.');

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur changement classe: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du changement de classe.');
        }
    }

    // ============================================================
    // DÉSINSCRIPTION (ABANDON / EXCLUSION)
    // ============================================================

    private function desinscrire(): void
    {
        requirePermission('inscriptions.modifier');

        $inscriptionId = (int) post('inscription_id', 0);
        $statut        = post('statut', 'Abandon');
        $motif         = post('motif', '');

        if ($inscriptionId <= 0) jsonResponse(false, 'ID inscription invalide.', [], 400);
        if (!in_array($statut, ['Abandon', 'Exclu', 'Diplômé'])) {
            jsonResponse(false, 'Statut invalide.', [], 400);
        }
        if (isBlank($motif)) jsonResponse(false, 'Le motif est obligatoire.', [], 422);

        try {
            $this->db->update(
                "UPDATE inscriptions SET statut = :statut, motif_statut = :motif, date_changement_statut = CURDATE()
                 WHERE id = :id",
                ['statut' => $statut, 'motif' => $motif, 'id' => $inscriptionId]
            );

            logActivity('Modification', 'inscriptions', $inscriptionId, "Désinscription : {$statut}");

            jsonResponse(true, 'Statut mis à jour avec succès.');

        } catch (Exception $e) {
            error_log('Erreur désinscription: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la mise à jour.');
        }
    }

    // ============================================================
    // RÉINSCRIPTION MASSIVE
    // ============================================================

    /**
     * Récupère les élèves d'une classe pour la réinscription
     */
    private function getElevesPourReinscription(): void
    {
        $classeSourceId = (int) post('classe_source_id', 0);
        $anneeSourceId  = (int) post('annee_source_id', 0);

        if ($classeSourceId <= 0) jsonResponse(false, 'Classe source invalide.', [], 400);

        // Si pas d'année source, prendre l'année active ou la dernière
        if ($anneeSourceId <= 0) {
            $anneeSourceId = (int) $this->db->fetchColumn(
                "SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1"
            );
        }

        // Récupérer les élèves avec leur moyenne annuelle
        $eleves = $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe, e.date_naissance,
                    i.id AS inscription_id, i.statut,
                    (SELECT r.moyenne_generale FROM resultats_trimestriels r
                     WHERE r.eleve_id = e.id AND r.trimestre_id = 3
                     ORDER BY r.id DESC LIMIT 1) AS moyenne_annuelle,
                    (SELECT r.decision FROM resultats_trimestriels r
                     WHERE r.eleve_id = e.id AND r.trimestre_id = 3
                     ORDER BY r.id DESC LIMIT 1) AS decision_conseil
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             WHERE i.classe_id = :cid AND i.annee_scolaire_id = :aid AND i.statut IN ('Actif', 'Redoublant')
             ORDER BY e.nom, e.prenom",
            ['cid' => $classeSourceId, 'aid' => $anneeSourceId]
        );

        // Calculer la décision automatique
        foreach ($eleves as &$eleve) {
            $moyenne = (float) ($eleve['moyenne_annuelle'] ?? 0);
            if ($moyenne >= 10) {
                $eleve['decision_auto'] = 'Passe';
            } else {
                $eleve['decision_auto'] = 'Redouble';
            }
        }
        unset($eleve);

        // Récupérer les classes disponibles pour l'année suivante
        $anneeActive = $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
        $classesDisponibles = $this->db->fetchAll(
            "SELECT id, nom, niveau FROM classes WHERE annee_scolaire_id = :aid ORDER BY niveau",
            ['aid' => $anneeActive]
        );

        // Classe source
        $classeSource = $this->db->fetchOne("SELECT * FROM classes WHERE id = :id", ['id' => $classeSourceId]);

        jsonResponse(true, 'Élèves récupérés.', [
            'eleves'              => $eleves,
            'classe_source'       => $classeSource,
            'classes_disponibles' => $classesDisponibles,
            'total'               => count($eleves)
        ]);
    }

    /**
     * Exécute la réinscription massive
     */
    private function reinscrireMassivement(): void
    {
        requirePermission('inscriptions.creer');

        $elevesData = post('eleves', '');
        $elevesData = json_decode($elevesData, true);

        if (empty($elevesData) || !is_array($elevesData)) {
            jsonResponse(false, 'Aucun élève sélectionné.', [], 400);
        }

        $anneeActive = $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
        $inscrits = 0;
        $erreurs = [];

        foreach ($elevesData as $data) {
            $eleveId         = (int) ($data['eleve_id'] ?? 0);
            $nouvelleClasseId = (int) ($data['nouvelle_classe_id'] ?? 0);
            $decision        = $data['decision'] ?? 'Passe';
            $ancienneInscriptionId = (int) ($data['ancienne_inscription_id'] ?? 0);

            if ($eleveId <= 0 || $nouvelleClasseId <= 0) {
                $erreurs[] = "Élève ID {$eleveId} : données invalides.";
                continue;
            }

            // Vérifier la capacité
            $capacite = (int) $this->db->fetchColumn("SELECT capacite_max FROM classes WHERE id = :id", ['id' => $nouvelleClasseId]);
            $nbInscrits = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions WHERE classe_id = :cid AND statut = 'Actif'",
                ['cid' => $nouvelleClasseId]
            );
            if ($nbInscrits >= $capacite) {
                $erreurs[] = "Élève ID {$eleveId} : classe destination complète.";
                continue;
            }

            // Vérifier qu'il n'est pas déjà inscrit cette année
            $dejaInscrit = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions WHERE eleve_id = :eid AND annee_scolaire_id = :aid AND statut = 'Actif'",
                ['eid' => $eleveId, 'aid' => $anneeActive]
            );
            if ($dejaInscrit > 0) {
                $erreurs[] = "Élève ID {$eleveId} : déjà inscrit pour cette année.";
                continue;
            }

            try {
                // Créer la nouvelle inscription
                $this->db->insert(
                    "INSERT INTO inscriptions (eleve_id, classe_id, annee_scolaire_id, date_inscription, statut)
                     VALUES (:eid, :cid, :aid, CURDATE(), :statut)",
                    [
                        'eid'    => $eleveId,
                        'cid'    => $nouvelleClasseId,
                        'aid'    => $anneeActive,
                        'statut' => $decision === 'Redouble' ? 'Redoublant' : 'Actif'
                    ]
                );

                $inscrits++;
            } catch (Exception $e) {
                $erreurs[] = "Élève ID {$eleveId} : erreur technique.";
                error_log('Erreur réinscription: ' . $e->getMessage());
            }
        }

        $message = "{$inscrits} élève(s) réinscrit(s) avec succès.";
        if (!empty($erreurs)) {
            $message .= ' ' . count($erreurs) . ' erreur(s).';
        }

        logActivity('Réinscription', 'inscriptions', null, "Réinscription massive : {$inscrits} élèves");

        jsonResponse(true, $message, [
            'inscrits' => $inscrits,
            'erreurs'  => $erreurs
        ]);
    }

    // ============================================================
    // HISTORIQUE D'UN ÉLÈVE
    // ============================================================

    private function getHistorique(): void
    {
        $eleveId = (int) post('eleve_id', 0);
        if ($eleveId <= 0) jsonResponse(false, 'ID élève invalide.', [], 400);

        $historique = $this->db->fetchAll(
            "SELECT i.*, c.nom AS classe_nom, c.niveau AS classe_niveau,
                    a.libelle AS annee_libelle,
                    DATE_FORMAT(i.date_inscription, '%d/%m/%Y') AS date_inscription_format
             FROM inscriptions i
             INNER JOIN classes c ON i.classe_id = c.id
             INNER JOIN annees_scolaires a ON i.annee_scolaire_id = a.id
             WHERE i.eleve_id = :eid
             ORDER BY a.date_debut DESC, i.date_inscription DESC",
            ['eid' => $eleveId]
        );

        $eleve = $this->db->fetchOne(
            "SELECT id, matricule, nom, prenom FROM eleves WHERE id = :id",
            ['id' => $eleveId]
        );

        jsonResponse(true, 'Historique récupéré.', [
            'eleve'      => $eleve,
            'historique' => $historique
        ]);
    }

    /**
     * Récupère les classes disponibles (avec capacité)
     */
    private function getClassesDisponibles(): void
    {
        $anneeId = (int) post('annee_id', 0);
        if ($anneeId <= 0) {
            $anneeId = (int) $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");
        }

        $classes = $this->db->fetchAll(
            "SELECT c.id, c.nom, c.niveau, c.capacite_max,
                    (SELECT COUNT(*) FROM inscriptions WHERE classe_id = c.id AND statut = 'Actif') AS nb_inscrits
             FROM classes c
             WHERE c.annee_scolaire_id = :aid
             ORDER BY c.niveau",
            ['aid' => $anneeId]
        );

        jsonResponse(true, 'Classes récupérées.', ['classes' => $classes]);
    }

    // ============================================================
    // MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère toutes les années scolaires (pour filtres)
     */
    public function getAnneesScolaires(): array
    {
        return $this->db->fetchAll("SELECT * FROM annees_scolaires ORDER BY date_debut DESC");
    }

    /**
     * Récupère les classes de l'année active
     */
    public function getClassesActives(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM inscriptions WHERE classe_id = c.id AND statut = 'Actif') AS nb_inscrits
             FROM classes c
             WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau"
        );
    }

    /**
     * Récupère les élèves non inscrits (disponibles pour inscription)
     */
    public function getElevesNonInscrits(): array
    {
        $anneeActive = $this->db->fetchColumn("SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1");

        return $this->db->fetchAll(
            "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe, e.date_naissance
             FROM eleves e
             WHERE e.est_actif = 1
             AND e.id NOT IN (
                 SELECT eleve_id FROM inscriptions WHERE annee_scolaire_id = :aid AND statut = 'Actif'
             )
             ORDER BY e.nom, e.prenom",
            ['aid' => $anneeActive]
        );
    }

    /**
     * Récupère une inscription par ID
     */
    public function getInscriptionById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT i.*, e.matricule, e.nom, e.prenom, c.nom AS classe_nom, a.libelle AS annee_libelle
             FROM inscriptions i
             INNER JOIN eleves e ON i.eleve_id = e.id
             INNER JOIN classes c ON i.classe_id = c.id
             INNER JOIN annees_scolaires a ON i.annee_scolaire_id = a.id
             WHERE i.id = :id",
            ['id' => $id]
        );
    }

    /**
     * Récupère l'historique complet d'un élève
     */
    public function getHistoriqueEleve(int $eleveId): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, c.nom AS classe_nom, c.niveau, a.libelle AS annee_libelle
             FROM inscriptions i
             INNER JOIN classes c ON i.classe_id = c.id
             INNER JOIN annees_scolaires a ON i.annee_scolaire_id = a.id
             WHERE i.eleve_id = :eid
             ORDER BY a.date_debut DESC",
            ['eid' => $eleveId]
        );
    }
}