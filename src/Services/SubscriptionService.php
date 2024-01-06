<?php

namespace App\Services;

use App\Entities\Creator;
use App\Entities\Tier;
use App\Model\SubscriptionModel;
use App\Services\DataObjects\Subscription;
use App\Services\DataObjects\SubscriptionInfo;

class SubscriptionService
{
    protected \PDO $db;
    
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }
    
    public function getByTxId(int $tx): SubscriptionModel
    {
        return SubscriptionModel::findOrFail($tx);
    }
    
    public function dropByTxId(int $tx): bool
    {
        return SubscriptionModel::destroy($tx) != 0;
    }
    
    public function updateExpirationByTxId(int $tx, \DateTime $dt): bool
    {
        $sub = $this->getByTxId($tx);
        $sub->expires = $dt;
        
        return $sub->saveQuietly();
    }
    
    public function subscribe(string $user, string $tier, \DateTime $expires, int $tx): bool
    {
        $sub = SubscriptionModel::where('sponsor', '=', $user)
            ->where('tier', '=', $tier)
            ->where('expires', '>=', date('Y-m-d H:i:s', time()))->first();
        
        if (!is_null($sub))
            return false;
        
        $sub = new SubscriptionModel;
        $sub->transaction = $tx;
        $sub->sponsor     = $user;
        $sub->tier        = $tier;
        $sub->expires     = $expires->format('Y-m-d H:i:s');
        
        return $sub->saveQuietly();
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