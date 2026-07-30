<?php
/**
 * Fichier : src/config/database.php
 * Rôle    : Connexion PDO à la base de données MySQL
 * Pattern : Singleton
 */

defined('ACCESS') or die('Accès interdit');

class Database
{
    private string $host     = 'localhost';
    private string $dbname   = 'gestion_scolaire';
    private string $username = 'root';
    private string $password = '';
    private string $charset  = 'utf8mb4';

    private static ?Database $instance = null;
    private PDO $pdo;

    /**
     * Constructeur privé
     */
    private function __construct()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log('[' . date('Y-m-d H:i:s') . '] Erreur BDD : ' . $e->getMessage());
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Erreur de connexion à la base de données.'
            ]));
        }
    }

    private function __clone() {}

    /**
     * Retourne l'instance unique
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Exécute une requête préparée
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('[' . date('Y-m-d H:i:s') . '] Erreur SQL : ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Récupère toutes les lignes
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Récupère une seule ligne
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Récupère une seule valeur
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $result = $this->query($sql, $params)->fetchColumn();
        return $result !== false ? $result : null;
    }

    /**
     * Insère et retourne le dernier ID
     */
    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met à jour et retourne le nombre de lignes affectées
     */
    public function update(string $sql, array $params = []): int
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Supprime et retourne le nombre de lignes affectées
     */
    public function delete(string $sql, array $params = []): int
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Vérifie si un enregistrement existe
     */
    public function exists(string $sql, array $params = []): bool
    {
        return $this->fetchColumn($sql, $params) !== null;
    }

    /**
     * Compte le nombre d'enregistrements
     */
    public function count(string $table, string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM {$table}";
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        return (int) $this->fetchColumn($sql, $params);
    }

    /**
     * Démarre une transaction
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Valide une transaction
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Annule une transaction
     */
    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}