# Services und Dependency Injection

Das Nexus-Framework verwendet einen Dependency Injection (DI) Container, um die Erstellung und Verwaltung von zentralen Diensten (Services) zu zentralisieren. Dies fördert lose Kopplung und hohe Testbarkeit.

## Service-Definition

Alle Services werden in der Datei `/config/services.php` registriert. Diese Datei gibt eine Funktion zurück, die den `ContainerBuilder` entgegennimmt.

**Beispiel:**
php
// in /config/services.php

$container->register('session_service', SessionService::class)
    ->setPublic(true);