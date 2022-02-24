<?php

namespace TrueCore\App\Http\Controllers\Base;

use TrueCore\App\Exceptions\Http\Controllers\ResourceItemException;
use TrueCore\App\Http\Controllers\Controller as BaseController;
use TrueCore\App\Http\Controllers\Api\ApiController as ApiControllerInterface;
use TrueCore\App\Http\Resources\Api\Traits\Adjustable;
use TrueCore\App\Libraries\Cache;
use Illuminate\Foundation\Validation\ValidatesRequests;
use TrueCore\App\Services\Service;
use TrueCore\App\Http\Controllers\Admin\Traits\{
    Search,
    Sort
};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ApiController
 *
 * @property Service|null $service
 * @property string       $resourceListClass
 * @property string       $resourceItemClass
 * @property string|null  $listKey
 * @property string|null  $itemKey
 * @property bool         $thumbs
 * @property array        $namespaceSegments
 *
 * @package TrueCore\App\Http\Controllers\Base
 */
abstract class ApiController extends BaseController implements ApiControllerInterface
{
    use Search, Sort, ValidatesRequests;

    protected ?Service $service           = null;
    protected string   $resourceListClass = '';
    protected string   $resourceItemClass = '';
    protected ?string  $listKey           = null;
    protected ?string  $itemKey           = null;
    protected bool     $thumbs            = true;
    protected array    $namespaceSegments = [];
    protected ?array   $defaultSortParam  = null;

    /**
     * ApiController constructor.
     *
     * @param Service $service
     * @param string  $resourceListClass
     * @param string  $resourceItemClass
     * @param bool    $thumbs
     */
    public function __construct(Service $service, string $resourceListClass, string $resourceItemClass, bool $thumbs = true)
    {
        $this->service = $service;
        $this->resourceListClass = $resourceListClass;
        $this->resourceItemClass = $resourceItemClass;
        $this->thumbs = $thumbs;
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws \Exception
     */
    public function getItemList()
    {
        $model = $this->service->getRepository()->getModel();

        $entityList = $this->getAvailableEntities();

        // TODO: Реализовать! Incarnator | 2020-03-03
//        if ((array_key_exists('Shop', $entityList) && is_array($entityList['Shop']) && in_array('Category', $entityList['Shop'])) === false) {
//            $this->data['message'] = 'Not available';
//            return $this->response(403);
//        }

        // TODO: Реализовать! Incarnator | 2020-03-23
//        if (is_array($this->namespaceSegments) && count($this->namespaceSegments) === 2) {
//            if ((array_key_exists($this->namespaceSegments[0], $entityList) && is_array($entityList[$this->namespaceSegments[0]]) && in_array($this->namespaceSegments[1], $entityList[$this->namespaceSegments[0]])) === false) {
//                $this->data['message'] = 'Not available';
//                return $this->response(403);
//            }
//        }

        $thumbType = request()->input('thumbType', ['resize']);
        $thumbDimensions = request()->input('thumbDimensions', ['auto,auto']);
        $listThumbType = request()->input('listThumbType', ['resize']);
        $listThumbDimensions = request()->input('listThumbDimensions', ['auto,auto']);

        $idList = [];

        if ( $this->thumbs !== false ) {

            if ( is_array($thumbType) ) {
                $thumbType = ((count(array_filter($thumbType, static fn($v): bool => in_array($v, ['fit', 'resize'], true))) > 0) ? $thumbType : ['resize']);
            } elseif ( is_string($thumbType) ) {
                $thumbType = [((in_array($thumbType, ['fit', 'resize'], true)) ? $thumbType : 'resize')];
            }

            if ( is_array($thumbDimensions) ) {
                $thumbDimensions = ((count($thumbDimensions) > 0) ? array_filter($thumbDimensions, static fn($v): bool => (is_string($v) && count(explode(',', $v)) === 2)) : ['auto,auto']);
                $thumbDimensions = ((count($thumbDimensions) > 0) ? $thumbDimensions : ['auto,auto']);
            } elseif ( is_string($thumbDimensions) ) {
                $thumbDimensions = [((count(explode(',', $thumbDimensions)) === 2) ? $thumbDimensions : 'auto,auto')];
            }

            if ( is_array($listThumbType) ) {
                $listThumbType = ((count(array_filter($listThumbType, static fn($v): bool => in_array($v, ['fit', 'resize'], true))) > 0) ? $listThumbType : ['resize']);
            } elseif ( is_string($thumbType) ) {
                $listThumbType = [((in_array($listThumbType, ['fit', 'resize'], true)) ? $listThumbType : 'resize')];
            }

            if ( is_array($listThumbDimensions) ) {
                $listThumbDimensions = ((count($listThumbDimensions) > 0) ? array_filter($listThumbDimensions, static fn($v): bool => (is_string($v) && count(explode(',', $v)) === 2)) :
                    ['auto,auto']);
                $listThumbDimensions = ((count($listThumbDimensions) > 0) ? $listThumbDimensions : ['auto,auto']);
            } elseif ( is_string($listThumbDimensions) ) {
                $listThumbDimensions = [((count(explode(',', $listThumbDimensions)) === 2) ? $listThumbDimensions : 'auto,auto')];
            }
        }

        $fields = request()->input('fields', null);
        $fields = ((is_string($fields) && $fields !== '') ? explode(',', $fields) : null);

        $limit = request()->input('limit', 10);
        $limit = ((is_numeric($limit) && (int) $limit >= 0) ? (int) $limit : 10);
        $offset = request()->input('offset', 0);
        $offset = ((is_numeric($offset) && (int) $offset >= 0) ? (int) $offset : 0);

        if ( is_array($fields) ) {
            $fields = static::processFields($fields);
            sort($fields);
        }

        $sortParams = [];

        if ( $this->hasSortRequest() ) {
            $sortParams = $this->getSortRequest();
        }
//        var_dump($sortParams);die;
        $conditions = $this->getConditions();

        $cacheKey = md5(serialize([
            'conditions'          => $conditions,
            'fields'              => $fields,
            'idList'              => $idList,
            'listThumbDimensions' => $listThumbDimensions,
            'listThumbType'       => $listThumbType,
            'thumbDimensions'     => $thumbDimensions,
            'thumbType'           => $thumbType,
            'limit'               => $limit,
            'offset'              => $offset,
            'sort'                => $sortParams,
        ]));

        $responseDataCacheCallback = function() use ($model, $cacheKey, $fields, $idList, $limit, $offset, $listThumbDimensions, $listThumbType, $thumbDimensions, $thumbType, $conditions, $sortParams
        ) {

            $cacheCallback = function() use ($model, $fields, $idList, $limit, $offset, $conditions, $sortParams) {

                $itemList = $this->service::getAllDynamicPaginator([
                    'conditions' => $conditions,
                    'sort'       => $sortParams,
                    'fields'     => $fields,
                ], $offset, $limit);

                return [
                    'items' => $this->service::mapList($itemList->items(), $fields),
                    'meta'  => [
                        'limit'  => $limit,
                        'offset' => $offset,
                        'total'  => $itemList->total(),
                    ],
                ];
            };

            if ( count($idList) > 0 ) {
                $itemList = Cache::getInstance()->rememberEntityGroupRecord(
                    get_class($model),
                    $idList,
                    $cacheKey,
                    $cacheCallback,
                    config('cache.lifetime', 7200)
                );
            } else {
                $itemList = Cache::getInstance()->rememberEntityRecord(
                    get_class($model),
                    '',
                    $cacheKey,
                    $cacheCallback,
                    config('cache.lifetime', 7200)
                );
            }

            $result = new $this->resourceListClass($itemList['items']);

            if ( in_array(Adjustable::class, array_values(class_uses($result))) ) {
                if ( $this->thumbs === true ) {
                    $result = $result
                        ->applyListThumbType($listThumbType)
                        ->applyListThumbDimensions($listThumbDimensions)
                        ->applyThumbType($thumbType)
                        ->applyThumbDimensions($thumbDimensions);
                }
                if ( is_array($fields) && count($fields) > 0 ) {
                    $result = $result->applyFieldList($fields);
                }
                $result = $result->toResponse(request())->getData(true)['data'];
            }

            return [
                'items' => $result,
                'meta'  => $itemList['meta'],
            ];
        };

        if ( count($idList) > 0 ) {
            $result = Cache::getInstance()->rememberEntityGroupRecord(
                get_class($model),
                $idList,
                $cacheKey . '_responseArray',
                $responseDataCacheCallback,
                config('cache.lifetime', 3600)
            );
        } else {
            $result = Cache::getInstance()->rememberEntityRecord(
                get_class($model),
                '',
                $cacheKey . '_responseArray',
                $responseDataCacheCallback,
                config('cache.lifetime', 3600)
            );
        }

        $listKey = ((is_string($this->listKey) && $this->listKey !== '') ? $this->listKey : 'items');

        /** @TODO: избавиться, когда везде listKey будет items. Incarnator | 2020-11-17 */
        if ( $listKey !== 'items' ) {
            $this->data = [
                $listKey => $result['items'],
                'items'  => $result['items'],
                'meta'   => $result['meta'],
            ];
        } else {
            $this->data = [
                'items'  => $result['items'],
                'meta'   => $result['meta'],
            ];
        }

//        $this->data = [
//            $listKey => $result['items'],
//            'meta'   => $result['meta'],
//        ];

        return $this->response();
    }

    /**
     * @param $id
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Contracts\View\Factory|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Illuminate\View\View
     * @throws \Exception
     */
    public function getItem($id)
    {
        try {

            $entityList = $this->getAvailableEntities();

            // TODO: Реализовать! Incarnator | 2020-03-03
//            if ((array_key_exists('Shop', $entityList) && is_array($entityList['Shop']) && in_array('Category', $entityList['Shop'])) === false) {
//                $this->data['message'] = 'Not available';
//                return $this->response(403);
//            }

            // TODO: Реализовать! Incarnator | 2020-03-23
//            if (is_array($this->namespaceSegments) && count($this->namespaceSegments) === 2) {
//                if ((array_key_exists($this->namespaceSegments[0], $entityList) && is_array($entityList[$this->namespaceSegments[0]]) && in_array($this->namespaceSegments[1], $entityList[$this->namespaceSegments[0]])) === false) {
//                    $this->data['message'] = 'Not available';
//                    return $this->response(403);
//                }
//            }

            $statusCode = 200;

            $thumbDimensions = request()->input('thumbDimensions', ['auto,auto']);
            $listThumbDimensions = request()->input('listThumbDimensions', ['auto,auto']);
            $thumbType = request()->input('thumbType', ['resize']);
            $listThumbType = request()->input('listThumbType', ['resize']);

            if ( $this->thumbs !== false ) {

                if ( is_array($thumbType) ) {
                    $thumbType = ((count(array_filter($thumbType, static fn($v): bool => in_array($v, ['fit', 'resize'], true))) > 0) ? $thumbType : ['resize']);
                } elseif ( is_string($thumbType) ) {
                    $thumbType = [((in_array($thumbType, ['fit', 'resize'], true)) ? $thumbType : 'resize')];
                }

                if ( is_array($thumbDimensions) ) {
                    $thumbDimensions = ((count($thumbDimensions) > 0) ? array_filter($thumbDimensions, static fn($v): bool => (is_string($v) && count(explode(',', $v)) === 2)) : ['auto,auto']);
                    $thumbDimensions = ((count($thumbDimensions) > 0) ? $thumbDimensions : ['auto,auto']);
                } elseif ( is_string($thumbDimensions) ) {
                    $thumbDimensions = [((count(explode(',', $thumbDimensions)) === 2) ? $thumbDimensions : 'auto,auto')];
                }

                if ( is_array($listThumbDimensions) ) {
                    $listThumbDimensions = ((count($listThumbDimensions) > 0) ? array_filter($listThumbDimensions, static fn($v): bool => (is_string($v) && count(explode(',', $v)) === 2)) :
                        ['auto,auto']);
                    $listThumbDimensions = ((count($listThumbDimensions) > 0) ? $listThumbDimensions : ['auto,auto']);
                } elseif ( is_string($listThumbDimensions) ) {
                    $listThumbDimensions = [((count(explode(',', $listThumbDimensions)) === 2) ? $listThumbDimensions : 'auto,auto')];
                }

                if ( is_array($listThumbType) ) {
                    $listThumbType = ((count(array_filter($listThumbType, static fn($v): bool => in_array($v, ['fit', 'resize'], true))) > 0) ? $listThumbType : ['resize']);
                } elseif ( is_string($listThumbType) ) {
                    $listThumbType = [((in_array($listThumbType, ['fit', 'resize'], true)) ? $listThumbType : 'resize')];
                }

            }

            $fields = request()->input('fields', null);
            $fields = ((is_string($fields) && $fields !== '') ? explode(',', $fields) : null);

            if ( is_array($fields) ) {
                $fields = static::processFields($fields);
                sort($fields);
            }

            $type = request()->input('type', 'slug');
            if ( !in_array($type, ['slug', 'id']) ) {
                $type = 'slug';
            }

            $cacheKey = 'detail_' . get_class($this->service) . '_by_' . $type . '_'
                        . md5(serialize([
                            'fields'              => $fields,
                            'id'                  => $id,
                            'listThumbDimensions' => $listThumbDimensions,
                            'listThumbType'       => $listThumbType,
                            'status'              => true,
                            'thumbDimensions'     => $thumbDimensions,
                            'thumbType'           => $thumbType,
                            'type'                => $type,
                        ]));

            $responseDataCacheCallback = function() use ($cacheKey, $fields, $id, $listThumbDimensions, $listThumbType, $thumbDimensions, $thumbType, $type) {

                $cacheCallback = function() use ($cacheKey, $fields, $id, $listThumbDimensions, $listThumbType, $thumbDimensions, $thumbType, $type) {

                    $service = $this->service::getOne([
                                                          (($type === 'slug') ? 'slug' : 'id') => $id,
                                                          'status'                             => true,
                                                      ], [
                                                          'fields' => $fields,
                                                      ]);

                    return (($service !== null) ? $service->mapDetail($fields) : null);
                };

                $item = Cache::getInstance()->rememberEntityRecord(
                    get_class($this->service),
                    $id,
                    $cacheKey,
                    $cacheCallback,
                    config('cache.lifetime', 7200)
                );

                if ( $item === null ) {
                    return $item;
                }

                try {

                    $result = (new $this->resourceItemClass($item));

                } catch (\Throwable $e) {
                    throw new ResourceItemException('wrong resource item class or some internal error occurred');
                }

                if ( in_array(Adjustable::class, array_values(class_uses($result))) ) {
                    if ( $this->thumbs === true ) {
                        $result = $result
                            ->applyThumbDimensions($thumbDimensions)
                            ->applyThumbType($thumbType)
                            ->applyListThumbType($listThumbType)
                            ->applyListThumbDimensions($listThumbDimensions);
                    }
                    $result = $result->applyFieldList($fields);
                }

                if ( is_string($this->itemKey) ) {
                    return [
                        $this->itemKey => $result->toArray(request()),
                    ];
                }

                return $result->toArray(request());
            };

            $this->data = Cache::getInstance()->rememberEntityRecord(
                get_class($this->service),
                $id,
                $cacheKey . '_responseArray',
                $responseDataCacheCallback,
                config('cache.lifetime', 3600)
            );

            if ( $this->data === null ) {
                $statusCode = 404;
            }

            return $this->response($statusCode);

        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => 'not found'], 404);
        } catch (ResourceItemException $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
    }

    /**
     * @return array
     */
    protected static function getConditions(): array
    {
        $conditions = ['status' => true];

        $idList = request()->input('id', []);
        $idList = ((is_array($idList)) ? $idList : []);
        $idList = array_filter($idList, function($v) {
            return is_numeric($v);
        });
        sort($idList);

        if ( count($idList) > 0 ) {
            $conditions['id'] = $idList;
        }

        return $conditions;
    }

    /**
     * @param array|null $fields
     *
     * @return array
     */
    protected static function processFields(?array $fields = null): ?array
    {
        $outData = $fields;

        return $outData;
    }
}
