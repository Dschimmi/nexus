<?php

declare(strict_types=1);

namespace MrWo\Nexus\Controller\Api\V1;

use MrWo\Nexus\Controller\Api\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Status-Endpunkt für die API Version 1.
 * 
 * Dient als Health-Check und Beispiel für die API-Architektur.
 * Gibt den Systemstatus und die API-Version zurück.
 */
class StatusController extends ApiController
{
    /**
     * Prüft die Verfügbarkeit der API.
     * 
     * @return JsonResponse JSON-Objekt mit Status und Version.
     */
    public function ping(): JsonResponse
    {
        return $this->json([
            'status' => 'operational',
            'api_version' => 'v1',
            'timestamp' => time()
        ]);
    }
}