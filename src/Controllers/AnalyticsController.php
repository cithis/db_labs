<?php

namespace App\Controllers;

use App\Services\OlapService;

final class AnalyticsController extends AbstractController
{
    protected OlapService $olap;

    function __construct(\PDO $database)
    {
        parent::__construct($database);

        $this->olap = new OlapService($database);
    }

    function index(): void
    {
        $this->render('Analytics/index.latte');
    }

    function octopusPosts(): void
    {
        $bl   = $_GET['blacklist'] ?? 'KFP';
        $ml   = (int) ($_GET['minLength'] ?? 0);
        $page = $this->getPage();
        [$octopi, $time] = clock(function() use ($bl, $ml, $page) {
            return $this->olap->getOctopusPosts($bl, $ml, $page);
        });
        
        $this->addFlash('info', "Query took $time" . 'ms');
        $this->render('Analytics/octopi.latte', [
            'bl'     => $bl,
            'ml'     => $ml,
            'octopi' => $octopi,
            'page'   => $page,
        ]);
    }

    function richestUsers(): void
    {
        $expiry   = new \DateTime($_GET['expiry'] ?? ('@' . time()));
        $spending = (int) ($_GET['minSpending'] ?? 0);
        $page     = $this->getPage();
        [$users, $time] = clock(function() use ($expiry, $spending, $page) {
            return $this->olap->getRichestUsers($expiry, $spending, $page);
        });
    
        $this->addFlash('info', "Query took $time" . 'ms');
        $this->render('Analytics/richest_users.latte', [
            'users'  => $users,
            'page'   => $page,
            'expiry' => $expiry,
            'spend'  => $spending,
        ]);
    }

    function richestCreators(): void
    {
        $expiry     = new \DateTime($_GET['until'] ?? ('@' . time()));
        $banned     = $this->boolSelect('include_banned', ['only', 'only-not']);
        $wealth     = (int) ($_GET['minWealth'] ?? 0);
        $page       = $this->getPage();
        [$info, $t] = clock(function() use ($expiry, $banned, $wealth, $page) {
            return $this->olap->getRichestCreators($expiry, $banned, $wealth, $page);
        });
    
        $this->addFlash('info', "Query took $t" . 'ms');
        $this->render('Analytics/richest_creators.latte', [
            'info'   => $info,
            'page'   => $page,
            'expiry' => $expiry,
            'banned' => $banned,
            'wealth' => $wealth,
        ]);
    }
}