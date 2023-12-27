<?php

require __DIR__ . '/vendor/autoload.php';

function open_database(): \PDO
{
    return new PDO('pgsql:host=127.0.0.1;port=5432;dbname=rgr', 'postgres', 'amogus', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

function bool2str(bool $bool): string
{
    return $bool ? 'true' : 'false';
}

function clock(callable $fun, mixed ...$args): array
{
    $base = microtime(true);
    return [
        $fun(...$args),
        ceil(1000 * (microtime(true) - $base)),
    ];
}