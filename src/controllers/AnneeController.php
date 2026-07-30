<?php
/**
 * Fichier : src/controllers/AnneeController.php
 * Rôle    : Gestion complète des années scolaires
 */

defined('ACCESS') or die('Accès interdit');

class AnneeController
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
        if (!can('parametres.consulter')) {
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
            case 'getAnnee':
                $this->getAnnee();
                break;
            case 'creer':
                $this->creer();
                break;
            case 'modifier':
                $this->modifier();
                break;
            case 'activer':
                $this->activer();
                break;
            case 'cloturer':
                $this->cloturer();
                break;
            case 'supprimer':
                $this->supprimer();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    /**
     * Liste de toutes les années scolaires
     */
    private function getListe(): void
    {
        $annees = $this->db->fetchAll(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM trimestres t WHERE t.annee_scolaire_id = a.id) AS nb_trimestres,
                    (SELECT COUNT(*) FROM classes c WHERE c.annee_scolaire_id = a.id) AS nb_classes,
                    (SELECT COUNT(*) FROM inscriptions i WHERE i.annee_scolaire_id = a.id) AS nb_inscriptions
             FROM annees_scolaires a
             ORDER BY a.date_debut DESC"
        );

        jsonResponse(true, 'Liste récupérée.', ['annees' => $annees]);
    }

    /**
     * Récupère une année par ID
     */
    private function getAnnee(): void
    {
        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $annee = $this->db->fetchOne(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM trimestres WHERE annee_scolaire_id = a.id) AS nb_trimestres,
                    (SELECT COUNT(*) FROM classes WHERE annee_scolaire_id = a.id) AS nb_classes,
                    (SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = a.id) AS nb_inscriptions
             FROM annees_scolaires a
             WHERE a.id = :id",
            ['id' => $id]
        );

        if (!$annee) jsonResponse(false, 'Année introuvable.', [], 404);

        // Récupérer les trimestres
        $trimestres = $this->db->fetchAll(
            "SELECT * FROM trimestres WHERE annee_scolaire_id = :aid ORDER BY numero",
            ['aid' => $id]
        );

        jsonResponse(true, 'Année récupérée.', [
            'annee'      => $annee,
            'trimestres' => $trimestres
        ]);
    }

    /**
     * Crée une nouvelle année scolaire avec ses 3 trimestres
     */
    private function creer(): void
    {
        requirePermission('parametres.modifier');

        $libelle    = post('libelle', '');
        $dateDebut  = post('date_debut', '');
        $dateFin    = post('date_fin', '');
        $creerTrimestres = (int) post('creer_trimestres', 1);

        $errors = [];

        if (isBlank($libelle)) {
            $errors['libelle'] = 'Le libellé est obligatoire.';
        } elseif (!preg_match('/^\d{4}-\d{4}$/', $libelle)) {
            $errors['libelle'] = 'Format invalide (ex: 2026-2027).';
        } else {
            $existe = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM annees_scolaires WHERE libelle = :libelle",
                ['libelle' => $libelle]
            );
            if ($existe > 0) $errors['libelle'] = 'Cette année scolaire existe déjà.';
        }

        if (isBlank($dateDebut)) {
            $errors['date_debut'] = 'La date de début est obligatoire.';
        } elseif (!isValidDate($dateDebut)) {
            $errors['date_debut'] = 'Date invalide.';
        }

        if (isBlank($dateFin)) {
            $errors['date_fin'] = 'La date de fin est obligatoire.';
        } elseif (!isValidDate($dateFin)) {
            $errors['date_fin'] = 'Date invalide.';
        }

        if (!isBlank($dateDebut) && !isBlank($dateFin) && $dateDebut >= $dateFin) {
            $errors['date_fin'] = 'La date de fin doit être après la date de début.';
        }

        // Vérifier le chevauchement avec d'autres années
        if (!isBlank($dateDebut) && !isBlank($dateFin)) {
            $chevauche = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM annees_scolaires 
                 WHERE (date_debut BETWEEN :debut AND :fin) 
                    OR (date_fin BETWEEN :debut2 AND :fin2)
                    OR (date_debut <= :debut3 AND date_fin >= :fin3)",
                [
                    'debut' => $dateDebut, 'fin' => $dateFin,
                    'debut2' => $dateDebut, 'fin2' => $dateFin,
                    'debut3' => $dateDebut, 'fin3' => $dateFin
                ]
            );
            if ($chevauche > 0) {
                $errors['date_debut'] = 'Cette période chevauche une année existante.';
            }
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->beginTransaction();

            // Créer l'année (désactivée par défaut)
            $anneeId = $this->db->insert(
                "INSERT INTO annees_scolaires (libelle, date_debut, date_fin, est_active, est_cloturee)
                 VALUES (:libelle, :debut, :fin, 0, 0)",
                [
                    'libelle' => $libelle,
                    'debut'   => $dateDebut,
                    'fin'     => $dateFin
                ]
            );

            // Créer les 3 trimestres automatiquement
            if ($creerTrimestres) {
                $this->creerTrimestres($anneeId, $dateDebut, $dateFin);
            }

            $this->db->commit();

            logActivity('Création', 'annees_scolaires', $anneeId, "Année créée : {$libelle}");

            jsonResponse(true, 'Année scolaire créée avec succès.', ['id' => $anneeId]);

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur création année: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la création.');
        }
    }

    /**
     * Modifie une année scolaire
     */
    private function modifier(): void
    {
        requirePermission('parametres.modifier');

        $id         = (int) post('id', 0);
        $libelle    = post('libelle', '');
        $dateDebut  = post('date_debut', '');
        $dateFin    = post('date_fin', '');

        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $annee = $this->db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $id]);
        if (!$annee) jsonResponse(false, 'Année introuvable.', [], 404);

        // Ne pas modifier une année clôturée
        if ($annee['est_cloturee']) {
            jsonResponse(false, 'Impossible de modifier une année clôturée.', [], 403);
        }

        $errors = [];

        if (isBlank($libelle)) $errors['libelle'] = 'Le libellé est obligatoire.';
        elseif (!preg_match('/^\d{4}-\d{4}$/', $libelle)) $errors['libelle'] = 'Format invalide.';
        else {
            $existe = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM annees_scolaires WHERE libelle = :libelle AND id != :id",
                ['libelle' => $libelle, 'id' => $id]
            );
            if ($existe > 0) $errors['libelle'] = 'Cette année existe déjà.';
        }

        if (isBlank($dateDebut)) $errors['date_debut'] = 'Date obligatoire.';
        if (isBlank($dateFin)) $errors['date_fin'] = 'Date obligatoire.';
        if (!isBlank($dateDebut) && !isBlank($dateFin) && $dateDebut >= $dateFin) {
            $errors['date_fin'] = 'La date de fin doit être après la date de début.';
        }

        // Vérifier le chevauchement
        if (!isBlank($dateDebut) && !isBlank($dateFin)) {
            $chevauche = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM annees_scolaires 
                 WHERE id != :id AND (
                     (date_debut BETWEEN :debut AND :fin) 
                     OR (date_fin BETWEEN :debut2 AND :fin2)
                     OR (date_debut <= :debut3 AND date_fin >= :fin3)
                 )",
                [
                    'id' => $id,
                    'debut' => $dateDebut, 'fin' => $dateFin,
                    'debut2' => $dateDebut, 'fin2' => $dateFin,
                    'debut3' => $dateDebut, 'fin3' => $dateFin
                ]
            );
            if ($chevauche > 0) $errors['date_debut'] = 'Cette période chevauche une autre année.';
        }

        if (!empty($errors)) {
            jsonResponse(false, 'Veuillez corriger les erreurs.', ['errors' => $errors], 422);
        }

        try {
            $this->db->update(
                "UPDATE annees_scolaires SET libelle = :libelle, date_debut = :debut, date_fin = :fin WHERE id = :id",
                ['libelle' => $libelle, 'debut' => $dateDebut, 'fin' => $dateFin, 'id' => $id]
            );

            logActivity('Modification', 'annees_scolaires', $id, "Année modifiée : {$libelle}");

            jsonResponse(true, 'Année modifiée avec succès.');

        } catch (Exception $e) {
            error_log('Erreur modification année: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la modification.');
        }
    }

    /**
     * Active une année (désactive les autres)
     */
    private function activer(): void
    {
        requirePermission('parametres.modifier');

        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $annee = $this->db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $id]);
        if (!$annee) jsonResponse(false, 'Année introuvable.', [], 404);

        if ($annee['est_cloturee']) {
            jsonResponse(false, 'Impossible d\'activer une année clôturée.', [], 403);
        }

        try {
            $this->db->beginTransaction();

            // Désactiver toutes les années
            $this->db->update("UPDATE annees_scolaires SET est_active = 0");

            // Activer celle-ci
            $this->db->update("UPDATE annees_scolaires SET est_active = 1 WHERE id = :id", ['id' => $id]);

            // Mettre à jour parametres_ecole
            $this->db->update(
                "UPDATE parametres_ecole SET annee_scolaire_active_id = :id WHERE id = 1",
                ['id' => $id]
            );

            $this->db->commit();

            logActivity('Activation', 'annees_scolaires', $id, "Année activée : {$annee['libelle']}");

            jsonResponse(true, "Année {$annee['libelle']} activée avec succès.");

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur activation année: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de l\'activation.');
        }
    }

    /**
     * Clôture une année
     */
    private function cloturer(): void
    {
        requirePermission('parametres.modifier');

        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $annee = $this->db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $id]);
        if (!$annee) jsonResponse(false, 'Année introuvable.', [], 404);

        if ($annee['est_cloturee']) {
            jsonResponse(false, 'Cette année est déjà clôturée.', [], 409);
        }

        try {
            // Clôturer
            $this->db->update(
                "UPDATE annees_scolaires SET est_cloturee = 1, est_active = 0 WHERE id = :id",
                ['id' => $id]
            );

            logActivity('Clôture', 'annees_scolaires', $id, "Année clôturée : {$annee['libelle']}");

            jsonResponse(true, "Année {$annee['libelle']} clôturée avec succès.");

        } catch (Exception $e) {
            error_log('Erreur clôture année: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la clôture.');
        }
    }

    /**
     * Supprime une année (si aucune inscription)
     */
    private function supprimer(): void
    {
        requirePermission('parametres.modifier');

        $id = (int) post('id', 0);
        if ($id <= 0) jsonResponse(false, 'ID invalide.', [], 400);

        $annee = $this->db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $id]);
        if (!$annee) jsonResponse(false, 'Année introuvable.', [], 404);

        // Vérifier les inscriptions
        $nbInscriptions = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = :aid",
            ['aid' => $id]
        );
        if ($nbInscriptions > 0) {
            jsonResponse(false, "Impossible de supprimer : {$nbInscriptions} élève(s) inscrit(s).", [], 409);
        }

        // Vérifier les classes
        $nbClasses = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM classes WHERE annee_scolaire_id = :aid",
            ['aid' => $id]
        );
        if ($nbClasses > 0) {
            jsonResponse(false, "Impossible de supprimer : {$nbClasses} classe(s) liée(s).", [], 409);
        }

        try {
            // Supprimer les trimestres
            $this->db->delete("DELETE FROM trimestres WHERE annee_scolaire_id = :aid", ['aid' => $id]);
            // Supprimer l'année
            $this->db->delete("DELETE FROM annees_scolaires WHERE id = :id", ['id' => $id]);

            logActivity('Suppression', 'annees_scolaires', $id, "Année supprimée : {$annee['libelle']}");

            jsonResponse(true, 'Année supprimée avec succès.');

        } catch (Exception $e) {
            error_log('Erreur suppression année: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la suppression.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Crée automatiquement les 3 trimestres pour une année
     */
    private function creerTrimestres(int $anneeId, string $dateDebut, string $dateFin): void
    {
        $debut = new DateTime($dateDebut);
        $fin   = new DateTime($dateFin);
        $dureeTotale = $debut->diff($fin)->days;
        $dureeTrimestre = floor($dureeTotale / 3);

        $trimestres = [
            ['numero' => 1, 'libelle' => '1er Trimestre'],
            ['numero' => 2, 'libelle' => '2ème Trimestre'],
            ['numero' => 3, 'libelle' => '3ème Trimestre']
        ];

        foreach ($trimestres as $i => $trim) {
            $tDebut = clone $debut;
            $tDebut->modify('+' . ($i * $dureeTrimestre) . ' days');

            $tFin = clone $tDebut;
            if ($i < 2) {
                $tFin->modify('+' . ($dureeTrimestre - 1) . ' days');
            } else {
                $tFin = clone $fin; // Dernier trimestre = fin de l'année
            }

            // Ajuster pour éviter que la fin dépasse
            if ($tFin > $fin) {
                $tFin = clone $fin;
            }

            $this->db->query(
                "INSERT INTO trimestres (annee_scolaire_id, numero, libelle, date_debut, date_fin, est_actif)
                 VALUES (:aid, :num, :lib, :debut, :fin, 0)",
                [
                    'aid'   => $anneeId,
                    'num'   => $trim['numero'],
                    'lib'   => $trim['libelle'],
                    'debut' => $tDebut->format('Y-m-d'),
                    'fin'   => $tFin->format('Y-m-d')
                ]
            );
        }

        // Activer le premier trimestre
        $premierTrimestre = $this->db->fetchColumn(
            "SELECT id FROM trimestres WHERE annee_scolaire_id = :aid AND numero = 1",
            ['aid' => $anneeId]
        );
        if ($premierTrimestre) {
            $this->db->update("UPDATE trimestres SET est_actif = 1 WHERE id = :id", ['id' => $premierTrimestre]);
        }
    }

    // ============================================================
    // MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère toutes les années
     */
    public function getAllAnnees(): array
    {
        return $this->db->fetchAll(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM classes WHERE annee_scolaire_id = a.id) AS nb_classes,
                    (SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = a.id) AS nb_inscriptions
             FROM annees_scolaires a
             ORDER BY a.date_debut DESC"
        );
    }

    /**
     * Récupère l'année active
     */
    public function getAnneeActive(): ?array
    {
        return $this->db->fetchOne("SELECT * FROM annees_scolaires WHERE est_active = 1 LIMIT 1");
    }

    /**
     * Récupère une année par ID
     */
    public function getAnneeById(int $id): ?array
    {
        return $this->db->fetchOne("SELECT * FROM annees_scolaires WHERE id = :id", ['id' => $id]);
    }
}