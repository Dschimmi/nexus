## 12. Zielgruppenspezifische Dokumentations-Artefakte

- Entwicklerdokumentation:
  - Inhalt: Dies ist das zentrale technische Handbuch für Entwickler, die mit dem Nexus-Base Framework arbeiten. Es muss mindestens die folgenden Themen abdecken:
   1. Installation und Inbetriebnahme: Eine Schritt-für-Schritt-Anleitung zur Einrichtung einer lokalen Entwicklungsumgebung.
   2. Architektur-Überblick: Eine Beschreibung der grundlegenden Architektur (Hexagonale Architektur) und der Verantwortlichkeiten der einzelnen Schichten.
   1. Konfiguration: Eine detaillierte Erklärung aller Konfigurationsoptionen und der Verwendung von Environment-Variablen (.env).
   2. Kern-Services: Anleitungen und Code-Beispiele zur Verwendung der zentralen Services (Routing, Session-Management, i18n, Logging etc.).
   3. Testing: Eine Anleitung zum Schreiben und Ausführen von automatisierten Tests.
  - Format: Die Dokumentation wird im Markdown-Format direkt im Git-Repository im Verzeichnis /docs gepflegt. Dies stellt sicher, dass die Dokumentation versioniert wird und synchron mit dem Code bleibt.
- Quellcode-Dokumentation (API-Referenz):
  - Inhalt: Alle öffentlichen Klassen, Methoden und Eigenschaften des Frameworks müssen gemäß den in PH: 9.1.1.11 definierten Standards (phpDocumentor) durch DocBlocks kommentiert sein.
  - Generierung: Aus diesen Kommentaren kann bei Bedarf automatisiert eine vollständige API-Referenz im HTML-Format generiert werden.
- Architecture Decision Records (ADRs):
  - Inhalt: Wichtige und richtungsweisende Architekturentscheidungen, die während der Entwicklung getroffen wurden, müssen als ADRs dokumentiert werden (siehe PH: 9.1.1.11).
  - Format: Kurze Markdown-Dateien im Verzeichnis /docs/adr, die den Kontext, die Entscheidung und deren Konsequenzen beschreiben.

---