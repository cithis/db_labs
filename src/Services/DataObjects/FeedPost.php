<?php

namespace App\Services\DataObjects;

use App\Entities\Creator;
use App\Entities\Post;

class FeedPost
{
    public Post $post;
    public Creator $creator;
    
    public function __construct(Post $post, Creator $creator)
    {
        $this->post = $post;
        $this->creator = $creator;
    }
}