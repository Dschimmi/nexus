# ADR 014: Verzicht auf CSS-Präprozessoren zugunsten von Nativem CSS

**Status:** Akzeptiert  
**Datum:** 2025-12-05  
**Autor:** Architecture Team  
**Betroffene Komponenten:** Designsystem, Frontend-Build-Pipeline, `vite.config`

## Kontext

Zu Beginn des Projekts wurde die Nutzung von SASS/SCSS als Standard für das Styling definiert. Im Laufe der Entwicklung des Designsystems und der Spezifikation modularer Komponenten (User-Modul, Terminal-Emulation) zeigten sich jedoch signifikante Nachteile dieser Entscheidung:

1.  **Komplexität der Toolchain:** Die Notwendigkeit, SASS zu kompilieren, erhöht die Abhängigkeiten (`node-sass` / `sass`) und verlangsamt den Build-Prozess.
2.  **Spezifitäts-Kriege:** Die Verschachtelungs-Funktionen (Nesting) von SASS verleiten dazu, Selektoren mit hoher Spezifität zu erzeugen (`.card .header .title`), die später schwer zu überschreiben sind. Dies widerspricht dem Ziel modularer, unabhängiger Komponenten.
3.  **Redundanz:** Moderne Browser unterstützen mittlerweile native CSS-Features, die SASS obsolet machen (CSS Variables, Nesting, Calculations).

## Entscheidung

Das Projekt wechselt mit sofortiger Wirkung vollständig auf **Modernes, Natives CSS**. Der Einsatz von SASS, SCSS, LESS oder anderen CSS-Präprozessoren ist im Core und in allen Modulen **untersagt**.

### 1. CSS Custom Properties (Variablen)
Das Theming (Farben, Abstände, Typografie) wird ausschließlich über CSS Custom Properties (`--primary-color`) im `:root`-Scope gesteuert. Dies ermöglicht dynamische Änderungen zur Laufzeit (z. B. Darkmode-Switch ohne Recompile).

### 2. BEM-Methodik (Block Element Modifier)
Um die Selektoren flach und performant zu halten, ist die strikte Anwendung der BEM-Namenskonvention (`.block__element--modifier`) verbindlich. Tiefe Verschachtelungen sind zu vermeiden.

### 3. Modulares CSS
Jedes Modul liefert seine eigenen CSS-Dateien. Diese werden über den zentralen Build-Prozess (Vite) gebündelt, nutzen aber Namespaces (Prefixes), um Konflikte zu vermeiden (z. B. `.nexus-terminal-...`).

## Konsequenzen

### Positiv
*   **Wartbarkeit:** CSS-Dateien sind ohne Build-Step lesbar und debugbar (DevTools).
*   **Performance:** Kleinere Bundle-Größen, da SASS-Mixins oft Code duplizieren. Der Browser übernimmt Berechnungen (`calc()`, `var()`) nativ.
*   **Architektur:** Erzwingt saubere Trennung und verhindert "Spaghetti-CSS" durch zu tiefe Verschachtelung.

### Negativ
*   **Umstellung:** Bestehende SCSS-Dateien müssen in natives CSS migriert werden (Variablen-Syntax ändern: `$color` -> `var(--color)`).
*   **Komfort:** Entwickler müssen auf "syntaktischen Zucker" (Mixins, komplexe Loops) verzichten und Standard-CSS-Lösungen verwenden.
