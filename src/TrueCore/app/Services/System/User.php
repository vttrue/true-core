<?php

namespace TrueCore\App\Services\System;

use Illuminate\Support\Facades\Date;
use TrueCore\App\Mail\AdminResetPassword;
use Firebase\JWT\JWT;
use TrueCore\App\Helpers\JWTHelper;
use TrueCore\App\Extended\Mail;
use TrueCore\App\Services\Traits\Emailable;
use TrueCore\App\Services\Auth\User as Authenticatable;

/**
 * Class User
 *
 * @method UserRepository getRepository()
 *
 * @method static UserStructure[] mapList(array $items, ?array $fields = null)
 * @method UserStructure mapDetail(?array $fields = null)
 *
 * @method static User|null add(array $data)
 *
 * @method static User|null getOne(array $conditions = [])
 * @method static User[] getAll(array $options = [], array $columns = ['*'])
 * @method static User[]|null getRandom(array $conditions = [], array $options = [])
 *
 * @package \TrueCore\App\Services\System
 */
class User extends Authenticatable
{
    use Emailable;

    /**
     * User constructor.
     *
     * @param UserRepository|null $repository
     * @param UserFactory|null $factory
     * @param UserObserver|null $observer
     *
     * @throws \Exception
     */
    public function __construct(UserRepository $repository = null, UserFactory $factory = null, UserObserver $observer = null)
    {
        parent::__construct($repository, $factory, $observer);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function normalizeData(array $data): array
    {
        $normalizedData = parent::normalizeData($data);

        if (array_key_exists('password', $normalizedData) && is_string($normalizedData['password']) && trim($normalizedData['password'] !== '')) {
            $normalizedData['password'] = bcrypt($normalizedData['password']);
        }

        return $normalizedData;
    }

    /**
     * @param array $data
     */
    protected function postProcessor(array $data): void
    {
        //
    }

    /**
     * @return bool
     * @throws \TrueCore\App\Services\Traits\Exceptions\ModelSaveException
     */
    public function setLastVisitAt(): bool
    {
        return $this->edit(['lastVisitAt' => Date::now()->format('Y-m-d H:i:s')]);
    }

    /**
     * @param string $field
     *
     * @return bool
     * @throws \Exception
     */
    public function switch(string $field): bool
    {
        return parent::switch($field);
    }

    /**
     * @throws \Exception
     */
    public function sendPasswordSetEmail()
    {
        $baseData = array_filter($this->getBaseEmailData(), function ($k) {
            return $k === 'site';
        }, ARRAY_FILTER_USE_KEY);

        $data = array_merge([
            'name'             => $this->getRepository()->getModel()->name,
            'email'            => $this->getRepository()->getModel()->email,
            'resetPasswordUrl' => config('app.frontUrl') . '/reset_password/' . JWTHelper::generateJWToken([
                    'data' => [
                        'id' => $this->getRepository()->getModel()->id,
                    ],
                ]),
        ], $baseData);

        Mail::queue(new AdminResetPassword($data, [
            'mailTo' => $this->getRepository()->getModel()->email,
            'nameTo' => $this->getRepository()->getModel()->name,
        ]));
    }

    /**
     * @param $token
     *
     * @return static|null
     * @throws \Exception
     */
    public static function getUserByJWToken(string $token) : ?User
    {
        try {

            $data = JWT::decode($token, config('app.key'), ['HS256']);

            $data = json_decode(json_encode($data), true);

            if (is_array($data) && array_key_exists('data', $data)
                && is_array($data['data'])
                && array_key_exists('id', $data['data'])
                && is_numeric($data['data']['id'])
            ) {
                return static::getOne(['id' => $data['data']['id']]);
            } else {
                throw new \Exception('user id is not defined');
            }

        } catch (\Throwable $e) {

            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @return UserStructure
     */
    protected function getStructureInstance(): UserStructure
    {
        return new UserStructure($this->getRepository());
    }
}
