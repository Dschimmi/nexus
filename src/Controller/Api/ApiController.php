<?php

declare(strict_types=1);

namespace MrWo\Nexus\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Basis-Controller für alle API-Endpunkte.
 * 
 * Stellt Hilfsmethoden bereit, um standardisierte JSON-Antworten
 * zu generieren (JSend-ähnliches Format oder einfaches JSON).
 * Dient als zentrale Stelle für API-spezifische Logik (z.B. Serializer-Integration).
 */
abstract class ApiController
{
    /**
     * Erstellt eine erfolgreiche JSON-Antwort.
     * 
     * @param mixed $data   Die Nutzdaten (werden zu JSON serialisiert).
     * @param int   $status HTTP-Statuscode (Standard: 200 OK).
     * @param array $headers Optionale HTTP-Header.
     * @return JsonResponse
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Erstellt eine JSON-Fehlermeldung.
     * 
     * @param string $message Die Fehlermeldung.
     * @param int    $status  HTTP-Statuscode (Standard: 400 Bad Request).
     * @return JsonResponse
     */
    protected function error(string $message, int $status = 400): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}