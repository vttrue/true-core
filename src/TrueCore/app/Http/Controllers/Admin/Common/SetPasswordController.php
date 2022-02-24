<?php

namespace TrueCore\App\Http\Controllers\Admin\Common;

use TrueCore\App\Http\Controllers\Controller;
use JWTAuth;
use TrueCore\App\Http\Requests\Admin\Common\SetPassword;
use TrueCore\App\Services\System\User as UserService;

/**
 * Class SetPasswordController
 *
 * @package TrueCore\App\Http\Controllers\Admin\Common
 */
class SetPasswordController extends Controller
{
    protected UserService $userService;

    /**
     * SetPasswordController constructor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param $token
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws \Exception
     */
    public function index($token)
    {
        $code = 200;

        if (trim($token) !== '' && $user = $this->userService::getUserByJWToken($token)) {

            $userStructure = $user->mapDetail(['email', 'name']);

            $this->data['email']    = $userStructure->email;
            $this->data['name']     = $userStructure->name;
            $this->data['token']    = $token;
        } else {

            $code = 403;

            $this->data = [
                'errors'    => [
                    'info'  => [
                        'Invalid token'
                    ]
                ]
            ];

        }

        return $this->response($code);
    }

    /**
     * @TODO: implement standalone Exception with error logging | Deprecator @ 2020-06-24
     *
     * @param SetPassword $request
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws \TrueCore\App\Services\Traits\Exceptions\ModelSaveException
     */
    public function save(SetPassword $request)
    {
        try {

            $token = $request->header('token', '');
            $token = ((is_string($token) && trim($token) !== '') ? $token : null);

            if ($token === null) {
                throw new \Exception('Invalid token', 403);
            }

            $user = $this->userService::getUserByJWToken($token);

            if ($user === null) {
                throw new \Exception('Invalid Token', 403);
            }

            $isSaved = $user->edit([
                'password'  => $request->input('password'),
                'status'    => true
            ]);

            if ($isSaved === false) {
                throw new \Exception('Unable to set new password', 422);
            }

            $this->data = [
                'success' => true
            ];

            return $this->response();

        } catch (\Throwable $e) {

            $this->data = [
                'errors' => [
                    'info' => [
                        $e->getMessage()
                    ]
                ]
            ];

            return $this->response(((in_array((int)$e->getCode(), [403, 422])) ? (int)$e->getCode() : 422));
        }
    }
}
