# Request Lifecycle

Jede Anfrage an das Nexus-Framework durchläuft die folgenden Schritte:

1.  **Einstiegspunkt (`public/index.php`):**
    *   Der Composer Autoloader wird geladen.
    *   Die `.env`-Datei wird geladen, um die Umgebung (`APP_ENV`) zu bestimmen.
    *   Tracy wird basierend auf der Umgebung aktiviert.
    *   Ein `Request`-Objekt wird erstellt.
    *   Der `Kernel` wird instanziiert und bekommt die `APP_ENV` übergeben.

2.  **Kernel (`src/Kernel/Kernel.php`):**
    *   Die `handleRequest`-Methode wird aufgerufen.
    *   `resolveRoute()`: Die passende Route wird gesucht.
    *   `resolveController()`: Der zuständige Controller wird instanziiert.
    *   Der Controller wird ausgeführt und gibt ein `Response`-Objekt zurück.
    *   Bei Fehlern fängt der `try-catch`-Block diese ab und die `handleError()`-Methode generiert eine Fehler-Response.

3.  **Ausgabe (`public/index.php`):**
    *   Die Methode `$response->send()` gibt die vom Kernel erzeugte Antwort an den Browser aus.