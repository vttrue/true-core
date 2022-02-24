<?php

namespace TrueCore\App\Http\Controllers\Admin\Base;

use TrueCore\App\Exceptions\Http\Service\SwitchException;
use TrueCore\App\Helpers\CommonHelper;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\{
    Auth\Access\AuthorizesRequests,
    Bus\DispatchesJobs,
    Http\FormRequest,
    Validation\ValidatesRequests
};
use Symfony\Component\HttpFoundation\Response;
use TrueCore\App\Libraries\Config;
use TrueCore\App\Services\{
    Interfaces\Service as ServiceInterface,
    Service,
    Traits\Exceptions\ModelSaveException
};
use TrueCore\App\Traits\Error;
use Illuminate\Support\Facades\{
    Auth,
    Validator
};
use Illuminate\Support\Str;
use TrueCore\App\Exceptions\Http\Controllers\{
    MissedUpdateMethodException,
    RequestBatchException,
    RequestStoreException,
    RequestUpdateException
};
use TrueCore\App\Exceptions\Http\Service\DeleteException;
use Illuminate\Http\{
    Resources\Json\JsonResource,
    Request
};
use TrueCore\App\Http\Controllers\Admin\Traits\{
    Search,
    Sort
};

/**
 * Class Controller
 *
 * @property Service|null $service
 * @property JsonResource|string $resourceListClass
 * @property JsonResource|string $resourceFormClass
 * @property FormRequest|string $requestBatchClass
 * @property FormRequest|string $requestStoreClass
 * @property FormRequest|string $requestUpdateClass
 * @property array $data
 * @property array $relations
 * @property string|null $listLey
 * @property string|null $itemLey
 * @property string $indexKey
 *
 * @package TrueCore\App\Http\Controllers\Admin
 */
abstract class Controller extends BaseController
{
    use Search, Sort, AuthorizesRequests, DispatchesJobs, Error, ValidatesRequests;

    protected ?Service $service            = null;
    protected string   $resourceListClass  = '';
    protected string   $resourceFormClass  = '';
    protected string   $requestBatchClass  = '';
    protected string   $requestStoreClass  = '';
    protected string   $requestUpdateClass = '';
    protected array    $data               = [];
    protected array    $relations          = [];
    protected ?string  $listKey            = null;
    protected ?string  $itemKey            = null;
    protected string   $indexKey           = 'id';
    protected ?array   $defaultSortParam   = null;

    /**
     * Controller constructor.
     *
     * @param Service $service
     * @param string $resourceListClass
     * @param string $resourceFormClass
     * @param string $requestBatchClass
     * @param string $requestStoreClass
     * @param string $requestUpdateClass
     */
    public function __construct(Service $service, string $resourceListClass, string $resourceFormClass, string $requestBatchClass, string $requestStoreClass, string $requestUpdateClass)
    {
        $this->service            = $service;
        $this->resourceListClass  = $resourceListClass;
        $this->resourceFormClass  = $resourceFormClass;
        $this->requestBatchClass  = $requestBatchClass;
        $this->requestStoreClass  = $requestStoreClass;
        $this->requestUpdateClass = $requestUpdateClass;
    }

    /**
     * @param array $input
     *
     * @return array
     */
    protected abstract function processInput(array $input): array;

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function index()
    {
        $canRead    = $this->hasPermission('read');
        $canReadOwn = $this->hasPermission('readOwn');

        if ($canRead === false && $canReadOwn === false) {
            return response()->json([], Response::HTTP_FORBIDDEN);
        }

        $sortParams   = [];
        $searchParams = [];

        if ($this->hasSortRequest()) {
            $sortParams = $this->getSortRequest();
        }

        if ($this->hasSearchRequest()) {
            $searchParams = $this->getSearchRequest();
        }

        $payLoad = $sortParams + $searchParams;

        $page = request()->input('page', '1');

        if (is_string($page) && $page !== '' && (int)$page > 0) {
            $payLoad['page'] = $page;
        }

        $limit  = request()->input('limit', 10);
        $limit  = ((is_numeric($limit) && (int)$limit >= 0) ? (int)$limit : Config::getInstance()->get('limitPagination', 'admin', 15));
        $offset = request()->input('offset', 0);
        $offset = ((is_numeric($offset) && (int)$offset >= 0) ? (int)$offset : 0);

        $conditions = static::getConditions();

        if ($canRead === false && $canReadOwn === true) {
            $conditions = [
                [
                    'AND' => [
                        array_merge_recursive($conditions, [
                            [
                                'owner.id'  => _getCurrentUser('api')->mapDetail(['id'])->id
                            ]
                        ])
                    ]
                ]
            ];
        }

        $result = $this->service::getAllDynamicPaginator(
            [
                'sort'       => $sortParams,
                'search'     => ((count($searchParams) === 2)
                    ? [
                        Str::snake($searchParams['search']) => [
                            'param'    => $searchParams['text'],
                            'splitBy'  => null,
                            'wildCard' => '*',
                        ],
                    ]
                    : []
                ),
                'conditions' => $conditions,
                'page'       => $page,
                'relations'  => $this->relations,
            ],
            $offset,
            $limit
        );

        /** @TODO: временно, убедиться в обратной совместимости возвращаемого фронтенду response. Incarnator | 2020-06-17 */
        $model = $this->service->getRepository()->getModel();

        $key = ((is_string($this->listKey) && $this->listKey !== '')
            ? $this->listKey
            : Str::plural(lcfirst(CommonHelper::getShortClassName(get_class($model)))));

        $this->data[$key] = (new $this->resourceListClass($this->service::mapList($result->items())));

        $this->data['meta'] = [
            'total'   => $result->total(),
            'offset'  => $offset,
            'limit'   => $result->perPage(),
            'payload' => $payLoad,
        ];

        return response()->json($this->data);
    }

    /**
     * @return array
     */
    protected static function getConditions(): array
    {
        $conditions = [];

        $idList = request()->input('id', []);
        $idList = ((is_array($idList)) ? array_filter($idList, function ($v) {
            return is_numeric($v);
        }) : ((is_numeric($idList)) ? (array)$idList : null));

        if (is_array($idList) && count($idList) > 0) {
            $conditions['id'] = array_values($idList);
        }

        return $conditions;
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws RequestStoreException
     */
    public function store(Request $request)
    {
        $canWrite       = $this->hasPermission('write');
        $canWriteOwn    = $this->hasPermission('writeOwn');

        if ($canWrite === false && $canWriteOwn === false) {
            return response()->json([], Response::HTTP_FORBIDDEN);
        }

        try {
            $request = $this->requestStoreClass::createFrom($request);
        } catch (\Throwable $e) {
            throw new RequestStoreException('Cannot init store request, wrong class');
        }

        $params = $request->all();
        $v      = Validator::make($params, $request->rules(), $request->messages());

        if ($v->fails()) {
            $this->data = [
                'message' => __('validation.exception.invalid_parameters_message'),
                'errors'  => $v->errors(),
            ];

            return $this->response(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {

            $service = $this->service::add($this->processInput($params));

            if ($service === null) {
                throw new ModelSaveException();
            }

            $this->data = [
                'message' => __('admin/common/form.store'),
                'id'      => $service->mapDetail(['id'])->id,
            ];

            return $this->response();

        } catch (ModelSaveException $e) {

            return response()->json([
                'message' => 'Unable to save: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws MissedUpdateMethodException
     * @throws \Exception
     */
    public function show($id)
    {
        $service = $this->service::getOne([$this->indexKey => $id]);

        if ($service === null) {
            return response()->json([
                'message' => 'Not found',
            ], 404);
        }

        $canRead       = $this->hasPermission('read', $service);
        $canReadOwn    = $this->hasPermission('readOwn', $service);

        if ($canRead === false && $canReadOwn === false) {
            return response()->json([], Response::HTTP_FORBIDDEN);
        }

        if (method_exists(static::class, 'update') === false) {
            throw new MissedUpdateMethodException('Controller update method is not defined');
        }

        /** @TODO: временно, убедиться в обратной совместимости возвращаемого фронтенду response. Incarnator | 2020-06-17 */
        $model = $this->service->getRepository()->getModel();

        $key = (($this->itemKey !== null)
            ? $this->itemKey
            : lcfirst(CommonHelper::getShortClassName(get_class($model))));

        $this->data[$key] = new $this->resourceFormClass($service->mapDetail());

//        $this->data['action'] = action(
//            str_replace('App\Http\Controllers\\', '', static::class) . '@update',
//            [$id]
//        );

        return $this->response();
    }

    /**
     * @param Request $request
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws RequestUpdateException
     * @throws \Exception
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

            if ($service->edit($this->processInput($request->all())) === false) {
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
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function saveSort(Request $request)
    {
        try {

            $canWrite       = $this->hasPermission('write');
            $canWriteOwn    = $this->hasPermission('writeOwn');

            if ($canWrite === false && $canWriteOwn === false) {
                return response()->json([], Response::HTTP_FORBIDDEN);
            }

            $itemIds = $request->post('itemIds', []);

            if (!is_array($itemIds)) {
                throw new \InvalidArgumentException('invalid id list');
            }

            $this->service->sortItems($itemIds);

            $this->data['success'] = true;

            return $this->response();

        } catch (\Throwable $e) {
            $this->data['error'] = true;

            return $this->response(Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @param $id
     * @param $field
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws SwitchException
     * @throws \Exception
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

            if ($service->switch(Str::snake($field))) {
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

    /**
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws DeleteException
     * @throws \Exception
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

            if ( request()->input('forceDelete', false) === false && method_exists($service, 'hasBrokingRelations') === true
                && $service->hasBrokingRelations() === true ) {

                return response()->json([
                    'message'   => 'Cant delete entity because of broking relations',
                    'canDelete' => ( (method_exists($service, 'forceDeleteAllowed') === true) ? $service->forceDeleteAllowed() : true ),
                ], 422);
            }

            if ($service->delete(true) === false) {
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
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function batch(Request $request)
    {
        try {

            $canWrite       = $this->hasPermission('write');
            $canWriteOwn    = $this->hasPermission('writeOwn');

            if ($canWrite === false && $canWriteOwn === false) {
                return response()->json([], 403);
            }

            try {
                $request = $this->requestBatchClass::createFrom($request);
            } catch (\Throwable $e) {
                throw new RequestBatchException('Cannot init batch request, wrong class');
            }

            $validator = Validator::make($request->all(), $request->rules(), $request->messages());

            if ($validator->fails()) {
                return response()->json([
                    'error'  => 'Wrong parameters passed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            /** @TODO: временно, убедиться в обратной совместимости возвращаемого фронтенду response. Incarnator | 2020-06-17 */
            $model = $this->service->getRepository()->getModel();

            $key = (($this->listKey !== null)
                ? $this->listKey
                : Str::plural(lcfirst(CommonHelper::getShortClassName(get_class($model)))));

            $saveList = $request->input($key, []);
            $saveList = ((is_array($saveList)) ? $saveList : []);

            $criteriaField = $request->input('criteriaField', 'id');
            $criteriaField = ((is_string($criteriaField) && $criteriaField !== '') ? $criteriaField : 'id');

            $idList = [];

            if (count($saveList) > 0) {

                $conditions = [
                    $criteriaField => [
                        '!=', array_values(array_filter(array_column($saveList, $criteriaField), function ($v) {
                            return $v !== null;
                        }))
                    ]
                ];

                if ($canWrite === false && $canWriteOwn === true) {
                    $conditions['owner.id'] = _getCurrentUser('api')->mapDetail(['id'])->id;
                }

            } else {
                $conditions = [];
            }

            $toDelete = $this->service::getAll(['conditions' => $conditions]);

            if (count($toDelete) > 0) {
                foreach ($toDelete as $itemToDelete) {
                    $itemToDelete->delete();
                }
            }

            if (count($saveList) > 0) {

                foreach ($saveList as $saveItem) {

                    if (array_key_exists($criteriaField, $saveItem) === false || $saveItem[$criteriaField] === null) {
                        $service = $this->service::add($this->processInput($saveItem));

                        if ($service === null) {
                            throw new ModelSaveException();
                        }

                    } else {

                        $getterConditions = [
                            $criteriaField => $saveItem[$criteriaField]
                        ];

                        if ($canWrite === false && $canWriteOwn === true) {
                            $getterConditions['owner.id'] = _getCurrentUser('api')->mapDetail(['id'])->id;
                        }

                        $service = $this->service::getOne($getterConditions);

                        if ($service !== null) {

                            if ($service->edit($this->processInput($saveItem)) === false) {
                                throw new ModelSaveException();
                            }
                        }
                    }

                    $mapping = $service->mapDetail([$criteriaField])->toArray();

                    if (array_key_exists($criteriaField, $mapping)) {
                        $idList[] = $mapping[$criteriaField];
                    }
                }
            }

            return response()->json([
                $criteriaField . 'List' => $idList,
                'message'               => __('admin/common/form.update'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Unable to batch. Error message: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @param string $permission
     * @param ServiceInterface|null $service
     *
     * @return bool
     */
    protected function hasPermission(string $permission, ?ServiceInterface $service = null) : bool
    {
        return Auth::guard('api')->user()->can($permission, [get_class($this->service), $service]);
    }

    /**
     * @param int $status
     *
     * @return \Illuminate\Http\Response|\Illuminate\View\View
     */
    protected function response(int $status = 200)
    {
        if (isset(request()->debug) || isset(request()->dd)) {
            return view('site.debug', [
                'data' => json_encode($this->data,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } else {
            return response($this->data, $status);
        }
    }
}
