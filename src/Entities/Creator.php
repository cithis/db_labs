<?php

namespace App\Entities;

class Creator
{
    protected ?string $nickname;
    protected string $displayName;
    protected string $avatarUrl;
    protected bool $isBanned;
    
    public function getNickname(): ?string
    {
        return $this->nickname;
    }
    
    public function setNickname(?string $nickname): Creator
    {
        $this->nickname = $nickname;
        return $this;
    }
    
    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    
    public function setDisplayName(string $displayName): Creator
    {
        $this->displayName = $displayName;
        return $this;
    }
    
    public function getAvatarUrl(): string
    {
        return $this->avatarUrl;
    }
    
    public function setAvatarUrl(string $avatarUrl): Creator
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }
    
    public function isBanned(): bool
    {
        return $this->isBanned;
    }
    
    public function setIsBanned(bool $isBanned): Creator
    {
        $this->isBanned = $isBanned;
        return $this;
    }
}