<?php

declare(strict_types=1);

namespace JulDx\Tests\Support {
    final class FakeMysqlResult
    {
        /**
         * @param list<array<string, mixed>> $rows
         */
        public function __construct(
            public array $rows,
            public int $cursor = 0,
        ) {
        }
    }

    final class FakeMysql
    {
        public static string $lastError = '';

        public static function reset(): void
        {
            self::$lastError = '';
        }

        public static function query(string $query): FakeMysqlResult|bool
        {
            $normalized = self::normalize($query);

            return match (true) {
                str_contains($normalized, 'select `disable` from `misc` where 1') => self::rows([
                    ['disable' => 0],
                ]),
                str_contains($normalized, 'select `date` from `posts` where (`id` % 100000) = 0') => self::rows([
                    ['date' => 0],
                ]),
                str_contains($normalized, 'select `name`, `file` from schemes where id=') => self::rows([
                    ['name' => 'Night', 'file' => 'night.php'],
                ]),
                str_contains($normalized, 'select count(*) from ipbans where') => self::rows([
                    ['count' => 0],
                ]),
                str_contains($normalized, 'select count(*) from `tor` where `ip` =') => self::rows([
                    ['count' => 0],
                ]),
                $normalized === 'select views from misc' => self::rows([
                    ['views' => 4321],
                ]),
                str_contains($normalized, 'select id,name,birthday,sex,powerlevel,aka from users where from_unixtime') => self::rows([]),
                str_contains($normalized, 'select id,name,powerlevel,lastactivity,sex,minipic,aka,birthday from users where lastactivity >') => self::rows([
                    [
                        'id' => 2,
                        'name' => 'Alice',
                        'powerlevel' => 0,
                        'lastactivity' => time(),
                        'sex' => 0,
                        'minipic' => '',
                        'aka' => '',
                        'birthday' => 0,
                    ],
                ]),
                str_contains($normalized, 'select count(*) from guests where date>') => self::rows([
                    ['count' => 2],
                ]),
                str_contains($normalized, 'select id,name,sex,powerlevel,aka,birthday from users order by id desc limit 1') => self::rows([
                    [
                        'id' => 3,
                        'name' => 'NewestUser',
                        'sex' => 0,
                        'powerlevel' => 0,
                        'aka' => '',
                        'birthday' => 0,
                    ],
                ]),
                str_contains($normalized, 'select (select count( * ) from posts where date>') && str_contains($normalized, 'as h') => self::rows([
                    ['h' => 1, 'd' => 4],
                ]),
                str_contains($normalized, 'select (select count( * ) from users) as u, (select count(*) from threads) as t, (select count(*) from posts) as p') => self::rows([
                    ['u' => 3, 't' => 7, 'p' => 9],
                ]),
                $normalized === 'select * from misc' => self::rows([
                    [
                        'disable' => 0,
                        'views' => 4321,
                        'maxpostsday' => 10,
                        'maxpostsdaydate' => time(),
                        'maxpostshour' => 5,
                        'maxpostshourdate' => time(),
                        'maxusers' => 10,
                        'maxusersdate' => time(),
                        'maxuserstext' => '',
                    ],
                ]),
                str_contains($normalized, 'select count(*) from `threads` where `lastpostdate` >') => self::rows([
                    ['count' => 2],
                ]),
                str_contains($normalized, 'select count(*) from `users` where `lastposttime` >') => self::rows([
                    ['count' => 1],
                ]),
                str_contains($normalized, 'from `threads` t left join `forums` f on t.forum = f.id left join `users` u on t.lastposter = u.id') => self::rows([
                    [
                        'id' => 11,
                        'lastposter' => 2,
                        'date' => time() - 600,
                        'ftitle' => 'Main Forum',
                        'fid' => 1,
                        'title' => 'Welcome thread',
                        'user' => 2,
                        'uname' => 'Alice',
                        'usex' => 0,
                        'upowerlevel' => 0,
                    ],
                ]),
                str_contains($normalized, 'select f.*,u.id as uid,name,sex,powerlevel,aka,birthday from forums f left join users u on f.lastpostuser=u.id') => self::rows([
                    [
                        'id' => 1,
                        'catid' => 1,
                        'title' => 'Main Forum',
                        'description' => 'General discussion',
                        'numthreads' => 7,
                        'numposts' => 9,
                        'lastpostdate' => time() - 600,
                        'lastpostid' => 21,
                        'uid' => 2,
                        'name' => 'Alice',
                        'sex' => 0,
                        'powerlevel' => 0,
                        'aka' => '',
                        'birthday' => 0,
                        'minpower' => 0,
                        'hidden' => '0',
                        'forder' => 1,
                    ],
                ]),
                str_contains($normalized, 'select id,name from categories where (!minpower or minpower<=') => self::rows([
                    ['id' => 1, 'name' => 'General'],
                ]),
                str_contains($normalized, 'select u.id id,name,sex,powerlevel,aka,forum,birthday from users u inner join forummods m on u.id=m.user order by name') => self::rows([]),
                str_starts_with($normalized, 'delete from guests where ip=') => true,
                str_starts_with($normalized, 'insert into guests (ip,date,useragent,lasturl) values') => true,
                str_starts_with($normalized, 'update misc set views=') => true,
                str_starts_with($normalized, 'insert into dailystats (date, users, threads, posts, views) values') => true,
                str_starts_with($normalized, 'update misc set maxpostsday=') => true,
                str_starts_with($normalized, 'update misc set maxpostshour=') => true,
                str_starts_with($normalized, 'update misc set maxusers=') => true,
                str_starts_with($normalized, 'insert into referer (time,url,ref,ip) values') => true,
                default => self::unknownQuery($query),
            };
        }

        public static function seek(FakeMysqlResult $result, int $offset): bool
        {
            if (!isset($result->rows[$offset])) {
                return false;
            }

            $result->cursor = $offset;

            return true;
        }

        private static function normalize(string $query): string
        {
            return strtolower(preg_replace('/\s+/', ' ', trim($query)) ?? trim($query));
        }

        /**
         * @param list<array<string, mixed>> $rows
         */
        private static function rows(array $rows): FakeMysqlResult
        {
            return new FakeMysqlResult($rows);
        }

        private static function unknownQuery(string $query): bool
        {
            self::$lastError = "Unhandled fake mysql query: {$query}";

            return false;
        }
    }
}

namespace {
    use JulDx\Tests\Support\FakeMysql;
    use JulDx\Tests\Support\FakeMysqlResult;

    if (!defined('MYSQL_ASSOC')) {
        define('MYSQL_ASSOC', 1);
    }
    if (!defined('MYSQL_NUM')) {
        define('MYSQL_NUM', 2);
    }
    if (!defined('MYSQL_BOTH')) {
        define('MYSQL_BOTH', 3);
    }

    if (!function_exists('mysql_connect')) {
        function mysql_connect(
            ?string $hostname = null,
            ?string $username = null,
            ?string $password = null,
            bool $new = false,
            int $flags = 0,
        ): object|false {
            return (object) [
                'hostname' => $hostname,
                'username' => $username,
                'password' => $password,
                'new' => $new,
                'flags' => $flags,
            ];
        }
    }

    if (!function_exists('mysql_pconnect')) {
        function mysql_pconnect(
            ?string $hostname = null,
            ?string $username = null,
            ?string $password = null,
            int $flags = 0,
        ): object|false {
            return mysql_connect($hostname, $username, $password, true, $flags);
        }
    }

    if (!function_exists('mysql_select_db')) {
        function mysql_select_db(string $databaseName, ?object $link = null): bool
        {
            return true;
        }
    }

    if (!function_exists('mysql_set_charset')) {
        function mysql_set_charset(string $charset, ?object $link = null): bool
        {
            return true;
        }
    }

    if (!function_exists('mysql_query')) {
        function mysql_query(string $query, ?object $link = null): FakeMysqlResult|bool
        {
            return FakeMysql::query($query);
        }
    }

    if (!function_exists('mysql_num_rows')) {
        function mysql_num_rows(FakeMysqlResult $result): int
        {
            return count($result->rows);
        }
    }

    if (!function_exists('mysql_fetch_array')) {
        function mysql_fetch_array(FakeMysqlResult $result, int $resultType = MYSQL_BOTH): array|false
        {
            $row = $result->rows[$result->cursor] ?? null;
            if ($row === null) {
                return false;
            }

            ++$result->cursor;

            if ($resultType === MYSQL_ASSOC) {
                return $row;
            }

            $numeric = array_values($row);
            if ($resultType === MYSQL_NUM) {
                return $numeric;
            }

            return $row + $numeric;
        }
    }

    if (!function_exists('mysql_result')) {
        function mysql_result(FakeMysqlResult $result, int $row, int|string $field = 0): mixed
        {
            $current = $result->rows[$row] ?? null;
            if ($current === null) {
                return false;
            }

            if (is_int($field) || ctype_digit((string) $field)) {
                $values = array_values($current);

                return $values[(int) $field] ?? false;
            }

            return $current[$field] ?? false;
        }
    }

    if (!function_exists('mysql_data_seek')) {
        function mysql_data_seek(FakeMysqlResult $result, int $offset): bool
        {
            return FakeMysql::seek($result, $offset);
        }
    }

    if (!function_exists('mysql_real_escape_string')) {
        function mysql_real_escape_string(string $string, ?object $link = null): string
        {
            return addslashes($string);
        }
    }

    if (!function_exists('mysql_error')) {
        function mysql_error(?object $link = null): string
        {
            return FakeMysql::$lastError;
        }
    }
}
