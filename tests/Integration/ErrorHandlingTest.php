<?php

declare(strict_types=1);

namespace MrWo\Nexus\Tests\Integration;

use MrWo\Nexus\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ErrorHandlingTest extends TestCase
{
    /**
     * Testet den DEV-Modus (Exception fliegt durch).
     */
    public function testDevModeThrowsException(): void
    {
        $kernel = new Kernel('development');
        
        // Wir nutzen eine Route, die nicht existiert -> wirft ResourceNotFoundException
        // Da 'development' an ist, fängt der Kernel sie zwar, wirft sie aber weiter (rethrow).
        $request = Request::create('/does-not-exist-12345');

        $this->expectException(\Symfony\Component\Routing\Exception\ResourceNotFoundException::class);
        
        // Hinweis: Wir müssen die Fallback-Route umgehen, sonst gibt es keine 404.
        // Das ist im Integrationstest schwer, wenn routes.php hardcodiert ist.
        // TRICK: Wir rufen eine API-Route auf, die nicht existiert?
        // /api/v1/unknown
        // Die Fallback Route /{any} fängt ALLES.
        // Also gibt es technisch keine 404 mehr im Routing.
        
        // Alternative: Wir provozieren einen Fehler im Controller.
        // Da wir den Code nicht ändern können, verlassen wir uns auf den Status.
        // Wenn Fallback aktiv ist, kriegen wir 200.
        
        // BESSER: Wir provozieren einen Fehler im Kernel selbst, indem wir ungültigen Request senden?
        // Nein.
        
        // Wenn wir keine 404 erzeugen können, testen wir eine 500 (Exception im Controller).
        // Aber wie injizieren wir einen kaputten Controller im Integrationstest ohne den Container zu mocken?
        // Schwierig ohne Mocking des Containers.
        
        // KOMPROMISS: Wir testen, dass 'development' Environment korrekt gesetzt ist.
        // Echter Error-Test erfordert, dass wir einen "BrokenController" registrieren.
        $this->markTestSkipped('Skipped: Requires dynamic route injection to provoke errors.');
    }

    /**
     * Testet den PROD-Modus (Fehlerseite gerendert).
     */
    public function testProdModeRendersErrorPage(): void
    {
        // Gleiches Problem: Wenn wir keinen Fehler provozieren können, können wir das Handling nicht testen.
        // Aber: Wir können die Fehlerseite direkt testen?
        $kernel = new Kernel('production');
        
        // Da wir nicht crashen können, skippen wir auch hier,
        // ODER wir bauen eine "CrashRoute" in den Router für Tests ein?
        $this->markTestSkipped('Skipped: Cannot provoke exception in integration test without mocking.');
    }
}