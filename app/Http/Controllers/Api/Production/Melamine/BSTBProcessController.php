<?php

namespace App\Http\Controllers\Api\Production\Melamine;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BSTBProcessController extends Controller
{
    private $process;
    public function getByNumberAndProcessId($number): JsonResponse
    {
        $union = DB::table("precise.bstb as b")
            ->where("b.bstb_retail_num", $number)
            ->select(
                "b.bstb_id",
                "b.bstb_num",
                "b.bstb_item",
                "b.bstb_design",
                "b.bstb_op_packing",
                "d.product_id",
                "b.bstb_packing_ppd_id",
                "b.bstb_packing_ppd_name",
                "b.bstb_packing_qty",
                "c.work_order_hd_id",
                "c.workcenter_id",
                "b.bstb_retail_qty AS qtyBSTB",
                "d.uom_code"
            )
            ->join("precise.work_order as c", "b.bstb_retail_pprh_id", "=", "c.work_order_number")
            ->join("precise.product as d", "b.bstb_packing_ppd_id", "=", "d.product_code")
            ->groupBy("b.bstb_id", "c.work_order_hd_id", "c.workcenter_id");

        $this->process = DB::table("precise.bstb_process as a")
            ->where("b.bstb_num", $number)
            ->where("a.work_process_id", 10)
            ->where("c.work_order_status", "!=", "X")
            ->select(
                "b.bstb_id",
                "b.bstb_num",
                "b.bstb_item",
                "b.bstb_design",
                "b.bstb_op_packing",
                "d.product_id",
                "b.bstb_packing_ppd_id",
                "b.bstb_packing_ppd_name",
                "b.bstb_packing_qty",
                "c.work_order_hd_id",
                "c.workcenter_id",
                DB::raw("IFNULL(SUM(a.trans_qty_kw1),0) AS qtyBSTB"),
                "d.uom_code"
            )
            ->join("precise.bstb as b", "a.bstb_id", "=", "b.bstb_id")
            ->join("precise.work_order as c", "b.bstb_op_packing", "=", "c.work_order_number")
            ->join("precise.product as d", "b.bstb_packing_ppd_id", "=", "d.product_code")
            ->groupBy("b.bstb_id", "c.work_order_hd_id", "c.workcenter_id")
            ->union($union)
            ->get();

        if (count($this->process) == 0) {
            return response()->json(["status" => "error", "message" => "not found"], 404);
        }

        return response()->json(["status" => "ok", "data" => $this->process], 200);
    }
}
