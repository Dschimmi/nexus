# ADR 019: Interface-Standardisierung & Einführung von Quality-Gates

**Status:** Akzeptiert  
**Datum:** 2025-12-06  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `TranslatorService`, `SessionService`, Build-Pipeline

## Kontext

Das Audit v0.8.0 identifizierte Verstöße gegen SOLID-Prinzipien (DIP/OCP) und fehlende Werkzeuge zur Qualitätssicherung. Insbesondere der `TranslatorService` war monolithisch implementiert und verhinderte Erweiterungen (z. B. DB-Übersetzungen). Gleichzeitig fehlten Mechanismen zur automatischen Prüfung von Coding-Standards.

## Entscheidung

### 1. Provider-Architektur für i18n
Der `TranslatorService` wurde refactored, um das Open/Closed Principle zu erfüllen.
*   Einführung von `TranslationProviderInterface`.
*   Auslagerung der Dateilogik in `PhpFileTranslationProvider`.
*   Der Service agiert nun als Aggregator.

### 2. YAGNI-Entscheidung: Kein SessionInterface
Entgegen der ursprünglichen Überlegung wird für den `SessionService` **kein** Interface eingeführt.
*   **Begründung:** Die Klasse ist final in ihrer Architektur (Bag-Pattern). Die notwendige Abstraktion erfolgt bereits auf Ebene des `SessionHandlerInterface` (Storage). Ein zusätzliches Interface würde unnötigen Boilerplate erzeugen, ohne Flexibilität zu gewinnen.

### 3. Quality Tooling
Integration statischer Analysewerkzeuge in den Dev-Stack:
*   **PHPStan:** Level 5, zur Erkennung von Typfehlern und toten Code-Pfaden.
*   **PHP-CS-Fixer:** Zur Durchsetzung von PSR-12 Coding Standards.

## Konsequenzen

### Positiv
*   **Erweiterbarkeit:** Neue Übersetzungsquellen können per Konfiguration hinzugefügt werden.
*   **Qualität:** Die Codebasis wird messbar robuster durch statische Analyse.
*   **Pragmatismus:** Verzicht auf unnötige Interfaces hält die Komplexität niedrig.

### Negativ
*   **Build-Zeit:** Die CI-Pipeline verlängert sich durch die zusätzlichen Checks (PHPStan, CS-Fixer).