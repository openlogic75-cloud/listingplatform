<?php

namespace App\Support;

/**
 * Deterministic HMAC index for encrypted PII columns.
 *
 * Encrypted columns (Laravel "encrypted" cast) cannot be queried directly.
 * Every encrypted email/phone also stores a blind-index hash in a sibling
 * `*_index` column so lookups (login, guest booking tracking) never require
 * decrypting the table. The hash is keyed, so it is not reversible.
 */
final class BlindIndex
{
    public static function make(string $value): string
    {
        $key = (string) config('services.pii.index_key');

        if ($key === '') {
            // Fall back to the app key so tests and local setups work without
            // a dedicated PII_INDEX_KEY. Production sets a dedicated key.
            $key = (string) config('app.key');
        }

        return hash_hmac('sha256', strtolower(trim($value)), $key);
    }
}
