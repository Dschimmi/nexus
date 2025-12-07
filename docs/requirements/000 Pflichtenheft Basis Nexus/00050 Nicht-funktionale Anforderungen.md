## 5. Nicht-funktionale Anforderungen
`(Konkretisierung der Anforderungen an Performance, Sicherheit, Skalierbarkeit, Wartbarkeit und Usability.)`

Dieses Kapitel konkretisiert die qualitativen und betrieblichen Anforderungen an das "Nexus"-Framework. Diese Anforderungen sind für alle Komponenten des Basisframeworks sowie für alle offiziellen Erweiterungs- und Spezialmodule verbindlich.

### 5.1. Leistungsanforderungen (Performance)
Spezifikation:
Das Framework muss durchgängig schnelle Ladezeiten und effiziente serverseitige Verarbeitungszeiten gewährleisten. Die folgenden Kennzahlen (KPIs) sind als verbindliche Ziele für den Produktivbetrieb definiert:
- Serverseitige Performance: Die durchschnittliche Server-Antwortzeit (Time To First Byte, TTFB) für eine dynamisch generierte, nicht gecachte Seite darf 500 Millisekunden nicht überschreiten.
- Client-seitige Performance: Alle im Frontend generierten Seiten müssen die Google Core Web Vitals im "grünen Bereich" erfüllen. Insbesondere ist auf einen Largest Contentful Paint (LCP) von unter 2,5 Sekunden zu achten.
- Datenbank-Effizienz: Das Rendern einer typischen Detailseite (z.B. ein Foren-Thread) darf im un-gecachten Zustand nicht mehr als 20 SQL-Abfragen an die Datenbank auslösen.
- Asset-Optimierung: Alle CSS- und JavaScript-Dateien müssen für den Produktionsbetrieb automatisiert minifiziert und gebündelt werden, um die Anzahl der HTTP-Anfragen zu minimieren.
- Caching: Das System muss die in den Coding Guidelines (PH: 9.1.1.13) definierten Caching-Ebenen (Opcode- und Application-Caching) vollumfänglich unterstützen. Zusätzlich müssen sinnvolle HTTP-Caching-Header (z.B. Cache-Control, ETag) gesetzt werden, um Browser-Caching zu ermöglichen.

Begründung:
Eine hohe Performance ist entscheidend für die Benutzerakzeptanz (User Experience) und die Suchmaschinenplatzierung (SEO). Durch die Festlegung von messbaren KPIs wird die Performance zu einem festen Qualitätskriterium im Entwicklungsprozess und stellt sicher, dass die Anforderungen aus dem Lastenheft (LH: S. 4) "schnelle Ladezeiten und effiziente Ablaufzeiten" erfüllt werden.

### 5.2. Sicherheit
Spezifikation:
Das Framework muss nach dem Prinzip der "mehrschichtigen Sicherheit" (Defense in Depth) entworfen und implementiert werden. Es muss einen robusten Schutz gegen die gängigsten Web-Angriffe (OWASP Top 10) bieten. Die folgenden Punkte sind verbindlich:
- Grundschutz: Die im Basisframework definierten Schutzmechanismen gegen XSS (durch Twig Auto-Escaping), SQL-Injection (durch reines PDO mit Prepared Statements) und CSRF (durch automatische Token-Generierung) sind fundamental und dürfen nicht umgangen werden (siehe PH: 4.1.4, PH: 4.1.5, PH: 4.2.2).
- Authentifizierung und Autorisierung: Die Passwort-Speicherung muss ausschließlich mit dem PASSWORD_ARGON2ID-Algorithmus erfolgen (PH: 4.2.1). Für die Zugriffskontrolle gilt das Prinzip "Deny by Default": Zugriff auf geschützte Ressourcen muss explizit gewährt werden; alles, was nicht explizit erlaubt ist, ist verboten.
- Session Management: Das Session-Management muss vollumfänglich gemäß der technischen Spezifikation in PH: 4.1.2 (Sichere Session-Verwaltung) implementiert werden. Die dort definierten Anforderungen an Entropie, Cookie-Konfiguration (Flags, Präfixe), Storage-Abstraktion und Lebenszyklus-Methoden (Migration, Invalidation) gelten als verbindliche Sicherheitsstandards für die Abnahme.
- Transportverschlüsselung und aktive Erzwingung: Jede Kommunikation zwischen dem Client (Browser) und dem Server muss ausnahmslos über HTTPS erfolgen.
  - Zur technischen Sicherstellung dieser Anforderung implementiert das Framework im zentralen Einstiegspunkt (Bootstrap-Prozess) eine aktive Überprüfung:
  - Wenn sich die Anwendung im Produktionsmodus befindet (definiert durch die Environment-Variable APP_ENV=production), wird bei jeder eingehenden Anfrage geprüft, ob die Verbindung über HTTPS erfolgt.
  - Ist die Verbindung nicht sicher, bricht das Framework die Ausführung sofort und vollständig ab und gibt eine eindeutige, statische Fehlermeldung aus (z.B. "Sicherheitsfehler: Der Betrieb dieser Anwendung im Produktionsmodus ist ausschließlich über eine gesicherte HTTPS-Verbindung zulässig.").
  - Diese Überprüfung ist im Entwicklungsmodus (APP_ENV=development) deaktiviert, um die lokale Entwicklung nicht zu beeinträchtigen.
- Sicherheitsupdates und Schwachstellen-Management: Alle externen Abhängigkeiten (via Composer) müssen regelmäßig auf bekannte Sicherheitslücken überprüft werden (z.B. mittels composer audit). Kritische Sicherheitsupdates für Abhängigkeiten oder das Framework selbst müssen zeitnah eingespielt werden.
- Secret Management: Sensible Daten wie Datenbank-Passwörter, API-Schlüssel oder private Schlüssel dürfen unter keinen Umständen im Git-Repository versioniert werden. Sie müssen strikt über Environment-Variablen verwaltet werden, wie in der Deployment-Automatisierung (PH: 9.2.2) vorgesehen.

### 5.3. Skalierbarkeit
Spezifikation:
Die Architektur des Frameworks muss so gestaltet sein, dass sie mit steigenden Nutzerzahlen und Datenmengen wachsen kann, ohne dass ein grundlegender Umbau der Anwendung erforderlich ist. Dies wird durch die folgenden Prinzipien sichergestellt:
- Horizontale Skalierbarkeit (Stateless Application): Das Framework muss als "stateless" Anwendung konzipiert sein. Das bedeutet, dass keine benutzerspezifischen Zustandsinformationen (wie Sessions oder temporäre Dateien) auf dem lokalen Dateisystem des Webservers gespeichert werden dürfen. Jede Anfrage eines Benutzers kann von jedem beliebigen Server in einem Load-Balancing-Verbund verarbeitet werden.
- Zentralisierte Zustandsverwaltung:
  - Sessions: Das Session-Handling muss so konfigurierbar sein, dass der Speicherort für Session-Daten auf ein zentrales, von mehreren Servern erreichbares System (z.B. eine Redis-Instanz oder eine Datenbanktabelle) ausgelagert werden kann.
  - Dateiuploads: Von Benutzern hochgeladene Dateien müssen auf einem geteilten Speicher (z.B. NFS-Share oder einem S3-kompatiblen Object Storage) abgelegt werden.
- Lose Kopplung: Die in Kapitel 3 definierte modulare und dienstorientierte Architektur unterstützt die Skalierbarkeit, da ressourcenintensive Aufgaben (z.B. PDF-Generierung, E-Mail-Versand) in dedizierte Hintergrund-Jobs (Queues) ausgelagert werden können, die auf separaten Servern skalieren.

Begründung:
Diese architektonischen Vorgaben sind die technische Voraussetzung, um die im Lastenheft (LH: S. 4) geforderte horizontale und vertikale Skalierbarkeit zu gewährleisten. Sie stellen sicher, dass die Anwendung auch bei hohem Nutzeraufkommen stabil und performant bleibt, indem die Last auf mehrere Serversysteme verteilt werden kann ("Load-Balancing").

### 5.4. Wartbarkeit
Spezifikation:
Die langfristige und kosteneffiziente Pflege und Weiterentwicklung des Frameworks und seiner Module muss durch strukturelle und prozessuale Vorgaben sichergestellt werden. Die Wartbarkeit wird nicht als nachträgliche Maßnahme, sondern als grundlegendes Designprinzip verstanden. Folgende Punkte sind verbindlich:
- Code-Qualität und Konsistenz: Der gesamte Code muss den in den Coding Guidelines (PH: 9.1.1) definierten Standards entsprechen. Die Einhaltung wird durch die CI/CD-Pipeline (PH: 9.2.2) automatisiert überprüft.
- Modulare und entkoppelte Architektur: Die strikte Einhaltung der in Kapitel 3.2 definierten Hexagonalen Architektur ist verpflichtend. Dies stellt sicher, dass Komponenten isoliert voneinander geändert oder ausgetauscht werden können, ohne unvorhergesehene Seiteneffekte im Gesamtsystem zu verursachen.
- Testbarkeit: Jede neue Funktion und jeder Bugfix muss durch automatisierte Tests (Unit- oder Integrationstests) abgedeckt sein, wie in der Teststrategie (PH: 9.2.1) festgelegt. Dies dient als Sicherheitsnetz, um Regressionen bei zukünftigen Änderungen zu verhindern.
- Dokumentation: Der Code muss den Dokumentationsstandards (PH: 9.1.1.11) genügen. Wichtige Architekturentscheidungen müssen als Architecture Decision Records (ADRs) festgehalten werden, um die Nachvollziehbarkeit für zukünftige Entwickler zu gewährleisten.
- Standardisierte Prozesse: Die Versionierung des Frameworks (PH: 9.1.2) und der Deployment-Prozess (PH: 9.2.2) sind standardisiert. Dies stellt sicher, dass der Zustand jeder Version reproduzierbar ist und Updates kontrolliert und sicher eingespielt werden können.

Begründung:
Diese Maßnahmen stellen die praktische Umsetzung der im Lastenheft (LH: S. 4) formulierten Anforderung nach einem "sauberen, gut dokumentierten und modularen Code" sicher. Die Wartbarkeit wird hier als Ergebnis der konsequenten Anwendung von Code-Qualitäts-, Test-, Architektur- und Prozess-Standards definiert. Dies senkt die Total Cost of Ownership (TCO) und ermöglicht eine agile und risikoarme Weiterentwicklung des Systems.

### 5.5. Usability (Benutzerfreundlichkeit)
Spezifikation:
Die Usability des "Nexus"-Frameworks bezieht sich auf zwei Zielgruppen: die Entwickler, die mit dem Framework arbeiten, und die Endnutzer, die die darauf basierenden Anwendungen verwenden.

- Für Entwickler (Developer Experience):
  - Klare Schnittstellen (APIs): Alle öffentlichen Methoden und Services des Frameworks und seiner Module müssen klar benannt, konsistent und über DocBlocks (PH: 9.1.1.11) dokumentiert sein.
  - Gute Debugging-Unterstützung: Das Framework muss im Entwicklungsmodus aussagekräftige Fehlermeldungen und Debuginformationen über das zentrale Werkzeug Tracy bereitstellen.
  - Umfassende Dokumentation: Eine zentrale technische Dokumentation muss die Architektur, die Kernkonzepte und Anwendungsbeispiele für die wichtigsten Funktionen beschreiben.
  - Einfache Konfiguration: Die Konfiguration des Frameworks und seiner Module muss über gut strukturierte und kommentierte Konfigurationsdateien erfolgen.

- Für Endnutzer:
  - Responsive Design: Alle von den offiziellen Modulen generierten Frontend-Ausgaben (Views) müssen standardmäßig responsiv sein und auf gängigen Bildschirmgrößen (Desktop, Tablet, Mobiltelefon) eine optimale Darstellung und Bedienbarkeit gewährleisten.
  - Barrierefreiheit (Accessibility): Die generierten HTML-Strukturen müssen semantisch korrekt sein und den grundlegenden Anforderungen der WCAG 2.1 (Level AA) genügen. Dies umfasst unter anderem die korrekte Verwendung von alt-Attributen für Bilder, die Kennzeichnung von Formularfeldern mit label-Elementen und die Sicherstellung eines ausreichenden Farbkontrasts. Alle Feedback-Mechanismen müssen auch für Screenreader und andere assistive Technologien zugänglich sein.
  - Konsistente Bedienung und Anordnung: Wiederkehrende Elemente wie Buttons, Formulare und Navigationselemente müssen über alle Module hinweg ein konsistentes Aussehen und Verhalten aufweisen. Soweit es der Kontext erlaubt, sollen diese Elemente auch in exakt gleicher Größe und an der exakt selben Position auf dem Bildschirm erscheinen, um die Erwartungskonformität zu maximieren.
  - Umfassendes System-Feedback ("Feedback is King"): Der Nutzer darf zu keinem Zeitpunkt im Unklaren über den Systemstatus gelassen werden. "Stille" Prozesse, bei denen der Nutzer keine Rückmeldung über die Aktivität des Systems erhält, sind nicht zulässig.
   1. Ladevorgänge und Aktionen: Jede Aktion, die eine spürbare Lade- oder Verarbeitungszeit benötigt, muss durch einen visuellen Ladeindikator (z.B. Spinner, Fortschrittsbalken) begleitet werden.
   2. Erfolgs- und Fehlermeldungen: Das Ergebnis einer Nutzeraktion (Erfolg, Fehler, Warnung) muss dem Nutzer sofort durch eine klare, visuelle und textuelle Rückmeldung (z.B. Toast-Meldungen) mitgeteilt werden.
   3. Technische Umsetzung: Für eine konsistente Darstellung müssen standardisierte Frontend-UI-Komponenten (z.B. für Ladeindikatoren, Toast-Meldungen) verwendet werden. API-Antworten des Backends an das Frontend müssen strukturierte Statusinformationen enthalten (z.B. {"status": "success", "message": "Profil erfolgreich gespeichert."}).
  - Interaktionshinweise: Die Verwendung einer permanenten Status- oder Informationszeile am unteren Bildschirmrand ist untersagt. Kontextbezogene Hinweise für den Benutzer (z.B. Tooltips, kurze Erklärungen) müssen als temporäres Pop-up direkt am Mauszeiger oder am auslösenden UI-Element erscheinen.

Begründung:
Eine hohe Usability für Entwickler senkt die Einarbeitungszeit und erhöht die Entwicklungsgeschwindigkeit. Die Sicherstellung einer hohen Usability für Endnutzer durch responsives, barrierefreies, konsistentes und feedback-reiches Design ist eine Kernanforderung aus dem Lastenheft (LH: S. 4) und entscheidend für den Erfolg, die Akzeptanz und die Reichweite der auf "Nexus" basierenden Webprojekte.

---