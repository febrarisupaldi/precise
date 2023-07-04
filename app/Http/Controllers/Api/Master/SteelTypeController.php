<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use Symfony\Component\HttpFoundation\JsonResponse;

class SteelTypeController extends Controller
{
    private $steelType;
    public function index(): JsonResponse
    {
        $this->steelType = DB::table('precise.steel_type')
            ->select(
                'steel_type_id',
                'steel_type_name',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        return response()->json(["status" => "ok", "data" => $this->steelType], 200);
    }

    public function show($id): JsonResponse
    {
        $this->steelType = DB::table('precise.steel_type')
            ->where('steel_type_id', $id)
            ->select(
                'steel_type_id',
                'steel_type_name',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->first();

        if (empty($this->steelType))
            return response()->json("not found", 404);
        return response()->json($this->steelType, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'steel_type_name'   => 'required|unique:steel_type,steel_type_name',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->steelType = DB::table('precise.steel_type')
            ->insert([
                'steel_type_name'   => $request->steel_type_name,
                'created_by'        => $request->created_by
            ]);
        if ($this->steelType == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
        }
        return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'steel_type_id'     => 'required|exists:steel_type,steel_type_id',
            'steel_type_name'   => 'required',
            'is_active'         => 'required|boolean',
            'updated_by'        => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->steelType = DB::table('precise.steel_type')
                ->where('steel_type_id', $request->steel_type_id)
                ->update([
                    'steel_type_name'   => $request->steel_type_name,
                    'is_active'         => $request->is_active,
                    'updated_by'        => $request->updated_by
                ]);
            if ($this->steelType == 0) {
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
            if ($type == "name") {
                $this->steelType = DB::table('precise.steel_type')
                    ->where('steel_type_name', $value)
                    ->count();
            }

            if ($this->steelType == 0)
                return response()->json(['status' => 'error', 'message' => $this->steelType], 404);
            return response()->json(['status' => 'ok', 'message' => $this->steelType], 200);
        }
    }
}
