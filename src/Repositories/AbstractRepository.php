<?php

namespace App\Repositories;

abstract class AbstractRepository
{
    protected \PDO $db;
    
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }
}