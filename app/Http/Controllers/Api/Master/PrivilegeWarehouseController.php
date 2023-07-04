<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

class PrivilegeWarehouseController extends Controller
{
    private $privilegeWH;
    public function index(): JsonResponse
    {
        $this->privilegeWH = DB::table('precise.privilege_warehouse as a')
            ->select(
                'privilege_warehouse_id',
                'user_id',
                'employee_name',
                'warehouse_code',
                'warehouse_name',
                'privilege_type',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin("precise.employee as e", "a.user_id", "=", "e.employee_nik")
            ->leftJoin("precise.warehouse as w", "a.warehouse_id", "=", "w.warehouse_id")
            ->get();
        return response()->json(["status" => "ok", "data" => $this->privilegeWH], 200);
    }

    public function show($id): JsonResponse
    {
        $this->privilegeWH = DB::table('precise.privilege_warehouse as a')
            ->where('privilege_warehouse_id', $id)
            ->select(
                'a.user_id',
                'e.employee_name',
                'a.warehouse_id',
                'w.warehouse_name',
                'a.privilege_type'
            )
            ->leftJoin('precise.employee as e', 'a.user_id', '=', 'e.employee_nik')
            ->leftJoin('precise.warehouse as w', 'a.warehouse_id', '=', 'w.warehouse_id')
            ->first();

        if (empty($this->privilegeWH))
            return response()->json("not found", 404);

        return response()->json($this->privilegeWH, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required|exists:users,user_id',
            'warehouse_id'      => 'required|exists:warehouse,warehouse_id',
            'privilege_type'    => ['required', Rule::in(['IN', 'OUT'])],
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->privilegeWH = DB::table('precise.privilege_warehouse')
            ->insert([
                'user_id'           => $request->user_id,
                'warehouse_id'      => $request->warehouse_id,
                'privilege_type'    => $request->privilege_type,
                'created_by'        => $request->created_by
            ]);

        if ($this->privilegeWH == 0)
            return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
        else
            return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'privilege_warehouse_id'    => 'required|exists:privilege_warehouse,privilege_warehouse_id',
            'user_id'                   => 'required|exists:users,user_id',
            'warehouse_id'              => 'required|exists:warehouse,warehouse_id',
            'privilege_type'            => ['required', Rule::in(['IN', 'OUT'])],
            'updated_by'                => 'required',
            'reason'                    => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->privilegeWH = DB::table('precise.privilege_warehouse')
                ->where('privilege_warehouse_id', $request->privilege_warehouse_id)
                ->update([
                    'user_id'           => $request->user_id,
                    'warehouse_id'      => $request->warehouse_id,
                    'privilege_type'    => $request->privilege_type,
                    'updated_by'        => $request->updated_by
                ]);
            if ($this->privilegeWH == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'privilege_warehouse_id'    => 'required|exists:privilege_warehouse,privilege_warehouse_id',
            'reason'                    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->privilegeWH = DB::table('precise.privilege_warehouse')
                ->where('privilege_wh_id', $request->privilege_warehouse_id)
                ->delete();

            if ($this->privilegeWH == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'failed delete data'], 500);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'success delete data'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required',
            'warehouse_id'      => 'required',
            'privilege_type'    => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->privilegeWH = DB::table('precise.privilege_warehouse')
            ->where([
                'user_id' => $request->get('user_id'),
                'warehouse_id' => $request->get('warehouse_id'),
                'privilege_type' => $request->get('privilege_type')
            ])
            ->count();

        if ($this->privilegeWH == 0)
            return response()->json(['status' => 'error', 'message' => $this->privilegeWH], 400);

        return response()->json(["status" => "ok", "message" => $this->privilegeWH], 200);
    }
}
