<?php

namespace App\Repositories;

use App\Entities\Tier;

class TierRepository extends AbstractRepository
{
    public function getByUUID(string $uuid): Tier
    {
        $stmt = $this->db->prepare("SELECT * FROM \"Tiers\" WHERE \"UUID\" = ?");
        $stmt->execute([$uuid]);
    
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object not found");
    
        return $stmt->fetchObject(Tier::class);
    }
    
    public function dropByUUID(string $uuid): bool
    {
        $stmt = $this->db->prepare("DELETE FROM \"Tiers\" WHERE \"UUID\" = ?");
        $stmt->execute([$uuid]);
        
        return $stmt->rowCount() != 0;
    }
    
    public function fetch(
        int $page = 1, ?string $creator = NULL, ?float $price = NULL, ?bool $isFree = NULL, $perPage = 10
    ): array
    {
        $query  = "SELECT * FROM \"Tiers\" WHERE 1=1";
        $params = [];
        if (!is_null($creator)) {
            $params[] = $creator;
            $query   .= " AND \"creator\" = ?";
        }
        
        if (!is_null($isFree)) {
            $query .= " AND \"price\" IS " . ($isFree ? '' : 'NOT ') . "NULL";
        } else if (!is_null($price)) {
            $params[] = $price;
            $query   .= " AND \"price\" > ?";
        }
        
        $offset = ($page - 1) * $perPage;
        $stmt   = $this->db->prepare("$query LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
    
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Tier::class);
    }
    
    public function update(Tier $tier): string
    {
        $stmt = $this->db->prepare(<<<'EOQ'
            UPDATE "Tiers" SET creator = ?, title = ?, description = ?, price = NULLIF(?, 0::numeric::money) WHERE "UUID" = ?
EOQ,);
    
        $stmt->execute([
            $tier->getCreator(),
            $tier->getTitle(),
            $tier->getDescription(),
            $tier->getPrice(),
            $tier->getUuid(),
        ]);
    
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object was not inserted");
        
        return $tier->getUuid();
    }
    
    public function insert(Tier $tier): string
    {
        $stmt = $this->db->prepare(<<<'EOQ'
            INSERT INTO "Tiers" VALUES (uuid_generate_v4(), ?, ?, ?, NULLIF(?, 0::numeric::money)) RETURNING "UUID"
EOQ,);
        
        $stmt->execute([
            $tier->getCreator(),
            $tier->getTitle(),
            $tier->getDescription(),
            $tier->getPrice(),
        ]);
        
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object was not inserted");
        
        return $stmt->fetchColumn();
    }
    
    public function save(Tier $tier): string
    {
        return $this->{!$tier->getUuid() ? 'insert' : 'update'}($tier);
    }
}