<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Zentraler Wrapper für Datenbankzugriffe.
 * Erzwingt Prepared Statements und sichere Konfiguration.
 */
class DatabaseService
{
    private ?PDO $pdo = null;

    public function __construct(
        private ConfigService $config
    ) {}

    /**
     * Stellt die Verbindung her (Lazy Loading).
     */
    private function connect(): void
    {
        if ($this->pdo !== null) {
            return;
        }

        // Config laden (noch nicht in .env, aber vorbereitet)
        $dsn = $this->config->get('database.dsn');
        $user = $this->config->get('database.user');
        $pass = $this->config->get('database.password');

        if (!$dsn) {
            throw new RuntimeException('Database DSN not configured.');
        }

        try {
            $this->pdo = new PDO($dsn, $user, $pass);
            
            // SECURITY: Emulierte Prepares ausschalten (SQL Injection Schutz)
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Error Mode auf Exception setzen
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Standard-Fetch-Mode auf Array
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Passwort aus Trace entfernen!
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Führt ein Prepared Statement aus.
     * Einziger Weg, Queries zu senden (kein query() oder exec() erlaubt).
     * 
     * @param string $sql Das SQL-Statement mit Platzhaltern (:name oder ?).
     * @param array $params Die Parameter für das Statement.
     * @return array Das Ergebnis (fetchAll).
     */
    public function query(string $sql, array $params = []): array
    {
        $this->connect();
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        // Bei SELECT geben wir Daten zurück, sonst leeres Array
        if (stripos(trim($sql), 'SELECT') === 0) {
            return $stmt->fetchAll();
        }
        
        return [];
    }

    /**
     * Gibt die ID des zuletzt eingefügten Datensatzes zurück.
     */
    public function lastInsertId(): string
    {
        $this->connect();
        return $this->pdo->lastInsertId();
    }
}