<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WarehouseRackOutController extends Controller
{
    private $warehouseRackOut;

    public function getByDate($date): JsonResponse
    {
        $this->warehouseRackOut = DB::table("precise.warehouse_rack_out_hd")
            ->where("out_date", $date)
            ->select(
                "warehouse_rack_out_hd_id",
                "cart_number",
                "out_date",
                "out_description",
                "cart_status",
                "user_id"
            )
            ->get();
        if (count($this->warehouseRackOut) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->warehouseRackOut, code: 200);
    }

    public function getDetailByHeaderId($id)
    {
        $this->warehouseRackOut = DB::table("precise.warehouse_rack_out_dt", "dt")
            ->where("dt.warehouse_rack_out_hd_id", $id)
            ->select(
                "dt.warehouse_rack_out_dt_id",
                "dt.warehouse_rack_out_hd_id",
                "hd.cart_number",
                "dt.product_id",
                "i.item_code",
                "d.design_code",
                "p.product_name",
                "dt.qty as cart_qty"
            )
            ->leftJoin("precise.warehouse_rack_out_hd as hd", "dt.warehouse_rack_out_hd_id", "=", "hd.warehouse_rack_out_hd_id")
            ->leftJoin("precise.product as p", "dt.product_id", "=", "p.product_id")
            ->leftJoin("precise.product_dictionary as pd", "dt.product_id", "=", "pd.product_id")
            ->leftJoin("precise.product_item as i", "pd.item_id", "=", "i.item_id")
            ->leftJoin("precise.product_design as d", "pd.design_id", "=", "d.design_id")
            ->get();

        if (count($this->warehouseRackOut) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->warehouseRackOut, code: 200);
    }

    public function createHeader(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "out_date"      => ["required", "date_format:Y-m-d"],
            "desc"      => ["nullable"],
            "cart_status"   => ["required"],
            "user_id"       => ["required", "exists:users,user_id"]
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        try {
            $uuid = Str::uuid();
            $this->warehouseRackOut = DB::table("precise.warehouse_rack_out_hd")
                ->insertGetId([
                    "cart_number"       => $uuid,
                    "out_date"          => $request->out_date,
                    "out_description"   => $request->desc,
                    "cart_status"       => $request->cart_status,
                    "user_id"           => $request->user_id
                ]);


            if ($this->warehouseRackOut == 0)
                return ResponseController::json(status: "error", message: "server error", code: 500);

            return ResponseController::json(status: "ok", message: "success input data", id: $this->warehouseRackOut, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function createDetail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "warehouse_rack_out_hd_id"  => ["required", "exists:warehouse_rack_out_hd,warehouse_rack_out_hd_id"],
            "product_id"                => ["required", "exists:product,product_id"],
            "qty"                       => ["required"]
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        try {
            $this->warehouseRackOut = DB::table("precise.warehouse_rack_out_dt")
                ->insert([
                    "warehouse_rack_out_hd_id"  => $request->warehouse_rack_out_hd_id,
                    "product_id"                => $request->product_id,
                    "qty"                       => $request->qty
                ]);

            if ($this->warehouseRackOut == 0)
                return ResponseController::json(status: "error", message: "server error", code: 500);

            return ResponseController::json(status: "ok", message: "success input data", code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function updateCartStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "warehouse_rack_out_hd_id"  => ["required", "exists:warehouse_rack_out_hd,warehouse_rack_out_hd_id"],
            "cart_status"               => ["required"]
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        try {
            $this->warehouseRackOut = DB::table("precise.warehouse_rack_out_hd")
                ->where("warehouse_rack_out_hd_id", $request->warehouse_rack_out_hd_id)
                ->update([
                    "cart_status"   => $request->cart_status
                ]);

            if ($this->warehouseRackOut == 0)
                return ResponseController::json(status: "error", message: "server error", code: 500);

            return ResponseController::json(status: "ok", message: "success update data", code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
