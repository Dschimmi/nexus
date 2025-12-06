## 11. Projektorganisation und Meilensteine
`(Beschreibung der Projektrollen, Verantwortlichkeiten und ein detaillierter Zeitplan mit den definierten Meilensteinen.)`

Grundsatz:
Dieses Kapitel beschreibt die organisatorische Struktur und den logischen Ablauf für die Umsetzung des "Nexus"-Projekts. Es definiert die Rollen und Verantwortlichkeiten der beteiligten Parteien sowie die zentralen Meilensteine. Anstelle eines festen Zeitplans wird der Projektfortschritt direkt an die in Kapitel PH: 9.1.2 definierten Versionierungskonventionen gekoppelt. Jeder erreichte Meilenstein resultiert in der Freigabe einer neuen, spezifischen Versionsnummer und markiert den Abschluss einer klar definierten Entwicklungsphase.

### 11.1. Rollen und Verantwortlichkeiten
- Auftraggeber (Product Owner):
  - Verantwortlichkeiten: Der Auftraggeber ist der alleinige Ansprechpartner für alle fachlichen und inhaltlichen Anforderungen an das Projekt. Er definiert und priorisiert die Funktionalitäten, prüft die umgesetzten Features und ist für die formale Abnahme der Meilensteine und des Gesamtprojekts verantwortlich. Er stellt zudem alle notwendigen Inhalte (z.B. Rechtstexte) und die betriebliche Infrastruktur (Hosting) bereit.
- Auftragnehmer (Lead Developer):
  - Verantwortlichkeiten: Der Auftragnehmer ist für die vollständige technische Konzeption, Architektur und Implementierung des "Nexus"-Frameworks gemäß den Spezifikationen dieses Pflichtenhefts verantwortlich. Er gewährleistet die Einhaltung der definierten Qualitätsstandards (Code-Qualität, Tests, Sicherheit), verantwortet den Build- und Deployment-Prozess und stellt die technische Dokumentation bereit.

### 11.2. Entwicklungsmeilensteine (Der Weg zu Version 1.0.0)
Der alleinige und zentrale Liefergegenstand dieses Projekts ist die Erstellung des Nexus-Base Frameworks und dessen Freigabe in einer stabilen Version 1.0.0. Der Weg zu diesem Ziel wird durch die folgenden, aufeinander aufbauenden Entwicklungsmeilensteine strukturiert. Jeder Meilenstein resultiert in einer neuen 0.x-Version und repräsentiert ein abgeschlossenes, testbares Inkrement des Basisframeworks.
Die Roadmap ist strategisch in zwei Hauptphasen unterteilt: die Feature-Entwicklungsphase und die anschließende Stabilisierungsphase. Der Sprung in der Versionierung (z.B. von 0.6.x auf 0.9.x) signalisiert den Übergang zwischen diesen Phasen und den Eintritt in den „Feature Freeze“, bei dem keine neuen Funktionen mehr hinzugefügt werden. Bewusst freigelassene Versionsnummern (z.B. 0.7.x, 0.8.x) dienen dabei als strategischer Puffer für eventuell notwendige, unvorhergesehene Zwischenschritte, ohne den geplanten Ablauf zu gefährden.

#### 11.2.1. Roadmap zur Version 1.0.0
Version 0.1.0: Grundgerüst und Rendering-Pipeline
Dieser erste Meilenstein schafft das absolute Skelett des Frameworks. Er umfasst die Implementierung der grundlegenden Verzeichnisstruktur, des zentralen Einstiegspunktes (public/index.php) und der minimalen Kernkomponenten für die Verarbeitung einer Anfrage. Nach Abschluss dieses Meilensteins ist das Framework in der Lage, eine statische Anfrage zu empfangen, sie über eine Routing-Komponente einem Controller zuzuordnen und eine einfache HTML-Seite über die integrierte Twig-Template-Engine auszuliefern. 

Dieser Meilenstein legt die technische Grundlage für PH: 4.1.1 (Seiten-Rendering und Template-Management).

Version 0.2.0: Sichere Session-Verwaltung und Konfiguration
Dieser Meilenstein baut auf dem Rendering-Grundgerüst auf und macht das Framework zustandsfähig. Er umfasst die Implementierung des zentralen, sicheren Session-Services gemäß PH: 4.1.2. Dies beinhaltet die korrekte Konfiguration von sicheren Cookie-Flags (httponly, secure, samesite=strict), den Mechanismus für das Session-Fingerprinting und die Fähigkeit, CSRF-Tokens zu generieren und zu validieren. Parallel dazu wird ein grundlegendes Konfigurationssystem eingeführt, das es ermöglicht, Anwendungs-Parameter (wie z.B. Session-Namen oder Cookie-Laufzeiten) aus zentralen Dateien zu laden. 

Nach Abschluss dieses Meilensteins ist die Basis für alle Funktionen gelegt, die einen Benutzerzustand über mehrere Anfragen hinweg speichern müssen.

Version 0.3.0: DSGVO- und Compliance-Mechanismen
Dieser Meilenstein implementiert die grundlegenden Funktionen zur Einhaltung der Datenschutzgrundverordnung. Er umfasst die Entwicklung des zentralen Services zur Verwaltung von Benutzerzustimmungen (Cookie-Consent) gemäß PH: 4.1.4. Dies beinhaltet die serverseitige Logik zur Speicherung und Abfrage des Zustimmungsstatus (unter Nutzung des in V0.2.0 geschaffenen Session-Services) sowie die Implementierung eines einfachen, erweiterbaren Cookie-Banners im Frontend. 

Nach Abschluss dieses Meilensteins erfüllt das Framework die Minimalanforderungen, um im Einklang mit der DSGVO betrieben werden zu können.

Version 0.4.0: Internationalisierung (i18n)
Dieser Meilenstein implementiert die Fähigkeit des Frameworks zur Mehrsprachigkeit gemäß PH: 4.1.3. Herzstück ist die Entwicklung eines zentralen "Translator"-Services, der eine definierte Schnittstelle (TranslatorInterface) implementiert. In dieser ersten Ausbaustufe wird ein Provider entwickelt, der Übersetzungs-Strings aus einfachen, sprachspezifischen PHP-Dateien lädt. Die im vorherigen Meilenstein (V0.3.0) eingeführten Texte für das Cookie-Banner werden auf diesen neuen Service umgestellt, um die korrekte Integration und Funktionalität nachzuweisen. 

Nach Abschluss dieses Meilensteins ist das Framework in der Lage, alle Frontend-Ausgaben über eine flexible und erweiterbare Übersetzungs-Architektur mehrsprachig darzustellen.

Version 0.5.0: Umgebungs-Handling, Logging und Sicherheits-Härtung
Dieser Meilenstein fokussiert sich auf die Stabilität und Sicherheit des Frameworks im Produktionsbetrieb.
- Implementierung des Umgebungs-Handlings: Es wird der in PH: 8.4 definierte Mechanismus zur Steuerung der Anwendung über Environment-Variablen (insb. APP_ENV) und eine .env-Datei implementiert. Dies beinhaltet die zwingende Umsetzung des Fail-Safe-Prinzips, das bei fehlender Konfiguration auf den production-Modus zurückfällt.
- Differenzierte Fehlerbehandlung: Die Konfiguration von Tracy wird direkt an die APP_ENV-Variable gekoppelt. Im development-Modus werden detaillierte Debug-Informationen angezeigt, im production-Modus wird die Anzeige von Fehlern unterdrückt und diese stattdessen in Log-Dateien geschrieben.
- Sicherheits-Härtung: Die in PH: 5.2 geforderte, aktive Erzwingung von HTTPS wird implementiert und ist ausschließlich im production-Modus aktiv.

Nach Abschluss dieses Meilensteins ist das Framework in der Lage, sein Verhalten sicher und automatisch an die jeweilige Betriebsumgebung anzupassen, was eine Grundvoraussetzung für den Go-Live ist.

Version 0.6.0: Standard Layout
Dieser Meilenstein implementiert ein visuell ansprechendes, nutzbares Standard-Design für das Nexus-Framework. Das Layout baut auf Twig-Infrastruktur auf und stellt sicher, dass die Webseite für die Implemetierung von Erweiterungsmodulen ausreichend funktional ist. Es umfasst alle relevanten UI-Komponenten und ist responsiv, barrierefrei sowie modular gestaltet.

Version 0.8.0: Test-Suite, CI-Pipeline und Entwicklerdokumentation
Dieser Meilenstein überführt das entwickelte Framework in einen qualitätsgesicherten Zustand.
- Aufbau der Test-Suite: Es wird eine umfassende Suite von automatisierten Unit- und Integrationstests (gemäß PH: 9.2.1) für alle bis einschließlich Version 0.5.0 implementierten Kernkomponenten (Routing, Session, i18n etc.) geschrieben.
- Einrichtung der CI-Pipeline: Die in PH: 9.2.2 definierte Continuous-Integration-Pipeline wird aufgesetzt. Ab diesem Zeitpunkt wird jeder Commit in das Repository automatisch auf die Einhaltung der Coding Guidelines und das erfolgreiche Durchlaufen aller Tests überprüft.
- Erstellung der Basis-Dokumentation: Eine erste Version der technischen Entwicklerdokumentation wird verfasst. Sie muss mindestens die Installation, die Konfiguration des Frameworks (inkl. der .env-Datei) und die grundlegende Verwendung der Kern-Services beschreiben.

Nach Abschluss dieses Meilensteins ist das Basisframework nicht nur funktional implementiert, sondern auch durch automatisierte Tests validiert, durch einen CI-Prozess geschützt und für Entwickler verständlich dokumentiert.

Version 0.9.0: Stabilisierung und Abnahme (Release Candidate)
Mit diesem Meilenstein wird das Basisframework als "feature-complete" deklariert. Es werden ab diesem Punkt keine neuen Funktionen mehr hinzugefügt. Der Fokus liegt ausschließlich auf der Stabilisierung und der Vorbereitung des finalen Releases.
- Bereitstellung zur Abnahme: Die Version 0.9.0 wird auf der Abnahmeumgebung (Staging-System) bereitgestellt.
- Formale Abnahmetests: Der Auftraggeber führt die formalen Abnahmetests gemäß der in PH: 10.1 definierten Kriterien durch. Alle gefundenen Fehler werden im Bugtracker (Mantis) erfasst.
- Bugfixing-Phase: Alle gemeldeten Fehler werden behoben. Jede Fehlerbehebung führt zu einer neuen Patch-Version (z.B. 0.9.1, 0.9.2).

Dieser Meilenstein ist erst dann abgeschlossen, wenn eine Version existiert, in der alle bekannten Fehler behoben sind und die die formale Freigabe durch den Auftraggeber erhält. Diese finale, fehlerfreie 0.9.x-Version ist die direkte Vorstufe zur Version 1.0.0.

### 11.3. Zuordnung der Pflichtenheft-Punkte zu Meilensteinen
Die folgende Aufstellung dient als verbindliche Checkliste ("Definition of Done") für jeden Meilenstein der Roadmap. Ein Meilenstein gilt erst dann als erreicht, wenn alle ihm zugeordneten Punkte aus dem Pflichtenheft nachweislich implementiert, getestet und (falls zutreffend) dokumentiert sind.

Meilenstein 0.1.0: Grundgerüst und Rendering-Pipeline
- PH: 4.1.1 (Seiten-Rendering und Template-Management) - Vollständige Implementierung.
- PH: 4.1.6 (SEO-Grundlagen) - Implementierung der Teile, die für das grundlegende Seiten-Rendering notwendig sind: Meta-Tag-Management für title und description sowie Canonical URLs.
- PH: 5.5 (Usability für Endnutzer) - Umsetzung der Teile, die das grundlegende HTML-Gerüst betreffen: Responsives Design (Basis-Layout) und semantisches HTML.
- PH: 7.2 (Barrierefreiheit) - Umsetzung der Teile, die das grundlegende HTML-Gerüst betreffen: Semantisches HTML, ARIA-Landmarks. Der von Ihnen gemeldete Bug "fehlendes title-Element" muss hier behoben werden.

Meilenstein 0.2.0: Sichere Session-Verwaltung und Konfiguration
- PH: 4.1.2 (Sichere Session-Verwaltung) - Vollständige Implementierung.
- PH: 4.1.5 (Sicherheitsgrundlagen) - Implementierung des CSRF-Token-Mechanismus, der im Session-Service verankert ist.
- PH: 5.2 (Sicherheit) - Umsetzung des Punkts "Session Management".
- PH: 5.5 (Usability für Entwickler) - Implementierung des Punkts "Einfache Konfiguration", da hier das grundlegende Konfigurationssystem eingeführt wird.

Meilenstein 0.3.0: DSGVO- und Compliance-Mechanismen
- PH: 4.1.4 (DSGVO- und Compliance-Mechanismen) - Vollständige Implementierung.
- PH: 8.1 (Gesetzliche und Regulatorische Anforderungen) - Technische Umsetzung des Punkts "Datenschutz".

Meilenstein 0.4.0: Internationalisierung (i18n)
- PH: 4.1.3 (Internationalisierung) - Vollständige Implementierung.

Meilenstein 0.5.0: Umgebungs-Handling, Logging und Sicherheits-Härtung
- PH: 8.4 (Umgebungs-spezifische Konfiguration) - Vollständige Implementierung.
- PH: 5.2 (Sicherheit) - Umsetzung des Punkts "Transportverschlüsselung und aktive Erzwingung".
- PH: 4.1.5 (Sicherheitsgrundlagen) - Implementierung des Punkts "einheitliches Logging mit Tracy".

Meilenstein 0.6.0: Standard-Layout und atomare CSS
Ziel
Dieser Meilenstein implementiert ein produktivfertiges, visuell ansprechendes Standard-Design für das Nexus-Framework. Das Layout ist responsiv, barrierefrei, SEO-optimiert und dient als Grundlage für alle zukünftigen Erweiterungsmodule. Die UI-Komponenten werden in der folgenden Reihenfolge umgesetzt: Header, Subheader, Content, Footer, Cookie-Banner.
Die technische Umsetzung erfolgt mit atomarem CSS, das für den Go-Live gemerged werden kann. Die Stylesheets sind modular entwickelt und werden in der Produktivumgebung zu einer optimierten main.css gebündelt. Eine public/manifest.json wird für die Verwaltung der zu minifizierenden CSS- und JavaScript-Dateien genutzt.

Spezifikation
1. UI-Komponenten
1.1 Header
- Struktur: 
  - Logo-Bereich (links, skalierbar, verlinkt zur Startseite).
  - Light-/Darkmode-Umschalter (Custom-Design, serverseitig gespeichert).
  - Sprachauswahl-Dropdown (i18n-Komponente, Browser-Sprache als Fallback).
  - Platzhalter für "Registrieren/Login" (nicht funktional, bei Login ausgeblendet).
- Verhalten: 
  - Sticky, responsiv (Hamburger-Menü auf kleinen Bildschirmen).
1.2 Subheader (Navigation)
- Struktur: 
  - Position: Direkt unter dem Header, volle Breite.
  - Navigationselemente als Browsertabs, mit Dropdown-Unterstützung.
- Verhalten: 
  - Sticky, responsiv (Scrollpfeile bei vielen Elementen).
1.3 Content-Bereich
- Struktur: 
  - Vollbild, lesbare Breite (900-1200px auf großen Bildschirmen).
  - Neutrale Hintergrundfarbe, visuelle Abgrenzung durch Akzentfarbe.
  - Beispiel-Elemente: Cards, Überschriften, Rasterlayout.
- SEO: 
  - Dynamische Meta-Tags (<title>, <meta name="description">).
  - Automatische Canonical URLs (rel="canonical").
  - Semantisches HTML (<main>, <section>, <article>).
1.4 Footer
- Struktur: 
  - Copyright-Hinweis, Impressum-Link, Datenschutz-Link, Sitemap, Social-Media-Icons.
  - Visuelle Abgrenzung durch Hintergrundfarbe/Rahmen.
- Verhalten: 
  - Responsive (gestapelt auf kleinen Bildschirmen).
1.5 Cookie-Banner
- Struktur: 
  - Zentral, modal, mit Text zur Cookie-Nutzung und Buttons ("Zustimmen", "Ablehnen").
- Verhalten: 
  - Blockierend (Hintergrund ausgeblendet/ausgeblurrt).

2. CSS- und JavaScript-Struktur
- Entwicklungsumgebung: 
  - Modular: Jede Komponente hat eigene CSS/JS-Dateien (z. B. header.css, cookie-banner.js).
  - Kein Präprozessor: modernes, natives CSS für Variablen/Mixins, BEM für Klassennamen.
- Produktivumgebung: 
  - Bündelung: Alle CSS/JS-Dateien werden zu main.css/main.js minifiziert.
  - Manifest: public/manifest.json listet atomare CSS/JS-Dateien für Vite/Webpack:

3. Barrierefreiheit (WCAG 2.1 AA)
- Arbeitsauftrag: 
  - Testen mit axe DevTools und WAVE: 
   1. Überprüfung der Tastaturbedienbarkeit.
   2. Validierung der Farbkontraste (4.5:1 für Text, 3:1 für großen Text).
   3. Prüfung der ARIA-Labels und semantischen HTML-Struktur.

4. SEO-Validierung
- Arbeitsauftrag: 
  - Testen mit Google Lighthouse und Screaming Frog: 
   1. Überprüfung der Meta-Tags und Canonical URLs.
   2. Validierung der semantischen Struktur und Ladeperformance.
   3. Analyse der robots.txt und Indexierbarkeit.

Definition of Done
-  Alle UI-Komponenten sind vollständig implementiert und getestet.
-  Layout ist responsiv, barrierefrei (WCAG 2.1 AA) und SEO-optimiert.
-  CSS/JS sind modular (natives CSS + BEM) und in manifest.json für Vite/Webpack aufgelistet.
-  main.css/main.js sind minifiziert und produktivfertig.
-  Barrierefreiheit und SEO wurden mit axe DevTools, WAVE, Lighthouse und Screaming Frog validiert.

Meilenstein 0.7.0: Volle Funktionalität der Standardseiten
Ziel
Dieser Meilenstein bringt das Standard-Template auf Produktiv-Stand um als Live-Landingpage auf www.exelor.de veröffentlicht zu werden. Danach erfolgt der Rückbau auf „Werkseinstellung“ zur Auslieferung an Kunden.
- Ausblenden nicht aktivierter Module (z. B. Userverwaltung, Sitesuche) im Header und Footer.
- Anlage aller benötigten Standardseiten (Impressum, Datenschutzerklärung) mit i18n-konformen Texten in de.php.
- Implementierung einer Admin-Seite (/public/admin/), die über ein Datei-basiertes Login (.env) geschützt ist.
- Konfigurierbarkeit von Modulen über Checkboxen in der Admin-Seite.
- Erstellung temporärer Dummy-Seiten für Demonstrationszwecke, die über die Navbar erreichbar sind.
- Automatisierte Generierung einer SEO-optimierten Sitemap bei der Erstellung neuer Seiten (Dummy oder echt).

Spezifikation
1. Standardseiten auf Produktiv-Stand bringen
1.1 Ausblenden nicht aktivierter Module
- Betroffene Komponenten: 
  - Header: Links für "Registrieren/Login" werden ausgeblendet, falls die Userverwaltung nicht aktiviert ist.
  - Header: Suchfeld wird ausgeblendet, falls die Sitesuche nicht aktiviert ist.
  - Footer: Social-Media-Icons werden ausgeblendet, falls keine Links hinterlegt sind.
1.2 Anlage benötigter Standardseiten
- Impressum (/impressum): 
  - Statische Seite mit i18n-konformen Platzhaltern für rechtliche Angaben (Adresse, Kontaktdaten) in de.php.
- Datenschutzerklärung (/datenschutz): 
  - Statische Seite mit i18n-konformen Platzhaltern für Datenschutzhinweise (Cookie-Nutzung, Tracking, Kontaktdaten) in de.php.
  - Verlinkung im Footer.
- Behandlung ungültiger Seitenaufrufe: 
  - Statt einer 404-Seite wird eine elegante Lösung implementiert: 
   1. Ein Hinweis im Content-Bereich informiert den Benutzer, dass die gewünschte Seite nicht gefunden wurde.
   2. Der Benutzer wird automatisch zur Startseite weitergeleitet.

2. Admin-Seite (/public/admin/)

2.1 Datei-basiertes Login
- Login-Formular: 
  - Eingabefelder für Benutzername und Passwort.
  - Prüfung der Anmeldedaten gegen die .env-Datei (ADMIN_USER und ADMIN_PASSWORD_HASH).
  - Sicherheitsmaßnahmen: 
   1. Passwort wird gehasht in der .env gespeichert (password_hash).
   2. Session-basierte Authentifizierung nach erfolgreicher Anmeldung.
   3. CSRF-Schutz für das Login-Formular.
- Zugangsschutz: 
  - Die Admin-Seite ist nur über /public/admin/ erreichbar.
  - 403-Fehler, falls nicht autorisiert.

2.2 Konfiguration von Modulen
- Checkbox-basierte Aktivierung/Deaktivierung von Modulen: 
  - Userverwaltung: 
   1. Checkbox: "Userverwaltung aktivieren".
   2. Effekt: Zeigt/blendet die Links "Registrieren/Login" im Header ein/aus.
  - Sitesuche: 
   1. Checkbox: "Sitesuche aktivieren".
   2. Effekt: Blendet das Suchfeld im Header ein/aus.
  - Cookie-Banner: 
   1. Checkbox: "Cookie-Banner aktivieren".
   2. Effekt: Zeigt/blendet das Cookie-Banner ein/aus.
  - Sprachauswahl: 
   1. Checkbox: "Sprachauswahl aktivieren".
   2. Effekt: Zeigt/blendet das Sprachauswahl-Dropdown im Header ein/aus.

2.3 Erstellung temporärer Dummy-Seiten
- Funktionalität: 
  - Formular zur Erstellung neuer Seiten: 
   1. Eingabefelder für Seitentitel, URL-Slug (z. B. /demo-seite) und Inhalt (HTML-Textarea).
   2. Speicherung: Die Seiten werden temporär in einer Datei (z. B. public/pages/{slug}.html) abgelegt.
  - Anzeige in der Navbar: 
   1. Neue Seiten werden automatisch als Link in der Navbar eingebunden.
  - Hinweis: 
   1. Seiten sind nur temporär und werden nicht in einer Datenbank gespeichert.
   2. Dienen ausschließlich Demonstrationszwecken.

3. Automatisierte Sitemap-Generierung für SEO
- Ziel: Automatische Aktualisierung der sitemap.xml bei der Erstellung neuer Seiten (Dummy oder echt) nach den auf https://www.sitemaps.org/protocol.html definierten Standards.
- Implementierung: 
  - Trigger: 
   1. Die Sitemap wird automatisch generiert/aktualisiert, wenn eine neue Seite erstellt wird (über Admin-Seite oder manuell).
  - Inhalte der Sitemap: 
   1. Statische Seiten (Startseite, Impressum, Datenschutz).
   2. Dummy-Seiten (temporäre Seiten aus /public/pages/).
  - Priorisierung: 
   1. Startseite: priority="1.0".
   2. Wichtige Seiten (Impressum, Datenschutz): priority="0.8".
   3. Dummy-Seiten: priority="0.5".
  - Aktualisierungsfrequenz: changefreq="weekly" (Standardwert).
- Technische Umsetzung: 
  - PHP-Skript (/public/generate_sitemap.php): 
  - Durchsucht das Verzeichnis /public/pages/ nach neuen Seiten.
  - Liest die URL-Slugs aus den Dateinamen (z. B. demo-seite.html → /demo-seite).
  - Generiert eine XML-Struktur gemäß dem Sitemap-Protokoll.
  - Speichert die sitemap.xml im Root-Verzeichnis (/public/sitemap.xml).
- Automatisierung: 
  - Das Skript wird automatisch aufgerufen, wenn eine neue Seite erstellt wird (z. B. über einen Hook in der Admin-Seite).
  - Alternativ: Manueller Aufruf über die Admin-Seite (Button "Sitemap neu generieren").

4. Technische Umsetzung

4.1 Dateistruktur
- Admin-Seite: 
  - /public/admin/: 
   1. index.php (Login-Formular und Admin-Dashboard).
   2. config.php (Liest .env-Daten und verwaltet Konfigurationen).
- Dummy-Seiten: 
  - /public/pages/: 
   1. {slug}.html (Temporäre HTML-Dateien für Dummy-Seiten).
- Sitemap-Generierung: 
  - /public/generate_sitemap.php (Skript zur Generierung der sitemap.xml).
  - /public/sitemap.xml (Generierte Sitemap).
4.2 .env-Konfiguration
- Beispiel: ADMIN_USER=admin ADMIN_PASSWORD_HASH=$2y$10$..
4.3 Session-Handling
- Login-Session: 
  - Nach erfolgreicher Anmeldung wird eine Session-Variable gesetzt, die das User|Gruppe|Rolle-Konzept berücksichtigt.
  - Session-Lifetime: 30 Minuten Inaktivität.
4.4 Behandlung ungültiger Seitenaufrufe
- Statt einer 404-Seite wird eine elegante Lösung implementiert: 
  - Ein Hinweis im Content-Bereich informiert den Benutzer, dass die gewünschte Seite nicht gefunden wurde.
  - Der Benutzer wird automatisch zur Startseite weitergeleitet.
5. Abhängigkeiten
- v0.6.0: Standard-Layout und UI-Komponenten.
- v0.4.0: i18n für mehrsprachige Standardseiten.
- v0.5.0: Umgebungs-Handling für .env-Konfiguration.

Definition of Done
- Alle nicht aktivierten Module sind ausgeblendet.
- Standardseiten (Impressum, Datenschutzerklärung) sind angelegt, i18n-konform und verlinkt.
- Admin-Seite (/public/admin/) ist implementiert: 
  - Datei-basiertes Login mit .env-Prüfung.
  - Konfiguration von Modulen über Checkboxen.
  - Erstellung temporärer Dummy-Seiten.
- Dummy-Seiten sind über die Navbar erreichbar.
- sitemap.xml wird automatisch generiert/aktualisiert bei der Erstellung neuer Seiten.
- Ungültige Seitenaufrufe werden elegant behandelt.
- Sicherheitsmaßnahmen (CSRF, Session-Handling) sind umgesetzt.
- Dokumentation der Admin-Funktionalitäten ist aktualisiert.

Meilenstein 0.8.0: Test-Suite, CI-Pipeline und Entwicklerdokumentation
- PH: 9.2.1 (Teststrategie, Testarten) - Vollständige Umsetzung: Die Test-Suite für alle bisherigen Features muss existieren und erfolgreich laufen.
- PH: 9.2.2 (Build- und Deployment-Automatisierung) - Vollständige Umsetzung: Die CI-Pipeline muss aufgesetzt sein und bei jedem Commit laufen.
- PH: 12.1 (Dokumentations-Artefakte) - Die Basis-Version der Entwicklerdoku muss erstellt sein.
- PH: 5.4 (Wartbarkeit) - Die Erfüllung dieses Meilensteins ist der wichtigste Nachweis für die Wartbarkeit des Systems.

Meilenstein 0.9.0: Stabilisierung und Abnahme
- PH: 10.1 (Formale Abnahmekriterien) - Der Prozess der Abnahme wird hier gestartet und durchgeführt.
- PH: 9.1.3 (Bugtracker) - Die Regel "keine bekannten Bugs" wird hier final überprüft und durchgesetzt.

Meilenstein 1.0.0: Produktivversion
Stellt die erste Version dar die:
- Alle geforderten Funktionalitäten enthält
- Frei von Bekannten Bug ist
- Vom Auftraggeber abgenommen ist

---