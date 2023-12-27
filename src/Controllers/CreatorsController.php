<?php

namespace App\Controllers;

use App\Entities\Creator;
use App\Repositories\CreatorRepository;
use App\Repositories\TierRepository;

final class CreatorsController extends AbstractController
{
    protected CreatorRepository $creators;
    protected TierRepository $tiers;
    
    function __construct(\PDO $database)
    {
        parent::__construct($database);
    
        $this->creators = new CreatorRepository($database);
        $this->tiers    = new TierRepository($database);
    }
    
    public function enumerate(): void
    {
        $banned = $this->boolSelect('include_banned', ['only', 'only-not']);
        
        $this->render('Creators/list.latte', [
            'creators' => $this->creators->fetch($page = $this->getPage(), $banned),
            'banned'   => $banned,
            'page'     => $page,
        ]);
    }
    
    public function view(string $id): void
    {
        $creator = $this->catchNotFound(
            fn () => $this->creators->getByNickname($id),
            \RuntimeException::class
        );
        
        $this->render('Creators/view.latte', [
            'creator' => $creator,
            'tiers'   => $this->tiers->fetch($page = $this->getPage(), $id),
            'page'    => $page,
        ]);
    }
    
    public function edit(string $id): void
    {
        if ($_POST['act'] === 'Create') {
            if (empty($_POST['nickname'])) {
                $this->addFlash('danger', 'Where nickname bro');
                $this->back();
            }
            
            $creator = new Creator;
        } else {
            $creator = $this->catchNotFound(
                fn () => $this->creators->getByNickname($id),
                \RuntimeException::class
            );
            
            if ($_POST['act'] === 'Delete') {
                $this->creators->dropByNickname($id);
                $this->addFlash('success', 'Creator removed');
                $this->redirect('/creators/list');
            }
        }
    
        $creator->setNickname($_POST['nickname'] ?? $creator->getNickname());
        $creator->setDisplayName($_POST['displayName'] ?? $creator->getDisplayName());
        $creator->setAvatarUrl($_POST['avatarUrl'] ?? $creator->getDisplayName());
        $creator->setIsBanned(($_POST['isBanned'] ?? 'off') === 'on');
        
        try {
            $id = $this->creators->save($creator);
            $this->addFlash('success', 'Creator saved');
        } catch (\PDOException $ex) {
            $this->addFlash('danger', 'PostgreSQL returned error: ' . $ex->getMessage());
            $this->back();
        }
        
        $this->redirect("/creators/@$id");
    }
    
    public function create(): void
    {
        $this->render('Creators/create.latte');
    }
}