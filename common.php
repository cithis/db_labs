<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Pagination\Paginator;

function open_database(): \PDO
{
    return new PDO('pgsql:host=127.0.0.1;port=5433;dbname=postgres', 'postgres', 'amogus', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
}

function prepare_orm(): void
{
    $capsule = new Capsule;
    $capsule->addConnection([
        'driver'    => 'pgsql',
        'host'      => '127.0.0.1',
        'port'      => 5433,
        'database'  => 'postgres',
        'username'  => 'postgres',
        'password'  => 'amogus',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
    ]);
    
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    
    Paginator::currentPageResolver(function ($pageName = 'page') {
        return (int) ($_GET[$pageName] ?? 1);
    });
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

prepare_orm();