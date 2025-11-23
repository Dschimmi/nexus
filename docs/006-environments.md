# Umgebungs-spezifische Konfiguration

Das Nexus-Framework unterscheidet strikt zwischen verschiedenen Betriebsumgebungen, um Sicherheit und Entwicklerkomfort zu gewährleisten. Die Steuerung erfolgt über die Environment-Variable `APP_ENV`.

## Konfiguration

Die Konfiguration erfolgt über eine `.env`-Datei im Projektstamm. Diese Datei wird von Git ignoriert.

```dotenv
# Mögliche Werte: 'development' oder 'production'
APP_ENV=development