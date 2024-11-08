<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
    public function success($data = [], $code = 200)
    {
        return response()->json([
            'code' => $code,
            'data' => $data
        ], $code);
    }
}
