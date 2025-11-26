# Internationalisierung (i18n)

Das Nexus-Framework ist vollständig mehrsprachig aufgebaut. Alle für den Benutzer sichtbaren Texte werden über ein zentrales Übersetzungssystem verwaltet.

## Übersetzungsdateien

Alle Übersetzungen werden in sprachspezifischen PHP-Dateien im Verzeichnis `/translations` abgelegt. Jede Datei (z.B. `de.php`, `en.php`) gibt ein assoziatives Array zurück, das Übersetzungsschlüssel auf den übersetzten Text abbildet.

**Beispiel (`/translations/de.php`):**
```php
<?php
return [
    'welcome_message' => 'Willkommen bei Nexus!',
];