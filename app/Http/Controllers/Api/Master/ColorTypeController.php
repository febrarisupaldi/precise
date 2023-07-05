<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ColorTypeController extends Controller
{
    private $colorType;
    public function index(): JsonResponse
    {
        $this->colorType = DB::table('precise.color_type')
            ->select(
                'color_type_id',
                'color_type_code',
                'color_type_name',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();

        if (count($this->colorType) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->colorType, code: 200);
    }

    public function show($id): JsonResponse
    {
        try {
            $this->colorType = DB::table('precise.color_type')
                ->where('color_type_id', $id)->select(
                    'color_type_id',
                    'color_type_code',
                    'color_type_name'
                )
                ->first();
            if (empty($this->colorType)) {
                return response()->json("not found", 404);
            }
            return response()->json($this->colorType, 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 200);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'color_type_code'   => 'required|unique:color_type,color_type_code',
            'color_type_name'   => 'required',
            'created_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->colorType = DB::table('precise.color_type')
            ->insert([
                'color_type_code'   => $request->color_type_code,
                'color_type_name'   => $request->color_type_name,
                'created_by'        => $request->created_by
            ]);

        if ($this->colorType == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'color_type_id'     => 'required|exists:color_type,color_type_id',
            'color_type_code'   => 'required',
            'color_type_name'   => 'required',
            'updated_by'        => 'required',
            'reason'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->colorType = DB::table('precise.color_type')
                ->where('color_type_id', $request->color_type_id)
                ->update([
                    'color_type_code'   => $request->color_type_code,
                    'color_type_name'   => $request->color_type_name,
                    'updated_by'        => $request->updated_by
                ]);

            if ($this->colorType == 0) {
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
            'color_type_id'     => 'required|exists:color_type,color_type_id',
            'reason'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->colorType = DB::table('precise.color_type')
                ->where('color_type_id', $request->color_type_id)
                ->delete();

            if ($this->colorType == 0) {
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
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == 'code') {
                $this->colorType = DB::table('precise.color_type')
                    ->where('color_type_code', $value)
                    ->count();
            } else if ($type == 'name') {
                $this->colorType = DB::table('precise.color_type')
                    ->where('color_type_name', $value)
                    ->count();
            }

            if ($this->colorType == 0)
                return ResponseController::json(status: "error", message: $this->colorType, code: 404);

            return ResponseController::json(status: "ok", message: $this->colorType, code: 200);
        }
    }
}
