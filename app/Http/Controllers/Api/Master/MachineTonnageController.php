<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class MachineTonnageController extends Controller
{
    private $machineTonnage;
    public function index(): JsonResponse
    {
        $this->machineTonnage = DB::table('precise.machine_tonnage')
            ->select(
                'tonnage_group',
                'tonnage_min',
                'tonnage_max',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        return response()->json(["status" => "ok", "data" => $this->machineTonnage], 200);
    }

    public function show($id): JsonResponse
    {
        $this->machineTonnage = DB::table('precise.machine_tonnage')
            ->where('tonnage_group', $id)
            ->select(
                'tonnage_group',
                'tonnage_min',
                'tonnage_max',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->first();

        if (empty($this->machineTonnage)) {
            return response()->json($this->machineTonnage, 404);
        }

        return response()->json($this->machineTonnage, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tonnage_group' => 'required|unique:machine_tonnage,tonnage_group',
            'tonnage_min'   => 'required|numeric',
            'tonnage_max'   => 'required|numeric',
            'created_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->machineTonnage = DB::table('precise.machine_tonnage')
                ->insert([
                    'tonnage_group'     => $request->tonnage_group,
                    'tonnage_min'       => $request->tonnage_min,
                    'tonnage_max'       => $request->tonnage_max,
                    'created_by'        => $request->created_by
                ]);
            if ($this->machineTonnage == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
            }

            return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tonnage_group' => 'required|exists:machine_tonnage,tonnage_group',
            'tonnage_min'   => 'required|numeric|lt:tonnage_max',
            'tonnage_max'   => 'required|numeric|gt:tonnage_min',
            'is_active'     => 'required|boolean',
            'reason'        => 'required',
            'updated_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->machineTonnage = DB::table('precise.machine_tonnage')
                ->where('tonnage_group', $request->tonnage_group)
                ->update([
                    'tonnage_min'       => $request->tonnage_min,
                    'tonnage_max'       => $request->tonnage_max,
                    'is_active'         => $request->is_active,
                    'updated_by'        => $request->updated_by
                ]);
            if ($this->machineTonnage == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
            }

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $type   = $request->get('type');
        $value  = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "group") {
                $this->machineTonnage = DB::table('precise.machine_tonnage')
                    ->where('tonnage_group', $value)
                    ->count();
            }

            if ($this->machineTonnage == 0) {
                return response()->json(['status' => 'error', 'message' => $this->machineTonnage], 404);
            }

            return response()->json(['status' => 'ok', 'message' => $this->machineTonnage]);
        }
    }
}
