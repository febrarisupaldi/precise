<?php

namespace App\Http\Controllers\Api\Application;

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
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->application = DB::table('information_schema.TABLES')
                ->select('AUTO_INCREMENT')
                ->where([
                    'TABLE_SCHEMA'  => $request->db,
                    'TABLE_NAME'    => $request->table
                ])
                ->get();
            return response()->json(['status' => 'ok', 'data' => $this->application], 200);
        }
    }

    public function serverTime(): JsonResponse
    {
        $this->application = DB::select("select sysdate() as 'ServerTime'");
        return response()->json(['status' => 'ok', 'data' => $this->application], 200);
    }

    public function globalVariabel(): JsonResponse
    {
        $this->application = DB::table('precise.system_variable')
            ->select(
                'variable_name',
                DB::raw('value')
            )
            ->get();
        return response()->json(['status' => 'ok', 'data' => $this->application], 200);
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
        return response()->json(['status' => 'ok', 'data' => $this->application], 200);
    }
}
