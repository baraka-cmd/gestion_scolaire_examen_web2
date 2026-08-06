<?php
/**
 * Fichier : src/controllers/NotificationController.php
 * Rôle    : Gestion des notifications — Création, envoi, consultation
 * Système : École primaire congolaise — Communication
 * Version : 1.0
 */

defined('ACCESS') or die('Accès interdit');
require_once SRC_PATH . 'services' . DIRECTORY_SEPARATOR . 'EmailService.php';
class NotificationController
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
        if (!can('notifications.consulter')) {
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
            case 'marquerLue':
                $this->marquerLue();
                break;
            case 'marquerToutesLues':
                $this->marquerToutesLues();
                break;
            case 'supprimer':
                $this->supprimer();
                break;
            case 'envoyer':
                $this->envoyer();
                break;
            case 'getCount':
                $this->getCount();
                break;
            case 'getParentsByClasse':
                $this->getParentsByClasse();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. LISTE DES NOTIFICATIONS
    // ============================================================

    /**
     * Récupère la liste paginée des notifications de l'utilisateur connecté
     */
    public function getListe(): void
{
    $page    = max(1, (int) post('page', 1));
    $type    = post('type', '');
    $lu      = post('lu', '');
    $parPage = ITEMS_PER_PAGE; // 15 depuis app.php

    $userId = $_SESSION['utilisateur_id'];
    $isAdmin = hasRole(['Admin', 'Directeur']);

    $where = [];
    $params = [];

    if (!$isAdmin) {
        $where[] = "n.utilisateur_id = :uid";
        $params['uid'] = $userId;
    }

    if (!empty($type)) {
        $where[] = "n.type_notification = :type";
        $params['type'] = $type;
    }

    if ($lu !== '') {
        $where[] = "n.est_lue = :lu";
        $params['lu'] = (int) $lu;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Compter le total
    $total = (int) $this->db->fetchColumn(
        "SELECT COUNT(*) FROM notifications n
         LEFT JOIN utilisateurs u ON n.utilisateur_id = u.id
         {$whereClause}",
        $params
    );

    // Calculer l'offset
    $offset = ($page - 1) * $parPage;
    $pages = ceil($total / $parPage);

    // Ajouter limit et offset directement dans la requête (pas en paramètres liés)
    $notifications = $this->db->fetchAll(
        "SELECT n.*, 
                DATE_FORMAT(n.date_envoi, '%d/%m/%Y') AS date_format,
                TIME_FORMAT(n.date_envoi, '%H:%i') AS heure_format,
                DATE_FORMAT(n.date_lecture, '%d/%m/%Y') AS date_lecture_format,
                CONCAT(u.nom, ' ', u.prenom) AS destinataire_nom,
                u.login AS destinataire_login
         FROM notifications n
         LEFT JOIN utilisateurs u ON n.utilisateur_id = u.id
         {$whereClause}
         ORDER BY n.date_envoi DESC
         LIMIT {$offset}, {$parPage}",
        $params
    );

    jsonResponse(true, 'Liste récupérée.', [
        'notifications' => $notifications,
        'total'         => $total,
        'page'          => $page,
        'pages'         => $pages,
        'is_admin'      => $isAdmin
    ]);
}

    // ============================================================
    // 2. MARQUER COMME LUE
    // ============================================================

    /**
     * Marque une notification comme lue
     */
    public function marquerLue(): void
    {
        $notificationId = (int) post('notification_id', 0);
        if ($notificationId <= 0) jsonResponse(false, 'ID notification invalide.', [], 400);

        $this->db->update(
            "UPDATE notifications SET est_lue = 1, date_lecture = NOW() WHERE id = :id AND utilisateur_id = :uid",
            ['id' => $notificationId, 'uid' => $_SESSION['utilisateur_id']]
        );

        jsonResponse(true, 'Notification marquée comme lue.');
    }

    /**
     * Marque toutes les notifications comme lues
     */
    public function marquerToutesLues(): void
    {
        $this->db->update(
            "UPDATE notifications SET est_lue = 1, date_lecture = NOW() WHERE utilisateur_id = :uid AND est_lue = 0",
            ['uid' => $_SESSION['utilisateur_id']]
        );

        jsonResponse(true, 'Toutes les notifications sont marquées comme lues.');
    }

    // ============================================================
    // 3. SUPPRIMER
    // ============================================================

    /**
     * Supprime une notification
     */
    public function supprimer(): void
    {
        $notificationId = (int) post('notification_id', 0);
        if ($notificationId <= 0) jsonResponse(false, 'ID notification invalide.', [], 400);

        $this->db->delete(
            "DELETE FROM notifications WHERE id = :id AND utilisateur_id = :uid",
            ['id' => $notificationId, 'uid' => $_SESSION['utilisateur_id']]
        );

        jsonResponse(true, 'Notification supprimée.');
    }

    // ============================================================
    // 4. ENVOYER UNE NOTIFICATION
    // ============================================================

    /**
     * Envoie une notification manuelle
     */
        /**
     * Envoie une notification manuelle
     */
    /**
 * Envoie une notification manuelle
 */
/**
 * Envoie une notification manuelle
 */
public function envoyer(): void
{
    error_log("=== DÉBUT ENVOI NOTIFICATION (CONTROLLER) ===");
    
    set_time_limit(120);
    requirePermission('notifications.envoyer');

    $destinataireType = post('destinataire_type', '');
    $classeId         = (int) post('classe_id', 0);
    $parentId         = (int) post('parent_id', 0);
    $titre            = post('titre', '');
    $message          = post('message', '');
    $typeNotif        = post('type_notification', 'Information');
    $envoyerEmail     = post('envoyer_email', '0');

    error_log("Données reçues : " . json_encode([
        'destinataire_type' => $destinataireType,
        'classe_id' => $classeId,
        'parent_id' => $parentId,
        'titre' => $titre,
        'type_notification' => $typeNotif,
        'envoyer_email' => $envoyerEmail
    ]));

    if (isBlank($titre)) {
        error_log("Erreur : titre vide");
        if (ob_get_length()) ob_clean();
        jsonResponse(false, 'Le titre est obligatoire.', [], 422);
        exit;
    }
    if (isBlank($message)) {
        error_log("Erreur : message vide");
        if (ob_get_length()) ob_clean();
        jsonResponse(false, 'Le message est obligatoire.', [], 422);
        exit;
    }
    if (empty($destinataireType)) {
        error_log("Erreur : destinataire_type vide");
        if (ob_get_length()) ob_clean();
        jsonResponse(false, 'Veuillez sélectionner un type de destinataire.', [], 422);
        exit;
    }

    try {
        $this->db->beginTransaction();

        $destinataires = [];

        switch ($destinataireType) {
            case 'tous':
                error_log("Recherche de TOUS les parents...");
                $destinataires = $this->db->fetchAll(
                    "SELECT u.id, pt.email, pt.telephone_principal
                     FROM utilisateurs u
                     INNER JOIN parents_tuteurs pt ON u.id = pt.utilisateur_id
                     WHERE u.est_actif = 1 AND pt.utilisateur_id IS NOT NULL"
                );
                error_log("Parents trouvés (tous) : " . count($destinataires));
                break;

            case 'classe':
                if ($classeId <= 0) {
                    error_log("Erreur : classe_id <= 0");
                    if (ob_get_length()) ob_clean();
                    jsonResponse(false, 'Veuillez sélectionner une classe.', [], 422);
                    exit;
                }
                error_log("Recherche des parents de la classe ID = " . $classeId);
                $destinataires = $this->db->fetchAll(
                    "SELECT DISTINCT u.id, pt.email, pt.telephone_principal
                     FROM utilisateurs u
                     INNER JOIN parents_tuteurs pt ON u.id = pt.utilisateur_id
                     INNER JOIN eleve_parent ep ON pt.id = ep.parent_id
                     INNER JOIN inscriptions i ON ep.eleve_id = i.eleve_id AND i.statut = 'Actif'
                     WHERE i.classe_id = :cid AND u.est_actif = 1",
                    ['cid' => $classeId]
                );
                error_log("Parents trouvés (classe) : " . count($destinataires));
                break;

            case 'parent':
                if ($parentId <= 0) {
                    error_log("Erreur : parent_id <= 0");
                    if (ob_get_length()) ob_clean();
                    jsonResponse(false, 'Veuillez sélectionner un parent.', [], 422);
                    exit;
                }
                error_log("Recherche du parent ID = " . $parentId);
                $destinataires = $this->db->fetchAll(
                    "SELECT u.id, pt.email, pt.telephone_principal
                     FROM utilisateurs u
                     INNER JOIN parents_tuteurs pt ON u.id = pt.utilisateur_id
                     WHERE pt.id = :pid AND u.est_actif = 1",
                    ['pid' => $parentId]
                );
                error_log("Parents trouvés (spécifique) : " . count($destinataires));
                break;
        }

        if (empty($destinataires)) {
            error_log("Aucun destinataire trouvé !");
            if (ob_get_length()) ob_clean();
            jsonResponse(false, 'Aucun destinataire trouvé.', [], 404);
            exit;
        }

        $nbEnvoyes = 0;
        $nbEmailsEnvoyes = 0;
        $nbEmailsEchecs = 0;
        
        // Instancier EmailService si nécessaire
        $emailService = null;
        if ($envoyerEmail === '1' || $envoyerEmail === 1) {
            error_log("Tentative d'instanciation EmailService pour l'envoi d'emails...");
            try {
                $emailService = new EmailService();
                error_log("EmailService instancié avec succès.");
            } catch (Throwable $e) {
                error_log("Erreur instanciation EmailService : " . $e->getMessage());
            }
        } else {
            error_log("Envoi d'email NON demandé (envoyer_email = " . $envoyerEmail . ")");
        }

        foreach ($destinataires as $dest) {
            // Enregistrement en base de données
            $this->db->insert(
                "INSERT INTO notifications (utilisateur_id, titre, message, type_notification, date_envoi)
                 VALUES (:uid, :titre, :message, :type, NOW())",
                [
                    'uid'     => $dest['id'],
                    'titre'   => $titre,
                    'message' => $message,
                    'type'    => $typeNotif
                ]
            );

            $nbEnvoyes++;
            error_log("Notification DB créée pour utilisateur ID {$dest['id']}");

            // Envoi de l'email si demandé
            if ($emailService && !empty($dest['email'])) {
                error_log("Tentative d'envoi d'email à {$dest['email']}...");
                try {
                    $// Remplacer cette ligne :


// Par ce bloc :
// Formater le contenu du message pour l'email
$contenuEmail = '<p>Bonjour,</p>';
$contenuEmail .= '<p>' . nl2br(htmlspecialchars($message)) . '</p>';
$contenuEmail .= '<div class="email-divider"></div>';
$contenuEmail .= '<p style="font-size:0.9rem;color:#64748b;">Ceci est une notification officielle de l\'école concernant votre enfant.</p>';

// Si c'est une urgence, ajouter un bandeau d'attention
if ($typeNotif === 'Urgence' || $typeNotif === 'Alerte') {
    $contenuEmail = '<div class="email-important">
        <strong>⚠️ ' . htmlspecialchars($typeNotif) . ' - Action requise</strong><br>
        Veuillez prendre connaissance de ce message dans les plus brefs délais.
    </div>' . $contenuEmail;
}

$result = $emailService->envoyerTemplate($dest['email'], $titre, $contenuEmail);
                    if ($result) {
                        $nbEmailsEnvoyes++;
                        error_log("✅ Email envoyé avec succès à {$dest['email']}");
                    } else {
                        $nbEmailsEchecs++;
                        error_log("❌ Échec envoi email à {$dest['email']} (envoyerTemplate a retourné false)");
                    }
                } catch (Throwable $eEmail) {
                    $nbEmailsEchecs++;
                    error_log("❌ Exception envoi email à {$dest['email']} : " . $eEmail->getMessage());
                }
            } else {
                if ($emailService && empty($dest['email'])) {
                    error_log("Email non envoyé : adresse email vide pour utilisateur ID {$dest['id']}");
                }
            }
        }

        $this->db->commit();
        error_log("Transaction commitée.");

        $messageSucces = "{$nbEnvoyes} notification(s) envoyée(s) avec succès.";
        if ($emailService) {
            $messageSucces .= " {$nbEmailsEnvoyes} email(s) envoyé(s)";
            if ($nbEmailsEchecs > 0) {
                $messageSucces .= ", {$nbEmailsEchecs} échec(s)";
            }
            $messageSucces .= ".";
        }
        
        error_log($messageSucces);
        logActivity('Envoi', 'notifications', null, $messageSucces);

        if (ob_get_length()) {
            ob_clean();
        }

        error_log("=== FIN ENVOI NOTIFICATION (SUCCÈS) ===");
        jsonResponse(true, $messageSucces);
        exit;

    } catch (Throwable $e) {
        // Annule la transaction si elle est active
        try {
            $this->db->rollback();
        } catch (Throwable $eRollback) {
            error_log("Erreur rollback : " . $eRollback->getMessage());
        }

        error_log("❌ Erreur envoi notification: " . $e->getMessage());
        error_log("Trace : " . $e->getTraceAsString());

        if (ob_get_length()) {
            ob_clean();
        }

        error_log("=== FIN ENVOI NOTIFICATION (ERREUR) ===");
        jsonResponse(false, 'Erreur lors de l\'envoi des notifications : ' . $e->getMessage());
        exit;
    }
}


    // Dans NotificationController.php (méthode temporaire)
public function testEmail() {
    // 1. Charger la configuration en utilisant SRC_PATH (déjà défini par index.php)
    if (defined('SRC_PATH')) {
        require_once SRC_PATH . 'config/app.php';
    } else {
        require_once __DIR__ . '/../config/app.php';
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER;
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USER, 'École LA RÉFÉRENCE');
        $mail->addAddress('barakantwali06@gmail.com');
        $mail->Subject = 'Test direct SMTP';
        $mail->Body    = 'Ceci est un test direct.';

        $mail->send();
        echo "<h3 style='color:green;'>ENVOI REUSSI !</h3>";
    } catch (\Exception $e) {
        echo "<h3 style='color:red;'>ERREUR : " . $mail->ErrorInfo . "</h3>";
    }

    exit;
}





    // ============================================================
    // 5. COMPTEUR DE NOTIFICATIONS NON LUES
    // ============================================================

    /**
     * Retourne le nombre de notifications non lues
     */
    public function getCount(): void
    {
        $userId = $_SESSION['utilisateur_id'] ?? 0;

        $count = (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM notifications WHERE utilisateur_id = :uid AND est_lue = 0",
            ['uid' => $userId]
        );

        jsonResponse(true, 'Compteur récupéré.', ['count' => $count]);
    }

    // ============================================================
    // 6. PARENTS PAR CLASSE
    // ============================================================

    /**
     * Récupère les parents d'une classe (pour l'envoi ciblé)
     */
    public function getParentsByClasse(): void
    {
        $classeId = (int) post('classe_id', 0);
        if ($classeId <= 0) jsonResponse(false, 'ID classe invalide.', [], 400);

        $parents = $this->db->fetchAll(
            "SELECT DISTINCT pt.id, pt.nom, pt.prenom, pt.telephone_principal, pt.email,
                    u.id AS utilisateur_id
             FROM parents_tuteurs pt
             INNER JOIN eleve_parent ep ON pt.id = ep.parent_id
             INNER JOIN inscriptions i ON ep.eleve_id = i.eleve_id AND i.statut = 'Actif'
             LEFT JOIN utilisateurs u ON pt.utilisateur_id = u.id
             WHERE i.classe_id = :cid
             ORDER BY pt.nom",
            ['cid' => $classeId]
        );

        jsonResponse(true, 'Parents récupérés.', ['parents' => $parents]);
    }

    // ============================================================
    // 7. MÉTHODES STATIQUES (appelées par d'autres contrôleurs)
    // ============================================================

    /**
     * Crée une notification automatique (appelée depuis AbsenceController, etc.)
     * 
     * @param int    $parentUserId ID de l'utilisateur parent
     * @param string $titre        Titre de la notification
     * @param string $message      Message de la notification
     * @param string $type         Type (Information, Alerte, Rappel, Urgence)
     * @param string $lien         Lien optionnel vers la ressource
     */
    public static function creerNotification(int $parentUserId, string $titre, string $message, string $type = 'Information', ?string $lien = null): void
    {
        try {
            $db = Database::getInstance();

            $db->insert(
                "INSERT INTO notifications (utilisateur_id, titre, message, type_notification, lien, date_envoi)
                 VALUES (:uid, :titre, :message, :type, :lien, NOW())",
                [
                    'uid'     => $parentUserId,
                    'titre'   => $titre,
                    'message' => $message,
                    'type'    => $type,
                    'lien'    => $lien
                ]
            );
        } catch (Exception $e) {
            error_log('Erreur création notification automatique: ' . $e->getMessage());
        }
    }

    /**
     * Crée une notification pour tous les parents d'un élève
     * 
     * @param int    $eleveId ID de l'élève
     * @param string $titre   Titre de la notification
     * @param string $message Message de la notification
     * @param string $type    Type de notification
     */
    public static function notifierParentsEleve(int $eleveId, string $titre, string $message, string $type = 'Information'): void
    {
        try {
            $db = Database::getInstance();

            // Récupérer les parents de l'élève qui ont un compte utilisateur
            $parents = $db->fetchAll(
                "SELECT pt.utilisateur_id
                 FROM parents_tuteurs pt
                 INNER JOIN eleve_parent ep ON pt.id = ep.parent_id
                 WHERE ep.eleve_id = :eid AND pt.utilisateur_id IS NOT NULL",
                ['eid' => $eleveId]
            );

            foreach ($parents as $parent) {
                self::creerNotification($parent['utilisateur_id'], $titre, $message, $type);
            }
        } catch (Exception $e) {
            error_log('Erreur notification parents élève: ' . $e->getMessage());
        }
    }

    // ============================================================
    // 8. MÉTHODES PRIVÉES
    // ============================================================

    /**
     * Envoie un email simple
     */
       /**
     * Envoie un email simple
     */
    public function envoyerEmail($destinataire, $sujet, $message) {
    // Le fichier app.php est déjà chargé par index.php, pas besoin de require_once ici

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // ⚡ 0 = Mode silencieux (pas d'affichage brut sur l'écran des utilisateurs)
        $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF; 
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8'; // Indispensable pour garder les accents propres

        $mail->setFrom(SMTP_USER, ECOLE_NOM);
        $mail->addAddress($destinataire);
        $mail->isHTML(true); // Permet d'envoyer du HTML si besoin
        $mail->Subject = $sujet;
        $mail->Body    = $message;

        return $mail->send(); // Renvoie true si succès
    } catch (\Exception $e) {
        // Enregistrer l'erreur dans les logs PHP au lieu de l'afficher à l'écran
        error_log("Erreur d'envoi d'email : " . $mail->ErrorInfo);
        return false; // Renvoie false en cas d'échec
    }
}



    // ============================================================
    // 9. MÉTHODES PUBLIQUES (pour les vues)
    // ============================================================

    /**
     * Récupère les classes de l'année active
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
     * Récupère les notifications non lues pour le header
     */
    public function getNotificationsHeader(): array
    {
        $userId = $_SESSION['utilisateur_id'] ?? 0;

        return $this->db->fetchAll(
            "SELECT id, titre, message, type_notification, lien,
                    DATE_FORMAT(date_envoi, '%d/%m/%Y %H:%i') AS date_format
             FROM notifications
             WHERE utilisateur_id = :uid AND est_lue = 0
             ORDER BY date_envoi DESC
             LIMIT 5",
            ['uid' => $userId]
        );
    }
}