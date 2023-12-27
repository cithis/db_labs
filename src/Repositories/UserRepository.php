<?php

namespace App\Repositories;

use App\Entities\User;

class UserRepository extends AbstractRepository
{
    public function getByUUID(string $uuid): User
    {
        $stmt = $this->db->prepare("SELECT * FROM \"Users\" WHERE \"UUID\" = ?");
        $stmt->execute([$uuid]);
        
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object not found");
        
        return $stmt->fetchObject(User::class);
    }
    
    public function dropByUUID(string $uuid): bool
    {
        $stmt = $this->db->prepare("DELETE FROM \"Users\" WHERE \"UUID\" = ?");
        $stmt->execute([$uuid]);
    
        return $stmt->rowCount() != 0;
    }
    
    public function fetch(int $page = 1, ?bool $isBanned = NULL, $perPage = 10): array
    {
        $params = [];
        $query  = "SELECT * FROM \"Users\"";
        if (!is_null($isBanned)) {
            $query   .= ' WHERE "isBanned" = ?::boolean';
            $params[] = bool2str($isBanned);
        }
        
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->db->prepare("$query LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_CLASS, User::class);
    }
    
    public function update(User $user): string
    {
        $stmt = $this->db->prepare(<<<'EOQ'
            UPDATE "Users" SET "displayName" = ?, "avatarUrl" = ?, "isBanned" = ?::boolean WHERE "UUID" = ?
EOQ,    );
        $stmt->execute([
            $user->getDisplayName(),
            $user->getAvatarUrl(),
            bool2str($user->isBanned()),
            $user->getUuid(),
        ]);
    
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object was not inserted");
        
        return $user->getUuid();
    }
    
    public function insert(User $user): string
    {
        $stmt = $this->db->prepare(<<<'EOQ'
            INSERT INTO "Users" VALUES (uuid_generate_v4(), ?, ?, ?::boolean) RETURNING "UUID"
EOQ,    );
        $stmt->execute([
            $user->getDisplayName(),
            $user->getAvatarUrl(),
            (string) $user->isBanned(),
        ]);
        
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object was not inserted");
        
        return $stmt->fetchColumn();
    }
    
    public function save(User $user): string
    {
        return $this->{!$user->getUuid() ? 'insert' : 'update'}($user);
    }
}