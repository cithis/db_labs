<?php

namespace App\Services\DataObjects;

class Subscription
{
    public int $transaction;
    public string $tier;
    public \DateTime $expires;
    
    public function __construct(int $tx, string $tier, \DateTime $expires)
    {
        $this->transaction = $tx;
        $this->tier        = $tier;
        $this->expires     = $expires;
    }
}