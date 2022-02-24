<?php

namespace TrueCore\App\Helpers;

use \Firebase\JWT\JWT;

use Illuminate\Support\Str;

class JWTHelper
{
    /**
     * Необходимые поля для Payloadable
     *
     * iss — (issuer) издатель токена
     * sub — (subject) "тема", назначение токена
     * aud — (audience) аудитория, получатели токена
     * exp — (expire time) срок действия токена
     * nbf — (not before) срок, до которого токен не действителен
     * iat — (issued at) время создания токена
     * jti — (JWT id) идентификатор токена
     */

    const DEFAULT_DAYS_BEFORE_EXPIRED = 30;

    const DEFAULT_PUBLISHER = 'defaultPublisher';
    const DEFAULT_SUBJECT = 'defaultSubject';
    const DEFAULT_AUDIENCE = 'defaultAudience';

    public static function generateJWToken($claims = [])
    {
        $defaultClaims = [
            'iss'  => self::DEFAULT_PUBLISHER,
            'sub'  => self::DEFAULT_SUBJECT,
            'aud'  => self::DEFAULT_AUDIENCE,
            'exp'  => time() + 60 * 60 * 24 * self::DEFAULT_DAYS_BEFORE_EXPIRED,
            'nbf'  => time(),
            'iat'  => time(),
            'jti'  => Str::random(),
            'data' => [],
        ];

        if (count($claims) === 0) {

            $claims = $defaultClaims;
        } else {

            $elementsToUpdate = array_intersect_key($claims, $defaultClaims);

            foreach ($elementsToUpdate as $field => $valueToUpdate) {
                unset($claims[$field]);
                $defaultClaims[$field] = $valueToUpdate;
            }

            $claims = array_merge($defaultClaims, $claims);
        }

        try {

            $token = JWT::encode($claims, config('app.key'));

            if (!$token) {
                throw new \Exception('cant generate token');
            }

        } catch (\Throwable $exception) {

            return $exception->getMessage();
        }

        return $token;
    }
}
