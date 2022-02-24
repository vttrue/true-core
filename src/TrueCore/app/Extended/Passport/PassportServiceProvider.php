<?php

namespace TrueCore\App\Extended\Passport;

use Laravel\Passport\Bridge\RefreshTokenRepository;
use TrueCore\App\Extended\Passport\Bridge\UserRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\Grant\PasswordGrant;
use Laravel\Passport\PassportServiceProvider as BasePassportServiceProvider;

class PassportServiceProvider extends BasePassportServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Create and configure a Password grant instance.
     *
     * @return \League\OAuth2\Server\Grant\PasswordGrant
     */
    protected function makePasswordGrant()
    {
        $grant = new PasswordGrant(
            $this->app->make(UserRepository::class),
            $this->app->make(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }
}
