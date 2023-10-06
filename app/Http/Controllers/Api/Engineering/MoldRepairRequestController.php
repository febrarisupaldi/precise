<?php

namespace App\Http\Controllers\Api\Engineering;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\JsonResponse;

class MoldRepairRequestController extends Controller
{
    private $moldRepair;
    public function index($workcenter, Request $request): JsonResponse
    {
        $start  = $request->get('start');
        $end    = $request->get('end');
        $validator = Validator::make($request->all(), [
            'start'         => 'required_with:end|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required_with:start|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $workcenter = explode("-", $workcenter);
        $this->moldRepair = DB::table('precise.mold_repair_request_hd as a')
            ->whereBetween('a.request_date', [$start, $end])
            ->whereIn('a.workcenter_id', $workcenter)
            ->select(
                'a.request_hd_id',
                'a.request_number',
                'a.request_date',
                'a.workcenter_id',
                'b.workcenter_code',
                'b.workcenter_name',
                'c.mold_hd_id',
                'c.mold_number',
                'c.mold_name',
                'a.request_description',
                'a.action_description',
                'a.requested_by',
                'a.received_on',
                'a.received_by',
                'a.approved_on',
                'a.approved_by',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('precise.workcenter as b', 'a.workcenter_id', '=', 'b.workcenter_id')
            ->leftJoin('precise.mold_hd as c', 'a.mold_hd_id', '=', 'c.mold_hd_id')
            ->get();

        if (count($this->moldRepair) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->moldRepair, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->moldRepair = DB::table('precise.mold_repair_request_hd as a')
            ->where('a.request_hd_id', $id)
            ->select(
                'a.request_hd_id',
                'a.request_number',
                'a.request_date',
                'a.workcenter_id',
                'b.workcenter_code',
                'b.workcenter_name',
                'c.mold_hd_id',
                'c.mold_number',
                'c.mold_name',
                'a.request_description',
                'a.action_description',
                'a.requested_by',
                'a.received_on',
                'a.received_by',
                'a.approved_on',
                'a.approved_by',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('precise.workcenter as b', 'a.workcenter_id', '=', 'b.workcenter_id')
            ->leftJoin('precise.mold_hd as c', 'a.mold_hd_id', '=', 'c.mold_hd_id')
            ->first();
        if (empty($this->moldRepair))
            return response()->json("not found", 404);
        return response()->json($this->moldRepair, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'request_number'        => 'required|unique:mold_repair_request_hd,request_number',
            'request_date'          => 'required|date_format:Y-m-d',
            'workcenter_id'         => 'required|exists:workcenter,workcenter_id',
            'mold_hd_id'            => 'required|exists:mold_hd,mold_hd_id',
            'request_desc'          => 'required',
            'action_desc'           => 'nullable',
            'requested_by'          => 'required',
            'received_on'           => 'required',
            'received_by'           => 'required',
            'created_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->moldRepair = DB::table('precise.mold_repair_request_hd')
            ->insert([
                'request_number'        => $request->request_number,
                'request_date'          => $request->request_date,
                'workcenter_id'         => $request->workcenter_id,
                'mold_hd_id'            => $request->mold_hd_id,
                'request_description'   => $request->request_desc,
                'action_description'    => $request->action_desc,
                'requested_by'          => $request->requested_by,
                'received_on'           => $request->received_on,
                'received_by'           => $request->received_by,
                'created_by'            => $request->created_by,
            ]);

        if ($this->moldRepair == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'request_hd_id'     => 'required|exists:mold_repair_request_hd,request_hd_id',
            'request_number'    => 'required',
            'request_date'      => 'required|date_format:Y-m-d',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'mold_hd_id'        => 'required|exists:mold_hd,mold_hd_id',
            'request_desc'      => 'required',
            'action_desc'       => 'nullable',
            'received_by'       => 'required',
            'approved_by'       => 'required',
            'updated_by'        => 'required',
            'reason'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            DB::beginTransaction();

            DBController::reason($request, "update");
            $this->moldRepair = DB::table('precise.mold_repair_request_hd')
                ->where('request_hd_id', $request->request_hd_id)
                ->update([
                    'request_number'        => $request->request_number,
                    'request_date'          => $request->request_date,
                    'workcenter_id'         => $request->workcenter_id,
                    'mold_hd_id'            => $request->mold_hd_id,
                    'request_description'   => $request->request_desc,
                    'action_description'    => $request->action_desc,
                    'received_by'           => $request->received_by,
                    'approved_on'           => $request->approved_on,
                    'approved_by'           => $request->approved_by,
                    'updated_by'            => $request->updated_by,
                ]);

            if ($this->moldRepair == 0) {
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
            'request_hd_id'     => 'required|exists:mold_repair_request_hd,request_hd_id',
            'reason'            => 'required',
            'deleted_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->moldRepair = DB::table('precise.mold_repair_request_hd')
                ->where('request_hd_id', $request->request_hd_id)
                ->delete();

            if ($this->moldRepair == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "failed delete data", code: 500);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success delete data", code: 204);
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
            'type'  => 'required_with:value',
            'value' => 'required_with:type'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "number") {
                $this->moldRepair = DB::table('precise.mold_repair_request_hd')
                    ->where('request_number', $value)
                    ->count();
            }
            if ($this->moldRepair == 0)
                return response()->json(['status' => 'error', 'message' => $this->moldRepair], 404);
            return response()->json(['status' => 'ok', 'message' => $this->moldRepair], 200);
        }
    }
}
