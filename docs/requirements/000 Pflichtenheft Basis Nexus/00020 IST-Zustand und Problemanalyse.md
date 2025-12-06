## 2. IST-Zustand und Problemanalyse
`(Detaillierte Analyse der bestehenden Systemlandschaft und der daraus resultierenden Herausforderungen.)`

### 2.1. Bestehende Systemlandschaft
Die aktuelle technologische Basis besteht aus fünf voneinander unabhängigen und technologisch heterogenen Web-Systemen, die produktiv eingesetzt werden. Diese sind:
1.	Ein MediaWiki als themenspezifisches Online-Lexikon.
2.	Ein phpBB-Forum als Community-Plattform, das nur lose und unzureichend angebunden ist.
3.	Ein eigenentwickeltes CMS, das Partnern zur eigenständigen Verwaltung von Inhalten und Werbung dient.
4.	Ein "Frachtbriefgenerator", der als HTML-Formular Benutzereingaben in ein PDF-Dokument umwandelt.
5.	Eine NDA-belegte "preAlpha"-Anwendung.
Die technologische Grundlage dieser Systeme ist fragmentiert und umfasst unter anderem PHP, JavaScript, SQL/PDO, AJAX, HTML und CSS.

### 2.2. Problemanalyse
Aus dem beschriebenen IST-Zustand resultieren mehrere schwerwiegende Probleme, die die Wartbarkeit, Sicherheit und Weiterentwicklung der Systeme erheblich erschweren:
- Hoher redundanter Aufwand: Wiederkehrende Aufgaben, wie die Implementierung eines DSGVO-konformen Cookie-Banners oder die Umsetzung von Sicherheitsupdates, müssen für jedes System einzeln und mit hohem Mehraufwand durchgeführt werden.
- Fehlende Integration und Inkonsistenz: Es existieren keine standardisierten Prozesse oder echten Schnittstellen zwischen den Plattformen. Die Benutzerverwaltung ist dezentral, was zu inkonsistenten Datenbeständen und einem schlechten Benutzererlebnis führt.
- Sicherheits- und Performance-Defizite: Durch die heterogene Landschaft werden Sicherheits- und Performance-Aspekte nicht systematisch, sondern improvisiert und reaktiv behandelt, was ein erhebliches Risiko darstellt.
- Erschwerte Wartung und Weiterentwicklung: Die Pflege von fünf unterschiedlichen Codebasen ist extrem zeitintensiv, fehleranfällig und verhindert eine agile Weiterentwicklung. Jede noch so kleine Anpassung birgt das Risiko von unvorhergesehenen Seiteneffekten in den jeweiligen Altsystemen.

**Fazit:** Der aktuelle Zustand ist wirtschaftlich ineffizient, technisch nicht zukunftsfähig und sicherheitstechnisch bedenklich. Die Entwicklung des "Nexus"-Frameworks ist die strategische Antwort, um diese grundlegenden Probleme zu lösen.

---