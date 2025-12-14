# ADR 029: Daten-Transformation und Integrations-Schnittstellen

**Status:** Akzeptiert  
**Datum:** 2025-12-14  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `DataTransformerInterface`, Import/Export-Module

## Kontext

Das Framework muss in der Lage sein, Daten mit externen Systemen auszutauschen (Import/Export). Die Anforderungen (Tickets 51-53) verlangten nach Adaptern für CSV, TSV und JSON.
Ohne eine standardisierte Schnittstelle würde jedes Modul eigene Parser und Mapper implementieren, was zu inkonsistentem Code und fehlender Wiederverwendbarkeit führen würde.

## Entscheidung

Wir definieren eine zentrale Schnittstelle für alle Datentransformationen: **`DataTransformerInterface`**.

### 1. Der Vertrag (Contract)
Das Interface erzwingt zwei Methoden:
*   `transform($data)`: Wandelt externe Rohdaten (z. B. CSV-Zeile) in interne Strukturen (z. B. DTOs/Arrays) um.
*   `reverseTransform($data)`: Wandelt interne Strukturen zurück in das externe Format.

### 2. Dezentrale Implementierung
Der Core liefert **keine** konkreten CSV-Parser mit. Die Implementierung erfolgt bedarfsgerecht in den Modulen (z. B. `CsvUserImporter` im User-Modul), die dieses Interface nutzen.

## Konsequenzen

### Positiv
*   **Standardisierung:** Alle Import/Export-Vorgänge folgen demselben Muster.
*   **Testbarkeit:** Transformer sind reine Logik-Klassen ohne Seiteneffekte und damit einfach zu testen.
*   **Entkopplung:** Der Core bleibt schlank und frei von schweren Parsing-Bibliotheken.

### Negativ
*   **Initiale Hürde:** Entwickler müssen für einfache Exporte eine Klasse schreiben, statt "schnell mal" `fputcsv` aufzurufen.