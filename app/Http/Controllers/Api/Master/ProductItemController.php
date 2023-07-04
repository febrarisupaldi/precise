<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductItemController extends Controller
{
    private $productItem;

    public function index(): JsonResponse
    {
        $this->productItem = DB::table("precise.product_item as i")
            ->select(
                'i.item_id',
                'i.item_code',
                'i.item_name',
                'i.item_name_en',
                'i.item_alias',
                'i.kind_code',
                's.series_name',
                'i.brand_id',
                'i.is_active_sell',
                'i.is_active_production',
                'i.created_on',
                'i.created_by',
                'i.updated_on',
                'i.updated_by'
            )
            ->leftJoin('precise.product_series as s', 'i.series_id', '=', 's.series_id')
            ->get();

        return response()->json(["status" => "ok", "data" => $this->productItem], 200);
    }

    public function showByKindCode($id): JsonResponse
    {
        $id = explode("-", $id);
        $this->productItem = DB::table('precise.product_item as a')
            ->whereIn('kind_code', $id)
            ->select(
                'item_id',
                'item_code as Kode item',
                'item_name as Nama item',
                'item_name_en',
                'item_alias as Nama alias',
                DB::raw("concat(product_kind_code,'-', product_kind_name) as `Jenis item`"),
                'c.series_name as Nama seri produk',
                DB::raw("case `a`.`is_active_sell`
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif'
                end as 'Status aktif jual'"),
                DB::raw("case `a`.`is_active_production`
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif'
                end as 'Status aktif produksi'"),
                'brand_id',
                'd.product_brand_name',
                'a.created_on as Tanggal input',
                'a.created_by as User input',
                'a.updated_on as Tanggal update',
                'a.updated_by as User update'
            )
            ->leftJoin('precise.product_kind as b', 'a.kind_code', '=', 'b.product_kind_code')
            ->leftJoin('precise.product_series as c', 'a.series_id', '=', 'c.series_id')
            ->leftJoin('precise.product_brand as d', 'a.brand_id', '=', 'd.product_brand_id')
            ->get();

        if (count($this->productItem) == 0)
            return response()->json(["status" => "ok", "data" => "not found"], 404);
        return response()->json(["status" => "ok", "data" => $this->productItem], 200);
    }

    public function show($id): JsonResponse
    {
        $this->productItem = DB::table('precise.product_item')
            ->where('item_id', $id)
            ->select(
                'item_id',
                'item_code',
                'item_name',
                'item_name_en',
                'item_alias',
                'kind_code',
                'series_id',
                'brand_id',
                'is_active_sell',
                'is_active_production'
            )
            ->first();

        if (empty($this->productItem))
            return response()->json($this->productItem, 404);

        return response()->json($this->productItem, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_code'     => 'required|unique:product_item,item_code',
            'item_name'     => 'required',
            'item_name_en'  => 'nullable',
            'item_alias'    => 'nullable',
            'kind_code'     => 'required|exists:product_kind,product_kind_code',
            'series_id'     => 'required|exists:product_series,series_id',
            'brand_id'      => 'nullable|exists:product_brand,product_brand_id',
            'created_by'    => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->productItem = DB::table('precise.product_item')
            ->insert([
                'item_code'     => $request->item_code,
                'item_name'     => $request->item_name,
                'item_name_en'  => $request->item_name_en,
                'item_alias'    => $request->item_alias,
                'kind_code'     => $request->kind_code,
                'series_id'     => $request->series_id,
                'brand_id'      => $request->brand_id,
                'created_by'    => $request->created_by
            ]);

        if ($this->productItem == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
        }
        return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_id'               => 'required|exists:product_item,item_id',
            'item_code'             => 'required',
            'item_name'             => 'required',
            'item_name_en'          => 'nullable',
            'item_alias'            => 'nullable',
            'kind_code'             => 'required|exists:product_kind,product_kind_code',
            'series_id'             => 'required|exists:product_series,series_id',
            'is_active_sell'        => 'required',
            'is_active_production'  => 'required',
            'brand_id'              => 'nullable|exists:product_brand,product_brand_id',
            'updated_by'            => 'required',
            'reason'                => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->productItem = DB::table('precise.product_item')
                ->where('item_id', $request->item_id)
                ->update([
                    'item_code'             => $request->item_code,
                    'item_name'             => $request->item_name,
                    'item_name_en'          => $request->item_name_en,
                    'item_alias'            => $request->item_alias,
                    'kind_code'             => $request->kind_code,
                    'series_id'             => $request->series_id,
                    'is_active_sell'        => $request->is_active_sell,
                    'is_active_production'  => $request->is_active_production,
                    'brand_id'              => $request->brand_id,
                    'updated_by'            => $request->updated_by
                ]);
            if ($this->productItem == 0) {
                DB::rollback();
                return response()->json(['status' => 'error', 'message' => 'server error'], 500);
            }

            $this->productItem = DB::table("precise.tmp_barcode")
                ->where("item_code", $request->item_code)
                ->update([
                    'item_description_id'   => $request->item_name,
                    'item_description_en'   => $request->item_name_en,
                    'brand_id'              => $request->brand_id
                ]);

            if ($this->productItem == 0) {
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

    public function destroy(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'item_id'       => 'required|exists:product_item,item_id',
            'deleted_by'    => 'required',
            'reason'        => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->productItem = DB::table('precise.product_item')
                ->where('item_id', $request->item_id)->delete();

            if ($this->productItem == 0) {
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
            'type' => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "code") {
                $this->productItem = DB::table('precise.product_item')->where([
                    'item_code' => $value
                ])->count();
            } else if ($type == "alias") {
                $this->productItem = DB::table('precise.product_item')->where([
                    'item_alias' => $value
                ])->count();
            }
            if ($this->productItem == 0)
                return response()->json(['status' => 'error', 'message' => $this->productItem], 404);
            return response()->json(['status' => 'ok', 'message' => $this->productItem], 200);
        }
    }
}
