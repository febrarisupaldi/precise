<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductVariantController extends Controller
{
    private $productVariant;
    public function index(): JsonResponse
    {
        $this->productVariant = DB::table('precise.product_variant')
            ->select(
                'product_variant_id',
                'product_variant_code',
                'product_variant_name',
                'product_variant_description',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->get();
        if (count($this->productVariant) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->productVariant, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->productVariant = DB::table('precise.product_variant')
            ->where('product_variant_id', $id)
            ->select(
                'product_variant_id',
                'product_variant_code',
                'product_variant_name',
                'product_variant_description',
                'is_active',
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )
            ->first();

        if (empty($this->productVariant))
            return response()->json('not found', 404);
        return response()->json($this->productVariant, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_variant_code'  =>  'required|unique:product_variant,product_variant_code',
            'product_variant_name'  =>  'required',
            'desc'                  =>  'required',
            'created_by'            =>  'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        $this->productVariant = DB::table('precise.product_variant')
            ->insert([
                'product_variant_code'          => $request->product_variant_code,
                'product_variant_name'          => $request->product_variant_name,
                'product_variant_description'   => $request->desc,
                'created_by'                    => $request->created_by
            ]);

        if ($this->productVariant == 0)
            return ResponseController::json(status: "error", message: "error input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_variant_id'    =>  'required|exists:product_variant,product_variant_id',
            'product_variant_code'  =>  'required',
            'product_variant_name'  =>  'required',
            'desc'                  =>  'required',
            'updated_by'            =>  'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->productVariant = DB::table('precise.product_variant')
                ->where('product_variant_id', $request->product_variant_id)
                ->update([
                    'product_variant_code'          => $request->product_variant_code,
                    'product_variant_name'          => $request->product_variant_name,
                    'product_variant_description'   => $request->desc,
                    'updated_by'                    => $request->updated_by
                ]);

            if ($this->productVariant == 0) {
                DB::rollBack();
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
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "code") {
                $this->productVariant = DB::table('precise.product_varian')
                    ->where('product_variant_code', $value)
                    ->count();
            } else if ($type == "name") {
                $this->productVariant = DB::table('precise.product_varian')
                    ->where('product_variant_name', $value)
                    ->count();
            }

            if ($this->productVariant == 0)
                return ResponseController::json(status: "error", message: $this->productVariant, code: 404);

            return ResponseController::json(status: "ok", message: $this->productVariant, code: 200);
        }
    }
}
