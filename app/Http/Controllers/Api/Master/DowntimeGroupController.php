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

class DowntimeGroupController extends Controller
{
    private $downtimeGroup;
    public function index(): JsonResponse
    {
        $this->downtimeGroup = DB::table('precise.downtime_group as a')
            ->select(
                'a.downtime_group_id',
                'a.downtime_group_code',
                'a.downtime_group_name',
                'a.downtime_group_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->get();


        if (count($this->downtimeGroup) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->downtimeGroup, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->downtimeGroup = DB::table('precise.downtime_group as a')
            ->where('downtime_group_id', $id)
            ->select(
                'a.downtime_group_id',
                'a.downtime_group_code',
                'a.downtime_group_name',
                'a.downtime_group_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->first();

        if (empty($this->downtimeGroup))
            return response()->json($this->downtimeGroup, 404);


        return response()->json($this->downtimeGroup, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'downtime_group_code'   => 'required|unique:downtime_group,downtime_group_code',
            'downtime_group_name'   => 'required',
            'desc'                  => 'nullable',
            'created_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->downtimeGroup = DB::table('precise.downtime_group')
            ->insert([
                'downtime_group_code'       => $request->downtime_group_code,
                'downtime_group_name'       => $request->downtime_group_name,
                'downtime_group_description' => $request->desc,
                'created_by'                => $request->created_by
            ]);

        if ($this->downtimeGroup == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'downtime_group_id'     => 'required|exists:downtime_group,downtime_group_id',
            'downtime_group_code'   => 'required',
            'downtime_group_name'   => 'required',
            'desc'                  => 'nullable',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->downtimeGroup = DB::table('precise.downtime_group')
                ->where('downtime_group_id', $request->downtime_group_id)
                ->update([
                    'downtime_group_code'           => $request->downtime_group_code,
                    'downtime_group_name'           => $request->downtime_group_name,
                    'downtime_group_description'    => $request->desc,
                    'updated_by'                    => $request->updated_by
                ]);

            if ($this->downtimeGroup == 0) {
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
            'downtime_group_id' => 'required|exists:downtime_group,downtime_group_id',
            'reason'            => 'required',
            'deleted_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->downtimeGroup = DB::table('precise.downtime_group')
                ->where('downtime_group_id', $request->downtime_group_id)
                ->delete();

            if ($this->downtimeGroup == 0) {
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
                $this->downtimeGroup = DB::table('precise.downtime_group')
                    ->where('downtime_group_code', $value)
                    ->count();
            }

            if ($this->downtimeGroup == 0)
                return ResponseController::json(status: "error", message: $this->downtimeGroup, code: 404);

            return ResponseController::json(status: "ok", message: $this->downtimeGroup, code: 200);
        }
    }
}
