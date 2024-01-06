<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CreatorModel extends AbstractModel
{
    protected $table      = 'Creators';
    protected $primaryKey = 'nickname';
    public $incrementing  = false;
    protected $keyType    = 'string';
    
    public function tiers(): HasMany
    {
        return $this->hasMany(TierModel::class, 'creator');
    }
    
    public function posts(): HasMany
    {
        return $this->hasMany(PostModel::class, 'creator', 'nickname');
    }
}