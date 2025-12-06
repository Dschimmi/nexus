# Audit Nexus Code-Review 06.12.2025

## Audit-Ergebnis Block 1:

### PHP Version (3.1.1): 
- In composer.json fehlt die explizite PHP-Anforderung ("php": ">=8.2" in require).
  - Status: ⚠️ Warnung (Es funktioniert, aber Deployment könnte auf altem PHP laufen).
> Todo: die explizite PHP-Anforderung `"php": ">=8.2"` in require einfügen
    
### Dependencies (3.3):
- Symfony HttpFoundation, Routing, DependencyInjection: ✅ Vorhanden.
- Twig: ✅ Vorhanden.
- Tracy: ✅ Vorhanden.
- PDO: ❌ Fehlt in require (ext-pdo sollte gelistet sein, um sicherzugehen).
> Todo: `ext-pdo` listen
    
### Entry Point (3.1.2): 
- public/index.php ist da und lädt Autoloader, Dotenv, Kernel. ✅

### HTTPS-Zwang (5.2):
- Ist im index.php hart implementiert (if ($appEnv === 'production')... die(...)). ✅

### Rewriting: 
- .htaccess leitet alles außer Assets auf index.php.
  - Status: ⚠️ Warnung (Es funktioniert, mögliche Sicherheitslücke!)
> Todo: 
- Assetliste erweitern: `RewriteCond %{REQUEST_URI} !\.(ico|css|js|gif|jpe?g|png|woff2?|ttf|eot|svg|json|webmanifest|mp4|webm|pdf)$ [NC]`
- Direkten Zugriff auf .php-Dateien unterbinden: 
```
# Blockiere direkten Zugriff auf PHP-Dateien (außer index.php)
RewriteCond %{REQUEST_URI} !^/index\.php$ [NC]
RewriteCond %{REQUEST_FILENAME} \.php$ [NC]
RewriteRule . index.php [L]
```
