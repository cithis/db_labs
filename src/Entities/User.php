<?php

namespace App\Entities;

class User
{
    protected ?string $UUID = NULL;
    protected string $displayName;
    protected string $avatarUrl;
    protected bool $isBanned;
    
    public function getUuid(): ?string
    {
        return $this->UUID;
    }
    
    public function setUuid(?string $uuid): User
    {
        $this->UUID = $uuid;
        return $this;
    }
    
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    
    public function setDisplayName(string $displayName): User
    {
        $this->displayName = $displayName;
        return $this;
    }
    
    public function getAvatarUrl(): string
    {
        return $this->avatarUrl;
    }
    
    public function setAvatarUrl(string $avatarUrl): User
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }
    
    public function isBanned(): bool
    {
        return $this->isBanned;
    }
    
    public function setIsBanned(bool $isBanned): User
    {
        $this->isBanned = $isBanned;
        return $this;
    }
}