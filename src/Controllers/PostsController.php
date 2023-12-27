<?php

namespace App\Controllers;

use App\Entities\Post;
use App\Repositories\CreatorRepository;
use App\Repositories\PostRepository;
use App\Repositories\TierRepository;
use App\Services\FeedService;

final class PostsController extends AbstractController
{
    protected PostRepository $posts;
    protected CreatorRepository $creators;
    protected FeedService $feed;
    
    function __construct(\PDO $database)
    {
        parent::__construct($database);
        
        $this->posts    = new PostRepository($database);
        $this->creators = new CreatorRepository($database);
        $this->feed     = new FeedService($database);
    }
    
    public function enumerate(): void
    {
        $this->render('Posts/list.latte', [
            'posts' => $this->posts->fetch($page = $this->getPage()),
            'page'  => $page,
        ]);
    }

    public function feed(): void
    {
        $this->render('Posts/list.latte', [
            'posts' => $this->feed->fetchPostsForUser($_GET['user'] ?? 'None', $page = $this->getPage()),
            'page'  => $page,
            'user'  => $_GET['user'] ?? false,
        ]);
    }

    public function creatorFeed(): void
    {
        $creator = $this->catchNotFound(
            fn () => $this->creators->getByNickname($_GET['creator'] ?? 'None'),
            \RuntimeException::class
        );

        $this->render('Posts/list.latte', [
            'posts' => $this->feed->fetchCreatorFeed($creator, $page = $this->getPage()),
            'page'  => $page,
            'creat' => $_GET['creator'] ?? false,
        ]);
    }

    public function view(string $id): void
    {
        $post = $this->catchNotFound(
            fn () => $this->posts->getByUUID($id),
            \RuntimeException::class
        );

        $this->render('Posts/view.latte', [
            'post'  => $post,
            'tiers' => $this->feed->getTiersForPost($id),
        ]);
    }

    public function edit(string $id): void
    {
        if ($_POST['act'] === 'Create') {
            $post = new Post;
        } else {
            $post = $this->catchNotFound(
                fn () => $this->posts->getByUUID($id),
                \RuntimeException::class
            );

            if ($_POST['act'] === 'Delete') {
                $this->posts->dropByUUID($id);
                $this->addFlash('success', 'Post removed');
                $this->redirect('/posts/list');
            }
        }

        $post->setTitle($_POST['title'] ?? $post->getTitle());
        $post->setContent($_POST['content'] ?? $post->getContent());
        $post->setCreator($_POST['creator'] ?? $post->getCreator());

        try {
            $id = $this->posts->save($post);
            $this->addFlash('success', 'Post saved');
        } catch (\PDOException $ex) {
            $this->addFlash('danger', 'PostgreSQL returned error: ' . $ex->getMessage());
            $this->back();
        }

        $this->redirect("/posts/@$id");
    }

    public function create(): void
    {
        $this->render('Posts/create.latte');
    }

    public function bind(string $id): void
    {
        try {
            $this->feed->bindPost($id, $_POST['tier']);
            $this->addFlash('success', 'Tiers saved');
        } catch (\PDOException $ex) {
            $this->addFlash('danger', 'PostgreSQL returned error: ' . $ex->getMessage());
        }

        $this->back();
    }

    public function unbind(string $post, string $tier): void
    {
        try {
            $this->feed->unbindPost($post, $tier);
            $this->addFlash('success', 'Tiers saved');
        } catch (\PDOException $ex) {
            $this->addFlash('danger', 'PostgreSQL returned error: ' . $ex->getMessage());
        }

        $this->back();
    }
}