<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductController extends Controller
{
    private $product;

    public function index(): JsonResponse
    {
        $this->product = DB::table('precise.product as p')
            ->select(
                'product_id',
                'product_code',
                'product_name',
                'product_alias',
                'p.product_group_id',
                'pg.product_group_code',
                'uom_code',
                'product_barcode',
                'product_serial_number',
                'product_std_price',
                'min_stock_level_week',
                'max_stock_level_week',
                'purchase_lead_time',
                'moq',
                'is_included_in_mrp_ml',
                'is_included_in_mrp_pl',
                'is_active',
                'p.created_on',
                'p.created_by',
                'p.updated_on',
                'p.updated_by'
            )
            ->leftJoin('precise.product_group as pg', 'p.product_group_id', '=', 'pg.product_group_id')
            ->get();

        if (count($this->product) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->product, code: 200);
    }

    public function showByProductGroup($id): JsonResponse
    {
        try {
            $id = explode("-", $id);
            $this->product = DB::table('product as a')
                ->select(
                    'product_id',
                    'product_code',
                    'product_name',
                    'product_alias',
                    DB::raw(
                        "
                                concat(c.product_type_code, ' - ', c.product_type_name) as 'Tipe produk',
                                concat(b.product_group_code, ' - ', b.product_group_name)  as 'Group produk'
                            "
                    ),
                    'uom_code',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )->leftJoin('product_group as b', 'a.product_group_id', '=', 'b.product_group_id')
                ->leftJoin('product_type as c', 'b.product_type_id', '=', 'c.product_type_id')
                ->whereIn('b.product_group_id', $id)
                ->orderBy('a.product_code')
                ->get();

            if (count($this->product) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->product, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $this->product = DB::table('precise.product as p')
                ->where('p.product_id', $id)
                ->select(
                    'p.product_id',
                    'p.product_code',
                    'p.product_name',
                    'p.product_alias',
                    'pg.product_group_id',
                    'pg.product_group_code',
                    'pg.product_group_name',
                    'p.uom_code',
                    'p.product_barcode',
                    'p.product_serial_number',
                    'p.product_std_price',
                    'p.is_active',
                    'p.created_on',
                    'p.created_by',
                    'p.updated_on',
                    'p.updated_by'
                )
                ->leftJoin('precise.product_group as pg', 'p.product_group_id', '=', 'pg.product_group_id')
                ->first();

            if (empty($this->product))
                return response()->json("not found", 404);

            return response()->json($this->product, 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function showByProductType($id): JsonResponse
    {
        try {
            $id = explode("-", $$id);
            $this->product = DB::table('precise.product as p')
                ->select(
                    'p.product_id',
                    'p.product_code',
                    'p.product_name',
                    'p.product_alias',
                    'pg.product_group_id',
                    'pg.product_group_code',
                    'pg.product_group_name',
                    'p.product_barcode',
                    'p.product_serial_number',
                    'p.uom_code',
                    'p.product_std_price',
                    'p.is_active',
                    'p.created_on',
                    'p.created_by',
                    'p.updated_on',
                    'p.updated_by'
                )
                ->leftJoin('product_group as pg', 'p.product_group_id', '=', 'pg.product_group_id')
                ->whereIn('pg.product_type_id', $id)
                ->orderBy('p.product_code')
                ->get();
            if (count($this->product) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->product, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function showByProductTypeWithGroup($id): JsonResponse
    {
        try {
            $id = explode("-", $id);
            $this->product = DB::table('precise.product as p')
                ->select(
                    'p.product_id',
                    'p.product_code',
                    'p.product_name',
                    'p.uom_code',
                    'pt.product_type_code',
                    'pt.product_type_name'
                )
                ->leftJoin('product_group as pg', 'p.product_group_id', '=', 'pg.product_group_id')
                ->leftJoin('product_type as pt', 'pg.product_type_id', '=', 'pt.product_type_id')
                ->whereIn('pg.product_type_id', $id)
                ->whereNotIn('pg.product_group_id', [83, 84])
                ->orWhere('pg.product_group_id', [77, 78, 85])
                ->orderBy('p.product_code')
                ->get();

            if (count($this->product) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->product, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function showByCustomer($id): JsonResponse
    {
        try {
            $customer = DB::table('precise.customer')
                ->select(
                    'customer_id',
                    'customer_code',
                    'customer_name',
                    'price_group_code',
                    'disc_group_code',
                    'disc_1',
                    'disc_2',
                    'disc_3'
                );

            $this->product = DB::table('precise.product as prod')
                ->select(
                    'prod.product_id',
                    'prod.product_code',
                    'prod.product_name',
                    'prod.uom_code',
                    'pldt.price_idr',
                    'cust.disc_1',
                    'cust.disc_2',
                    'cust.disc_3',
                    'cust.price_group_code',
                    'plhd.price_seq AS price_group_seq'
                )
                // ->leftJoin(DB::raw("({$customer->toSql()}) as cust"), "", "=", "")
                ->leftJoinSub($customer, 'cust', function ($join) use ($id) {
                    $join->on('cust.customer_id', '=', DB::raw("{$id}"));
                })
                ->leftJoin('precise.price_list_hd as plhd', function ($join) {
                    $join->on('cust.price_group_code', '=', 'plhd.price_group_code');
                    $join->on('plhd.price_status', '!=', DB::raw("'N'"));
                })
                ->leftJoin('precise.price_list_dt as pldt', function ($join) {
                    $join->on('cust.price_group_code', '=', 'pldt.price_group_code');
                    $join->on('plhd.price_seq', '=', 'pldt.price_group_seq');
                    $join->on('prod.product_code', '=', 'pldt.product_code');
                })
                ->mergeBindings($customer)
                ->get();

            if (count($this->product) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->product, code: 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function showByWorkcenter($id): JsonResponse
    {
        try {
            $value = explode("-", $id);
            $this->product = DB::table('precise.product_workcenter as pw')
                ->whereIn('pw.workcenter_id', $value)
                ->select(
                    'pw.product_workcenter_id',
                    'p.product_id',
                    'p.product_code',
                    'p.product_name',
                    'p.uom_code',
                    'pw.workcenter_id',
                    'w.workcenter_code',
                    'w.workcenter_name'
                )
                ->leftJoin('precise.product as p', 'pw.product_id', '=', 'p.product_id')
                ->leftJoin('precise.workcenter as w', 'pw.workcenter_id', '=', 'w.workcenter_id')
                ->get();

            if (count($this->product) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->product, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function showByWarehouse(Request $request): JsonResponse
    {
        $warehouse = $request->get('warehouse');
        $product_type = $request->get('product_type');

        $validator = Validator::make($request->all(), [
            "year"          => 'required|date_format:Y',
            "warehouse"     => 'required',
            "start"         => 'required|date_format:Y-m-d|before_or_equal:end',
            "end"           => 'required|date_format:Y-m-d|after_or_equal:start',
            "product_type"  => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            $warehouses = explode("-", $warehouse);
            $product_types = explode("-", $product_type);

            $union = DB::table("precise.warehouse_trans_hd as hd")
                ->whereIn("hd.trans_from", $warehouses)
                ->whereBetween("hd.trans_date", [$request->start, $request->end])
                ->select(DB::raw("DISTINCT('product_id')"))
                ->join('precise.warehouse_trans_dt as dt', 'hd.trans_hd_id', '=', 'dt.trans_hd_id');

            $subQuery = DB::table("precise.stock_posting_yearly as spy")
                ->where('spy.posting_year', $request->year)
                ->whereIn('spy.warehouse_id', $warehouses)
                ->union($union);

            $this->product = DB::table(DB::raw("({$subQuery->toSql()})base"))
                ->mergeBindings($subQuery)
                ->whereIn("pg.product_type_id", $product_types)
                ->where("p.is_active", 1)
                ->leftJoin("precise.product as p", "base.product_id", "=", "p.product_id")
                ->leftJoin("precise.product_group as pg", "p.product_group_id", "=", "pg.product_group_id")
                ->leftJoin("precise.product_type as pt", "pg.product_type_id", "=", "pt.product_type_id")
                ->get();

            if (count($this->product) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->product, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'product_code'              => 'required|unique:product,product_code',
            'product_name'              => 'required',
            'product_alias'             => 'nullable',
            'product_group_id'          => 'required|exists:product_group,product_group_id',
            'product_barcode'           => 'required',
            'product_serial_number'     => 'required',
            'uom_code'                  => 'required|exists:uom,uom_code',
            'product_std_price'         => 'nullable|numeric',
            'created_by'                => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            $id = DB::table('precise.product')
                ->insertGetId([
                    'product_code'          => $data['product_code'],
                    'product_name'          => $data['product_name'],
                    'product_alias'         => $data['product_alias'],
                    'product_group_id'      => $data['product_group_id'],
                    'product_barcode'       => $data['product_barcode'],
                    'product_serial_number' => $data['product_serial_number'],
                    'uom_code'              => $data['uom_code'],
                    'product_std_price'     => $data['product_std_price'],
                    'created_by'            => $data['created_by']
                ]);

            if ($id < 1) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error input data", code: 500);
            }

            $dictionary = $data["product_dictionary"][0];
            $validator = Validator::make($dictionary, [
                'item_id'           => 'required|exists:product_item,item_id',
                'design_id'         => 'required|exists:product_design,design_id',
                'variant_id'        => 'required|exists:product_variant,product_variant_id',
                'process_type_id'   => 'required|exists:production_process_type,process_type_id',
                'color_id'          => 'required|exists:color_gradation,color_id',
                'brand_id'          => 'required|exists:product_brand,product_brand_id',
                'packing_qty'       => 'required|numeric',
                'created_by'        => 'required'
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
            }

            $this->product = DB::table("precise.product_dictionary")
                ->insert([
                    "product_id"        => $id,
                    "item_id"           => $dictionary["item_id"],
                    "design_id"         => $dictionary["design_id"],
                    "variant_id"        => $dictionary["variant_id"],
                    "process_type_id"   => $dictionary["process_type_id"],
                    "color_id"          => $dictionary["color_id"],
                    "brand_id"          => $dictionary["brand_id"],
                    "packing_qty"       => $dictionary["packing_qty"],
                    "created_by"        => $dictionary["created_by"]
                ]);

            if ($this->product == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error input data", code: 500);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success input data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'product_id'                => 'required|exists:product,product_id',
            'product_code'              => 'required',
            'product_name'              => 'required',
            'product_alias'             => 'nullable',
            'product_group_id'          => 'required|exists:product_group,product_group_id',
            'product_barcode'           => 'required',
            'product_serial_number'     => 'required',
            'uom_code'                  => 'required|exists:uom,uom_code',
            'product_std_price'         => 'nullable|numeric',
            'updated_by'                => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update", $data);
            $this->product = DB::table('precise.product')
                ->where('product_id', $data['product_id'])
                ->update([
                    'product_code'          => $data['product_code'],
                    'product_name'          => $data['product_name'],
                    'product_alias'         => $data['product_alias'],
                    'product_group_id'      => $data['product_group_id'],
                    'product_barcode'       => $data['product_barcode'],
                    'product_serial_number' => $data['product_serial_number'],
                    'uom_code'              => $data['uom_code'],
                    'product_std_price'     => $data['product_std_price'],
                    'updated_by'            => $data['updated_by']
                ]);

            $dictionary = $data["product_dictionary"][0];

            $validator = Validator::make($dictionary, [
                'item_id'           => 'required|exists:product_item,item_id',
                'design_id'         => 'required|exists:product_design,design_id',
                'variant_id'        => 'required|exists:product_variant,product_variant_id',
                'process_type_id'   => 'required|exists:production_process_type,process_type_id',
                'color_id'          => 'required|exists:color_gradation,color_id',
                'brand_id'          => 'required|exists:product_brand,product_brand_id',
                'packing_qty'       => 'required|numeric',
                'updated_by'        => 'required'
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
            }

            $this->product = DB::table("precise.product_dictionary")
                ->where("product_id", $dictionary["product_id"])
                ->update([
                    "item_id"           => $dictionary["item_id"],
                    "design_id"         => $dictionary["design_id"],
                    "variant_id"        => $dictionary["variant_id"],
                    "process_type_id"   => $dictionary["brand_id"],
                    "color_id"          => $dictionary["color_id"],
                    "packing_qty"       => $dictionary["packing_qty"],
                    "created_by"        => $dictionary["created_by"]
                ]);

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
            'product_id'    =>  'required|exists:product,product_id',
            'reason'        =>  'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->product = DB::table('precise.product')
                ->where('product_id', $request->product_id)
                ->delete();

            if ($this->product == 0) {
                DB::rollback();
                return ResponseController::json(status: "error", message: "failed delete data", code: 500);
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: "success delete data", code: 204);
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
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "code") {
                $this->product = DB::table('precise.product')
                    ->where('product_code', $value)
                    ->count();
            }

            if ($this->product == 0)
                return ResponseController::json(status: "error", message: $this->product, code: 404);

            return ResponseController::json(status: "ok", message: $this->product, code: 200);
        }
    }
}
