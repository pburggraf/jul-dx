<?php

declare(strict_types=1);

function getlegacy_password_hash(string $pass, int $id): string
{
    return sha1(md5((string) $id) . $pass);
}

function is_modern_password_hash(string $hash): bool
{
    $info = password_get_info($hash);

    return $info['algo'] !== null;
}

function getpwhash(string $pass, int $id): string
{
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    if (is_string($hash) && $hash !== '') {
        return $hash;
    }

    return getlegacy_password_hash($pass, $id);
}

function password_matches(string $pass, int $id, string $storedHash): bool
{
    if (is_modern_password_hash($storedHash)) {
        return password_verify($pass, $storedHash);
    }

    return hash_equals($storedHash, getlegacy_password_hash($pass, $id));
}

function password_needs_upgrade(string $storedHash): bool
{
    if (!is_modern_password_hash($storedHash)) {
        return true;
    }

    return password_needs_rehash($storedHash, PASSWORD_DEFAULT);
}
