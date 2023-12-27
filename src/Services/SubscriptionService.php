<?php

namespace App\Services;

use App\Entities\Creator;
use App\Entities\Tier;
use App\Services\DataObjects\Subscription;
use App\Services\DataObjects\SubscriptionInfo;

class SubscriptionService
{
    protected \PDO $db;
    
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }
    
    public function getByTxId(int $tx): Subscription
    {
        $stmt = $this->db->prepare("SELECT ?, tier, expires FROM \"Subscriptions\" WHERE \"transaction\" = ?");
        $stmt->execute([$tx, $tx]);
    
        if ($stmt->rowCount() == 0)
            throw new \RuntimeException("Object not found");
    
        return $stmt->fetchObject(Subscription::class);
    }
    
    public function dropByTxId(int $tx): bool
    {
        $stmt = $this->db->prepare("DELETE FROM \"Subscriptions\" WHERE \"transaction\" = ?");
        $stmt->execute([$tx]);
        
        return $stmt->rowCount() != 0;
    }
    
    public function updateExpirationByTxId(int $tx, \DateTime $dt): bool
    {
        $stmt = $this->db->prepare("UPDATE \"Subscriptions\" SET expires = ? WHERE \"transaction\" = ?");
        $stmt->execute([$dt->format('Y-m-d H:i:s'), $tx]);
    
        return $stmt->rowCount() != 0;
    }
    
    public function subscribe(string $user, string $tier, \DateTime $expires, int $tx): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM \"Subscriptions\" WHERE sponsor = ? AND expires > CURRENT_TIMESTAMP");
        $stmt->execute([$user]);
        
        if ($stmt->fetchColumn(0) != 0)
            return false; # Record already exists
        
        $stmt = $this->db->prepare("INSERT INTO \"Subscriptions\" VALUES (?, ?, ?, ?)");
        $stmt->execute([$user, $tier, $expires->format('Y-m-d H:i:s'), $tx]);
        
        return $stmt->rowCount() != 0;
    }
    
    public function getSubscriptions(
        string $uid, ?bool $expired = NULL, int $page = 1, int $perPage = 10
    ): array
    {
        $query = <<<'EOQ'
            SELECT
                t."UUID",
                t.title,
                cr.nickname,
                cr."displayName",
                cr."avatarUrl",
                s.expires,
                s."transaction"
            FROM "Subscriptions" s
            INNER JOIN "Tiers" t
                ON s.tier = t."UUID"
            INNER JOIN "Creators" cr
                ON t.creator = cr.nickname
            WHERE
                sponsor=?
EOQ;
        if (!is_null($expired))
            $query .= " AND expires " . ($expired ? '<' : '>') . " CURRENT_TIMESTAMP";
        
        $offset = ($page - 1) * $perPage;
        $query .= " LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$uid]);
        
        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
            $tier    = new Tier;
            $creator = new Creator;
    
            $tier->setUuid($row[0]);
            $tier->setTitle($row[1]);
            $creator->setNickname($row[2]);
            $creator->setDisplayName($row[3]);
            $creator->setAvatarUrl($row[4]);
    
            $results[] = new SubscriptionInfo($row[6], $tier, $creator, new \DateTime($row[5]));
        }
        
        return $results;
    }
}