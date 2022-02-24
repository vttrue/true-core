<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 19.12.2018
 * Time: 12:51
 */

namespace TrueCore\App\Http\Controllers\Admin\Traits;

trait Sort
{
    private array $_sortParams = [];

    /**
     * @return array
     */
    protected function getSortRequest(): array
    {
        $sortParams = $this->getSortParam();
        $orderParams = $this->getSortDirection();

        $sortParamCount = count($sortParams);
        $orderParamCount = count($orderParams);

        if ($sortParamCount > $orderParamCount) {
            $orderParams = array_pad($orderParams, $sortParamCount, 'ASC');
        } else if ($sortParamCount < $orderParamCount) {
            $orderParams = array_slice($orderParams, 0, $sortParamCount);
        }

        $this->_sortParams = array_combine($sortParams, $orderParams);

        return $this->_sortParams;
    }

    /**
     * @return bool
     */
    protected function hasSortRequest(): bool
    {
        return ($this->getSortParam() !== '');
    }

    /**
     * @return array
     */
    private function getSortParam(): array
    {
        $sortParam = request()->input('sort', []);
        $sortParam = ((is_array($sortParam)) ? $sortParam : []);
        $sortParam = array_filter($sortParam, function ($v) {
            return is_string($v);
        });

        if ( count($sortParam) > 0 ) {
            return $sortParam;
        } else {
            return ((property_exists($this, 'defaultSortParam') && is_array($this->defaultSortParam)) ? array_filter(array_keys($this->defaultSortParam), static fn($k) => (is_string($k))) : []);
        }
    }

    /**
     * @return array
     */
    private function getSortDirection(): array
    {
        $sortDirection = request()->input('order', []);
        $sortDirection = ((is_array($sortDirection)) ? $sortDirection :
            (((is_string($sortDirection) && in_array(strtoupper($sortDirection), ['ASC', 'DESC'])))
                ? (array)$sortDirection : ['ASC']));
        $sortDirection = array_filter($sortDirection, function ($v) {
            return (is_string($v) && in_array(strtoupper($v), ['ASC', 'DESC']));
        });

        if ( count($sortDirection) > 0 ) {
            return $sortDirection;
        } else {
            return ((property_exists($this, 'defaultSortParam') && is_array($this->defaultSortParam))
                ? array_filter(array_values($this->defaultSortParam), static fn($k) => (is_string($k) && in_array(strtoupper($k), ['ASC', 'DESC'])))
                : ['ASC']);
        }
    }
}
