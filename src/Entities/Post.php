<?php

namespace App\Entities;

class Post
{
    protected ?string $UUID = NULL;
    protected string $creator;
    protected string $title;
    protected string $content;
    
    public function getUuid(): ?string
    {
        return $this->UUID;
    }
    
    public function setUuid(?string $uuid): Post
    {
        $this->UUID = $uuid;
        return $this;
    }
    
    public function getCreator(): string
    {
        return $this->creator;
    }
    
    public function setCreator(string $creator): Post
    {
        $this->creator = $creator;
        return $this;
    }
    
    public function getTitle(): string
    {
        return $this->title;
    }
    
    public function setTitle(string $title): Post
    {
        $this->title = $title;
        return $this;
    }
    
    public function getContent(): string
    {
        return $this->content;
    }
    
    public function setContent(string $content): Post
    {
        $this->content = $content;
        return $this;
    }
}