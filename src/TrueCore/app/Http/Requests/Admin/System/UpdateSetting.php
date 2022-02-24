<?php

namespace TrueCore\App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;

use App\Services\System\Setting as SettingService;

/**
 * Class UpdateSetting
 *
 * @package TrueCore\App\Http\Requests\Admin\System
 */
class UpdateSetting extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $validationCallback = function($groupList) {

            $rules = [];

            foreach ($groupList as $group) {

                $rules[$group] = 'required';

                foreach (SettingService::$structure[$group] as $key => $value) {

                    if ( array_key_exists('rule', $value) ) {

//                        if ( $value['type'] === 'array' ) {
//                            $rules[$group . '.' . $key . '.*'] = $value['rule'];
//                        } else {
                            $rules[$group . '.' . $key] = $value['rule'];
//                        }
                    }
                }
            }

            return $rules;
        };

        return $validationCallback(((request()->group) ? [request()->group] : array_keys(SettingService::$structure)));
    }

    public function messages()
    {
        // @TODO в идеале и это гавно в проход по структуре обернуть
        return [
            'notify.emails.*.email' => 'Указан недействительный E-mail в настройках уведомлений',
        ];
    }
}
