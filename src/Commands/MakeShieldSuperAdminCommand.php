<?php

namespace MaherAlmatari\FilamentShield\Commands;

use MaherAlmatari\FilamentShield\Commands\Concerns\CanValidateInput;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class MakeShieldSuperAdminCommand extends Command
{
    use CanValidateInput;

    public $signature = 'shield:super-admin
        {--user= : ID of user to be made super admin.}';

    public $description = 'Creates Filament Super Admin';

    public function handle(): int
    {
        /** @var SessionGuard $auth */
        $auth = Filament::auth();

        /** @var EloquentUserProvider $userProvider */
        $userProvider = $auth->getProvider();

        if (! Role::whereName(config('filament-shield.super_admin.name'))->exists()) {
            Role::create([
                'name' => config('filament-shield.super_admin.name'),
                'guard_name' => config('filament.auth.guard'),
            ]);
        }

        if (config('filament-shield.filament_user.enabled') && ! Role::whereName(config('filament-shield.filament_user.name'))->exists()) {
            Role::create([
                'name' => config('filament-shield.filament_user.name'),
                'guard_name' => config('filament.auth.guard'),
            ]);
        }

        if ($this->option('user')) {
            $superAdmin = $userProvider->getModel()::findOrFail($this->option('user'));

            $superAdmin->assignRole(config('filament-shield.super_admin.name'));

            if (config('filament-shield.filament_user.enabled')) {
                $superAdmin->assignRole(config('filament-shield.filament_user.name'));
            }
        } elseif ($userProvider->getModel()::count() === 1) {
            $superAdmin = $userProvider->getModel()::first();

            $superAdmin->assignRole(config('filament-shield.super_admin.name'));

            if (config('filament-shield.filament_user.enabled')) {
                $superAdmin->assignRole(config('filament-shield.filament_user.name'));
            }
        } elseif ($userProvider->getModel()::count() > 1) {
            $this->table(
                ['ID','Name','Email','Roles'],
                $userProvider->getModel()::get()->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => implode(',', $user->roles->pluck('name')->toArray()),
                    ];
                })
            );

            $superAdminId = $this->ask('Please provide the `UserID` to be set as `super_admin`');

            $superAdmin = $userProvider->getModel()::find($superAdminId);

            $superAdmin->assignRole(config('filament-shield.super_admin.name'));

            if (config('filament-shield.filament_user.enabled')) {
                $superAdmin->assignRole(config('filament-shield.filament_user.name'));
            }
        } else {
            $superAdmin = $userProvider->getModel()::create([
                'name' => $this->validateInput(fn () => $this->ask('Name'), 'name', ['required']),
                'email' => $this->validateInput(fn () => $this->ask('Email address'), 'email', ['required', 'email', 'unique:' . $userProvider->getModel()]),
                'password' => Hash::make($this->validateInput(fn () => $this->secret('Password'), 'password', ['required', 'min:8'])),
            ]);

            $superAdmin->assignRole(config('filament-shield.super_admin.name'));

            if (config('filament-shield.filament_user.enabled')) {
                $superAdmin->assignRole(config('filament-shield.filament_user.name'));
            }
        }

        $loginUrl = route('filament.auth.login');
        $this->info("Success! {$superAdmin->email} may now log in at {$loginUrl}.");

        return self::SUCCESS;
    }
}
