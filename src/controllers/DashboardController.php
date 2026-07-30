<?php
/**
 * Fichier : src/controllers/DashboardController.php
 * Rôle    : Gestion des données du tableau de bord
 */

defined('ACCESS') or die('Accès interdit');

class DashboardController
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
        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'getElevesParClasse':
                $this->getElevesParClasse();
                break;
            case 'getPaiementsMensuels':
                $this->getPaiementsMensuels();
                break;
            case 'getPresenceParClasse':
                $this->getPresenceParClasse();
                break;
            case 'getRepartitionSexe':
                $this->getRepartitionSexe();
                break;
            case 'getResultatsParTrimestre':
                $this->getResultatsParTrimestre();
                break;
            case 'getDerniersEleves':
                $this->getDerniersEleves();
                break;
            case 'getAnniversaires':
                $this->getAnniversaires();
                break;
            case 'getActivites':
                $this->getActivites();
                break;
            case 'getStats':
                $this->getStats();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    /**
     * Récupère toutes les statistiques
     */
    public function getStats(): void
    {
        try {
            $anneeActive = $this->getAnneeActiveId();

            $totalEleves = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM inscriptions WHERE annee_scolaire_id = :annee AND statut = 'Actif'",
                ['annee' => $anneeActive]
            );

            $totalEnseignants = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM enseignants WHERE est_actif = 1"
            );

            $totalClasses = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM classes WHERE annee_scolaire_id = :annee",
                ['annee' => $anneeActive]
            );

            // Taux de présence du jour
            $absencesJour = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM absences WHERE date_absence = CURDATE() AND type_absence = 'Absence'"
            );
            $presencesJour = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM absences WHERE date_absence = CURDATE() AND type_absence = 'Présent'"
            );
            $totalPresence = $absencesJour + $presencesJour;
            $tauxPresence = $totalPresence > 0 ? round(($presencesJour / $totalPresence) * 100, 1) : 0;

            // Paiements du mois
            $paiementsMois = $this->db->fetchColumn(
                "SELECT COALESCE(SUM(montant_paye), 0) FROM paiements 
                 WHERE MONTH(date_paiement) = MONTH(CURDATE()) AND YEAR(date_paiement) = YEAR(CURDATE())"
            );

            // Messages non lus
            $messagesNonLus = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM messages 
                 WHERE destinataire_id = :uid AND est_lu = 0 AND est_supprime_destinataire = 0",
                ['uid' => $_SESSION['utilisateur_id']]
            );

            // Évolution élèves (ce mois vs mois dernier)
            $elevesCeMois = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM eleves WHERE MONTH(date_inscription) = MONTH(CURDATE()) AND YEAR(date_inscription) = YEAR(CURDATE())"
            );
            $elevesMoisDernier = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM eleves WHERE MONTH(date_inscription) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(date_inscription) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
            );

            jsonResponse(true, 'Statistiques récupérées.', [
                'stats' => [
                    'totalEleves'       => (int) $totalEleves,
                    'totalEnseignants'  => (int) $totalEnseignants,
                    'totalClasses'      => (int) $totalClasses,
                    'tauxPresence'      => $tauxPresence,
                    'paiementsMois'     => (float) $paiementsMois,
                    'messagesNonLus'    => (int) $messagesNonLus,
                    'elevesCeMois'      => (int) $elevesCeMois,
                    'elevesMoisDernier' => (int) $elevesMoisDernier,
                ]
            ]);

        } catch (Exception $e) {
            error_log('Erreur getStats: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du chargement des statistiques.');
        }
    }

    /**
     * Élèves par classe
     */
    private function getElevesParClasse(): void
    {
        try {
            $anneeActive = $this->getAnneeActiveId();

            $result = $this->db->fetchAll(
                "SELECT c.nom, COUNT(i.id) as total
                 FROM classes c
                 LEFT JOIN inscriptions i ON c.id = i.classe_id AND i.statut = 'Actif'
                 WHERE c.annee_scolaire_id = :annee
                 GROUP BY c.id, c.nom
                 ORDER BY c.niveau",
                ['annee' => $anneeActive]
            );

            jsonResponse(true, 'Données récupérées.', [
                'labels' => array_column($result, 'nom'),
                'values' => array_map('intval', array_column($result, 'total')),
            ]);

        } catch (Exception $e) {
            error_log('Erreur getElevesParClasse: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du chargement des données.');
        }
    }

    /**
     * Paiements mensuels
     */
    private function getPaiementsMensuels(): void
    {
        try {
            $result = $this->db->fetchAll(
                "SELECT MONTH(date_paiement) as mois, COALESCE(SUM(montant_paye), 0) as total
                 FROM paiements
                 WHERE YEAR(date_paiement) = YEAR(CURDATE())
                 GROUP BY MONTH(date_paiement)
                 ORDER BY mois"
            );

            $mois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
            $values = array_fill(0, 12, 0);

            foreach ($result as $row) {
                $values[$row['mois'] - 1] = (float) $row['total'];
            }

            jsonResponse(true, 'Données récupérées.', [
                'labels' => $mois,
                'values' => $values,
            ]);

        } catch (Exception $e) {
            error_log('Erreur getPaiementsMensuels: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du chargement des données.');
        }
    }

    /**
     * Taux de présence par classe (aujourd'hui)
     */
    private function getPresenceParClasse(): void
    {
        try {
            $anneeActive = $this->getAnneeActiveId();

            $result = $this->db->fetchAll(
                "SELECT c.nom,
                        COALESCE(SUM(CASE WHEN a.type_absence = 'Présent' THEN 1 ELSE 0 END), 0) as presents,
                        COUNT(a.id) as total
                 FROM classes c
                 INNER JOIN inscriptions i ON c.id = i.classe_id AND i.statut = 'Actif'
                 INNER JOIN eleves e ON i.eleve_id = e.id AND e.est_actif = 1
                 LEFT JOIN absences a ON e.id = a.eleve_id AND a.date_absence = CURDATE()
                 WHERE c.annee_scolaire_id = :annee
                 GROUP BY c.id, c.nom
                 ORDER BY c.niveau",
                ['annee' => $anneeActive]
            );

            $labels = [];
            $values = [];

            foreach ($result as $row) {
                $labels[] = $row['nom'];
                $total = (int) $row['total'];
                $presents = (int) $row['presents'];
                $taux = $total > 0 ? round(($presents / $total) * 100, 1) : 0;
                $values[] = $taux;
            }

            jsonResponse(true, 'Données récupérées.', [
                'labels' => $labels,
                'values' => $values,
            ]);

        } catch (Exception $e) {
            error_log('Erreur getPresenceParClasse: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du chargement des données.');
        }
    }

    /**
     * Répartition filles/garçons
     */
    private function getRepartitionSexe(): void
    {
        try {
            $anneeActive = $this->getAnneeActiveId();

            $garcons = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM eleves e
                 INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
                 WHERE e.sexe = 'M' AND i.annee_scolaire_id = :annee AND e.est_actif = 1",
                ['annee' => $anneeActive]
            );

            $filles = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM eleves e
                 INNER JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
                 WHERE e.sexe = 'F' AND i.annee_scolaire_id = :annee AND e.est_actif = 1",
                ['annee' => $anneeActive]
            );

            jsonResponse(true, 'Données récupérées.', [
                'labels' => ['Garçons', 'Filles'],
                'values' => [(int) $garcons, (int) $filles],
            ]);

        } catch (Exception $e) {
            error_log('Erreur getRepartitionSexe: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du chargement des données.');
        }
    }

    /**
     * Résultats par trimestre (réussite/ajournement/échec)
     */
    private function getResultatsParTrimestre(): void
    {
        try {
            $anneeActive = $this->getAnneeActiveId();

            $result = $this->db->fetchAll(
                "SELECT t.libelle,
                        COALESCE(SUM(CASE WHEN r.decision = 'Passe' THEN 1 ELSE 0 END), 0) as reussite,
                        COALESCE(SUM(CASE WHEN r.decision = 'Ajourné' THEN 1 ELSE 0 END), 0) as ajournement,
                        COALESCE(SUM(CASE WHEN r.decision = 'Redouble' THEN 1 ELSE 0 END), 0) as echec
                 FROM trimestres t
                 LEFT JOIN resultats_trimestriels r ON t.id = r.trimestre_id
                 WHERE t.annee_scolaire_id = :annee
                 GROUP BY t.id, t.libelle
                 ORDER BY t.numero",
                ['annee' => $anneeActive]
            );

            jsonResponse(true, 'Données récupérées.', [
                'labels'      => array_column($result, 'libelle'),
                'reussite'    => array_map('intval', array_column($result, 'reussite')),
                'ajournement' => array_map('intval', array_column($result, 'ajournement')),
                'echec'       => array_map('intval', array_column($result, 'echec')),
            ]);

        } catch (Exception $e) {
            error_log('Erreur getResultatsParTrimestre: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du chargement des données.');
        }
    }

    /**
     * Derniers élèves inscrits
     */
    private function getDerniersEleves(): void
    {
        try {
            $eleves = $this->db->fetchAll(
                "SELECT e.id, e.matricule, e.nom, e.prenom, e.sexe, 
                        DATE_FORMAT(e.date_inscription, '%d/%m/%Y') as date_inscription,
                        c.nom as classe
                 FROM eleves e
                 LEFT JOIN inscriptions i ON e.id = i.eleve_id AND i.statut = 'Actif'
                 LEFT JOIN classes c ON i.classe_id = c.id
                 WHERE e.est_actif = 1
                 ORDER BY e.date_inscription DESC
                 LIMIT 5"
            );

            jsonResponse(true, 'Données récupérées.', [
                'eleves' => $eleves
            ]);

        } catch (Exception $e) {
            error_log('Erreur getDerniersEleves: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du chargement des données.');
        }
    }

    /**
     * Prochains anniversaires (7 jours)
     */
    private function getAnniversaires(): void
    {
        try {
            $anniversaires = $this->db->fetchAll(
                "SELECT id, nom, prenom, 
                        DATE_FORMAT(date_naissance, '%d/%m') as date_naissance,
                        TIMESTAMPDIFF(YEAR, date_naissance, CURDATE()) + 1 as age
                 FROM eleves
                 WHERE est_actif = 1
                 AND DATE_FORMAT(date_naissance, '%m-%d') 
                     BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') 
                     AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')
                 ORDER BY DATE_FORMAT(date_naissance, '%m-%d')
                 LIMIT 5"
            );

            jsonResponse(true, 'Données récupérées.', [
                'anniversaires' => $anniversaires
            ]);

        } catch (Exception $e) {
            error_log('Erreur getAnniversaires: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du chargement des données.');
        }
    }

    /**
     * Activités récentes
     */
    private function getActivites(): void
    {
        try {
            $activites = $this->db->fetchAll(
                "SELECT description, 
                        DATE_FORMAT(date_action, '%d/%m/%Y à %H:%i') as date_action
                 FROM logs_activites
                 ORDER BY date_action DESC
                 LIMIT 10"
            );

            jsonResponse(true, 'Données récupérées.', [
                'activites' => $activites
            ]);

        } catch (Exception $e) {
            error_log('Erreur getActivites: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors du chargement des données.');
        }
    }

    /**
     * Récupère l'ID de l'année scolaire active
     */
    private function getAnneeActiveId(): int
    {
        $id = $this->db->fetchColumn(
            "SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1"
        );

        if (!$id) {
            $id = $this->db->fetchColumn(
                "SELECT id FROM annees_scolaires WHERE est_active = 1 LIMIT 1"
            );
        }

        return (int) ($id ?? 1);
    }
}