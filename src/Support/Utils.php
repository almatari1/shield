<?php

namespace MaherAlmatari\FilamentShield\Support;

class Utils
{
    public static function getResourceSlug(): string
    {
        return (string) config('filament-shield.shield_resource.slug');
    }

    public static function getResourceNavigationSort(): int
    {
        return config('filament-shield.shield_resource.navigation_sort');
    }

    public static function isResourceNavigationBadgeEnabled(): bool
    {
        return config('filament-shield.shield_resource.navigation_badge', true);
    }

    public static function getAuthProviderFQCN()
    {
        return config('filament-shield.auth_provider_model.fqcn');
    }

    public static function isAuthProviderConfigured(): bool
    {
        return in_array("MaherAlmatari\FilamentShield\Traits\HasFilamentShield", class_uses(static::getAuthProviderFQCN()))
        || in_array("Spatie\Permission\Traits\HasRoles", class_uses(static::getAuthProviderFQCN())) ;
    }
}
