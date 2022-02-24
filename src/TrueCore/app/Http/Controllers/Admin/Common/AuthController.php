<?php

namespace TrueCore\App\Http\Controllers\Admin\Common;

use App\Http\Controllers\Admin\Exceptions\AuthFailedException;
use App\Libraries\OAuth;
use Illuminate\Http\JsonResponse;
use TrueCore\App\Http\Controllers\Controller;
use TrueCore\App\Http\Requests\Admin\Common\{
    Login,
    ResetPassword
};
use TrueCore\App\Http\Resources\Admin\System\UserForm;
use TrueCore\App\Services\System\User as UserService;
use Illuminate\Http\Request;

/**
 * Class AuthController
 *
 * @package TrueCore\App\Http\Controllers\Admin\Common
 */
class AuthController extends Controller
{
    /**
     * @param Login $request
     *
     * @return JsonResponse|UserForm
     * @throws \Exception
     */
    public function login(Login $request)
    {
        try {

            $credentials = $request->only(['email', 'password']);

            $user = UserService::getOne(['email' => $credentials['email']]);

            if ( $user === null ) {
                return response()->json([
                    'message' => __('validation.exception.message'),
                    'errors'  => ['email' => [__('auth.failed')]],
                ], 422);
            }

            if ( $user->mapDetail(['status'])->status === false ) {
                return response()->json([
                    'message' => __('validation.exception.message'),
                    'errors'  => ['email' => [__('auth.not_active')]],
                ], 422);
            }

            try {
                $content = OAuth::issueToken($credentials, 'password');
            } catch (AuthFailedException $e) {
                return response()->json([
                    'message' => __('validation.exception.message'),
                    'errors'  => ['email' => [$e->getMessage()]],
                ], 422);
            }

            $user->setLastVisitAt();

            return (new UserForm($user->mapDetail()))->additional([
                'meta' => [
                    'type'         => $content['token_type'],
                    'token'        => $content['access_token'],
                    'refreshToken' => $content['refresh_token'],
                ],
            ]);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid data' . $e->getMessage()], 422);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            request()->user()->token()->revoke();
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Invalid request'
            ], 422);
        }

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * @param ResetPassword $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function forgotten(ResetPassword $request)
    {
        try {

            $userService = UserService::getOne(['email' => $request->input('email')]);

            if ($userService === null) {
                return response()->json(['message' => __('user.not_found')], 404);
            }

            $userService->sendPasswordSetEmail();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true], 422);
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        /** @var $userService UserService|null */
        $userService = _getCurrentUser('api');

        if ($userService !== null) {

            $userData = $userService->mapDetail(['id', 'role', 'name','phone', 'email', 'status']);

            $authorizationHeader = $request->header('Authorization');
            $token = str_replace('Bearer ', '', $authorizationHeader);

            $this->data['data']         = (new UserForm($userData))->toArray($request) + ['accessToken' => $token];
            $this->data['permissions']  = array_values(array_filter($userData->role['permissions'], fn($v) => (array_key_exists('status',$v) && $v['status'] === true)));

            $this->data['meta'] = [
                'token' => $token,
            ];

            return response()->json($this->data)->header('Authorization', $authorizationHeader);
        }

        return response()->json('Unauthenticated', 401);
    }
}
