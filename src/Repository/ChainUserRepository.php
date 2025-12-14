<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

use MrWo\Nexus\Domain\User\User;
use MrWo\Nexus\Domain\User\UserRepositoryInterface;

/**
 * Delegiert die Benutzersuche an eine Liste von Providern (Chain of Responsibility).
 * Ermöglicht hybride Setups (z.B. Env-Admin + Datenbank-User).
 */
class ChainUserRepository implements UserRepositoryInterface
{
    /** @var UserRepositoryInterface[] */
    private array $providers = [];

    /**
     * @param iterable $providers Liste der Provider (tagged services).
     */
    public function __construct(iterable $providers)
    {
        // Iterator in Array umwandeln für mehrfache Durchläufe
        foreach ($providers as $provider) {
            if ($provider instanceof UserRepositoryInterface) {
                $this->providers[] = $provider;
            }
        }
    }

    public function findByIdentifier(string $identifier): ?User
    {
        foreach ($this->providers as $provider) {
            $user = $provider->findByIdentifier($identifier);
            if ($user !== null) {
                return $user; // Treffer! Kette beenden.
            }
        }
        return null;
    }

    public function upgradePassword(User $user, string $newHash): void
    {
        foreach ($this->providers as $provider) {
            // Wir versuchen das Update auf allen Providern.
            // Der zuständige Provider (z.B. DB) wird das Update durchführen.
            // Provider, die den User nicht kennen oder Read-Only sind, ignorieren es.
            $provider->upgradePassword($user, $newHash);
        }
    }
}