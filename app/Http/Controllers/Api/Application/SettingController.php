<?php

namespace App\Http\Controllers\Api\Application;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    private $setting;
    public function index()
    {
    }

    public function access(): JsonResponse
    {
        $user  = new AuthController();
        $me = $user->me()->getData();
        $query = DB::table('precise.privilege')
            ->where('user_id', $me->data->user_id)
            ->select(
                'menu_id',
                'can_read',
                'can_create',
                'can_update',
                'can_delete',
                'can_print',
                'can_double_print',
                'can_approve'
            );

        $this->setting = DB::table('precise.menu')
            ->where('menu.is_active', 1)
            ->select(
                'menu.menu_id',
                'menu.menu_name',
                DB::raw('ifnull(can_read, 0) can_read,
                ifnull(can_create, 0) can_create,
                ifnull(can_update, 0) can_update,
                ifnull(can_delete, 0) can_delete,
                ifnull(can_print, 0) can_print,
                ifnull(can_double_print, 0) can_double_print,
                ifnull(can_approve, 0) can_approve')
            )
            ->mergeBindings($query)
            ->leftJoin(DB::raw("({$query->toSql()})GivenAccess"), 'menu.menu_id', '=', 'GivenAccess.menu_id')
            ->get();

        return response()->json(["status" => "ok", "data" => $this->setting], 200);
    }

    public function logging(): JsonResponse
    {
        $this->setting = DB::table('precise.system_variable')
            ->select(
                'variable_name',
                DB::raw("if(`value` = '1', true, false) `value`")
            )->whereIn('variable_name', [
                'desktop_enable_logging_menu',
                'desktop_enable_logging_login',
                'desktop_enable_logging_menu_action',
                'desktop_enable_logging_loading_time'
            ])->get();
        return response()->json(["status" => "ok", "data" => $this->setting], 200);
    }


    public function version(): JsonResponse
    {
        $this->setting = DB::table('precise.precise_release')
            ->select(
                'release_id',
                'release_date',
                'app_version',
                'app_major',
                'app_minor',
                'app_revision',
                'release_note',
                'release_link_local',
                'release_link_online',
                'file_name'
            )
            ->orderBy('app_major', 'desc')
            ->orderBy('app_minor', 'desc')
            ->orderBy('app_revision', 'desc')
            ->first();
        return response()->json($this->setting, 200);
    }

    public function warehouse(): JsonResponse
    {
        $user  = new AuthController();
        $me = $user->me()->getData();
        $this->setting = DB::table('precise.privilege_warehouse as a')
            ->select(
                'a.warehouse_id',
                'b.warehouse_code',
                'b.warehouse_name',
                'a.privilege_type'
            )
            ->leftJoin('precise.warehouse as b', 'a.warehouse_id', '=', 'b.warehouse_id')
            ->where('a.user_id', $me->data->user_id)
            ->get();
        return response()->json(["status" => "ok", "data" => $this->setting], 200);
    }

    public function user()
    {
        $user  = new AuthController();
        $me = $user->me()->getData();
        $this->setting = DB::table('precise.users as u')
            ->where('u.user_id', $me->data->user_id)
            ->select(
                'user_id',
                'employee_name',
                'email_internal',
                'email_external',
                'device_id',
                'main_phone_number',
                'telegram_id',
                'u.is_active'
            )->leftJoin('employee as e', 'u.user_id', '=', 'e.employee_nik')
            ->get();
        return response()->json(["status" => "ok", "data" => $this->setting], 200);
    }

    public function workcenter(): JsonResponse
    {
        $user  = new AuthController();
        $me = $user->me()->getData();

        $this->setting = DB::table('precise.privilege_workcenter as a')
            ->select(
                'a.workcenter_id',
                'b.workcenter_code',
                'b.workcenter_name'
            )
            ->leftJoin('workcenter as b', 'a.workcenter_id', '=', 'b.workcenter_id')
            ->where('a.user_id', $me->data->user_id)
            ->orderBy('workcenter_code')
            ->get();

        return response()->json(["status" => "ok", "data" => $this->setting], 200);
    }
}
