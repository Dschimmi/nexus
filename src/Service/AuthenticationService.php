<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use MrWo\Nexus\Repository\UserRepositoryInterface;

/**
 * Zentraler Service für Authentifizierungsprozesse.
 * Prüft Zugangsdaten und verwaltet die Benutzer-Identität.
 * Entkoppelt von der Datenquelle durch UserRepositoryInterface.
 */
class AuthenticationService
{
    public function __construct(
        private SessionService $session,
        private UserRepositoryInterface $userRepository,
        private SecurityLogger $logger,
        private RateLimiter $rateLimiter,
    ) {}

    /**
     * Versucht, einen Benutzer zu authentifizieren.
     * 
     * @param string $identifier Benutzername oder E-Mail.
     * @param string $password Das Passwort.
     * @return bool True bei Erfolg.
     */
    public function login(string $identifier, string $password): bool
    {
        // 0. Rate Limiting Check (Ticket 34)
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        // Wenn die IP gesperrt ist, sofort abbrechen.
        if ($this->rateLimiter->isRateLimited($clientIp)) {
            $this->logger->log('auth_login_failure', [
                'reason' => 'rate_limit_ip', 
                'identifier' => $identifier
            ]);
            return false;
        }

        // 1. User über das Repository suchen (egal ob Env, DB oder LDAP)
        $user = $this->userRepository->findByIdentifier($identifier);

        // 2. Wenn User nicht gefunden -> Abbruch
        if (!$user) {
            $this->logger->log('auth_login_failure', [
                'reason' => 'user_not_found', 
                'identifier' => $identifier
            ]);

            // Fehlschlag auf IP und Identifier aufzeichnen
            $this->rateLimiter->recordFailedAttempt($clientIp);
            $this->rateLimiter->recordFailedAttempt($identifier);
            return false;
        }

        // 3. Passwort prüfen (gegen den Hash aus dem User-Objekt)
        if (password_verify($password, $user->getPasswordHash())) {
            
            // SECURITY: Session-ID rotieren
            $this->session->migrate(true);

            // SECURITY: User-Daten (ohne Passwort!) in Session speichern
            $this->session->getBag('security')->set('user', $user->toArray());

            $this->logger->log('auth_login_success', [
                'user_id' => $user->getId(),
                'username' => $user->getUsername()
            ]);

            return true;
        }

        $this->logger->log('auth_login_failure', [
            'reason' => 'invalid_password', 
            'identifier' => $identifier
        ]);

        // Fehlschlag auf IP und Identifier aufzeichnen
        $this->rateLimiter->recordFailedAttempt($clientIp);
        $this->rateLimiter->recordFailedAttempt($identifier);

        return false;
    }

    /**
     * Loggt den aktuellen Benutzer aus.
     */
    public function logout(): void
    {

        $user = $this->getUser();
        $username = $user['username'] ?? 'unknown';

        $this->logger->log('auth_logout', ['user' => $username]);

        // 1. User-Daten löschen
        $this->session->getBag('security')->clear();
        
        // 2. Session rotieren (Sicherheit)
        $this->session->migrate(true);
    }

    /**
     * Gibt den aktuell eingeloggten Benutzer zurück oder null.
     */
    public function getUser(): ?array
    {
        return $this->session->getBag('security')->get('user');
    }

    /**
     * Prüft, ob der aktuelle Benutzer ein System-Administrator ist.
     */
    public function isAdmin(): bool
    {
        $userData = $this->getUser();
        if (!$userData) {
            return false;
        }

        return ($userData['group'] ?? '') === 'System' && ($userData['role'] ?? '') === 'Administrator';
    }

    /**
     * Prüft, ob der User in der Session noch gültig ist (Anti-Replay).
     * Lädt den User frisch aus dem Repository und vergleicht die authVersion.
     * 
     * @return bool True, wenn Session valide ist.
     */
    public function validateSessionUser(): bool
    {
        $sessionUser = $this->getUser();
        if (!$sessionUser) {
            return false;
        }

        // Wir nutzen den Username als Identifier, da IDs bei EnvUser 'root' sind
        $freshUser = $this->userRepository->findByIdentifier($sessionUser['username']);

        if (!$freshUser) {
            // User existiert nicht mehr (gelöscht?)
            $this->logout();
            return false;
        }

        // Anti-Replay Check
        if ($freshUser->getAuthVersion() !== ((int) ($sessionUser['auth_version'] ?? 0))) {
            // Version mismatch! (Passwort geändert / Session kill)
            $this->logout();
            return false;
        }

        return true;
    }
}