<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductDictionaryController extends Controller
{
    private $productDictionary;
    public function index(): JsonResponse
    {
        $this->productDictionary = DB::table('precise.product_dictionary as a')
            ->select(
                'a.dictionary_id',
                'b.product_code',
                'b.product_name',
                'c.item_code',
                'c.item_name',
                'd.design_code',
                'd.design_name',
                'h.product_variant_code',
                'h.product_variant_name',
                'e.process_code',
                'f.product_brand_name',
                DB::raw("
                    concat(g.color_type_code, ' - ', g.color_type_name) as 'color_type'
                "),
                'a.packing_qty',
                DB::raw("
                    CASE 
                        WHEN a.is_active_sell = '1' THEN 'Aktif'
                        WHEN a.is_active_sell = '0' THEN 'Tidak aktif'
                    ELSE NULL
                    END as 'active_sell',
                    CASE 
                        WHEN a.is_active_production = '1' THEN 'Aktif'
                        WHEN a.is_active_production = '0' THEN 'Tidak aktif'
                        ELSE NULL
                    END as 'active_production'
                "),
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )->leftJoin('precise.product as b', 'a.product_id', '=', 'b.product_id')
            ->leftJoin('precise.product_item as c', 'a.item_id', '=', 'c.item_id')
            ->leftJoin('precise.product_design as d', 'a.design_id', '=', 'd.design_id')
            ->leftJoin('precise.production_process_type as e', 'a.process_type_id', '=', 'e.process_type_id')
            ->leftJoin('precise.product_brand as f', 'a.brand_id', '=', 'f.product_brand_id')
            ->leftJoin('precise.color_type as g', 'a.color_id', '=', 'g.color_type_id')
            ->leftJoin('precise.product_variant as h', 'a.variant_id', '=', 'h.product_variant_id')
            ->get();

        if (count($this->productDictionary) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->productDictionary, code: 200);
    }

    public function show($id): JsonResponse
    {

        $this->productDictionary = DB::table('precise.product_dictionary as a')
            ->where('b.dictionary_id', $id)
            ->select(
                'a.dictionary_id',
                'b.product_id',
                'b.product_code',
                'b.product_name',
                'c.item_id',
                'c.item_code',
                'd.design_id',
                'd.design_code',
                'a.variant_id',
                'h.product_varian_code',
                'h.product_varian_name',
                'e.process_type_id',
                'e.process_code',
                'a.brand_id',
                'f.product_brand_name',
                'a.color_id',
                'g.color_type_code',
                'a.packing_qty',
                'a.is_active_sell',
                'a.is_active_production',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )->leftJoin('precise.product as b', 'a.product_id', '=', 'b.product_id')
            ->leftJoin('precise.product_item as c', 'a.item_id', '=', 'c.item_id')
            ->leftJoin('precise.product_design as d', 'a.design_id', '=', 'd.design_id')
            ->leftJoin('precise.production_process_type as e', 'a.process_type_id', '=', 'e.process_type_id')
            ->leftJoin('precise.product_brand as f', 'a.brand_id', '=', 'f.product_brand_id')
            ->leftJoin('precise.color_type as g', 'a.color_id', '=', 'g.color_type_id')
            ->leftJoin('precise.product_variant as h', 'a.variant_id', '=', 'h.product_variant_id')
            ->first();

        if (empty($this->productDictionary))
            return response()->json("not found", 404);

        return response()->json($this->productDictionary, 200);
    }

    public function showByProductID($id): JsonResponse
    {
        $this->productDictionary = DB::table('precise.product_dictionary as a')
            ->where('b.product_id', $id)
            ->select(
                'a.dictionary_id',
                'b.product_id',
                'b.product_code',
                'b.product_name',
                'c.item_id',
                'c.item_code',
                'd.design_id',
                'd.design_code',
                'a.variant_id',
                'h.product_variant_code',
                'h.product_variant_name',
                'e.process_type_id',
                'e.process_code',
                'a.brand_id',
                'f.product_brand_name',
                'a.color_id',
                'g.color_type_code',
                'a.packing_qty',
                'a.is_active_sell',
                'a.is_active_production',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )->leftJoin('precise.product as b', 'a.product_id', '=', 'b.product_id')
            ->leftJoin('precise.product_item as c', 'a.item_id', '=', 'c.item_id')
            ->leftJoin('precise.product_design as d', 'a.design_id', '=', 'd.design_id')
            ->leftJoin('precise.production_process_type as e', 'a.process_type_id', '=', 'e.process_type_id')
            ->leftJoin('precise.product_brand as f', 'a.brand_id', '=', 'f.product_brand_id')
            ->leftJoin('precise.color_type as g', 'a.color_id', '=', 'g.color_type_id')
            ->leftJoin('precise.product_variant as h', 'a.variant_id', '=', 'h.product_variant_id')
            ->get();

        if (count($this->productDictionary) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->productDictionary, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id'        => 'required|exists:product,product_id',
            'item_id'           => 'required|exists:product_item,item_id',
            'design_id'         => 'required|exists:product_design,design_id',
            'variant_id'        => 'required|exists:product_variant,product_variant_id',
            'process_type_id'   => 'required|exists:production_process_type,process_type_id',
            'color_id'          => 'required|exists:color_type,color_type_id',
            'packing_qty'       => 'required|numeric',
            'created_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->productDictionary = DB::table('precise.product_dictionary')
            ->insert([
                'product_id'        => $request->product_id,
                'item_id'           => $request->item_id,
                'design_id'         => $request->design_id,
                'varian_id'         => $request->variant_id,
                'process_type_id'   => $request->process_type_id,
                'brand_id'          => $request->brand_id,
                'color_id'          => $request->color_id,
                'packing_qty'       => $request->packing_qty,
                'created_by'        => $request->created_by
            ]);
        if ($this->productDictionary == 0)
            return ResponseController::json(status: "error", message: "error input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dictionary_id'     => 'required|exists:product_dictionary,dictionary_id',
            'product_id'        => 'required|exists:product,product_id',
            'item_id'           => 'required|exists:product_item,item_id',
            'design_id'         => 'required|exists:product_design,design_id',
            'variant_id'        => 'required|exists:product_variant,product_variant_id',
            'process_type_id'   => 'required|exists:production_process_type,process_type_id',
            'color_id'          => 'required|exists:color_type,color_type_id',
            'packing_qty'       => 'required|numeric',
            'reason'            => 'required',
            'updated_by'        => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->productDictionary = DB::table('precise.product_dictionary')
                ->where('dictionary_id', $request->dictionary_id)
                ->update([
                    'product_id'        => $request->product_id,
                    'item_id'           => $request->item_id,
                    'design_id'         => $request->design_id,
                    'variant_id'        => $request->variant_id,
                    'process_type_id'   => $request->process_type_id,
                    'brand_id'          => $request->brand_id,
                    'color_id'          => $request->color_id,
                    'packing_qty'       => $request->packing_qty,
                    'updated_by'        => $request->updated_by
                ]);
            if ($this->productDictionary == 0) {
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
            'product_id'    => 'required|exists:product,product_id',
            'deleted_by'    => 'required',
            'reason'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->productDictionary = DB::table('precise.product_dictionary')
                ->where('product_id', $request->product_id)
                ->delete();

            if ($this->productDictionary == 0) {
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
}
