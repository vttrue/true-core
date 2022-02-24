<?php

namespace TrueCore\App\Extended\Passport\Bridge;

use Laravel\Passport\Bridge\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Laravel\Passport\Bridge\UserRepository as BaseUserRepository;
use RuntimeException;

/**
 * Class UserRepository
 *
 * @package App\Passport\Bridge
 */
class UserRepository extends BaseUserRepository
{
    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $provider = $clientEntity->provider ?: config('auth.guards.api.provider');

        if (is_null($service = config('auth.providers.'.$provider.'.service'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }

        if (method_exists($service, 'findAndValidateForPassport')) {
            $user = (new $service)->findAndValidateForPassport($username, $password);

            if (! $user) {
                return;
            }

            return new User($user->getAuthIdentifier());
        }

        if (method_exists($service, 'findForPassport')) {
            $user = (new $service)->findForPassport($username);
        } else {
            $user = (new $service)::getOne([
                'email' => $username
            ]);
        }

        if (! $user) {
            return;
        } elseif (method_exists($user, 'validateForPassportPasswordGrant')) {
            if (! $user->validateForPassportPasswordGrant($password)) {
                return;
            }
        } elseif (! $this->hasher->check($password, $user->getAuthPassword())) {
            return;
        }

        return new User($user->getAuthIdentifier());
    }
}
