<?php

namespace App\Services\DataObjects;

use App\Entities\Creator;
use App\Entities\Tier;

class SubscriptionInfo
{
    public int $tx;
    public Tier $tier;
    public Creator $creator;
    public \DateTime $expires;
    
    public function __construct(int $tx, Tier $tier, Creator $creator, \DateTime $expires)
    {
        $this->tx = $tx;
        $this->tier = $tier;
        $this->creator = $creator;
        $this->expires = $expires;
    }
}