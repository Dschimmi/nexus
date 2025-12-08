# ADR 017: Entkopplung der Benutzer-Authentifizierung (Repository Pattern)

**Status:** Akzeptiert  
**Datum:** 2025-12-06  
**Autor:** Architecture Team  
**Betroffene Komponenten:** `AuthenticationService`, `UserRepositoryInterface`, `EnvUserRepository`

## Kontext

Der ursprüngliche `AuthenticationService` war fest ("hardcoded") an die Umgebungsvariablen (`.env`) gekoppelt. Er erwartete `ADMIN_USER`, `ADMIN_EMAIL` und `ADMIN_PASSWORD_HASH` direkt im Konstruktor.
Dies stand im Konflikt mit der geplanten Einführung des Moduls "Userverwaltung" (PH 4.2.1), das Benutzerdaten aus einer Datenbank laden muss. Um das Modul zu integrieren, hätte der Core-Service umgeschrieben werden müssen, was das "Open/Closed Principle" verletzt.

## Entscheidung

Wir stellen die Authentifizierung auf das **Repository Pattern** um.

1.  **Interface:** Einführung von `UserRepositoryInterface`. Es definiert die Methode `findByIdentifier(string $identifier): ?User`.
2.  **Entity:** Einführung einer anämischen `User` Entity als Datentransferobjekt (DTO) zwischen Repository und Service.
3.  **Implementierung:** Der bestehende `.env`-Zugriff wurde in eine eigene Klasse `EnvUserRepository` extrahiert, die das Interface implementiert.
4.  **Dependency Injection:** Der `AuthenticationService` erhält nun nicht mehr Strings, sondern das `UserRepositoryInterface` injiziert.

## Konsequenzen

### Positiv
*   **Erweiterbarkeit:** Das kommende User-Modul kann einfach eine eigene Implementierung (`DatabaseUserRepository`) bereitstellen und im Container registrieren ("Provider Swap"). Der Core-Code (`AuthenticationService`) bleibt dabei unverändert.
*   **Testbarkeit:** Im Unit-Test kann das Repository einfach gemockt werden, ohne mit echten Passwörtern hantieren zu müssen.
*   **Sauberkeit:** Die Authentifizierungslogik (Passwort-Verify) ist von der Datenbeschaffung (Wo liegt der User?) getrennt.

### Negativ
*   **Komplexität:** Es wurden drei neue Dateien (`User.php`, `UserRepositoryInterface.php`, `EnvUserRepository.php`) eingeführt, um eine Logik abzubilden, die vorher in 5 Zeilen passte. Dies ist der Preis für die Modularität.