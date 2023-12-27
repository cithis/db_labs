<?php

namespace App;

class DatabaseSeeder
{
    private \PDO $db;
    private float $start;
    
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }
    
    private function clean(): void
    {
        $this->db->query('ALTER SEQUENCE gen_nicknames RESTART WITH 1;');
        $this->db->query('ALTER SEQUENCE gen_transactions RESTART WITH 1;');
        $this->db->query('TRUNCATE "PostTierRelation" CASCADE;');
        $this->db->query('TRUNCATE "Posts" CASCADE;');
        $this->db->query('TRUNCATE "Subscriptions" CASCADE;');
        $this->db->query('TRUNCATE "Tiers" CASCADE;');
        $this->db->query('TRUNCATE "Users" CASCADE;');
        $this->db->query('TRUNCATE "Creators" CASCADE;');
        
        echo (microtime(true) - $this->start), " CLEAN OK\r\n";
    }
    
    private function createUsers(int $count): void
    {
        $query = <<<EOQ
            DO \$do$ BEGIN
            FOR i IN 1..$count LOOP
                INSERT INTO "Users"
                SELECT
                    UID,
                    name_generate(),
                    ('/ava/' || UID::varchar || '.jpeg'),
                    (RANDOM() >= 0.5)::boolean
                FROM gen_random_uuid() sub(UID);
            END LOOP;
            END \$do$;
EOQ;
        $this->db->query($query);
    
        echo (microtime(true) - $this->start), " USERS OK\r\n";
    }
    
    private function createCreators(int $count): void
    {
        $query = <<<EOQ
            DO \$do$ BEGIN
            FOR i IN 1..$count LOOP
                INSERT INTO "Creators"
                SELECT
                    ('GEN' || UID),
                    name_generate(),
                    ('/ava/' || UID || '.jpeg'),
                    (RANDOM() >= 0.5)::boolean
                FROM NEXTVAL('gen_nicknames') sub(UID);
            END LOOP;
            END \$do$;
EOQ;
        $this->db->query($query);
    
        echo (microtime(true) - $this->start), " CREATORS OK\r\n";
    }
    
    private function createTiers(int $count): void
    {
        $query = <<<EOQ
            INSERT INTO "Tiers"
            WITH creator_sequence AS (
                SELECT "nickname"
                FROM "Creators"
                CROSS JOIN GENERATE_SERIES(1, 55)
                ORDER BY RANDOM()
                LIMIT $count
            ) SELECT
                gen_random_uuid(),
                nickname,
                string_generate(10::smallint, 64::SMALLINT),
                string_generate(128::smallint, 512::smallint)::text,
                NULLIF(FLOOR(RANDOM() * 20)::numeric::money, 0::money)
            FROM generate_series(1, 1) FULL OUTER JOIN creator_sequence ON 1=1
EOQ;
        $this->db->query($query);
    
        echo (microtime(true) - $this->start), " TIERS OK\r\n";
    }
    
    private function createSubs(int $maxCount, float $staleFactor): void
    {
        $fresh = $maxCount * (1.0 - $staleFactor);
        $stale = $maxCount - $fresh;
        $freshQuery = <<<EOQ
            INSERT INTO "Subscriptions"
            WITH user_sequence AS (
                SELECT "UUID" AS SUID
                FROM "Users"
                CROSS JOIN GENERATE_SERIES(1, 55)
                ORDER BY RANDOM()
                LIMIT $fresh
            ), subs_sequence AS (
                SELECT
                    SUID AS suid,
                    (
                        SELECT "UUID" FROM "Tiers" WHERE NOT EXISTS(
                            SELECT * FROM "Subscriptions"
                                WHERE "expires" > CURRENT_TIMESTAMP
                                    AND "sponsor" = SUID
                        ) AND "UUID" IS NOT NULL ORDER BY RANDOM() LIMIT 1
                   ) AS tuid
                FROM GENERATE_SERIES(1, 1)
                FULL OUTER JOIN user_sequence ON 1=1
            ) SELECT
                suid,
                tuid,
                CURRENT_TIMESTAMP + (CEIL(RANDOM() * 30) || ' day')::INTERVAL,
               NEXTVAL('gen_transactions')
            FROM GENERATE_SERIES(1, 1)
            FULL OUTER JOIN subs_sequence ON 1=1
            WHERE tuid IS NOT NULL
EOQ;
        $staleQuery = <<<EOQ
            INSERT INTO "Subscriptions"
            WITH user_sequence AS (
                SELECT "UUID" AS SUID FROM "Users" ORDER BY RANDOM() LIMIT $stale
            )
            SELECT
                SUID,
                (SELECT "UUID" FROM "Tiers" ORDER BY RANDOM() LIMIT 1),
                CURRENT_TIMESTAMP - (CEIL(RANDOM() * 30) || ' month')::INTERVAL,
                nextval('gen_transactions')
            FROM user_sequence
EOQ;
    
        $inserted  = $this->db->query($freshQuery)->rowCount();
        $inserted += $this->db->query($staleQuery)->rowCount();
    
        echo (microtime(true) - $this->start), " SUBS OK: miss=", ($maxCount - $inserted), "\r\n";
    }
    
    private function createPosts(int $count): void
    {
        $query = <<<EOQ
            INSERT INTO "Posts"
            WITH creators_sequence AS (
                SELECT nickname
                FROM "Creators"
                CROSS JOIN GENERATE_SERIES(1, 55)
                ORDER BY RANDOM()
                LIMIT $count
            )
            SELECT
                gen_random_uuid(),
                nickname,
                string_generate(10::smallint, 64::smallint),
                string_generate(256::smallint, 4096::smallint)::text
            FROM GENERATE_SERIES(1, 1)
            FULL OUTER JOIN creators_sequence ON 1=1;
EOQ;
        $this->db->query($query);
    
        echo (microtime(true) - $this->start), " POSTS OK\r\n";
    }
    
    private function createPostRels(int $maxCount): void
    {
        $query = <<<EOQ
            INSERT INTO "PostTierRelation"
            WITH post_sequence AS (
                SELECT "UUID" AS PUID, creator AS CUID
                FROM "Posts"
                CROSS JOIN GENERATE_SERIES(1, 3)
                ORDER BY RANDOM()
                LIMIT $maxCount
            ), rels_sequence AS (
                SELECT PUID, TUID
                FROM post_sequence p
                INNER JOIN (
                    SELECT "UUID" AS TUID, creator AS CUID
                    FROM "Tiers"
                    ORDER BY RANDOM()
                ) t ON p.CUID=t.CUID
                LIMIT $maxCount
            )
            SELECT * FROM rels_sequence
            ON CONFLICT DO NOTHING
EOQ;
    
        $rows = $this->db->query($query)->rowCount();
    
        echo (microtime(true) - $this->start), " RELS OK: miss=", ($maxCount - $rows), "\r\n";
    }
    
    public function seed(int $users = 100_000): void
    {
        echo "Seeding for $users users (c=0.25, t=0.5, s=1 [sf=90%], p=1, r=0.5)\r\n";
        
        $this->start = microtime(true);
        $this->clean();
        $this->createUsers($users);
        $this->createCreators($users / 4);
        $this->createTiers($users / 2);
        $this->createSubs($users, 0.9);
        $this->createPosts($users);
        $this->createPostRels($users / 2);
    }
}