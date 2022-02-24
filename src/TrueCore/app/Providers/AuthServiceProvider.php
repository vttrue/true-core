<?php

namespace TrueCore\App\Providers;

use TrueCore\App\Services\System\{
    Role,
    Setting,
    User
};
use TrueCore\App\Policies\System\{
    RolePolicy,
    SettingsPolicy,
    UserPolicy
};
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Role::class               => RolePolicy::class,
        Setting::class            => SettingsPolicy::class,
        User::class               => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();

        Auth::provider('service', function ($app, array $config) {
            return new ServiceAuthProvider($app['hash'], $config['service']);
        });
    }
}
