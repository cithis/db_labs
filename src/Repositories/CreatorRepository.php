<?php

namespace App\Repositories;

use App\Model\CreatorModel;

class CreatorRepository extends AbstractRepository
{
    public function getByNickname(string $nickname): CreatorModel
    {
        return CreatorModel::findOrFail($nickname);
    }
    
    public function dropByNickname(string $nickname): bool
    {
        return CreatorModel::destroy($nickname) != 0;
    }
    
    public function fetch(int $page = 1, ?bool $isBanned = NULL, $perPage = 10): array
    {
        $creators = CreatorModel::query();
        if (!is_null($isBanned))
            $creators = $creators->where('isBanned', $isBanned);
    
        $offset   = ($page - 1) * $perPage;
        $creators = $creators->offset($offset)->limit($perPage)->get();
        
        return iterator_to_array($creators);
    }
    
    public function save(CreatorModel $user): string
    {
        $user->saveOrFail();
        
        return $user->nickname;
    }
}