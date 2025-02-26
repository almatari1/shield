<?php

namespace MaherAlmatari\FilamentShield;

use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FilamentShield
{
    public static function generateForResource(string $resource): void
    {
        if (config('filament-shield.entities.resources')) {
            $permissions = collect();
            collect(config('filament-shield.permission_prefixes.resource'))
                ->each(function ($prefix) use ($resource, $permissions) {
                    $permissions->push(Permission::firstOrCreate(
                        ['name' => $prefix . '_' . $resource],
                        ['guard_name' => config('filament.auth.guard')]
                    ));
                });

            static::giveSuperAdminPermission($permissions);
            static::giveFilamentUserPermission($permissions);
        }
    }

    public static function generateForPage(string $page): void
    {
        if (config('filament-shield.entities.pages')) {
            $permission = Permission::firstOrCreate(
                ['name' => $page ],
                ['guard_name' => config('filament.auth.guard')]
            )->name;

            static::giveSuperAdminPermission($permission);
            static::giveFilamentUserPermission($permission);
        }
    }

    public static function generateForWidget(string $widget): void
    {
        if (config('filament-shield.entities.widgets')) {
            $permission = Permission::firstOrCreate(
                ['name' => $widget ],
                ['guard_name' => config('filament.auth.guard')]
            )->name;

            static::giveSuperAdminPermission($permission);
            static::giveFilamentUserPermission($permission);
        }
    }

    protected static function giveSuperAdminPermission(string|array|Collection $permissions): void
    {
        if (config('filament-shield.super_admin.enabled')) {
            $superAdmin = Role::firstOrCreate(
                ['name' => config('filament-shield.super_admin.name')],
                ['guard_name' => config('filament.auth.guard') ?? 'web']
            );

            $superAdmin->givePermissionTo($permissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    protected static function giveFilamentUserPermission(string|array|Collection $permissions): void
    {
        if (config('filament-shield.filament_user.enabled')) {
            $filamentUser = Role::firstOrCreate(
                ['name' => config('filament-shield.filament_user.name')],
                ['guard_name' => config('filament.auth.guard')]
            );

            $filamentUser->givePermissionTo($permissions);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    /**
     * Transform filament resources to key value pair for shield
     *
     * @return array
     */
    public static function getResources(): ?array
    {
        return collect(Filament::getResources())
            ->unique()
            ->filter(function ($resource) {
                if (config('filament-shield.exclude.enabled')) {
                    return ! in_array(
                        Str::of($resource)->afterLast('\\'),
                        config('filament-shield.exclude.resources')
                    );
                }

                return true;
            })
            ->reduce(function ($resources, $resource) {
                $name = Str::of($resource)->afterLast('Resources\\')->before('Resource')->replace('\\', '')->headline()->snake()->replace('_', '::');
                $resources["{$name}"] = [
                    'resource' => "{$name}",
                    'model' => Str::of($resource::getModel())->afterLast('\\'),
                    'fqcn' => $resource,
                ];

                return $resources;
            }, collect())
            ->sortKeys()
            ->toArray();
    }

    /**
     * Get the localized resource label
     *
     * @param string $entity
     * @return String
     */
    public static function getLocalizedResourceLabel(string $entity): string
    {
        $label = collect(Filament::getResources())->filter(function ($resource) use ($entity) {
            return $resource === $entity;
        })->first()::getModelLabel();

        return Str::of($label)->headline();
    }

    /**
     * Get the localized resource permission label
     *
     * @param string $permission
     * @return string
     */
    public static function getLocalizedResourcePermissionLabel(string $permission): string
    {
        return Lang::has("filament-shield::filament-shield.resource_permission_prefixes_labels.$permission", app()->getLocale())
            ? __("filament-shield::filament-shield.resource_permission_prefixes_labels.$permission")
            : Str::of($permission)->headline();
    }

    /**
    * Transform filament pages to key value pair for shield
    *
    * @return array
    */
    public static function getPages(): ?array
    {
        return collect(Filament::getPages())
            ->filter(function ($page) {
                if (config('filament-shield.exclude.enabled')) {
                    return ! in_array(Str::afterLast($page, '\\'), config('filament-shield.exclude.pages'));
                }

                return true;
            })
            ->reduce(function ($pages, $page) {
                $prepend = Str::of(config('filament-shield.permission_prefixes.page'))->append('_');
                $name = Str::of(class_basename($page))
                    ->prepend($prepend);

                $pages["{$name}"] = "{$name}";

                return $pages;
            }, collect())
            ->toArray();
    }

    /**
     * Get localized page label
     *
     * @param string $page
     * @return string|bool
     */
    public static function getLocalizedPageLabel(string $page): string|bool
    {
        $object = static::transformClassString($page);

        if (Str::of($pageTitle = invade(new $object())->getTitle())->isNotEmpty()) {
            return $pageTitle;
        }

        return invade(new $object())->getNavigationLabel();
    }

    /**
    * Transform filament widgets to key value pair for shield
    *
    * @return array
    */
    public static function getWidgets(): ?array
    {
        return collect(Filament::getWidgets())
            ->filter(function ($widget) {
                if (config('filament-shield.exclude.enabled')) {
                    return ! in_array(Str::afterLast($widget, '\\'), config('filament-shield.exclude.widgets'));
                }

                return true;
            })
            ->reduce(function ($widgets, $widget) {
                $prepend = Str::of(config('filament-shield.permission_prefixes.widget'))->append('_');
                $name = Str::of(class_basename($widget))
                    ->prepend($prepend);

                $widgets["{$name}"] = "{$name}";

                return $widgets;
            }, collect())
            ->toArray();
    }

    /**
     * Get localized widget label
     *
     * @param string $page
     * @return string|bool
     */
    public static function getLocalizedWidgetLabel(string $widget): string
    {
        $class = static::transformClassString($widget, false);
        $parent = get_parent_class($class);
        $grandpa = get_parent_class($parent);

        $heading = Str::of($widget)
            ->after(config('filament-shield.permission_prefixes.widget').'_')
            ->headline();

        if ($grandpa === "Filament\Widgets\ChartWidget") {
            return (string) invade(new $class())->getHeading() ?? $heading;
        }

        return match ($parent) {
            "Filament\Widgets\TableWidget" => (string) invade(new $class())->getTableHeading(),
            "Filament\Widgets\StatsOverviewWidget" => (string) static::hasHeadingForShield($class)
                ? (new $class())->getHeadingForShield()
                : $heading,
            default => $heading
        };
    }

    protected static function transformClassString(string $string, bool $isPageClass = true): string
    {
        return (string) collect($isPageClass ? Filament::getPages() : Filament::getWidgets())
            ->first(fn ($item) =>
                Str::endsWith(
                    $item,
                    Str::of($string)
                    ->after('_')
                    ->studly()
                ));
    }

    protected static function hasHeadingForShield(object|string $class): bool
    {
        return method_exists($class, 'getHeadingForShield');
    }

    /**
     * Shield structured data.
     *
     * @return array
     */
    public static function getShieldData(): array
    {
        return collect(static::getResources())
            ->map(function ($entity) {
                return collect(config('filament-shield.permission_prefixes.resource'))
                    ->reduce(
                        function ($option, $permission) use ($entity) {
                            $option[$permission . '_' . $entity] = ['label' => $permission,'value' => false];

                            return $option;
                        },
                        [
                            'label' => static::getLocalizedResourceLabel($entity),
                            'value' => false,
                        ]
                    );
            })
            ->sortKeys()
            ->all();
    }
}
