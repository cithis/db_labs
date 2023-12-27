<?php

namespace App\Util;
use Latte;

class View
{
    public static function render(string $template, array $data = []): void
    {
        static $latte = NULL;
        if (is_null($latte)) {
            $latte = new Latte\Engine();
            $latte->setTempDirectory(__DIR__ . '/../../tmp');
        }
        
        if ($template[0] != '/')
            $template = __DIR__ . "/../Templates/$template";
        
        $latte->render($template, $data);
    }
}