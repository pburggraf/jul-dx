<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/lib/password.php';

final class PasswordHashingTest extends TestCase
{
    public function testNewPasswordsUseModernHashing(): void
    {
        $hash = getpwhash('correct horse battery staple', 42);

        self::assertTrue(is_modern_password_hash($hash));
        self::assertTrue(password_matches('correct horse battery staple', 42, $hash));
        self::assertFalse(password_needs_upgrade($hash));
    }

    public function testLegacyHashesStillValidateAndAreMarkedForUpgrade(): void
    {
        $hash = getlegacy_password_hash('old-password', 7);

        self::assertFalse(is_modern_password_hash($hash));
        self::assertTrue(password_matches('old-password', 7, $hash));
        self::assertTrue(password_needs_upgrade($hash));
    }
}
