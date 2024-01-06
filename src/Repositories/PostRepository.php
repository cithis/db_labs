<?php

namespace App\Repositories;

use App\Model\PostModel;

class PostRepository extends AbstractRepository
{
    public function getByUUID(string $uuid): PostModel
    {
        return PostModel::findOrFail($uuid);
    }
    
    public function dropByUUID(string $uuid): bool
    {
        return PostModel::destroy($uuid) != 0;
    }
    
    public function fetch(int $page = 1, $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        
        return iterator_to_array(PostModel::offset($offset)->limit($perPage)->get());
    }
    
    public function save(PostModel $post): string
    {
        $post->saveOrFail();
        
        return $post->UUID;
    }
}