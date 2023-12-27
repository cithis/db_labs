<?php

namespace App\Controllers;

use App\Util\View;
use JetBrains\PhpStorm\NoReturn;

abstract class AbstractController
{
    protected \PDO $database;
    
    public function __construct(\PDO $database)
    {
        $this->database = $database;
    }
    
    #[NoReturn]
    protected function notFound(): void
    {
        header('HTTP/1.1 404 Not Found');
        echo "<center><h1>Not Found</h1><hr/>php</center>";
        exit;
    }
    
    protected function getPage(string $id = 'page'): int
    {
        if (!isset($_GET[$id]) || !ctype_digit($_GET[$id]))
            return 1;
        
        return max(1, (int) $_GET[$id]);
    }
    
    protected function boolSelect(string $id, array $values, ?bool $default = NULL): ?bool
    {
        $val = $_GET[$id] ?? NULL;
        if (is_null($val))
            return $default;
        
        return $val == $values[0]
            ? true
            : ($val == $values[1] ? false : NULL);
    }
    
    protected function catchNotFound(callable $cb, string $exceptionClass): mixed
    {
        try {
            return $cb();
        } catch (\Exception $e) {
            $e instanceof $exceptionClass
                ? $this->notFound()
                : throw $e;
        }
    }
    
    protected function addFlash(string $kind, string $message): void
    {
        if (!isset($_SESSION['flashes']))
            $_SESSION['flashes'] = [];
        
        $_SESSION['flashes'][] = [$kind, $message];
    }
    
    protected function getFlashes(bool $peek = false): array
    {
        $flashes = $_SESSION['flashes'] ?? [];
        if (!$peek)
            $_SESSION['flashes'] = [];
        
        return $flashes;
    }
    
    protected function render(string $template, array $data = []): void
    {
        View::render($template, array_merge($data, [
            'flashes' => $this->getFlashes(),
        ]));
    }
    
    #[NoReturn]
    protected function redirect(string $addr, int $code = 2): void
    {
        header("HTTP/1.1 30$code Found");
        header("Location: $addr");
        exit;
    }
    
    #[NoReturn]
    protected function back(string $default = '/'): void
    {
        $this->redirect($_SERVER['HTTP_REFERER'] ?? $default);
    }
}