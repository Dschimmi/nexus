<?php

declare(strict_types=1);

namespace MrWo\Nexus\Infrastructure\Queue;

use MrWo\Nexus\Domain\Queue\QueueInterface;

/**
 * Synchroner Queue-Adapter (Dev/Test).
 * Führt Jobs sofort aus oder loggt sie nur (je nach Strategie).
 */
class SyncQueue implements QueueInterface
{
    public function push(string $queueName, array $payload): void
    {
        // In v0.8.15 loggen wir nur, dass ein Job käme.
        // Später könnte hier ein Event-Dispatcher feuern.
        // file_put_contents(..., json_encode($payload)); 
    }
}