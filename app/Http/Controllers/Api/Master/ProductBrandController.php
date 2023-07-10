<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductBrandController extends Controller
{
    private $productBrand;
    public function index(): JsonResponse
    {
        $this->productBrand = DB::table('precise.product_brand')
            ->select(
                'product_brand_id',
                'product_brand_name',
                DB::raw("
                    case is_active 
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'is_active'
                "),
                'created_on',
                'created_by',
                'updated_on',
                'updated_by'
            )->get();
        if (count($this->productBrand) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->productBrand, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->productBrand = DB::table('precise.product_brand')
            ->where('product_brand_id', $id)
            ->select('product_brand_name', 'is_active')
            ->first();
        if (empty($this->productBrand))
            return response()->json("not found", 404);

        return response()->json($this->productBrand, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_brand_name'    => 'required',
            'created_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        $this->productBrand = DB::table('precise.product_brand')
            ->insert([
                'product_brand_name'    => $request->product_brand_name,
                'created_by'            => $request->created_by
            ]);

        if ($this->productBrand == 0)
            return ResponseController::json(status: "error", message: "error input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_brand_id'      => 'required|exists:product_brand,product_brand_id',
            'product_brand_name'    => 'required',
            'is_active'             => 'required|boolean',
            'updated_by'            => 'required',
            'reason'                => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->productBrand = DB::table('precise.product_brand')
                ->where('product_brand_id', $request->product_brand_id)
                ->update([
                    'is_active'             => $request->is_active,
                    'product_brand_name'    => $request->product_brand_name,
                    'updated_by'            => $request->updated_by
                ]);

            if ($this->productBrand == 0) {
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
            'type'  => 'required',
            'value' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "name") {
                $this->productBrand = DB::table('precise.product_brand')
                    ->where('product_brand_name', $value)
                    ->count();
            }

            if ($this->productBrand == 0)
                return ResponseController::json(status: "error", message: $this->productBrand, code: 404);

            return ResponseController::json(status: "ok", message: $this->productBrand, code: 200);
        }
    }
}
