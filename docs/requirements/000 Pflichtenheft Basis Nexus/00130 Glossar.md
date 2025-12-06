## 13. Glossar

Dieses Kapitel definiert zentrale Fachbegriffe, Abkürzungen und projektspezifische Bezeichnungen, die im Rahmen dieses Pflichtenhefts und im Projektverlauf verwendet werden. Das Glossar dient dazu, ein einheitliches Vokabular und ein gemeinsames Verständnis bei allen Projektbeteiligten sicherzustellen und Missverständnisse zu vermeiden. Es wird bei Bedarf im Laufe des Projekts erweitert.

Abnahmekriterien:
Ein in Kapitel PH: 10.1 definierter Katalog von Anforderungen, die nachweislich erfüllt sein müssen, damit die entwickelte Software als fertiggestellt gilt und vom Auftraggeber formal abgenommen werden kann.

ADR (Architecture Decision Record):
Ein Dokument, das eine wichtige Architekturentscheidung, deren Kontext und Konsequenzen festhält. Dient der Nachvollziehbarkeit des "Warums" hinter technischen Festlegungen.

Anti-Corruption Layer:
Ein Architekturmuster, bei dem eine isolierende Schicht zwischen zwei Systemen geschaffen wird. Diese Schicht übersetzt die Datenmodelle und Kommunikationsmuster, um das eigene System (Nexus) vor inkonsistenten oder unpassenden Datenstrukturen des externen Systems zu schützen. Umgesetzt in Kapitel PH: 6.2.

Application-Schicht:
Eine der vier zentralen Schichten der Hexagonalen Architektur. Sie orchestriert die Domain-Logik, um konkrete Anwendungsfälle (Use Cases) abzubilden. Sie enthält keine Business-Regeln, sondern definiert den Ablauf.

Barrierefreiheit (Accessibility):
Die Gestaltung von Webanwendungen so, dass sie von allen Menschen, einschließlich solcher mit Behinderungen, uneingeschränkt genutzt werden können. Im Projekt durch die Einhaltung der WCAG 2.1 AA Standards (PH: 7.2) definiert.

Build-Artefakt:
Das Ergebnis eines automatisierten Build-Prozesses. Es ist ein sauberes, in sich geschlossenes Paket der Anwendung, das alle für den Betrieb notwendigen Code- und Asset-Dateien enthält und bereit für das Deployment auf einem Server ist.

CI/CD (Continuous Integration / Continuous Deployment):
Ein automatisierter Prozess (Pipeline) in der Softwareentwicklung, der sicherstellt, dass Code-Änderungen kontinuierlich getestet (CI) und anschließend automatisiert auf Servern bereitgestellt (CD) werden. Definiert in PH: 9.2.2.

Composer:
Der De-facto-Standard für das Management von Abhängigkeiten (Bibliotheken, Komponenten) im PHP-Ökosystem.

Core Application Scaffolding:
Die Bezeichnung für das Kernprodukt dieses Projekts (Nexus-Base Framework). Es ist ein stabiles, sicheres und erweiterbares Grundgerüst für Webanwendungen, das bewusst keine anwendungsspezifische Logik wie eine Userverwaltung enthält.

DDD (Domain-Driven Design):
Ein Ansatz zur Softwareentwicklung, der sich auf die Modellierung der Software an der Fachlichkeit (Domäne) orientiert. Dient als Leitprinzip für die Architektur von "Nexus".

DI (Dependency Injection):
Ein Entwurfsmuster, bei dem ein Objekt seine Abhängigkeiten von einer externen Quelle (dem DI-Container) erhält, anstatt sie selbst zu erstellen.

Domain-Schicht:
Der technologisch unabhängige Kern der Hexagonalen Architektur. Enthält die reine Business-Logik (Entities, Value Objects) und die Geschäftsregeln des Projekts.

Environment-Variable:
Eine Variable, deren Wert außerhalb des Programmcodes in der Betriebsumgebung (z.B. auf dem Server) gesetzt wird. Dient zur Konfiguration der Anwendung (z.B. Datenbank-Passwörter, APP_ENV).

.env-Datei:
Eine Textdatei, die Environment-Variablen für die lokale Entwicklungsumgebung definiert. Sie darf niemals in die Versionskontrolle (Git) eingecheckt werden.

Fail-Safe-Prinzip:
Ein Sicherheitsgrundsatz, der besagt, dass ein System bei einem Fehler oder einer fehlenden Konfiguration immer in den sichersten möglichen Zustand zurückfallen muss. Für Nexus bedeutet dies, dass bei einer nicht definierten APP_ENV-Variable immer der production-Modus angenommen wird (PH: 8.4).

Feature Freeze:
Ein Zeitpunkt im Entwicklungszyklus, ab dem keine neuen Funktionen mehr zum Projekt hinzugefügt werden. Der Fokus liegt danach ausschließlich auf der Fehlerbehebung und Stabilisierung. Markiert im Projekt den Übergang zu Version 0.9.0.

Hexagonale Architektur (Ports & Adapters):
Ein Architekturmuster, das den Anwendungskern strikt von der Außenwelt (UI, Datenbank, APIs) isoliert. Dies ermöglicht die Austauschbarkeit von Infrastrukturkomponenten und eine hohe Testbarkeit.

Infrastructure-Schicht:
Eine Schicht der Hexagonalen Architektur, die die konkreten technischen Implementierungen für externe Abhängigkeiten enthält (z.B. Datenbank-Repositories, die PDO nutzen).

Internationalisierung (i18n):
Der Prozess, eine Software so zu gestalten, dass sie einfach an verschiedene Sprachen und regionale Eigenheiten angepasst werden kann, ohne dass der Code geändert werden muss.

Lastenheft (LH):
Das Dokument, das die Gesamtheit der Anforderungen des Auftraggebers an ein Projekt beschreibt ("Was" soll getan werden?). Es ist die Grundlage für das Pflichtenheft.

Nexus-Base Framework:
Der alleinige und zentrale Liefergegenstand dieses Projekts in der Version 1.0.0. Siehe auch "Core Application Scaffolding".

Pflichtenheft (PH):
Dieses Dokument. Es beschreibt auf Basis des Lastenhefts die konkreten Lösungsansätze und technischen Spezifikationen zur Umsetzung der Anforderungen ("Wie" und "Womit" wird es getan?).

PDO (PHP Data Objects):
Eine konsistente Datenzugriffsabstraktionsschicht in PHP, deren Nutzung im Projekt "Nexus" verbindlich ist, um Datenbankunabhängigkeit zu gewährleisten.

Presentation-Schicht:
Die äußerste Schicht der Hexagonalen Architektur, die Anfragen von außen empfängt (z.B. über Web-Controller, API-Endpunkte).

PSR (PHP Standard Recommendation):
Eine von der PHP Framework Interop Group (PHP-FIG) veröffentlichte Spezifikation, die Coding-Styles und Schnittstellen-Standards für PHP definiert.

RBAC (Role-Based Access Control):
Ein rollenbasiertes Zugriffskontrollsystem, bei dem Berechtigungen nicht direkt an Benutzer, sondern an Rollen vergeben werden.

Release Candidate:
Eine Software-Version (im Projekt 0.9.x), die potenziell die finale Version ist und zur finalen Abnahme bereitgestellt wird.

Repository Pattern:
Ein Entwurfsmuster, das den Datenzugriff von der Geschäftslogik entkoppelt, indem es eine sammlungsartige Schnittstelle für den Zugriff auf Domänenobjekte bereitstellt.

REST-API:
Eine Programmierschnittstelle (API), die den architektonischen Prinzipien von REST (Representational State Transfer) folgt. Dient der standardisierten Kommunikation zwischen Softwaresystemen.

Service Provider:
Ein in sich geschlossenes Software-Paket, dessen einzige Aufgabe es ist, neue Dienste (Services) im zentralen DI-Container der Anwendung zu registrieren, um deren Funktionalität zu erweitern.

Session-Fingerprinting:
Ein Sicherheitsmechanismus, bei dem Merkmale des Benutzer-Clients (z.B. User-Agent, IP-Adresse) an seine Session gebunden werden, um Session-Hijacking zu erschweren.

Smoke-Test:
Ein kurzer, oberflächlicher Test nach einem Deployment auf einem Server, um zu überprüfen, ob die grundlegendsten Funktionen der Anwendung (z.B. Startseite lädt, Datenbankverbindung steht) noch funktionieren.

Stateless Application:
Eine Anwendung, die keine benutzerspezifischen Zustandsinformationen (wie Sessions) auf dem lokalen Server speichert. Dies ist die Voraussetzung für horizontale Skalierbarkeit.

Theming:
Die Möglichkeit, das visuelle Erscheinungsbild einer Anwendung durch austauschbare Design-Pakete (Themes) anzupassen, ohne den Kern-Code zu verändern.

Tracy:
Das zentrale Debugging- und Logging-Tool für PHP, das im Projekt "Nexus" verbindlich eingesetzt wird.

Twig:
Die Template-Engine, die im Projekt "Nexus" zur strikten Trennung von Logik und Darstellung verwendet wird.

Usability:
Die Benutzerfreundlichkeit einer Anwendung. Bezieht sich im Projekt sowohl auf die Endnutzer als auch auf die Entwickler (Developer Experience).

WCAG (Web Content Accessibility Guidelines):
Ein internationaler Standard mit Empfehlungen zur Gestaltung barrierefreier Webinhalte. Für Nexus ist die Konformität mit Version 2.1 auf Level AA verbindlich.

---