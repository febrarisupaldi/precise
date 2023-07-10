<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Master\HelperController;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class PrivilegeController extends Controller
{
    private $privilege;
    public function index(): JsonResponse
    {
        $this->privilege = DB::table('precise.privilege as a')
            ->select(
                'privilege_id',
                'user_id',
                'c.employee_name',
                'a.menu_id',
                'b.menu_name',
                'b.menu_parent',
                'can_read',
                'can_create',
                'can_update',
                'can_delete',
                'can_print',
                'can_double_print',
                'can_approve'
            )
            ->leftJoin('menu as b', 'a.menu_id', '=', 'b.menu_id')
            ->leftJoin('employee as c', 'a.user_id', '=', 'c.employee_nik')
            ->get();
        if (count($this->privilege) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->privilege, code: 200);
    }

    public function showMenuByUser($id): JsonResponse
    {
        $sql = DB::table('precise.privilege')
            ->where('user_id', $id)
            ->select(
                'privilege_id',
                'menu_id',
                'can_read',
                'can_create',
                'can_update',
                'can_delete',
                'can_print',
                'can_double_print',
                'can_approve'
            );

        $this->privilege = DB::table('precise.menu as a')
            ->mergeBindings($sql)
            ->where('is_active', 1)
            ->select(
                'a.menu_id',
                'menu_name',
                'menu_parent',
                'b.menu_category_name',
                'can_read',
                'can_create',
                'can_update',
                'can_delete',
                'can_print',
                'can_double_print',
                'can_approve'
            )
            ->leftJoin('precise.menu_category as b', 'a.menu_category_id', '=', 'b.menu_category_id')
            ->leftJoin(DB::raw("({$sql->toSql()})p"), 'a.menu_id', '=', 'p.menu_id')
            ->get();

        if (count($this->privilege) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->privilege, code: 200);
    }

    public function showUserByMenu($id): JsonResponse
    {
        $this->privilege = DB::table('privilege')
            ->where('menu_id', $id)->count();

        if ($this->privilege == 0)
            $this->privilege = DB::select(
                "select null as 'User ID', null as 'Can READ', null as 'Can CREATE', null as 'Can UPDATE', null as 'Can DELETE', null as 'Can PRINT', null as 'Can DOUBLE PRINT', null as 'Can APPROVE'
                from dual"
            );
        else
            $this->privilege = DB::table('privilege as p')
                ->select(
                    'user_id as User ID',
                    'can_read as Can READ',
                    'can_create as Can CREATE',
                    'can_update as Can UPDATE',
                    'can_delete as Can DELETE',
                    'can_print as Can PRINT',
                    'can_double_print as Can DOUBLE PRINT',
                    'can_approve as Can APPROVE'
                )->leftJoin('employee as e', 'p.user_id', '=', 'e.employee_nik')
                ->leftJoin('menu as m', 'p.menu_id', '=', 'm.menu_id')
                ->where('p.menu_id', $id)
                ->get();
        if (count($this->privilege) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->privilege, code: 200);
    }

    public function user(): JsonResponse
    {
        $query = DB::table('precise.privilege')
            ->select('user_id')
            ->groupBy('user_id');

        $this->privilege = DB::table(DB::raw("({$query->toSql()})p"))
            ->select(
                'p.user_id',
                'e.employee_name'
            )
            ->leftJoin('precise.employee as e', 'p.user_id', '=', 'e.employee_nik')
            ->get();

        if (count($this->privilege) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->privilege, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        DB::beginTransaction();
        foreach ($data['data'] as $val) {
            $validator = Validator::make($val, [
                "user_id"           => 'required|exists:users,user_id',
                "menu_id"           => 'required|exists:menu,menu_id',
                "can_read"          => 'required|boolean',
                "can_create"        => 'required|boolean',
                "can_update"        => 'required|boolean',
                "can_delete"        => 'required|boolean',
                "can_print"         => 'required|boolean',
                "can_double_print"  => 'required|boolean',
                "can_approve"       => 'required|boolean',
                "created_by"        => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
            }


            $values[] = [
                "user_id"          => $val["user_id"],
                "menu_id"          => $val["menu_id"],
                "can_read"         => $val["can_read"],
                "can_create"       => $val["can_create"],
                "can_update"       => $val["can_update"],
                "can_delete"       => $val["can_delete"],
                "can_print"        => $val["can_print"],
                "can_double_print" => $val["can_double_print"],
                "can_approve"      => $val["can_approve"],
                "created_by"       => $val["created_by"]
            ];
        }
        $this->privilege = DB::table("precise.privilege")
            ->upsert(
                $values,
                ['user_id', 'menu_id'],
                ['can_read', 'can_create', 'can_update', 'can_delete', 'can_print', 'can_double_print', 'can_approve', 'created_by']
            );

        if ($this->privilege < 1)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function copy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "user_id_from"    => 'required|exists:users,user_id',
            "user_id_to"      => 'required|exists:users,user_id',
            "created_by"      => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {

            $query = DB::table('precise.privilege')
                ->where('user_id', $request->user_id_from)
                ->selectRaw("
                '$request->user_id_to',
                menu_id,
                can_read,
                can_create,
                can_update,
                can_delete,
                can_print,
                can_double_print,
                can_approve,
                '$request->created_by'
            ");
            $this->privilege = DB::table('precise.privilege as a')
                ->where('a.user_id', $request->user_id_to)
                ->whereNull('b.privilege_id')
                ->selectRaw("
                    '$request->user_id_to',
                    a.menu_id,
                    a.can_read,
                    a.can_create,
                    a.can_update,
                    a.can_delete,
                    a.can_print,
                    a.can_double_print,
                    a.can_approve,
                    '$request->created_by'
                ")->leftJoin(
                    'privilege as b',
                    function ($join) use ($request) {
                        $join->on('a.menu_id', '=', 'b.menu_id')
                            ->where('b.user_id', '=', $request->user_id_from);
                    }
                )->union($query)->get();

            if (count($this->privilege) == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "failed load data", code: 500);
            }
            foreach ($this->privilege as $priv) {
                $values[] = [
                    "user_id"           => $request->user_id_to,
                    "menu_id"           => $priv->menu_id,
                    "can_read"          => $priv->can_read,
                    "can_create"        => $priv->can_create,
                    "can_update"        => $priv->can_update,
                    "can_delete"        => $priv->can_delete,
                    "can_print"         => $priv->can_print,
                    "can_double_print"  => $priv->can_double_print,
                    "can_approve"       => $priv->can_approve,
                    "created_by"        => $request->created_by
                ];
            }

            $result = DB::table("precise.privilege")
                ->upsert(
                    $values,
                    ['user_id', 'menu_id'],
                    ['can_read', 'can_create', 'can_update', 'can_delete', 'can_print', 'can_double_print', 'can_approve', 'created_by']
                );

            if ($result == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "failed update data", code: 500);
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: "success update data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
