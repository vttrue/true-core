<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 20.08.2020
 * Time: 13:58
 */

namespace TrueCore\App\Libraries\ImageResizeManager;

use GuzzleHttp\Exception\ClientException;
use TrueCore\App\Libraries\Image;
use TrueCore\App\Libraries\ImageResizeManager\Exceptions\ApiResponseException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
/**
 * Class ImageResizeManager
 *
 * @package TrueCore\App\Libraries\ImageResizeManager
 */
class ImageResizeManager
{
    protected string $driver = 'local';

    /**
     * ImageResizeManager constructor.
     *
     * @param string $driver
     */
    public function __construct(string $driver = 'local')
    {
        $this->driver = ((in_array($driver, ['local', 'trueResizer'], true) === true) ? $driver : 'local');
    }

    /**
     * @param array $image
     * @param array $sizeList
     *
     * @return array
     * @throws ApiResponseException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function processImage(array $image, array $sizeList = []) : array
    {
        if (array_key_exists('path', $image) === false || is_string($image['path']) === false) {
            return [];
        }

        // @TODO: refactor to objects, throw Exceptions, etc... | Deprecator @ 2020-08-20

        $hasOriginal    = false;
        $duplicateList  = [];

        $sizeListToProcess = array_filter($sizeList, function (array $v) use(&$hasOriginal, &$duplicateList) : bool {

            $isCorrect = (
                array_key_exists('method', $v) &&
                is_string($v['method']) === true &&
                in_array($v['method'], ['resize', 'fit'], true) === true &&
                array_key_exists('width', $v) &&
                (
                    (is_numeric($v['width']) && (int)$v['width'] > 0) || $v['width'] === null
                ) &&
                array_key_exists('height', $v) &&
                (
                    (is_numeric($v['height']) && (int)$v['height'] > 0) || $v['height'] === null
                )
            );

            if ($isCorrect === true) {

                if ($v['width'] === null && $v['height'] === null) {
                    $hasOriginal = true;
                }

            }

            $gravityList = [
                'center',
                'west',
                'east',
                'south',
                'north',
                'southwest',
                'southeast',
                'northwest',
                'northeast'
            ];

            $thumbSignature = $v['method'] . '_' . (int)$v['width'] . 'x' . (int)$v['height'] . '_' . ((array_key_exists('gravity', $v) && is_string($v['gravity']) && in_array($v['gravity'], $gravityList)) ? $v['gravity'] : 'center');

            if (in_array($thumbSignature, $duplicateList, true) === true) {
                return false;
            }

            $duplicateList[] = $thumbSignature;

            return $isCorrect;

        });

        if ($hasOriginal === false) {
            $sizeListToProcess = array_merge([
                [
                    'method' => 'resize',
                    'width'  => null,
                    'height' => null
                ]
            ], $sizeListToProcess);
        }

        return (($this->driver === 'local') ? $this->processLocally($image, $sizeListToProcess) : $this->processViaApi($image, $sizeListToProcess));
    }

    /**
     * @param array $image
     * @param array $sizeList
     *
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function processLocally(array $image, array $sizeList = []) : array
    {
        $force = (array_key_exists('force', $image) && $image['force'] === true);

        $resultList = [];

        foreach ($sizeList AS $sizeItem) {

            $result = null;

            // @TODO: Refactor to use objects instead of arrays | Deprecator @ 2020-08-20
            if (in_array($sizeItem['method'], ['resize', 'fit'], true) === true) {

                if ($sizeItem['method'] === 'resize') {
                    $result = Image::getInstance()->resize($image['path'], $sizeItem['width'], $sizeItem['height'], false, ((array_key_exists('gravity', $sizeItem) === true) ? $sizeItem['gravity'] : 'center'), $force);
                } else {
                    $result = Image::getInstance()->fit($image['path'], $sizeItem['width'], $sizeItem['height'], false, ((array_key_exists('gravity', $sizeItem) === true) ? $sizeItem['gravity'] : 'center'), $force);
                }

            }

            if (is_array($result) === false) {
                continue;
            }

            $resultList[] = [
                'url'        => $result['url'],
                'path'       => $result['path'],
                'method'     => $sizeItem['method'],
                'width'      => $sizeItem['width'],
                'height'     => $sizeItem['height'],
                'realWidth'  => $result['width'],
                'realHeight' => $result['height'],
                'gravity'    => ((array_key_exists('gravity', $sizeItem)) ? $sizeItem['gravity'] : 'center')
            ];
        }

        return [
            'image'     => $image,
            'thumbList' => $this->processLocalResult($resultList)
        ];
    }

    /**
     * @param array $image
     * @param array $sizeList
     *
     * @return array
     * @throws ApiResponseException
     */
    protected function processViaApi(array $image, array $sizeList = []) : array
    {
        $thumbList = [];

        $callBackHeaders = [
            'Authorization' => config('resize.drivers.trueResizer.callback.authorization')
        ];

        $pipeList = [];

        foreach ($sizeList AS $sizeItem) {

            $pipeItem = [
                'persist'   => true,
                'force'     => (array_key_exists('force', $image) && $image['force'] === true)
            ];

            $method = $sizeItem['method'];

            if ($sizeItem['width'] === null && $sizeItem['height'] === null) {
                $method = 'origin';
            }

            $pipeItem['action'] = 'resize';

            if ($method !== 'origin') {

                $pipeItem['width']  = (int)$sizeItem['width'];
                $pipeItem['height'] = (int)$sizeItem['height'];

                $gravityList = [
                    'southwest' => 'south_west',
                    'southeast' => 'south_east',
                    'northwest' => 'north_west',
                    'northeast' => 'north_east'
                ];

                if (array_key_exists('gravity', $sizeItem) === true) {
                    $pipeItem['gravity'] = str_replace(array_keys($gravityList), $gravityList, $sizeItem['gravity']);
                }

                if (array_key_exists('backgroundColor', $sizeItem) === true) {
                    $pipeItem['background_color'] = $sizeItem['backgroundColor'];
                }

                $pipeItem['persist']        = false;

                // Not used to be true if not set explicitly but turned out to produce a better proportioned thumb | Deprecator @ 2021-01-11
                if (array_key_exists('leastDimension', $sizeItem) === true && is_bool($sizeItem['leastDimension'])) {
                    $pipeItem['least_dimension'] = $sizeItem['leastDimension'];
                } else {
                    $pipeItem['least_dimension'] = true;
                }

                // Used to be true if not set explicitly but turned out to have some misconduct with expected result | Deprecator @ 2021-01-11
                if (array_key_exists('saveAspect', $sizeItem) === true && is_bool($sizeItem['saveAspect'])) {
                    $pipeItem['save_aspect'] = $sizeItem['saveAspect'];
                }

                $secondPipeItem = $pipeItem;

                $secondPipeItem['persist']    = true;
                $secondPipeItem['action']     = (($method === 'resize') ? 'fit' : 'crop');

                unset($secondPipeItem['save_aspect'], $secondPipeItem['least_dimension']);

                $pipeList[] = [
                    'pipe' => [
                        $pipeItem,
                        $secondPipeItem
                    ],
                ];

            } else {

                $pipeItem['action'] = 'origin';

                $pipeList[] = [
                    'pipe'  => [
                        $pipeItem
                    ]
                ];
            }
        }

//        $image['path'] = 'storage/d98b1e1c69320d97f276a9eb118410d6_origin.jpg';
//        $pipeList = [
//            [
//                'pipe' => [
//                    /*[
//                        'action'      => 'resize',
//                        'persist'     => false,
//                        'save_aspect' => true,
//                        'width'       => 370,
//                        'height'      => 370,
//                        'gravity'     => 'CENTER'
//                    ],*/ [
//                        'action'  => 'fit',
//                        'persist' => true,
//                        'width'   => 836,
//                        'height'  => 836,
//                        'gravity' => 'CENTER'
//                    ]
//                ]
//            ]
//        ];

        $imageInfo = ((array_key_exists('additionalData', $image) && is_array($image['additionalData']) && count($image['additionalData']) > 0) ? $image['additionalData'] : []);

        $requestUrl     = config('resize.drivers.trueResizer.url');
        $requestBody    = [
            [
                'key'             => md5(serialize($image)),
                'bucket'          => [
                    'source' => config('resize.drivers.trueResizer.bucket.source'),
                    'dest'   => config('resize.drivers.trueResizer.bucket.dest'),
                ],
                'imagePath'       => $image['path'],
                'deleteOriginal'  => false,
                'tags'            => [],
                'callback'        => config('resize.drivers.trueResizer.callback.url'),
                'callbackHeaders' => $callBackHeaders,
                'additionalData'  => [
                    'imageInfo' => $imageInfo,
                    'pipeList'  => $pipeList
                ],
                'sizes'           => $pipeList,
            ],
        ];

        $requestHeaders = [
            'Authorization' => 'Bearer ' . config('resize.drivers.trueResizer.authorization'),
            'Content-Type'  => 'application/json',
        ];

        $httpClient = new Client();

        try {
            Log::channel('imageResize')->info('Sended to observer');
            Log::channel('imageResize')->info($requestBody);
            $response = $httpClient->post($requestUrl, [
                'body'    => json_encode($requestBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'headers' => $requestHeaders
            ]);

            $responseBody   = $response->getBody()->getContents();

            if ($response->getStatusCode() === 200) {

                $chunkList  = ((is_array($responseBody)) ? $responseBody : json_decode($responseBody, true));

                if (is_array($chunkList) && count($chunkList) > 0) {

                    foreach ($chunkList AS $chunk) {

                        if (!array_key_exists('sourcePath', $chunk['additionalData']['imageInfo']) || $chunk['additionalData']['imageInfo']['sourcePath'] !== $imageInfo['sourcePath']) {
                            continue;
                        }

                        $thumbList = $this->processApiResult($chunk);
                    }
                }

            } else {

                throw new ApiResponseException(
                    'Resize API request failed. Server responded with code ' . $response->getStatusCode() . "\n" .
                    json_encode([
                        'request'   => [
                            'headers'   => $requestHeaders,
                            'body'      => $requestBody
                        ],
                        'response'  => [
                            'headers'   => $response->getHeaders(),
                            'body'      => $responseBody
                        ]
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    0,
                    null,
                    $response->getStatusCode()
                );

            }

        } catch (ClientException $e) {

            throw new ApiResponseException(
                'Resize API request failed. Server responded with code ' . $e->getResponse()->getStatusCode() . "\n" .
                json_encode([
                    'request'   => [
                        'headers'   => $requestHeaders,
                        'body'      => $requestBody
                    ],
                    'response'  => [
                        'headers'   => $e->getResponse()->getHeaders(),
                        'body'      => $e->getResponse()->getBody()
                    ],
                    'message'   => $e->getMessage()
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                0,
                null,
                $e->getResponse()->getStatusCode()
            );

        }

        return [
            'image'     => $image,
            'thumbList' => $thumbList
        ];
    }

    /**
     * @param array $response
     *
     * @return array
     */
    public function processResult(array $response) : array
    {
        return (($this->driver === 'local') ? $this->processLocalResult($response) : $this->processApiResult($response));
    }

    /**
     * @param array $response
     *
     * @return array
     */
    protected function processLocalResult(array $response) : array
    {
        return array_map(function (array $item) : array {

            [$realWidth, $realHeight] = getimagesize($item['url']);

            return [
                'path'          => $item['path'],
                'method'        => $item['method'],
                'gravity'       => $item['gravity'],
                'width'         => $item['width'],
                'height'        => $item['height'],
                'realWidth'     => $item['realWidth'],
                'realHeight'    => $item['realHeight']
            ];
        }, $response);
    }

    /**
     * @param array $response
     *
     * @return array
     */
    protected function processApiResult(array $response) : array
    {
        $thumbList = [];

        $path = ((array_key_exists('path', $response) && is_string($response['path']) && trim($response['path']) !== '') ? $response['path'] : null);

        $awsPrefix = config('filesystems.disks.image.awsPrefix');
        $awsPrefix = substr($awsPrefix, -1) !== '/' ? $awsPrefix . "/" : $awsPrefix;
        $path = str_replace($awsPrefix,"", $path);
        if ($path === null || (array_key_exists('processed', $response) === true && is_array($response['processed']) === true && count($response['processed']) > 0) === false) {
            return $thumbList;
        }

        $pathParts     = explode('.', $path);
        $pathPartCount = count($pathParts);

        if ($pathPartCount < 2) {
            return $thumbList;
        } elseif ($pathPartCount === 2) {
            $thumbPath = $pathParts[0];
            $thumbExt  = $pathParts[1];
        } else {
            $thumbPath = array_slice($pathParts, 0, ($pathPartCount - 1));
            $thumbExt  = $pathParts[($pathPartCount - 1)];
        }

        $processedElementList = array_filter($response['processed'], function (array $processedElement) : bool {
            return (
                array_key_exists('type', $processedElement) &&
                is_string($processedElement['type']) &&
                trim($processedElement['type']) !== '' &&
                array_key_exists('fullPath', $processedElement) &&
                is_string($processedElement['fullPath']) &&
                trim($processedElement['fullPath']) !== '' &&
                array_key_exists('resultWidth', $processedElement) &&
                is_numeric($processedElement['resultWidth']) &&
                (int)$processedElement['resultWidth'] > 0
                && array_key_exists('resultHeight', $processedElement) &&
                is_numeric($processedElement['resultHeight']) &&
                (int)$processedElement['resultHeight'] > 0 &&
                (
                    array_key_exists('width', $processedElement) &&
                    (is_numeric($processedElement['width']) || $processedElement['width'] === null)
                ) &&
                (
                    array_key_exists('height', $processedElement) &&
                    (is_numeric($processedElement['height']) || $processedElement['height'] === null)
                )
            );
        });

        $originWidth  = ((array_key_exists('width', $response) && is_numeric($response['width']) && (int)$response['width'] > 0) ? (int)$response['width'] : null);
        $originHeight = ((array_key_exists('height', $response) && is_numeric($response['height']) && (int)$response['height'] > 0) ? (int)$response['height'] : null);

        $thumbList[] = [
            'path'       => $thumbPath . '_origin.' . $thumbExt,
            'method'     => 'resize',
            'gravity'    => 'center',
            'width'      => null,
            'height'     => null,
            'realWidth'  => $originWidth,
            'realHeight' => $originHeight
        ];

        $dimensionList = array_map(function ($v) : string {

            $pathSegments = explode('_', $v['fullPath']);

            $gravity = null;

            if (in_array($v['type'], ['fit', 'crop'], true) === true) {
                $gravity = 'center';

                if (count($pathSegments) >= 4) {
                    $gravity = $pathSegments[3];
                }
            }

            return ($v['type'] . '_' . $v['width'] . '_' . $v['height'] . (($gravity !== null) ? '_' . $gravity : ''));
        }, $processedElementList);

        foreach ($response['additionalData']['pipeList'] AS ['pipe' => $pipeItemList]) {

            if (count($pipeItemList) === 2) {

                $thumbType = null;

                if ($pipeItemList[0]['action'] === 'resize' && $pipeItemList[1]['action'] === 'fit') {
                    $thumbType = 'resize';
                } elseif ($pipeItemList[0]['action'] === 'resize' && $pipeItemList[1]['action'] === 'crop') {
                    $thumbType = 'fit';
                }

                if ($thumbType !== null) {

                    $thumbWidth  = $pipeItemList[1]['width'];
                    $thumbHeight = $pipeItemList[1]['height'];

                    $thumbGravity = null;

                    if (in_array($pipeItemList[1]['action'], ['fit', 'crop'], true) === true) {
                        $thumbGravity = ((array_key_exists('gravity', $pipeItemList[1]) === true) ? strtolower($pipeItemList[1]['gravity']) : 'center');
                    }

                    $dimensionKey = $pipeItemList[1]['action'] . '_' . $thumbWidth . '_' . $thumbHeight . (($thumbGravity !== null) ? '_' . $thumbGravity : '');
                    $dimensionInd = array_search($dimensionKey, $dimensionList, true);

                    if ($dimensionInd !== false && array_key_exists($dimensionInd, $processedElementList) === true) {
                        $thumbList[] = [
                            'path'       => $thumbPath . '_' . $processedElementList[$dimensionInd]['fullPath'] . '.' . $thumbExt,
                            'method'     => $thumbType,
                            'gravity'    => ((array_key_exists('gravity', $processedElementList[$dimensionInd])) ? $processedElementList[$dimensionInd]['gravity'] : 'center'),
                            'width'      => (($processedElementList[$dimensionInd]['width'] !== 0) ? (int)$processedElementList[$dimensionInd]['width'] : null),
                            'height'     => (($processedElementList[$dimensionInd]['height'] !== 0) ? (int)$processedElementList[$dimensionInd]['height'] : null),
                            'realWidth'  => (int)$processedElementList[$dimensionInd]['resultWidth'],
                            'realHeight' => (int)$processedElementList[$dimensionInd]['resultHeight']
                        ];
                    }
                }
            }

        }
        //dd($response, $dimensionList, $thumbList);

        return $thumbList;
    }
}
