<?php

namespace App\Support\Traits;

trait ValidateEntityProperties
{

    public function validateProperties(array $data): array
    {
        return array_filter($data, function ($property) {
            return in_array($property, $this->properties);
        }, ARRAY_FILTER_USE_KEY);
    }
}
