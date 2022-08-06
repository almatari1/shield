<?php

namespace MaherAlmatari\FilamentShield\Resources\RoleResource\Pages;

use MaherAlmatari\FilamentShield\Resources\RoleResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    use ListRecords\Concerns\Translatable;

    protected static string $resource = RoleResource::class;

    protected function getActions(): array
    {
        return [
            Actions\LocaleSwitcher::make(),
            Actions\CreateAction::make(),
        ];
    }
}
