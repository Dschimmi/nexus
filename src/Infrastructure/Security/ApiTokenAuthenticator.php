<?php

declare(strict_types=1);

namespace MrWo\Nexus\Infrastructure\Security;

use MrWo\Nexus\Repository\ApiTokenRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service zur Validierung von API-Tokens.
 * 
 * Extrahiert das Bearer-Token aus dem Header und delegiert
 * die Prüfung an das Repository.
 */
class ApiTokenAuthenticator
{
    public function __construct(
        private ApiTokenRepositoryInterface $repository
    ) {}

    /**
     * Prüft, ob der Request ein gültiges Token enthält.
     */
    public function validate(Request $request): bool
    {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return false;
        }

        $token = substr($authHeader, 7);

        return $this->repository->isValid($token);
    }
}