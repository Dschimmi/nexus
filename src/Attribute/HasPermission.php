<?php

declare(strict_types=1);

namespace MrWo\Nexus\Attribute;

use Attribute;

/**
 * Fordert eine spezifische Berechtigung für den Zugriff an.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class HasPermission
{
    public function __construct(
        public string $permission
    ) {}
}