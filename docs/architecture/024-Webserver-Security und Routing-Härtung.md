# ADR 024: Webserver-Security und Routing-Härtung

**Status:** Akzeptiert
**Datum:** 2025-12-11
**Autor:** Architecture Team
**Betroffene Komponenten:** `.htaccess`, `index.php`

## Kontext
Das Sicherheits-Audit (v0.8.0) deckte auf, dass direkte Zugriffe auf interne PHP-Dateien möglich waren und die Asset-Routing-Liste in der `public/.htaccess` unvollständig war. Dies vergrößerte die Angriffsfläche (z.B. direkte Ausführung von Skripten unter Umgehung des Frontcontrollers).

## Entscheidung

### 1. Whitelist-Ansatz für Assets
In der Webserver-Konfiguration (`.htaccess`) wird eine **Whitelist** zulässiger Dateiendungen (z.B. `ico|css|js|gif|jpe?g|png|woff2?|ttf|eot|svg|json|webmanifest|mp4|webm|pdf`) definiert.
* **Ziel:** Alles, was nicht auf der Whitelist steht, wird zwingend an die `index.php` (Frontcontroller) geleitet.

### 2. Blockieren direkter PHP-Ausführung
Der direkte Aufruf von `.php`-Dateien (außer `index.php`) wird auf Webserver-Ebene blockiert und zum Frontcontroller umgeleitet.
* **Ziel:** Verhindert das unkontrollierte Ausführen von Skripten, die nicht durch den Framework-Lifecycle (Auth, CSRF, etc.) laufen.

### 3. Entry-Point Validierung
Der Frontcontroller (`public/index.php`) ist der einzige legitime Einstiegspunkt. Umgebungsvariablen und Context werden dort zentral initialisiert.

## Konsequenzen

### Positiv
* **Sicherheit:** Massiv reduzierte Angriffsfläche. Umgehung des Framework-Security-Layers ist nicht mehr trivial möglich.
* **Kontrolle:** Alle relevanten Requests laufen durch den überwachten Lifecycle der Applikation.

### Negativ
* **Flexibilität:** Ad-hoc Test-Skripte im Root-Verzeichnis können nicht mehr direkt aufgerufen werden. (Gewünschter Effekt)