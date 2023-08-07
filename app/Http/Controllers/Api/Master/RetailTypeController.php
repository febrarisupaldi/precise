<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class RetailTypeController extends Controller
{
    private $retailType;
    public function index(): JsonResponse
    {
        $this->retailType = DB::table('precise.retail_type')
            ->select(
                'retail_type_id',
                'retail_type_code',
                'retail_type_description',
                'GroupCodeOnProint',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        if ($this->retailType)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->retailType, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->retailType = DB::table('precise.retail_type')
            ->where('retail_type_id', $id)
            ->select(
                'retail_type_code',
                'retail_type_description'
            )
            ->first();


        if (empty($this->retailType))
            return response()->json("not found", 404);
        return response()->json($this->retailType, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'retail_type_code'  => 'required|unique:retail_type,retail_type_code',
            'desc'              => 'required',
            'created_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->retailType = DB::table('precise.retail_type')
            ->insert([
                'retail_type_code'          => $request->retail_type_code,
                'retail_type_description'   => $request->desc,
                'created_by'                => $request->created_by
            ]);

        if ($this->retailType == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'retail_type_id'    => 'required|exists:retail_type,retail_type_id',
            'retail_type_code'  => 'required',
            'desc'              => 'required',
            'reason'            => 'required',
            'updated_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->retailType = DB::table('precise.retail_type')
                ->where('retail_type_id', $request->retail_type_id)
                ->update([
                    'retail_type_code'          => $request->retail_type_code,
                    'retail_type_description'   => $request->desc,
                    'updated_by'                => $request->updated_by
                ]);

            if ($this->retailType == 0) {
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

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "code")
                $this->retailType = DB::table('precise.retail_type')
                    ->where('retail_type_code', $value)
                    ->count();
            elseif ($type == "desc")
                $this->retailType = DB::table('precise.retail_type')
                    ->where('retail_type_description', $value)
                    ->count();
            if ($this->retailType == 0)
                return ResponseController::json(status: "error", message: $this->retailType, code: 404);

            return ResponseController::json(status: "ok", message: $this->retailType, code: 200);
        }
    }
}
