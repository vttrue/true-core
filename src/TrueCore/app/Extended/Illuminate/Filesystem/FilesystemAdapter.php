<?php

namespace TrueCore\App\Extended\Illuminate\Filesystem;

use League\Flysystem\Cached\CachedAdapter;
use Illuminate\Filesystem\FilesystemAdapter as BaseFilesystemAdapter;

/**
 * Class FilesystemAdapter
 *
 * @package TrueCore\App\Extended\Illuminate\Filesystem
 */
class FilesystemAdapter extends BaseFilesystemAdapter
{
    /**
     * Get the URL for the file at the given path.
     *
     * @param  \League\Flysystem\AwsS3v3\AwsS3Adapter  $adapter
     * @param  string  $path
     * @return string
     */
    protected function getAwsUrl($adapter, $path)
    {
        $originalUrl      = parent::getAwsUrl($adapter, $path);

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if (! is_null($url = $this->driver->getConfig()->get('url'))) {

            $urlPrefixRewriteRules = $this->driver->getConfig()->get('urlPrefixRewrite');

            if (is_array($urlPrefixRewriteRules) && count($urlPrefixRewriteRules) > 0) {

                $originalUrlParts = parse_url($originalUrl);

                if (array_key_exists('path', $originalUrlParts) === true) {

                    foreach ($urlPrefixRewriteRules AS $str => $replacement) {

                        $resultPath = ((strpos($originalUrlParts['path'], $str) === 0) ? substr_replace($originalUrlParts['path'], $replacement, 0, strlen($str)) : $originalUrlParts['path']);

                        if ($resultPath !== $originalUrlParts['path']) {
                            return $this->concatPathToUrl($url, $resultPath);
                        }
                    }
                }
            }
        }

        return $originalUrl;
    }
}
