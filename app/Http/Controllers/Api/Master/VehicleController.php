<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;
use Symfony\Component\HttpFoundation\JsonResponse;

class VehicleController extends Controller
{
    private $vehicle;
    public function index(): JsonResponse
    {
        $this->vehicle = DB::table('precise.vehicle')
            ->select(
                'vehicle_id',
                'vehicle_model',
                'license_number',
                'vehicle_description',
                DB::raw("
                    case is_owned
                        when 0 then 'Tidak'
                        when 1 then 'Ya' 
                    end as 'Milik PC',
                    case is_active 
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'Status aktif'
                "),
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )->get();

        if (count($this->vehicle) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->vehicle, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->vehicle = DB::table('precise.vehicle')
            ->where('vehicle_id', $id)
            ->select(
                'vehicle_model',
                'license_number',
                'vehicle_description',
                'is_active',
                'is_owned'
            )->first();
        if (empty($this->vehicle))
            return response()->json("not empty", 404);
        return response()->json($this->vehicle, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'vehicle_model'     => 'required',
                'license_number'    => 'required',
                'desc'              => 'nullable',
                'is_owned'          => 'required|boolean',
                'created_by'        => 'required'
            ]
        );
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->vehicle = DB::table('precise.vehicle')
            ->insert([
                'vehicle_model'         => $request->vehicle_model,
                'license_number'        => $request->license_number,
                'vehicle_description'   => $request->desc,
                'is_owned'              => $request->is_owned,
                'created_by'            => $request->created_by,
            ]);

        if ($this->vehicle == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'vehicle_id'        => 'required|exists:vehicle,vehicle_id',
                'vehicle_model'     => 'required',
                'license_number'    => 'required',
                'is_active'         => 'required|boolean',
                'is_owned'          => 'required|boolean',
                'updated_by'        => 'required',
                'reason'            => 'required'
            ]
        );

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");

            $this->vehicle = DB::table('precise.vehicle')
                ->where('vehicle_id', $request->vehicle_id)
                ->update([
                    'vehicle_model'         => $request->vehicle_model,
                    'license_number'        => $request->license_number,
                    'vehicle_description'   => $request->desc,
                    'is_owned'              => $request->is_owned,
                    'is_active'             => $request->is_active,
                    'updated_by'            => $request->updated_by
                ]);
            if ($this->vehicle == 0) {
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

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 500);
        } else {
            if ($type == "license_number") {
                $this->vehicle = DB::table('precise.vehicle')
                    ->where('license_number', $value)
                    ->count();
            }
            if ($this->vehicle == 0)
                return ResponseController::json(status: "error", message: $this->vehicle, code: 404);

            return ResponseController::json(status: "ok", message: $this->vehicle, code: 200);
        }
    }
}
