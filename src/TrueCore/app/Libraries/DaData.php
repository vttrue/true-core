<?php

namespace TrueCore\App\Libraries;

class DaData
{
    protected array $suggestionTypes = [
        'country',
        'region',
        'area',
        'city',
        'settlement',
        'street',
        'house',
    ];

    /**
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return 'https://suggestions.dadata.ru/suggestions/api/4_1/rs';
    }

    /**
     * @param string $queryStr
     * @param string $fromBound
     * @param string $toBound
     * @param array $fields
     *
     * @return array
     */
    public function suggest(string $queryStr, string $fromBound = 'street', string $toBound = 'house', array $fields = []): array
    {
        if (!in_array($fromBound, $this->suggestionTypes) || !in_array($toBound, $this->suggestionTypes)) {
            throw new \InvalidArgumentException('Invalid fromBound or toBound');
        }

        $url = $this->getBaseUrl() . '/suggest/address';

        $query = [
            'query'      => $queryStr,
            'from_bound' => [
                'value' => $fromBound,
            ],
            'to_bound'   => [
                'value' => $toBound,
            ],
        ];

        $requestBody = json_encode($query);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . config('app.daDataApiToken'),
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        try {
            $result = json_decode(curl_exec($ch), true);
        } catch (\Throwable $e) {
            $result = null;
        }

        if (is_array($result) && array_key_exists('suggestions', $result) && is_array($result['suggestions'])) {

            if (count($fields) > 0) {

                foreach ($result['suggestions'] AS &$suggestion) {

                    if (array_key_exists('data', $suggestion) && is_array($suggestion['data']) && array_key_exists('kladr_id', $suggestion['data']) && is_string($suggestion['data']['kladr_id'])) {

                        $ch = curl_init('https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/delivery');
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                            'query' => $suggestion['data']['kladr_id'],
                        ]));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Authorization: Token ' . config('app.daDataApiToken'),
                            'Content-Type: application/json',
                            'Accept: application/json',
                        ]);

                        $cDekResult = json_decode(curl_exec($ch), true);

                        if (is_array($cDekResult) && array_key_exists('suggestions', $cDekResult) && is_array($cDekResult['suggestions']) && count($cDekResult['suggestions']) > 0) {
                            $cDekResult = reset($cDekResult['suggestions']);
                        }

                        if (is_array($cDekResult) && array_key_exists('data', $cDekResult) && is_array($cDekResult['data']) && array_key_exists('cdek_id', $cDekResult['data'])) {
                            $suggestion = array_merge_recursive($suggestion, $cDekResult);
                        }
                    }
                }
            }

            return array_map(fn($v) => (array_merge(
                ['value' => $v['value']],
                array_filter($v['data'], fn($k) => (in_array($k, $fields)), ARRAY_FILTER_USE_KEY)
            )), $result['suggestions']);
        }

        return ['error' => true];
    }

    /**
     *
     * Получение массива геолокации
     *
     * @param string $ip
     *
     * @return array|null
     */
    public static function getCityByIp(string $ip): ?string
    {
        $status = curl_init("https://dadata.ru/api/v2/status/CLEAN");

        curl_setopt($status, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($status, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($status, CURLOPT_HEADER, true);
        curl_setopt($status, CURLOPT_HTTPHEADER, ['Authorization: Token ' . config('app.daDataApiToken')]);

        if (curl_exec($status)) {

            $statusArray = curl_getinfo($status);

            curl_close($status);

            if (array_key_exists('http_code', $statusArray) && is_numeric($statusArray['http_code']) && (int)$statusArray['http_code'] === 200) {

                $geo = curl_init('https://suggestions.dadata.ru/suggestions/api/4_1/rs/detectAddressByIp?ip=' . $ip);

                curl_setopt($geo, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($geo, CURLOPT_BINARYTRANSFER, true);
                curl_setopt($geo, CURLOPT_HTTPHEADER, ['Accept: application/json']);
                curl_setopt($geo, CURLOPT_HTTPHEADER, ['Authorization: Token ' . config('app.daDataApiToken')]);

                $geoJson = curl_exec($geo);

                try {
                    $geoArray = json_decode($geoJson, true);
                } catch (\Throwable $e) {
                    $geoArray = [];
                }

                if (array_key_exists('location', $geoArray) && is_array($geoArray['location']) && array_key_exists('data', $geoArray['location']) && is_array($geoArray['location']['data'])
                    && array_key_exists('city', $geoArray['location']['data']) && is_string($geoArray['location']['data']['city'])
                ) {
                    return trim($geoArray['location']['data']['city']);
                }
            }
        }

        return null;
    }
}
