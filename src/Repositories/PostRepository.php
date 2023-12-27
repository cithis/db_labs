<?php

namespace App\Repositories;

use App\Entities\Post;

class PostRepository extends AbstractRepository
{
    public function getByUUID(string $uuid): Post
    {
        $stmt = $this->db->prepare("SELECT * FROM \"Posts\" WHERE \"UUID\" = ?");
        $stmt->execute([$uuid]);
        
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object not found");
        
        return $stmt->fetchObject(Post::class);
    }
    
    public function dropByUUID(string $uuid): bool
    {
        $stmt = $this->db->prepare("DELETE FROM \"Posts\" WHERE \"UUID\" = ?");
        $stmt->execute([$uuid]);
        
        return $stmt->rowCount() != 0;
    }
    
    public function fetch(int $page = 1, $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->db->prepare("SELECT * FROM \"Posts\" LIMIT $perPage OFFSET $offset");
        $stmt->execute();
    
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Post::class);
    }
    
    public function update(Post $post): string
    {
        $stmt = $this->db->prepare(<<<'EOQ'
            UPDATE "Posts" SET creator = ?, title = ?, content = ? WHERE "UUID" = ?
EOQ,    );
        $stmt->execute([
            $post->getCreator(),
            $post->getTitle(),
            $post->getContent(),
            $post->getUuid(),
        ]);
    
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object was not inserted");

        return $post->getUuid();
    }
    
    public function insert(Post $post): string
    {
        $stmt = $this->db->prepare(<<<'EOQ'
            INSERT INTO "Posts" VALUES (uuid_generate_v4(), ?, ?, ?) RETURNING "UUID"
EOQ,    );
        $stmt->execute([
            $post->getCreator(),
            $post->getTitle(),
            $post->getContent(),
        ]);
        
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object was not inserted");

        return $stmt->fetchColumn();
    }
    
    public function save(Post $post): string
    {
        return $this->{!$post->getUuid() ? 'insert' : 'update'}($post);
    }
}