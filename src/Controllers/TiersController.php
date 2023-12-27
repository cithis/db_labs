<?php

namespace App\Controllers;

use App\Entities\Tier;
use App\Repositories\TierRepository;

final class TiersController extends AbstractController
{
    protected TierRepository $tiers;
    
    function __construct(\PDO $database)
    {
        parent::__construct($database);
        
        $this->tiers = new TierRepository($database);
    }
    
    function enumerate(): void
    {
        $free  = $this->boolSelect('include_free', ['only', 'only-not']);
        $price = NULL;
        if (!empty($_GET['price'])) {
            if ($free) {
                $this->addFlash('warning', 'Both price and free specified, ignoring price.');
            } else {
                $free  = NULL;
                $price = (float) $_GET['price'];
            }
        }
        
        $this->render('Tiers/list.latte', [
            'tiers' => $this->tiers->fetch($page = $this->getPage(), NULL, $price, $free),
            'page'  => $page,
            'free'  => $free,
            'price' => $price,
        ]);
    }
    
    function view(string $id): void
    {
        $this->render('Tiers/view.latte', [
            'tier' => $this->catchNotFound(
                fn () => $this->tiers->getByUUID($id),
                \RuntimeException::class
            ),
        ]);
    }
    
    function edit(string $id): void
    {
        if ($_POST['act'] === 'Create') {
            $tier = new Tier;
        } else {
            $tier = $this->catchNotFound(
                fn () => $this->tiers->getByUUID($id),
                \RuntimeException::class
            );
        
            if ($_POST['act'] === 'Delete') {
                $this->tiers->dropByUUID($id);
                $this->addFlash('success', 'Tier removed');
                $this->redirect('/tiers/list');
            }
        }
    
        $tier->setTitle($_POST['title'] ?? $tier->getTitle());
        $tier->setDescription($_POST['description'] ?? $tier->getDescription());
        $tier->setCreator($_POST['creator'] ?? $tier->getCreator());
        if (($_POST['free'] ?? 'off') === 'on')
            $tier->setPrice(NULL);
        else
            $tier->setPrice($_POST['price'] ?? $tier->getPrice());
    
        try {
            $id = $this->tiers->save($tier);
            $this->addFlash('success', 'Tier saved');
        } catch (\PDOException|\RuntimeException $ex) {
            $this->addFlash('danger', 'PostgreSQL returned error: ' . $ex->getMessage());
            $this->back();
        }
    
        $this->redirect("/tiers/@$id");
    }
    
    public function create(): void
    {
        $this->render('Tiers/create.latte', [
            'creator' => $_GET['creator'] ?? NULL,
        ]);
    }
}