# 12. Teststrategie und Continuous Integration (Meilenstein 0.8.0)

Datum: 2025-12-02
Status: Akzeptiert

## Kontext
Mit der wachsenden Komplexität des Nexus-Frameworks (Auth, Config, PageManager) stieg das Risiko von Regressionen. Es gab bisher keine automatisierten Tests. Manuelle Tests waren zeitaufwändig und fehleranfällig. Zudem fehlte ein Mechanismus, der sicherstellt, dass Code-Änderungen die bestehende Funktionalität nicht brechen, bevor sie gemerged werden.

## Entscheidung

Wir etablieren eine strikte Qualitätssicherungs-Strategie basierend auf drei Säulen:

1.  **Unit-Testing mit PHPUnit:**
    *   Wir testen Services isoliert (Unit Tests).
    *   Tests liegen in `tests/Unit/`.
    *   *Ziel:* Abdeckung der Geschäftslogik (Services), nicht der Infrastruktur (Controller/Framework-Glue).

2.  **Dateisystem-Mocking mit vfsStream:**
    *   Da viele Kern-Komponenten (`PageManagerService`, `ConfigService`, `TranslatorService`) auf das Dateisystem zugreifen, nutzen wir die Bibliothek `mikey179/vfsstream`.
    *   *Entscheidung:* Keine echten Datei-Operationen in Unit-Tests.
    *   *Grund:* Performance (RAM statt HDD), Isolation (keine Seiteneffekte) und Unabhängigkeit von der lokalen Umgebung.

3.  **Dependency Injection für Testbarkeit:**
    *   Services, die Pfade nutzen (z.B. `TranslatorService`), dürfen `__DIR__` nicht hardcodieren.
    *   *Vorgabe:* Basispfade müssen im Konstruktor injiziert werden, damit sie im Test durch `vfsStream::url(...)` ersetzt werden können.

4.  **Continuous Integration (CI) mit GitHub Actions:**
    *   Jeder Push auf `main` triggert die Pipeline (`.github/workflows/ci.yml`).
    *   Die Pipeline testet gegen PHP 8.2 und 8.3 (Matrix).
    *   Ein fehlgeschlagener Testlauf markiert den Commit als "failing".

## Konsequenzen

*   **Vorteil:** Hohe Sicherheit bei Refactorings. Fehler in der Logik fallen sofort auf.
*   **Vorteil:** Die Architektur wird sauberer, da "hartcodierte" Pfade entfernt werden müssen, um Testbarkeit zu erreichen (Zwang zur Entkopplung).
*   **Aufwand:** Jeder neue Service muss mit Tests geliefert werden. Die `composer.json` muss Dev-Dependencies (`phpunit`, `vfsstream`) enthalten.
*   **Einschränkung:** Wir testen aktuell nur die Logik-Schicht (Services). Controller-Tests (Integration/E2E) sind noch nicht Teil dieser Strategie.