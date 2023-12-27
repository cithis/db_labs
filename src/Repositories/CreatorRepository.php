<?php

namespace App\Repositories;

use App\Entities\Creator;

class CreatorRepository extends AbstractRepository
{
    public function getByNickname(string $nickname): Creator
    {
        $stmt = $this->db->prepare("SELECT * FROM \"Creators\" WHERE nickname = ?");
        $stmt->execute([$nickname]);
        
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object not found");
        
        return $stmt->fetchObject(Creator::class);
    }
    
    public function dropByNickname(string $nickname): bool
    {
        $stmt = $this->db->prepare("DELETE FROM \"Creators\" WHERE \"nickname\" = ?");
        $stmt->execute([$nickname]);
        
        return $stmt->rowCount() != 0;
    }
    
    public function fetch(int $page = 1, ?bool $isBanned = NULL, $perPage = 10): array
    {
        $params = [];
        $query  = "SELECT * FROM \"Creators\"";
        if (!is_null($isBanned)) {
            $query   .= " WHERE \"isBanned\" = ?::boolean";
            $params[] = bool2str($isBanned);
        }
    
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->db->prepare("$query LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Creator::class);
    }
    
    public function save(Creator $user): string
    {
        $stmt = $this->db->prepare(<<<'EOQ'
            INSERT INTO "Creators" VALUES (?, ?, ?, ?::boolean) ON CONFLICT (nickname) DO UPDATE
                SET "displayName" = ?, "avatarUrl" = ?, "isBanned" = ?::boolean
            RETURNING nickname
EOQ,    );
        $stmt->execute([
            $user->getNickname(),
            $user->getDisplayName(),
            $user->getAvatarUrl(),
            bool2str($user->isBanned()),
            $user->getDisplayName(),
            $user->getAvatarUrl(),
            bool2str($user->isBanned()),
        ]);
        
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object was not inserted");
        
        return $stmt->fetchColumn();
    }
}