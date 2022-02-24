<?php

namespace TrueCore\App\Http\Controllers\Admin\System;

use Symfony\Component\HttpFoundation\Response;
use TrueCore\App\Exceptions\Http\Controllers\RequestUpdateException;
use TrueCore\App\Exceptions\Http\Service\DeleteException;
use TrueCore\App\Http\Controllers\Admin\Base\Controller;
use TrueCore\App\Http\Resources\Admin\System\{
    UserForm,
    UserList
};
use TrueCore\App\Http\Requests\Admin\System\{
    StoreUser,
    UpdateUser
};
use TrueCore\App\Services\System\User as UserService;
use TrueCore\App\Services\Traits\Exceptions\{
    ModelSaveException
};
use Illuminate\Http\Request;
use Illuminate\Support\{
    Facades\Validator,
    Str
};

/**
 * Class UserController
 *
 * @package TrueCore\App\Http\Controllers\Admin\System
 */
class UserController extends Controller
{
    protected ?string $listKey = 'userList';
    protected ?string $itemKey = 'user';

    /**
     * UserController constructor.
     *
     * @param UserService $service
     */
    public function __construct(UserService $service)
    {
        parent::__construct($service, UserList::class, UserForm::class, '', StoreUser::class, UpdateUser::class);
    }

    /**
     * @param array $input
     *
     * @return array
     */
    protected function processInput(array $input): array
    {
        $input['user'] = [
            'id' => _getCurrentUser('api')->mapDetail(['id'])->id
        ];

        return $input;
    }

    /**
     * @return array
     */
    protected static function getConditions(): array
    {
        $conditions = parent::getConditions();

        $conditions['isEditable'] = true;

        return $conditions;
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws RequestUpdateException
     */
    public function update(Request $request, $id)
    {
        $service = $this->service::getOne([$this->indexKey => $id]);

        if ($service === null) {
            return response()->json([
                'message' => 'Not found',
            ], 404);
        }

        $canWrite       = $this->hasPermission('write', $service);
        $canWriteOwn    = $this->hasPermission('writeOwn', $service);

        if ($canWrite === false && $canWriteOwn === false) {
            return response()->json([], Response::HTTP_FORBIDDEN);
        }

        try {
            $request = $this->requestUpdateClass::createFrom($request);
        } catch (\Throwable $e) {
            throw new RequestUpdateException('Cannot init update request, wrong class');
        }

        $request->request->add([
            'id' => $id,
        ]);

        $v = Validator::make($request->all(), $request->rules(), $request->messages());

        if ($v->fails()) {
            $this->data['message'] = __('admin/validation.exception.invalid_parameters_message');
            $this->data['errors']  = $v->errors();

            return $this->response(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {

            if ($service->mapDetail()->toArray()['isEditable'] === false || $service->edit($this->processInput($request->all())) === false) {
                throw new ModelSaveException();
            }

            $this->data['id']      = $id;
            $this->data['message'] = __('admin/common/form.update');

            return $this->response();

        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'Unable to save: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function destroy($id)
    {
        try {

            $service = $this->service::getOne([$this->indexKey => $id]);

            if ($service === null) {
//                throw new NotFoundHttpException;
                return response()->json([
                    'message' => 'Not found',
                ], 404);
            }

            $canWrite       = $this->hasPermission('write', $service);
            $canWriteOwn    = $this->hasPermission('writeOwn', $service);

            if ($canWrite === false && $canWriteOwn === false) {
                return response()->json([], 403);
            }

            if ($service->mapDetail()->toArray()['isEditable'] === false || $service->delete(true) === false) {
                throw new DeleteException(__('admin/common/form.destroy.fail'));
            }

            $this->data['message'] = __('admin/common/form.destroy.success');
            $this->data['total']   = $this->service::count();

            return $this->response();

        } catch (DeleteException | \Exception $e) {

            try {
                return response()->json([
                    'message' => __('admin/common/form.destroy.fail'),
                    'total'   => $this->service::count(),
                ], 422);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Server error occurred',
                    'total'   => 0,
                ], 500);
            }
        }
    }

    /**
     * @param string $id
     * @param string $field
     * @return \Illuminate\Http\JsonResponse
     */
    public function switch($id, $field)
    {
        try {

            $service = $this->service::getOne([$this->indexKey => $id]);

            if ($service === null) {
//                throw new NotFoundHttpException;
                return response()->json([
                    'message' => 'Not found',
                ], 404);
            }

            $canWrite       = $this->hasPermission('write', $service);
            $canWriteOwn    = $this->hasPermission('writeOwn', $service);

            if ($canWrite === false && $canWriteOwn === false) {
                return response()->json([], 403);
            }

            if ($service->mapDetail()->toArray()['isEditable'] === true && $service->switch(Str::snake($field))) {
                return response()->json([
                    'success' => true,
                    $field    => (($service->mapDetail([$field])->{$field}) ? true : false),
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    $field  => (($service->mapDetail([$field])->{$field}) ? true : false),
                ], 422);
            }

        } catch (\Throwable $e) {

            return response()->json([
                'error'   => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
