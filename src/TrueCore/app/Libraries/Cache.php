<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 25.11.2018
 * Time: 15:32
 */

namespace TrueCore\App\Libraries;

class Cache
{
    private static $_instance = null;

    private $cacheListStorage = null;

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
     * @return Cache
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof Cache)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * @param string|null $driver
     * @return \Illuminate\Cache\Repository
     */
    private static function driver(string $driver = null)
    {
        return \Illuminate\Support\Facades\Cache::driver($driver);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key) : bool
    {
        return self::driver()->has($key);
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return self::driver()->get($key, $default);
    }

    /**
     * @param iterable $values
     * @param null $default
     * @return iterable
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMultiple(iterable $values, $default = null)
    {
        return self::driver()->getMultiple($values, $default);
    }

    /**
     * @TODO: apparently there's a bug with the Laravel Cache Setter. If we somehow set a cache entry with null value
     * It would neither fetch nor set it again until the key is clear.
     *
     * @param string $key
     * @param $value
     * @param null|int|\DateInterval $duration
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function set(string $key, $value, $duration = null): bool
    {
        self::addLogEntry('Added', $key, $value);

        if($this->has($key)) {
            $this->delete($key);
        }

        self::driver()->put($key, $value, $duration);

        return (bool)$this->has($key);
    }

    /**
     * @TODO: buggy. Always returns false because the Facade's setMultiple method is a void method for some reason.
     * Looking for ways to get boolean result, but still no time to look deeper | deprecator @ 2018-12-06
     *
     * @TODO: Had to resort to sort of a wheelchair, have to look around for some other ideas of how to handle it | deprecator @ 2018-12-07
     *
     * @param iterable $values
     * @param null|int|\DateInterval $duration
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setMultiple(iterable $values, $duration = null): bool
    {
        $hasToBeBooleanButNull = self::driver()->setMultiple($values, $duration);

        $keys = array_keys($values);
        $result = [];

        foreach ($keys AS $key) {
            self::addLogEntry('Added multiply', $key, $values[$key]);
            $result[] = $this->has($key);
        }

        return (count($keys) === count(array_filter($result, function ($v) {
                return $v === true;
            })));
    }

    /**
     * @param string $key
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delete(string $key): bool
    {
        self::addLogEntry('Deleted', $key, '');
        return (bool)self::driver()->delete($key);
    }

    /**
     * @param iterable $keys
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteMultiple(iterable $keys): bool
    {
//        foreach($keys AS $key) {
//            self::addLogEntry('Deleted multiply', $key, '');
//        }
        //dd(\Illuminate\Support\Facades\Cache::deleteMultiple($keys), $keys);
        return (bool)self::driver()->deleteMultiple($keys);
    }

    /**
     * @param string $key
     * @param \DateTimeInterface|\DateInterval|float|int $minutes
     * @param \Closure $callback
     * @return mixed
     */
    public function remember(string $key, $minutes, \Closure $callback)
    {
        return self::driver()->remember($key, $minutes, $callback);
    }

    /**
     * @param string $key
     * @param \Closure $callback
     * @return bool
     */
    public function rememberForever(string $key, \Closure $callback) : bool
    {
        self::driver()->rememberForever($key, $callback);

        return $this->has($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        return self::driver()->forget($key);
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @param string $key
     * @param \Closure $callback
     * @param null|int|\DateInterval $duration
     * @return mixed
     */
    public function rememberEntityRecord(string $entityName, $entityID, string $key, \Closure $callback, $duration = null)
    {
        $data = $this->getEntityRecord($entityName, $entityID, $key, null);

        if (!is_null($data)) {
            return $data;
        }

        $this->setEntityRecord($entityName, $entityID, $key, $data = $callback(), $duration);

        return $data;
    }

    /**
     * @param string $entityName
     * @param iterable $entityIdList
     * @param string $key
     * @param \Closure $callback
     * @param null|int|\DateInterval $duration
     * @return array|mixed
     */
    public function rememberEntityGroupRecord(string $entityName, iterable $entityIdList, string $key, \Closure $callback, $duration = null)
    {
        $data = $this->getEntityGroupRecord($entityName, $entityIdList, $key, null);

        if (!is_null($data)) {
            return $data;
        }

        $this->setEntityGroupRecord($entityName, $entityIdList, $key, $data = $callback(), $duration);

        return $data;
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getEntityRecord(string $entityName, $entityID, string $key, $default = null)
    {
        $cacheKey = self::getEntityCacheKey($entityName, $entityID, $key);

        $data = $this->get($cacheKey, $default);

        self::driver($this->cacheListStorage)->has(self::getEntityCacheListKey($entityName, $entityID));

        return $data;
    }

    /**
     * @param string $entityName
     * @param iterable $entityIdList
     * @param string $key
     * @param null $default
     * @return array|mixed
     */
    public function getEntityGroupRecord(string $entityName, iterable $entityIdList, string $key, $default = null)
    {
        if (count($entityIdList) > 0) {

            $idKeyPart = md5(serialize($entityIdList));
            $cacheKey = self::getEntityCacheKey($entityName, null, $idKeyPart . '_' . $key);

            $data = $this->get($cacheKey, $default);

            foreach($entityIdList AS $entityId) {
                self::driver($this->cacheListStorage)->has(self::getEntityCacheListKey($entityName, $entityId));
            }

            return $data;

        }

        return [];
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @param string $key
     * @param $data
     * @param null|int|\DateInterval $duration
     * @return bool
     */
    public function setEntityRecord(string $entityName, $entityID, string $key, $data, $duration = null): bool
    {
        $cacheKey = self::getEntityCacheKey($entityName, $entityID, $key);

        try {
            if ($this->set($cacheKey, $data, $duration)) {

                $this->updateEntityCacheList($entityName, $entityID, [$cacheKey]);

                return true;
            }
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
            return false;
        }

        return false;
    }

    /**
     * @param string $entityName
     * @param array $entityIdList
     * @param string $key
     * @param mixed $data
     * @param null|int|\DateInterval $duration
     * @return bool
     */
    public function setEntityGroupRecord(string $entityName, array $entityIdList, string $key, $data, $duration = null): bool
    {
        try {
            if (count($entityIdList) > 0) {

                $idKeyPart = md5(serialize($entityIdList));
                $cacheKey = self::getEntityCacheKey($entityName, null, $idKeyPart . '_' . $key);

                if ($this->set($cacheKey, $data, $duration)) {

                    $cacheListValues = [];

                    foreach ($entityIdList AS $entityID) {
                        $cacheListValues[self::getEntityCacheListKey($entityName, $entityID)][] = $cacheKey;
                    }

                    $cacheList = self::driver($this->cacheListStorage)->getMultiple(array_keys($cacheListValues));

                    //print_r([$cacheList, $cacheListValues, array_merge_recursive($cacheList, $cacheListValues)]);die;

                    $cacheListNew = array_map(function($v) {
                        return array_unique($v);
                    }, array_merge_recursive($cacheList, $cacheListValues));

                    // We need to set cacheList forever so it won't expire before any of its inhabitants
                    self::driver($this->cacheListStorage)->setMultiple($cacheListNew, 5256000);

                    return true;

                }

            }
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
            return false;
        }

        return false;
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @param string $key
     * @return bool
     */
    public function deleteEntityRecord(string $entityName, $entityID, string $key): bool
    {
        $cacheKey = self::getEntityCacheKey($entityName, $entityID, $key);

        try {
            if (!$this->has($cacheKey) || $this->delete($cacheKey)) {
                return $this->clearEntityCache($entityName, $entityID);
            }
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @return bool
     */
    public function clearEntityCache(string $entityName, $entityID = null): bool
    {
        try {
            $cacheKey = self::getEntityCacheListKey($entityName, $entityID);

            $cacheList = $this->getEntityCacheList($entityName, $entityID);

            if ($this->deleteMultiple($cacheList)) {
                return $this->delete($cacheKey);
            }
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
            return false;
        }

        return false;
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @return array
     */
    protected function getEntityCacheList(string $entityName, $entityID = null): array
    {
        return self::driver($this->cacheListStorage)->get(self::getEntityCacheListKey($entityName, $entityID), []);
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @param mixed $data
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function updateEntityCacheList(string $entityName, $entityID, $data): bool
    {
        $cacheList = $this->getEntityCacheList($entityName, $entityID);
        $cacheList = array_merge($cacheList, $data);

        return $this->setEntityCacheList($entityName, $entityID, $cacheList);
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @param array $data
     * @return bool
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function setEntityCacheList(string $entityName, $entityID, array $data = []): bool
    {
        $cacheKey = self::getEntityCacheListKey($entityName, $entityID);

        if(self::driver($this->cacheListStorage)->has($cacheKey)) {
            self::driver($this->cacheListStorage)->delete($cacheKey);
        }

        self::driver($this->cacheListStorage)->rememberForever($cacheKey, function() use($data) {
            return $data;
        });

        return self::driver($this->cacheListStorage)->has($cacheKey);
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @param string $key
     * @return string
     */
    private static function getEntityCacheKey(string $entityName, $entityID, string $key): string
    {
        return $entityName . ((is_string($entityID) || is_int($entityID)) ? '_' . $entityID : '') . '_' . (($key !== 'cacheList') ? $key : 'cache_list');
    }

    /**
     * @param string $entityName
     * @param int|float|string|null $entityID
     * @return string
     */
    private static function getEntityCacheListKey(string $entityName, $entityID) : string
    {
        return $entityName . ((is_string($entityID) || is_numeric($entityID)) ? '_' . $entityID : '') . '_cacheList';
    }

    /**
     * @param string $type
     * @param $key
     * @param $value
     */
    private static function addLogEntry(string $type, $key, $value) : void
    {
//        $logPath = base_path() . '/cache_library_log.log';
//        $logFileContents = ((is_file($logPath)) ? file_get_contents($logPath) : '');
//        file_put_contents($logPath, $logFileContents . 'Cache entry ' . $key . ' has been ' . $type . ((strpos($key, '_cacheList') !== false) ? '; The value of cacheList is: ' . "\n=========\n" . print_r($value, true) . "\n========\n" : '') . "\n");
    }
}