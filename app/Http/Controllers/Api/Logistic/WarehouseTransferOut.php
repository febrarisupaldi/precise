<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WarehouseTransferOut extends Controller
{
    private $transfer;

    public function index(Request $request, $type, $from): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|required_with:start|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $f = explode('-', $from);
        $this->transfer = DB::table('precise.warehouse_trans_hd as hd ' . DB::raw('use index(`idx_on_wh_trans_hd___trans_date__trans_from__trans_type`)'))
            ->where('hd.trans_date', [$start, $end])
            ->where('hd.trans_type', $type)
            ->whereIn('hd.trans_from', $f)
            ->select(
                'hd.trans_hd_id',
                'hd.trans_number',
                'hd.trans_date',
                DB::raw("
                        concat(wh1.warehouse_code, ' - ', wh1.warehouse_name) 'Gudang pengirim',
                        concat(wh.warehouse_code, ' - ', wh.warehouse_name) 'Gudang tujuan'
                    "),
                'hd.trans_description',
                'hd.trans_con_number',
                'hd2.trans_date',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin('precise.warehouse as wh1', 'hd.trans_from', '=', 'wh1.warehouse_id')
            ->leftJoin('precise.warehouse as wh2', 'hd.trans_to', '=', 'wh.warehouse_id')
            ->leftJoin('precise.warehouse_trans_hd as hd2', 'hd.trans_con_number', '=', 'hd2.trans_number')
            ->get();

        if (count($this->transfer) == 0)
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->transfer], 200);
    }

    public function showDetailOnly($id): JsonResponse
    {
        $this->transfer = DB::table('precise.warehouse_trans_dt as dt')
            ->where('trans_hd_id', $id)
            ->select(
                'trans_dt_id',
                'trans_hd_id',
                'trans_seq',
                'dt.product_id',
                'p.product_code',
                'p.product_name',
                'trans_out_qty',
                'trans_uom',
                'dt.created_on',
                'dt.created_by',
                'dt.updated_on',
                'dt.updated_by'
            )
            ->leftJoin('precise.product as p', 'dt.product_id', '=', 'p.product_id')
            ->orderBy('trans_seq')
            ->get();

        if (count($this->transfer) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);
        return response()->json(["status" => "ok", "data" => $this->transfer], 200);
    }

    public function getHeaderDetail(Request $request, $type, $from): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|required_with:start|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $f = explode('-', $from);
        $this->transfer = DB::table('precise.warehouse_trans_hd as hd ' . DB::raw('use index(`idx_on_wh_trans_hd___trans_date__trans_from__trans_type`)'))
            ->where('hd.trans_date', [$start, $end])
            ->where('hd.trans_type', $type)
            ->whereIn('hd.trans_from', $f)
            ->select(
                'hd.trans_hd_id',
                'hd.trans_number',
                'hd.trans_date',
                DB::raw("
                        concat(wh1.warehouse_code, ' - ', wh1.warehouse_name) 'Gudang pengirim',
                        concat(wh.warehouse_code, ' - ', wh.warehouse_name) 'Gudang tujuan'
                    "),
                'hd.trans_description',
                'hd.trans_con_number',
                'hd2.trans_date',
                'dt.trans_seq',
                'dt.product_id',
                'p.product_code',
                'p.product_name',
                'dt.trans_out_qty',
                'dt.trans_uom',
                'dt2.trans_in_qty',
                'dt2.trans_uom',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->join('precise.warehouse_trans_dt as dt', 'hd.trans_hd_id', '=', 'dt.trans_hd_id')
            ->leftJoin('precise.warehouse as wh1', 'hd.trans_from', '=', 'wh1.warehouse_id')
            ->leftJoin('precise.warehouse as wh', 'hd.trans_to', '=', 'wh.warehouse_id')
            ->leftJoin('precise.warehouse_trans_hd as hd2', 'hd.trans_con_number', '=', 'hd2.trans_number')
            ->leftJoin('precise.warehouse_trans_dt as dt2', function ($join) {
                $join->on('hd.trans_con_number', '=', 'dt2.trans_number');
                $join->on('dt.trans_seq', '=', 'dt2.trans_seq');
            })
            ->leftJoin('precise.product as p', 'dt.product_id', '=', 'p.product_id')
            ->orderBy('hd.trans_number')
            ->orderBy('dt.trans_seq')
            ->get();
        if (count($this->transfer) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->transfer], 200);
    }

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required_with:value',
            'value' => 'required_with:type'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "number") {
                $this->transfer = DB::table('precise.warehouse_trans_hd')->where('trans_number', $value)->count();
            }
            if ($this->transfer == 0)
                return response()->json(['status' => 'error', 'message' => $this->transfer], 404);
            return response()->json(['status' => 'ok', 'message' => $this->transfer], 200);
        }
    }
}
