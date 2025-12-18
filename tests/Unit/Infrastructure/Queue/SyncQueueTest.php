<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Queue;

use MrWo\Nexus\Infrastructure\Queue\SyncQueue;
use PHPUnit\Framework\TestCase;

/**
 * Testet den synchronen Queue-Adapter.
 * Dient primär als Smoke-Test, da die Implementierung aktuell ein No-Op ist.
 */
class SyncQueueTest extends TestCase
{
    /**
     * Testet, ob die push-Methode fehlerfrei durchläuft.
     * Da die SyncQueue synchron arbeitet (und aktuell ein No-Op ist),
     * erwarten wir keine Rückgabewerte oder Seiteneffekte, nur das Fehlen von Exceptions.
     */
    public function testPushExecutesWithoutError(): void
    {
        $this->expectNotToPerformAssertions();
        
        $queue = new SyncQueue();
        $queue->push('test_queue', ['data' => 123]);
    }
}