<?php

namespace TrueCore\App\Libraries;

use Illuminate\Support\{
    Facades\Storage,
    Str
};

/**
 * Class Image
 *
 * @package TrueCore\App\Libraries
 */
class Image
{
    private static ?Image $_instance = null;

    const ORIGIN = 'storage/images/';
    const CACHE = 'storage/images/cache/';

    const NO_IMAGE = 'no_image.svg';
    const OG_NO_IMAGE = 'no_image.svg';

    const WEBP_QUALITY_STANDARD = 90;
    const WEBP_QUALITY_BEST = 95;

    private array $mimeTypes = [
        'image/gif'     => 'gif',
        'image/jpeg'    => 'jpg',
        'image/pjpeg'   => 'jpg',
        'image/png'     => 'png',
        'image/svg+xml' => 'svg',
        'image/svg'     => 'svg'
    ];

    private array $gravityList = [
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

    protected function __construct()
    {
        //
    }

    private function __clone()
    {
        //
    }

    private function __wakeup()
    {
        //
    }

    /**
     * @return Image
     */
    public static function getInstance() : Image
    {
        if ((self::$_instance instanceof Image) === false) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * @return bool
     */
    private static function shouldExecuteCommands() : bool
    {
        $commandList = ['mogrify', 'optipng', 'jpegoptim', 'cwebp', 'composite'];

        $output = shell_exec('ps -axeo pid,command | grep -E "(' . implode('|', $commandList) . ')"');

        if (is_string($output) === true && trim($output) !== '') {

            return (
                count(
                    array_filter(
                        explode("\n", $output),
                        static function (string $line) use ($commandList) : bool {

                            $parts = explode(' ', $line);

                            return (count($parts) > 1 && in_array($parts[1], $commandList) === true);
                        }
                    )
                ) === 0
            );

        }

        return true;
    }

    /**
     * @param string $fileName
     * @param string $method
     * @param int|null $width
     * @param int|null $height
     * @param bool $version
     * @param string $align
     * @param bool $regenerate
     * @param array|null
     *
     * @return array|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function getThumb(string $fileName, string $method, ?int $width = null, ?int $height = null, bool $version = false, string $align = 'center', bool $regenerate = false, ?array $waterMarkParams = null) : ?array
    {
        if (!Storage::disk('image')->exists($fileName !== 'no_image' ? $fileName : self::NO_IMAGE)) {
            return null;
        }

        $method = ((in_array($method, ['resize', 'fit'], true) === true) ? $method : 'resize');

        if (in_array($fileName, ['no_image', 'og_no_image'], true) === true) {

            if ($method === 'resize') {
                $fileName = (($fileName === 'no_image') ? self::NO_IMAGE : self::OG_NO_IMAGE);
            } else {
                return self::resize((($fileName === 'no_image') ? self::NO_IMAGE : self::OG_NO_IMAGE), $width, $height);
            }
        }

        $mimeType = Storage::disk('image')->mimeType($fileName);

        if (array_key_exists($mimeType, $this->mimeTypes)) {

            $ext = $this->mimeTypes[$mimeType];

            $resultWidth  = null;
            $resultHeight = null;

            switch ($ext) {
                case 'svg':
                    $result = self::ORIGIN . $fileName;
                    break;
                default:

                    $stream = Storage::disk('image')->readStream($fileName);

                    if (is_resource($stream) === true && ($streamContents = stream_get_contents($stream)) !== false) {

                        [$originalWidth, $originalHeight] = getimagesizefromstring($streamContents);

                    } else {
                        return self::resize(self::NO_IMAGE, $width, $height);
                    }

                    [
                        'path'      => $probablePath,
                        'width'     => $width,
                        'height'    => $height
                    ] = $this->guessThumbInfo($fileName, $method, $originalWidth, $originalHeight, $width, $height);

                    $cachePath = 'cache/' . $probablePath;

                    $result = self::CACHE . $probablePath;

                    $originalFileInfo = Storage::disk('image')->getMetadata($fileName);
                    $resultFileInfo   = ((Storage::disk('image')->exists($cachePath) === true) ? Storage::disk('image')->getMetadata($cachePath) : null);

                    if ($resultFileInfo === null || ($originalFileInfo['timestamp'] > $resultFileInfo['timestamp']) || $regenerate === true) {

                        if (in_array($ext, ['jpg', 'png'], true) === true) {

                            $tempPath = 'tmp/' . str_replace('/', '_', $fileName) . '.' . $ext;

                            if (
                                (
                                    Storage::disk('local')->exists($tempPath) === false ||
                                    Storage::disk('local')->delete($tempPath) === true
                                )
                                &&
                                Storage::disk('local')->put($tempPath, $streamContents) === true
                            ) {

                                $tempAbsPath = Storage::disk('local')->path($tempPath);

                                $command   = null;
                                $wpCommand = null;

                                if ($method === 'resize') {

                                    if ($ext === 'png') {
                                        $command   = 'mogrify -write "' . $tempAbsPath . '" -resize ' . $width . 'x' . $height
                                            . ' -gravity ' . $align . ' -extent ' . $width . 'x' . $height . ' -background none -quality 100 -strip -colorspace sRGB "'
                                            . $tempAbsPath . '" &&'
                                            . 'optipng -o2 -strip all "' . $tempAbsPath . '"';
                                        $wpCommand = static::makeWpCommand($tempAbsPath, $width, $height);
                                    }

                                    if ($ext === 'jpg') {

                                        $quality = (((int)$width > 500 || (int)$height > 500) ? self::WEBP_QUALITY_STANDARD : self::WEBP_QUALITY_BEST);

                                        $command   = 'mogrify -write "' . $tempAbsPath . '" -resize ' . $width . 'x' . $height
                                            . ' -gravity ' . $align . ' -extent ' . $width . 'x' . $height . ' -fill white -quality 100 -strip -colorspace sRGB "'
                                            . $tempAbsPath . '" &&'
                                            . ' jpegoptim "' . $tempAbsPath . '" --all-progressive --strip-all --max=90 --force';
                                        $wpCommand = static::makeWpCommand($tempAbsPath, $width, $height);
                                    }

                                } else {

                                    $command = 'mogrify -write "' . $tempAbsPath . '" -resize "' . $width . 'x' . $height
                                        . '^" -gravity ' . $align . ' -crop ' . $width . 'x' . $height . '+0+0 "' . $tempAbsPath . '"';

                                    // for some reason the tool needs a relative path, not absolute =(
                                    if ($ext === 'png') {
                                        $command   .= ' && optipng -o2 -strip all "' . $tempAbsPath . '"';
                                        $wpCommand = static::makeWpCommand($tempAbsPath, $width, $height);
                                    }

                                    if ($ext === 'jpg') {
                                        $command   .= ' && jpegoptim "' . $tempAbsPath . '" --all-progressive --strip-all --max=90 --force';
                                        $wpCommand = static::makeWpCommand($tempAbsPath, $width, $height);
                                    }

                                }

                                if ($command !== null) {

                                    // Shouldn't hang. I hope.
                                    while (self::shouldExecuteCommands() === false) {
                                        continue;
                                    }

                                    if(is_array($waterMarkParams)) {

                                        $normalizedWaterMarkParams = self::normalizeWaterMarkParams($waterMarkParams);

                                        if(is_string($normalizedWaterMarkParams['image'])) {

                                            shell_exec($command);

                                            $this->watermark(
                                                $tempAbsPath,
                                                $normalizedWaterMarkParams['image'],
                                                $normalizedWaterMarkParams['width'],
                                                $normalizedWaterMarkParams['height'],
                                                $normalizedWaterMarkParams['opacity'],
                                                $normalizedWaterMarkParams['align'],
                                                $normalizedWaterMarkParams['xOffset'],
                                                $normalizedWaterMarkParams['yOffset']
                                            );

                                        } else {
                                            shell_exec($command . ' && ' . $wpCommand);
                                        }

                                    } else {
                                        shell_exec($command . ' && ' . $wpCommand);
                                    }
                                }

                                if (Storage::disk('local')->exists($tempPath) === true) {

                                    if (Storage::disk('image')->exists($cachePath) === false || Storage::disk('image')->delete($cachePath) === true) {
                                        Storage::disk('image')->put($cachePath, Storage::disk('local')->readStream($tempPath));
                                    }

                                    Storage::disk('local')->delete($tempPath);

                                }

                                if (Storage::disk('local')->exists($tempPath . '.webp')) {

                                    if (Storage::disk('image')->exists($cachePath . '.webp') === false || Storage::disk('image')->delete($cachePath . '.webp') === true) {
                                        Storage::disk('image')->put($cachePath . '.webp', Storage::disk('local')->readStream($tempPath . '.webp'));
                                    }

                                    Storage::disk('local')->delete($tempPath . '.webp');

                                }

                                if ($resultFileInfo !== null) {

                                    try {

                                        $resultSize = getimagesize(Storage::disk('image')->url($cachePath));

                                        if (is_array($resultSize) && count($resultSize) === 2) {
                                            $resultWidth  = $resultSize[0];
                                            $resultHeight = $resultSize[1];
                                        }

                                    } catch (\Throwable $e) {

                                        if (strpos($e->getMessage(), 'Read error!') !== false) {
                                            return $this->getThumb($fileName, $method, $width, $height, $version, $align, $regenerate, $waterMarkParams);
                                        }

                                    }

                                }

                            }

                        }

                    }
            }

            $versionStr = (($version === true) ? '?v=' . Str::random(10) : '');

            return [
                'path'   => str_replace(self::ORIGIN, '', $result) . $versionStr,
                'url'    => asset($result) . $versionStr,
                'width'  => $resultWidth,
                'height' => $resultHeight
            ];

        } else {

            return self::resize(self::NO_IMAGE, $width, $height);
        }
    }

    /**
     * @param string $fileName
     * @param int|null $width
     * @param int|null $height
     * @param bool $version
     * @param string $align
     * @param bool $regenerate
     * @param array|null $waterMarkParams
     *
     * @return array|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function fit(string $fileName, ?int $width = null, ?int $height = null, bool $version = false, string $align = 'center', bool $regenerate = false, ?array $waterMarkParams = null) : ?array
    {
        return $this->getThumb($fileName, 'fit', $width, $height, $version, $align, $regenerate, $waterMarkParams);
    }

    /**
     * @param string $fileName
     * @param int|null $width
     * @param int|null $height
     * @param bool $version
     * @param string $align
     * @param bool $regenerate
     * @param array|null $waterMarkParams
     *
     * @return array|null
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function resize(string $fileName, ?int $width = null, ?int $height = null, bool $version = false, string $align = 'center', bool $regenerate = false, ?array $waterMarkParams = null) : ?array
    {
        return $this->getThumb($fileName, 'resize', $width, $height, $version, $align, $regenerate, $waterMarkParams);
    }

    /**
     * @param int $originalWidth
     * @param int $originalHeight
     * @param int|null $width
     * @param int|null $height
     *
     * @return array
     */
    protected function calculateThumbDimensions(int $originalWidth, int $originalHeight, ?int $width = null, ?int $height = null) : array
    {
        if ((int)$width === 0 && (int)$height === 0) {
            $width   = $originalWidth;
            $height  = $originalHeight;
        } else {
            if ((int)$width === 0) {
                $width   = round($originalWidth / ($originalHeight / $height));
            } else {
                if ((int)$height === 0) {
                    $height  = round($originalHeight / ($originalWidth / $width));
                }
            }
        }

        return [
            'width'     => $width,
            'height'    => $height
        ];
    }

    /**
     * @param string $fileName
     * @param string $method
     * @param int|null $originalWidth
     * @param int|null $originalHeight
     * @param int|null $width
     * @param int|null $height
     * @param string $gravity
     *
     * @return array
     */
    public function guessThumbInfo(string $fileName, string $method, ?int $originalWidth = null, ?int $originalHeight = null, ?int $width = null, ?int $height = null, string $gravity = 'center') : array
    {
        $method = ((in_array($method, ['resize', 'fit'], true) === true) ? $method : 'resize');

        [
            'width'     => $resultWidth,
            'height'    => $resultHeight
        ] = $this->calculateThumbDimensions($originalWidth, $originalHeight, $width, $height);

        if ((int)$width === 0 && (int)$height === 0) {
            $postfix = 'origin';
        } elseif ((int)$width === 0) {
            $postfix = 'h' . $height;
        } elseif ((int)$height === 0) {
            $postfix = 'w' . $width;
        } else {
            $postfix = $width . 'x' . $height;
        }

        if (in_array($gravity, $this->gravityList) === true && $gravity !== 'center') {
            $postfix .= '-' . $gravity;
        }

        $ext = basename(str_replace('.', '/', $fileName));
        $ext = ((in_array($ext, $this->mimeTypes, true) === true) ? $ext : 'png');

        return [
            'path'      => substr($fileName, 0, strrpos($fileName, '.')) . '-' . $postfix . (($postfix !== 'origin') ? '_' . $method : '') . '.' . $ext,
            'width'     => $resultWidth,
            'height'    => $resultHeight
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    private static function normalizeWaterMarkParams(array $params) : array
    {
        $xPosList       = ['center', 'left', 'right'];
        $yPosList       = ['bottom', 'center', 'top'];

        $waterMarkImage = ((array_key_exists('image', $params) && is_string($params['image']) && $params['image'] !== null) ? $params['image'] : null);

        $xMargin        = ((array_key_exists('marginX', $params) && is_numeric($params['marginX']) && (int)$params['marginX'] > 0) ? (int)$params['marginX'] : 0);
        $yMargin        = ((array_key_exists('marginY', $params) && is_numeric($params['marginY']) && (int)$params['marginY'] > 0) ? (int)$params['marginY'] : 0);
        $xSize          = ((array_key_exists('sizeX', $params) && is_numeric($params['sizeX']) && (int)$params['sizeX'] > 0) ? (int)$params['sizeX'] : 0);
        $ySize          = ((array_key_exists('sizeY', $params) && is_numeric($params['sizeY']) && (int)$params['sizeY'] > 0) ? (int)$params['sizeY'] : 0);
        $opacity        = ((array_key_exists('opacity', $params) && is_numeric($params['opacity']) && (int)$params['opacity'] >= 0) ? (int)$params['opacity'] : 100);
        $posX           = ((array_key_exists('posX', $params) && is_string($params['posX']) && in_array($params['posX'], $xPosList)) ? $params['posX'] : 'center');
        $posY           = ((array_key_exists('posY', $params) && is_string($params['posY']) && in_array($params['posY'], $yPosList)) ? $params['posY'] : 'center');

        $positionList   = [
            'center' . 'top'    => 'north',
            'left'   . 'top'    => 'northwest',
            'right'  . 'top'    => 'northeast',
            'left'   . 'center' => 'west',
            'right'  . 'center' => 'east',
            'center' . 'bottom' => 'south',
            'right'  . 'bottom' => 'southeast',
            'left'   . 'bottom' => 'southwest',
            'center' . 'center' => 'center'
        ];

        $wmAlign = ((array_key_exists($posX . $posY, $positionList)) ? $positionList[$posX . $posY] : 'center');

        $xMargin = ((!in_array($wmAlign, ['center', 'north', 'northeast', 'south', 'southeast', 'east'])) ? $xMargin : 0);
        $yMargin = ((!in_array($wmAlign, ['center', 'east', 'west'])) ? $yMargin : 0);

        return [
            'align'     => $wmAlign,
            'image'     => $waterMarkImage,
            'opacity'   => $opacity,
            'xOffset'   => $xMargin,
            'yOffset'   => $yMargin,
            'width'     => $xSize,
            'height'    => $ySize
        ];
    }

    /**
     * @param string $imagePath
     * @param string $waterMarkPath
     * @param int|null $width
     * @param int|null $height
     * @param int $opacity
     * @param string $align
     * @param int $xOffset
     * @param int $yOffset
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function watermark(string $imagePath, string $waterMarkPath, ?int $width, ?int $height, int $opacity = 0, string $align = 'center', int $xOffset = 0, int $yOffset = 0) : string
    {
        $waterMarkPath  = ((strpos($waterMarkPath, '/') === 0) ? $waterMarkPath : Storage::disk('image')->path($waterMarkPath));
        $imagePath      = ((strpos($imagePath, '/') === 0) ? $imagePath : Storage::disk('image')->path($imagePath));

        if(Storage::disk('image')->exists($imagePath) && Storage::disk('image')->exists($waterMarkPath)) {

            $tempWmPath = 'tmp/' . str_replace('/', '_', $waterMarkPath);

            if (
                (
                    Storage::disk('local')->exists($tempWmPath) === false ||
                    Storage::disk('local')->delete($tempWmPath) === true
                )
                &&
                Storage::disk('local')->put($tempWmPath, stream_get_contents(Storage::disk('image')->readStream($waterMarkPath))) === true
            ) {

                $isTempImage = true;

                $tempImagePath = 'tmp/' . str_replace('/', '_', $imagePath);

                if (Storage::disk('local')->exists($tempImagePath) === false && Storage::disk('local')->put($tempImagePath, stream_get_contents(Storage::disk('image')->readStream($imagePath))) === true) {
                    $isTempImage = false;
                }

                if (($width === null || $width <= 0) || ($height === null || $height <= 0)) {
                    $imageSizes = getimagesize(Storage::disk('local')->path($tempImagePath));

                    $width  = ((is_int($width) && $width > 0 && $width <= $imageSizes[0]) ? $width : $imageSizes[0]);
                    $height = ((is_int($height) && $height > 0 && $height <= $imageSizes[1]) ? $height : $imageSizes[1]);
                }

                $opacity        = (($opacity >= 0 && $opacity <= 100) ? $opacity : 0);

                $positionList   = [
                    'north',
                    'northwest',
                    'northeast',
                    'west',
                    'east',
                    'south',
                    'southeast',
                    'southwest',
                    'center'
                ];

                $align = ((in_array($align, $positionList, true)) ? $align : 'center');

                // Shouldn't hang, I hope. We need to wait until all of possibly conflicting operations are complete.
                while (self::shouldExecuteCommands() === false) {
                    continue;
                }

                shell_exec(static::makeWaterMarkCommand($imagePath, $tempImagePath, $width, $height, $xOffset, $yOffset, $align, $opacity) . ' && ' . static::makeWpCommand($imagePath, $width, $height));

                Storage::disk('local')->delete($tempWmPath);

                if ($isTempImage === false) {
                    Storage::disk('local')->delete($tempImagePath);
                }
            }
        }

        return $imagePath;
    }

    /**
     * @param string $imagePath
     * @param int $width
     * @param int $height
     *
     * @return string|null
     */
    protected static function makeWpCommand(string $imagePath, int $width, int $height) : ?string
    {
        $command = null;

        $ext = basename(str_replace('.', '/', $imagePath));

        if ($ext === 'png') {
            $command = 'cwebp -mt "' . $imagePath . '" -o "' . $imagePath . '.webp"';
        }

        if ($ext === 'jpg') {

            $quality = (((int)$width > 500 || (int)$height > 500) ? self::WEBP_QUALITY_STANDARD : self::WEBP_QUALITY_BEST);

            $command = 'cwebp -q ' . $quality . ' -mt "' . $imagePath . '" -o "' . $imagePath . '.webp"';
        }

        return $command;
    }

    /**
     * @param string $imagePath
     * @param string $waterMarkPath
     * @param int $wmWidth
     * @param int $wmHeight
     * @param int $xOffset
     * @param int $yOffset
     * @param string $align
     * @param int $opacity
     *
     * @return string
     */
    protected static function makeWaterMarkCommand(string $imagePath, string $waterMarkPath, int $wmWidth, int $wmHeight, int $xOffset = 0, int $yOffset = 0, string $align = 'center', int $opacity = 0) : string
    {
        return 'composite -compose atop -geometry ' . $wmWidth . 'x' . $wmHeight . '+' . $xOffset . '+' . $yOffset . ' -gravity ' . $align
            . ' -background none -dissolve ' . $opacity . '% "'
            . $waterMarkPath . '" "' . $imagePath . '" "' . $imagePath . '"';
    }
}