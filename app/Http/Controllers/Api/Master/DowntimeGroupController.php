<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
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


        return response()->json(["status" => "ok", "data" => $this->downtimeGroup], 200);
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

        if (empty($this->downtimeGroup)) {
            return response()->json($this->downtimeGroup, 404);
        }


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
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->downtimeGroup = DB::table('precise.downtime_group')
                ->insert([
                    'downtime_group_code'       => $request->downtime_group_code,
                    'downtime_group_name'       => $request->downtime_group_name,
                    'downtime_group_description' => $request->desc,
                    'created_by'                => $request->created_by
                ]);

            if ($this->downtimeGroup == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed insert data'], 500);
            }

            return response()->json(['status' => 'ok', 'message' => 'success insert data'], 200);
        }
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
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
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
                    return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
                }
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
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
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $this->downtimeGroup = DB::table('precise.downtime_group')
                ->where('downtime_group_id', $request->downtime_group_id)
                ->delete();

            if ($this->downtimeGroup == 0) {
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
                $this->downtimeGroup = DB::table('precise.downtime_group')
                    ->where('downtime_group_code', $value)
                    ->count();
            }

            if ($this->downtimeGroup == 0) {
                return response()->json(['status' => 'error', 'message' => 'not found'], 404);
            }

            return response()->json(['status' => 'ok', 'message' => $this->downtimeGroup], 200);
        }
    }
}
