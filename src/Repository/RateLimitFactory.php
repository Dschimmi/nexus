<?php

declare(strict_types=1);

namespace MrWo\Nexus\Repository;

use MrWo\Nexus\Infrastructure\Config\ConfigService;
use Psr\Container\ContainerInterface;

/**
 * Factory zur dynamischen Auswahl und Erstellung der RateLimitInterface-Implementierung.
 * * Diese Factory dient als 'Weiche' (Switch) im Dependency Injection Container, um zur Laufzeit
 * basierend auf der Konfiguration (DB_DSN) zu entscheiden, welche konkrete
 * Repository-Implementierung (In-Memory oder Datenbank) verwendet werden soll.
 * * Hinweis: Die explizite Factory-Klasse wird benötigt, um PHPStan-Fehler zu vermeiden
 * und um das Inlining der Repositories durch den DI-Compiler zu verhindern.
 */
class RateLimitFactory
{
    /**
     * Erstellt entweder das InMemoryRateLimit oder das DatabaseRateLimit Repository.
     *
     * @param ContainerInterface $c Der DI Container, wird von Symfony injiziert ('service_container').
     * @return RateLimitInterface Die gewählte Rate Limit Implementierung.
     */
    public static function createRateLimit(ContainerInterface $c): RateLimitInterface
    {
        // Der ConfigService wird über seinen registrierten Alias aus dem Container abgerufen.
        /** @var ConfigService $config */
        $config = $c->get('config_service');

        // 1. Prüfen der Konfiguration: Ist eine Datenbank-DSN gesetzt?
        if ($config->get('database.dsn')) {
            // Wenn die Datenbank konfiguriert ist, verwenden wir die persistente Version.
            // Wir verwenden die explizit definierte Service-ID, um das Inlining durch den DI-Compiler zu verhindern.
            return $c->get('rate_limit.database'); 
        }
        
        // 2. Fallback: Wenn keine Datenbank konfiguriert ist, verwenden wir die In-Memory-Version.
        // Auch hier verwenden wir die explizite Service-ID.
        return $c->get('rate_limit.in_memory'); 
    }
}