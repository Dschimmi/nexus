<?php

declare(strict_types=1);

namespace MrWo\Nexus\Application\Auth;

use MrWo\Nexus\Domain\User\UserRepositoryInterface;
use MrWo\Nexus\Infrastructure\Security\RateLimiter;
use MrWo\Nexus\Infrastructure\Security\SecurityLogger;
use MrWo\Nexus\Infrastructure\Session\SessionService;

/**
 * Zentraler Service für Authentifizierungsprozesse (Application Service).
 * 
 * Orchestriert den Login-Vorgang unter Verwendung von Repositories (Domain)
 * und Infrastruktur-Diensten (Session, Logger, RateLimiter).
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
        // 0. Rate Limiting Check
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        if ($this->rateLimiter->isRateLimited($clientIp)) {
            $this->logger->log('auth_login_failure', [
                'reason' => 'rate_limit_ip', 
                'identifier' => $identifier
            ]);
            return false;
        }

        // 1. User über das Repository suchen
        $user = $this->userRepository->findByIdentifier($identifier);

        // 2. Wenn User nicht gefunden -> Abbruch
        if (!$user) {
            $this->logger->log('auth_login_failure', [
                'reason' => 'user_not_found', 
                'identifier' => $identifier
            ]);

            $this->rateLimiter->recordFailedAttempt($clientIp);
            $this->rateLimiter->recordFailedAttempt($identifier);
            return false;
        }

        // 3. Passwort prüfen
        if (password_verify($password, $user->getPasswordHash())) {
            
            // SECURITY: Session-ID rotieren
            $this->session->migrate(true);

            // SECURITY: User-Daten in Session speichern
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

        $this->session->getBag('security')->clear();
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
     * @return bool True, wenn Session valide ist.
     */
    public function validateSessionUser(): bool
    {
        $sessionUser = $this->getUser();
        if (!$sessionUser) {
            return false;
        }

        $freshUser = $this->userRepository->findByIdentifier($sessionUser['username']);

        if (!$freshUser) {
            $this->logout();
            return false;
        }

        if ($freshUser->getAuthVersion() !== ((int) ($sessionUser['auth_version'] ?? 0))) {
            $this->logout();
            return false;
        }

        return true;
    }
}