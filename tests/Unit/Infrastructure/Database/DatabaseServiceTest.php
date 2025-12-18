<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Database;

use MrWo\Nexus\Infrastructure\Config\ConfigService;
use MrWo\Nexus\Infrastructure\Database\DatabaseService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Testet den DatabaseService.
 * Nutzt eine echte SQLite In-Memory Datenbank, um PDO-Interaktionen realistisch zu testen,
 * ohne eine externe MySQL-Datenbank zu benötigen.
 */
class DatabaseServiceTest extends TestCase
{
    private $configMock;
    private $dbService;

    /**
     * Initialisiert die Testumgebung.
     * Erstellt eine SQLite In-Memory Verbindung und legt eine Test-Tabelle an.
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(ConfigService::class);
        
        // Konfiguration simulieren: Wir nutzen SQLite im RAM
        $this->configMock->method('get')->willReturnMap([
            ['database.dsn', null, 'sqlite::memory:'],
            ['database.user', null, null],
            ['database.password', null, null]
        ]);

        $this->dbService = new DatabaseService($this->configMock);
        
        // Schema erstellen
        $this->dbService->query('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
    }

    /**
     * Prüft, ob Prepared Statements (INSERT und SELECT) korrekt ausgeführt werden.
     */
    public function testQueryExecutesAndReturnsResults(): void
    {
        // Act: Daten einfügen
        $this->dbService->query('INSERT INTO test (name) VALUES (?)', ['Nexus']);
        
        // Act: Daten lesen
        $results = $this->dbService->query('SELECT * FROM test');
        
        // Assert
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Nexus', $results[0]['name']);
    }

    /**
     * Prüft, ob lastInsertId() die ID des neuen Datensatzes zurückgibt.
     */
    public function testLastInsertIdReturnsId(): void
    {
        $this->dbService->query('INSERT INTO test (name) VALUES (?)', ['Item 1']);
        $id = $this->dbService->lastInsertId();
        
        // SQLite beginnt bei 1
        $this->assertEquals('1', $id);
    }

    /**
     * Prüft, ob eine Exception geworfen wird, wenn der DSN in der Config fehlt.
     */
    public function testConnectThrowsExceptionIfDsnMissing(): void
    {
        $config = $this->createMock(ConfigService::class);
        $config->method('get')->willReturn(null); // Simuliert fehlende Config

        $service = new DatabaseService($config);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database DSN not configured');
        
        // Trigger connection
        $service->query('SELECT 1');
    }
}