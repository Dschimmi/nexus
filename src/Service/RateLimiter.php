<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use MrWo\Nexus\Repository\RateLimitInterface;
use Throwable;

/**
 * Service zur Implementierung von Rate Limiting, insbesondere für
 * fehlgeschlagene Anmeldeversuche (Brute-Force-Schutz).
 */
class RateLimiter
{
    /**
     * @param ConfigService $config Der Konfigurations-Service für die Grenzwerte.
     * @param RateLimitInterface $repository Das Repository für die Speicherung der Zähler.
     * @param SecurityLogger $logger Der Logger für die Protokollierung von Sperrungen.
     */
    public function __construct(
        private ConfigService $config,
        private RateLimitInterface $repository,
        private SecurityLogger $logger
    ) {}

    /**
     * Prüft, ob der gegebene Identifier (Username, E-Mail oder IP)
     * aktuell von einer Sperre betroffen ist.
     *
     * @param string $identifier Benutzername, E-Mail oder anonymisierte IP-Adresse.
     * @return bool True, wenn der Identifier gesperrt ist.
     */
    public function isRateLimited(string $identifier): bool
    {
        try {
            $expiry = $this->repository->getLockoutExpiry($identifier);
            $now = time();

            if ($expiry > $now) {
                // Protokollierung bei noch aktiver Sperre
                $this->logger->log('rate_limit_active', [
                    'identifier' => $identifier,
                    'expiry' => $expiry
                ]);
                return true;
            }

            // Falls die Sperre abgelaufen ist, den Eintrag löschen (optional, hängt vom Repo ab)
            // oder einfach ignorieren. Hier ignorieren wir, da getLockoutExpiry 0 zurückgibt.
            return false;
        } catch (Throwable $e) {
            // Fällt der Repositor-Zugriff aus, erlauben wir den Login, 
            // um einen Denial of Service (DoS) durch Datenbank-Fehler zu vermeiden.
            $this->logger->log('rate_limit_error', [
                'identifier' => $identifier,
                'message' => 'Repository access failed: ' . $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Prüft, ob die maximale Anzahl fehlgeschlagener Versuche erreicht wurde
     * und verhängt ggf. eine Sperre (Lockout).
     *
     * @param string $identifier Benutzername, E-Mail oder anonymisierte IP-Adresse.
     * @return void
     */
    public function checkAndLockout(string $identifier): void
    {
        $maxAttempts = $this->config->get('security.login_max_attempts');
        $lockoutTime = $this->config->get('security.login_lockout_time');
        $windowTime = $this->config->get('security.login_attempt_window');

        // 1. Aktuelle Versuche innerhalb des Fensters abrufen
        $attempts = $this->repository->getAttempts($identifier, $windowTime);

        // 2. Prüfung: Versuchslimit erreicht?
        if ($attempts >= $maxAttempts) {
            // 3. Sperre verhängen
            $this->repository->setLockout($identifier, $lockoutTime);
            
            $this->logger->log('rate_limit_lockout', [
                'identifier' => $identifier,
                'attempts' => $attempts,
                'lockout_time' => $lockoutTime
            ]);
        }
    }

    /**
     * Zeichnet einen fehlgeschlagenen Versuch auf und führt ggf. die Sperrprüfung durch.
     * Diese Methode wird vom AuthenticationService nach einem fehlgeschlagenen Login aufgerufen.
     *
     * @param string $identifier
     * @return void
     */
    public function recordFailedAttempt(string $identifier): void
    {
        $this->repository->recordAttempt($identifier);
        $this->checkAndLockout($identifier);
    }
}