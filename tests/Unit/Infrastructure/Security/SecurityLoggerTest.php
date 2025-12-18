<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Security;

use MrWo\Nexus\Infrastructure\Security\SecurityLogger;
use PHPUnit\Framework\TestCase;

/**
 * Testet den SecurityLogger.
 * 
 * Fokus liegt auf der korrekten Anonymisierung von IP-Adressen (DSGVO).
 * Der eigentliche Log-Aufruf (via Tracy) wird nur auf Ausführbarkeit geprüft.
 */
class SecurityLoggerTest extends TestCase
{
    private SecurityLogger $logger;

    /**
     * Initialisiert die Testumgebung.
     * Konfiguriert Tracy (den Logger), damit Debugger::log() ein Zielverzeichnis hat.
     */
    protected function setUp(): void
    {
        $this->logger = new SecurityLogger();
        
        // Temporäres Log-Verzeichnis erstellen
        $logDir = sys_get_temp_dir() . '/nexus_test_log_' . uniqid();
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        // Tracy aktivieren, damit der Logger schreiben kann.
        // Im Unit-Test nutzen wir DETECT, um CLI-Modus zu erkennen.
        \Tracy\Debugger::enable(\Tracy\Debugger::DETECT, $logDir);
    }

    /**
     * Prüft die Anonymisierung von IPv4-Adressen (/24).
     */
    public function testAnonymizeIpV4(): void
    {
        // 192.168.1.123 -> 192.168.1.0
        $this->assertEquals('192.168.1.0', $this->logger->anonymizeIp('192.168.1.123'));
        
        // Edge Case: Localhost
        $this->assertEquals('127.0.0.0', $this->logger->anonymizeIp('127.0.0.1'));
    }

    /**
     * Prüft die Anonymisierung von IPv6-Adressen (/64).
     */
    public function testAnonymizeIpV6(): void
    {
        $ip = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
        $anon = $this->logger->anonymizeIp($ip);
        
        // Start muss identisch sein (Prefix)
        $this->assertStringStartsWith('2001:db8:85a3::', $anon);
        // Ende muss sich unterscheiden
        $this->assertNotEquals($ip, $anon);
    }

    /**
     * Prüft, dass ungültige IPs unverändert zurückgegeben werden (Fallback).
     */
    public function testAnonymizeInvalidIp(): void
    {
        $this->assertEquals('not-an-ip', $this->logger->anonymizeIp('not-an-ip'));
    }

    /**
     * Smoke-Test für die Log-Methode.
     * Stellt sicher, dass der Code durchläuft (auch wenn Tracy nicht aktiv ist).
     * @runInSeparateProcess
     */
    public function testLogRunsWithoutError(): void
    {
        $this->expectNotToPerformAssertions();
        
        // Mocking $_SERVER für Kontext
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $_SERVER['HTTP_USER_AGENT'] = 'TestBot';

        $this->logger->log('test_event', ['foo' => 'bar']);
    }
}