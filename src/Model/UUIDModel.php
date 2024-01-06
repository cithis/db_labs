<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

abstract class UUIDModel extends AbstractModel
{
    use HasUuids;
    
    protected $primaryKey = 'UUID';
    public $incrementing  = false;
    protected $keyType    = 'string';
}