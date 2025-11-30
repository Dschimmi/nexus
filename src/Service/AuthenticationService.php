<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

/**
 * Zentraler Service für Authentifizierungsprozesse.
 * Prüft Zugangsdaten (Username/Email + Passwort) und verwaltet die Benutzer-Identität.
 */
class AuthenticationService
{
    private SessionService $session;
    private string $adminUser;
    private string $adminEmail;
    private string $adminPassHash;

    /**
     * @param SessionService $session Der Session-Service.
     * @param string $adminUser Der Admin-Benutzername (.env).
     * @param string $adminEmail Die Admin-Email (.env).
     * @param string $adminPassHash Der Hash des Admin-Passworts (.env).
     */
    public function __construct(
        SessionService $session,
        string $adminUser,
        string $adminEmail,
        string $adminPassHash
    ) {
        $this->session = $session;
        $this->adminUser = $adminUser;
        $this->adminEmail = $adminEmail;
        $this->adminPassHash = $adminPassHash;
    }

    /**
     * Versucht, einen Benutzer zu authentifizieren.
     * Akzeptiert Benutzername ODER E-Mail-Adresse als Identifikator.
     * 
     * @param string $identifier Benutzername oder E-Mail.
     * @param string $password Das Passwort.
     * @return bool True bei Erfolg, sonst False.
     */
    public function login(string $identifier, string $password): bool
    {
        // 1. Identität prüfen (Username ODER Email)
        $isCorrectUser = ($identifier === $this->adminUser || $identifier === $this->adminEmail);

        // 2. Passwort prüfen (nur wenn User/Email stimmt, um Timing-Attacks minimal zu erschweren, 
        // wobei verify selbst Zeit braucht, aber wir sparen uns den verify call bei falschem User nicht, 
        // da wir hier keinen DB-Lookup haben. Bei File-Based ist das ok.)
        if ($isCorrectUser && password_verify($password, $this->adminPassHash)) {
            
            // Sicherheits-Feature: Session-ID IMMER regenerieren bei Login
            $this->session->regenerate();

            // Benutzerdaten speichern (Immer den kanonischen Username verwenden)
            $this->session->set('user', [
                'id'       => 'root',
                'username' => $this->adminUser, // Einheitlicher Username in Session
                'email'    => $this->adminEmail,
                'group'    => 'System',
                'role'     => 'Administrator'
            ]);

            return true;
        }

        return false;
    }

    /**
     * Loggt den aktuellen Benutzer aus.
     */
    public function logout(): void
    {
        $this->session->remove('user');
        $this->session->regenerate();
    }

    /**
     * Gibt den aktuell eingeloggten Benutzer zurück oder null.
     */
    public function getUser(): ?array
    {
        return $this->session->get('user');
    }

    /**
     * Prüft, ob der aktuelle Benutzer ein System-Administrator ist.
     */
    public function isAdmin(): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        return $user['group'] === 'System' && $user['role'] === 'Administrator';
    }
}