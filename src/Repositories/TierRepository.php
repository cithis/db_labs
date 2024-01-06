<?php

namespace App\Repositories;

use App\Model\TierModel;

class TierRepository extends AbstractRepository
{
    public function getByUUID(string $uuid): TierModel
    {
        return TierModel::findOrFail($uuid);
    }
    
    public function dropByUUID(string $uuid): bool
    {
        return TierModel::destroy($uuid) != 0;
    }
    
    public function fetch(
        int $page = 1, ?string $creator = NULL, ?float $price = NULL, ?bool $isFree = NULL, $perPage = 10
    ): array
    {
        $tiers = TierModel::query();
        if (!is_null($creator))
            $tiers = $tiers->where('creator', $creator);
        if (!is_null($isFree))
            $tiers = $tiers->{$isFree ? 'whereNull' : 'whereNotNull'}('price');
        else if (!is_null($price))
            $tiers = $tiers->where('price', '>', $price);
    
        $offset = ($page - 1) * $perPage;
        $tiers  = $tiers->offset($offset)->limit($perPage);
        
        return iterator_to_array($tiers->get());
    }
    
    public function save(TierModel $tier): string
    {
        $tier->save();
        
        return $tier->UUID;
    }
}