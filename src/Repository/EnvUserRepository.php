<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

use MrWo\Nexus\Entity\User;

/**
 * Implementierung des UserRepository basierend auf Umgebungsvariablen.
 * 
 * Dient als Fallback-Provider für den initialen Admin-Zugang, solange
 * noch keine Datenbank oder externe Benutzerverwaltung konfiguriert ist.
 * Liest ADMIN_USER, ADMIN_EMAIL und ADMIN_PASSWORD_HASH aus der Umgebung.
 */
class EnvUserRepository implements UserRepositoryInterface
{
    /**
     * @param string $adminUser     Der Admin-Benutzername aus der .env.
     * @param string $adminEmail    Die Admin-E-Mail aus der .env.
     * @param string $adminPassHash Der Passwort-Hash aus der .env.
     */
    public function __construct(
        private string $adminUser,
        private string $adminEmail,
        private string $adminPassHash
    ) {}

    /**
     * Sucht den Admin-Benutzer anhand von Name oder E-Mail.
     * 
     * @param string $identifier Benutzername oder E-Mail.
     * @return User|null Das User-Objekt, wenn der Identifier passt, sonst null.
     */
    public function findByIdentifier(string $identifier): ?User
    {
        // Prüfen, ob der Identifier mit dem konfigurierten Admin übereinstimmt
        if ($identifier === $this->adminUser || $identifier === $this->adminEmail) {
            return new User(
                'root',             // Statische ID für den Root-Admin
                $this->adminUser,
                $this->adminEmail,
                $this->adminPassHash,
                'System',           // Feste Gruppe
                'Administrator',     // Feste Rolle
                1             // Feste Auth-Version (Anti-Replay)
            );
        }

        return null;
    }
}