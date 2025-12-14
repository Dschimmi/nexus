<?php

declare(strict_types=1);

namespace MrWo\Nexus\Contract;

/**
 * Schnittstelle für die Transformation von Daten.
 * Dient der Standardisierung von Import/Export-Prozessen (Ticket 51).
 */
interface DataTransformerInterface
{
    /**
     * Transformiert Rohdaten (z.B. aus CSV) in ein internes Format.
     */
    public function transform(mixed $data): mixed;

    /**
     * Transformiert interne Daten in ein Zielformat (z.B. für CSV-Export).
     */
    public function reverseTransform(mixed $data): mixed;
}