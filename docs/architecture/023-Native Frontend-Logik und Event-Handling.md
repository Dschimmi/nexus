# ADR 023: Native Frontend-Logik und Event-Handling

**Status:** Akzeptiert
**Datum:** 2025-12-11
**Autor:** Architecture Team
**Betroffene Komponenten:** `public/js/*`, `templates/*`

## Kontext
Im Rahmen des Template-Refactorings (Ticket 0000062) musste die JavaScript-Logik an die neue HTML-Struktur angepasst werden. Es stellte sich die Frage, ob ein Frontend-Framework (Vue/React) oder Build-Tools (Webpack/Vite für JS) eingeführt werden sollen.

## Entscheidung

### 1. Vanilla JS & YAGNI
Wir bleiben bei **Nativem JavaScript (ES6+)** ohne Transpilierung oder Bundling für die Logik.
* **Begründung:** Die Interaktivität (Dropdowns, Mobile Menu, Cookie Banner) ist zu gering, um den Overhead eines Frameworks zu rechtfertigen. Moderne Browser unterstützen ES6-Module und Features nativ.

### 2. Explizites DOM-Binding
Wir binden JavaScript-Logik ausschließlich über:
* **IDs** für Singleton-Elemente (z.B. `#header`, `#global-spinner`).
* **Daten-Attribute** oder spezifische Klassen für wiederkehrende Elemente (z.B. `.dropdown__trigger`).
* **Inline-Event-Handlers** werden strikt vermieden (Ausnahme: Legacy-Fallback, mittlerweile refactored zu Event-Listeners).

### 3. Separation of Concerns (Dateistruktur)
Die Logik wird fachlich getrennt:
* `layout.js`: UI-Interaktionen (Navigation, Dropdowns, Shortcuts).
* `theme.js`: Status-Management (Dark Mode).
* `compliance.js`: Rechtliche Logik (Cookies).

## Konsequenzen

### Positiv
* **Performance:** Kein JS-Parsing-Overhead durch große Framework-Bundles.
* **Einfachheit:** Niedrige Einstiegshürde für Backend-Entwickler (kein `npm run build` für JS-Logik nötig).

### Negativ
* **Skalierbarkeit:** Bei steigender Komplexität der UI-Logik kann dieser Ansatz zu unübersichtlichem "Spaghetti-Code" führen.