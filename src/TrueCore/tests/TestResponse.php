<?php

namespace TrueCore\Tests;

use Illuminate\Testing\{
    Assert as PHPUnit,
    TestResponse as BaseTestResponse
};

/**
 * @mixin \Illuminate\Http\Response
 */
class TestResponse extends BaseTestResponse
{
    private $fieldTypes = [
        'array', 'boolean', 'numeric', 'integer', 'string', 'nullable',
    ];

    /**
     * Assert that the response has a given JSON structure, additionally check field types.
     *
     * @param array|null $structure
     * @param array|null $responseData
     * @param array $logPathSegments
     *
     * @return $this
     */
    public function assertJsonStructure(array $structure = null, $responseData = null, array $logPathSegments = [])
    {
        if (is_null($structure)) {
            return $this->assertExactJson($this->json());
        }

        if (is_null($responseData)) {
            $responseData = $this->decodeResponseJson();
        }

        foreach ($structure as $key => $value) {

            if (is_array($value) && $key === '*') {

                $logPathSegments[] = '*';

                PHPUnit::assertIsArray($responseData, 'Expected array type but received ' . gettype($responseData) . '. Path: ' . implode('.', $logPathSegments));

                foreach ($responseData as $responseDataItem) {
                    $this->assertJsonStructure($structure['*'], $responseDataItem, $logPathSegments);
                }

            } else if (is_array($value)) {

                if (array_key_exists('field', $value) && is_string($value['field']) && $value['field'] !== '') {
                    $logPathSegments[] = $value['field'];
                    PHPUnit::assertArrayHasKey($value['field'], $responseData, 'Expected has key ' . $value['field'] . '. Path: ' . implode('.', $logPathSegments));
                }

                if (array_key_exists('type', $value) && (is_string($value['type']) || is_array($value['type']))) {

                    $allowedTypes = (is_string($value['type']) ? explode('|', $value['type']) : $value['type']);
                    $allowedTypes = array_filter($allowedTypes, function ($v) {
                        return is_string($v) && in_array($v, $this->fieldTypes);
                    });
                    sort($allowedTypes);

                    $expected = null;

                    if (in_array('array', $allowedTypes)) {

                        $expected = (in_array('nullable', $allowedTypes)
                            ? (is_array($responseData[$value['field']]) || is_null($responseData[$value['field']]))
                            : (is_array($responseData[$value['field']])));

                    } else if (in_array('boolean', $allowedTypes)) {

                        $expected = (in_array('nullable', $allowedTypes)
                            ? (is_bool($responseData[$value['field']]) || is_null($responseData[$value['field']]))
                            : (is_bool($responseData[$value['field']])));

                    } else if (in_array('integer', $allowedTypes)) {

                        if (in_array('string', $allowedTypes)) {
                            $expected = (in_array('nullable', $allowedTypes)
                                ? (is_string($responseData[$value['field']]) || is_integer($responseData[$value['field']]) || is_null($responseData[$value['field']]))
                                : (is_string($responseData[$value['field']]) || is_integer($responseData[$value['field']])));
                        } else {
                            $expected = (in_array('nullable', $allowedTypes)
                                ? (is_integer($responseData[$value['field']]) || is_null($responseData[$value['field']]))
                                : (is_integer($responseData[$value['field']])));
                        }

                    } else if (in_array('numeric', $allowedTypes)) {

                        if (in_array('string', $allowedTypes)) {
                            $expected = (in_array('nullable', $allowedTypes)
                                ? (is_string($responseData[$value['field']]) || is_numeric($responseData[$value['field']]) || is_null($responseData[$value['field']]))
                                : (is_string($responseData[$value['field']]) || is_numeric($responseData[$value['field']])));
                        } else {
                            $expected = (in_array('nullable', $allowedTypes)
                                ? (is_numeric($responseData[$value['field']]) || is_null($responseData[$value['field']]))
                                : (is_numeric($responseData[$value['field']])));
                        }

                    } else if (in_array('string', $allowedTypes)) {
                        $expected = (in_array('nullable', $allowedTypes)
                            ? (is_string($responseData[$value['field']]) || is_null($responseData[$value['field']]))
                            : (is_string($responseData[$value['field']])));
                    }

                    if ($expected !== null) {
                        PHPUnit::assertTrue($expected, 'Expected ' . implode('/', $allowedTypes) . ' type of ' . $value['field'] . ' field but received ' . gettype($responseData[$value['field']]) . '. Path: ' . implode('.', $logPathSegments));
                    }
                }

                if (array_key_exists('structure', $value) && is_array($value['structure'])) {

                    if ((array_key_exists('type', $value) && is_string($value['type']) && strpos($value['type'], 'nullable') !== false && is_null($responseData[$value['field']])) === false) {
                        $this->assertJsonStructure($value['structure'], $responseData[$value['field']], $logPathSegments);
                    }
                }

            } else {
                $logPathSegments[] = $value;

                PHPUnit::assertArrayHasKey($value, $responseData, 'Expected has key ' . $value . '. Path: ' . implode(' . ', $logPathSegments));
            }
        }

        return $this;
    }
}
