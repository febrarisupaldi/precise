<?php

namespace App\Http\Controllers\Api\Production\Melamine;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MaterialHandlingController extends Controller
{
    private $materialHandling;

    public function showByCode($code): JsonResponse
    {
        $this->materialHandling = DB::table("precise.material_to_product", "mtp")
            ->where("mtp.material_code", $code)
            ->select(
                "mtp.material_code",
                "p.product_id",
                "p.product_code",
                "p.product_name",
                "p.uom_code"
            )
            ->leftJoin("precise.product as p", "mtp.product_id", "=", "p.product_id")
            ->get();

        if (count($this->materialHandling) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);

        return response()->json(["status" => "ok", "data" => $this->materialHandling], 200);
    }

    public function showByDate($date): JsonResponse
    {
        $this->materialHandling = DB::table("precise.material_supply_hd", "hd")
            ->where('trans_date', $date)
            ->select(
                'hd.material_supply_hd_id',
                'hd.trans_date',
                'hd.shift',
                'hd.machine_id',
                'mc.machine_code',
                'mc.lane_code',
                'mc.lane_number',
                'hd.created_on',
                'hd.created_by'
            )
            ->leftJoin("precise.machine as mc", "hd.machine_id", "=", "mc.machine_id")
            ->get();

        if (count($this->materialHandling) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);

        return response()->json(["status" => "ok", "data" => $this->materialHandling], 200);
    }

    public function showDetailByHdId($id): JsonResponse
    {
        $this->materialHandling = DB::table("precise.material_supply_dt", "dt")
            ->where('dt.material_supply_hd_id', $id)
            ->select(
                'dt.material_supply_dt_id',
                'dt.material_supply_hd_id',
                'dt.material_id',
                'mtp.material_code',
                'p.product_name',
                'dt.lot_number',
                'dt.supply_qty',
                'dt.supplied_on',
                'dt.return_qty',
                'dt.returned_on',
                'dt.uom_code',
                'dt.created_by',
                'dt.created_on',
                'dt.updated_by',
                'dt.updated_on'
            )
            ->leftJoin("precise.material_to_product as mtp", "dt.material_id", "=", "mtp.product_id")
            ->leftJoin("precise.product as p", "dt.material_id", "=", "p.product_id")
            ->get();

        if (count($this->materialHandling) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);

        return response()->json(["status" => "ok", "data" => $this->materialHandling], 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "trans_date"    => ["required", "date_format:Y-m-d"],
            "shift"         => ["required"],
            "machine_id"    => ["required", "exists:machine,machine_id"],
            "created_by"    => ["required"]
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->materialHandling = DB::table("precise.material_supply_hd")
            ->insertGetId([
                "trans_date"    => $request->trans_date,
                "shift"         => $request->shift,
                "machine_id"    => $request->machine_id,
                "created_by"    => $request->created_by,
            ]);

        if ($this->materialHandling < 1)
            return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);

        return response()->json(['status' => 'ok', 'message' => 'success input data', 'id' => $this->materialHandling], 200);
    }

    public function createDetail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "material_supply_hd_id" => ["required", "exists:material_supply_hd,material_supply_hd_id"],
            "material_id"           => ["required", "exists:product,product_id"],
            "lot_number"            => ["required"],
            "supply_qty"            => ["required", "numeric"],
            "created_by"            => ["required"]
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->materialHandling = DB::table("precise.material_supply_dt")
            ->insert([
                "material_supply_hd_id" => $request->material_supply_hd_id,
                "material_id"           => $request->material_id,
                "lot_number"            => $request->lot_number,
                "supply_qty"            => $request->supply_qty,
                "created_by"            => $request->created_by
            ]);

        if ($this->materialHandling == 0)
            return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);

        return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
    }

    public function updateDetail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "material_supply_dt_id" => ["required", "exists:material_supply_dt,material_supply_dt_id"],
            "return_qty"            => ["required", "numeric"],
            "return_on"             => ["required"],
            "updated_by"            => ["required"]
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->materialHandling = DB::table("precise.material_supply_dt")
            ->where("material_supply_dt_id", $request->material_supply_dt_id)
            ->update([
                "return_qty"            => $request->return_qty,
                "return_on"             => $request->return_on,
                "updated_by"            => $request->updated_by
            ]);

        if ($this->materialHandling == 0)
            return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);

        return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
    }
}
