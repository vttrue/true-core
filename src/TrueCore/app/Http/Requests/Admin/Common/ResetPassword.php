<?php

namespace TrueCore\App\Http\Requests\Admin\Common;

use Illuminate\Foundation\Http\FormRequest;

class ResetPassword extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email|exists:users',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Обязательное поле',
            'email.email'    => 'Некорректный формат E-mail',
            'email.exists'   => 'Администратора с таким E-mail не существует',
        ];
    }
}
