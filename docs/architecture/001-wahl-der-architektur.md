# 1. Wahl der Architektur: Hexagonale Architektur (Ports & Adapters)

* **Status:** Angenommen
* **Datum:** 2025-11-21

## Kontext

Das Projekt "Nexus" erfordert eine flexible, wartbare und testbare Architektur, um eine heterogene Systemlandschaft abzulösen. Die Kernanwendungslogik muss von externen Abhängigkeiten wie der UI, Datenbanken oder APIs entkoppelt sein, um Wiederverwendbarkeit und Austauschbarkeit zu gewährleisten (PH: 3.2).

## Entscheidung

Wir entscheiden uns für die Implementierung einer **Hexagonalen Architektur (Ports & Adapters)**, geleitet von den Prinzipien des **Domain-Driven Design (DDD)**.

## Konsequenzen

*   Eine strikte Trennung in die Schichten Domain, Application, Infrastructure und Presentation wird umgesetzt.
*   Die Geschäftslogik in der Domain-Schicht ist frei von jeglicher Infrastruktur und somit maximal wiederverwendbar.
*   Die Testbarkeit wird signifikant erhöht, da jede Schicht isoliert getestet werden kann.
*   Der initiale Entwicklungsaufwand ist etwas höher, da Interfaces ("Ports") und Implementierungen ("Adapter") klar getrennt werden müssen. Dies zahlt sich langfristig durch eine deutlich bessere Wartbarkeit aus.