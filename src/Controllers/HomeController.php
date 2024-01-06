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
        header('Expires: Thu, 19 Nov 2077 08:52:00 GMT');
        
        $ico = new Identicon;
        $ico->setValue($seed);
        $ico->setSize(96);
        $ico->displayImage('jpeg');
    }
}