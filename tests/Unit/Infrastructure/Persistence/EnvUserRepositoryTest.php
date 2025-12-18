<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Infrastructure\Persistence;

use MrWo\Nexus\Domain\User\User;
use MrWo\Nexus\Infrastructure\Persistence\EnvUserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Testet das Environment-basierte User-Repository.
 * 
 * Validiert:
 * - Finden des Admins anhand der .env-Daten (Name/Email).
 * - Korrekte Rückgabe von null bei unbekannten Usern.
 * - Dass Schreiboperationen (save, upgrade) fehlerfrei ignoriert werden (No-Op).
 */
class EnvUserRepositoryTest extends TestCase
{
    private EnvUserRepository $repo;
    
    private string $user = 'admin';
    private string $email = 'admin@host.local';
    private string $hash = 'hash123';

    protected function setUp(): void
    {
        $this->repo = new EnvUserRepository($this->user, $this->email, $this->hash);
    }

    /**
     * Prüft, ob der Admin über den Benutzernamen gefunden wird.
     */
    public function testFindByIdentifierReturnsUserByName(): void
    {
        $user = $this->repo->findByIdentifier('admin');
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('root', $user->getId());
        $this->assertEquals($this->user, $user->getUsername());
    }

    /**
     * Prüft, ob der Admin über die E-Mail gefunden wird.
     */
    public function testFindByIdentifierReturnsUserByEmail(): void
    {
        $user = $this->repo->findByIdentifier('admin@host.local');
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->email, $user->getEmail());
    }

    /**
     * Prüft, ob null zurückgegeben wird, wenn der Identifier nicht passt.
     */
    public function testFindByIdentifierReturnsNullOnMismatch(): void
    {
        $this->assertNull($this->repo->findByIdentifier('hacker'));
        $this->assertNull($this->repo->findByIdentifier(''));
    }

    /**
     * Prüft, dass save() keine Exception wirft (Read-Only Implementierung).
     */
    public function testSaveIsNoOp(): void
    {
        $this->expectNotToPerformAssertions();
        
        $user = $this->createMock(User::class);
        $this->repo->save($user);
    }

    /**
     * Prüft, dass upgradePassword() keine Exception wirft (Read-Only).
     */
    public function testUpgradePasswordIsNoOp(): void
    {
        $this->expectNotToPerformAssertions();
        
        $user = $this->createMock(User::class);
        $this->repo->upgradePassword($user, 'newhash');
    }
}