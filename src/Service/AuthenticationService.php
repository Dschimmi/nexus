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
        private UserRepositoryInterface $userRepository
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
        // 1. User über das Repository suchen (egal ob Env, DB oder LDAP)
        $user = $this->userRepository->findByIdentifier($identifier);

        // 2. Wenn User nicht gefunden -> Abbruch
        if (!$user) {
            return false;
        }

        // 3. Passwort prüfen (gegen den Hash aus dem User-Objekt)
        if (password_verify($password, $user->getPasswordHash())) {
            
            // SECURITY: Session-ID rotieren
            $this->session->migrate(true);

            // SECURITY: User-Daten (ohne Passwort!) in Session speichern
            $this->session->getBag('security')->set('user', $user->toArray());

            return true;
        }

        return false;
    }

    /**
     * Loggt den aktuellen Benutzer aus.
     */
    public function logout(): void
    {
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
}