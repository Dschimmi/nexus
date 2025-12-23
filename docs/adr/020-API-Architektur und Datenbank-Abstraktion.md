# ADR 020: API-Architektur und Datenbank-Abstraktion

**Status:** Akzeptiert  
**Datum:** 2025-12-06  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `ApiController`, `DatabaseService`, `ApiTokenAuthenticator`

## Kontext

Für die geplante Erweiterung durch Module (User, Terminal) und externe Integrationen fehlten dem Framework zwei essenzielle Säulen:
1.  Eine standardisierte Schnittstelle für REST-APIs (JSON, Auth, Versioning).
2.  Ein sicherer Zugriff auf relationale Datenbanken, der SQL-Injection systemisch verhindert.

## Entscheidung

### 1. API-Design
Wir etablieren eine strikte Trennung zwischen Web-Controllern (HTML) und API-Controllern (JSON).
*   **Basisklasse:** Alle API-Endpunkte erben von `ApiController`, um einheitliche Response-Formate zu garantieren.
*   **Authentifizierung:** APIs nutzen keine Sessions/Cookies, sondern `Bearer Tokens`. Ein `ApiTokenAuthenticator` validiert diese gegen ein Repository (Env oder DB).
*   **Versionierung:** Alle Routen müssen versioniert sein (`/api/v1/...`).

### 2. Datenbank-Abstraktion (PDO Wrapper)
Wir führen einen `DatabaseService` ein, der die native `PDO`-Klasse kapselt.
*   **Zwang zu Prepared Statements:** Die Methode `query()` akzeptiert SQL und Parameter, führt aber intern zwingend `prepare()` und `execute()` aus.
*   **Sicherheits-Flags:** `ATTR_EMULATE_PREPARES` wird deaktiviert, um echte Datenbank-Prepares zu erzwingen (Schutz vor SQL-Injection bei speziellen Charsets).
*   **Lazy Connection:** Die Verbindung wird erst beim ersten Query aufgebaut, um Ressourcen zu sparen.

## Konsequenzen

### Positiv
*   **Sicherheit:** Entwickler können nicht versehentlich unsichere Queries (`query("SELECT $id")`) schreiben.
*   **Standardisierung:** API-Antworten folgen immer demselben Schema.
*   **Flexibilität:** Der Wechsel von `.env`-Tokens auf Datenbank-Tokens ist durch das Repository-Pattern vorbereitet.

### Negativ
*   **Overhead:** Für einfache Queries ist der Wrapper etwas verboser als rohes PDO.