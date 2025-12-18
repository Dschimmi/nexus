<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Session;

use MrWo\Nexus\Infrastructure\Session\SessionBag;
use PHPUnit\Framework\TestCase;

/**
 * Testet die SessionBag Klasse.
 * Stellt sicher, dass Daten korrekt gekapselt und manipuliert werden.
 */
class SessionBagTest extends TestCase
{
    private SessionBag $bag;

    protected function setUp(): void
    {
        $this->bag = new SessionBag('test_bag', ['initial' => 'value']);
    }

    /**
     * Prüft, ob der Name korrekt zurückgegeben wird.
     */
    public function testGetNameReturnsNamespace(): void
    {
        $this->assertEquals('test_bag', $this->bag->getName());
    }

    /**
     * Prüft Setzen und Abrufen von Werten.
     */
    public function testSetAndGet(): void
    {
        $this->bag->set('foo', 'bar');
        $this->assertEquals('bar', $this->bag->get('foo'));
        $this->assertEquals('value', $this->bag->get('initial')); // Initial data
        $this->assertNull($this->bag->get('missing')); // Default null
    }

    /**
     * Prüft die Add-Funktion für Arrays (Flash-Messages).
     */
    public function testAddAppendsToArray(): void
    {
        $this->bag->add('messages', 'msg1');
        $this->bag->add('messages', 'msg2');

        $result = $this->bag->get('messages');
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('msg1', $result[0]);
    }

    /**
     * Prüft das Löschen von Schlüsseln und das Leeren des Bags.
     */
    public function testRemoveAndClear(): void
    {
        $this->bag->remove('initial');
        $this->assertFalse($this->bag->has('initial'));

        $this->bag->set('new', 'val');
        $this->bag->clear();
        $this->assertEmpty($this->bag->all());
    }
}