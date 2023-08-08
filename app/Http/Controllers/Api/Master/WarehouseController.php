<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class WarehouseController extends Controller
{
    private $warehouse;
    public function index(): JsonResponse
    {
        $this->warehouse = DB::table('precise.warehouse')
            ->select(
                'warehouse_id',
                'warehouse_code',
                'warehouse_name',
                'warehouse_alias',
                'warehouse_group_code',
                'warehouse_pic_1',
                'warehouse_pic_2',
                'warehouse_approver',
                'is_active'
            )->get();

        if (count($this->warehouse) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->warehouse, code: 200);
    }

    public function show($id): JsonResponse
    {

        $this->warehouse = DB::table('precise.warehouse')
            ->where('warehouse_id', $id)
            ->select(
                'warehouse_id',
                'warehouse_code',
                'warehouse_name',
                'warehouse_alias',
                'warehouse_group_code',
                'warehouse_pic_1',
                'warehouse_pic_2',
                'warehouse_approver',
                'is_active'
            )
            ->first();
        if (empty($this->warehouse))
            return response()->json("not found", 404);
        return response()->json($this->warehouse, 200);
    }

    public function showByWarehouseGroup($id): JsonResponse
    {
        $id = explode("-", $id);
        $this->warehouse = DB::table('precise.warehouse')
            ->whereIn('warehouse_group_code', $id)
            ->select(
                'warehouse_id',
                'warehouse_code',
                'warehouse_name',
                'warehouse_alias',
                'warehouse_group_code',
                DB::raw("case is_active 
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif' 
                end as 'Status aktif'"),
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        if (count($this->warehouse) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->warehouse, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_code'        => 'required|unique:warehouse,warehouse_code',
            'warehouse_name'        => 'required',
            'warehouse_alias'       => 'nullable',
            'warehouse_group_code'  => 'required|exists:warehouse_group,warehouse_group_code',
            'created_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        $this->warehouse = DB::table('precise.warehouse')
            ->insert([
                'warehouse_code'        => $request->warehouse_code,
                'warehouse_name'        => $request->warehouse_name,
                'warehouse_alias'       => $request->warehouse_alias,
                'warehouse_group_code'  => $request->warehouse_group_code,
                'created_by'            => $request->created_by
            ]);

        if ($this->warehouse == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
            'warehouse_code'        => 'required',
            'warehouse_name'        => 'required',
            'warehouse_group_code'  => 'required|exists:warehouse_group,warehouse_group_code',
            'is_active'             => 'required|boolean',
            'updated_by'            => 'required',
            'reason'                => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->warehouse = DB::table('precise.warehouse')
                ->where('warehouse_id', $request->warehouse_id)
                ->update(
                    [
                        'warehouse_code'        => $request->warehouse_code,
                        'warehouse_name'        => $request->warehouse_name,
                        'warehouse_alias'       => $request->warehouse_alias,
                        'warehouse_group_code'  => $request->warehouse_group_code,
                        'is_active'             => $request->is_active,
                        'updated_by'            => $request->updated_by
                    ]
                );

            if ($this->warehouse == 0) {
                DB::rollback();
                return ResponseController::json(status: "error", message: "failed update data", code: 500);
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: "success update data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
            'deleted_by'            => 'required',
            'reason'                => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->warehouse = DB::table('precise.warehouse')
                ->where('warehouse_id', $request->warehouse_id)
                ->delete();

            if ($this->warehouse == 0) {
                DB::rollback();
                return ResponseController::json(status: "error", message: "failed delete data", code: 500);
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: "success delete data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "code") {
                $this->warehouse = DB::table('precise.warehouse')
                    ->where('warehouse_code', $value)
                    ->count();
            } else if ($type == "name") {
                $this->warehouse = DB::table('precise.warehouse')
                    ->where('warehouse_name', $value)
                    ->count();
            } else if ($type == "alias") {
                $this->warehouse = DB::table('precise.warehouse')
                    ->where('warehouse_alias', $value)
                    ->count();
            }
            if ($this->warehouse == 0)
                return ResponseController::json(status: "error", message: $this->warehouse, code: 404);

            return ResponseController::json(status: "ok", message: $this->warehouse, code: 200);
        }
    }
}
