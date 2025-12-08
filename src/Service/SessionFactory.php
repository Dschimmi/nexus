<?php

declare(strict_types=1);

namespace MrWo\Nexus\Service;

use SessionHandler;
use SessionHandlerInterface;
use RuntimeException;

/**
 * Factory zur Erstellung des konfigurierten Session-Handlers.
 * Ermöglicht den Wechsel zwischen Dateisystem, Redis und Datenbank.
 */
class SessionFactory
{
    /**
     * @param ConfigService $config Der Konfigurations-Service.
     */
    public function __construct(
        private ConfigService $config
    ) {}

    /**
     * Erstellt die Instanz des Session-Handlers basierend auf der Konfiguration.
     * 
     * @return SessionHandlerInterface
     * @throws RuntimeException Wenn der Handler-Typ unbekannt oder nicht implementiert ist.
     */
    public function createHandler(): SessionHandlerInterface
    {
        $type = $this->config->get('session.handler', 'native');

        switch ($type) {
            case 'native':
            case 'files':
                // Der Standard PHP-Handler (nutzt session.save_path aus php.ini)
                return new SessionHandler();

            case 'redis':
                // Platzhalter für Redis-Implementierung (Mantis 0000039)
                throw new RuntimeException("Redis Session Handler not yet implemented.");

            case 'database':
                // Platzhalter für DB-Implementierung (Mantis 0000039)
                throw new RuntimeException("Database Session Handler not yet implemented.");

            default:
                throw new RuntimeException("Unknown session handler type: $type");
        }
    }
}