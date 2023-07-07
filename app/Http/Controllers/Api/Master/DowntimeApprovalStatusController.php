<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DowntimeApprovalStatusController extends Controller
{
    private $downtimeApprovalStatus;
    public function index(): JsonResponse
    {
        $this->downtimeApprovalStatus = DB::table('precise.downtime_approval_status as a')
            ->select(
                'a.status_code',
                'a.status_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->get();
        if (count($this->downtimeApprovalStatus) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->downtimeApprovalStatus, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->downtimeApprovalStatus = DB::table('precise.downtime_approval_status as a')
            ->where('status_code', $id)
            ->select(
                'a.status_code',
                'a.status_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->first();

        if (empty($this->downtimeApprovalStatus)) {
            return response()->json("error", 404);
        }

        return response()->json($this->downtimeApprovalStatus, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status_code'           => 'required|unique:downtime_approval_status,status_code',
            'created_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->downtimeApprovalStatus = DB::table('precise.downtime_approval_status')
            ->insert([
                'status_code'               => $request->status_code,
                'status_description'        => $request->desc,
                'created_by'                => $request->created_by
            ]);

        if ($this->downtimeApprovalStatus == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status_code'           => 'required|exists:downtime_approval_status,status_code',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            DB::beginTransaction();
            DBController::reason($request, "update");
            $this->downtimeApprovalStatus = DB::table('precise.downtime_approval_status')
                ->where('status_code', $request->status_code)
                ->update([
                    'status_code'               => $request->status_code,
                    'status_description'        => $request->desc,
                    'updated_by'                => $request->updated_by
                ]);

            if ($this->downtimeApprovalStatus == 0) {
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
            'status_code'   => 'required|exists:downtime_approval_status,status_code',
            'reason'        => 'required',
            'deleted_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->downtimeApprovalStatus = DB::table('precise.downtime_approval_status')
                ->where('status_code', $request->status_code)
                ->delete();

            if ($this->downtimeApprovalStatus == 0) {
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
                $this->downtimeApprovalStatus = DB::table('precise.downtime_approval_status')
                    ->where('status_code', $value)
                    ->count();
            }

            if ($this->downtimeApprovalStatus == 0) {
                return ResponseController::json(status: "error", message: $this->downtimeApprovalStatus, code: 404);
            }

            return ResponseController::json(status: "ok", message: $this->downtimeApprovalStatus, code: 200);
        }
    }
}
