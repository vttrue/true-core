<?php

namespace TrueCore\App\Services\System;

use \TrueCore\App\Services\Structure;

/**
 * Class UserStructure
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $email
 * @property bool $isEditable
 * @property string|null $password
 * @property string|null $rememberToken
 * @property string|null $lastVisitAt
 * @property string|null $createdAt
 * @property string|null $updatedAt
 * @property bool $status
 * @property array $role
 * @property array|null $owner
 *
 * @package TrueCore\App\Services\System
 */
class UserStructure extends Structure
{
    /**
     * UserStructure constructor.
     *
     * @param UserRepository $repository
     */
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
}
