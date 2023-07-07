<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class DowntimeController extends Controller
{
    private $downtime;

    public function index($workcenter): JsonResponse
    {
        try {
            $workcenter = explode("-", $workcenter);
            $this->downtime = DB::table('precise.downtime as a')
                ->whereIn('w.workcenter_id', $workcenter)
                ->select(
                    'a.downtime_id',
                    'a.downtime_code',
                    'a.downtime_name',
                    'a.downtime_description',
                    'g.downtime_group_code',
                    'g.downtime_group_name',
                    'a.std_duration',
                    DB::raw("
                        case a.is_planned 
                            when 0 then 'Tidak aktif'
                            when 1 then 'Aktif' 
                        end as 'is_planned',
                        case a.is_need_approval 
                            when 0 then 'Tidak aktif'
                            when 1 then 'Aktif' 
                        end as 'is_need_approval'
                    "),
                    'a.to_be_added1',
                    'a.to_be_added2',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin('precise.downtime_group as g', 'a.downtime_group_id', '=', 'g.downtime_group_id')
                ->leftJoin('precise.workcenter as w', 'a.workcenter_id', '=', 'w.workcenter_id')
                ->get();
            if (count($this->downtime) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->downtime, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function show($id): JsonResponse
    {
        $this->downtime = DB::table('precise.downtime as a')
            ->where('a.downtime_id', $id)
            ->select(
                'a.downtime_id',
                'a.downtime_code',
                'a.downtime_name',
                'a.downtime_description',
                'a.downtime_group_id',
                'g.downtime_group_code',
                'g.downtime_group_name',
                'a.workcenter_id',
                'w.workcenter_code',
                'w.workcenter_name',
                'a.std_duration',
                'a.is_planned',
                'a.is_need_approval',
                'a.to_be_added1',
                'a.to_be_added2',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('precise.downtime_group as g', 'a.downtime_group_id', '=', 'g.downtime_group_id')
            ->leftJoin('precise.workcenter as w', 'a.workcenter_id', '=', 'w.workcenter_id')
            ->first();

        if (empty($this->downtime)) {
            return response()->json("error", 404);
        }

        return response()->json($this->downtime, 200);
    }


    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'downtime_code'     => 'required|unique:downtime,downtime_code',
            'downtime_name'     => 'required',
            'downtime_group_id' => 'required|exists:downtime_group,downtime_group_id',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'std_duration'      => 'nullable|date_format:H:i:s',
            'is_planned'        => 'required|boolean',
            'is_need_approval'  => 'required|boolean',
            'desc'              => 'nullable',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->downtime = DB::table('precise.downtime')
            ->insert([
                'downtime_code'         => $request->downtime_code,
                'downtime_name'         => $request->downtime_name,
                'downtime_description'  => $request->desc,
                'downtime_group_id'     => $request->downtime_group_id,
                'workcenter_id'         => $request->workcenter_id,
                'std_duration'          => $request->std_duration,
                'is_planned'            => $request->is_planned,
                'is_need_approval'      => $request->is_need_approval,
                'to_be_added1'          => $request->to_be_added1,
                'to_be_added2'          => $request->to_be_added2,
                'created_by'            => $request->created_by
            ]);

        if ($this->downtime == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'downtime_id'       => 'required|exists:downtime,downtime_id',
            'downtime_code'     => 'required',
            'downtime_name'     => 'required',
            'downtime_group_id' => 'required|exists:downtime_group,downtime_group_id',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'std_duration'      => 'nullable|date_format:H:i:s',
            'is_planned'        => 'required|boolean',
            'is_need_approval'  => 'required|boolean',
            'reason'            => 'required',
            'desc'              => 'nullable',
            'updated_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->downtime = DB::table('precise.downtime')
                ->where('downtime_id', $request->downtime_id)
                ->update([
                    'downtime_code'         => $request->downtime_code,
                    'downtime_name'         => $request->downtime_name,
                    'downtime_description'  => $request->desc,
                    'downtime_group_id'     => $request->downtime_group_id,
                    'workcenter_id'         => $request->workcenter_id,
                    'std_duration'          => $request->std_duration,
                    'is_planned'            => $request->is_planned,
                    'is_need_approval'      => $request->is_need_approval,
                    'to_be_added1'          => $request->to_be_added1,
                    'to_be_added2'          => $request->to_be_added2,
                    'updated_by'            => $request->updated_by
                ]);


            if ($this->downtime == 0) {
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
            'downtime_id'       => 'required|exists:downtime,downtime_id',
            'deleted_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->downtime = DB::table('precise.downtime')
                ->where('downtime_id', $request->downtime_id)
                ->delete();

            if ($this->downtime == 0) {
                DB::rollback();
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
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "code") {
                $this->downtime = DB::table('precise.downtime')
                    ->where('downtime_code', $value)
                    ->count();
            }

            if ($this->downtime == 0)
                return ResponseController::json(status: "error", message: $this->downtime, code: 404);

            return ResponseController::json(status: "ok", message: $this->downtime, code: 200);
        }
    }
}
