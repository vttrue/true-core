<?php

namespace TrueCore\App\Services\Traits;

use \TrueCore\App\Libraries\Config;
use \TrueCore\App\Services\Structure;
use Illuminate\Support\{
    Carbon,
    Facades\Storage
};

trait Emailable
{
    /**
     * @return array
     * @throws \Exception
     */
    protected function getBaseEmailData(): array
    {
        $data = [];

        if(method_exists($this, 'mapDetail')) {
            $data = $this->mapDetail();
            $data = (($data instanceof Structure) ? $data->toArray() : []);
        }

        $data['site']['adminFrontUrl']  = config('app.adminFrontUrl');
        $data['site']['url']            = str_replace('https://', '', config('app.frontUrl'));
        $data['site']['companyName']    = Config::getInstance()->get('companyName', 'contacts');

        $phoneList = Config::getInstance()->get('phones', 'contacts', []);
        $phoneList = ((is_array($phoneList)) ? array_filter($phoneList, static fn($v) => (!in_array($v['phone'], ['', null], true))) : []);
        $phoneList = array_map(static fn($v): array => ['phone' => $v['phone'], 'description' => $v['description'], 'url' => $v['url']], $phoneList);

        $data['site']['phoneList']      = $phoneList;
        $data['site']['email']          = Config::getInstance()->get('email', 'contacts');

        $logo = '';
        $logoAbsolutePath = Config::getInstance()->get('logo', 'email');
        $logoPath = ((is_string($logoAbsolutePath) && $logoAbsolutePath !== '') ? Storage::disk('image')->path($logoAbsolutePath) : null);
        if (!in_array($logoPath, ['', null])) {
            $logo = Storage::disk('image')->url($logoAbsolutePath);
        }
        $data['site']['logo'] = $logo;

        $data['createdAt'] = ((array_key_exists('createdAt', $data) && is_string($data['createdAt']) && $data['createdAt'] !== '')
            ? (new Carbon($data['createdAt']))->format('d.m.Y H:i') : null);
        $data['updatedAt'] =  ((array_key_exists('updatedAt', $data) && is_string($data['updatedAt']) && $data['updatedAt'] !== '')
            ? (new Carbon($data['updatedAt']))->format('d.m.Y H:i') : null);

        return $data;
    }
}
