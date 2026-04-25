<?php

declare(strict_types=1);

use JulDx\Tests\Support\FakeMysql;

require __DIR__ . '/../bootstrap.php';

/**
 * @return array<string, mixed>
 */
function request_payload(): array
{
    global $argv;

    $json = $argv[1] ?? '{}';
    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

/**
 * @param array<string, mixed> $payload
 */
function setup_request(array $payload): void
{
    $root = dirname(__DIR__, 2);
    $password = 'stored-password-hash';

    FakeMysql::reset([
        'multiple_categories' => (bool) ($payload['multiple_categories'] ?? false),
        'user_password' => $password,
    ]);

    $_GET = is_array($payload['get'] ?? null) ? $payload['get'] : [];
    $_POST = [];
    $_ENV = [];
    $_COOKIE = [];
    $_SERVER = [
        'DOCUMENT_ROOT' => $root,
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'PHPUnit Index Request',
        'HTTP_REFERER' => '',
        'SCRIPT_NAME' => '/index.php',
        'PHP_SELF' => '/index.php',
        'QUERY_STRING' => http_build_query($_GET),
        'SERVER_NAME' => 'localhost',
    ];

    if (($payload['logged_in'] ?? false) === true) {
        $_COOKIE['loguserid'] = '4';
        $_COOKIE['logverify'] = '0' . sha1($password . 'verification IP: ');
    }

    putenv('SCRIPT_URL=/index.php');
    putenv('QUERY_STRING=' . $_SERVER['QUERY_STRING']);
    putenv('HTTP_CLIENT_IP');
    putenv('HTTP_X_FORWARDED_FOR');

    mt_srand(1234);
    srand(1234);
    chdir($root);
}

register_shutdown_function(static function (): void {
    $headers = function_exists('xdebug_get_headers') ? xdebug_get_headers() : headers_list();
    $output = ob_get_contents() ?: '';

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode([
        'headers' => $headers,
        'output' => $output,
        'error' => FakeMysql::$lastError,
        'queries' => FakeMysql::$queries,
    ], JSON_THROW_ON_ERROR);
});

setup_request(request_payload());
ob_start();
require dirname(__DIR__, 2) . '/index.php';
