<?php

namespace App\Services;

use App\Entities\Post;
use App\Entities\User;
use App\Services\DataObjects\RevenueInfo;
use App\Services\DataObjects\SpendingInfo;

class OlapService
{
    protected \PDO $db;
    
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }
    
    public function getOctopusPosts(
        string $creatorBlackListPrefix, int $minLength, int $page = 1, int $perPage = 10
    ): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = <<<EOQ
            SELECT
                p.*, COUNT(*) AS cnt
            FROM "Posts" p
            INNER JOIN "PostTierRelation" r
                ON r.post = p."UUID"
            WHERE p.creator
                NOT LIKE ?
            GROUP BY p."UUID"
            HAVING
                LENGTH(p.content) > ?
            ORDER BY cnt DESC
            LIMIT $perPage
            OFFSET $offset
EOQ;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(["$creatorBlackListPrefix%", $minLength]);
        
        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
            $post = (new Post)
                ->setUuid($row[0])
                ->setCreator($row[1])
                ->setTitle($row[2])
                ->setContent($row[3]);
            $results[] = [$post, $row[4]];
        }
        
        return $results;
    }
    
    public function getRichestUsers(\DateTime $expiry, int $minSpending, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = <<<EOQ
            WITH sponsors_sequence AS (
                SELECT
                    s.sponsor,
                    COUNT(s.sponsor) AS subs,
                    SUM(t.price) AS spending
                FROM "Subscriptions" s
                INNER JOIN "Tiers" t
                    ON t."UUID" = s.tier
                WHERE
                    s.expires > ?
                GROUP BY s.sponsor
                HAVING
                    SUM(t.price) IS NOT NULL
                      AND SUM(t.price) >= ?
                ORDER BY spending DESC
                LIMIT $perPage
                OFFSET $offset
            ) SELECT
                u.*,
                subs,
                spending
            FROM sponsors_sequence x
            INNER JOIN "Users" u
                ON u."UUID" = x.sponsor
            ORDER BY spending DESC
EOQ;
    
        $stmt = $this->db->prepare($query);
        $stmt->execute([$expiry->format('Y-m-d H:i:s'), $minSpending]);
    
        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
            $user = (new User)
                ->setUuid($row[0])
                ->setDisplayName($row[1])
                ->setAvatarUrl($row[2])
                ->setIsBanned($row[3]);
            
            $results[] = new SpendingInfo($user, $row[4], (float) substr($row[5], 1));
        }
        
        return $results;
    }
    
    public function getRichestCreators(
        \DateTime $until, ?bool $banned = NULL, int $minWealth = 0, int $page = 1, int $perPage = 10
    ): array
    {
        $banClause = "1=1";
        if (!is_null($banned))
            $banClause = ($banned ? '' : 'NOT') . ' cr."isBanned"';
    
        $offset = ($page - 1) * $perPage;
        $query  = <<<EOQ
            SELECT
                cr.nickname AS "creatorNickname",
                COUNT(s.sponsor) AS subs,
                SUM(t.price) AS revenue
            FROM "Creators" cr
            INNER JOIN "Tiers" t
                ON t.creator = cr.nickname
                  AND $banClause
            INNER JOIN "Subscriptions" s
                ON s.tier = t."UUID"
            WHERE
                s.expires > ?
            GROUP BY cr.nickname
            HAVING
                SUM(t.price) > ?
            ORDER BY revenue DESC
            LIMIT $perPage
            OFFSET $offset
EOQ;
    
        $stmt = $this->db->prepare($query);
        $stmt->execute([$until->format('Y-m-d H:i:s'), $minWealth]);
        
        return $stmt->fetchAll(\PDO::FETCH_CLASS, RevenueInfo::class);
    }
}