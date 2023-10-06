<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WarehouseRackInController extends Controller
{
    private $rack;
    public function getCummulativeByDate($date): JsonResponse
    {
        $this->rack = DB::table("precise.warehouse_rack_in as wri")
            ->where("wri.trans_date", $date)
            ->select(
                "wri.rack_id",
                "wr.rack_code",
                DB::raw("COUNT(wr.rack_code)AS total_bstb")
            )
            ->leftJoin("precise.warehouse_rack as wr", "wri.rack_id", "=", "wr.rack_id")
            ->groupBy("wri.rack_id")
            ->get();

        if (count($this->rack) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->rack, code: 200);
    }

    public function show($id, $date): JsonResponse
    {
        $this->rack = DB::table("precise.warehouse_rack_in", "wri")
            ->where("wri.rack_id", $id)
            ->where("wri.trans_date", $date)
            ->select(
                "wri.warehouse_rack_in_id",
                "wr.rack_id",
                "wr.rack_code",
                "bs.bstb_id",
                "bs.bstb_num",
                "bs.bstb_item",
                "bs.bstb_design",
                "wh.warehouse_id",
                "wh.warehouse_code",
                "wh.warehouse_name",
                "wri.trans_date",
                "wri.trans_in_qty",
                "nd.NIP AS employee_id",
                "nd.NAMA AS employee_name",
                "wri.description",
                "wri.created_on",
                "wri.created_by",
                "wri.updated_on",
                "wri.updated_by"
            )
            ->leftJoin("precise.warehouse_rack as wr", "wri.rack_id", "=", "wr.rack_id")
            ->leftJoin("precise.bstb as bs", "bs.bstb_id", "=", "wri.bstb_id")
            ->leftJoin("precise.warehouse as wh", "wh.warehouse_id", "=", "wr.warehouse_id")
            ->leftJoin("dbhrd.newdatakar as nd", "nd.NIP", "=", "wri.user_id")
            ->get();

        if (count($this->rack) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->rack, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rack_id'       => 'required|exists:warehouse_rack,rack_id',
            'bstb_id'       => 'required|exists:bstb,bstb_id',
            'trans_date'    => 'required|date_format:Y-m-d',
            'trans_shift'   => 'required',
            'user_id'       => 'required|exists:users,user_id',
            'desc'          => 'nullable',
            'trans_in_qty'  => 'required|numeric',
            'created_by'    => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->rack = DB::table("precise.warehouse_rack_in")
            ->insert([
                'rack_id'       => $request['rack_id'],
                'bstb_id'       => $request['bstb_id'],
                'trans_date'    => $request['trans_date'],
                'trans_shift'   => $request['trans_shift'],
                'user_id'       => $request['user_id'],
                'description'   => $request['desc'],
                'trans_in_qty'  => $request['trans_in_qty'],
                'created_by'    => $request['created_by']
            ]);

        if ($this->rack == 0)
            return ResponseController::json(status: "error", message: "failed input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'warehouse_rack_in_id'  => 'required|exists:warehouse_rack_in,warehouse_rack_in_id',
            'reason'                => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->rack = DB::table('precise.warehouse_rack_in')
                ->where('warehouse_rack_in_id', $request->warehouse_rack_in_id)
                ->delete();

            if ($this->rack == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error delete data", code: 500);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success delete data", code: 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
