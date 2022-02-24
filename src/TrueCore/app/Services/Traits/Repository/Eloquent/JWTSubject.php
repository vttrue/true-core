<?php

namespace TrueCore\App\Services\Traits\Repository\Eloquent;

/**
 * Trait JWTSubject
 *
 * @package TrueCore\App\Services\Traits\Repository\Eloquent
 */
trait JWTSubject
{
    /**
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getJWTIdentifier()
    {
        return $this->mapDetail([$this->getAuthIdentifierName()])->{$this->getAuthIdentifierName()};
    }
}
