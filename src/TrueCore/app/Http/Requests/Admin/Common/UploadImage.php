<?php

namespace TrueCore\App\Http\Requests\Admin\Common;

use Illuminate\Foundation\Http\FormRequest;

class UploadImage extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'image' => 'required|mimetypes:image/jpg,image/jpeg,image/png,image/gif,image/svg,image/svg+xml,image/webp',
        ];
    }
}
