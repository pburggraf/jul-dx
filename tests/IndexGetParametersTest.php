<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IndexGetParametersTest extends TestCase
{
    public function testIndexPageRendersForDefaultGuestRequest(): void
    {
        $result = $this->runIndexRequest();

        self::assertSame('', $result['error']);
        self::assertStringContainsString('3 registered users', $result['output']);
        self::assertStringContainsString('Recently active threads', $result['output']);
        self::assertStringContainsString('Main Forum', $result['output']);
        self::assertStringContainsString('Welcome thread', $result['output']);
        self::assertStringContainsString('General discussion', $result['output']);
    }

    #[DataProvider('redirectParameters')]
    public function testRedirectGetParameters(string $parameter, string $value, string $expectedLocation): void
    {
        $result = $this->runIndexRequest([
            'get' => [
                $parameter => $value,
            ],
        ]);

        self::assertSame('', $result['error']);
        self::assertContains("Location: {$expectedLocation}", $result['headers']);
    }

    public function testMarkForumReadActionRedirectsAndRunsExpectedQueries(): void
    {
        $result = $this->runIndexRequest([
            'logged_in' => true,
            'get' => [
                'action' => 'markforumread',
                'forumid' => '1',
            ],
        ]);

        self::assertSame('', $result['error']);
        self::assertContains('Location: index.php', $result['headers']);
        self::assertQueryContains($result['queries'], "DELETE FROM forumread WHERE user=4 AND forum='1'");
        self::assertQueryContains($result['queries'], 'INSERT INTO forumread (user,forum,readdate) VALUES (4,1,');
    }

    public function testMarkAllForumsReadActionRedirectsAndRunsExpectedQueries(): void
    {
        $result = $this->runIndexRequest([
            'logged_in' => true,
            'get' => [
                'action' => 'markallforumsread',
            ],
        ]);

        self::assertSame('', $result['error']);
        self::assertContains('Location: index.php', $result['headers']);
        self::assertQueryContains($result['queries'], 'DELETE FROM forumread WHERE user=4');
        self::assertQueryContains($result['queries'], 'INSERT INTO forumread (user,forum,readdate) SELECT 4,id,');
    }

    public function testOldCounterSwitchesToLegacyStatsBlip(): void
    {
        $result = $this->runIndexRequest([
            'get' => [
                'oldcounter' => '1',
            ],
        ]);

        self::assertSame('', $result['error']);
        self::assertStringContainsString('4 posts during the last day, 1 posts during the last hour.', $result['output']);
    }

    public function testCatFiltersForumListingToSelectedCategory(): void
    {
        $result = $this->runIndexRequest([
            'multiple_categories' => true,
            'get' => [
                'cat' => '2',
            ],
        ]);

        self::assertSame('', $result['error']);
        self::assertStringContainsString('Project Forum', $result['output']);
        self::assertStringContainsString('Side discussion', $result['output']);
        self::assertStringNotContainsString('General discussion', $result['output']);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function redirectParameters(): iterable
    {
        yield 'user profile shortcut' => ['u', '42', 'profile.php?id=42'];
        yield 'post shortcut' => ['p', '77', 'thread.php?pid=77#77'];
        yield 'thread shortcut' => ['t', '15', 'thread.php?id=15'];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{headers: list<string>, output: string, error: string, queries: list<string>}
     */
    private function runIndexRequest(array $payload = []): array
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/Support/run_index_request.php') . ' ' . escapeshellarg(json_encode($payload, JSON_THROW_ON_ERROR));
        $raw = shell_exec($command);

        self::assertIsString($raw, 'Failed to execute index request runner.');

        /** @var array{headers: list<string>, output: string, error: string, queries: list<string>} $decoded */
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @param list<string> $queries
     */
    private static function assertQueryContains(array $queries, string $needle): void
    {
        foreach ($queries as $query) {
            if (str_contains($query, $needle)) {
                self::assertTrue(true);

                return;
            }
        }

        self::fail("Failed asserting that queries contain: {$needle}");
    }
}
