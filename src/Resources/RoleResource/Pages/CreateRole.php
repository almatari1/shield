<?php

namespace MaherAlmatari\FilamentShield\Resources\RoleResource\Pages;

use MaherAlmatari\FilamentShield\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Filament\Pages\Actions;
use Illuminate\Support\Facades\Auth;

class CreateRole extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;
    protected static string $resource = RoleResource::class;
    protected function getActions(): array
    {
        return [
            Actions\LocaleSwitcher::make()
        ];
    }
    public Collection $permissions;



    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissions = collect($data)->filter(function ($permission, $key) {
            return ! in_array($key, ['name','guard_name','select_all']) && Str::contains($key, '_');
        })->keys();
        $data['user_type_id'] = Auth::user()->user_type_id;
        $data['user_id'] = Auth::id();
        // dd($data);
        // return $data;

        return Arr::only($data, ['name','guard_name', 'user_id', 'user_type_id']);
    }

    protected function afterCreate(): void
    {
        $permissionModels = collect();
        $this->permissions->each(function ($permission) use ($permissionModels) {
            $permissionModels->push(Permission::firstOrCreate(
                /** @phpstan-ignore-next-line */
                ['name' => $permission],
                ['guard_name' => config('filament.auth.guard')]
            ));
        });

        $this->record->syncPermissions($permissionModels);
    }





}
