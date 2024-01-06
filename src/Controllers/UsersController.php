<?php

namespace App\Controllers;

use App\Model\UserModel;
use App\Services\SubscriptionService;
use Illuminate\Contracts\Pagination\Paginator;

final class UsersController extends AbstractController
{
    protected SubscriptionService $subs;
    
    function __construct(\PDO $database)
    {
        parent::__construct($database);
        
        $this->subs = new SubscriptionService($database);
    }
    
    public function enumerate(): void
    {
        $banned = $this->boolSelect('include_banned', ['only', 'only-not']);
        $users  = UserModel::query();
        if (!is_null($banned))
            $users = $users->where('isBanned', $banned);
        
        $this->render('Users/list.latte', [
            'users'  => iterator_to_array($users->paginate(10)),
            'banned' => $banned,
            'page'   => $this->getPage(),
        ]);
    }
    
    public function view(string $id): void
    {
        $user = $this->catchNotFound(
            fn () => UserModel::findOrFail($id),
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
            $user = new UserModel;
        } else {
            $user = $this->catchNotFound(
                fn () => UserModel::findOrFail($id),
                \RuntimeException::class
            );
            
            if ($_POST['act'] === 'Delete') {
                $user->delete();
                $this->addFlash('success', 'User removed');
                $this->redirect('/users/list');
            }
        }
    
        $user->displayName = $_POST['displayName'] ?? $user->displayName;
        $user->avatarUrl   = $_POST['avatarUrl'] ?? $user->avatarUrl;
        $user->isBanned    = ($_POST['isBanned'] ?? 'off') == 'on';
        
        try {
            $user->saveOrFail();
            $id = $user->UUID;
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'PostgreSQL returned error: ' . $e->getMessage());
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