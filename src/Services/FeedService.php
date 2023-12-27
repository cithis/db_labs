<?php

namespace App\Services;

use App\Entities\Creator;
use App\Entities\Post;
use App\Entities\Tier;
use App\Services\DataObjects\FeedPost;
use App\Services\DataObjects\FeedPostWithTiers;

class FeedService
{
    protected \PDO $db;
    
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }
    
    public function getTiersForPost(string $post): array
    {
        $stmt = $this->db->prepare(<<<'EOQ'
            SELECT t.*
            FROM "Posts" p
            INNER JOIN "PostTierRelation" r
               ON r.post=p."UUID"
            INNER JOIN "Tiers" t
               ON t."UUID" = r.tier
            WHERE
                p."UUID" = ?
EOQ);
        $stmt->execute([$post]);
        
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Tier::class);
    }
    
    public function unbindPost(string $post, string $tier): bool
    {
        $stmt = $this->db->prepare("DELETE FROM \"PostTierRelation\" WHERE post = ? AND tier = ?");
        $stmt->execute([$post, $tier]);
    
        return $stmt->rowCount() != 0;
    }
    
    public function bindPost(string $post, string $tier): bool
    {
        $stmt = $this->db->prepare("INSERT INTO \"PostTierRelation\" VALUES (?, ?) ON CONFLICT DO NOTHING");
        $stmt->execute([$post, $tier]);
    
        return $stmt->rowCount() != 0;
    }
    
    public function fetchPostsForUser(string $user, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = <<<EOQ
            SELECT DISTINCT
                p."UUID",
                p.title,
                p.content,
                cr.nickname,
                cr."displayName",
                cr."avatarUrl"
            FROM "Subscriptions" s
            INNER JOIN "PostTierRelation" r
                ON s.tier = r.tier
            INNER JOIN "Posts" p
                ON p."UUID" = r.post
            INNER JOIN "Creators" cr
                ON cr.nickname = p.creator
            WHERE
                sponsor=?
                  AND
                expires > CURRENT_TIMESTAMP
            ORDER BY p."UUID"
            LIMIT $perPage
            OFFSET $offset
EOQ;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user]);
        
        $results = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
            $post    = new Post;
            $creator = new Creator;
            $post->setUuid($row[0]);
            $post->setTitle($row[1]);
            $post->setContent($row[2]);
            $creator->setNickname($row[3]);
            $creator->setDisplayName($row[4]);
            $creator->setAvatarUrl($row[5]);
            
            $results[] = new FeedPost($post, $creator);
        }
        
        return $results;
    }
    
    public function fetchCreatorFeed(Creator $creator, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $query  = <<<EOQ
            WITH posts_sequence AS (
                SELECT
                    "UUID",
                    title,
                    content
                FROM "Posts"
                WHERE creator=?
                LIMIT $perPage
                OFFSET $offset
            )
            SELECT
                p."UUID",
                p.title,
                p.content,
                t."UUID" AS tierId,
                t.title
            FROM posts_sequence p
            INNER JOIN "PostTierRelation" r
               ON r.post=p."UUID"
            INNER JOIN "Tiers" t
               ON t."UUID" = r.tier
EOQ;
    
        $stmt = $this->db->prepare($query);
        $stmt->execute([$creator->getNickname()]);
        
        $posts = [];
        $ids   = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
            $tier = new Tier;
            $tier->setUuid($row[3]);
            $tier->setCreator($creator->getNickname());
            $tier->setTitle($row[4]);
            
            if (array_key_exists($row[0], $posts)) {
                $posts[$row[0]]->tiers[] = $tier;
                continue;
            }
            
            $post = new Post;
            $post->setUuid($row[0]);
            $post->setTitle($row[1]);
            $post->setContent($row[2]);
            $post->setCreator($creator->getNickname());
            
            $fp  = new FeedPost($post, $creator);
            $fpt = new FeedPostWithTiers($fp, [$tier]);
            $posts[$ids[] = $row[0]] = $fpt;
        }
        
        $results = [];
        foreach ($ids as $id)
            $results[] = $posts[$id];
        
        return $results;
    }
}