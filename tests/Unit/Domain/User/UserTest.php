<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Unit\Domain\User;

use MrWo\Nexus\Domain\User\User;
use PHPUnit\Framework\TestCase;

/**
 * Testet die User-Entität (Domain-Objekt).
 * 
 * Validiert:
 * - Unveränderlichkeit der Daten (Getter).
 * - Korrekte Serialisierung für die Session (toArray).
 * - Korrekte Wiederherstellung aus der Session (fromArray).
 * - Sicherheitsaspekt: Passwort-Hash darf nicht im Array landen.
 */
class UserTest extends TestCase
{
    private User $user;
    
    // Testdaten
    private string $id = 'uuid-123';
    private string $username = 'tester';
    private string $email = 'test@example.com';
    private string $hash = '$argon2id$...';
    private string $group = 'Admins';
    private string $role = 'SuperAdmin';
    private int $version = 5;

    protected function setUp(): void
    {
        $this->user = new User(
            $this->id,
            $this->username,
            $this->email,
            $this->hash,
            $this->group,
            $this->role,
            $this->version
        );
    }

    /**
     * Prüft, ob die Getter exakt die Werte zurückgeben, die im Konstruktor übergeben wurden.
     */
    public function testGettersReturnCorrectValues(): void
    {
        $this->assertEquals($this->id, $this->user->getId());
        $this->assertEquals($this->username, $this->user->getUsername());
        $this->assertEquals($this->email, $this->user->getEmail());
        $this->assertEquals($this->hash, $this->user->getPasswordHash());
        $this->assertEquals($this->group, $this->user->getGroup());
        $this->assertEquals($this->role, $this->user->getRole());
        $this->assertEquals($this->version, $this->user->getAuthVersion());
    }

    /**
     * Prüft die Umwandlung in ein Array (für die Session).
     * Wichtig: Der Passwort-Hash darf hier NICHT enthalten sein.
     */
    public function testToArraySerializesCorrectly(): void
    {
        $array = $this->user->toArray();

        $this->assertEquals($this->id, $array['id']);
        $this->assertEquals($this->username, $array['username']);
        $this->assertEquals($this->version, $array['auth_version']);
        
        // SECURITY: Hash darf nicht in der Session landen
        $this->assertArrayNotHasKey('passwordHash', $array);
        $this->assertArrayNotHasKey('password', $array);
    }

    /**
     * Prüft die Wiederherstellung eines Objekts aus einem Array.
     * Der Hash ist dabei leer (da er nicht in der Session war).
     */
    public function testFromArrayDeserializesCorrectly(): void
    {
        $data = [
            'id' => '999',
            'username' => 'importer',
            'email' => 'import@test.com',
            'group' => 'Users',
            'role' => 'User',
            'auth_version' => 10
        ];

        $user = User::fromArray($data);

        $this->assertEquals('999', $user->getId());
        $this->assertEquals('importer', $user->getUsername());
        $this->assertEquals(10, $user->getAuthVersion());
        
        // Hash muss leer sein
        $this->assertEmpty($user->getPasswordHash());
    }

    /**
     * Prüft, ob Default-Werte genutzt werden, wenn das Array unvollständig ist.
     */
    public function testFromArrayHandlesMissingKeysWithDefaults(): void
    {
        $user = User::fromArray([]);

        $this->assertEmpty($user->getId());
        $this->assertEmpty($user->getUsername());
        $this->assertEquals(1, $user->getAuthVersion()); // Default 1
    }
}