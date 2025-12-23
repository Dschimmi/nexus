# ADR 025: v0.8.14 Einführung von Quality Gates, Teststrategie und CI/CD-Baseline

### 1. Titel
Einführung von Quality Gates, umfassender Teststrategie und Continuous Integration/Deployment (CI/CD) Baseline.

### 2. Kontext
Vor der Veröffentlichung von v0.8.14 existierten kritische Abweichungen von den Qualitätsanforderungen, insbesondere fehlende Testabdeckung für Kernkomponenten, deaktivierte Code-Coverage-Messung und das Fehlen eines formalisierten Deployment-Prozesses (CD). Die bestehende CI-Pipeline deckte nur rudimentäre Checks ab.

### 3. Entscheidung
Es wird entschieden, einen **Quality-Gates-Release (v0.8.14)** durchzuführen, der die Teststrategie implementiert und die CI-Pipeline zu einer vollständigen CI/CD-Pipeline erweitert.

### 4. Details der Umsetzung

#### 4.1. Teststrategie und Quality Gates (Tickets 0000043, 0000072, 0000074)

* **Integrationstests implementiert:** Neben den bestehenden Unit-Tests wurden Integrationstests für den Request-Lifecycle eingeführt, um das Zusammenspiel mehrerer Komponenten zu prüfen (Controller, Kernel, Routing).
* **Kernkomponenten-Abdeckung:** Vollständige Testabdeckung wurde für den **Kernel** (`KernelTest.php`) und alle **Controller** (`AdminControllerTest.php`, `HomepageControllerTest.php`) hinzugefügt.
* **Test-Suite Korrektur:** Alle veralteten Unit-Tests (`AuthenticationServiceTest.php`, `ConfigServiceTest.php`, `SessionServiceTest.php`) wurden an die neue Dependency Injection-Architektur und die `User`-Entity-Signatur angepasst, um die Testsuite stabil zu halten.
* **Coverage-Durchsetzung:** In der Konfigurationsdatei (`phpunit.xml.dist`) wurde der Schwellenwert für die Code-Coverage der Domain-Logik auf **90 %** festgelegt. Die CI-Pipeline bricht nun ab, wenn dieser Schwellenwert unterschritten wird.

#### 4.2. CI/CD Pipeline-Erweiterung (Tickets 0000075, 0000044)


Die Pipeline in `.github/workflows/ci.yml` wurde strukturell erweitert und stabilisiert:

| Bereich | Maßnahme | Artefakte / Konfiguration |
| :--- | :--- | :--- |
| **Code Quality** | Linting und Statische Analyse als frühe Fehlererkennungsschritte eingeführt. | PHP-CS-Fixer und PHPStan-Jobs in `build-and-test` integriert. |
| **Coverage-Erfassung** | Coverage-Erfassung im Setup-Schritt der Pipeline aktiviert. | `coverage: xdebug` im "Setup PHP" Schritt; Upload des `coverage.xml` Reports. |
| **Continuous Deployment (CD)** | Neuer dedizierter Job für CD- und Versionierungsaufgaben. | Job `release_and_deploy` (läuft nach erfolgreichem Test-Job). |
| **Versionierung** | Einführung eines automatisierten Tagging-Schritts. | `release_and_deploy` erstellt und pusht automatisch Git-Tags (`vX.Y.Z`) nach erfolgreichem Build. |
| **Stabilität** | Behebung von Syntax- und Logikfehlern (z.B. in `run: |` Blöcken, `:` im Step-Namen). | Entfernung des Kommentars im `run:`-Block (Step 5); Ersetzung des Doppelpunkts im Deployment-Step-Namen. |


### 5. Konsequenzen

| Positiv | Negativ |
| :--- | :--- |
| **Erhöhte Stabilität:** Code-Coverage > 90 % sorgt für hohe Vertrauenswürdigkeit der Business-Logik. | **Initialer Mehraufwand:** Hoher initialer Aufwand zur Erstellung und Korrektur der Testsuite (Kernel, Controller, Services). |
| **Standardisierter Release:** Versionierung (Git Tagging) und Deployment-Baseline sind automatisiert. | **Build-Zeit:** Die Laufzeit der CI-Pipeline erhöht sich durch Linting, Static Analysis und Coverage-Generierung leicht. |
| **Frühe Fehlererkennung:** Statische Analyse und Linting verhindern, dass minderwertiger Code überhaupt in die Testphase gelangt. | **Strictness:** Die 90 % Coverage-Schwelle muss bei jeder Code-Änderung aktiv gemanagt werden, um Build-Fehler zu vermeiden. |

### 6. Status
Akzeptiert (Accepted) in v0.8.14.