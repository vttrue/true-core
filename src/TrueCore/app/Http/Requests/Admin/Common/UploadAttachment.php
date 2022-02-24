<?php

namespace TrueCore\App\Http\Requests\Admin\Common;

use Illuminate\Foundation\Http\FormRequest;

class UploadAttachment extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $mimeTypes = [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/pdf',
            'image/png',
            'image/jpg',
            'image/jpeg',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/rtf'
        ];

        return [
            'file' => 'required|mimetypes:' . implode(',', $mimeTypes),
        ];
    }
}
