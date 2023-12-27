<?php

namespace App\Entities;

class Tier
{
    protected ?string $UUID       = NULL;
    protected string $creator     = '';
    protected string $title       = '';
    protected string $description = '';
    protected ?string $price      = NULL;
    
    public function getUuid(): ?string
    {
        return $this->UUID;
    }
    
    public function setUuid(?string $uuid): Tier
    {
        $this->UUID = $uuid;
        return $this;
    }
    
    public function getCreator(): string
    {
        return $this->creator;
    }
    
    public function setCreator(string $creator): Tier
    {
        $this->creator = $creator;
        return $this;
    }
    
    public function getTitle(): string
    {
        return $this->title;
    }
    
    public function setTitle(string $title): Tier
    {
        $this->title = $title;
        return $this;
    }
    
    public function getDescription(): string
    {
        return $this->description;
    }
    
    public function setDescription(string $description): Tier
    {
        $this->description = $description;
        return $this;
    }
    
    public function getPrice(): ?string
    {
        return $this->price;
    }
    
    public function setPrice(?string $price): Tier
    {
        $this->price = $price;
        return $this;
    }
}