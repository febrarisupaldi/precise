<?php

namespace App\Http\Controllers\Api\Application;

use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    private $application;
    public function index()
    {
    }

    public function autoIncrement(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'db'    => 'required',
            'table' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->application = DB::table('information_schema.TABLES')
            ->select('AUTO_INCREMENT')
            ->where([
                'TABLE_SCHEMA'  => $request->db,
                'TABLE_NAME'    => $request->table
            ])
            ->get();
        if (count($this->application) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->application, code: 200);
    }

    public function serverTime(): JsonResponse
    {
        $this->application = DB::select("select sysdate() as 'ServerTime'");
        return ResponseController::json(status: "ok", data: $this->application, code: 200);
    }

    public function globalVariabel(): JsonResponse
    {
        $this->application = DB::table('precise.system_variable')
            ->select(
                'variable_name',
                DB::raw('value')
            )
            ->get();
        if (count($this->application) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->application, code: 200);
    }

    public function error(): JsonResponse
    {
        $this->application = DB::table('precise.error_code_hd')
            ->select(
                'error_code',
                'help_link_local',
                'help_link_online'
            )
            ->get();
        if (count($this->application) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->application, code: 200);
    }
}
