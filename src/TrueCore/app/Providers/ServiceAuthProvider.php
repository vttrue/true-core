<?php

namespace TrueCore\App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Support\Str;

/**
 * Class ServiceAuthProvider
 *
 * @package TrueCore\App\Providers
 */
class ServiceAuthProvider implements UserProvider
{
    /**
     * The hasher implementation.
     *
     * @var HasherContract
     */
    protected $hasher;

    /**
     * The Eloquent user model.
     *
     * @var string
     */
    protected $service;

    /**
     * Create a new database user provider.
     *
     * @param  HasherContract  $hasher
     * @param  string  $service
     * @return void
     */
    public function __construct(HasherContract $hasher, $service)
    {
        $this->service = $service;
        $this->hasher = $hasher;
    }

    /**
     * Get the first key from the credential array.
     *
     * @param  array  $credentials
     * @return string|null
     */
    protected function firstCredentialKey(array $credentials)
    {
        foreach ($credentials as $key => $value) {
            return $key;
        }
    }

    /**
     * @param array $credentials
     *
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (count($credentials) === 0 ||
            (count($credentials) === 1 &&
                Str::contains($this->firstCredentialKey($credentials), 'password'))) {
            return null;
        }

        $credentials = array_filter($credentials, function ($k) {
            return Str::contains($k, 'password') === false;
        }, ARRAY_FILTER_USE_KEY);

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
//        $query = $this->newModelQuery();

//        foreach ($credentials as $key => $value) {
//            if (Str::contains($key, 'password')) {
//                continue;
//            }
//
//            if (is_array($value) || $value instanceof Arrayable) {
//                $query->whereIn($key, $value);
//            } else {
//                $query->where($key, $value);
//            }
//        }

//        return $query->first();

        return $this->service::getOne($credentials);
    }

    /**
     * @param Authenticatable $user
     * @param string $token
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);

//        $timestamps = $user->timestamps;
//
//        $user->timestamps = false;
//
//        $user->save();
//
//        $user->timestamps = $timestamps;
    }

    /**
     * @param Authenticatable $user
     * @param array $credentials
     *
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];

        return $this->hasher->check($plain, $user->getAuthPassword());
    }

    /**
     * @param mixed $identifier
     * @param string $token
     *
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        $userService = new $this->service;

        $userService = $userService::getOne([$userService->getAuthIdentifierName() => $identifier]);

        $rememberToken = $userService->getRememberToken();

        return ((is_string($rememberToken) && hash_equals($rememberToken, $token))
            ? $userService : null);

//        return (($userRepo !== null) ? new User($userRepo) : null);
//
//
//        $model = $this->createModel();
//
//        $retrievedModel = $this->newModelQuery($model)->where(
//            $model->getAuthIdentifierName(), $identifier
//        )->first();
//
//        if (! $retrievedModel) {
//            return;
//        }
//
//        $rememberToken = $retrievedModel->getRememberToken();
//
//        return $rememberToken && hash_equals($rememberToken, $token)
//            ? $retrievedModel : null;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        $userService = new $this->service;

        return $userService::getOne([$userService->getAuthIdentifierName() => $identifier]);

//        return $class = '\\'.ltrim($this->model, '\\');
//
//        return new $class;

//        $model = $this->createModel();
//
//        return $this->newModelQuery($model)
//            ->where($model->getAuthIdentifierName(), $identifier)
//            ->first();

    }
}
