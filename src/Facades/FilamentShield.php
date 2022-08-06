<?php

namespace MaherAlmatari\FilamentShield\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MaherAlmatari\FilamentShield\FilamentShield
 */
class FilamentShield extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'filament-shield';
    }
}
