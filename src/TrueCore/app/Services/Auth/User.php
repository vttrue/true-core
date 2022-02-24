<?php

namespace TrueCore\App\Services\Auth;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use TrueCore\App\Services\Service;
use TrueCore\App\Services\Traits\Repository\Eloquent\{
    Authenticatable as AuthenticatableTrait,
    CanResetPassword as CanResetPasswordTrait,
};
use Laravel\Passport\PersonalAccessTokenFactory;

/**
 * Class User
 *
 * @package TrueCore\App\Services\Auth
 */
abstract class User extends Service implements Authenticatable, AuthorizableContract, CanResetPasswordContract
{
    use Authorizable, AuthenticatableTrait, CanResetPasswordTrait;

    protected ?\Laravel\Passport\Token $accessToken;

    /**
     * Get the current access token being used by the user.
     *
     * @return \Laravel\Passport\Token|null
     */
    public function token()
    {
        return $this->accessToken;
    }

    /**
     * Determine if the current API token has a given scope.
     *
     * @param  string  $scope
     * @return bool
     */
    public function tokenCan($scope)
    {
        return $this->accessToken ? $this->accessToken->can($scope) : false;
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $scopes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function createToken($name, array $scopes = [])
    {
        return Container::getInstance()->make(PersonalAccessTokenFactory::class)->make(
            $this->getKey(), $name, $scopes
        );
    }

    /**
     * Set the current access token for the user.
     *
     * @param  \Laravel\Passport\Token  $accessToken
     *
     * @return static
     */
    public function withAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
