## 7. Design-, Barrierefreiheits- und Usability-Vorgaben
`(Vorgaben für das User Interface (UI), die User Experience (UX) und die Einhaltung von Standards wie WCAG 2.1.)`

Dieses Kapitel definiert die verbindlichen Vorgaben für die Gestaltung der Benutzeroberflächen (User Interface, UI) und das Benutzererlebnis (User Experience, UX). Diese Richtlinien stellen sicher, dass alle auf dem "Nexus"-Framework basierenden Anwendungen ein konsistentes, modernes und zugängliches Erscheinungsbild aufweisen, wie im Lastenheft (LH: S. 5, "Designvorgaben") gefordert.
### 7.1. Grundlegendes Designsystem und Theming

#### 7.1.1. Spezifikation:
- Zentrales Designsystem: Das Framework wird mit einem zentralen, wiederverwendbaren Designsystem ausgeliefert. Dieses System definiert das Standard-Erscheinungsbild für alle grundlegenden UI-Komponenten (z.B. Buttons, Formularfelder, Typografie, Farbpalette, Icons).
- Technische Basis (Native CSS): Das Designsystem wird ausschließlich mit nativem, modernem CSS umgesetzt. Der Einsatz von CSS-Präprozessoren (wie SASS, SCSS, LESS) ist untersagt, um die Komplexität der Toolchain zu minimieren und Spezifitäts-Probleme durch tief verschachtelte Selektoren zu verhindern.
  - Methodik: Die Einhaltung der BEM-Methodik (Block, Element, Modifier) ist verbindlich, um flache Selektoren-Strukturen zu garantieren.
  - Konfigurierbarkeit: Variablen und Theming werden ausschließlich über CSS Custom Properties (CSS Variables) realisiert.
  -  Scope: Modul-spezifisches CSS muss so geschrieben sein, dass es globale Stile nicht ungewollt überschreibt (z.B. durch striktes Prefixing der Klassen gemäß Modul-Namen).
- Theming-Architektur: Das Design muss über eine Theming-Architektur anpassbar sein. Es muss möglich sein, ein projektspezifisches "Theme" zu erstellen, das die Standard-Stile des Kern-Designsystems überschreibt oder erweitert. Dies geschieht primär durch die Anpassung von Konfigurationsvariablen (z.B. für Farben, Schriftarten, Abstände), ohne dass das Kern-CSS verändert werden muss.
- Individualisierbarkeit: Die Individualisierbarkeit des Designs für jedes "geforkte" Projekt, wie im Lastenheft (LH: S. 2, SOLL-Zustand) gefordert, wird ausschließlich über diese Theming-Architektur realisiert.

Begründung:
Ein zentrales Designsystem gewährleistet die in den Usability-Anforderungen (PH: 5.5) geforderte visuelle Konsistenz über alle Module und Anwendungen hinweg. Die Theming-Architektur bietet die notwendige Flexibilität, um das Erscheinungsbild für verschiedene Webprojekte individuell anzupassen, ohne die gemeinsame Codebasis zu verletzen. Dies reduziert den Design- und Entwicklungsaufwand erheblich.

##### 7.1.2.1. Header
- Logo-Bereich (linke Hälfte): 
  - Platzhalter-Logo in einem dezent hervorgehobenen Fenster (z. B. leichter Schatten oder Farbverlauf), das eine freie Skalierung und Positionierung des Logos ermöglicht.
  - Das Logo ist verlinkt (z. B. zur Startseite).
- Rechte Seite: 
  - Light-/Darkmode-Umschalter: Custom-Design, funktional, serverseitig gespeichert.
  - Sprachauswahl-Dropdown: Custom-Design, zeigt alle verfügbaren Sprachen an, initial wird die Browser-Sprache verwendet (Fallback: Englisch), serverseitig gespeichert.
  - Platzhalter für "Registrieren/Login": Nicht funktional, aber sichtbar, ausblendbar bei eingeloggtem Benutzer.
  - Suchfeld: Platzhalter, nicht funktional, ausblendbar.
  - Eingeloggter Benutzer: 
   1. Begrüßungstext ("Hallo, [Benutzername]") mit Dropdown (mindestens "Logout"-Option).
- Sticky: Der Header bleibt am oberen Bildschirmrand fixiert.
- Responsive: Anpassung an alle Bildschirmgrößen, auf kleinen Bildschirmen werden Elemente ggf. in ein Hamburger-Menü ausgeklappt.

##### 7.1.2.2. Subheader (Navigation)
- Position: Direkt unter dem Header, volle Breite.
- Design: Akzentfarbe zur Abgrenzung vom Header und Content.
  - Navigationselemente: 
   1. Wie Browsertabs, horizontal ausgerichtet.
   2. Bei zu vielen Elementen: Scrollpfeile (links/rechts).
   3. Unterstützung für Dropdown-Menüs (Unterpunkte).
  - Sticky: Fixiert unter dem Header.
  - Responsive: Anpassung an alle Bildschirmgrößen.

##### 7.1.2.3. Content-Bereich
- Breite: Vollbild (100% Bildschirmbreite).
- Text-Elemente: 
  - Auf großen Bildschirmen: Lesbare Breite (900–1200px), zentriert.
  - Auf schmalen Bildschirmen: 100% Breite.
- Hintergrund: Neutral, harmonisch mit Header/Subheader.
- Visuelle Abgrenzung: 
  - Akzentfarbe für Rahmen oder Schatten (z. B. bei Cards).
  - Standard-Abstände (Padding/Margin) für Konsistenz.
- Beispiel-Elemente: 
  - Cards mit Titel, Platzhaltertext und Button.
  - Überschriften und Absätze in klaren Abschnitten.
  - Rasterlayout für Cards (responsiv).

##### 7.1.2.4. Footer
- Breite: 100% der Bildschirmbreite.
  - Inhalte: 
   1. Copyright-Hinweis (z. B. "© 2025 Nexus Framework. Alle Rechte vorbehalten.").
   2. Impressum-Link (verlinkt zur Impressumsseite).
   3. Datenschutz-Link (verlinkt zur Datenschutzerklärung).
   4. Sitemap (Liste der wichtigsten Seiten oder Link zur Sitemap-Seite).
   5. Platzhalter-Bereich für Social-Media-Icons.
  - Visuelle Abgrenzung: 
   1. Leichte Hintergrundfarbe oder Rahmen/Schatten zur Abgrenzung vom Content.
   2. Standard-Abstände (Padding/Margin) für klare Struktur.
  - Responsive: 
   1. Auf kleinen Bildschirmen: Inhalte übereinander (gestapelt).
   2. Auf großen Bildschirmen: Inhalte nebeneinander (z. B. Copyright links, Links in der Mitte, Social-Media-Icons rechts).

##### 7.1.2.5. Cookie-Banner
- Positionierung: Zentral mittig auf dem Bildschirm, modal.
  - Verhalten: 
   1. Blockierend: Keine Interaktionen mit der Webseite möglich, bis der Nutzer eine Auswahl trifft.
   2. Nicht wegklickbar ohne Auswahl.
  - Visueller Effekt: 
   1. Hintergrund der Webseite (außer dem Cookie-Banner) wird ausgeblendet/ausgeblurrt (CSS: filter: blur(5px)).
  - Inhalt: 
   1. Kurzer, klarer Text zur Cookie-Nutzung.
   2. Zwei Buttons: "Zustimmen" (akzeptiert alle Cookies) und "Ablehnen" (akzeptiert nur essentielle Cookies).
  - Design: 
   1. Neutraler oder leicht abgedunkelter Hintergrund.
   2. Akzentfarbe für Buttons.

##### 7.1.2.6. CSS-Struktur
- Entwicklungsumgebung: Modular: Klare Trennung in Dateien (z. B. cookie-banner.css, header.css).
- Produktivumgebung: Gebündelt: Alle CSS-Dateien werden zu einer main.css.

##### 7.1.2.7. Barrierefreiheit & Usability
- Tastaturbedienbar: Alle interaktiven Elemente sind per Tastatur erreichbar.
- Farbkontraste: WCAG 2.1 AA-konform.
- ARIA-Labels: Für Dropdowns, Schalter und interaktive Elemente.

##### 7.1.2.8. Responsive Design
- Anpassung an alle Bildschirmgrößen (Desktop, Tablet, Mobil).
- Mobile First: Priorisierung der mobilen Nutzerfreundlichkeit.

##### 7.1.2.9. Abhängigkeiten
- Version 0.4.0 (Internationalisierung): Sprachauswahl-Dropdown nutzt die i18n-Komponente.
- Version 0.5.0 (Umgebungs-Handling): Light-/Darkmode-Umschalter nutzt die serverseitige Speicherung.

#####i 7.1.2.10. Definition of Done
- Alle UI-Komponenten sind implementiert und getestet.
- Das Layout ist responsiv und barrierefrei.
- Das Cookie-Banner ist blockierend und funktional.
- Die CSS-Struktur ist modular und produktionsbereit.

#### 7.1.3. Standard-Farben

##### 7.1.3.1 Farbpalette
Alle Farben sind als Hex-Codes angegeben.
- Hauptfarbe 1: #152836 (Dunkelblau, dies ist die Farbe der Logo-Ruhezone)
  - Lightmode: Schriften, Visuelle Trenner etc.
  - Darkmode: Seitenhintergrund
- Hauptfarbe 2: #d9dcdf (Aschweiß)
  - Lightmode: Seitenhintergrund
  - Darkmode: Schriften, Visuelle Trenner etc.
- Akzentfarbe 1: #f2b400 (Goldgelb)
  - Light- und Darkmode: Trenner für Hauptseitenelemente Header, Subheader, Content und Footer.

##### 7.1.3.2 Farbvariationen
- Folgende Farbvariationen sind zulässig:
  - Dunkelblau: 
   1. Darker Shades: #132431, #11202b, #0f1c26, #0d1820, #0b141b, #081016, #060c10, #04080b, #020405
   2. Lighter Shades: #2c3c4a, #44535e, #5b6972, #737e86, #8a949b, #a1a9af, #b9bfc3, #d0d4d7, #e8eaeb
  - Aschweiß: 
   1. Darker Shades: #c3c6c9, #aeb0b2, #989a9c, #828486, #6d6e70, #575859, #414243, #2b2c2d, # 161616
   2. Lighter Shades: #dde0e2, #e1e3e5, #e4e7e9, #e8eaec, eceeef, #f0f1f2, #f4f5f5, #f7f8f9, #fbfcfc
  - Goldgelb: keine Variationen erlaubt.

### 7.2. Barrierefreiheit (Accessibility)
Spezifikation:
- Verbindlicher Standard: Alle von offiziellen Modulen generierten Benutzeroberflächen müssen den Web Content Accessibility Guidelines (WCAG) in der Version 2.1 auf dem Konformitätslevel AA entsprechen. Dies ist eine verbindliche Anforderung.
- Tastaturbedienbarkeit: Jede interaktive Komponente der Benutzeroberfläche (Links, Buttons, Formularfelder, Menüs) muss vollständig und ohne Funktionseinschränkung ausschließlich über die Tastatur bedienbar sein. Die Fokus-Reihenfolge muss logisch und nachvollziehbar sein, und das aktuell fokussierte Element muss visuell klar hervorgehoben werden.
- Semantisches HTML: Die HTML-Struktur muss semantisch korrekt sein. Es sind die passenden HTML5-Elemente für die jeweilige Aufgabe zu verwenden (z.B. <nav> für Navigation, <main> für den Hauptinhalt, <button> für Aktionen). Der Einsatz von <div>-Elementen für interaktive Komponenten ist zu vermeiden.
- ARIA-Landmarks: Zur Unterstützung von assistiven Technologien müssen ARIA (Accessible Rich Internet Applications) Roles für die Hauptbereiche der Seite (z.B. `role="navigation"`, `role="search"`) verwendet werden, wo semantische HTML-Elemente nicht ausreichen.
- Alternativtexte: Alle informativen Bildelemente (<img>) müssen ein aussagekräftiges alt-Attribut enthalten. Dekorative Bilder müssen ein leeres `alt=""`-Attribut besitzen.
- Farbkontraste: Bei der Erstellung des Designsystems (PH: 7.1) und seiner Standard-Themes muss sichergestellt werden, dass die gewählten Farbkombinationen für Text und Hintergrund ein Kontrastverhältnis von mindestens 4.5:1 (für normalen Text) bzw. 3:1 (für großen Text) aufweisen. Ein Werkzeug zur Überprüfung des Kontrasts ist im Design- und Entwicklungsprozess verpflichtend einzusetzen.
  - Empfehlung 1 (Entwicklung & Test): Das primäre Werkzeug für die Überprüfung sind die integrierten Entwicklertools moderner Browser (insbesondere Firefox), da diese eine Live-Analyse des gerenderten Inhalts ermöglichen.
  - Empfehlung 2 (Design-Phase): Für die initiale Definition der Farbpalette kann ergänzend der "WebAIM Contrast Checker" herangezogen werden.

Begründung:
Die Einhaltung von Barrierefreiheitsstandards ist eine direkte Anforderung aus dem Lastenheft (LH: S. 7), die ab 2025 auch eine gesetzliche Verpflichtung darstellt (BFSG). Eine barrierefreie Gestaltung stellt sicher, dass die Anwendungen für alle Menschen, einschließlich solcher mit Behinderungen, zugänglich und nutzbar sind. Dies erweitert die potenzielle Zielgruppe und erfüllt die ethischen und rechtlichen Anforderungen an moderne Webanwendungen.

---