<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class RejectController extends Controller
{
    private $reject;
    public function index(Request $request): JsonResponse
    {
        $wc = $request->get('workcenter');
        $validator = Validator::make($request->all(), [
            'workcenter' => 'required|exists:workcenter,workcenter_id'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $workcenter = explode("-", $wc);
        $this->reject = DB::table('precise.reject as a')
            ->whereIn('a.workcenter_id', $workcenter)
            ->select(
                'a.reject_id',
                'a.reject_code',
                'a.reject_name',
                'a.reject_description',
                'r.reject_group_code',
                'r.reject_group_name',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('precise.reject_group as r', 'a.reject_group_id', '=', 'r.reject_group_id')
            ->leftJoin('precise.workcenter as w', 'a.workcenter_id', '=', 'w.workcenter_id')
            ->get();

        return response()->json(["status" => "ok", "data" => $this->reject], 200);
    }

    public function show($id): JsonResponse
    {
        $this->reject = DB::table('precise.reject as a')
            ->where('a.reject_id', $id)
            ->select(
                'a.reject_id',
                'a.reject_code',
                'a.reject_name',
                'a.reject_description',
                'a.reject_group_id',
                'r.reject_group_code',
                'r.reject_group_name',
                'a.workcenter_id',
                'w.workcenter_code',
                'w.workcenter_name',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('precise.reject_group as r', 'a.reject_group_id', '=', 'r.reject_group_id')
            ->leftJoin('precise.workcenter as w', 'a.workcenter_id', '=', 'w.workcenter_id')
            ->first();
        if (empty($this->reject))
            return response()->json("not found", 404);
        return response()->json($this->reject, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reject_code'       => 'required|unique:reject,reject_code',
            'reject_name'       => 'required',
            'reject_group_id'   => 'required|exists:reject_group,reject_group_id',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'desc'              => 'nullable',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->reject = DB::table('precise.reject')
            ->insert([
                'reject_code'         => $request->reject_code,
                'reject_name'         => $request->reject_name,
                'reject_description'  => $request->desc,
                'reject_group_id'     => $request->reject_group_id,
                'workcenter_id'       => $request->workcenter_id,
                'created_by'          => $request->created_by
            ]);

        if ($this->reject == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
        }
        return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reject_id'         => 'required|exists:reject,reject_id',
            'reject_code'       => 'required',
            'reject_name'       => 'required',
            'reject_group_id'   => 'required|exists:reject_group,reject_group_id',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'desc'              => 'nullable',
            'reason'            => 'required',
            'updated_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->reject = DB::table('precise.reject')
                ->where('reject_id', $request->reject_id)
                ->update([
                    'reject_code'         => $request->reject_code,
                    'reject_name'         => $request->reject_name,
                    'reject_description'  => $request->desc,
                    'reject_group_id'     => $request->reject_group_id,
                    'workcenter_id'       => $request->workcenter_id,
                    'updated_by'          => $request->updated_by
                ]);

            if ($this->reject == 0) {
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

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reject_id'         => 'required|exists:reject,reject_id',
            'reason'            => 'required',
            'deleted_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->reject = DB::table('precise.reject')
                ->where('reject_id', $request->reject_id)
                ->delete();

            if ($this->reject == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'failed delete data'], 500);
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success delete data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
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
                $this->reject = DB::table('precise.reject')
                    ->where('reject_code', $value)
                    ->count();
            }
            if ($this->reject == 0)
                return response()->json(['status' => 'error', 'message' => $this->reject], 500);
            return response()->json(['status' => 'ok', 'message' => $this->reject], 200);
        }
    }
}
