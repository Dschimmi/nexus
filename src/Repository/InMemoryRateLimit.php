<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

use MrWo\Nexus\Repository\RateLimitInterface;

/**
 * In-Memory Implementierung des RateLimitInterface.
 * Achtung: Speichert Daten nur für die Dauer eines Requests (statische Variable). 
 * Dient als Fallback und für isolierte Tests.
 */
class InMemoryRateLimit implements RateLimitInterface
{
    /**
     * @var array Speichert Zähler und Lockouts: ['identifier' => ['attempts' => [], 'lockout_expiry' => 0]]
     */
    private static array $store = [];

    /**
     * Zählt einen fehlgeschlagenen Login-Versuch für einen gegebenen Identifier.
     */
    public function recordAttempt(string $identifier): void
    {
        if (!isset(self::$store[$identifier])) {
            self::$store[$identifier] = ['attempts' => [], 'lockout_expiry' => 0];
        }

        self::$store[$identifier]['attempts'][] = time();
    }

    /**
     * Ruft die Anzahl der fehlgeschlagenen Versuche für einen Identifier innerhalb
     * eines definierten Zeitfensters ab.
     */
    public function getAttempts(string $identifier, int $windowInSeconds): int
    {
        if (!isset(self::$store[$identifier])) {
            return 0;
        }

        $minTimestamp = time() - $windowInSeconds;
        
        // Versuche filtern, die außerhalb des Fensters liegen
        self::$store[$identifier]['attempts'] = array_filter(
            self::$store[$identifier]['attempts'],
            fn(int $timestamp) => $timestamp >= $minTimestamp
        );

        return count(self::$store[$identifier]['attempts']);
    }

    /**
     * Speichert einen Sperr-Zeitstempel (Lockout) für einen Identifier.
     */
    public function setLockout(string $identifier, int $lockoutTimeInSeconds): void
    {
        if (!isset(self::$store[$identifier])) {
            self::$store[$identifier] = ['attempts' => [], 'lockout_expiry' => 0];
        }
        
        self::$store[$identifier]['lockout_expiry'] = time() + $lockoutTimeInSeconds;
    }

    /**
     * Ruft den Sperr-Zeitstempel (Timestamp) für einen Identifier ab.
     */
    public function getLockoutExpiry(string $identifier): int
    {
        return self::$store[$identifier]['lockout_expiry'] ?? 0;
    }
}