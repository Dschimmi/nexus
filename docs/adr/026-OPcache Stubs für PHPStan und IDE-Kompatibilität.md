# ADR: OPcache Stubs für PHPStan und IDE-Kompatibilität

**Status:** Accepted  
**Datum:** 2025-12-14  
**Entscheider:** Dschimmi / Nexus Core Team  

## Kontext

Im Nexus-Core-Projekt traten wiederholt Probleme auf, wenn Funktionen der OPcache-Erweiterung verwendet wurden:

1. **PHPStan Fehler:**
   - `Call to unknown function: opcache_compile_file`
   - `return type has no value type specified in iterable type array`
   - `not all code paths return a value`

2. **Editor / Linter Fehler (VS Code + Intelephense):**
   - `not all code paths return a value` für Funktionen mit Rückgabewert und leerem Funktionskörper.
   - Semikolon-Stubs (`function opcache_compile_file(...): bool;`) werden als **Syntax Error** markiert.

Diese Probleme entstanden, weil OPcache eine **Zend Extension** ist und die Funktionen von PHPStan und IDEs standardmäßig nicht erkannt werden. Ohne explizite Stubs könnten Entwickler auf die Idee kommen, unsaubere Workarounds oder „weiche“ Lösungen zu implementieren.

---

## Entscheidung

Wir implementieren **explizite Dummy-Return-Stubs** für alle relevanten OPcache-Funktionen:

- `opcache_compile_file`  
- `opcache_invalidate`  
- `opcache_is_script_cached`  
- `opcache_get_status`  
- `opcache_get_configuration`  
- `opcache_reset`  

Merkmale der Stubs:

1. **Leere Funktionskörper mit Dummy-Rückgabe** (`return true;` oder `return false;`) → Editor + PHPStan zufrieden  
2. **Rückgabewert-Typen** (`bool` oder `array|false`) → PHPStan erkennt die Typen korrekt  
3. **PHPDoc für jede Funktion** → zusätzliche Typinformation für PHPStan und IDE  
4. **Keine Runtime-Operationen** → Stubs dienen ausschließlich der statischen Analyse  
5. **Verpflichtend** → verhindert, dass Entwickler „weiche“ Workarounds bauen

Beispiel:

function opcache_compile_file(string $filename): bool { return true; }  
function opcache_get_status(bool $fetch_scripts = true): array|false { return false; }

---

## Konsequenzen

- **Vorteile:**
  - Alle PHPStan-Fehler für OPcache verschwinden  
  - Editor/Linter erkennen keine Warnungen mehr  
  - Einheitliche Basis für Nexus-Core  
  - Entwickler können keinen unsauberen Workaround einführen  

- **Nachteile / Limitationen:**
  - Stubs müssen bei zukünftigen Erweiterungen von OPcache ggf. aktualisiert werden  
  - Dummy-Rückgaben repräsentieren **nicht die Runtime**, dienen nur der statischen Analyse

---

## Status-Quo nach Umsetzung

- Stubs liegen in `stubs/opcache.php`  
- `phpstan.neon` inkludiert die Stubs via `stubFiles`  
- Alle PHPStan- und Editor-Warnungen zu OPcache-Funktionen sind verschwunden  
- Tracy Debugger zeigt keine Fehler mehr  
- Das Projekt ist nun **sauber und wartbar**, ohne temporäre oder unsaubere Lösungen

---

**ADR-Begründung:**  
Diese Entscheidung stellt sicher, dass die Codebasis sauber, wartbar und analysierbar bleibt. Dummy-Return-Stubs sind der **einzige praktikable Weg**, um PHPStan und Editor gleichzeitig zufriedenzustellen, ohne dass Entwickler unsaubere Workarounds implementieren müssen.
