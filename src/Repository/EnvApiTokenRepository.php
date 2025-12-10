<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

/**
 * Validiert Tokens gegen eine Umgebungsvariable.
 * Dient als einfacher Schutz für initiale Deployments.
 */
class EnvApiTokenRepository implements ApiTokenRepositoryInterface
{
    public function __construct(
        private string $masterToken
    ) {}

    public function isValid(string $token): bool
    {
        // Einfacher Vergleich gegen Master-Token
        return hash_equals($this->masterToken, $token);
    }
}