<?php

namespace TrueCore\App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;

class StoreUser extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'role'              => 'required|array',
            'role.id'           => 'required|integer|exists:roles,id',
            'name'              => 'required|max:255',
            'phone'             => 'required|max:32|unique:users',
            'email'             => 'required|email|max:64|unique:users',
            'password'          => 'required|string|min:6|max:255',
            'confirmPassword'   => 'required_with:password|nullable|string|min:6|max:255|same:password',
        ];
    }
}
