<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

/**
 * Zentraler Service für Authentifizierungsprozesse.
 * Prüft Zugangsdaten und verwaltet die Benutzer-Identität.
 * Nutzt den gehärteten SessionService mit Security-Bag.
 */
class AuthenticationService
{
    public function __construct(
        private SessionService $session,
        private string $adminUser,
        private string $adminEmail,
        private string $adminPassHash
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
        // 1. Identität prüfen (Legacy .env Check)
        // TODO: Später auf UserProviderInterface umstellen für DB-Auth
        $isCorrectUser = ($identifier === $this->adminUser || $identifier === $this->adminEmail);

        if ($isCorrectUser && password_verify($password, $this->adminPassHash)) {
            
            // SECURITY: Session-ID rotieren (Schutz vor Fixation)
            // Wir nutzen migrate(true), um die alte Session zu löschen.
            $this->session->migrate(true);

            // SECURITY: User in den isolierten Security-Bag speichern
            $this->session->getBag('security')->set('user', [
                'id'       => 'root',
                'username' => $this->adminUser,
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
        // 1. User-Daten löschen (Logout)
        $this->session->getBag('security')->clear();
        
        // 2. Session-ID wechseln (Sicherheit), aber Attribute (Sprache) behalten
        // migrate(true) löscht die ALTE Session-Datei, aber behält $_SESSION im RAM für die NEUE ID.
        $this->session->migrate(true);
    }

    /**
     * Gibt den aktuell eingeloggten Benutzer zurück oder null.
     */
    public function getUser(): ?array
    {
        // Daten aus dem Security-Bag lesen
        return $this->session->getBag('security')->get('user');
    }

    /**
     * Prüft, ob der aktuelle Benutzer ein System-Administrator ist.
     */
    public function isAdmin(): bool
    {
        $user = $this->getUser();
        return $user && $user['group'] === 'System' && $user['role'] === 'Administrator';
    }
}