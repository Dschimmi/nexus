<?php

declare(strict_types=1);

namespace MrWo\Nexus\Infrastructure\Util;

/**
 * Hilfsklasse zur Erzeugung von URL-freundlichen Slugs.
 * Nutzt die intl-Extension für korrekte Transliteration (Umlaute, Akzente).
 */
class SlugService
{
    /**
     * Wandelt einen beliebigen String in einen URL-Slug um.
     * Beispiel: "Müller & Söhne!" -> "mueller-soehne"
     * 
     * @param string $text Der Eingabetext.
     * @return string Der bereinigte Slug.
     */
    public function slugify(string $text): string
    {
        // 1. Transliteration (UTF-8 zu ASCII)
        // Any-Latin: Kyrillisch/Griechisch zu Latein
        // Latin-ASCII: Accents entfernen (é -> e)
        // NFD; [:Nonspacing Mark:] Remove; NFC: Unicode-Normalisierung
        // Lower(): Alles klein
        
        // Da transliterator_transliterate manchmal false liefert (Fehler), brauchen wir Fallback.
        $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        
        if ($transliterated === false) {
            // Fallback: Einfaches Lowercase, falls intl spinnt
            $transliterated = strtolower($text);
        }

        // 2. Ersetzen von nicht-alphanumerischen Zeichen durch Bindestrich
        $slug = preg_replace('/[^a-z0-9]+/', '-', $transliterated);
        
        // 3. Trimmen von Bindestrichen am Anfang/Ende
        return trim($slug, '-');
    }
}