<?php

namespace TrueCore\App\Services\Traits\Repository\Eloquent;

/**
 * Trait CanResetPassword
 *
 * @package TrueCore\App\Services\Traits\Repository\Eloquent
 */
trait CanResetPassword
{
    /**
     * @TODO: realize. Incarnator | 2020-06-17
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        return;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getEmailForPasswordReset()
    {
        return $this->mapDetail(['email'])->email;
    }
}
