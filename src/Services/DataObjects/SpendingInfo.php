<?php

namespace App\Services\DataObjects;

use App\Entities\User;

class SpendingInfo
{
    public User $user;
    public int $subscriptions;
    public float $spending;
    
    public function __construct(User $user, int $subscriptions, float $spending)
    {
        $this->user          = $user;
        $this->subscriptions = $subscriptions;
        $this->spending      = $spending;
    }
}