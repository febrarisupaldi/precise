<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductCustomerController extends Controller
{
    private $productCustomer;
    public function index(): JsonResponse
    {
        $this->productCustomer = DB::table('precise.product_customer as a')
            ->select(
                'a.product_customer_id',
                'p.product_code',
                'p.product_name',
                'c.customer_code',
                'c.customer_name',
                'a.loss_tolerance',
                'a.moq',
                'a.oem_material_supply_type',
                DB::raw("case a.is_return_runner 
                    when 0 then 'Tidak'
                    when 1 then 'Ya' 
                end as 'is_return_runner'"),
                DB::raw("case a.is_order_qty_include_reject 
                    when 0 then 'Tidak'
                    when 1 then 'Ya' 
                end as 'is_order_qty_include_reject'"),
                DB::raw("case a.is_active 
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif' 
                end as 'Status aktif order'"),
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('precise.product as p', 'a.product_id', '=', 'p.product_id')
            ->leftJoin('precise.customer as c', 'a.customer_id', '=', 'c.customer_id')
            ->get();

        if (count($this->productCustomer) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->productCustomer, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->productCustomer = DB::table('precise.product_customer as a')
            ->where('product_customer_id', $id)
            ->select(
                'a.product_customer_id',
                'a.product_id',
                'p.product_code',
                'p.product_name',
                'a.customer_id',
                'c.customer_code',
                'c.customer_name',
                'a.loss_tolerance',
                'a.moq',
                'a.oem_material_supply_type',
                'a.is_return_runner',
                'a.is_order_qty_include_reject',
                'a.is_active',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('precise.product as p', 'a.product_id', '=', 'p.product_id')
            ->leftJoin('precise.customer as c', 'a.customer_id', '=', 'c.customer_id')
            ->first();

        if (empty($this->productCustomer))
            return response()->json("not found", 404);

        return response()->json($this->productCustomer, 200);
    }

    public function showCustomer($id): JsonResponse
    {
        $this->productCustomer = DB::table('precise.product_customer as a')
            ->where("a.customer_id", $id)
            ->where("a.is_active", 1)
            ->select(
                'a.product_customer_id',
                'a.product_id',
                'p.product_code',
                'p.product_name',
                'p.uom_code',
                'pldt.price_idr',
                'a.loss_tolerance',
                'a.moq',
                'a.oem_material_supply_type',
                DB::raw("case a.is_return_runner 
                    when 0 then 'Tidak'
                    when 1 then 'Ya' 
                end as 'is_return_runner'"),
                DB::raw("case a.is_order_qty_include_reject 
                    when 0 then 'Tidak'
                    when 1 then 'Ya' 
                end as 'is_order_qty_include_reject'"),
                DB::raw("case a.is_active 
                when 0 then 'Tidak aktif'
                when 1 then 'Aktif' 
            end as 'Status aktif order'")
            )->leftJoin('precise.product as p', 'a.product_id', '=', 'p.product_id')
            ->leftJoin('precise.customer as c', 'a.customer_id', '=', 'c.customer_id')
            ->leftJoin('precise.price_list_hd as plhd', function ($join) {
                $join
                    ->on('plhd.price_group_code', '=', 'c.price_group_code')
                    ->where('plhd.price_status', '=', 'A');
            })
            ->leftJoin('precise.price_list_dt as pldt', function ($join) {
                $join
                    ->on('pldt.price_group_code', '=', 'plhd.price_group_code')
                    ->on('pldt.price_group_seq', '=', 'plhd.price_seq')
                    ->on('p.product_code', '=', 'pldt.product_code');
            })
            ->get();

        if (count($this->productCustomer) == 0)
            return response()->json(["status" => "error", "message" => "not found"], 404);

        return response()->json(["status" => "ok", "data" => $this->productCustomer], 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id'                    => 'required|exists:product,product_id',
            'customer_id'                   => 'required|exists:customer,customer_id',
            'loss_tolerance'                => 'nullable',
            'moq'                           => 'nullable',
            'oem_material_supply_type'      => 'required|exists:oem_material_supply_type,oem_material_supply_type',
            'is_return_runner'              => 'nullable',
            'is_order_qty_include_reject'   => 'nullable',
            'created_by'                    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->productCustomer = DB::table('precise.product_customer')
            ->insert([
                'product_id'                    => $request->product_id,
                'customer_id'                   => $request->customer_id,
                'loss_tolerance'                => $request->loss_tolerance,
                'moq'                           => $request->moq,
                'oem_material_supply_type'      => $request->material_supply_type,
                'is_return_runner'              => $request->is_return_runner,
                'is_order_qty_include_reject'   => $request->is_order_qty_include_reject,
                'created_by'                    => $request->created_by
            ]);

        if ($this->productCustomer == 0)
            return ResponseController::json(status: "error", message: "error input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_customer_id'           => 'required|exists:product_customer,product_customer_id',
            'product_id'                    => 'required|exists:product,product_id',
            'customer_id'                   => 'required|exists:customer,customer_id',
            'loss_tolerance'                => 'nullable',
            'moq'                           => 'nullable',
            'oem_material_supply_type'      => 'nullable',
            'is_return_runner'              => 'nullable',
            'is_order_qty_include_reject'   => 'nullable',
            'is_active'                     => 'required|boolean',
            'updated_by'                    => 'required',
            'reason'                        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->productCustomer = DB::table('precise.product_customer')
                ->where('product_customer_id', $request->product_customer_id)
                ->update([
                    'product_id'                    => $request->product_id,
                    'customer_id'                   => $request->customer_id,
                    'loss_tolerance'                => $request->loss_tolerance,
                    'moq'                           => $request->moq,
                    'oem_material_supply_type'      => $request->material_supply_type,
                    'is_return_runner'              => $request->is_return_runner,
                    'is_order_qty_include_reject'   => $request->is_order_qty_include_reject,
                    'is_active'                     => $request->is_active,
                    'updated_by'                    => $request->created_by
                ]);

            if ($this->productCustomer == 0) {
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

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_customer_id'   => 'required|exists:product_customer,product_customer_id',
            'deleted_by'            => 'required',
            'reason'                => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->productCustomer = DB::table('precise.product_customer')
                ->where('product_customer_id', $request->product_customer_id)
                ->delete();

            if ($this->productCustomer == 0) {
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
        $product = $request->get('product_id');
        $customer = $request->get('customer_id');

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:product,product_id',
            'customer_id' => 'required|exists:customer,customer_id'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            $this->productCustomer = DB::table('product_customer')->where([
                'product_id' => $product,
                'customer_id' => $customer
            ])->count();

            if ($this->productCustomer == 0)
                return ResponseController::json(status: "error", message: $this->productCustomer, code: 404);

            return ResponseController::json(status: "ok", message: $this->productCustomer, code: 200);
        }
    }
}
