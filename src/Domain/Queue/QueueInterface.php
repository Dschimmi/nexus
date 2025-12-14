<?php

declare(strict_types=1);

namespace MrWo\Nexus\Domain\Queue;

/**
 * Schnittstelle für das Einreihen von Hintergrundaufgaben.
 * Ermöglicht asynchrone Verarbeitung (Entkopplung).
 */
interface QueueInterface
{
    /**
     * Reiht eine Nachricht/Job in die Warteschlange ein.
     * 
     * @param string $queueName Name der Queue (z.B. 'emails', 'pdf').
     * @param array  $payload   Daten für den Job.
     */
    public function push(string $queueName, array $payload): void;
}