<?php

namespace App\Controllers;

use App\Entities\User;
use App\Repositories\UserRepository;
use App\Services\SubscriptionService;

final class UsersController extends AbstractController
{
    protected UserRepository $users;
    protected SubscriptionService $subs;
    
    function __construct(\PDO $database)
    {
        parent::__construct($database);
        
        $this->users = new UserRepository($database);
        $this->subs  = new SubscriptionService($database);
    }
    
    public function enumerate(): void
    {
        $banned = $this->boolSelect('include_banned', ['only', 'only-not']);
    
        $this->render('Users/list.latte', [
            'users'  => $this->users->fetch($page = $this->getPage(), $banned),
            'banned' => $banned,
            'page'   => $page,
        ]);
    }
    
    public function view(string $id): void
    {
        $user = $this->catchNotFound(
            fn () => $this->users->getByUUID($id),
            \RuntimeException::class
        );
        
        $expr = $this->boolSelect('include_expired', ['only', 'only-not'], false);
        $subs = $this->subs->getSubscriptions($id, $expr, $page = $this->getPage());
    
        $this->render('Users/view.latte', [
            'user' => $user,
            'subs' => $subs,
            'expr' => $expr,
            'page' => $page,
        ]);
    }
    
    public function edit(string $id): void
    {
        if ($_POST['act'] === 'Create') {
            $user = new User;
        } else {
            $user = $this->catchNotFound(
                fn () => $this->users->getByUUID($id),
                \RuntimeException::class
            );
    
            if ($_POST['act'] === 'Delete') {
                $this->users->dropByUUID($id);
                $this->addFlash('success', 'User removed');
                $this->redirect('/users/list');
            }
        }
        
        $user->setDisplayName($_POST['displayName'] ?? $user->getDisplayName());
        $user->setAvatarUrl($_POST['avatarUrl'] ?? $user->getDisplayName());
        $user->setIsBanned(($_POST['isBanned'] ?? 'off') === 'on');
        
        try {
            $id = $this->users->save($user);
            $this->addFlash('success', 'User saved');
        } catch (\PDOException $ex) {
            $this->addFlash('danger', 'PostgreSQL returned error: ' . $ex->getMessage());
            $this->back();
        }
        
        $this->redirect("/users/@$id");
    }
    
    public function create(): void
    {
        $this->render('Users/create.latte');
    }
    
    public function sub(string $uid): void
    {
        try {
            $res = $this->subs->subscribe(
                $uid,
                $_POST['tier'],
                new \DateTime($_POST['expires']),
                200_000 + random_int(0, 100_000)
            );
            
            if ($res)
                $this->addFlash('success', 'Subscribed successfully');
            else
                $this->addFlash('danger', 'Subscription was not saved: Generic error');
        } catch (\PDOException $ex) {
            $this->addFlash('danger', 'Subscription was not saved: ' . $ex->getMessage());
        }
        
        $this->back();
    }
    
    public function editSub(string $tx): void
    {
        try {
            if ($_POST['act'] === 'Create') {
                $res = $this->subs->subscribe(
                    $_POST['sponsor'],
                    $_POST['tier'],
                    new \DateTime($_POST['expires']),
                    (int) $_POST['tx'],
                );
            } else if ($_POST['act'] === 'Delete') {
                $res = $this->subs->dropByTxId((int) $tx);
            } else {
                $res = $this->subs->updateExpirationByTxId($tx, new \DateTime($_POST['expires']));
            }
    
            if ($res)
                $this->addFlash('success', 'Subscribed successfully');
            else
                $this->addFlash('danger', 'Subscription was not saved: Generic error');
        } catch (\PDOException $ex) {
            $this->addFlash('danger', 'Subscription was not saved: ' . $ex->getMessage());
        }
    
        $this->back();
    }
}