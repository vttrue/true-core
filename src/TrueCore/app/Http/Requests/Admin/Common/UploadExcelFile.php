<?php

namespace TrueCore\App\Http\Requests\Admin\Common;

use Illuminate\Foundation\Http\FormRequest;

class UploadExcelFile extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $mimetypes = [
            'application/vnd.ms-office',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return [
            'file' => 'required|file|mimetypes:' . implode(',', $mimetypes)
        ];
    }
}
