<?php

namespace App\Support\Entities;

use App\Core\Infra\Persistence\ORM;

class BaseEntity extends ORM
{
    protected array $attributes;
    
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __get($name)
    {
        if (!array_key_exists($name, $this->attributes))
            throw new \InvalidArgumentException("Unknown property $name");
        
        return $this->attributes[$name];
    }
}
