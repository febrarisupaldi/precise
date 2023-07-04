<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MonitoringSalesOrderController extends Controller
{
    private $monitoring;
    public function index(Request $request): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $validator = Validator::make($request->all(), [
            'start'     => 'required_with:end|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required_with:start|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->monitoring = DB::table('precise.monitoring_so_hd as a')
            ->whereBetween('b.sales_order_date', [$start, $end])
            ->whereNotNull('a.pick_up_priority_id')
            ->orWhere('a.pick_up_priority_id', '!=', '')
            ->select(
                'a.monitoring_hd_id',
                'b.sales_order_number',
                DB::raw("concat(customer_code, ' - ', customer_name) as 'Customer'"),
                'b.sales_order_description',
                DB::raw("
                        precise.get_friendly_date(sales_order_date) 'Tanggal SO',
                        precise.get_friendly_date(b.est_delivery_date) 'Est. tanggal kirim'
                    "),
                'release_seq',
                'precise.get_friendly_datetime(released_on)',
                'release_note',
                DB::raw("
                        case when a.pick_up_priority_id = 0 then 'PK'
			                else a.pick_up_priority_id 
			            end as 'Prioritas pick up'
                    "),
                'f.status_name',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('precise.sales_order_hd as b', 'a.sales_order_hd_id', '=', 'b.sales_order_hd_id')
            ->leftJoin('precise.customer as c', 'b.customer_id', '=', 'c.customer_id')
            ->leftJoin('precise.pick_up_status as f', 'a.pick_up_status_id', '=', 'f.status_id')
            ->leftJoin('precise.monitoring_so_history as g', function ($join) {
                $join->on('a.monitoring_hd_id', '=', 'a.monitoring_hd_id');
                $join->on('g.pick_up_status_id', '=', 1);
            })
            ->get();

        if (count($this->monitoring) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);
        return response()->json(["status" => "ok", "data" => $this->monitoring], 200);
    }

    public function getReleasedSO(): JsonResponse
    {
        $this->monitoring = DB::table('precise.monitoring_so_hd as a')
            ->whereNull('pick_up_priority_id')
            ->orWhere('pick_up_priority_id', '=', '')
            ->select(
                'monitoring_hd_id',
                'b.sales_order_hd_id',
                'sales_order_number',
                'release_seq',
                DB::raw("
                    concat(customer_code, ' - ', customer_name) as 'Customer',
                    ifnull(d.city_name, '') as 'Kota'
                "),
                'b.sales_order_description',
                DB::raw("
                    precise.get_friendly_date(sales_order_date) as 'Tanggal SO',
                    precise.get_friendly_date(b.est_delivery_date) as 'Est. tanggal kirim',
                    precise.get_friendly_datetime(released_on) as 'Tanggal rilis'
                "),
                'release_note'
            )
            ->leftJoin('precise.sales_order_hd as b', 'a.sales_order_hd_id', '=', 'b.sales_order_hd_id')
            ->leftJoin('precise.customer as c', 'b.customer_id', '=', 'c.customer_id')
            ->leftJoin('precise.city as d', 'c.city_id', '=', 'd.city_id')
            ->get();

        return response()->json(["status" => "ok", "data" => $this->monitoring], 200);
    }

    public function getReleasedItem(Request $request): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $validator = Validator::make($request->all(), [
            'start'     => 'required|required_with:end|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required_with:start|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->monitoring = DB::table('precise.monitoring_so_hd as hd')
            ->whereBetween('sohd.sales_order_date', [$start, $end])
            ->select(
                'hd.monitoring_hd_id',
                'sodt.sales_order_seq',
                'prod.product_code',
                'prod.product_name',
                'dt.release_qty',
                'sodt.uom_code'
            )
            ->join('precise.monitoring_so_dt as dt', 'hd.monitoring_hd_id', '=', 'dt.monitoring_hd_id')
            ->leftJoin('precise.sales_order_hd as sohd', 'hd.sales_order_hd_id', '=', 'sohd.sales_order_hd_id')
            ->leftJoin('precise.sales_order_dt as sodt', 'dt.sales_order_dt_id', '=', 'sodt.sales_order_dt_id')
            ->leftJoin('precise.product as prod', 'sodt.product_id', '=', 'prod.product_id')
            ->orderBy('monitoring_hd_id')
            ->orderBy('sales_order_seq')
            ->get();

        if (count($this->monitoring) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);
        return response()->json(["status" => "ok", "data" => $this->monitoring], 200);
    }

    public function getPendingPicking($name)
    {
    }

    public function getPicker($id): JsonResponse
    {
        $this->monitoring = DB::table('precise.monitoring_so_hd as a')
            ->where('monitoring_hd_id', $id)
            ->select(DB::raw("
                case 	when pick_up_status_id = 2 then true
                        when pick_up_status_id = 3 then false
                        else false
                end as ok2pick,
                case 	when pick_up_status_id = 2 then null
                        when pick_up_status_id = 3 then picked_up_by
                        else picked_up_by
                end as picked_up_by,
                case 	when pick_up_status_id = 2 then null
                        when pick_up_status_id = 3 then picked_up_on
                        else picked_up_on
                end as picked_up_on
            "))
            ->first();

        if (empty($this->monitoring))
            return response()->json("not found", 404);
        return response()->json($this->monitoring, 200);
    }

    public function getPacker($id): JsonResponse
    {
        $this->monitoring = DB::table('precise.monitoring_so_hd as a')
            ->where('monitoring_hd_id', $id)
            ->select(DB::raw("
                case 	when pick_up_status_id = 4 then true
                        when pick_up_status_id = 5 then false
                        else false
                end as ok2pick,
                case 	when pick_up_status_id = 4 then null
                        when pick_up_status_id = 5 then packed_by
                        else packed_by
                end as packed_by,
                case 	when pick_up_status_id = 4 then null
                        when pick_up_status_id = 5 then packed_on
                        else packed_on
                end as packed_on
            "))
            ->first();

        if (empty($this->monitoring))
            return response()->json("not found", 404);
        return response()->json($this->monitoring, 200);
    }

    public function updateSOPicker(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'monitoring_hd_id'  => 'required|exists:monitoring_so_hd,monitoring_hd_id',
                'picked_up_by'      => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->monitoring = DB::table("precise.monitoring_so_hd")
            ->where("monitoring_hd_id", $request->monitoring_hd_id)
            ->update([
                'picked_up_by'  => $request->picked_up_by,
                'pick_up_status_id' => 3,
                'updated_on'    => DB::raw('sysdate()'),
                'picked_up_on'  => DB::raw('sysdate()')
            ]);

        if ($this->monitoring == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
        }
        return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
    }

    public function updatePickUpStatus(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'monitoring_hd_id'      => 'required|exists:monitoring_so_hd,monitoring_hd_id',
                'picked_up_status_id'   => 'required',
                'updated_by'            => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->monitoring = DB::table("precise.monitoring_so_hd")
            ->where("monitoring_hd_id", $request->monitoring_hd_id)
            ->update([
                "pick_up_status_id" => $request->pick_up_status_id,
                "updated_on"        => DB::raw("sysdate()"),
                "updated_by"        => $request->updated_by
            ]);

        if ($this->monitoring == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed update data'], 500);
        }
        return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
    }
}
