<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

/**
 * Schnittstelle für den Zugriff auf API-Tokens.
 * Ermöglicht den Austausch der Token-Quelle (Env, DB, Redis).
 */
interface ApiTokenRepositoryInterface
{
    /**
     * Prüft, ob ein gegebenes Token existiert und gültig ist.
     * 
     * @param string $token Das zu prüfende Token (Raw String).
     * @return bool True, wenn das Token gültig ist.
     */
    public function isValid(string $token): bool;
}