<?php
declare(strict_types=1);

/**
 * OPcache extension stubs for static analysis.
 * @link https://www.php.net/opcache
 */

/**
 * Compiles a PHP script without executing it.
 *
 * @param string $filename The path to the PHP file.
 * @return bool True on success, false on failure.
 */
function opcache_compile_file(string $filename): bool { return true; }

/**
 * Invalidates a cached script in OPcache.
 *
 * @param string $filename The path to the PHP file.
 * @param bool $force Force invalidation even if the file is unchanged.
 * @return bool True on success, false on failure.
 */
function opcache_invalidate(string $filename, bool $force = false): bool { return true; }

/**
 * Checks if a script is cached in OPcache.
 *
 * @param string $filename The path to the PHP file.
 * @return bool True if cached, false otherwise.
 */
function opcache_is_script_cached(string $filename): bool { return true; }

/**
 * Returns status information about OPcache.
 *
 * @param bool $fetch_scripts Whether to include information about cached scripts.
 * @return array<string, mixed>|false Status array or false on failure.
 */
function opcache_get_status(bool $fetch_scripts = true): array|false { return false; }

/**
 * Returns configuration information about OPcache.
 *
 * @return array<string, mixed>|false Configuration array or false on failure.
 */
function opcache_get_configuration(): array|false { return false; }

/**
 * Resets the entire OPcache.
 *
 * @return bool True on success, false on failure.
 */
function opcache_reset(): bool { return true; }
