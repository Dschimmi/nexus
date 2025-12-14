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
 * Read-Only Implementierung.
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

    /**
     * Aktualisiert das Passwort. (Nicht unterstützt in Env).
     * @inheritDoc
     */
    public function upgradePassword(User $user, string $newHash): void
    {
        // EnvUserRepository ist read-only.
        // Wir können das Passwort in der .env nicht zur Laufzeit ändern.
        // Silent ignore, da dies das erwartete Verhalten für diesen Provider ist.
    }

    /**
     * Speichert einen Benutzer. (Nicht unterstützt in Env).
     * @inheritDoc
     */
    public function save(User $user): void
    {
        // Read-Only: JIT Provisioning nicht möglich.
        // Methode bleibt leer, um Interface zu erfüllen.
    }
}