<?php

use Illuminate\Support\Str;

if (!function_exists('_truncate')) {
    /**
     * @param $string
     * @param int $length
     * @param string $etc
     * @param bool $break_words
     * @param bool $middle
     * @param bool $stripTags
     * @return null|string|string[]
     */
    function _truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false, $stripTags = true)
    {
        if ($length == 0)
            return '';

        if ($stripTags) {
            $string = strip_tags($string);
        }

        if (mb_strlen($string, 'UTF-8') > $length) {
            $length -= mb_strlen($etc, 'UTF-8');
            if (!$break_words && !$middle) {
                $string = preg_replace('/\s+?(\S+)?$/u', '',
                    mb_substr($string, 0, $length + 1, 'UTF-8'));
            }
            if (!$middle) {
                return mb_substr($string, 0, $length, 'UTF-8') . $etc;
            } else {
                return mb_substr($string, 0, $length / 2, 'UTF-8') . $etc . mb_substr($string, -$length / 2, 'UTF-8');
            }
        } else {
            return $string;
        }
    }
}

if (!function_exists('_camelCase')) {
    /**
     * Преобразует из snake_case в camelCase
     * @param $word
     * @return string
     */
    function _camelCase($word): string
    {
        return $word = preg_replace_callback(
            "/(^|_)([a-z])/",
            function ($m) {
                return strtoupper("$m[2]");
            },
            $word
        );
    }
}

if (!function_exists('_camelCaseArrayKeys')) {
    /**
     * Преобразует ключи массива в строки с camelCase
     * @param $array
     * @return array
     */
    function _camelCaseArrayKeys($array): array
    {
        return collect($array)->mapWithKeys(function ($item, $key) {

            return [Str::camel($key) => $item];
        })->toArray();
    }
}

if (!function_exists('_snakeCaseArrayKeys')) {
    /**
     * Преобразует ключи массива в строки с snake_case
     * @param $array
     * @return array
     */
    function _snakeCaseArrayKeys($array): array
    {
        return collect($array)->mapWithKeys(function ($item, $key) {

            return [Str::snake($key) => $item];
        })->toArray();
    }
}

if (!function_exists('_getCurrentUser')) {
    /**
     * @param null|string $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable|\App\Models\Sale\Customer|null
     */
    function _getCurrentUser(?string $guard = null)
    {
        //dd($guard ? auth()->guard($guard)->user() : auth()->user());
        return $guard ? auth()->guard($guard)->user() : auth()->user();
    }
}

if (!function_exists('_code')) {
    /**
     * Нормализует уникальные коды у сущностей
     * @param string $code
     * @return string
     */
    function _code(string $code): string
    {
        return strtoupper(trim((string)$code));
    }
}

if (!function_exists('_currency')) {
    /**
     * @param $value
     * @return string
     */
    function _currency($value): string
    {
        if (!$value) {
            $value = 0;
        }

        return number_format(
            round($value, _defaultCurrency()->decimals),
            _defaultCurrency()->decimals,
            _defaultCurrency()->decimal_separator,
            ' ');
    }
}


if (!function_exists('_currencyHTML')) {
    /**
     * @param $value
     * @param string $template
     * @return string
     */
    function _currencyHTML($value, $template = '<span>%s</span>'): string
    {
        if (!$value) {
            $value = 0;
        }

        $priceText = str_replace('.', _defaultCurrency()->decimal_separator, _currency($value));

        if (_defaultCurrency()->is_symbol_before) {
            return _defaultCurrency()->symbol . sprintf($template, $priceText);
        } else {
            return sprintf($template, $priceText) . _defaultCurrency()->symbol;
        }
    }
}

if (!function_exists('_normalizeText')) {
    /**
     * Преобразует первый символ строки в верхний регистр, остальные в нижний (для кириллицы)
     * @param string $text
     * @return string
     */
    function _normalizeText(string $text): string
    {
        return mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8')
            . mb_substr(mb_convert_case($text, MB_CASE_LOWER, 'UTF-8'), 1, mb_strlen($text), 'UTF-8');
    }
}

if (!function_exists('_formatPhone')) {
    /**
     * @param $phone
     * @param bool $check
     * @return string|bool
     */
    function _formatPhone($phone, $check = false)
    {
        $phone = preg_replace('~[^0-9]~', '', trim($phone));

        $error = false;

        if (mb_strlen($phone, 'UTF-8') == 10) {
            if ($phone[0] == '9') {
                $phone = '7' . $phone;
            } else {
                $phone = '7' . $phone;
            }
        } else if (mb_strlen($phone, 'UTF-8') == 9) {
            $phone = '79' . $phone;
            $error = true;
        } else if (mb_strlen($phone, 'UTF-8') > 11) {
            $error = true;
        } else if (mb_strlen($phone, 'UTF-8') < 9) {
            $error = true;
        }

        return ($check && $error) ? false : $phone;
    }
}

//if (!function_exists('_currentCity')) {
//    /**
//     * @return \App\Models\System\City|null
//     */
//    function _currentCity(): ?\App\Models\System\City
//    {
//        return resolve('currentCity');
//    }
//}
//
//
//if (!function_exists('_defaultCurrency')) {
//    /**
//     * @return \App\Models\Shop\Currency|null
//     */
//    function _defaultCurrency(): ?\App\Models\Shop\Currency
//    {
//        return resolve('defaultCurrency');
//    }
//}
//
//if (!function_exists('_currentCustomer')) {
//    /**
//     * @return \App\Models\Shop\Customer|null
//     */
//    function _currentCustomer(): ?\App\Models\Shop\Customer
//    {
//        return resolve('currentCustomer');
//    }
//}
//
//// Библиотеки
//if (!function_exists('_breadcrumb')) {
//    /**
//     * @return App\Libraries\Breadcrumb
//     */
//    function _breadcrumb(): \App\Libraries\Breadcrumb
//    {
//        return resolve('breadcrumb');
//    }
//}
//
//if (!function_exists('_config')) {
//    /**
//     * @return App\Libraries\Config
//     */
//    function _config(): \App\Libraries\Config
//    {
//        return resolve('setting');
//    }
//}
//
//if (!function_exists('_cart')) {
//    /**
//     * @return \App\Libraries\Cart|null
//     */
//    function _cart(): ?\App\Libraries\Cart
//    {
//        return resolve('cart');
//    }
//}
//
//if (!function_exists('_document')) {
//    /**
//     * @return App\Libraries\Document
//     */
//    function _document(): \App\Libraries\Document
//    {
//        return resolve('document');
//    }
//}
//
//if (!function_exists('_geo')) {
//    /**
//     * @return App\Libraries\GeoLocation
//     */
//    function _geo(): \App\Libraries\GeoLocation
//    {
//        return resolve('geo');
//    }
//}
//
//if (!function_exists('_image')) {
//    /**
//     * @return App\Libraries\Image
//     */
//    function _image(): \App\Libraries\Image
//    {
//        return resolve('_image');
//    }
//}
//
//if (!function_exists('_history')) {
//    /**
//     * @return App\Libraries\History
//     */
//    function _history(): \App\Libraries\History
//    {
//        return resolve('history');
//    }
//}
//
//if (!function_exists('_comparison')) {
//    /**
//     * @return App\Libraries\Comparison
//     */
//    function _comparison(): \App\Libraries\Comparison
//    {
//        return resolve('comparison');
//    }
//}
//
//if (!function_exists('_wishList')) {
//    /**
//     * @return App\Libraries\WishList
//     */
//    function _wishList(): \App\Libraries\WishList
//    {
//        return resolve('wishList');
//    }
//}

if (!function_exists('_fileLog')) {
    /**
     * @param $filename
     * @param $output
     * @param string $delimiter
     */
    function _fileLog($filename, $output, string $delimiter = PHP_EOL)
    {
        if (file_exists(base_path() . '/' . $filename . '.txt')) {
            $log = file_get_contents(base_path() . '/' . $filename . '.txt');
        } else {
            $log = '';
        }

        $log .= $delimiter . print_r($output, true);

        file_put_contents(base_path() . '/' . $filename . '.txt', $log);
    }
}
