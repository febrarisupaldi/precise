<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseRackController extends Controller
{
    private $rack;
    public function getByCode($code)
    {
        $this->rack = DB::table("precise.warehouse_rack", "wr")
            ->where("wr.rack_code", $code)
            ->select(
                "wr.rack_id",
                "wr.rack_code",
                "wr.rack_width",
                "wr.rack_lane",
                "wh.warehouse_id",
                "wh.warehouse_code",
                "wh.warehouse_name",
                "wr.rack_description",
                "wr.rack_length",
                "wr.rack_width",
                "wr.rack_height",
                "wr.rack_qty",
                "wr.dimension_uom",
                "wr.created_by",
                "wr.created_on",
                "wr.updated_by",
                "wr.updated_on"
            )
            ->leftJoin("precise.warehouse as wh", "wr.warehouse_id", "=", "wh.warehouse_id")
            ->get();

        if (count($this->rack) == 0)
            return response()->json(["status" => "error", "message" => "not found"], 404);

        return response()->json(["status" => "ok", "data" => $this->rack], 200);
    }
}
