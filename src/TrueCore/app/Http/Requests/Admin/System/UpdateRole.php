<?php

namespace TrueCore\App\Http\Requests\Admin\System;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRole extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'                => 'required|max:255',
            'permissions'         => 'required|array',
            'permissions.*.id'    => 'required|integer|exists:entities,id'
        ];
    }
}
