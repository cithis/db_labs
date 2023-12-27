<?php

namespace App\Controllers;
use App\Util\View;
use Jdenticon\Identicon;

final class HomeController extends AbstractController
{
    function home(): void
    {
        $this->render('Home/index.latte');
    }
    
    function identicon(string $seed): void
    {
        header('Cache-Control: max-age=3600');
        
        $ico = new Identicon;
        $ico->setValue($seed);
        $ico->setSize(96);
        $ico->displayImage('jpeg');
    }
}