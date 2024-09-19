<?php

require __DIR__.'/vendor/autoload.php';

const LOG_FILE = 'logs.txt';

function addLogs($ext): void
{
    file_put_contents(LOG_FILE, print_r(date('Y-m-d H:i:s'), true).':'.PHP_EOL,
        FILE_APPEND | LOCK_EX);
    file_put_contents(LOG_FILE, print_r($ext, true).PHP_EOL,
        FILE_APPEND | LOCK_EX);
}

?>

<h1>File Storage</h1>
