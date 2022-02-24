<?php

namespace TrueCore\App\Services\Traits\Repository\Eloquent;

/**
 * Trait Authenticatable
 *
 * @package TrueCore\App\Services\Traits\Repository\Eloquent
 */
trait Authenticatable
{
    /**
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getAuthIdentifier(): int
    {
        return $this->mapDetail([$this->getAuthIdentifierName()])->{$this->getAuthIdentifierName()};
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    public function getAuthPassword(): ?string
    {
        return $this->mapDetail(['password'])->password;
    }

    /**
     * @return string
     */
    public function getRememberTokenName(): string
    {
        return 'rememberToken';
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    public function getRememberToken(): ?string
    {
        return $this->mapDetail([$this->getRememberTokenName()])->{$this->getRememberTokenName()};
    }

    /**
     * @param string $value
     *
     * @throws \TrueCore\App\Services\Traits\Exceptions\ModelSaveException
     */
    public function setRememberToken($value): void
    {
        $this->edit([$this->getRememberTokenName() => $value]);
    }
}
