<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductTypeController extends Controller
{
    private $productType;
    public function index(): JsonResponse
    {
        $this->productType = DB::table('precise.product_type')
            ->select(
                'product_type_id',
                'product_type_code',
                'product_type_name',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        if (count($this->productType) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->productType, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->productType = DB::table('precise.product_type')
            ->where('product_type_id', $id)
            ->select(
                'product_type_id',
                'product_type_code',
                'product_type_name'
            )->first();

        if (empty($this->productType))
            return response()->json($this->productType, 404);
        return response()->json($this->productType, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_type_code' => 'required|unique:product_type,product_type_code',
            'product_type_name' => 'required',
            'created_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->productType = DB::table('precise.product_type')
            ->insert([
                'product_type_code'  => $request->product_type_code,
                'product_type_name'  => $request->product_type_name,
                'created_by'         => $request->created_by
            ]);

        if ($this->productType == 0)
            return ResponseController::json(status: "error", message: "error input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_type_id'   => 'required|exists:product_type,product_type_id',
            'product_type_code' => 'required',
            'product_type_name' => 'required',
            'updated_by'        => 'required',
            'reason'            => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            DB::beginTransaction();
            DBController::reason($request, "update");
            $this->productType = DB::table('precise.product_type')
                ->where('product_type_id', $request->product_type_id)
                ->update([
                    'product_type_code' => $request->product_type_code,
                    'product_type_name' => $request->product_type_name,
                    'updated_by'        => $request->updated_by
                ]);

            if ($this->productType == 0) {
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

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required_with:value',
            'value' => 'required_with:type'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "code") {
                $this->productType = DB::table('precise.product_type')
                    ->where('product_type_code', $value)
                    ->count();
            } else if ($type == "name") {
                $this->productType = DB::table('precise.product_type')
                    ->where('product_type_name', $value)
                    ->count();
            }

            if ($this->productType == 0)
                return ResponseController::json(status: "error", message: $this->productType, code: 404);

            return ResponseController::json(status: "ok", message: $this->productType, code: 200);
        }
    }
}
