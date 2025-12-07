# ADR 015: Sprachumschaltung via POST (PRG-Pattern)

**Status:** Akzeptiert  
**Datum:** 2025-12-05  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `LanguageController`, `TranslatorService`, `Routing`, `Frontend`

## Kontext

Ursprünglich wurde die Sprachumschaltung über GET-Parameter (`?lang=en`) realisiert. Dies führte zu mehreren Problemen:
1.  **SEO / Duplicate Content:** Jede Seite war unter mehreren URLs erreichbar (`/admin`, `/admin?lang=en`, `/admin?lang=de`). Canonical-Tags können dies lindern, aber nicht vollständig lösen.
2.  **State Change via GET:** Gemäß REST-Prinzipien sollten GET-Anfragen idempotent sein und keinen serverseitigen Zustand (Session-Sprache) ändern.
3.  **URL-Verschmutzung:** Der Parameter blieb in der Adressleiste stehen oder erforderte komplexe Redirect-Logik im Kernel, um ihn zu entfernen.

## Entscheidung

Wir stellen die Sprachumschaltung konsequent auf das **Post-Redirect-Get (PRG) Pattern** um.

### 1. Dedizierter Controller
Ein neuer `LanguageController` (`POST /language/switch`) nimmt die gewünschte Sprache entgegen, validiert sie, setzt die Session-Variable und leitet via `Location`-Header zurück zur Ursprungsseite (`Referer`).

### 2. Frontend-Implementierung
Die Sprachwahl im Header erfolgt nicht mehr über Hyperlinks (`<a>`), sondern über kleine HTML-Formulare mit Submit-Buttons. Diese Buttons werden mittels CSS so gestylt, dass sie sich visuell nahtlos in das Dropdown-Menü einfügen.

### 3. Service-Bereinigung
Der `TranslatorService` und der `Kernel` werden von jeglicher Redirect-Logik befreit. Der `TranslatorService` liest die Sprache beim Start ausschließlich aus der Session (oder fällt auf Default zurück). Er akzeptiert keine URL-Parameter mehr.

## Konsequenzen

### Positiv
*   **Saubere URLs:** Keine `?lang=` Parameter mehr in der Adressleiste.
*   **SEO:** Eindeutige URLs für Crawler.
*   **Architektur:** Klare Trennung von Zustandsänderung (Controller) und Anzeige (View). Einhaltung von HTTP-Semantik (POST für Änderungen).

### Negativ
*   **Frontend-Komplexität:** Das Template für die Sprachwahl ist etwas verboser (Formulare statt Links).
*   **CSS-Aufwand:** Zusätzliches Styling für Buttons notwendig, um Konsistenz mit Links zu wahren.