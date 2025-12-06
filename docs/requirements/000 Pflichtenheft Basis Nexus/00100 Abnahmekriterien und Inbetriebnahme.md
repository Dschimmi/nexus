## 10. Abnahmekriterien und Inbetriebnahme
`(Definition der Kriterien, die für eine erfolgreiche Abnahme der Liefergegenstände erfüllt sein müssen, sowie der Plan für den Go-Live.)`

Grundsatz:
Dieses Kapitel definiert die formalen Kriterien und den Prozess, nach dem die im Rahmen des "Nexus"-Projekts erstellten Liefergegenstände vom Auftraggeber geprüft und abgenommen werden. Eine erfolgreiche Abnahme ist die Voraussetzung für die Inbetriebnahme (Go-Live) der jeweiligen Komponente. Die Abnahmekriterien leiten sich direkt aus den in diesem Pflichtenheft spezifizierten Anforderungen (insbesondere Kapitel 4 bis 9) ab.

### 10.1. Formale Abnahmekriterien
Die Abnahme der entwickelten Software erfolgt, sobald die folgenden Kriterien nachweislich und vollständig erfüllt sind. Die Prüfung erfolgt durch den Auftraggeber auf einer bereitgestellten Abnahmeumgebung (Staging-System), die der späteren Produktivumgebung technisch entspricht.
- Vollständigkeit der funktionalen Anforderungen: Alle in Kapitel 4 (Funktionale Anforderungen im Detail) spezifizierten Features des Basisframeworks und der beauftragten Module müssen implementiert sein und fehlerfrei funktionieren.
- Erfüllung der nicht-funktionalen Anforderungen: Die in Kapitel 5 (Nicht-funktionale Anforderungen) definierten Qualitätsmerkmale müssen nachweislich erfüllt sein. Dies umfasst insbesondere:
  - Die Einhaltung der Performance-KPIs (PH: 5.1).
  - Die Umsetzung aller Sicherheitsvorgaben (PH: 5.2).
  - Die Gewährleistung der Skalierbarkeit durch eine stateless Architektur (PH: 5.3).
- Erfolgreiche Test-Suite: Die automatisierte Test-Suite (PH: 9.2.1) muss für den finalen Abnahme-Build vollständig und ohne Fehler durchlaufen. Die definierte Code-Coverage für die kritische Domain-Logik muss erreicht sein.
- Fehlerfreiheit: Zum Zeitpunkt der Abnahme dürfen keine bekannten, im Bugtracker (Mantis) erfassten Fehler mehr im System vorhanden sein. Diese Anforderung entspricht der in PH: 9.1.3 definierten Regel, dass alle bekannten Bugs vor dem finalen Versionssprung (z.B. auf 1.0.0) behoben sein müssen.
- Vollständigkeit der Dokumentation: Die in Kapitel 12 (Dokumentation) geforderte technische Dokumentation und die Installationsanleitung müssen in ihrer finalen Version vorliegen und den aktuellen Stand der Software korrekt beschreiben.

### 10.2. Prozess der Inbetriebnahme (Go-Live)
Voraussetzung:
Die Inbetriebnahme der Software auf dem finalen Produktivsystem kann erst nach der erfolgreichen und formell vom Auftraggeber bestätigten Abnahme gemäß der Kriterien in Kapitel 10.1 erfolgen.

Spezifikation:
Der Go-Live-Prozess wird nach einem standardisierten Plan durchgeführt, um Risiken zu minimieren und einen reibungslosen Übergang zu gewährleisten. Der Prozess umfasst die folgenden Schritte:
1.	Bereitstellung der Produktivumgebung: Der Auftraggeber stellt sicher, dass die Produktivumgebung alle in Kapitel 8.3 (Hosting und Betrieb) definierten Systemvoraussetzungen erfüllt.
2.	Finales Deployment: Der Auftragnehmer führt das finale Deployment des abgenommenen Softwarestandes auf der Produktivumgebung durch. Dieser Prozess erfolgt automatisiert über die in Kapitel 9.2.2 definierte CI/CD-Pipeline.
3.	Konfiguration: Die Konfiguration der produktiven Environment-Variablen (z.B. Datenbankzugangsdaten, API-Schlüssel) wird vorgenommen.
4.	Smoke-Test: Nach dem Deployment führt der Auftragnehmer einen abschließenden "Smoke-Test" auf der Produktivumgebung durch, um die grundlegende Funktionsfähigkeit der Kernkomponenten (z.B. Erreichbarkeit der Startseite, Datenbankverbindung) zu verifizieren.
5.	Formale Übergabe: Nach erfolgreichem Smoke-Test erfolgt die formale Übergabe des Systems an den Auftraggeber, der damit den produktiven Betrieb aufnimmt.

#### 10.2.1. Go-Live-Checkliste für Webserver-Konfiguration
Grundsatz:
Zusätzlich zu den im Prozess der Inbetriebnahme (PH: 10.2) genannten Schritten muss vor dem finalen Go-Live sichergestellt werden, dass die Webserver-Konfiguration für eine optimale Performance und zur Vermeidung unnötiger Fehler optimiert ist.
Verbindliche Prüfpunkte:
1.	SSL/TLS-Zertifikat: Für die Produktionsdomain muss ein gültiges SSL/TLS-Zertifikat installiert und korrekt konfiguriert sein, um den Betrieb über HTTPS zu ermöglichen (siehe PH: 5.2).
2.	favicon.ico: Eine favicon.ico-Datei muss im Document Root (/public-Verzeichnis) des Projekts platziert werden.
  - Begründung: Dies verhindert, dass jeder Browser-Aufruf eine 404 Not Found-Anfrage in der Anwendung auslöst, was die Log-Dateien unnötig füllt und minimale, aber vermeidbare Serverlast erzeugt.
3.	.htaccess-Optimierung (für Apache): Die in der public/.htaccess-Datei definierten RewriteCond-Regeln zur Ausnahme von statischen Assets (CSS, JS, Bilder, ICO) müssen vorhanden und aktiv sein.
  - Begründung: Dies stellt sicher, dass Anfragen für statische Dateien direkt vom Webserver ausgeliefert werden, ohne die PHP-Anwendung unnötig zu starten, was die Server-Performance signifikant verbessert.

---