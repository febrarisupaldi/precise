<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class WorkcenterController extends Controller
{
    private $workcenter;
    public function index(): JsonResponse
    {
        $this->workcenter = DB::table('precise.workcenter', 'wc')
            ->select(
                'workcenter_id',
                'workcenter_code',
                'workcenter_name',
                'workcenter_description',
                DB::raw(
                    "concat(wh.warehouse_code, ' - ', wh.warehouse_name) 'Gudang default'
                        , case wc.is_active 
                            when 0 then 'Tidak aktif'
                            when 1 then 'Aktif' 
                        end as 'Status aktif'
                    "
                ),
                'production_type',
                'wh.created_on',
                'wh.created_by',
                'wh.updated_on',
                'wh.updated_by'
            )->leftJoin('precise.warehouse as wh', 'wc.default_warehouse', '=', 'wh.warehouse_id')
            ->get();
        if (count($this->workcenter) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->workcenter, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->workcenter = DB::table('precise.workcenter')
            ->select(
                'workcenter_id',
                'workcenter_code',
                'workcenter_name',
                'workcenter_description',
                'warehouse.warehouse_id',
                'warehouse.warehouse_code',
                'warehouse.warehouse_name',
                'workcenter.is_active',
                'production_type'
            )->leftJoin('warehouse', 'workcenter.default_warehouse', '=', 'warehouse.warehouse_id')
            ->where('workcenter_id', $id)
            ->first();

        if (empty($this->workcenter))
            return response()->json("not found", 404);
        return response()->json($this->workcenter, 200);
    }

    public function showByCode($id): JsonResponse
    {
        $this->workcenter = DB::table('precise.workcenter as wc')
            ->select(
                'wc.workcenter_id',
                'wc.workcenter_code',
                'wc.workcenter_name',
                'wc.workcenter_description',
                'wh.warehouse_id',
                'wh.warehouse_code',
                'wh.warehouse_name',
                'wc.is_active',
                'wc.production_type'
            )->leftJoin('precise.warehouse as wh', 'wc.default_warehouse', '=', 'wh.warehouse_id')
            ->where('wc.workcenter_code', $id)
            ->get();

        if (count($this->workcenter) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->workcenter, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workcenter_code'   => 'required|unique:workcenter,workcenter_code',
            'workcenter_name'   => 'required',
            'desc'              => 'nullable',
            'warehouse_id'      => 'nullable|exists:warehouse,warehouse_id',
            'created_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->workcenter = DB::table('precise.workcenter')
            ->insert([
                'workcenter_code'           => $request->workcenter_code,
                'workcenter_name'           => $request->workcenter_name,
                'workcenter_description'    => $request->desc,
                'default_warehouse'         => $request->warehouse_id,
                'created_by'                => $request->created_by
            ]);

        if ($this->workcenter == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'workcenter_code'   => 'required',
            'workcenter_name'   => 'required',
            'warehouse_id'      => 'required|exists:warehouse,warehouse_id',
            'desc'              => 'nullable',
            'is_active'         => 'required|boolean',
            'updated_by'        => 'required',
            'reason'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->workcenter = DB::table('precise.workcenter')
                ->where('workcenter_id', $request->workcenter_id)
                ->update(
                    [
                        'workcenter_code'           => $request->workcenter_code,
                        'workcenter_name'           => $request->workcenter_name,
                        'workcenter_description'    => $request->desc,
                        'default_warehouse'         => $request->warehouse_id,
                        'is_active'                 => $request->is_active,
                        'updated_by'                => $request->updated_by
                    ]
                );

            if ($this->workcenter == 0) {
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
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'deleted_by'        => 'required',
            'reason'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->workcenter = DB::table('precise.workcenter')
                ->where('workcenter_id', $request->workcenter_id)
                ->delete();

            if ($this->workcenter == 0) {
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
            'type' => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "code")
                $this->workcenter = DB::table('precise.workcenter')
                    ->where('workcenter_code', $value)
                    ->count();
            elseif ($type == "name")
                $this->workcenter = DB::table('workcenter')
                    ->where('workcenter_name', $value)
                    ->count();

            if ($this->workcenter == 0)
                return ResponseController::json(status: "error", message: $this->workcenter, code: 404);

            return ResponseController::json(status: "ok", message: $this->workcenter, code: 200);
        }
    }
}
