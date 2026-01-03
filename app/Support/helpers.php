<?php

declare(strict_types=1);

/**
 * Execute the callable within try block.
 *
 * @template TCallback
 * @template TDefault
 *
 * @param  callable():TCallback  $callback
 * @param  TDefault|null  $default
 * @return ($default is null ? TCallback|null : TCallback|TDefault)
 */
function catchup(callable $callback, $default = null)
{
    try {
        return $callback();
    } catch (Throwable) {
        return $default;
    }
}

/**
 * Build a cache key scoped to the current tenant.
 */
function tenant_cache_key(string $key): string
{
    $tenantId = App\Support\TenantContext::currentId();

    return $tenantId ? sprintf('tenant:%s:%s', $tenantId, $key) : $key;
}

/**
 * Build a storage path scoped to the current tenant.
 */
function tenant_storage_path(string $path): string
{
    $tenantId     = App\Support\TenantContext::currentId();
    $relativePath = ltrim($path, '/');

    return $tenantId ? sprintf('tenants/%s/%s', $tenantId, $relativePath) : $relativePath;
}
