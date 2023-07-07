<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DriverController extends Controller
{
    private $driver;
    public function index(): JsonResponse
    {
        $this->driver = DB::table('precise.driver as d')
            ->select(
                'd.driver_nik',
                'e.employee_name',
                DB::raw(
                    "case d.is_active 
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif' 
                end as 'Status aktif'"
                ),
                'd.created_on',
                'd.created_by',
                'd.updated_on',
                'd.updated_by'
            )
            ->leftJoin('precise.employee as e', 'd.driver_nik', '=', 'e.employee_nik')
            ->get();

        if (count($this->driver) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->driver, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->driver = DB::table('precise.driver as d')
            ->where('d.driver_nik', $id)
            ->select(
                'd.driver_nik',
                'e.employee_name',
                'd.is_active'
            )
            ->leftJoin('precise.employee as e', 'd.driver_nik', '=', 'e.employee_nik')
            ->first();

        if (empty($this->driver)) {
            return response()->json($this->driver, 404);
        }
        return response()->json($this->driver, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'driver_nik' => 'required|unique:driver,driver_nik|exists:employee,employee_nik',
                'created_by' => 'required'
            ]
        );
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->driver = DB::table('precise.driver')
            ->insert([
                'driver_nik' => $request->driver_nik,
                'created_by' => $request->created_by
            ]);

        if ($this->driver == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'driver_nik'    => 'required|exists:employee,employee_nik',
                'updated_by'    => 'required',
                'is_active'     => 'boolean',
                'reason'        => 'required'
            ]
        );

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->driver = DB::table('precise.driver')
                ->where('driver_nik', $request->driver_nik)
                ->update([
                    'is_active' => $request->is_active,
                    'updated_by' => $request->updated_by
                ]);

            if ($this->driver == 0) {
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
            if ($type == "nik") {
                $this->driver = DB::table('driver')
                    ->where('driver_nik', $value)
                    ->count();
            }

            if ($this->driver == 0)
                return ResponseController::json(status: "error", message: $this->driver, code: 404);

            return ResponseController::json(status: "ok", message: $this->driver, code: 200);
        }
    }
}
