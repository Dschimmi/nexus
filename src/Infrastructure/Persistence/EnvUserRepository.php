<?php

declare(strict_types=1);

namespace MrWo\Nexus\Infrastructure\Persistence;

use MrWo\Nexus\Domain\User\User;
use MrWo\Nexus\Domain\User\UserRepositoryInterface;

/**
 * Implementierung des UserRepository basierend auf Umgebungsvariablen.
 * 
 * Teil der Infrastructure-Schicht (Adapter).
 * Implementiert den Port (Interface) aus der Domain-Schicht.
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
                'Administrator',    // Feste Rolle
                1                   // Feste Auth-Version
            );
        }

        return null;
    }
}