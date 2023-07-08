<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class MachinePressingController extends Controller
{
    private $machine;
    public function index(): JsonResponse
    {
        $this->machine = DB::table('precise.machine_pressing as m')
            ->join('precise.machine_status as s', 'm.machine_status_code', '=', 's.status_code')
            ->select(
                'm.machine_pressing_id',
                'm.machine_code',
                'm.line_code',
                'm.line_number',
                'm.tonnage',
                'm.serial_number',
                'm.production_year',
                'm.brand',
                'm.motor_power',
                'm.heater_power',
                DB::raw("
                    case when m.can_plain = 1 then 'Ya' else 'Tidak' end as can_plain,  
                    case when m.can_print = 1 then 'Ya' else 'Tidak' end as can_print,  
                    case when m.can_mug = 1 then 'Ya' else 'Tidak' end as can_mug,  
                    case when m.can_bico_lg = 1 then 'Ya' else 'Tidak' end as an_bico_lg, 
                    case when m.can_bico_material = 1 then 'Ya' else 'Tidak' end as can_bico_material 
                "),
                'm.machine_status_code',
                's.status_description',
                'm.created_by',
                'm.created_on',
                'm.created_by',
                'm.updated_on',
                'm.updated_by'
            )->get();

        if (count($this->machine) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->machine, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->machine = DB::table('precise.machine_pressing as m')
            ->where('m.machine_pressing_id', $id)
            ->select(
                'm.machine_pressing_id',
                'm.machine_code',
                'm.line_code',
                'm.line_number',
                'm.tonnage',
                'm.serial_number',
                'm.old_machine_code',
                'm.production_year',
                'm.brand',
                'm.motor_power',
                'm.heater_power',
                'm.can_plain',
                'm.can_print',
                'm.can_mug',
                'm.can_bico_lg',
                'm.can_bico_material',
                'm.machine_status_code',
                'm.created_by',
                'm.created_on',
                'm.created_by',
                'm.updated_on',
                'm.updated_by'
            )->first();

        if (empty($this->machine)) {
            return response()->json($this->machine, 404);
        }

        return response()->json($this->machine, 200);
    }

    public function showByCode($code): JsonResponse
    {
        $this->machine = DB::table('precise.machine_pressing as m')
            ->where('m.machine_code', $code)
            ->select(
                'm.machine_pressing_id',
                'm.machine_code',
                'm.line_code',
                'm.line_number',
                'm.tonnage',
                'm.serial_number',
                'm.old_machine_code',
                'm.production_year',
                'm.brand',
                'm.motor_power',
                'm.heater_power',
                'm.can_plain',
                'm.can_print',
                'm.can_mug',
                'm.can_bico_lg',
                'm.can_bico_material',
                'm.machine_status_code',
                'm.created_by',
                'm.created_on',
                'm.created_by',
                'm.updated_on',
                'm.updated_by'
            )->first();

        if (empty($this->machine)) {
            return response()->json($this->machine, 404);
        }

        return response()->json($this->machine, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'machine_code'          => 'required',
                'line_code'             => 'required',
                'line_number'           => 'required',
                'tonnage'               => 'required',
                'serial_number'         => 'required',
                'production_year'       => 'required',
                'brand'                 => 'required',
                'motor_power'           => 'required',
                'heater_power'          => 'required',
                'can_plain'             => 'required',
                'can_print'             => 'required',
                'can_mug'               => 'required',
                'can_bico_lg'           => 'required',
                'can_bico_material'     => 'required',
                'machine_status_code'   => 'required',
                'created_by'            => 'required'
            ]
        );

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->machine = DB::table('precise.machine_pressing')->insert([
            'machine_code'          => $request->machine_code,
            'line_code'             => $request->line_code,
            'line_number'           => $request->line_number,
            'tonnage'               => $request->tonnage,
            'serial_number'         => $request->serial_number,
            'production_year'       => $request->production_year,
            'brand'                 => $request->brand,
            'motor_power'           => $request->motor_power,
            'heater_power'          => $request->heater_power,
            'can_plain'             => $request->can_plain,
            'can_print'             => $request->can_print,
            'can_mug'               => $request->can_mug,
            'can_bico_lg'           => $request->can_bico_lg,
            'can_bico_material'     => $request->can_bico_material,
            'machine_status_code'   => $request->machine_status_code,
            'created_by'            => $request->created_by
        ]);

        if ($this->machine == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'machine_pressing_id'   => 'required|exists:machine_pressing,machine_pressing_id',
                'machine_code'          => 'required',
                'line_code'             => 'required',
                'line_number'           => 'required',
                'tonnage'               => 'required',
                'serial_number'         => 'required',
                'production_year'       => 'required',
                'brand'                 => 'required',
                'motor_power'           => 'required',
                'heater_power'          => 'required',
                'can_plain'             => 'required',
                'can_print'             => 'required',
                'can_mug'               => 'required',
                'can_bico_lg'           => 'required',
                'can_bico_material'     => 'required',
                'machine_status_code'   => 'required',
                'reason'                => 'required',
                'updated_by'            => 'required'
            ]
        );
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->machine = DB::table('precise.machine_pressing')
                ->where('machine_pressing_id', $request->machine_pressing_id)
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
                    'can_plain'             => $request->can_plain,
                    'can_print'             => $request->can_print,
                    'can_mug'               => $request->can_mug,
                    'can_bico_lg'           => $request->can_bico_lg,
                    'can_bico_material'     => $request->can_bico_material,
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
        $type   = $request->get('type');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "code") {
                $value  = $request->get('value');
                $this->machine = DB::table('precise.machine_pressing')
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
                    $this->machine = DB::table('precise.machine_pressing')
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
