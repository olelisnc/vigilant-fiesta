<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class ApiController extends Controller
{
    public function upload(Request $request)
    {
        Storage::put('file.txt', $request->post('data'));

        return new JsonResponse([
            'status' => 0
        ]);
    }
}
