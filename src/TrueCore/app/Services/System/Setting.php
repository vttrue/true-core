<?php

namespace TrueCore\App\Services\System;

use TrueCore\App\Libraries\{
    Cache,
    Config
};
use TrueCore\App\Services\Service;
use TrueCore\App\Services\Traits\Image as ImageTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

use TrueCore\App\Models\{
    System\Entity,
    System\PaymentHandler,
    Traits\HasImageFields,
    Traits\HasImages
};

/**
 * Class Setting
 *
 * @method SettingRepository getRepository()
 *
 * @method static SettingStructure[] mapList(array $items, ?array $fields = null)
 * @method SettingStructure mapDetail(?array $fields = null)
 *
 * @method static Setting|null add(array $data)
 *
 * @method static Setting|null getOne(array $conditions = [])
 * @method static Setting[] getAll(array $options = [], array $columns = ['*'])
 * @method static Setting[]|null getRandom(array $conditions = [], array $options = [])
 *
 * @package TrueCore\App\Services\System
 */
class Setting extends Service
{
    use ImageTrait;

    protected static array $validationErrors = [];

    public static array $structure = [
        'cache'         => [
            'clearedAt'   => ['type' => 'string', 'rule' => 'nullable|string|max:30'],
            'createdAt'   => ['type' => 'string', 'rule' => 'nullable|string|max:30'],
        ],
        'contacts' => [
            'phones'      => ['type' => 'array', 'rule' => 'required'],
            'email'       => ['type' => 'string', 'rule' => 'nullable|email|max:255'],
            'addresses'   => ['type' => 'array'],
            'companyName' => ['type' => 'string', 'rule' => 'nullable|email|max:1000'],
        ],
    ];

    /**
     * Setting constructor.
     *
     * @param SettingRepository|null $repository
     * @param SettingFactory|null $factory
     * @param SettingObserver|null $observer
     *
     * @throws \Exception
     */
    public function __construct(SettingRepository $repository = null, SettingFactory $factory = null, SettingObserver $observer = null)
    {
        parent::__construct($repository, $factory, $observer);
    }

    /**
     * List of each parameter get-processor callbacks
     *
     * @return array
     */
    protected static function getProcessors()
    {
        return [
            'watermark'       => [
                'watermark' => function ($value) {

                    $value  = ((is_array($value)) ? $value : []);
                    $result = [];

                    /**
                     * {"image":null,"posX":"center","posY":"center","marginX":0,"marginY":0,"opacity":0,"sizeX":1,"sizeY":1}
                     */
                    if (array_key_exists('image', $value) && is_string($value['image']) && $value['image'] !== '' && Storage::disk('image')->exists($value['image'])) {
                        $result['image'] = [
                            'image'   => $value['image'],
                            'thumb'   => Storage::disk('image')->url($value['image']),
                            'thumb2x' => Storage::disk('image')->url($value['image']),
                        ];
                    } else {
                        $result['image'] = null;
                    }

                    $xPosList = ['left', 'center', 'right'];
                    $yPosList = ['top', 'center', 'bottom'];

                    $result['posX']    = ((array_key_exists('posX', $value) && is_string($value['posX']) && in_array(strtolower($value['posX']), $xPosList)) ? strtolower($value['posX']) : 'center');
                    $result['posY']    = ((array_key_exists('posY', $value) && is_string($value['posY']) && in_array(strtolower($value['posY']), $yPosList)) ? strtolower($value['posY']) : 'center');
                    $result['marginX'] = ((array_key_exists('marginX', $value) && is_numeric($value['marginX']) && (int)$value['marginX'] >= 0) ? (int)$value['marginX'] : 0);
                    $result['marginY'] = ((array_key_exists('marginY', $value) && is_numeric($value['marginY']) && (int)$value['marginY'] >= 0) ? (int)$value['marginY'] : 0);
                    $result['sizeX']   = ((array_key_exists('sizeX', $value) && is_numeric($value['sizeX']) && (int)$value['sizeX'] > 0) ? (int)$value['sizeX'] : 1);
                    $result['sizeY']   = ((array_key_exists('sizeY', $value) && is_numeric($value['sizeY']) && (int)$value['sizeY'] > 0) ? (int)$value['sizeY'] : 1);
                    $result['opacity'] = ((array_key_exists('opacity', $value) && is_numeric($value['opacity']) && (int)$value['opacity'] >= 0 && (int)$value['opacity'] <= 100) ? (int)$value['opacity'] : 100);

                    return $result;
                },
            ],
            'images'          => [
                'previewSizes' => function ($value) {

                    $value = ((is_array($value)) ? $value : []);

                    $entityList = Entity::where('status', '=', 1)->get()->toArray();

                    $result = [];

                    foreach ($entityList as $entity) {

                        $traits = class_uses($entity['namespace']);

                        if (!in_array(HasImages::class, $traits) && !in_array(HasImageFields::class, $traits)) {
                            continue;
                        }

                        $sizes = ((array_key_exists($entity['namespace'], $value) && is_array($value[$entity['namespace']])) ? $value[$entity['namespace']] : []);
                        $sizes = array_map(function ($v) {
                            return [
                                'width'     => ((array_key_exists('width', $v) && is_numeric($v['width']) && (int)$v['width'] > 0) ? (int)$v['width'] : null),
                                'height'    => ((array_key_exists('height', $v) && is_numeric($v['height']) && (int)$v['height'] > 0) ? (int)$v['height'] : null),
                                'watermark' => (array_key_exists('watermark', $v) && is_bool($v['watermark']) && $v['watermark']),
                            ];
                        }, array_filter($sizes, function ($v) {
                            return is_array($v);
                        }));

                        $result[$entity['namespace']] = [
                            'name'   => $entity['name'],
                            'entity' => $entity['namespace'],
                            'sizes'  => array_values($sizes),
                        ];
                    }
                    Log::channel('imageResize')->info('getProcessors result');
                    Log::channel('imageResize')->info(array_values($result));
                    return array_values($result);
                },
            ],
            'paymentHandlers' => [
                'paymentHandlers' => function ($value) {

                    $value = ((is_array($value)) ? $value : []);

                    $result = [];

                    $paymentHandlerList = PaymentHandler::where('status', '=', true)->get()->toArray();

                    $settingHandlerList = array_column($value, 'handler');

                    foreach ($paymentHandlerList as $paymentHandler) {

                        $paramInd = array_search($paymentHandler['handler'], $settingHandlerList);

                        $params = null;

                        if ($paramInd !== false) {
                            $params = $value[$paramInd]['params'];
                        }

                        /**
                         * [
                         *  [
                         *  'handler' => \App\...
                         *  'id'      => 1
                         *  'params'  => [
                         *      'apiKey' => 'test'
                         *  ]
                         * ]
                         * ]
                         */

                        $result[] = [
                            'handler' => $paymentHandler['handler'],
                            'params'  => $paymentHandler['params'],
                        ];
                    }

                    return $result;
                },
            ],
        ];
    }

    /**
     * List of each parameter post processor callbacks
     *
     * @return array
     */
    protected static function postProcessors()
    {
        return [
            'watermark' => [
                'watermark' => function ($value) {

                    $result = [];

                    if (is_array($value) && array_key_exists('image', $value) && is_string($value['image']) && $value['image'] !== '') {
                        $result['image'] = $value['image'];
                    } elseif (is_string($value) && $value !== '') {
                        $result['image'] = $value;
                    } else {
                        $result['image'] = null;
                    }

                    if ($result['image'] !== null) {
                        $currentImage = Config::getInstance()->get('watermark', 'watermark', null);

                        if ($currentImage !== null && array_key_exists('image', $currentImage) && $currentImage['image'] !== $result['image']) {
                            if (Storage::disk('image')->exists($result['image'])) {

                                $ext     = basename(str_replace('.', '/', $result['image']));
                                $newPath = 'settings/watermark.' . $ext;

                                if (Storage::disk('image')->exists($newPath)) {
                                    Storage::disk('image')->delete($newPath);
                                }

                                if (Storage::disk('image')->move($result['image'], $newPath)) {
                                    $result['image'] = $newPath;
                                } else {
                                    $result['image'] = null;
                                }

                            } else {
                                $result['image'] = null;
                            }
                        }
                    }

                    $xPosList = ['left', 'center', 'right'];
                    $yPosList = ['top', 'center', 'bottom'];

                    $result['posX']    = ((array_key_exists('posX', $value) && is_string($value['posX']) && in_array(strtolower($value['posX']), $xPosList)) ? strtolower($value['posX']) : 'center');
                    $result['posY']    = ((array_key_exists('posY', $value) && is_string($value['posY']) && in_array(strtolower($value['posY']), $yPosList)) ? strtolower($value['posY']) : 'center');
                    $result['marginX'] = ((array_key_exists('marginX', $value) && is_numeric($value['marginX']) && (int)$value['marginX'] >= 0) ? (int)$value['marginX'] : 0);
                    $result['marginY'] = ((array_key_exists('marginY', $value) && is_numeric($value['marginY']) && (int)$value['marginY'] >= 0) ? (int)$value['marginY'] : 0);
                    $result['sizeX']   = ((array_key_exists('sizeX', $value) && is_numeric($value['sizeX']) && (int)$value['sizeX'] > 0) ? (int)$value['sizeX'] : 0);
                    $result['sizeY']   = ((array_key_exists('sizeY', $value) && is_numeric($value['sizeY']) && (int)$value['sizeY'] > 0) ? (int)$value['sizeY'] : 0);
                    $result['opacity'] = ((array_key_exists('opacity', $value) && is_numeric($value['opacity']) && (int)$value['opacity'] >= 0 && (int)$value['opacity'] <= 100) ? (int)$value['opacity'] : 100);

                    return $result;
                },
            ],
            'images'    => [
                'previewSizes' => function ($value) {

                    $value = ((is_array($value)) ? $value : []);

                    $entityList = Entity::where('status', '=', 1)->get()->toArray();

                    $result = [];

                    foreach ($entityList as $entity) {

                        $traits = class_uses($entity['namespace']);

                        if (!in_array(HasImages::class, $traits) && !in_array(HasImageFields::class, $traits)) {
                            continue;
                        }

                        $previewEntityList = array_column($value, 'entity');
                        $entityIndex       = array_search($entity['namespace'], $previewEntityList);

                        if ($entityIndex !== false && is_array($value[$entityIndex]) && array_key_exists('sizes', $value[$entityIndex]) && is_array($value[$entityIndex]['sizes'])) {
                            $result[$entity['namespace']] = array_values(array_map(function ($v) {
                                return [
                                    'width'     => $v['width'],
                                    'height'    => $v['height'],
                                    'watermark' => (array_key_exists('watermark', $v) && in_array($v['watermark'], [true, 'true', 1, '1'])),
                                ];
                            }, array_filter($value[$entityIndex]['sizes'], function ($v) {
                                return (
                                    is_array($v) &&
                                    (array_key_exists('width', $v) && ((is_numeric($v['width']) && $v['width'] > 0) || $v['width'] === null)) &&
                                    (array_key_exists('height', $v) && ((is_numeric($v['height']) && $v['height'] > 0) || $v['height'] === null))
                                );
                            })));
                        }
                    }
                    Log::channel('imageResize')->info('postProcessors result');
                    Log::channel('imageResize')->info($result);
                    return $result;
                },
            ],
        ];
    }

    /**
     * @param array       $data
     * @param null|string $group
     *
     * @return array
     */
    protected static function preSave(array $data, ?string $group = null)
    {
        $result = [];

        $keys = (($group) ? [$group] : array_keys(static::$structure));

        $postProcessorList = static::postProcessors();

        foreach ($keys as $key) {

            $innerKeys = array_keys(static::$structure[$key]);

            foreach ($innerKeys as $innerKey) {
                $result[$key][$innerKey] = isset($data[$key][$innerKey]) ? $data[$key][$innerKey] : null;
            }

            if ( array_key_exists($key, $postProcessorList) && count($postProcessorList[$key]) ) {
                foreach ($result[$key] as $settingName => $settingValue) {
                    if ( array_key_exists($settingName, $postProcessorList[$key]) && ($postProcessorList[$key][$settingName] instanceof \Closure) ) {
                        $result[$key][$settingName] = $postProcessorList[$key][$settingName]($result[$key][$settingName]);
                    }
                }
            }
        }

        // @TODO пофиксить сохранение текста null (пофиксил, проверить везде)

        return $result;
    }

    /**
     * @param array       $data
     * @param null|string $group
     *
     * @return bool
     */
    public function edit(array $data, ?string $group = null): bool
    {
        try {

            $data = ((count($data) === 1 && array_key_exists('settings', $data) && is_array($data['settings'])) ? $data['settings'] : $data);

            $data = static::preSave($data, $group);

            $keys = (($group !== null) ? [$group] : array_keys($data));

            foreach ($keys as $group) {
                Config::getInstance()->saveSettings($data[$group], $group);
            }

            Cache::getInstance()->forget('setting');

            return true;

        } catch (InvalidArgumentException $exception) {
            dd($exception);
        }

        return false;
    }

    /**
     * @param array       $array
     * @param null|string $group
     *
     * @return bool
     */
    protected static function validate(array $array, ?string $group = null)
    {
        static::$validationErrors = [];

        $keys = (($group !== null) ? [$group] : array_keys(static::$structure));

        foreach ($keys as $key) {

            if ( array_key_exists($key, $array) === false ) {
                static::$validationErrors[] = 'Missing group "' . $key . '"';
            }

            if(array_key_exists($key, static::$structure) === false) {

                static::$validationErrors[] = 'Missing group "' . $key . '" in settings structure';

            } else {

                $innerKeys = array_keys(static::$structure[$key]);

                foreach ($innerKeys as $innerKey) {
                    if ( array_key_exists($innerKey, $array[$key]) === false ) {
                        static::$validationErrors[] = 'Missing setting "' . $innerKey . '" in group "' . $key . '"';
                    }
                }
            }
        }

        return (count(static::$validationErrors) === 0);
    }

    /**
     * @param array       $array
     * @param null|string $group
     *
     * @return array
     */
    public static function map(array $array, string $group = null)
    {
        if ( !static::validate($array, $group) ) {
            throw new InvalidArgumentException('Mapping validation errors: ' . "\n\n" . implode("\n", static::$validationErrors), 422);
        }

        $data = [];

        $preProcessorList = static::getProcessors();

        $keys = (($group !== null) ? [$group] : array_keys(static::$structure));

        foreach ($keys as $key) {

            $data[$key] = Config::getInstance()->getGroup($key);

            if ( array_key_exists($key, $preProcessorList) && count($preProcessorList[$key]) ) {
                foreach ($data[$key] as $settingName => $settingValue) {
                    if ( array_key_exists($settingName, $preProcessorList[$key]) && ($preProcessorList[$key][$settingName] instanceof \Closure) ) {
                        $data[$key][$settingName] = $preProcessorList[$key][$settingName]($data[$key][$settingName]);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @return SettingStructure
     */
    protected function getStructureInstance() : SettingStructure
    {
        return new SettingStructure($this->getRepository());
    }
}
