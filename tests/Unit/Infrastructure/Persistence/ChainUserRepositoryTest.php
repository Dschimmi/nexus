<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Persistence;

use MrWo\Nexus\Domain\User\User;
use MrWo\Nexus\Domain\User\UserRepositoryInterface;
use MrWo\Nexus\Infrastructure\Persistence\ChainUserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Testet die Provider-Chain (Chain of Responsibility).
 * 
 * Validiert:
 * - Delegation: findByIdentifier fragt Provider der Reihe nach.
 * - Abbruch: Sobald ein Provider liefert, wird nicht weiter gesucht.
 * - Broadcast: Schreiboperationen (save, upgrade) gehen an ALLE Provider.
 */
class ChainUserRepositoryTest extends TestCase
{
    /**
     * Prüft, ob die Suche beim ersten Treffer stoppt.
     */
    public function testFindByIdentifierStopsAtFirstMatch(): void
    {
        $user = $this->createMock(User::class);

        // Provider 1: Findet den User
        $provider1 = $this->createMock(UserRepositoryInterface::class);
        $provider1->expects($this->once())
            ->method('findByIdentifier')
            ->with('admin')
            ->willReturn($user);

        // Provider 2: Sollte NICHT aufgerufen werden
        $provider2 = $this->createMock(UserRepositoryInterface::class);
        $provider2->expects($this->never())->method('findByIdentifier');

        $chain = new ChainUserRepository([$provider1, $provider2]);
        
        $result = $chain->findByIdentifier('admin');
        $this->assertSame($user, $result);
    }

    /**
     * Prüft, ob null zurückkommt, wenn kein Provider den User findet.
     */
    public function testFindByIdentifierReturnsNullIfNotFound(): void
    {
        $provider1 = $this->createMock(UserRepositoryInterface::class);
        $provider1->method('findByIdentifier')->willReturn(null);

        $chain = new ChainUserRepository([$provider1]);
        
        $this->assertNull($chain->findByIdentifier('unknown'));
    }

    /**
     * Prüft, ob save() an alle Provider weitergeleitet wird (JIT Support).
     */
    public function testSaveDelegatesToAllProviders(): void
    {
        $user = $this->createMock(User::class);

        $provider1 = $this->createMock(UserRepositoryInterface::class);
        $provider1->expects($this->once())->method('save')->with($user);

        $provider2 = $this->createMock(UserRepositoryInterface::class);
        $provider2->expects($this->once())->method('save')->with($user);

        $chain = new ChainUserRepository([$provider1, $provider2]);
        $chain->save($user);
    }

    /**
     * Prüft, ob upgradePassword() an alle Provider weitergeleitet wird.
     */
    public function testUpgradePasswordDelegatesToAllProviders(): void
    {
        $user = $this->createMock(User::class);
        $newHash = 'hash';

        $provider1 = $this->createMock(UserRepositoryInterface::class);
        $provider1->expects($this->once())->method('upgradePassword')->with($user, $newHash);

        $chain = new ChainUserRepository([$provider1]);
        $chain->upgradePassword($user, $newHash);
    }
}