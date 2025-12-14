<?php

declare(strict_types=1);

namespace MrWo\Nexus\Attribute;

use Attribute;

/**
 * Markiert einen Controller oder eine Methode als öffentlich zugänglich.
 * Umgeht die "Deny by Default" Firewall.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class IsPublic
{
}