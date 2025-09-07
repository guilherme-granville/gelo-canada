<?php
/**
 * Classe de conexão com banco de dados
 * Suporta MySQL e SQLite
 */

require_once __DIR__ . '/../../config/config.php';

class Database {
    private static $instance = null;
    private $connection = null;
    private $dbType;
    
    private function __construct() {
        $this->dbType = DB_TYPE;
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            if ($this->dbType === 'sqlite') {
                // Criar diretório se não existir
                $dbDir = dirname(SQLITE_PATH);
                if (!is_dir($dbDir)) {
                    mkdir($dbDir, 0755, true);
                }
                
                $this->connection = new PDO('sqlite:' . SQLITE_PATH);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->connection->exec('PRAGMA foreign_keys = ON');
                $this->connection->exec('PRAGMA journal_mode = WAL');
                $this->connection->exec('PRAGMA synchronous = NORMAL');
                $this->connection->exec('PRAGMA cache_size = 10000');
            } else {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
                ]);
            }
        } catch (PDOException $e) {
            error_log("Erro de conexão com banco: " . $e->getMessage());
            throw new Exception("Erro de conexão com banco de dados: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function getDbType() {
        return $this->dbType;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Erro na query: " . $e->getMessage() . " SQL: " . $sql);
            throw new Exception("Erro na execução da query: " . $e->getMessage());
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function tableExists($tableName) {
        if ($this->dbType === 'sqlite') {
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
        } else {
            $sql = "SHOW TABLES LIKE ?";
        }
        
        $result = $this->fetchOne($sql, [$tableName]);
        return $result !== false;
    }
    
    public function getTableColumns($tableName) {
        if ($this->dbType === 'sqlite') {
            $sql = "PRAGMA table_info(" . $tableName . ")";
            $result = $this->fetchAll($sql);
            $columns = [];
            foreach ($result as $row) {
                $columns[] = $row['name'];
            }
            return $columns;
        } else {
            $sql = "DESCRIBE " . $tableName;
            $result = $this->fetchAll($sql);
            $columns = [];
            foreach ($result as $row) {
                $columns[] = $row['Field'];
            }
            return $columns;
        }
    }
    
    public function backup($filePath) {
        if ($this->dbType === 'sqlite') {
            return copy(SQLITE_PATH, $filePath);
        } else {
            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s',
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                $filePath
            );
            return system($command) !== false;
        }
    }
    
    public function restore($filePath) {
        if ($this->dbType === 'sqlite') {
            return copy($filePath, SQLITE_PATH);
        } else {
            $command = sprintf(
                'mysql -h %s -u %s -p%s %s < %s',
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                $filePath
            );
            return system($command) !== false;
        }
    }
    
    public function getDatabaseSize() {
        if ($this->dbType === 'sqlite') {
            return filesize(SQLITE_PATH);
        } else {
            $sql = "SELECT 
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                    FROM information_schema.tables 
                    WHERE table_schema = ?";
            $result = $this->fetchOne($sql, [DB_NAME]);
            return $result['size_mb'] ?? 0;
        }
    }
    
    public function optimize() {
        if ($this->dbType === 'sqlite') {
            $this->query('VACUUM');
            $this->query('ANALYZE');
        } else {
            $tables = $this->fetchAll("SHOW TABLES");
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                $this->query("OPTIMIZE TABLE " . $tableName);
            }
        }
    }
    
    public function close() {
        $this->connection = null;
    }
    
    public function __destruct() {
        $this->close();
    }
}
