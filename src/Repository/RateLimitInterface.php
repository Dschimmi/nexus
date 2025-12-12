<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

/**
 * Definiert die Schnittstelle für die Speicherung und Abfrage von
 * Login-Limitierungsdaten (z.B. fehlgeschlagene Versuche, Sperrzeiten).
 */
interface RateLimitInterface
{
    /**
     * Zählt einen fehlgeschlagenen Login-Versuch für einen gegebenen Identifier.
     *
     * @param string $identifier Der Benutzername, die E-Mail oder die IP-Adresse.
     * @return void
     */
    public function recordAttempt(string $identifier): void;

    /**
     * Ruft die Anzahl der fehlgeschlagenen Versuche für einen Identifier innerhalb
     * eines definierten Zeitfensters ab.
     *
     * @param string $identifier Der Benutzername, die E-Mail oder die IP-Adresse.
     * @param int $windowInSeconds Das Zeitfenster, in dem gezählt wird (z.B. 3600 Sekunden).
     * @return int Die aktuelle Anzahl der Versuche.
     */
    public function getAttempts(string $identifier, int $windowInSeconds): int;

    /**
     * Speichert einen Sperr-Zeitstempel (Lockout) für einen Identifier.
     *
     * @param string $identifier Der Benutzername, die E-Mail oder die IP-Adresse.
     * @param int $lockoutTimeInSeconds Die Dauer der Sperrung in Sekunden.
     * @return void
     */
    public function setLockout(string $identifier, int $lockoutTimeInSeconds): void;

    /**
     * Ruft den Sperr-Zeitstempel (Timestamp) für einen Identifier ab.
     *
     * @param string $identifier Der Benutzername, die E-Mail oder die IP-Adresse.
     * @return int Der Timestamp der Sperr-Freigabe, oder 0, wenn nicht gesperrt.
     */
    public function getLockoutExpiry(string $identifier): int;
}