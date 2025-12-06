## 8. Rahmenbedingungen
`(Festlegung der rechtlichen, organisatorischen und technischen Rahmenbedingungen, inklusive Datenschutz und Hosting.)`

Dieses Kapitel definiert die technischen, organisatorischen und rechtlichen Randbedingungen, die für die Entwicklung und den Betrieb des "Nexus"-Frameworks zwingend einzuhalten sind.

### 8.1. Gesetzliche und Regulatorische Anforderungen
Spezifikation:
- Datenschutz (DSGVO / TDDDG): Das System wird nach den Prinzipien "Privacy by Design" und "Privacy by Default" entwickelt.
  - Personenbezogene Daten werden nur erhoben, wenn sie für den jeweiligen Zweck technisch notwendig sind (Datenminimierung).
  - Die technische Umsetzung der Einwilligungserfordernisse (Cookie-Consent) erfolgt über das in Kapitel 4.1.4 definierte Modul.
  - Der Serverstandort für das Hosting von Produktionsdaten muss innerhalb der Europäischen Union (bevorzugt Deutschland) liegen, um die DSGVO-Konformität zu gewährleisten.
- Barrierefreiheit (BFSG): Die Einhaltung des Barrierefreiheitsstärkungsgesetzes (BFSG) wird durch die konsequente Umsetzung der WCAG 2.1 AA Standards (siehe PH: 7.2) sichergestellt.
- Der Hinweis, dass "das BFSG bestmöglich berücksichtigt und eingehalten wurde, aber nicht garantiert werden kann, dass diese Webseite 100% BFSG-Konform ist." Muss an passender Stelle im Impressum, AGB oder der Datenschutzerklärung, erwähnt sein.
- Rechtstexte (Impressum, AGB, Datenschutz): Das Framework stellt konfigurierbare Platzhalter (Views oder Datenbankfelder) bereit, um rechtlich notwendige Texte (Impressum, Datenschutzerklärung, AGB) einzubinden.
  - Abgrenzung: Die inhaltliche Erstellung und juristische Prüfung dieser Texte liegt nicht im Verantwortungsbereich des Auftragnehmers, sondern obliegt dem Auftraggeber. Das System unterstützt jedoch die Einbindung von externen Generatoren (z.B. per iFrame oder API, sofern vom Generator unterstützt) oder die einfache Pflege über das Core-Template.

### 8.2. Standards und Normen
Spezifikation:
- IT-Sicherheit: Die Architektur und Entwicklung orientieren sich an den Empfehlungen des BSI IT-Grundschutz sowie der ISO 27001 für die sichere Entwicklung von Webanwendungen (Secure Software Development Life Cycle). Dies wird technisch durch die Maßnahmen in Kapitel 5.2 (Sicherheit) und 9.2 (Tests & CI/CD) umgesetzt.
- Lizenzrechtliche Konformität: Bei der Verwendung von externen Bibliotheken (via Composer oder NPM) darf ausschließlich Software eingesetzt werden, deren Lizenz (z.B. MIT, Apache 2.0, BSD) mit der kommerziellen Nutzung und Weiterentwicklung des "Nexus"-Frameworks vereinbar ist. Copyleft-Lizenzen (wie GPL), die eine Offenlegung des gesamten Quellcodes erzwingen würden, sind für Kernkomponenten zu vermeiden, sofern dies nicht explizit gewünscht ist.

### 8.3. Hosting und Betrieb
Spezifikation:
- Systemvoraussetzungen: Das Framework muss auf handelsüblichen Linux-Server-Umgebungen (Shared Hosting, VPS oder Dedicated Server) lauffähig sein, sofern diese die in Kapitel 3.1 definierten Anforderungen (PHP 8.2+, MySQL/PostgreSQL, Nginx/Apache) erfüllen.
- Backup und Wiederherstellung:
  - Das System muss so konzipiert sein, dass ein vollständiges Backup durch das Sichern des Dateisystems (Code + Uploads) und ein Export der Datenbank (SQL-Dump) möglich ist.
  - Es dürfen keine systemrelevanten Zustände existieren, die nicht in der Datenbank oder im Dateisystem persistiert sind (Stateless-Prinzip, siehe PH: 5.3).
- Wartungsmodus: Das Framework muss über einen konfigurierbaren "Wartungsmodus" verfügen. Wenn dieser aktiv ist, wird für alle Benutzer (außer Administratoren) eine statische "Wartungsarbeiten"-Seite angezeigt, und der Zugriff auf die Anwendung wird blockiert.

### 8.4. Umgebungs-spezifische Konfiguration
Grundsatz:
Das Verhalten des Frameworks muss sich zwingend zwischen verschiedenen Betriebsumgebungen (insbesondere Entwicklung und Produktion) unterscheiden, um maximale Sicherheit und Performance im Live-Betrieb zu gewährleisten und gleichzeitig eine komfortable Entwicklung zu ermöglichen.

Spezifikation:
- Steuerung über Environment-Variablen: Die Konfiguration des Frameworks wird über Environment-Variablen (Umgebungsvariablen) gesteuert. Eine zentrale Variable, z.B. APP_ENV, definiert die aktuelle Umgebung (development, production etc.).
- Fail-Safe-Prinzip (Default to Production): Das System muss so implementiert werden, dass es bedingungslos in den production-Modus zurückfällt, falls die APP_ENV-Umgebungsvariable nicht explizit gesetzt oder auslesbar ist. Dies stellt sicher, dass eine fehlerhaft konfigurierte Serverumgebung niemals versehentlich sensible Debug-Informationen preisgibt oder in einem unsicheren Modus betrieben wird.
- .env-Datei für lokale Entwicklung: Für die lokale Entwicklung wird eine .env-Datei im Stammverzeichnis des Projekts verwendet, um diese Variablen zu setzen. Diese Datei wird von einer Standard-Bibliothek (wie vlucas/phpdotenv) eingelesen.
- Sicherheitsvorgabe: Die .env-Datei enthält potenziell sensible Daten und darf niemals in das Git-Repository eingecheckt werden. Stattdessen wird eine .env.example-Datei als Vorlage versioniert.
- Produktivumgebung: In Produktivumgebungen werden die Environment-Variablen direkt auf dem Server (z.B. in der Webserver- oder Docker-Konfiguration) gesetzt, um die höchste Sicherheit zu gewährleisten.

Begründung:
Dieser Ansatz ist ein etablierter Standard für moderne Webanwendungen. Er trennt den Code sauber von der Konfiguration, verhindert das Einchecken von sensiblen Zugangsdaten in die Versionskontrolle und ermöglicht durch das Fail-Safe-Prinzip ein robustes, sicheres Umschalten zwischen verschiedenen Betriebsmodi.

---