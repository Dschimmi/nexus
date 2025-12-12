<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

use MrWo\Nexus\Service\DatabaseService;

/**
 * Datenbank-Implementierung des RateLimitInterface.
 * Dient als Placeholder und soll später die Persistence über die Datenbank
 * (DatabaseService) regeln.
 */
class DatabaseRateLimit implements RateLimitInterface
{
    // HINWEIS: DatabaseService wird injiziert, um die Datenbank-Operationen durchzuführen.
    public function __construct(private DatabaseService $db)
    {
        // Hier würde die Tabelle 'rate_limits' geprüft/initialisiert werden.
    }

    public function recordAttempt(string $identifier): void
    {
        // TODO: Implementierung zur Datenbank-Speicherung des Versuchs.
    }

    public function getAttempts(string $identifier, int $windowInSeconds): int
    {
        // TODO: Implementierung zur Abfrage der Versuche aus der Datenbank.
        return 0;
    }

    public function setLockout(string $identifier, int $lockoutTimeInSeconds): void
    {
        // TODO: Implementierung zur Speicherung des Lockout-Timestamps.
    }

    public function getLockoutExpiry(string $identifier): int
    {
        // TODO: Implementierung zur Abfrage des Lockout-Timestamps.
        return 0;
    }
}