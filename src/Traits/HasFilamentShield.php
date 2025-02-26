<?php

namespace MaherAlmatari\FilamentShield\Traits;

use Spatie\Permission\Traits\HasRoles;

trait HasFilamentShield
{
    use HasRoles;

    public static function bootHasFilamentShield()
    {
        if (config('filament-shield.filament_user.enabled')) {
            static::created(fn ($user) => $user->assignRole(static::filamentUserRole()));

            static::deleting(fn ($user) => $user->removeRole(static::filamentUserRole()));
        }
    }

    public function canAccessFilament(): bool
    {
        return $this->hasRole(config('filament-shield.super_admin.name')) || $this->hasRole(static::filamentUserRole());
    }

    protected static function filamentUserRole(): string
    {
        return (string) config('filament-shield.filament_user.name');
    }
}
