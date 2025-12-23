# ADR 030: Test-Strategie für Session-abhängige Integrationstests

**Status:** Akzeptiert  
**Datum:** 2025-12-07  
**Autor:** Architecture Team  
**Betroffene Komponenten:** Test-Suite (`tests/Integration`), `Kernel`, `SessionService`

## Kontext

Die Integrationstests laufen im CLI-Modus (PHPUnit). Da PHP im CLI-Modus keine native Session-Persistenz zwischen Requests (wie ein Browser mit Cookies) bietet, schlugen Tests fehl, die auf Stateful-Workflows (Login -> Redirect -> Dashboard) basieren.
Der `Kernel` unterband zudem das Speichern der Session im CLI-Modus (Ticket #26), was Tests unmöglich machte.

## Entscheidung

### 1. Kernel-Anpassung für Tests
Der `Kernel` wurde angepasst, um Sessions auch im CLI-Modus zu starten und zu speichern, **wenn** die Umgebung `APP_ENV=test` ist. Dies erlaubt "Stateful Testing" ohne Webserver.

### 2. Manuelle Session-Propagation
In Integrationstests simulieren wir den Browser, indem wir:
1.  Die Session-ID manuell extrahieren (`session_id()`).
2.  Die Session explizit schließen (`session_write_close()`), um das Schreiben auf die Platte zu erzwingen.
3.  Die Session-ID im Folge-Request als Cookie-Header injizieren (`$request->cookies->set(...)`).

### 3. Prozess-Isolation
Tests, die globale Zustände (`$_SESSION`, `$_SERVER`) manipulieren, müssen mit der PHPUnit-Annotation `@runInSeparateProcess` markiert werden, um Seiteneffekte auf andere Tests zu verhindern.

## Konsequenzen

### Positiv
*   **Testbarkeit:** Wir können komplexe User-Flows (Login, Consent) ohne echten Browser testen.
*   **Performance:** Schneller als Browser-Tests (Panther/Selenium).

### Negativ
*   **Komplexität:** Der Test-Code ist verboser ("Session-Dance").
*   **Kernel-Logik:** Der Kernel enthält nun test-spezifische Weichen (`if appEnv === 'test'`).