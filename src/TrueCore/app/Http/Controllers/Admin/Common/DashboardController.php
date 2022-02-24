<?php

namespace TrueCore\App\Http\Controllers\Admin\Common;

use \TrueCore\App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $this->data['message'] = 'Добро пожаловать!';

        return response()->json($this->data);
    }
}
