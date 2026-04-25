<?php

declare(strict_types=1);

use JulDx\Tests\Support\FakeMysql;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class IndexSmokeTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testIndexPageRendersForGuestRequest(): void
    {
        FakeMysql::reset();

        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_ENV = [];
        $_SERVER = [
            'DOCUMENT_ROOT' => '/Users/philip/Projects/jul-dx',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit Smoke Test',
            'HTTP_REFERER' => '',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'QUERY_STRING' => '',
            'SERVER_NAME' => 'localhost',
        ];

        putenv('SCRIPT_URL=/index.php');
        putenv('QUERY_STRING=');
        putenv('HTTP_CLIENT_IP');
        putenv('HTTP_X_FORWARDED_FOR');

        mt_srand(1234);
        srand(1234);

        chdir(__DIR__ . '/..');

        ob_start();
        require __DIR__ . '/../index.php';
        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString('3 registered users', $output);
        self::assertStringContainsString('Recently active threads', $output);
        self::assertStringContainsString('Main Forum', $output);
        self::assertStringContainsString('Welcome thread', $output);
        self::assertStringContainsString('General discussion', $output);
        self::assertSame('', FakeMysql::$lastError, FakeMysql::$lastError);
    }
}
