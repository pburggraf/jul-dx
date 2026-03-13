<?php

declare(strict_types=1);

header('Content-type: text/plain');
$userid = intval($_GET['u']);

if (!$userid) {
    exit('No userid specified.');
}
chdir('..');
require 'lib/function.php';

echo $sql->resultq("SELECT `posts` FROM `users` WHERE `id` = '$userid'");
