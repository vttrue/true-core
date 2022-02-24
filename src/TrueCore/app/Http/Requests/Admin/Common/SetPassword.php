<?php

namespace TrueCore\App\Http\Requests\Admin\Common;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SetPassword
 *
 * @package TrueCore\App\Http\Requests\Admin\Common
 */
class SetPassword extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'password' => 'required|min:6|max:64',
            'confirm'  => 'same:password',
        ];
    }

    public function messages()
    {
        return [
            'password.required' => 'Обязательное поле',
            'password.min'      => 'Минимальная длина - 6 символов',
            'password.max'      => 'Максимальная длина - 64 символа',
            'confirm.same'      => 'Подтверждение не совпадает с паролем',
        ];
    }
}
