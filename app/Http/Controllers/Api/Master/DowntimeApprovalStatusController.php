<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
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


        return response()->json(["status" => "ok", "data" => $this->downtimeApprovalStatus], 200);
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
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->downtimeApprovalStatus = DB::table('precise.downtime_approval_status')
                ->insert([
                    'status_code'               => $request->status_code,
                    'status_description'        => $request->desc,
                    'created_by'                => $request->created_by
                ]);

            if ($this->downtimeApprovalStatus == 0) {
                return response()->json(['status' => 'error', 'message' => 'Failed to insert downtime approval status, Contact your administrator']);
            }

            return response()->json(['status' => 'ok', 'message' => 'downtime approval status has been inserted']);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status_code'           => 'required|exists:downtime_approval_status,status_code',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
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
                    return response()->json(['status' => 'error', 'message' => 'Failed to update downtime approval status, Contact your administrator']);
                }

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'downtime approval status has been updated']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
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
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->downtimeApprovalStatus = DB::table('precise.downtime_approval_status')
                ->where('status_code', $request->status_code)
                ->delete();

            if ($this->downtimeApprovalStatus == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'Failed to delete downtime approval status, Contact your administrator']);
            }

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'downtime approval status has been deleted']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
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
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if ($type == "code") {
                $this->downtimeApprovalStatus = DB::table('precise.downtime_approval_status')
                    ->where('status_code', $value)
                    ->count();
            }

            if ($this->downtimeApprovalStatus == 0) {
                return response()->json(['status' => 'error', 'message' => 'not found'], 404);
            }

            return response()->json(['status' => 'ok', 'message' => $this->downtimeApprovalStatus]);
        }
    }
}
