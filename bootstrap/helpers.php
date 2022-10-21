<?php

function env(string $key, mixed $default = null): string
{
    return $_ENV[$key] ?? $default;
}