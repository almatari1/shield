<?php

namespace MaherAlmatari\FilamentShield;

use MaherAlmatari\FilamentShield\Resources\RoleResource;
use Filament\PluginServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

class FilamentShieldServiceProvider extends PluginServiceProvider
{
    protected array $resources = [
        RoleResource::class,
    ];

    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-shield')
            ->hasConfigFile()
            ->hasMigrations(['add_user_type_id'])
            ->hasTranslations()
            ->hasCommands($this->getCommands())
        ;
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // $this->publishes([
        //     __DIR__ . '/../database/migrations/add_user_type_id.php.stub' => $this->getMigrationFileName('add_user_type_id.php'),
        // ], 'migrations');

        if (config('filament-shield.register_role_policy.enabled')) {
            Gate::policy('Spatie\Permission\Models\Role', 'App\Policies\RolePolicy');
        }
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app->scoped('filament-shield', function (): FilamentShield {
            return new FilamentShield();
        });


        // $this->publishes([
        //     $this->package->basePath("/../stubs/ShieldSettingSeeder.stub") => database_path('seeders/ShieldSettingSeeder.php'),
        // ], "{$this->package->shortName()}-seeder");
    }

    protected function getCommands(): array
    {
        return [
            Commands\MakeShieldDoctorCommand::class,
            Commands\MakeShieldUpgradeCommand::class,
            Commands\MakeShieldInstallCommand::class,
            Commands\MakeShieldGenerateCommand::class,
            Commands\MakeShieldSuperAdminCommand::class,
        ];
    }


    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @return string
     */
    protected function getMigrationFileName($migrationFileName): string
    {
        $timestamp = date('Y_m_d_His');

        $filesystem = $this->app->make(Filesystem::class);

        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $migrationFileName) {
                return $filesystem->glob($path . '*_' . $migrationFileName);
            })
            ->push($this->app->databasePath() . "/migrations/{$timestamp}_{$migrationFileName}")
            ->first();
    }
}
