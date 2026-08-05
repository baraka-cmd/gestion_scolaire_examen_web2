<?php
/**
 * Fichier : src/controllers/SauvegardeController.php
 * Rôle    : Gestion des sauvegardes de la base de données
 * Système : École primaire congolaise — Administration
 * Version : 1.1 — Adaptée à la structure réelle de la table
 */

defined('ACCESS') or die('Accès interdit');

class SauvegardeController
{
    private Database $db;
    private string $backupDir;
    private string $host;
    private string $dbname;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->backupDir = STORAGE_PATH . 'backups' . DIRECTORY_SEPARATOR;

        // Créer le dossier s'il n'existe pas
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        // Protéger le dossier avec un .htaccess
        $htaccess = $this->backupDir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }

        // Récupérer les infos de connexion depuis la classe Database
        $reflector = new ReflectionClass(Database::class);
        $props = $reflector->getProperties(ReflectionProperty::IS_PRIVATE);
        foreach ($props as $prop) {
            $prop->setAccessible(true);
            $value = $prop->getValue($this->db);
            if ($prop->getName() === 'host') $this->host = $value;
            if ($prop->getName() === 'dbname') $this->dbname = $value;
            if ($prop->getName() === 'username') $this->username = $value;
            if ($prop->getName() === 'password') $this->password = $value;
        }
    }

    /**
     * Point d'entrée pour les requêtes AJAX
     */
    public function handleAjax(): void
    {
        if (!can('sauvegardes.consulter')) {
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
            case 'sauvegarder':
                $this->sauvegarder();
                break;
            case 'supprimer':
                $this->supprimer();
                break;
            case 'restaurer':
                $this->restaurer();
                break;
            default:
                jsonResponse(false, 'Action inconnue.', [], 400);
        }
    }

    // ============================================================
    // 1. LISTE DES SAUVEGARDES
    // ============================================================

    private function getListe(): void
    {
        $page    = max(1, (int) post('page', 1));
        $parPage = ITEMS_PER_PAGE;

        // Récupérer depuis la base de données
        $total = (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sauvegardes");

        $offset = ($page - 1) * $parPage;

        $sauvegardes = $this->db->fetchAll(
            "SELECT s.*, 
                    DATE_FORMAT(s.date_creation, '%d/%m/%Y') AS date_format,
                    TIME_FORMAT(s.date_creation, '%H:%i') AS heure_format,
                    CONCAT(u.prenom, ' ', u.nom) AS auteur_nom,
                    CASE 
                        WHEN s.taille_fichier > 1048576 THEN CONCAT(ROUND(s.taille_fichier / 1048576, 1), ' Mo')
                        WHEN s.taille_fichier > 1024 THEN CONCAT(ROUND(s.taille_fichier / 1024, 1), ' Ko')
                        ELSE CONCAT(s.taille_fichier, ' o')
                    END AS taille_formatee
             FROM sauvegardes s
             LEFT JOIN utilisateurs u ON s.cree_par = u.id
             ORDER BY s.date_creation DESC
             LIMIT :limit OFFSET :offset",
            ['limit' => $parPage, 'offset' => $offset]
        );

        // Vérifier que les fichiers existent encore sur le disque
        foreach ($sauvegardes as &$s) {
            $s['fichier_existe'] = file_exists($this->backupDir . $s['nom_fichier']);
        }

        jsonResponse(true, 'Liste récupérée.', [
            'sauvegardes' => $sauvegardes,
            'total'       => $total,
            'page'        => $page,
            'pages'       => ceil($total / $parPage)
        ]);
    }

    // ============================================================
    // 2. SAUVEGARDER LA BASE DE DONNÉES
    // ============================================================

    private function sauvegarder(): void
    {
        requirePermission('sauvegardes.creer');

        $userId = $_SESSION['utilisateur_id'];
        $debutExecution = microtime(true);

        try {
            // Nom du fichier avec horodatage
            $nomFichier = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $cheminFichier = $this->backupDir . $nomFichier;

            // Générer le dump SQL complet
            $sqlDump = $this->generateDump();

            // Écrire le fichier sur le disque
            if (file_put_contents($cheminFichier, $sqlDump) === false) {
                jsonResponse(false, 'Erreur lors de l\'écriture du fichier de sauvegarde.', [], 500);
            }

            // Calculer les métriques
            $tailleFichier = filesize($cheminFichier);
            $dureeExecution = round(microtime(true) - $debutExecution, 2);

            // Enregistrer dans la base de données
            $sauvegardeId = $this->db->insert(
                "INSERT INTO sauvegardes (nom_fichier, chemin_fichier, taille_fichier, type_sauvegarde, statut, cree_par, date_creation, duree_execution)
                 VALUES (:nom, :chemin, :taille, 'Manuelle', 'Succès', :uid, NOW(), :duree)",
                [
                    'nom'    => $nomFichier,
                    'chemin' => 'storage/backups/' . $nomFichier,
                    'taille' => $tailleFichier,
                    'uid'    => $userId,
                    'duree'  => $dureeExecution
                ]
            );

            // Journaliser l'action
            logActivity('Sauvegarde', 'sauvegardes', $sauvegardeId, "Sauvegarde créée : {$nomFichier} ({$tailleFichier} octets)");

            // Réponse
            $tailleFormatee = $tailleFichier > 1048576 
                ? round($tailleFichier / 1048576, 1) . ' Mo' 
                : round($tailleFichier / 1024, 1) . ' Ko';

            jsonResponse(true, 'Sauvegarde effectuée avec succès.', [
                'id'       => $sauvegardeId,
                'fichier'  => $nomFichier,
                'taille'   => $tailleFormatee,
                'duree'    => $dureeExecution . ' secondes'
            ]);

        } catch (Exception $e) {
            // Enregistrer l'échec dans la base
            try {
                $this->db->insert(
                    "INSERT INTO sauvegardes (nom_fichier, chemin_fichier, taille_fichier, type_sauvegarde, statut, message_erreur, cree_par, date_creation)
                     VALUES (:nom, :chemin, 0, 'Manuelle', 'Échec', :erreur, :uid, NOW())",
                    [
                        'nom'    => $nomFichier ?? 'backup_erreur.sql',
                        'chemin' => '',
                        'erreur' => $e->getMessage(),
                        'uid'    => $userId
                    ]
                );
            } catch (Exception $e2) {
                // Silencieux
            }

            error_log('Erreur sauvegarde BDD: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la sauvegarde : ' . $e->getMessage());
        }
    }

    // ============================================================
    // 3. SUPPRIMER UNE SAUVEGARDE
    // ============================================================

    private function supprimer(): void
    {
        requirePermission('sauvegardes.supprimer');

        $sauvegardeId = (int) post('sauvegarde_id', 0);
        if ($sauvegardeId <= 0) {
            jsonResponse(false, 'ID sauvegarde invalide.', [], 400);
        }

        // Récupérer les infos avant suppression
        $sauvegarde = $this->db->fetchOne(
            "SELECT id, nom_fichier, chemin_fichier FROM sauvegardes WHERE id = :id",
            ['id' => $sauvegardeId]
        );

        if (!$sauvegarde) {
            jsonResponse(false, 'Sauvegarde introuvable.', [], 404);
        }

        // Supprimer le fichier physique du disque
        $cheminFichier = $this->backupDir . $sauvegarde['nom_fichier'];
        if (file_exists($cheminFichier)) {
            if (!unlink($cheminFichier)) {
                jsonResponse(false, 'Impossible de supprimer le fichier physique.', [], 500);
            }
        }

        // Supprimer l'enregistrement en base
        $this->db->delete("DELETE FROM sauvegardes WHERE id = :id", ['id' => $sauvegardeId]);

        // Journaliser
        logActivity('Suppression', 'sauvegardes', $sauvegardeId, "Sauvegarde supprimée : {$sauvegarde['nom_fichier']}");

        jsonResponse(true, 'Sauvegarde supprimée avec succès.');
    }

    // ============================================================
    // 4. RESTAURER UNE SAUVEGARDE
    // ============================================================

    private function restaurer(): void
    {
        requirePermission('sauvegardes.restaurer');

        $sauvegardeId = (int) post('sauvegarde_id', 0);
        if ($sauvegardeId <= 0) {
            jsonResponse(false, 'ID sauvegarde invalide.', [], 400);
        }

        // Récupérer les infos de la sauvegarde à restaurer
        $sauvegarde = $this->db->fetchOne(
            "SELECT id, nom_fichier, chemin_fichier, taille_fichier FROM sauvegardes WHERE id = :id",
            ['id' => $sauvegardeId]
        );

        if (!$sauvegarde) {
            jsonResponse(false, 'Sauvegarde introuvable.', [], 404);
        }

        // Vérifier que le fichier existe
        $cheminFichier = $this->backupDir . $sauvegarde['nom_fichier'];
        if (!file_exists($cheminFichier)) {
            jsonResponse(false, 'Le fichier de sauvegarde n\'existe plus sur le serveur.', [], 404);
        }

        try {
            // === ÉTAPE 1 : Créer une sauvegarde de sécurité avant restauration ===
            $nomBackupSecurite = 'backup_avant_restauration_' . date('Y-m-d_H-i-s') . '.sql';
            $cheminBackupSecurite = $this->backupDir . $nomBackupSecurite;
            $sqlDumpSecurite = $this->generateDump();
            file_put_contents($cheminBackupSecurite, $sqlDumpSecurite);

            // Enregistrer la sauvegarde de sécurité
            $this->db->insert(
                "INSERT INTO sauvegardes (nom_fichier, chemin_fichier, taille_fichier, type_sauvegarde, statut, cree_par, date_creation)
                 VALUES (:nom, :chemin, :taille, 'Automatique', 'Succès', :uid, NOW())",
                [
                    'nom'    => $nomBackupSecurite,
                    'chemin' => 'storage/backups/' . $nomBackupSecurite,
                    'taille' => filesize($cheminBackupSecurite),
                    'uid'    => $_SESSION['utilisateur_id']
                ]
            );

            // === ÉTAPE 2 : Restaurer la sauvegarde sélectionnée ===
            $sql = file_get_contents($cheminFichier);

            if (empty(trim($sql))) {
                jsonResponse(false, 'Le fichier de sauvegarde est vide ou corrompu.', [], 500);
            }

            $this->executeSqlDump($sql);

            // Journaliser
            logActivity('Restauration', 'sauvegardes', $sauvegardeId, 
                "Base restaurée depuis {$sauvegarde['nom_fichier']}. Backup sécurité : {$nomBackupSecurite}");

            jsonResponse(true, 'Base de données restaurée avec succès. Une sauvegarde de sécurité a été créée.', [
                'backup_securite' => $nomBackupSecurite
            ]);

        } catch (Exception $e) {
            error_log('Erreur restauration BDD: ' . $e->getMessage());
            jsonResponse(false, 'Erreur lors de la restauration : ' . $e->getMessage());
        }
    }

    // ============================================================
    // 5. GÉNÉRER LE DUMP SQL COMPLET
    // ============================================================

    private function generateDump(): string
    {
        $sql = "-- ============================================================\n";
        $sql .= "-- Sauvegarde complète de la base de données\n";
        $sql .= "-- Base : {$this->dbname}\n";
        $sql .= "-- Date : " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Généré par : Gestion Scolaire v1.0\n";
        $sql .= "-- ============================================================\n\n";

        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $sql .= "SET AUTOCOMMIT = 0;\n";
        $sql .= "START TRANSACTION;\n\n";

        // Récupérer toutes les tables
        $tables = $this->db->fetchAll("SHOW TABLES");

        foreach ($tables as $table) {
            $tableName = current($table);

            // Commentaire
            $sql .= "-- --------------------------------------------------------\n";
            $sql .= "-- Table : `{$tableName}`\n";
            $sql .= "-- --------------------------------------------------------\n\n";

            // Structure de la table
            $createTable = $this->db->fetchAll("SHOW CREATE TABLE `{$tableName}`");
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $createTable[0]['Create Table'] . ";\n\n";

            // Données de la table
            $rows = $this->db->fetchAll("SELECT * FROM `{$tableName}`");

            if (!empty($rows)) {
                $sql .= "-- Données de `{$tableName}`\n";
                $sql .= "LOCK TABLES `{$tableName}` WRITE;\n";
                $sql .= "/*!40000 ALTER TABLE `{$tableName}` DISABLE KEYS */;\n";

                foreach ($rows as $row) {
                    $values = array_map(function($value) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return "'" . addslashes((string)$value) . "'";
                    }, $row);

                    $sql .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
                }

                $sql .= "/*!40000 ALTER TABLE `{$tableName}` ENABLE KEYS */;\n";
                $sql .= "UNLOCK TABLES;\n\n";
            }
        }

        $sql .= "COMMIT;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $sql;
    }

    // ============================================================
    // 6. EXÉCUTER UN DUMP SQL
    // ============================================================

    private function executeSqlDump(string $sql): void
    {
        // Désactiver les contraintes
        $this->db->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->query("SET AUTOCOMMIT = 0");

        // Séparer les requêtes
        $queries = explode(";\n", $sql);

        $this->db->beginTransaction();

        foreach ($queries as $query) {
            $query = trim($query);

            // Ignorer les commentaires et commandes système
            if (empty($query)) continue;
            if (str_starts_with($query, '--')) continue;
            if (str_starts_with($query, '/*')) continue;
            if (str_starts_with($query, 'SET FOREIGN_KEY_CHECKS')) continue;
            if (str_starts_with($query, 'SET SQL_MODE')) continue;
            if (str_starts_with($query, 'SET AUTOCOMMIT')) continue;
            if (str_starts_with($query, 'START TRANSACTION')) continue;
            if (str_starts_with($query, 'COMMIT')) continue;
            if (str_starts_with($query, 'LOCK TABLES')) continue;
            if (str_starts_with($query, 'UNLOCK TABLES')) continue;

            // Ne pas exécuter DROP TABLE ni CREATE TABLE (on garde la structure actuelle)
            if (str_starts_with($query, 'DROP TABLE')) continue;
            if (str_starts_with($query, 'CREATE TABLE')) continue;

            try {
                $this->db->query($query);
            } catch (Exception $e) {
                error_log('Erreur requête restauration: ' . $e->getMessage() . ' | SQL: ' . substr($query, 0, 300));
                // Continuer malgré les erreurs isolées
            }
        }

        $this->db->commit();

        // Réactiver les contraintes
        $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
        $this->db->query("SET AUTOCOMMIT = 1");
    }

    // ============================================================
    // 7. TÉLÉCHARGER UNE SAUVEGARDE
    // ============================================================

    public function telecharger(int $sauvegardeId): void
    {
        requirePermission('sauvegardes.consulter');

        // Récupérer les infos
        $sauvegarde = $this->db->fetchOne(
            "SELECT id, nom_fichier, chemin_fichier FROM sauvegardes WHERE id = :id",
            ['id' => $sauvegardeId]
        );

        if (!$sauvegarde) {
            setFlash('error', 'Sauvegarde introuvable.');
            redirect('/gestion_scolaire/sauvegardes');
        }

        // Vérifier l'existence du fichier
        $cheminFichier = $this->backupDir . $sauvegarde['nom_fichier'];

        if (!file_exists($cheminFichier)) {
            setFlash('error', 'Le fichier de sauvegarde n\'existe plus sur le serveur.');
            redirect('/gestion_scolaire/sauvegardes');
        }

        // Envoyer le fichier au navigateur
        $taille = filesize($cheminFichier);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $sauvegarde['nom_fichier'] . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $taille);
        header('Cache-Control: private, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: 0');

        // Envoyer par morceaux pour les gros fichiers
        $handle = fopen($cheminFichier, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 8192);
            ob_flush();
            flush();
        }
        fclose($handle);
        exit;
    }
}