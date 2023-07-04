<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class MachineController extends Controller
{
    private $machine;
    public function index(): JsonResponse
    {
        try {
            $this->machine = DB::table('precise.machine as m')
                ->select(
                    'machine_id',
                    'machine_code',
                    'machine_name',
                    'machine_brand',
                    'machine_model',
                    'serial_number',
                    'tonnage',
                    'manufacture_date',
                    'acquisition_date',
                    'active_date',
                    'inactive_date',
                    'm.workcenter_id',
                    'w.workcenter_code',
                    'w.workcenter_name',
                    'lane_code',
                    'lane_number',
                    'm.is_active',
                    'm.created_on',
                    'm.created_by',
                    'm.updated_on',
                    'm.updated_by'
                )
                ->leftJoin('precise.workcenter as w', 'm.workcenter_id', '=', 'w.workcenter_id')
                ->get();
            return response()->json(["status" => "ok", "data" => $this->machine], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "data" => $e->getMessage()], 500);
        }
    }

    public function show($id): JsonResponse

    {
        try {
            $this->machine = DB::table('precise.machine as m')
                ->where('machine_id', $id)
                ->select(
                    'machine_id',
                    'machine_code',
                    'machine_name',
                    'machine_brand',
                    'machine_model',
                    'serial_number',
                    'tonnage',
                    'manufacture_date',
                    'acquisition_date',
                    'active_date',
                    'inactive_date',
                    'm.workcenter_id',
                    'lane_code',
                    'lane_number',
                    'm.is_active',
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
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function showByWorkcenter(Request $request): JsonResponse
    {
        $wc = $request->get('id');
        $validator = Validator::make(
            $request->all(),
            [
                'id'    => 'required'
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $workcenter = explode("-", $wc);
            $this->machine = DB::table('precise.machine as m')
                ->whereIn('m.workcenter_id', $workcenter)
                ->select(
                    'machine_id',
                    'machine_code',
                    'machine_name',
                    'machine_brand',
                    'machine_model',
                    'serial_number',
                    'tonnage',
                    'manufacture_date',
                    'acquisition_date',
                    'active_date',
                    'inactive_date',
                    'm.workcenter_id',
                    'w.workcenter_code',
                    'w.workcenter_name',
                    'lane_code',
                    'lane_number',
                    'm.is_active',
                    'm.created_on',
                    'm.created_by',
                    'm.updated_on',
                    'm.updated_by'
                )
                ->leftJoin('precise.workcenter as w', 'm.workcenter_id', '=', 'w.workcenter_id')
                ->get();

            if (count($this->machine) == 0) {
                return response()->json(["status" => "ok", "data" => "not found"], 404);
            }

            return response()->json(["status" => "ok", "data" => $this->machine], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function showByProductionType($type): JsonResponse
    {
        try {
            $this->machine = DB::table('precise.machine as m')
                ->where('w.production_type', $type)
                ->where('m.is_active', 1)
                ->select(
                    'machine_id',
                    'machine_code',
                    'machine_name',
                    'machine_brand',
                    'machine_model',
                    'serial_number',
                    'tonnage',
                    DB::raw("
                        CONCAT(m.lane_code, ' - ', m.lane_number) as lane_code_and_number
                    "),
                    'w.workcenter_id',
                    'w.workcenter_code',
                    'w.workcenter_name',
                    'm.created_on',
                    'm.created_by',
                    'm.updated_on',
                    'm.updated_by'
                )
                ->leftJoin('precise.workcenter as w', 'm.workcenter_id', '=', 'w.workcenter_id')
                ->get();

            if (count($this->machine) == 0) {
                return response()->json(["status" => "ok", "data" => "not found"], 404);
            }

            return response()->json(["status" => "ok", "data" => $this->machine], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'machine_code'      => 'required|unique:machine,machine_code',
                'machine_name'      => 'required',
                'machine_brand'     => 'required',
                'machine_model'     => 'required',
                'serial_number'     => 'required',
                'tonnage'           => 'required|numeric',
                'manufacture_date'  => 'required|date_format:Y-m-d',
                'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
                'lane_code'         => 'required',
                'lane_number'       => 'required',
                'created_by'        => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $this->machine = DB::table('precise.machine')
                ->insert([
                    'machine_code'      => $request->machine_code,
                    'machine_name'      => $request->machine_name,
                    'machine_brand'     => $request->machine_brand,
                    'machine_model'     => $request->machine_model,
                    'serial_number'     => $request->serial_number,
                    'tonnage'           => $request->tonnage,
                    'manufacture_date'  => $request->manufacture_date,
                    'acquisition_date'  => $request->acquisition_date,
                    'active_date'       => $request->active_date,
                    'inactive_date'     => $request->inactive_date,
                    'workcenter_id'     => $request->workcenter_id,
                    'lane_code'         => $request->lane_code,
                    'lane_number'       => $request->lane_number,
                    'created_by'        => $request->created_by
                ]);

            if ($this->machine == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed insert data'], 500);
            }

            return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'machine_id'        => 'required|exists:machine,machine_id',
                'machine_code'      => 'required',
                'machine_name'      => 'required',
                'machine_brand'     => 'required',
                'machine_model'     => 'required',
                'serial_number'     => 'required',
                'tonnage'           => 'required|numeric',
                'manufacture_date'  => 'required|date_format:Y-m-d',
                'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
                'lane_code'         => 'required',
                'lane_number'       => 'required',
                'reason'            => 'required',
                'updated_by'        => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");

            $this->machine = DB::table('precise.machine')
                ->where('machine_id', $request->machine_id)
                ->update([
                    'machine_code'      => $request->machine_code,
                    'machine_name'      => $request->machine_name,
                    'machine_brand'     => $request->machine_brand,
                    'machine_model'     => $request->machine_model,
                    'serial_number'     => $request->serial_number,
                    'tonnage'           => $request->tonnage,
                    'manufacture_date'  => $request->manufacture_date,
                    'acquisition_date'  => $request->acquisition_date,
                    'active_date'       => $request->active_date,
                    'inactive_date'     => $request->inactive_date,
                    'workcenter_id'     => $request->workcenter_id,
                    'lane_code'         => $request->lane_code,
                    'lane_number'       => $request->lane_number,
                    'is_active'         => $request->is_active,
                    'updated_by'        => $request->updated_by
                ]);

            if ($this->machine == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
            } else {
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $validator = Validator::make($request->all(), [
            'type' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            try {
                if ($type == "code") {
                    $value = $request->get('value');
                    $validator = Validator::make($request->all(), [
                        'value' => 'required'
                    ]);
                    if ($validator->fails()) {
                        return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                    } else {
                        $this->machine = DB::table('precise.machine')
                            ->where('machine_code', $value)
                            ->select(
                                'machine_code'
                            )
                            ->count();
                    }
                } else if ($type == "lane") {
                    $workcenter = $request->get('workcenter');
                    $code = $request->get('lane_code');
                    $number = $request->get('lane_number');

                    $validator = Validator::make($request->all(), [
                        'workcenter' => 'required',
                        'lane_code'  => 'required',
                        'lane_number' => 'required'
                    ]);

                    if ($validator->fails()) {
                        return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                    } else {
                        $this->machine = DB::table('precise.machine')
                            ->where('workcenter_id', $workcenter)
                            ->where('lane_code', $code)
                            ->where('lane_number', $number)
                            ->select(
                                'machine_code'
                            )
                            ->count();
                    }
                }

                if ($this->machine == 0) {
                    return response()->json(['status' => 'error', 'message' => $this->machine], 404);
                }

                return response()->json(['status' => 'ok', 'message' => $this->machine], 200);
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        }
    }
}
