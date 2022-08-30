<?php

namespace Symnext\Toolkit;

class Choice
{
    private $values_allowed;

    private $default_value;

    private $value;

    public function __construct(array $values_allowed, string $default_value)
    {
        $this->values_allowed = $values_allowed;
        $this->default_value = $default_value;
    }

    public function get()
    {
        return $this->value ?? $this->default_value;
    }

    public function set($value)
    {
        if (in_array($value, $this->values_allowed)) {
            $this->value = $value;
        }
    }
}
