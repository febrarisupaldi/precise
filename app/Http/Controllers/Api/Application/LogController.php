<?php

namespace App\Http\Controllers\Api\Application;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\JsonResponse;

class LogController extends Controller
{
    private $log;
    public function error(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'error_code'    => 'required',
            'log_id'        => 'required|exists:log_user_login,log_id'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->log = DB::table('precise.log_error')
            ->insert([
                'error_code'        => $request->error_code,
                'error_date'        => DB::raw('sysdate()'),
                'log_user_login_id' => $request->log_id,
                'log_note'          => $request->log_note
            ]);

        if ($this->log == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function menu(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'log_id'    => 'required|exists:log_user_login,log_id',
            'menu_id'   => 'required|exists:menu,menu_id'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->log = DB::table('precise.log_menu')
            ->insert([
                'log_user_login_id' => $request->log_id,
                'menu_id'           => $request->menu_id,
                'access_on'         => DB::raw('sysdate()'),
            ]);

        if ($this->log == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function menuAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'log_id'                => 'required|exists:log_user_login,log_id',
            'menu_id'               => 'required|exists:menu,menu_id',
            'menu_action_type_id'   => 'required|exists:menu_action_type,menu_action_type_id'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->log = DB::table('log_menu_action')
            ->insert([
                'log_user_login_id'     => $request->log_id,
                'menu_id'               => $request->menu_id,
                'menu_action_type_id'   => $request->menu_action_type_id,
                'action_on'             => DB::raw("sysdate()")
            ]);
        if ($this->log == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }
}
