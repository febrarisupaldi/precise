<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class WarehouseGroupController extends Controller
{
    private $warehouseGroup;
    public function index(): JsonResponse
    {
        $this->warehouseGroup = DB::table('precise.warehouse_group')
            ->select(
                'warehouse_group_code',
                'warehouse_group_name',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )->get();
        if (count($this->warehouseGroup) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->warehouseGroup, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->warehouseGroup = DB::table('precise.warehouse_group')
            ->where('warehouse_group_code', $id)
            ->first();
        if (empty($this->warehouseGroup))
            return response()->json("not found", 404);
        return response()->json($this->warehouseGroup, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_group_code'  => 'required|unique:warehouse_group,warehouse_group_code',
            'warehouse_group_name'  => 'required',
            'created_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        $this->warehouseGroup = DB::table('precise.warehouse_group')
            ->insert([
                'warehouse_group_code'  => $request->warehouse_group_code,
                'warehouse_group_name'  => $request->warehouse_group_name,
                'created_by'            => $request->created_by
            ]);

        if ($this->warehouseGroup == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_group_code'      => 'required|exists:warehouse_group,warehouse_group_code',
            'new_warehouse_group_code'  => 'required',
            'warehouse_group_name'      => 'required',
            'updated_by'                => 'required',
            'reason'                    => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->warehouseGroup = DB::table('precise.warehouse_group')
                ->where('warehouse_group_code', $request->warehouse_group_code)
                ->update([
                    'warehouse_group_code'  => $request->new_warehouse_group_code,
                    'warehouse_group_name'  => $request->warehouse_group_name,
                    'updated_by'            => $request->updated_by
                ]);

            if ($this->warehouseGroup == 0) {
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
            'warehouse_group_code'      => 'required|exists:warehouse_group,warehouse_group_code',
            'deleted_by'                => 'required',
            'reason'                    => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->warehouseGroup = DB::table('precise.warehouse_group')
                ->where('warehouse_group_code', $request->warehouse_group_code)
                ->delete();
            if ($this->warehouseGroup == 0) {
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
                $this->warehouseGroup = DB::table('precise.warehouse_group')
                    ->where('warehouse_group_code', $value)
                    ->count();
            }

            if ($this->warehouseGroup == 0)
                return response()->json(['status' => 'error', 'message' => "not found"], 200);
            return response()->json(['status' => 'ok', 'message' => $this->warehouseGroup], 200);
        }
    }
}
