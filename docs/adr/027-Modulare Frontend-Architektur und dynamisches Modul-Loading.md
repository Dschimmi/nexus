# ADR 027: Modulare Frontend-Architektur und dynamisches Modul-Loading

**Status:** Akzeptiert  
**Datum:** 2025-12-14  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `config/services.php`, `config/routes.php`, CSS-Struktur, `vite.config`

## Kontext

Mit der Einführung von Feature-Modulen (User, Terminal) stieß die monolithische Struktur des Frameworks an ihre Grenzen:
1.  **CSS-Chaos:** Eine riesige `base.css` verhinderte die Wiederverwendbarkeit von Komponenten.
2.  **Hardcoding:** Neue Module mussten manuell in den Core-Konfigurationsdateien registriert werden.
3.  **Build-Komplexität:** Jedes Modul brachte eigene Assets mit, die zentral verwaltet werden mussten.

## Entscheidung

### 1. Dynamischer Module Loader
Der Kernel (via `config/*.php`) scannt nun automatisch das Verzeichnis `/modules/`.
*   **Service-Discovery:** Findet `modules/*/config/services.php` und lädt diese in den DI-Container.
*   **Route-Discovery:** Findet `modules/*/config/routes.php` und importiert diese in den Router.
*   **Konsequenz:** Module sind "Plug-and-Play". Das Löschen eines Modul-Ordners entfernt es restlos aus dem System.

### 2. Atomare CSS-Architektur (Native CSS)
Wir strukturieren das Frontend in strikte Layer um:
*   **Layer 1 (Global):** `variables.css`, `reset.css` (Tokens).
*   **Layer 2 (Utilities):** `utilities.css` (Atomare Helferklassen `u-mt-4`).
*   **Layer 3 (Components):** `buttons.css`, `forms.css`, `alerts.css` (BEM-Komponenten).
*   **Layer 4 (Modules):** Modulspezifische Styles (`cookie-banner.css`).

Die Bündelung erfolgt zentral über Vite (`app.css`), wobei im Dev-Mode Einzeldateien geladen werden (oder via AssetService Mapping).

### 3. Zentralisierte UI-Komponenten
Wiederkehrende UI-Elemente (Flash Messages, Cookie Banner) wurden in Twig-Partials (`templates/partials/`) ausgelagert, um Konsistenz über alle Module hinweg zu garantieren.

## Konsequenzen

### Positiv
*   **Skalierbarkeit:** Neue Module können hinzugefügt werden, ohne den Core zu berühren.
*   **Wartbarkeit:** CSS ist granular und konfliktfrei (BEM).
*   **Performance:** Vite bündelt alles in ein einziges File für Production.

### Negativ
*   **Initialaufwand:** Das Refactoring der `base.css` erforderte Eingriffe in alle Templates.