<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PostModel extends UUIDModel
{
    protected $table = 'Posts';
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(CreatorModel::class, 'UUID', 'creator');
    }
    
    public function tiers(): BelongsToMany
    {
        return $this->belongsToMany(
            TierModel::class,
            'PostTierRelation',
            'post',
            'tier'
        );
    }
}