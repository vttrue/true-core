<?php
/**
 * Created by PhpStorm.
 * User: Deprecator
 * Date: 19.12.2018
 * Time: 12:51
 */

namespace TrueCore\App\Http\Controllers\Admin\Traits;

trait Search
{
    private array $_searchParams = [];

    /**
     * @return array
     */
    protected function getSearchRequest(): array
    {
        if (count($this->_searchParams) === 0) {
            $this->_searchParams = [
                'search' => $this->getSearchParam(),
                'text'   => $this->getSearchText(),
            ];
        }

        return $this->_searchParams;
    }

    /**
     * @return bool
     */
    protected function hasSearchRequest(): bool
    {
        return ($this->getSearchParam() !== '' && $this->getSearchText() !== '');
    }

    /**
     * @return string
     */
    private function getSearchParam(): string
    {
        $searchParam = request()->input('search', '');
        return ((is_string($searchParam)) ? $searchParam : '');
    }

    /**
     * @return string
     */
    private function getSearchText(): string
    {
        $searchText = request()->input('text', '');
        // using urldecode just in case a controversial character such as * was passed encoded; PHP doesn't decode it by default.
        return ((is_string($searchText)) ? urldecode($searchText) : '');
    }
}
