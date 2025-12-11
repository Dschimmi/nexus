# Opcache Strategie & Konfiguration

## 1. Übersicht
Der Opcache ist die kritischste Performance-Komponente für die PHP-Ausführung. Er speichert vorkompilierten Bytecode im Shared Memory, um den Overhead des Parsens und Kompilierens bei jedem Request zu eliminieren.

**Wichtige Abgrenzung:**
Dieses Dokument behandelt ausschließlich den **Opcode-Cache** (PHP-Systemebene).
Es betrifft *nicht* den **Application-Cache** (z.B. Twig-Cache in `var/cache/`, Redis oder Datenbank-Caches).

## 2. Konfiguration
Das Projekt liefert eine produktionsreife Referenz-Konfiguration.

* **Datei:** `config/opcache.ini`
* **Einbindung:** Diese Datei sollte in die Server-Konfiguration (z.B. `/etc/php/8.x/fpm/conf.d/`) symlinked oder kopiert werden.

### Kritische Einstellungen
| Parameter | Wert | Begründung |
|---|---|---|
| `opcache.validate_timestamps` | `0` | **Nur Production:** Deaktiviert Filesystem-Checks. Maximale I/O-Performance. Erfordert Cache-Flush bei Deployments. |
| `opcache.memory_consumption` | `256` | Ausreichend Puffer für Framework + Vendor-Libs (Enterprise Standard). |
| `opcache.max_accelerated_files` | `20000` | Verhindert Cache-Eviction bei großen Codebasen. |
| `opcache.jit` | `1255` | Tracing JIT aktiviert für CPU-intensive Tasks. |

## 3. Preloading
Um die "Boot-Time" des Frameworks zu minimieren, nutzen wir Opcache Preloading.

* **Skript:** `config/preload.php`
* **Funktion:** Lädt den Kernel, Services und Controller rekursiv in den Speicher, bevor der erste Request bedient wird.
* **Aktivierung (php.ini):**
  ```ini
  opcache.preload=/var/www/html/config/preload.php
  opcache.preload_user=www-data
  ```

## 4. Shared Hosting Fallback
Für Umgebungen ohne Zugriff auf die globale `php.ini` beinhaltet die `public/.htaccess` Fallback-Direktiven (`php_value opcache.*`), um bestmögliche Performance via Modul-Konfiguration zu erzielen. Preloading ist in diesen Umgebungen technisch nicht verfügbar.

## 5. Deployment Prozess
Da `opcache.validate_timestamps=0` gesetzt ist, **muss** der Opcache nach jedem Deployment geleert werden.

**Empfohlener Workflow:**
1. Code deployen (Symlink switch).
2. `config/preload.php` ist am neuen Ort verfügbar.
3. PHP-FPM Prozess neu laden:
   ```bash
   service php8.3-fpm reload
   ```