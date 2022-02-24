<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 27.07.2019
 * Time: 18:11
 */

namespace TrueCore\App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use TrueCore\App\Libraries\ImageResizeManager\ImageResizeManager;

/**
 * Class GenerateSingleThumb
 *
 * @package TrueCore\App\Jobs
 */
class GenerateSingleThumb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $_method  = 'resize';
    private string $_sourcePath;
    private ?int   $_width   = null;
    private ?int   $_height  = null;
    private string $_gravity = 'center';

    private ?string $_entityNamespace = null;
    private ?int    $_entityId        = null;

    private array $_additionalData = [];

    private bool $_force = false;

    /**
     * Create a new job instance.
     *
     * @param string $method
     * @param string $sourcePath
     * @param int|null $width
     * @param int|null $height
     * @param string $gravity
     * @param string|null $entityNamespace
     * @param int|null $entityId
     * @param array $additionalData
     * @param bool $force
     *
     * @return void
     */
    public function __construct(string $method, string $sourcePath, ?int $width, ?int $height, string $gravity = 'center', ?string $entityNamespace = null, ?int $entityId = null, array $additionalData = [], bool $force = false)
    {
        $this->queue            = 'generate_thumbs';

        $this->_method     = $method;
        $this->_sourcePath = $sourcePath;
        $this->_width      = ((is_int($width) && $width > 0) ? $width : null);
        $this->_height     = ((is_int($height) && $height > 0) ? $height : null);

        $this->_entityNamespace = $entityNamespace;
        $this->_entityId        = $entityId;

        $this->_additionalData = $additionalData;

        $this->_force = $force;
    }

    /**
     * @return int
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \TrueCore\App\Libraries\ImageResizeManager\Exceptions\ApiResponseException
     */
    public function handle()
    {
        if(in_array($this->_method, ['resize', 'fit']) && is_string($this->_sourcePath) && $this->_sourcePath !== '') {

            $previewPath = null;

            $resizeDriver = config('resize.driver');

            $thumbParams = [
                    'mayBeRemoved'  => ($resizeDriver === 'local'),
                    'driver'        => $resizeDriver,
                    'entity'        => $this->_entityNamespace,
                    'key'           => $this->_entityId,
                    'sourcePath'    => $this->_sourcePath
                ] + $this->_additionalData;

            $resizeResult = (new ImageResizeManager($resizeDriver))->processImage([
                'path'           => $this->_sourcePath,
                'additionalData' => $thumbParams,
                'force'          => $this->_force
            ], [
                [
                    'method'  => $this->_method,
                    'width'   => (($this->_width !== null) ? $this->_width : null),
                    'height'  => (($this->_height !== null) ? $this->_height : null),
                    'gravity' => $this->_gravity
                ]
            ]);

            $thumbList = array_values(array_filter($resizeResult['thumbList'], function ($thumb) {
                return (
                    $thumb['method'] === $this->_method &&
                    (
                        ($thumb['width'] === $this->_width && $thumb['height'] === $this->_height) ||
                        ($thumb['width'] === null && $thumb['height'] === null)
                    ) &&
                    $thumb['gravity'] === $this->_gravity
                );
            }));

            if(count($thumbList) > 0 && $this->_entityNamespace !== null && $this->_entityId !== null) {

                try {

                    $entity = (new $this->_entityNamespace)->find($this->_entityId);

                    if ($entity !== null && method_exists($entity, 'saveThumbs') === true) {
                        $entity->saveThumbs($resizeResult['image'], $resizeResult['thumbList']);
                    }

                } catch (\Throwable $e) {

                    Log::channel('imageResize')->info(json_encode([
                        'data'      => [
                            'method'        => $this->_method,
                            'sourcePath'    => $this->_sourcePath,
                            'width'         => $this->_width,
                            'height'        => $this->_height,
                            'gravity'       => $this->_gravity,
                            'entity'        => $this->_entityNamespace,
                            'entityId'      => $this->_entityId,
                            'params'        => $thumbParams,
                            'force'         => $this->_force
                        ],
                        'exception' => get_class($e),
                        'message'   => $e->getMessage(),
                        'file'      => $e->getFile(),
                        'line'      => $e->getLine(),
                        'code'      => $e->getCode()
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

                    $this->release(5);

                }
            }
        }

        return 0;
    }
}