<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class MachineInjectionController extends Controller
{
    private $machine;

    public function index(): JsonResponse
    {
        $this->machine = DB::table('precise.machine_injection as m')
            ->select(
                'm.machine_injection_id',
                'm.machine_code',
                'm.line_code',
                'm.line_number',
                'm.tonnage',
                'm.serial_number',
                'm.production_year',
                'm.brand',
                'm.motor_power',
                'm.heater_power',
                'm.machine_status_code',
                's.status_description',
                'm.created_by',
                'm.created_on',
                'm.created_by',
                'm.updated_on',
                'm.updated_by'
            )
            ->join('precise.machine_status as s', 'm.machine_status_code', '=', 's.status_code')
            ->get();

        if (count($this->machine) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->machine, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->machine = DB::table('precise.machine_injection as m')
            ->where('machine_injection_id', $id)
            ->select(
                'm.machine_injection_id',
                'm.machine_code',
                'm.line_code',
                'm.line_number',
                'm.tonnage',
                'm.serial_number',
                'm.production_year',
                'm.brand',
                'm.motor_power',
                'm.heater_power',
                'm.machine_status_code',
                'm.created_by',
                'm.created_on',
                'm.created_by',
                'm.updated_on',
                'm.updated_by'
            )
            ->first();

        if (empty($this->machine)) {
            return response()->json($this->machine, 404);
        }

        return response()->json($this->machine, 200);
    }

    public function showByCode($code): JsonResponse
    {
        $this->machine = DB::table("precise.machine_injection as m")
            ->where('m.machine_code', $code)
            ->select(
                'm.machine_injection_id',
                'm.machine_code',
                'm.old_machine_code',
                'm.line_code',
                'm.line_number',
                'm.tonnage',
                'm.serial_number',
                'm.production_year',
                'm.brand',
                'm.motor_power',
                'm.heater_power',
                'm.machine_status_code',
                'm.created_by',
                'm.created_on',
                'm.created_by',
                'm.updated_on',
                'm.updated_by'
            )
            ->first();
        if (empty($this->machine)) {
            return response()->json($this->machine, 404);
        }

        return response()->json($this->machine, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'machine_code'          =>  'required|unique:machine_injection,machine_code',
            'line_code'             =>  'required',
            'line_number'           =>  'required',
            'tonnage'               =>  'required',
            'serial_number'         =>  'required',
            'production_year'       =>  'required',
            'brand'                 =>  'required',
            'motor_power'           =>  'required',
            'heater_power'          =>  'required',
            'machine_status_code'   =>  'required|exists:machine_status,status_code',
            'created_by'            =>  'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            $this->machine = DB::table('precise.machine_injection')
                ->insert([
                    'machine_code'          => $request->machine_code,
                    'line_code'             => $request->line_code,
                    'line_number'           => $request->line_number,
                    'tonnage'               => $request->tonnage,
                    'serial_number'         => $request->serial_number,
                    'production_year'       => $request->production_year,
                    'brand'                 => $request->brand,
                    'motor_power'           => $request->motor_power,
                    'heater_power'          => $request->heater_power,
                    'machine_status_code'   => $request->machine_status_code,
                    'created_by'            => $request->created_by
                ]);

            if ($this->machine == 0)
                return ResponseController::json(status: "error", message: "failed input data", code: 500);

            return ResponseController::json(status: "ok", message: "success input data", code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'machine_injection_id'  =>  'required|exists:machine_injection,machine_injection_id',
            'machine_code'          =>  'required',
            'line_code'             =>  'required',
            'line_number'           =>  'required',
            'tonnage'               =>  'required',
            'serial_number'         =>  'required',
            'production_year'       =>  'required',
            'brand'                 =>  'required',
            'motor_power'           =>  'required',
            'heater_power'          =>  'required',
            'machine_status_code'   =>  'required|exists:machine_status,status_code',
            'updated_by'            =>  'required',
            'reason'                =>  'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->machine = DB::table('precise.machine_injection')
                ->where('machine_injection_id', $request->machine_injection_id)
                ->update([
                    'machine_code'          => $request->machine_code,
                    'line_code'             => $request->line_code,
                    'line_number'           => $request->line_number,
                    'tonnage'               => $request->tonnage,
                    'serial_number'         => $request->serial_number,
                    'production_year'       => $request->production_year,
                    'brand'                 => $request->brand,
                    'motor_power'           => $request->motor_power,
                    'heater_power'          => $request->heater_power,
                    'machine_status_code'   => $request->machine_status_code,
                    'updated_by'            => $request->updated_by
                ]);

            if ($this->machine == 0) {
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
            'type' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "code") {
                $this->machine = DB::table('precise.machine_injection')
                    ->where('machine_code', $value)
                    ->count();
            } else if ($type == "line") {
                $code = $request->get('line_code');
                $number = $request->get('line_number');

                $validator = Validator::make($request->all(), [
                    'line_code'  => 'required',
                    'line_number' => 'required'
                ]);

                if ($validator->fails()) {
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                } else {
                    $this->machine = DB::table('precise.machine_injection')
                        ->where('line_code', $code)
                        ->where('line_number', $number)
                        ->count();
                }
            }
            if ($this->machine == 0)
                return ResponseController::json(status: "error", message: $this->machine, code: 404);

            return ResponseController::json(status: "ok", message: $this->machine, code: 200);
        }
    }
}
