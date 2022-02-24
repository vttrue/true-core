<?php

namespace TrueCore\App\Libraries;

use \TrueCore\App\Models\System\Setting as mSetting;
use Illuminate\Support\Facades\Cache;

class Config
{
    private static $_instance = null;

    protected $data = [];

    protected function __construct()
    {
        $this->data = Cache::remember('setting', config('cache.lifetime'), fn() => $this->fetchSettings());
    }

    protected function __clone()
    {
        //
    }

    protected function __wakeup()
    {
        //
    }

    /**
     * @return Config
     */
    public static function getInstance()
    {
        if (!(self::$_instance instanceof Config)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * @param null|string $key
     * @param string $group
     * @param mixed $default
     * @return mixed|null
     */
    public function get(?string $key = null, $group = 'main', $default = null)
    {
        if ($key) {
            return $this->data[$group][$key] ?? $default;
        } else {
            return array_key_exists($group, $this->data) ? [$group => $this->data[$group]] : $this->data;
        }
    }

    /**
     * @param string $group
     * @return mixed|null
     */
    public function getGroup(string $group)
    {
        return $this->data[$group] ?? null;
    }

    /**
     * @param $data
     * @param string $group
     * @param bool $saveIfNew - нет необходимости создавать новые записи в БД с настройками, кроме тех, что указаны в сервисе, но на будущее оставил
     */
    public function saveSettings($data, $group = 'main', $saveIfNew = false)
    {
        foreach ($data as $key => $value) {

            if (is_array($value)) {
                $record = [
                    'group' => $group,
                    'key'   => $key,
                    'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                    'json'  => 1,
                ];
            } else {
                $record = [
                    'group' => $group,
                    'key'   => $key,
                    'value' => $value,
                    'json'  => 0,
                ];
            }

            if ($saveIfNew) {
                mSetting::updateOrCreate(
                    ['group' => $group, 'key' => $key],
                    $record);
            } else {
                mSetting
                    ::where('group', '=', $group)->where('key', '=', $key)
                    ->firstOrFail()
                    ->update($record);
            }

            $this->data[$group][$key] = $value;
        }
    }

    /**
     * @return array
     */
    private function fetchSettings(): array
    {
        $data = [];

        foreach (mSetting::all() as $setting) {
            if ( $setting->json === true ) {
                $data[$setting->group][$setting->key] = json_decode($setting->value, true);
            } else {
                $data[$setting->group][$setting->key] = $setting->value ?? null;
            }
        }

        return $data;
    }

    /**
     * @param string $group
     * @param string $key
     * @return bool
     */
    public static function checkFrontAccess(string $group, ?string $key = null): bool
    {
        $frontAllowedSettings = config('settings.front_allowed_settings', []);

        if (count($frontAllowedSettings) > 0) {

            if ($key === null) {
                if (in_array($group, $frontAllowedSettings)) {
                    return true;
                }
            } else {
                if (in_array($group . '.' . $key, $frontAllowedSettings)) {
                    return true;
                }
            }
        }

        return false;
    }

}
