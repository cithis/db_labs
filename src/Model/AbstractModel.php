<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractModel extends Model
{
    public $timestamps = false;
    
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, "get") || str_starts_with($method, "set") || str_starts_with($method, "is")) {
            $property = lcfirst(substr($method, $method[0] == 'i' ? 0 : 3));
            $property = $property == 'uuid' ? 'UUID' : $property;
            if (array_key_exists($property, $this->attributes)) {
                return ($method[0] == 'g' || $method[0] == 'i')
                    ? $this->{$property}
                    : ($this->{$property} = $parameters[0] ?? NULL);
            }
        }
        
        return parent::__call($method, $parameters);
    }
}