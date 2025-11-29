# ADR 007: Atomare CSS-Architektur und Variablen-basiertes Theming

* **Status:** Akzeptiert
* **Datum:** 29.11.2025
* **Kontext:** Meilenstein 0.6.0 (Standard-Layout)

## Kontext und Problemstellung
Das Pflichtenheft fordert in Abschnitt 7.1.2.6 und 11.3 eine "atomare CSS-Struktur", die eine klare Trennung der Komponenten in der Entwicklung ermöglicht, aber für den Produktivbetrieb effizient gebündelt werden kann. Zudem werden strikte Vorgaben für das Corporate Design (Farben, Dark Mode) und Barrierefreiheit (WCAG 2.1 AA) gemacht. Der Einsatz großer Frameworks (Bootstrap, Tailwind) wurde evaluiert, aber zugunsten einer schlanken, wartbaren Eigenlösung verworfen, um den Overhead zu minimieren (PH 5.1).

## Entscheidung
Wir implementieren eine **Vanilla-CSS-Architektur** basierend auf CSS Custom Properties (Variablen), die in spezifische, "atomare" Dateien aufgeteilt ist.

1.  **Dateistruktur:**
    *   `variables.css`: Enthält ausschließlich Design-Tokens (Farben, Maße) und das Theme-Mapping (Light/Dark).
    *   `base.css`: Enthält Reset, Typografie und globale, wiederverwendbare Komponenten (Buttons, Forms).
    *   `[komponente].css`: Spezifische Styles für Layout-Bereiche (header.css, footer.css, content.css).

2.  **Theming-Strategie:**
    *   Wir nutzen CSS-Variablen (`--bg-body`, `--text-main`) als Abstraktionsschicht.
    *   Konkrete Hex-Werte werden nur in `variables.css` definiert.
    *   Der Dark Mode wird ausschließlich über das Überschreiben dieser Variablen im Selektor `[data-theme="dark"]` realisiert.

3.  **Generischer Ansatz:**
    *   Layouts werden durch generische Klassen (`.grid-4`, `.textbox`) gesteuert, statt durch kontext-spezifische IDs, um die Wiederverwendbarkeit zu erhöhen.

## Konsequenzen
*   **Positiv:** Maximale Performance (kein ungenutzter Code), volle Kontrolle über das Design, einfache Anpassung des Dark Mode an zentraler Stelle.
*   **Negativ:** Höherer initialer Schreibaufwand im Vergleich zu Frameworks wie Bootstrap.
*   **Compliance:** Erfüllt PH 7.1 (Designsystem) und PH 5.1 (Performance).

### Abweichung von der Spezifikation (SASS vs. Native CSS)
Das Pflichtenheft fordert in Kapitel 11 (S. 42) explizit den Einsatz des Präprozessors **SASS** für Variablen und Mixins. Wir haben uns bewusst **gegen SASS** und für **natives CSS (Level 3+)** entschieden.

**Begründung:**
1.  **Dynamisches Theming (Dark Mode):** SASS-Variablen werden zur Build-Zeit in statische Hex-Codes kompiliert. Ein Umschalten des Farbschemas im Browser (Light/Dark Toggle) wäre damit nur durch das Laden einer komplett neuen CSS-Datei möglich. Native CSS Custom Properties (`var(--...)`) können hingegen zur Laufzeit per JavaScript manipuliert werden, was eine performantere und flüssigere User Experience bietet.
2.  **Zukunftssicherheit & Performance:** Moderne Browser unterstützen mittlerweile native CSS-Nesting und Variablen performant. Dies eliminiert die Notwendigkeit für einen schweren SASS-Compiler im Development-Stack und reduziert die Komplexität der Build-Pipeline (Vite), was dem Ziel eines "leichtgewichtigen Frameworks" (LH S. 1) besser entspricht.
3.  **Wartbarkeit:** Die geforderte Wiederverwendbarkeit von Design-Elementen wird durch die strikte Trennung in `variables.css` (Design Tokens) gleichermaßen, wenn nicht sogar flexibler, erreicht.