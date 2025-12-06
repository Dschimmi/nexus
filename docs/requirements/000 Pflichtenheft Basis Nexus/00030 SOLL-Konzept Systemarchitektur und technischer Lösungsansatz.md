## 3. SOLL-Konzept: Systemarchitektur und technischer Lösungsansatz
`(Beschreibung der grundlegenden Architektur des "Nexus"-Frameworks, des Technologie-Stacks und der zentralen Design-Entscheidungen.)`

Dieses Kapitel beschreibt die grundlegende technische Architektur und die zentralen Design-Entscheidungen für das "Nexus"-Framework. Die hier vorgestellte Architektur ist die direkte technische Antwort auf die im IST-Zustand (Lastenheft, S. 2) identifizierten Herausforderungen – insbesondere die Heterogenität der Systeme, der hohe Wartungsaufwand und die fehlenden zentralen Komponenten wie eine einheitliche Userverwaltung. Das Ziel ist die Schaffung einer einheitlichen, modularen und flexiblen Framework-Basis, wie im SOLL-Zustand gefordert.

### 3.1. Kern-Technologie-Stack
Der Technologie-Stack ist so gewählt, dass er die im Lastenheft geforderten Ziele wie Sicherheit, Wartbarkeit, Performance und Flexibilität bestmöglich unterstützt.

#### 3.1.1. Programmiersprache: PHP 8.2+
Die Festlegung auf eine aktuelle PHP-Version ist eine strategische Entscheidung, um die im Lastenheft geforderte hohe Qualität und Sicherheit zu gewährleisten. Moderne Sprachfeatures und strikte Typisierung reduzieren die Fehleranfälligkeit und verbessern die Wartbarkeit, was direkt das Problem des "aufwändigen und fehleranfälligen" IST-Zustands adressiert.

#### 3.1.2. Webserver: Nginx / Apache 2.4+ mit URL-Rewriting
Das Framework wird über einen einzigen Einstiegspunkt (eine index.php im /public-Verzeichnis) betrieben. Dies ist ein fundamentaler Bruch mit den bisherigen, separaten Installationen und die technische Voraussetzung für eine zentrale Steuerung von Anfragen, Sicherheit und Konfiguration.

#### 3.1.3. Datenbank-Schnittstelle: PHP Data Objects (PDO)
Um die im Lastenheft (S. 1) explizit geforderte Unabhängigkeit von spezifischen Datenbanksystemen zu erreichen, wird ausschließlich die PDO-Schnittstelle verwendet. Dies stellt sicher, dass das "Nexus"-Framework zukünftig flexibel mit unterschiedlichen Datenbanken (primär MySQL und PostgreSQL) arbeiten kann, ohne dass der Kern des Frameworks angepasst werden muss.

#### 3.1.4. Abhängigkeitsmanagement: Composer 2.x
Die im Lastenheft geforderte Modularität und Erweiterbarkeit wird technisch durch den Einsatz von Composer als zentralem Werkzeug für das Management von Bibliotheken und Framework-eigenen Modulen umgesetzt.

### 3.2. Architekturmuster: Hexagonale Architektur (Ports & Adapters)
Um die zentrale Herausforderung – die Schaffung einer einzigen Codebasis für verschiedene Anwendungen (Wiki, Forum, CMS etc.) – zu lösen, wird eine Hexagonale Architektur, geleitet von den Prinzipien des Domain-Driven Design (DDD), implementiert.

Dieses Muster trennt den Anwendungskern (Business-Logik) strikt von der Außenwelt (UI, Datenbank, externe APIs). Dies ermöglicht es uns, für dieselbe Kernlogik unterschiedliche "Adapter" zu erstellen. So kann beispielsweise die zentrale Userverwaltung (Kernlogik) sowohl von einem Web-Controller (für das CMS) als auch von einer API (für eine zukünftige App) genutzt werden, ohne Code zu duplizieren.

#### 3.2.1. Domain-Schicht (Der Kern):
Inhalt: Enthält die reine Business-Logik und die Geschäftsregeln, z.B. was ein User ist oder welche Regeln für einen CMR-Frachtbrief gelten.

Zweck: Diese Schicht ist komplett unabhängig von jeder Technologie. Sie ist das wiederverwendbare Herzstück des "Nexus"-Frameworks.

#### 3.2.2. Application-Schicht (Die Anwendungsfälle):
Inhalt: Orchestriert die Domain-Logik, um konkrete Anwendungsfälle abzubilden (z.B. RegistriereNeuenNutzer, ErstelleFrachtbriefPDF).

Zweck: Definiert, was die Software tun kann. Bildet die Schnittstelle zwischen der Außenwelt und dem Kern.

#### 3.2.3. Infrastructure-Schicht (Die Adapter "nach außen"):
Inhalt: Enthält die konkreten Implementierungen für externe Abhängigkeiten: Datenbank-Repositories (z.B. PostgresUserRepository), PDF-Generatoren, E-Mail-Versanddienste etc.

Zweck: Macht die Außenwelt für den Kern nutzbar. Diese Schicht ist austauschbar. Wir können von MySQL auf PostgreSQL wechseln, indem wir lediglich einen Adapter in dieser Schicht austauschen, ohne den Kern anzufassen.
#### 3.2.4. Presentation-Schicht (Die Adapter "nach innen"):
Inhalt: Empfängt Anfragen von außen. Dies können Web-Controller, API-Endpunkte oder Konsolen-Befehle sein.

Zweck: Dient als Einstiegspunkt für Benutzer und externe Systeme. Für jedes "alte" Projekt (Wiki, Forum) wird hier ein eigener Satz von Controllern und Views erstellt, die alle auf dieselbe Application- und Domain-Schicht zugreifen.

Dieses Modell adressiert direkt die im Lastenheft geforderte Wiederverwendbarkeit, einfache Erweiterbarkeit und Reduktion des Pflegeaufwands.

### 3.3. Auswahl von Kern-Bibliotheken und Komponenten
Das "Nexus"-Framework wird kein monolithisches Fremd-Framework nutzen, sondern nach dem Baukastenprinzip auf bewährten, einzelnen Komponenten aufbauen. Dies stellt sicher, dass der Kern des Frameworks schlank bleibt und nicht "überladen oder unnötig spezialisiert" wird (Lastenheft, S. 1).

#### 3.3.1. Dependency Injection Container: Symfony DI
Um die lose Kopplung der Komponenten und die Testbarkeit sicherzustellen, wird der DI-Container von Symfony verwendet. Er ist für die zentrale Verwaltung und das "Verdrahten" aller Dienste verantwortlich.

#### 3.3.2. HTTP-Abstraktion und Routing: Symfony HttpFoundation & Routing
Diese Komponenten handhaben die Verarbeitung von HTTP-Anfragen und -Antworten sowie das Mapping von URLs auf die zuständigen Controller in der Presentation-Schicht. Ihre Flexibilität erlaubt es, die Anforderungen von einfachen Landing-Pages bis hin zu komplexen Anwendungen abzudecken.

#### 3.3.3. Template-Engine: Twig
Um eine strikte Trennung von Logik und Darstellung zu gewährleisten, wird Twig als Template-Engine eingesetzt. Seine Vererbungs- und Inklusionsmechanismen unterstützen die im Lastenheft geforderte Wiederverwendbarkeit von Design-Elementen.

#### 3.3.4. Logging-Schnittstelle: Tracy ILogger
Wie in den Coding Guidelines (9.1.1) festgelegt, dient die ILogger-Schnittstelle von Tracy als zentraler Logging-Mechanismus, der per Dependency Injection in alle relevanten Dienste injiziert wird.

---