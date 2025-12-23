# 2. Wahl der Kern-Bibliotheken

* **Status:** Angenommen
* **Datum:** 2025-11-21

## Kontext

Gemäß Pflichtenheft (PH: 3.3) soll das Framework nicht auf einem monolithischen Fremd-Framework basieren, sondern nach dem Baukastenprinzip auf bewährten, einzelnen Komponenten aufbauen, um den Kern schlank zu halten.

## Entscheidung

Wir entscheiden uns für die Verwendung der folgenden, industrie-etablierten Symfony-Komponenten und Bibliotheken als Fundament des Frameworks:
*   **symfony/http-foundation & symfony/routing:** Für die HTTP-Abstraktion und das URL-Routing.
*   **symfony/dependency-injection:** Für die Umsetzung der Dependency Injection und die Verwaltung von Services.
*   **twig/twig:** Als Template-Engine zur strikten Trennung von Logik und Darstellung.
*   **tracy/tracy:** Als zentrales Werkzeug für Debugging und Logging.

## Konsequenzen

*   Das Framework bleibt schlank und enthält keinen unnötigen Code.
*   Wir profitieren von der Stabilität, Sicherheit und der exzellenten Dokumentation dieser weit verbreiteten Komponenten.
*   Das Projekt hat eine Abhängigkeit zum Symfony-Ökosystem, was jedoch aufgrund der hohen Qualität und Langlebigkeit dieser Komponenten als Vorteil bewertet wird.