<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionModel extends AbstractModel
{
    protected $table      = 'Subscriptions';
    protected $primaryKey = 'transaction';
    public $incrementing  = false;
    
    public function tier(): BelongsTo
    {
        return $this->belongsTo(TierModel::class, 'UUID', 'tier');
    }
    
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'UUID', 'sponsor');
    }
}