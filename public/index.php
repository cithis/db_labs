<?php

use function FastRoute\simpleDispatcher;

require '../common.php';

session_start();
header("Referrer-Policy: unsafe-url");

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri        = $_SERVER['REQUEST_URI'];

if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

if (file_exists($f = __DIR__ . $uri) && is_file($f)) {
    readfile($f);
    exit;
}

$dispatcher = simpleDispatcher(require __DIR__ . '/../src/routes.php', [
    'cacheFile'     => __DIR__ . '/../route.cache',
    'cacheDisabled' => true,
]);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        header("HTTP/1.0 404");
        echo "<center><h1>Not Found</h1><hr/>php</center>";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        header("HTTP/1.0 405");
        echo "<center><h1>Method Not Allowed</h1><hr/>php</center>";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = explode(":", $routeInfo[1]);
        $vars    = $routeInfo[2];
        
        $controller = 'App\\Controllers\\' . $handler[0] . 'Controller';
        $controller = new $controller(open_database());
        $controller->{$handler[1]}(...array_values($vars));
        break;
}