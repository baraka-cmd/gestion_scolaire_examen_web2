<?php
/**
 * Fichier : src/controllers/MessageController.php
 * Rôle    : Gestion complète de la messagerie interne
 * Système : École primaire congolaise — Communication
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');

class MessageController
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
        if (!can('messages.consulter')) {
            jsonResponse(false, 'Accès refusé.', [], 403);
        }

        if (!isAjax() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(false, 'Requête invalide.', [], 400);
        }

        $action = post('action', '');

        switch ($action) {
            // ============================================================
            // BOÎTE DE RÉCEPTION
            // ============================================================
            case 'getListe':
                $this->getListe();
                break;
            case 'getConversation':
                $this->getConversation();
                break;
            case 'marquerLu':
                $this->marquerLu();
                break;
            case 'supprimer':
                $this->supprimer();
                break;
            case 'archiver':
                $this->archiver();
                break;

            // ============================================================
            // ENVOI
            // ============================================================
            case 'envoyer':
                $this->envoyer();
                break;
            case 'getDestinataires':
                $this->getDestinataires();
                break;
            case 'rechercherUtilisateurs':
                $this->rechercherUtilisateurs();
                break;

            // Dans la méthode handleAjax(), ajouter ces 3 cases dans le switch :

            case 'getConversationsGroupees':
                $this->getConversationsGroupees();
                break;
            case 'archiverConversation':
                $this->archiverConversation();
                break;
            case 'desarchiverConversation':
                $this->desarchiverConversation();
                break;
            case 'supprimerConversation':
                $this->supprimerConversation();
                break;
            case 'marquerToutLu':
                $this->marquerToutLu();
                break;
            // ============================================================
            // STATS
            // ============================================================
            case 'getCount':
                $this->getCount();
                break;

            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. LISTE DES MESSAGES (BOÎTE DE RÉCEPTION)
    // ============================================================

    private function getListe(): void
    {
        $page    = max(1, (int) post('page', 1));
        $type    = post('type', 'recus'); // recus | envoyes | archives
        $lu      = post('lu', '');
        $recherche = post('recherche', '');
        $parPage = ITEMS_PER_PAGE;

        $userId = $_SESSION['utilisateur_id'];

        $where = [];
        $params = ['uid' => $userId];

        switch ($type) {
            case 'recus':
                $where[] = "m.destinataire_id = :uid";
                $where[] = "m.est_supprime_destinataire = 0";
                $where[] = "m.est_archive_destinataire = 0";
                break;
            case 'envoyes':
                $where[] = "m.expediteur_id = :uid";
                $where[] = "m.est_supprime_expediteur = 0";
                break;
            case 'archives':
                $where[] = "m.destinataire_id = :uid";
                $where[] = "m.est_archive_destinataire = 1";
                $where[] = "m.est_supprime_destinataire = 0";
                break;
        }

        if ($lu !== '') {
            $where[] = "m.est_lu = :lu";
            $params['lu'] = (int) $lu;
        }

        if (!empty($recherche)) {
            $where[] = "(m.sujet LIKE :r1 OR m.contenu LIKE :r2 OR CONCAT(exp.nom, ' ', exp.prenom) LIKE :r3)";
            $params['r1'] = "%{$recherche}%";
            $params['r2'] = "%{$recherche}%";
            $params['r3'] = "%{$recherche}%";
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM messages m {$whereClause}",
            $params
        );

        $offset = ($page - 1) * $parPage;

        $messages = $this->db->fetchAll(
            "SELECT m.*, 
                    DATE_FORMAT(m.date_envoi, '%d/%m/%Y') AS date_format,
                    TIME_FORMAT(m.date_envoi, '%H:%i') AS heure_format,
                    CONCAT(exp.prenom, ' ', exp.nom) AS expediteur_nom,
                    exp.login AS expediteur_login,
                    CONCAT(dest.prenom, ' ', dest.nom) AS destinataire_nom,
                    dest.login AS destinataire_login
             FROM messages m
             INNER JOIN utilisateurs exp ON m.expediteur_id = exp.id
             INNER JOIN utilisateurs dest ON m.destinataire_id = dest.id
             {$whereClause}
             ORDER BY m.date_envoi DESC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $parPage, 'offset' => $offset])
        );

        // Compter les messages non lus
        $nbNonLus = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM messages 
             WHERE destinataire_id = :uid AND est_lu = 0 AND est_supprime_destinataire = 0 AND est_archive_destinataire = 0",
            ['uid' => $userId]
        );

        jsonResponse(true, 'Liste récupérée.', [
            'messages'  => $messages,
            'total'     => $total,
            'page'      => $page,
            'pages'     => ceil($total / $parPage),
            'nb_non_lus'=> $nbNonLus
        ]);
    }

    // ============================================================
    // 2. CONVERSATION (FIL DE DISCUSSION)
    // ============================================================

    private function getConversation(): void
    {
        $userId       = $_SESSION['utilisateur_id'];
        $interlocuteurId = (int) post('interlocuteur_id', 0);
        $page         = max(1, (int) post('page', 1));
        $parPage      = 50;

        if ($interlocuteurId <= 0) {
            jsonResponse(false, 'ID interlocuteur invalide.', [], 400);
        }

        // Récupérer les infos de l'interlocuteur
        $interlocuteur = $this->db->fetchOne(
            "SELECT id, nom, prenom, login, photo FROM utilisateurs WHERE id = :id",
            ['id' => $interlocuteurId]
        );

        if (!$interlocuteur) {
            jsonResponse(false, 'Interlocuteur introuvable.', [], 404);
        }

        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM messages 
             WHERE ((expediteur_id = :uid1 AND destinataire_id = :uid2 AND est_supprime_expediteur = 0)
                 OR (expediteur_id = :uid3 AND destinataire_id = :uid4 AND est_supprime_destinataire = 0))",
            ['uid1' => $userId, 'uid2' => $interlocuteurId, 'uid3' => $interlocuteurId, 'uid4' => $userId]
        );

        $offset = ($page - 1) * $parPage;

        $messages = $this->db->fetchAll(
            "SELECT m.*, 
                    DATE_FORMAT(m.date_envoi, '%d/%m/%Y') AS date_format,
                    TIME_FORMAT(m.date_envoi, '%H:%i') AS heure_format,
                    CONCAT(exp.prenom, ' ', exp.nom) AS expediteur_nom,
                    CONCAT(dest.prenom, ' ', dest.nom) AS destinataire_nom
             FROM messages m
             INNER JOIN utilisateurs exp ON m.expediteur_id = exp.id
             INNER JOIN utilisateurs dest ON m.destinataire_id = dest.id
             WHERE ((m.expediteur_id = :uid1 AND m.destinataire_id = :uid2 AND m.est_supprime_expediteur = 0)
                 OR (m.expediteur_id = :uid3 AND m.destinataire_id = :uid4 AND m.est_supprime_destinataire = 0))
             ORDER BY m.date_envoi ASC
             LIMIT :limit OFFSET :offset",
            array_merge(
                ['uid1' => $userId, 'uid2' => $interlocuteurId, 'uid3' => $interlocuteurId, 'uid4' => $userId],
                ['limit' => $parPage, 'offset' => $offset]
            )
        );

        // Marquer comme lus les messages reçus de cet interlocuteur
        $this->db->update(
            "UPDATE messages SET est_lu = 1, date_lecture = NOW() 
             WHERE expediteur_id = :exp AND destinataire_id = :dest AND est_lu = 0",
            ['exp' => $interlocuteurId, 'dest' => $userId]
        );

        jsonResponse(true, 'Conversation récupérée.', [
            'messages'      => $messages,
            'interlocuteur' => $interlocuteur,
            'total'         => $total,
            'page'          => $page,
            'pages'         => ceil($total / $parPage)
        ]);
    }

    // ============================================================
    // 3. ENVOYER UN MESSAGE
    // ============================================================

    private function envoyer(): void
    {
        requirePermission('messages.envoyer');

        $destinataireType = post('destinataire_type', ''); // tous | role | classe | utilisateur
        $roleDestinataire = post('role_destinataire', '');
        $classeId         = (int) post('classe_id', 0);
        $destinataireId   = (int) post('destinataire_id', 0);
        $sujet            = post('sujet', '');
        $contenu          = post('contenu', '');
        $messageParentId  = (int) post('message_parent_id', 0);

        if (isBlank($sujet)) jsonResponse(false, 'Le sujet est obligatoire.', [], 422);
        if (isBlank($contenu)) jsonResponse(false, 'Le message est obligatoire.', [], 422);
        if (empty($destinataireType)) jsonResponse(false, 'Veuillez choisir un type de destinataire.', [], 422);

        $userId = $_SESSION['utilisateur_id'];
        $destinataires = [];

        switch ($destinataireType) {
            case 'tous':
                // Tous les utilisateurs actifs sauf soi-même
                $destinataires = $this->db->fetchAll(
                    "SELECT id FROM utilisateurs WHERE est_actif = 1 AND id != :uid",
                    ['uid' => $userId]
                );
                break;

            case 'role':
                // Tous les utilisateurs d'un rôle spécifique
                if (empty($roleDestinataire)) {
                    jsonResponse(false, 'Veuillez sélectionner un rôle.', [], 422);
                }
                $destinataires = $this->db->fetchAll(
                    "SELECT u.id FROM utilisateurs u
                     INNER JOIN roles r ON u.role_id = r.id
                     WHERE r.nom = :role AND u.est_actif = 1 AND u.id != :uid",
                    ['role' => $roleDestinataire, 'uid' => $userId]
                );
                break;

            case 'classe':
                // Tous les parents des élèves d'une classe
                if ($classeId <= 0) {
                    jsonResponse(false, 'Veuillez sélectionner une classe.', [], 422);
                }
                $destinataires = $this->db->fetchAll(
                    "SELECT DISTINCT u.id FROM utilisateurs u
                     INNER JOIN parents_tuteurs pt ON u.id = pt.utilisateur_id
                     INNER JOIN eleve_parent ep ON pt.id = ep.parent_id
                     INNER JOIN inscriptions i ON ep.eleve_id = i.eleve_id
                     WHERE i.classe_id = :cid AND i.statut = 'Actif' AND u.est_actif = 1 AND u.id != :uid",
                    ['cid' => $classeId, 'uid' => $userId]
                );

                // Ajouter aussi l'enseignant titulaire de la classe
                $titulaire = $this->db->fetchColumn(
                    "SELECT u.id FROM utilisateurs u
                     INNER JOIN enseignants e ON u.id = e.utilisateur_id
                     WHERE e.classe_id = :cid AND e.est_actif = 1 AND u.est_actif = 1 AND u.id != :uid",
                    ['cid' => $classeId, 'uid' => $userId]
                );
                if ($titulaire) {
                    $destinataires[] = ['id' => $titulaire];
                }
                break;

            case 'utilisateur':
                // Un seul utilisateur
                if ($destinataireId <= 0) {
                    jsonResponse(false, 'Veuillez sélectionner un destinataire.', [], 422);
                }
                $destinataires = [['id' => $destinataireId]];
                break;

            default:
                jsonResponse(false, 'Type de destinataire invalide.', [], 400);
        }

        if (empty($destinataires)) {
            jsonResponse(false, 'Aucun destinataire trouvé.', [], 404);
        }

        try {
            $this->db->beginTransaction();

            $nbEnvoyes = 0;
            foreach ($destinataires as $dest) {
                $this->db->insert(
                    "INSERT INTO messages (expediteur_id, destinataire_id, sujet, contenu, message_parent_id, date_envoi)
                     VALUES (:exp, :dest, :sujet, :contenu, :parent, NOW())",
                    [
                        'exp'     => $userId,
                        'dest'    => $dest['id'],
                        'sujet'   => $sujet,
                        'contenu' => $contenu,
                        'parent'  => $messageParentId > 0 ? $messageParentId : null
                    ]
                );
                $nbEnvoyes++;
            }

            $this->db->commit();

            logActivity('Envoi', 'messages', null, "Message envoyé à {$nbEnvoyes} destinataire(s) : {$sujet}");

            jsonResponse(true, "Message envoyé à {$nbEnvoyes} destinataire(s).");

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('Erreur envoi message: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de l\'envoi du message.');
        }
    }

    // ============================================================
    // 4. MARQUER COMME LU
    // ============================================================

    private function marquerLu(): void
    {
        $messageId = (int) post('message_id', 0);
        if ($messageId <= 0) jsonResponse(false, 'ID message invalide.', [], 400);

        $this->db->update(
            "UPDATE messages SET est_lu = 1, date_lecture = NOW() 
             WHERE id = :id AND destinataire_id = :uid",
            ['id' => $messageId, 'uid' => $_SESSION['utilisateur_id']]
        );

        jsonResponse(true, 'Message marqué comme lu.');
    }

    // ============================================================
    // 5. SUPPRIMER UN MESSAGE
    // ============================================================

    private function supprimer(): void
    {
        $messageId = (int) post('message_id', 0);
        if ($messageId <= 0) jsonResponse(false, 'ID message invalide.', [], 400);

        $userId = $_SESSION['utilisateur_id'];

        // Marquer comme supprimé pour l'utilisateur concerné
        $this->db->update(
            "UPDATE messages SET 
                est_supprime_expediteur = CASE WHEN expediteur_id = :uid THEN 1 ELSE est_supprime_expediteur END,
                est_supprime_destinataire = CASE WHEN destinataire_id = :uid2 THEN 1 ELSE est_supprime_destinataire END
             WHERE id = :id AND (expediteur_id = :uid3 OR destinataire_id = :uid4)",
            ['uid' => $userId, 'uid2' => $userId, 'uid3' => $userId, 'uid4' => $userId, 'id' => $messageId]
        );

        jsonResponse(true, 'Message supprimé.');
    }

    // ============================================================
    // 6. ARCHIVER UN MESSAGE
    // ============================================================

    private function archiver(): void
    {
        $messageId = (int) post('message_id', 0);
        if ($messageId <= 0) jsonResponse(false, 'ID message invalide.', [], 400);

        $this->db->update(
            "UPDATE messages SET est_archive_destinataire = 1 
             WHERE id = :id AND destinataire_id = :uid",
            ['id' => $messageId, 'uid' => $_SESSION['utilisateur_id']]
        );

        jsonResponse(true, 'Message archivé.');
    }

    // ============================================================
    // 7. RECHERCHER DES DESTINATAIRES
    // ============================================================

    private function rechercherUtilisateurs(): void
    {
        $recherche = post('recherche', '');
        if (mb_strlen($recherche) < 2) {
            jsonResponse(true, 'Recherche trop courte.', ['utilisateurs' => []]);
        }

        $userId = $_SESSION['utilisateur_id'];

        $utilisateurs = $this->db->fetchAll(
            "SELECT u.id, u.nom, u.prenom, u.login, r.nom AS role_nom
             FROM utilisateurs u
             INNER JOIN roles r ON u.role_id = r.id
             WHERE u.est_actif = 1 AND u.id != :uid
             AND (CONCAT(u.prenom, ' ', u.nom) LIKE :r1 OR u.login LIKE :r2 OR r.nom LIKE :r3)
             ORDER BY u.nom
             LIMIT 20",
            ['uid' => $userId, 'r1' => "%{$recherche}%", 'r2' => "%{$recherche}%", 'r3' => "%{$recherche}%"]
        );

        jsonResponse(true, 'Utilisateurs trouvés.', ['utilisateurs' => $utilisateurs]);
    }

    // ============================================================
    // 8. LISTE DES DESTINATAIRES PAR GROUPE
    // ============================================================

    private function getDestinataires(): void
    {
        $type  = post('type', '');
        $userId = $_SESSION['utilisateur_id'];

        $result = [
            'roles'    => [],
            'classes'  => [],
            'utilisateurs' => []
        ];

        // Rôles disponibles
        $result['roles'] = $this->db->fetchAll(
            "SELECT DISTINCT r.nom, r.description, COUNT(u.id) AS nb_utilisateurs
             FROM roles r
             LEFT JOIN utilisateurs u ON r.id = u.role_id AND u.est_actif = 1
             WHERE r.est_actif = 1
             GROUP BY r.id, r.nom, r.description
             ORDER BY r.niveau_acces DESC"
        );

        // Classes disponibles
        $result['classes'] = $this->db->fetchAll(
            "SELECT c.id, c.nom, c.niveau,
                    (SELECT COUNT(*) FROM inscriptions i WHERE i.classe_id = c.id AND i.statut = 'Actif') AS nb_eleves
             FROM classes c
             WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau"
        );

        jsonResponse(true, 'Destinataires récupérés.', $result);
    }

    // ============================================================
    // 9. COMPTEUR DE MESSAGES NON LUS
    // ============================================================

    private function getCount(): void
    {
        $userId = $_SESSION['utilisateur_id'] ?? 0;

        $nbNonLus = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM messages 
             WHERE destinataire_id = :uid AND est_lu = 0 
             AND est_supprime_destinataire = 0 AND est_archive_destinataire = 0",
            ['uid' => $userId]
        );

        jsonResponse(true, 'Compteur récupéré.', ['count' => $nbNonLus]);
    }

    // ============================================================
    // MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère les classes actives
     */
    public function getClassesActives(): array
    {
        return $this->db->fetchAll(
            "SELECT c.* FROM classes c
             WHERE c.annee_scolaire_id = (SELECT annee_scolaire_active_id FROM parametres_ecole LIMIT 1)
             ORDER BY c.niveau"
        );
    }

    /**
     * Récupère les messages non lus pour le header
     */
    public function getMessagesHeader(): array
    {
        $userId = $_SESSION['utilisateur_id'] ?? 0;

        return $this->db->fetchAll(
            "SELECT m.id, m.sujet, 
                    DATE_FORMAT(m.date_envoi, '%d/%m/%Y %H:%i') AS date_format,
                    CONCAT(u.prenom, ' ', u.nom) AS expediteur_nom
             FROM messages m
             INNER JOIN utilisateurs u ON m.expediteur_id = u.id
             WHERE m.destinataire_id = :uid AND m.est_lu = 0 
             AND m.est_supprime_destinataire = 0 AND m.est_archive_destinataire = 0
             ORDER BY m.date_envoi DESC
             LIMIT 5",
            ['uid' => $userId]
        );
    }


    // ============================================================
// CONVERSATIONS GROUPÉES (STYLE WHATSAPP)
// ============================================================

private function getConversationsGroupees(): void
{
    $page      = max(1, (int) post('page', 1));
    $type      = post('type', 'recus'); // recus | envoyes | archives
    $lu        = post('lu', '');
    $recherche = post('recherche', '');
    $parPage   = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 15;

    $userId = $_SESSION['utilisateur_id'];

    if ($type === 'recus') {
        $sql = "SELECT 
                    u.id AS interlocuteur_id,
                    CONCAT(u.prenom, ' ', u.nom) AS expediteur_nom,
                    u.login AS expediteur_login,
                    r.nom AS expediteur_role,
                    0 AS destinataire_id,
                    '' AS destinataire_nom,
                    '' AS destinataire_login,
                    '' AS destinataire_role,
                    COALESCE(nonlus.cnt, 0) AS nb_non_lus,
                    COALESCE(total.cnt, 0) AS nb_total,
                    dernier.contenu AS dernier_message,
                    DATE_FORMAT(dernier.date_envoi, '%d/%m/%Y') AS dernier_message_date,
                    TIME_FORMAT(dernier.date_envoi, '%H:%i') AS dernier_message_heure,
                    dernier.date_envoi AS dernier_timestamp
                FROM utilisateurs u
                INNER JOIN roles r ON u.role_id = r.id
                INNER JOIN (
                    SELECT expediteur_id, MAX(id) AS max_id
                    FROM messages
                    WHERE destinataire_id = :uid_base 
                      AND est_supprime_destinataire = 0 
                      AND est_archive_destinataire = 0
                    GROUP BY expediteur_id
                ) grp ON u.id = grp.expediteur_id
                INNER JOIN messages dernier ON dernier.id = grp.max_id
                LEFT JOIN (
                    SELECT expediteur_id, COUNT(*) AS cnt
                    FROM messages
                    WHERE destinataire_id = :uid_nonlu 
                      AND est_lu = 0 
                      AND est_supprime_destinataire = 0 
                      AND est_archive_destinataire = 0
                    GROUP BY expediteur_id
                ) nonlus ON u.id = nonlus.expediteur_id
                LEFT JOIN (
                    SELECT expediteur_id, COUNT(*) AS cnt
                    FROM messages
                    WHERE destinataire_id = :uid_total 
                      AND est_supprime_destinataire = 0 
                      AND est_archive_destinataire = 0
                    GROUP BY expediteur_id
                ) total ON u.id = total.expediteur_id
                WHERE u.est_actif = 1";

        $params = [
            'uid_base'   => $userId,
            'uid_nonlu'  => $userId,
            'uid_total'  => $userId
        ];

    } elseif ($type === 'envoyes') {
        $sql = "SELECT 
                    u.id AS interlocuteur_id,
                    '' AS expediteur_nom,
                    '' AS expediteur_login,
                    '' AS expediteur_role,
                    u.id AS destinataire_id,
                    CONCAT(u.prenom, ' ', u.nom) AS destinataire_nom,
                    u.login AS destinataire_login,
                    r.nom AS destinataire_role,
                    0 AS nb_non_lus,
                    COALESCE(total.cnt, 0) AS nb_total,
                    dernier.contenu AS dernier_message,
                    DATE_FORMAT(dernier.date_envoi, '%d/%m/%Y') AS dernier_message_date,
                    TIME_FORMAT(dernier.date_envoi, '%H:%i') AS dernier_message_heure,
                    dernier.date_envoi AS dernier_timestamp
                FROM utilisateurs u
                INNER JOIN roles r ON u.role_id = r.id
                INNER JOIN (
                    SELECT destinataire_id, MAX(id) AS max_id
                    FROM messages
                    WHERE expediteur_id = :uid_base 
                      AND est_supprime_expediteur = 0
                    GROUP BY destinataire_id
                ) grp ON u.id = grp.destinataire_id
                INNER JOIN messages dernier ON dernier.id = grp.max_id
                LEFT JOIN (
                    SELECT destinataire_id, COUNT(*) AS cnt
                    FROM messages
                    WHERE expediteur_id = :uid_total 
                      AND est_supprime_expediteur = 0
                    GROUP BY destinataire_id
                ) total ON u.id = total.destinataire_id
                WHERE u.est_actif = 1";

        $params = [
            'uid_base'  => $userId,
            'uid_total' => $userId
        ];

    } else {
        // Archives
        $sql = "SELECT 
                    u.id AS interlocuteur_id,
                    CONCAT(u.prenom, ' ', u.nom) AS expediteur_nom,
                    u.login AS expediteur_login,
                    r.nom AS expediteur_role,
                    0 AS destinataire_id,
                    '' AS destinataire_nom,
                    '' AS destinataire_login,
                    '' AS destinataire_role,
                    0 AS nb_non_lus,
                    COALESCE(total.cnt, 0) AS nb_total,
                    dernier.contenu AS dernier_message,
                    DATE_FORMAT(dernier.date_envoi, '%d/%m/%Y') AS dernier_message_date,
                    TIME_FORMAT(dernier.date_envoi, '%H:%i') AS dernier_message_heure,
                    dernier.date_envoi AS dernier_timestamp
                FROM utilisateurs u
                INNER JOIN roles r ON u.role_id = r.id
                INNER JOIN (
                    SELECT expediteur_id, MAX(id) AS max_id
                    FROM messages
                    WHERE destinataire_id = :uid_base 
                      AND est_archive_destinataire = 1 
                      AND est_supprime_destinataire = 0
                    GROUP BY expediteur_id
                ) grp ON u.id = grp.expediteur_id
                INNER JOIN messages dernier ON dernier.id = grp.max_id
                LEFT JOIN (
                    SELECT expediteur_id, COUNT(*) AS cnt
                    FROM messages
                    WHERE destinataire_id = :uid_total 
                      AND est_archive_destinataire = 1 
                      AND est_supprime_destinataire = 0
                    GROUP BY expediteur_id
                ) total ON u.id = total.expediteur_id
                WHERE u.est_actif = 1";

        $params = [
            'uid_base'  => $userId,
            'uid_total' => $userId
        ];
    }

    // Filtrage par recherche utilisateur
    if (!empty($recherche)) {
        $sql .= " AND (CONCAT(u.prenom, ' ', u.nom) LIKE :search OR u.login LIKE :search)";
        $params['search'] = "%{$recherche}%";
    }

    // Encapsulation SQL pour le filtrage lu/non-lu avant pagination
    $whereConditions = [];
    if ($lu === '0') {
        $whereConditions[] = "nb_non_lus > 0";
    } elseif ($lu === '1') {
        $whereConditions[] = "nb_non_lus = 0";
    }

    $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

    $queryFinal = "SELECT * FROM ({$sql}) AS conv_list {$whereClause} ORDER BY dernier_timestamp DESC";

    // Compter le total filtré
    $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM ({$queryFinal}) AS count_tbl", $params);

    // Calcul de l'offset et exécution de la pagination
    $offset = ($page - 1) * $parPage;
    $conversations = $this->db->fetchAll(
        $queryFinal . " LIMIT :limit OFFSET :offset",
        array_merge($params, ['limit' => $parPage, 'offset' => $offset])
    );

    jsonResponse(true, 'Conversations récupérées.', [
        'conversations' => $conversations,
        'total'         => $total,
        'page'          => $page,
        'pages'         => $total > 0 ? ceil($total / $parPage) : 1
    ]);
}

private function archiverConversation(): void
{
    $interlocuteurId = (int) post('interlocuteur_id', 0);
    $userId = $_SESSION['utilisateur_id'];

    $this->db->update(
        "UPDATE messages SET est_archive_destinataire = 1 
         WHERE destinataire_id = :uid AND expediteur_id = :iid",
        ['uid' => $userId, 'iid' => $interlocuteurId]
    );

    jsonResponse(true, 'Conversation archivée.');
}

private function desarchiverConversation(): void
{
    $interlocuteurId = (int) post('interlocuteur_id', 0);
    $userId = $_SESSION['utilisateur_id'];

    $this->db->update(
        "UPDATE messages SET est_archive_destinataire = 0 
         WHERE destinataire_id = :uid AND expediteur_id = :iid",
        ['uid' => $userId, 'iid' => $interlocuteurId]
    );

    jsonResponse(true, 'Conversation désarchivée.');
}

private function supprimerConversation(): void
{
    $interlocuteurId = (int) post('interlocuteur_id', 0);
    $userId = $_SESSION['utilisateur_id'];

    $this->db->update(
        "UPDATE messages SET 
            est_supprime_destinataire = CASE WHEN destinataire_id = :uid THEN 1 ELSE est_supprime_destinataire END,
            est_supprime_expediteur = CASE WHEN expediteur_id = :uid2 THEN 1 ELSE est_supprime_expediteur END
         WHERE (expediteur_id = :uid3 AND destinataire_id = :iid)
            OR (expediteur_id = :iid2 AND destinataire_id = :uid4)",
        ['uid' => $userId, 'uid2' => $userId, 'uid3' => $userId, 'iid' => $interlocuteurId, 'iid2' => $interlocuteurId, 'uid4' => $userId]
    );

    jsonResponse(true, 'Conversation supprimée.');
}

private function marquerToutLu(): void
{
    $userId = $_SESSION['utilisateur_id'];

    $this->db->update(
        "UPDATE messages SET est_lu = 1, date_lecture = NOW() 
         WHERE destinataire_id = :uid AND est_lu = 0",
        ['uid' => $userId]
    );

    jsonResponse(true, 'Tous les messages sont marqués comme lus.', ['count' => 0]);
}
}