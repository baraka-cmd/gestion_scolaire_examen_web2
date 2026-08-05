<?php
/**
 * Fichier : src/controllers/LogController.php
 * Rôle    : Consultation et gestion des journaux d'activité
 * Système : École primaire congolaise — Administration
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class LogController
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
        if (!can('logs.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            case 'getListe':
                $this->getListe();
                break;
            case 'getStatistiques':
                $this->getStatistiques();
                break;
            case 'getTopActions':
                $this->getTopActions();
                break;
            case 'getTopUtilisateurs':
                $this->getTopUtilisateurs();
                break;
            case 'getActiviteParJour':
                $this->getActiviteParJour();
                break;
            case 'viderLogs':
                $this->viderLogs();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. LISTE PAGINÉE DES LOGS
    // ============================================================

    private function getListe(): void
    {
        $page       = max(1, (int) post('page', 1));
        $utilisateurId = (int) post('utilisateur_id', 0);
        $action     = post('action_filtre', '');
        $table      = post('table_concernee', '');
        $dateDebut  = post('date_debut', '');
        $dateFin    = post('date_fin', '');
        $recherche  = post('recherche', '');
        $parPage    = ITEMS_PER_PAGE;

        $where = [];
        $params = [];

        if ($utilisateurId > 0) {
            $where[] = "l.utilisateur_id = :uid";
            $params['uid'] = $utilisateurId;
        }

        if (!empty($action)) {
            $where[] = "l.action = :action";
            $params['action'] = $action;
        }

        if (!empty($table)) {
            $where[] = "l.table_concernee = :table_name";
            $params['table_name'] = $table;
        }

        if (!empty($dateDebut)) {
            $where[] = "l.date_action >= :debut";
            $params['debut'] = $dateDebut . ' 00:00:00';
        }

        if (!empty($dateFin)) {
            $where[] = "l.date_action <= :fin";
            $params['fin'] = $dateFin . ' 23:59:59';
        }

        if (!empty($recherche)) {
            $where[] = "(l.description LIKE :r1 OR l.action LIKE :r2 OR CONCAT(u.prenom, ' ', u.nom) LIKE :r3)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM logs_activites l
             LEFT JOIN utilisateurs u ON l.utilisateur_id = u.id
             {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;

        $logs = $this->db->fetchAll(
            "SELECT l.*, 
                    DATE_FORMAT(l.date_action, '%d/%m/%Y') AS date_format,
                    TIME_FORMAT(l.date_action, '%H:%i:%s') AS heure_format,
                    CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, '')) AS utilisateur_nom,
                    u.login AS utilisateur_login,
                    COALESCE(u.prenom, 'Système') AS utilisateur_prenom
             FROM logs_activites l
             LEFT JOIN utilisateurs u ON l.utilisateur_id = u.id
             {$whereClause}
             ORDER BY l.date_action DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        jsonResponse(true, 'Liste récupérée.', [
            'logs'  => $logs,
            'total' => $total,
            'page'  => $page,
            'pages' => ceil($total / $parPage)
        ]);
    }

    // ============================================================
    // 2. STATISTIQUES GLOBALES
    // ============================================================

    private function getStatistiques(): void
    {
        // Total des logs
        $totalLogs = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM logs_activites");

        // Logs aujourd'hui
        $logsAujourdhui = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM logs_activites WHERE DATE(date_action) = CURDATE()"
        );

        // Logs cette semaine
        $logsSemaine = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM logs_activites WHERE YEARWEEK(date_action, 1) = YEARWEEK(CURDATE(), 1)"
        );

        // Logs ce mois
        $logsMois = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM logs_activites WHERE MONTH(date_action) = MONTH(CURDATE()) AND YEAR(date_action) = YEAR(CURDATE())"
        );

        // Dernière activité
        $derniereActivite = $this->db->fetchOne(
            "SELECT l.*, 
                    DATE_FORMAT(l.date_action, '%d/%m/%Y à %H:%i:%s') AS date_format,
                    CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, '')) AS utilisateur_nom
             FROM logs_activites l
             LEFT JOIN utilisateurs u ON l.utilisateur_id = u.id
             ORDER BY l.date_action DESC LIMIT 1"
        );

        // Nombre d'utilisateurs distincts ayant effectué des actions
        $nbUtilisateursActifs = (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT utilisateur_id) FROM logs_activites WHERE utilisateur_id IS NOT NULL"
        );

        jsonResponse(true, 'Statistiques récupérées.', [
            'stats' => [
                'total_logs'           => $totalLogs,
                'logs_aujourdhui'      => $logsAujourdhui,
                'logs_semaine'         => $logsSemaine,
                'logs_mois'            => $logsMois,
                'nb_utilisateurs'      => $nbUtilisateursActifs,
                'derniere_activite'    => $derniereActivite
            ]
        ]);
    }

    // ============================================================
    // 3. TOP 10 ACTIONS LES PLUS FRÉQUENTES
    // ============================================================

    private function getTopActions(): void
    {
        $topActions = $this->db->fetchAll(
            "SELECT action, COUNT(*) AS nb 
             FROM logs_activites 
             GROUP BY action 
             ORDER BY nb DESC 
             LIMIT 10"
        );

        jsonResponse(true, 'Top actions récupéré.', ['top_actions' => $topActions]);
    }

    // ============================================================
    // 4. TOP 5 UTILISATEURS LES PLUS ACTIFS
    // ============================================================

    private function getTopUtilisateurs(): void
    {
        $topUtilisateurs = $this->db->fetchAll(
            "SELECT CONCAT(COALESCE(u.prenom, ''), ' ', COALESCE(u.nom, '')) AS utilisateur_nom,
                    u.login,
                    COUNT(*) AS nb,
                    MAX(l.date_action) AS derniere_action,
                    DATE_FORMAT(MAX(l.date_action), '%d/%m/%Y à %H:%i') AS derniere_action_format
             FROM logs_activites l
             INNER JOIN utilisateurs u ON l.utilisateur_id = u.id
             GROUP BY l.utilisateur_id, u.prenom, u.nom, u.login
             ORDER BY nb DESC
             LIMIT 5"
        );

        jsonResponse(true, 'Top utilisateurs récupéré.', ['top_utilisateurs' => $topUtilisateurs]);
    }

    // ============================================================
    // 5. ACTIVITÉ PAR JOUR (7 DERNIERS JOURS)
    // ============================================================

    private function getActiviteParJour(): void
    {
        $activite = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $nb = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM logs_activites WHERE DATE(date_action) = :date",
                ['date' => $date]
            );
            $activite[] = [
                'date'  => $date,
                'label' => date('d/m', strtotime($date)),
                'jour'  => jourEnFrancais($date),
                'nb'    => $nb
            ];
        }

        jsonResponse(true, 'Activité récupérée.', ['activite' => $activite]);
    }

    // ============================================================
    // 6. VIDER LES LOGS (avec confirmation)
    // ============================================================

    private function viderLogs(): void
    {
        requirePermission('logs.supprimer');

        $nbSupprimes = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM logs_activites");

        // Garder les 100 derniers logs pour la sécurité
        $this->db->query(
            "DELETE FROM logs_activites 
             WHERE id NOT IN (SELECT id FROM (SELECT id FROM logs_activites ORDER BY date_action DESC LIMIT 100) AS recent)"
        );

        $nbRestants = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM logs_activites");
        $nbSupprimesReel = $nbSupprimes - $nbRestants;

        logActivity('Nettoyage', 'logs_activites', null, "{$nbSupprimesReel} logs supprimés. 100 conservés.");

        jsonResponse(true, "{$nbSupprimesReel} logs supprimés. Les 100 plus récents sont conservés.", [
            'supprimes' => $nbSupprimesReel,
            'restants'  => $nbRestants
        ]);
    }

    // ============================================================
    // 7. MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère les utilisateurs pour le filtre
     */
    public function getUtilisateursActifs(): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT u.id, CONCAT(u.prenom, ' ', u.nom) AS nom_complet, u.login
             FROM logs_activites l
             INNER JOIN utilisateurs u ON l.utilisateur_id = u.id
             ORDER BY u.nom"
        );
    }

    /**
     * Récupère les actions distinctes pour le filtre
     */
    public function getActionsDistinctes(): array
    {
        $result = $this->db->fetchAll(
            "SELECT DISTINCT action FROM logs_activites ORDER BY action"
        );
        return array_column($result, 'action');
    }

    /**
     * Récupère les tables distinctes pour le filtre
     */
    public function getTablesDistinctes(): array
    {
        $result = $this->db->fetchAll(
            "SELECT DISTINCT table_concernee FROM logs_activites WHERE table_concernee IS NOT NULL ORDER BY table_concernee"
        );
        return array_column($result, 'table_concernee');
    }
}