<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class UserModel extends UUIDModel
{
    protected $table = 'Users';
    
    public function accessibleTiers(): HasManyThrough
    {
        return $this->hasManyThrough(
            TierModel::class,
            SubscriptionModel::class,
            'sponsor',
            'tier'
        );
    }
}