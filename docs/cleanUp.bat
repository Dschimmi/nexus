@echo off
setlocal

:: --- KONFIGURATION ---
set "SOURCE_DIR=."
set "ARCHIVE_DIR=_archive"
set "DAYS_TO_KEEP=28"

:: Ordner erstellen, falls nicht vorhanden
if not exist "%ARCHIVE_DIR%" mkdir "%ARCHIVE_DIR%"

echo ========================================================
echo  NEXUS DOKUMENTATIONS-CLEANUP
echo ========================================================
echo.

:: 1. VERSCHIEBEN (Robocopy)
:: /S   = Unterordner mitnehmen
:: /MOV = Verschieben (kopieren + quelle löschen)
:: /XD  = Exclude Directories (Archiv selbst, Git und versteckte Ordner auslassen)
:: /NFL /NDL = Logs klein halten (No File List, No Dir List)

echo [1/2] Verschiebe alle *.pdf nach %ARCHIVE_DIR%...
robocopy "%SOURCE_DIR%" "%ARCHIVE_DIR%" *.pdf /S /MOV /XD "%ARCHIVE_DIR%" ".git" "vendor" "node_modules"

:: Robocopy Exit Codes: 0=No Files, 1=Files Copied. Alles unter 8 ist okay.
if %ERRORLEVEL% GEQ 8 (
    echo FEHLER beim Verschieben!
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo [2/2] Bereinige Archiv (Dateien aelter als %DAYS_TO_KEEP% Tage)...

:: 2. BEREINIGEN (Forfiles)
:: /P  = Pfad
:: /S  = Rekursiv im Archiv suchen
:: /D -X = Älter als X Tage
:: /C  = Befehl ausführen (del)

forfiles /P "%ARCHIVE_DIR%" /S /M *.pdf /D -%DAYS_TO_KEEP% /C "cmd /c echo Loesche @path & del @path" 2>nul

if %ERRORLEVEL% NEQ 0 (
    echo   -> Nichts zu loeschen oder Archiv ist leer.
)

echo.
echo ========================================================
echo  FERTIG. Dein Workspace ist sauber.
echo ========================================================
timeout /t 5