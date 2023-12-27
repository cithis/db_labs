<?php

namespace App\Services\DataObjects;

class FeedPostWithTiers
{
    public FeedPost $post;
    public array $tiers;
    
    public function __construct(FeedPost $post, array $tiers)
    {
        $this->post  = $post;
        $this->tiers = $tiers;
    }
}