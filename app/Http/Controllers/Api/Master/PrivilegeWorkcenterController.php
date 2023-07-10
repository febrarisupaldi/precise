<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class PrivilegeWorkcenterController extends Controller
{
    private $privilegeWC;

    public function index(): JsonResponse
    {
        $this->privilegeWC = DB::table('privilege_workcenter as a')
            ->select(
                'privilege_workcenter_id',
                'a.user_id',
                'e.employee_name',
                'w.workcenter_code',
                'w.workcenter_name',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('employee as e', 'a.user_id', '=', 'e.employee_nik')
            ->leftJoin('workcenter as w', 'a.workcenter_id', '=', 'w.workcenter_id')
            ->get();
        if (count($this->privilegeWC) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->privilegeWC, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->privilegeWC = DB::table('privilege_workcenter as a')
            ->where('privilege_workcenter_id', $id)
            ->select(
                'privilege_workcenter_id',
                'a.user_id',
                'e.employee_name',
                'a.workcenter_id',
                'w.workcenter_code',
                'w.workcenter_name'
            )
            ->leftJoin('employee as e', 'a.user_id', '=', 'e.employee_nik')
            ->leftJoin('workcenter as w', 'a.workcenter_id', '=', 'w.workcenter_id')
            ->first();

        if (empty($this->privilegeWC))
            return response()->json("not found", 404);

        return response()->json($this->privilegeWC, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id'       => 'required|exists:users,user_id',
            'workcenter_id' => 'required|exists:workcenter,workcenter_id',
            'created_by'    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->privilegeWC = DB::table('precise.privilege_workcenter')
            ->insert([
                'user_id'       => $request->user_id,
                'workcenter_id' => $request->workcenter_id,
                'created_by'    => $request->created_by
            ]);

        if ($this->privilegeWC == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);
        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'privilege_workcenter_id'   => 'required|exists:privilege_workcenter,privilege_workcenter_id',
            'user_id'                   => 'required|exists:users,user_id',
            'workcenter_id'             => 'required|exists:workcenter,workcenter_id',
            'updated_by'                => 'required',
            'reason'                    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->privilegeWC = DB::table('precise.privilege_workcenter')
                ->where('privilege_workcenter_id', $request->privilege_workcenter_id)
                ->update([
                    'user_id'       => $request->user_id,
                    'workcenter_id' => $request->workcenter_id,
                    'updated_by'    => $request->updated_by
                ]);

            if ($this->privilegeWC == 0) {
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

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'privilege_workcenter_id'   => 'required|exists:privilege_workcenter,privilege_workcenter_id',
            'reason'                    => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            DBController::reason($request, "delete");

            $this->privilegeWC = DB::table('privilege_workcenter')
                ->where('privilege_workcenter_id', $request->privilege_workcenter_id)
                ->delete();

            if ($this->privilegeWC == 0) {
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
        $validator = Validator::make($request->all(), [
            'user_id'       => 'required',
            'workcenter_id' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            $this->privilegeWC = DB::table('precise.privilege_workcenter')
                ->where([
                    ['user_id', '=', $request->get('user_id')],
                    ['workcenter_id', '=', $request->get('workcenter_id')]
                ])
                ->count();

            if ($this->privilegeWC == 0)
                return ResponseController::json(status: "error", message: $this->privilegeWC, code: 404);

            return ResponseController::json(status: "ok", message: $this->privilegeWC, code: 200);
        }
    }
}
