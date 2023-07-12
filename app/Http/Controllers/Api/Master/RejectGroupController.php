<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;
use Symfony\Component\HttpFoundation\JsonResponse;

class RejectGroupController extends Controller
{
    private $rejectGroup;
    public function index(): JsonResponse
    {
        $this->rejectGroup = DB::table('precise.reject_group as a')
            ->select(
                'a.reject_group_id',
                'a.reject_group_code',
                'a.reject_group_name',
                'a.reject_group_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->get();

        if (count($this->rejectGroup) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->rejectGroup, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->rejectGroup = DB::table('precise.reject_group as a')
            ->where('a.reject_group_id', $id)
            ->select(
                'a.reject_group_id',
                'a.reject_group_code',
                'a.reject_group_name',
                'a.reject_group_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->first();
        if (empty($this->rejectGroup))
            return response()->json("not found", 404);

        return response()->json($this->rejectGroup, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reject_group_code' => 'required|unique:reject_group,reject_group_code',
            'reject_group_name' => 'required',
            'desc'              => 'nullable',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->rejectGroup = DB::table('precise.reject_group')
            ->insert([
                'reject_group_code'         => $request->reject_group_code,
                'reject_group_name'         => $request->reject_group_name,
                'reject_group_description'  => $request->desc,
                'created_by'                => $request->created_by
            ]);

        if ($this->rejectGroup == 0)
            return ResponseController::json(status: "error", message: "error input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reject_group_id'   => 'required|exists:reject_group,reject_group_id',
            'reject_group_code' => 'required',
            'reject_group_name' => 'required',
            'desc'              => 'nullable',
            'reason'            => 'required',
            'updated_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");

            $this->rejectGroup = DB::table('precise.reject_group')
                ->where('reject_group_id', $request->reject_group_id)
                ->update([
                    'reject_group_code'         => $request->reject_group_code,
                    'reject_group_name'         => $request->reject_group_name,
                    'reject_group_description'  => $request->desc,
                    'updated_by'                => $request->updated_by
                ]);

            if ($this->rejectGroup == 0) {
                DB::rollback();
                return ResponseController::json(status: "error", message: "error update data", code: 500);
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
            'reject_group_id'   => 'required|exists:reject_group,reject_group_id',
            'reason'            => 'required',
            'deleted_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->rejectGroup = DB::table('precise.reject_group')
                ->where('reject_group_id', $request->reject_group_id)
                ->delete();

            if ($this->rejectGroup == 0) {
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
                $this->rejectGroup = DB::table('precise.reject_group')
                    ->where('reject_group_code', $value)
                    ->count();
            }
            if ($this->rejectGroup == 0)
                return ResponseController::json(status: "error", message: $this->rejectGroup, code: 404);

            return ResponseController::json(status: "ok", message: $this->rejectGroup, code: 200);
        }
    }
}
