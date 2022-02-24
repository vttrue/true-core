<?php

namespace TrueCore\App\Http\Requests\Admin\Common;

use Illuminate\Foundation\Http\FormRequest;

class Login extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email'    => 'required|email',
            'password' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'email.required'    => 'Обязательное поле',
            'email.email'       => 'Некорректный формат E-mail',
            'password.required' => 'Обязательное поле',
        ];
    }
}
