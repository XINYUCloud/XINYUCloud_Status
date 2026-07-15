<?php
class Database {
    private static ?Database $instance = null;
    private PDO $pdo;
    private function __construct() {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::MYSQL_ATTR_INIT_COMMAND=>"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"]);
    }
    public static function getInstance(): self { if(self::$instance===null)self::$instance=new self(); return self::$instance; }
    public function getPdo(): PDO { return $this->pdo; }
    public function query(string $sql, array $params=[]): PDOStatement { $stmt=$this->pdo->prepare($sql); $stmt->execute($params); return $stmt; }
    public function fetchAll(string $sql, array $params=[]): array { return $this->query($sql,$params)->fetchAll(); }
    public function fetch(string $sql, array $params=[]): ?array { return $this->query($sql,$params)->fetch()?:null; }
    public function fetchColumn(string $sql, array $params=[], int $col=0): mixed { return $this->query($sql,$params)->fetchColumn($col); }
    public function insert(string $table, array $data): int { $cols=implode(', ',array_keys($data)); $ph=implode(', ',array_fill(0,count($data),'?')); $this->query("INSERT INTO `{$table}` ({$cols}) VALUES ({$ph})",array_values($data)); return (int)$this->pdo->lastInsertId(); }
    public function update(string $table, array $data, string $where, array $wp=[]): int { $sets=implode(', ',array_map(fn($c)=>"`{$c}` = ?",array_keys($data))); return $this->query("UPDATE `{$table}` SET {$sets} WHERE {$where}",array_merge(array_values($data),$wp))->rowCount(); }
    public function delete(string $table, string $where, array $params=[]): int { return $this->query("DELETE FROM `{$table}` WHERE {$where}",$params)->rowCount(); }
    public function exists(string $table, string $where, array $params=[]): bool { return (bool)$this->query("SELECT 1 FROM `{$table}` WHERE {$where} LIMIT 1",$params)->fetch(); }
    public function count(string $table, string $where='1', array $params=[]): int { return (int)$this->fetchColumn("SELECT COUNT(*) FROM `{$table}` WHERE {$where}",$params); }
    public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
    public function commit(): bool { return $this->pdo->commit(); }
    public function rollback(): bool { return $this->pdo->rollBack(); }
    private function __clone() {}
}