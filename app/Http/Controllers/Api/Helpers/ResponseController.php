<?php

namespace App\Http\Controllers\Api\Helpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResponseController extends Controller
{
    public static function json($status, $message = "", $data = "", $id = "", $code): JsonResponse
    {
        return response()->json(["status" => $status, "message" => $message, "data" => $data, "id" => $id], $code);
    }
}
