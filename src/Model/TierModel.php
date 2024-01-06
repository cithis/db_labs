<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class TierModel extends UUIDModel
{
    protected $table = 'Tiers';
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(CreatorModel::class, 'UUID', 'creator');
    }
    
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(
            PostModel::class,
            'PostTierRelation',
            'tier',
            'post'
        );
    }
    
    public function subscribers(): HasManyThrough
    {
        return $this->hasManyThrough(
            UserModel::class,
            SubscriptionModel::class,
            'tier',
            'sponsor'
        );
    }
}