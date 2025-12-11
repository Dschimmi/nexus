# ADR 021: Caching-Strategie und Opcache-Konfiguration

**Status:** Akzeptiert
**Datum:** 2025-12-11
**Autor:** Architecture Team
**Betroffene Komponenten:** `Config`, `Kernel`, Deployment-Pipeline

## Kontext
Das Framework verfügte bisher über keine definierte Caching-Strategie für den PHP-Bytecode (Ticket 0000032). Die Performance hing vollständig von der Zufallskonfiguration des Hosters ab. Für den Enterprise-Einsatz ist jedoch eine deterministische, hochperformante Ausführungsumgebung erforderlich, die die "Boot-Time" des Frameworks minimiert.

## Entscheidung

### 1. Aggressives Opcache-Tuning
Wir konfigurieren den Opcache explizit für "Immutable Deployments".
* `opcache.validate_timestamps=0`: Wir deaktivieren die Prüfung auf Dateiänderungen im laufenden Betrieb.
* **Begründung:** Maximale I/O-Reduktion. Im Enterprise-Kontext ändert sich Code nicht zur Laufzeit, sondern nur durch Deployments.

### 2. Opcache Preloading
Wir setzen Preloading (`config/preload.php`) ein, um den "Hot Path" (Kernel, Services, Controller) bereits beim Serverstart in den Shared Memory zu laden.
* **Umfang:** Rekursives Laden aller Klassen unter `src/`.
* **Begründung:** Eliminiert den Autoloading-Overhead und Class-Linking-Kosten für jeden einzelnen Request.

### 3. Trennung von Konfiguration und Laufzeit
* Die `config/opcache.ini` dient als unveränderliche Referenz für die Produktionsumgebung.
* Runtime-Fallbacks in `.htaccess` dienen nur der Kompatibilität mit Shared-Hosting, unterstützen aber kein Preloading.

## Konsequenzen

### Positiv
* **Performance:** Signifikante Reduktion der Latenz und CPU-Last pro Request.
* **Stabilität:** Das Verhalten der Anwendung ist weniger abhängig von Dateisystem-Latenzen.

### Negativ
* **Deployment-Komplexität:** Ein Code-Update erfordert zwingend einen Neustart des PHP-Prozesses (z.B. PHP-FPM Reload), da Änderungen sonst nicht wirksam werden.
* **Entwicklung:** In der Dev-Umgebung müssen abweichende Einstellungen (`validate_timestamps=1`) genutzt werden, um DX (Developer Experience) nicht zu gefährden.