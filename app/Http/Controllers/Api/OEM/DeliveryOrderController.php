<?php

namespace App\Http\Controllers\Api\OEM;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\JsonResponse;

class DeliveryOrderController extends Controller
{
    private $deliveryOrder;
    public function index($warehouse, Request $request): JsonResponse
    {
        $start = $request->get('start', date("Y-m-d"));
        $end = $request->get('end', date("Y-m-d"));
        $validator = Validator::make($request->all(), [
            'start'         => 'required_with:end|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required_with:start|date_format:Y-m-d|after_or_equal:start'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $warehouse = explode("-", $warehouse);
        $this->deliveryOrder = DB::table("precise.oem_delivery_hd as hd")
            ->whereBetween('hd.oem_delivery_date', [$start, $end])
            ->whereIn('hd.warehouse_id', $warehouse)
            ->select(
                'hd.oem_delivery_hd_id',
                'oem_delivery_number',
                'oem_delivery_date',
                'hd.oem_order_hd_id',
                'hd.warehouse_id',
                'wh.warehouse_code',
                'wh.warehouse_name',
                'hd.delivery_method_id',
                'dm.delivery_method_name',
                'hd.customer_id',
                'cust.customer_code',
                'cust.customer_name',
                'ca.address',
                'POHd.oem_order_number',
                'POHd.oem_so_number',
                'hd.vehicle_id',
                'v.license_number',
                'v.vehicle_model',
                'hd.driver_nik',
                'e.employee_name as driver_name',
                'hd.taker_name',
                'hd.delivery_description',
                'hd.delivery_status',
                DB::raw("concat(ods.`oem_delivery_status_code`, ' - ', ods.`oem_delivery_status_name`) as delivery_status_code_and_name"),
                'hd.received_date',
                'hd.print_count',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin('precise.oem_order_hd as POHd', 'hd.oem_order_hd_id', '=', 'POHd.oem_order_hd_id')
            ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
            ->leftJoin('precise.warehouse as wh', 'hd.warehouse_id', '=', 'wh.warehouse_id')
            ->leftJoin('precise.vehicle as v', 'hd.vehicle_id', '=', 'v.vehicle_id')
            ->leftJoin('precise.employee as e', 'hd.driver_nik', '=', 'e.employee_nik')
            ->leftJoin('precise.delivery_method as dm', 'hd.delivery_method_id', '=', 'dm.delivery_method_id')
            ->leftJoin('precise.oem_delivery_status as ods', 'hd.delivery_status', '=', 'ods.oem_delivery_status_id')
            ->leftJoin('precise.customer_address as ca', 'POHd.shipping_address_id', '=', 'ca.customer_address_id')
            ->orderBy('oem_delivery_number')
            ->get();

        if (count($this->deliveryOrder) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->deliveryOrder, code: 200);
    }
    public function show($id): JsonResponse
    {
        $master = DB::table('precise.oem_delivery_hd as hd')
            ->where('hd.oem_delivery_hd_id', $id)
            ->select(
                'hd.oem_delivery_hd_id',
                'hd.oem_delivery_number',
                'hd.oem_delivery_date',
                'hd.oem_order_hd_id',
                'poHd.oem_order_number',
                'poHd.oem_so_number',
                'hd.customer_id',
                'cust.customer_code',
                'cust.customer_name',
                'hd.warehouse_id',
                'wh.warehouse_code',
                'wh.warehouse_name',
                'hd.vehicle_id',
                'v.vehicle_model',
                'v.license_number',
                'hd.driver_nik',
                'e.employee_name',
                'hd.taker_name',
                'delivery_description',
                'delivery_status',
                'received_date',
                'print_count',
                'hd.created_by',
                'hd.created_on',
                'hd.updated_by',
                'hd.updated_on'
            )
            ->leftJoin('precise.oem_order_hd as poHd', 'hd.oem_order_hd_id', '=', 'poHd.oem_order_hd_id')
            ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
            ->leftJoin('precise.warehouse as wh', 'hd.warehouse_id', '=', 'wh.warehouse_id')
            ->leftJoin('precise.vehicle as v', 'hd.vehicle_id', '=', 'v.vehicle_id')
            ->leftJoin('precise.employee as e', 'hd.driver_nik', '=', 'e.employee_nik')
            ->leftJoin('precise.customer_address as ca', 'poHd.shipping_address_id', '=', 'ca.customer_address_id')
            ->first();

        if (empty($master)) {
            return response()->json("not found", 404);
        }
        $detail = DB::table('precise.oem_delivery_dt as dt')
            ->where('dt.oem_delivery_hd_id', $master->oem_delivery_hd_id)
            ->select(
                'dt.oem_delivery_dt_id',
                'dt.oem_delivery_hd_id',
                'dt.oem_order_dt_id',
                'poDt.oem_order_dt_seq',
                'ods.is_on_going',
                'ods.is_received',
                'pc.oem_material_supply_type',
                'dt.product_id',
                'prod.product_code',
                'prod.product_name',
                DB::raw(
                    "    
                    IFNULL(mstd.mstd_prod_std_ext, 0) AS mstd_prod_std_ext"
                ),
                'pldt.price_idr',
                'poDt.oem_order_qty',
                'poDt.due_date',
                DB::raw("DATEDIFF(poDt.due_date, DATE(NOW())) AS days_left"),
                'v.total_on_going_qty',
                'v.total_received_qty',
                'v.outstanding_qty',
                DB::raw("MIN(v.psq) AS PSQ"),
                'dt.delivery_qty',
                'dt.uom_code',
                DB::raw("
                    CAST(IFNULL(mstd.`mstd_prod_std_ext`, 0) * dt.delivery_qty / 1000 AS DECIMAL(12,4)) AS net_weight,
                    'KG' AS weight_uom
                "),
                'dt.received_qty',
                'dt.uom_code_received',
                DB::raw("dt.delivery_qty - dt.received_qty AS receipt_diff"),
                'dt.packaging_id',
                'prod2.product_code AS packaging_code',
                'prod2.product_name AS packaging_name',
                'phd.packaging_alias',
                'dt.packaging_qty',
                'prod2.uom_code AS packaging_uom_code',
                'dt.packaging_description',
                'dt.created_on',
                'dt.created_by',
                'dt.updated_on',
                'dt.updated_by'
            )
            ->join("precise.oem_delivery_hd as hd", "hd.oem_delivery_hd_id", "=", "dt.oem_delivery_hd_id")
            ->leftJoin("precise.oem_order_dt as poDt", "dt.oem_order_dt_id", "=", "poDt.oem_order_dt_id")
            ->leftJoin("precise.product_customer as pc", "poDt.product_customer_id", "=", "pc.product_customer_id")
            ->leftJoin("precise.product as prod", "dt.product_id", "=", "prod.product_id")
            ->leftJoin("precise.packaging_hd as phd", "dt.packaging_id", "=", "phd.packaging_id")
            ->leftJoin("precise.product as prod2", "phd.packaging_id", "=", "prod2.product_id")
            ->leftJoin("precise.customer as cust", "hd.customer_id", "=", "cust.customer_id")
            ->leftJoin("precise.price_list_hd as plhd", function ($sub) {
                $sub->on("cust.price_group_code", "=", "plhd.price_group_code");
                $sub->on("plhd.price_status", "=", DB::raw("'A'"));
            })
            ->leftJoin("precise.price_list_dt as pldt", function ($sub) {
                $sub->on("pldt.price_group_code", "=", "plhd.price_group_code");
                $sub->on("plhd.price_seq", "=", "pldt.price_group_seq");
                $sub->on("prod.product_code", "=", "pldt.product_code");
            })
            ->leftJoin("precise.oem_delivery_status as ods", "hd.delivery_status", "=", "ods.oem_delivery_status_id")
            ->leftJoin("precise.view_oem_permitted_shipping_qty as v", function ($sub) {
                $sub->on("poDt.oem_order_hd_id", "=", "v.oem_order_hd_id");
                $sub->on("poDt.oem_order_dt_id", "=", "v.oem_order_dt_id");
            })
            ->leftJoin("precise.pd_prod_definition_mstd as ppdm", "prod.product_code", "=", "ppdm.ppdm_ppd_id")
            ->leftJoin("precise.ms_std_tech as mstd", "ppdm_mstd_id", "=", "mstd_id")
            ->groupBy("v.oem_order_dt_id", "pldt.price_idr", "dt.oem_delivery_dt_id", "mstd.mstd_prod_std_ext")
            ->get();

        $this->deliveryOrder =
            array_merge_recursive(
                (array)$master,
                array("detail" => $detail)
            );

        return response()->json($this->deliveryOrder, 200);
    }

    public function getDOByPO($id): JsonResponse
    {
        $this->deliveryOrder = DB::table('precise.oem_delivery_hd as hd')
            ->where('hd.oem_order_hd_id', $id)
            ->select(
                'oem_delivery_hd_id',
                'oem_delivery_number',
                'oem_delivery_date',
                DB::raw("
                    concat(warehouse_code, ' - ', warehouse_name) as warehouse_code_and_name
                ")
            )
            ->leftJoin('precise.warehouse as wh', 'hd.warehouse_id', '=', 'wh.warehouse_id')
            ->get();

        if (count($this->deliveryOrder) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->deliveryOrder, code: 200);
    }

    public function check($id, $deliveryStatus): JsonResponse
    {
        $status = explode("-", $deliveryStatus);
        $this->deliveryOrder = DB::table('precise.oem_delivery_hd')
            ->whereIn('delivery_status', $status)
            ->where('oem_order_hd_id', $id)
            ->count();
        if ($this->deliveryOrder == 0)
            return ResponseController::json(status: "error", message: $this->deliveryOrder, code: 404);

        return ResponseController::json(status: "ok", message: $this->deliveryOrder, code: 200);
    }

    public function getOutstandingPOofDeliveryScheduleByDate($product, $customer, $warehouse, Request $request): JsonResponse
    {
        $date     = $request->get("delivery_date");

        $validator = Validator::make(
            $request->all(),
            [
                'delivery_date' => 'required|date_format:Y-m-d'
            ]
        );
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->deliveryOrder = DB::table('precise.oem_delivery_hd as hd')
            ->where('hd.customer_id', $customer)
            ->where('hd.warehouse_id', $warehouse)
            ->where('dt.product_id', $product)
            ->where('hd.oem_delivery_date', $date)
            ->select(
                'hd.oem_delivery_hd_id',
                'hd.oem_delivery_number',
                'hd.oem_delivery_date',
                DB::raw("
                        concat(ods.oem_delivery_status_code, ' - ', ods.oem_delivery_status_name) as delivery_status
                    "),
                'dt.delivery_qty',
                'dt.received_qty',
                'dt.uom_code',
                'ooh.oem_order_number',
                'ooh.oem_order_date'
            )
            ->join('precise.oem_delivery_dt as dt', 'hd.oem_delivery_hd_id', '=', 'dt.oem_delivery_hd_id')
            ->leftJoin('precise.oem_order_dt as ood', 'dt.oem_order_dt_id', '=', 'ood.oem_order_dt_id')
            ->join('precise.oem_order_hd as ooh', 'ood.oem_order_hd_id', '=', 'ooh.oem_order_hd_id')
            ->leftJoin('precise.oem_delivery_status as ods', 'hd.delivery_status', '=', 'ods.oem_delivery_status_id')
            ->get();
        if (count($this->deliveryOrder) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->deliveryOrder, code: 200);
    }

    public function getHistoryDOByPOAndDate($id, $detailId, Request $request): JsonResponse
    {

        $validator = Validator::make(
            $request->all(),
            [
                'delivery_date'     => 'required|date_format:Y-m-d'
            ]
        );
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->deliveryOrder = DB::table("precise.oem_delivery_hd", "hd")
            ->where('hd.oem_order_hd_id', $id)
            ->where('dt.oem_order_dt_id', $detailId)
            ->where('hd.oem_delivery_date', $request->delivery_date)
            ->select(
                'hd.oem_delivery_hd_id',
                'hd.oem_delivery_number',
                'hd.oem_delivery_date',
                DB::raw("
                        concat(ods.oem_delivery_status_code, ' - ', ods.oem_delivery_status_name) as delivery_status
                   "),
                'dt.delivery_qty',
                'dt.received_qty',
                'dt.uom_code'
            )
            ->join('precise.oem_delivery_dt as dt', 'hd.oem_delivery_hd_id', '=', 'dt.oem_delivery_hd_id')
            ->leftJoin('precise.oem_delivery_status as ods', 'hd.delivery_status', '=', 'ods.oem_delivery_status_id')
            ->get();
        if (count($this->deliveryOrder) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->deliveryOrder, code: 200);
    }

    public function getHistoryDOByPO($id): JsonResponse
    {
        $this->deliveryOrder = DB::table('precise.oem_delivery_hd as hd')
            ->where('hd.oem_order_hd_id', $id)
            ->select(
                'hd.oem_delivery_hd_id',
                'hd.oem_delivery_number',
                'hd.delivery_status',
                DB::raw(" 
                    concat(ods.`oem_delivery_status_code`, ' - ', ods.`oem_delivery_status_name`) as delivery_status_code_and_name
                "),
                'ood.due_date',
                'ood.oem_order_qty',
                'oem_delivery_date',
                'received_date',
                'dt.product_id',
                'p.product_code',
                'p.product_name',
                'dt.uom_code',
                'delivery_qty',
                'received_qty'
            )
            ->join('precise.oem_delivery_dt as dt', 'hd.oem_delivery_hd_id', '=', 'dt.oem_delivery_hd_id')
            ->leftJoin('precise.oem_order_dt as ood', 'dt.oem_order_dt_id', '=', 'ood.oem_order_dt_id')
            ->leftJoin('precise.product as p', 'dt.product_id', '=', 'p.product_id')
            ->leftJoin('precise.oem_delivery_status as ods', 'hd.delivery_status', '=', 'ods.oem_delivery_status_id')
            ->get();

        if (count($this->deliveryOrder) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->deliveryOrder, code: 200);
    }

    public function detail($warehouse, Request $request): JsonResponse
    {
        $start = $request->get('start', date("Y-m-d"));
        $end = $request->get('end', date("Y-m-d"));

        $validator = Validator::make($request->all(), [
            'start'         => 'required_with:end|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required_with:start|date_format:Y-m-d|after_or_equal:start'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            $warehouse = explode('-', $warehouse);
            $query = DB::table('oem_delivery_hd as hd')
                ->whereBetween('hd.oem_delivery_date', [$start, $end])
                ->whereIn('hd.warehouse_id', $warehouse)
                ->select(
                    'hd.oem_delivery_hd_id',
                    'oem_delivery_number',
                    'oem_delivery_date',
                    'hd.oem_order_hd_id',
                    'hd.warehouse_id',
                    'wh.warehouse_code',
                    'wh.warehouse_name',
                    'hd.delivery_method_id',
                    'dm.delivery_method_name',
                    'hd.customer_id',
                    'cust.customer_code',
                    'cust.customer_name',
                    'POHd.oem_order_number',
                    'hd.vehicle_id',
                    'v.license_number',
                    'v.vehicle_model',
                    'hd.driver_nik',
                    'e.employee_name as driver_name',
                    'hd.taker_name',
                    'hd.delivery_description',
                    'hd.delivery_status',
                    DB::raw("
                        concat(ods.`oem_delivery_status_code`, ' - ', ods.`oem_delivery_status_name`) as delivery_status_code_and_name
                    "),
                    'hd.received_date',
                    'hd.print_count',
                    'prod.product_code',
                    'prod.product_name',
                    'dt.delivery_qty',
                    'dt.uom_code',
                    'dt.received_qty',
                    'dt.uom_code_received',
                    DB::raw("ifnull(dt.delivery_qty, 0) - ifnull(dt.received_qty, 0) as receipt_diff"),
                    DB::raw("ifnull(dt.packaging_id, 0)"),
                    'prod2.product_code AS packaging_code',
                    'prod2.product_name AS packaging_name',
                    'phd.packaging_alias',
                    'dt.packaging_qty',
                    'prod2.uom_code AS packaging_uom_code',
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->leftJoin('precise.oem_delivery_dt as dt', 'hd.oem_delivery_hd_id', '=', 'dt.oem_delivery_hd_id')
                ->leftJoin('precise.packaging_hd as phd', 'dt.packaging_id', '=', 'phd.packaging_id')
                ->leftJoin('precise.product as prod2', 'phd.packaging_id', '=', 'prod2.product_id')
                ->leftJoin('precise.oem_order_hd as POHd', 'hd.oem_order_hd_id', '=', 'POHd.oem_order_hd_id')
                ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
                ->leftJoin('precise.warehouse as wh', 'hd.warehouse_id', '=', 'wh.warehouse_id')
                ->leftJoin('precise.vehicle as v', 'hd.vehicle_id', '=', 'v.vehicle_id')
                ->leftJoin('precise.employee as e', 'hd.driver_nik', '=', 'e.employee_nik')
                ->leftJoin('precise.product as prod', 'dt.product_id', '=', 'prod.product_id')
                ->leftJoin('precise.delivery_method as dm', 'hd.delivery_method_id', '=', 'dm.delivery_method_id')
                ->leftJoin('precise.oem_delivery_status as ods', 'hd.delivery_status', '=', 'ods.oem_delivery_status_id');

            $this->deliveryOrder = DB::table('precise.oem_delivery_hd as hd')
                ->whereBetween('hd.oem_delivery_date', [$start, $end])
                ->whereIn('hd.warehouse_id', $warehouse)
                ->whereNotNull('odp.packaging_id')
                ->select(
                    'hd.oem_delivery_hd_id',
                    'oem_delivery_number',
                    'oem_delivery_date',
                    'hd.oem_order_hd_id',
                    'hd.warehouse_id',
                    'wh.warehouse_code',
                    'wh.warehouse_name',
                    'hd.delivery_method_id',
                    'dm.delivery_method_name',
                    'hd.customer_id',
                    'cust.customer_code',
                    'cust.customer_name',
                    'POHd.oem_order_number',
                    'hd.vehicle_id',
                    'v.license_number',
                    'v.vehicle_model',
                    'hd.driver_nik',
                    'e.employee_name as driver_name',
                    'hd.taker_name',
                    'hd.delivery_description',
                    'hd.delivery_status',
                    DB::raw("
                            concat(ods.`oem_delivery_status_code`, ' - ', ods.`oem_delivery_status_name`) as delivery_status_code_and_name
                        "),
                    'hd.received_date',
                    'hd.print_count',
                    'prod.product_code',
                    'prod.product_name',
                    'odp.packaging_qty as delivery_qty',
                    'prod.uom_code',
                    'odp.packaging_qty as received_qty',
                    'prod.uom_code as uom_code_received',
                    DB::raw("ifnull(odp.packaging_qty, 0) - ifnull(odp.packaging_qty, 0) as receipt_diff"),
                    DB::raw("ifnull('odp.packaging_id', 0)"),
                    'prod2.product_code AS packaging_code',
                    'prod2.product_name AS packaging_name',
                    'phd.packaging_alias',
                    'odp.packaging_qty',
                    'prod2.uom_code AS packaging_uom_code',
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->leftJoin('precise.oem_delivery_packaging as odp', 'hd.oem_delivery_hd_id', '=', 'odp.oem_delivery_hd_id')
                ->leftJoin('precise.packaging_hd as phd', 'odp.packaging_id', '=', 'phd.packaging_id')
                ->leftJoin('precise.product as prod2', 'phd.packaging_id', '=', 'prod2.product_id')
                ->leftJoin('precise.oem_order_hd as POHd', 'hd.oem_order_hd_id', '=', 'POHd.oem_order_hd_id')
                ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
                ->leftJoin('precise.warehouse as wh', 'hd.warehouse_id', '=', 'wh.warehouse_id')
                ->leftJoin('precise.vehicle as v', 'hd.vehicle_id', '=', 'v.vehicle_id')
                ->leftJoin('precise.employee as e', 'hd.driver_nik', '=', 'e.employee_nik')
                ->leftJoin('precise.product as prod', 'odp.packaging_id', '=', 'prod.product_id')
                ->leftJoin('precise.delivery_method as dm', 'hd.delivery_method_id', '=', 'dm.delivery_method_id')
                ->leftJoin('precise.oem_delivery_status as ods', 'hd.delivery_status', '=', 'ods.oem_delivery_status_id')
                ->union($query)
                ->orderBy('oem_delivery_number')
                ->orderByDesc('product_code')
                ->get();

            if (count($this->deliveryOrder) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->deliveryOrder, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function getDOForPO($id, $status): JsonResponse
    {
        $stat = explode("-", $status);
        $this->deliveryOrder = DB::table("precise.oem_delivery_hd")
            ->where("oem_order_hd_id", $id)
            ->whereIn("delivery_status", $stat)
            ->count();

        if ($this->deliveryOrder == 0)
            return response()->json(["status" => "error", "message" => $this->deliveryOrder], 404);
        return response()->json(["status" => "ok", "message" => $this->deliveryOrder], 200);
    }

    public function getDOForValidateProduct($header, $detail): JsonResponse
    {
        $this->deliveryOrder = DB::table("precise.oem_delivery_dt", "dt")
            ->where("hd.oem_order_hd_id", $header)
            ->where("dt.oem_order_dt_id", $detail)
            ->select(
                "dt.oem_delivery_hd_id",
                "hd.oem_delivery_number",
                "hd.oem_delivery_date",
                DB::raw("
                    CONCAT(warehouse_code, '-', warehouse_name) as warehouse_code_and_name
                ")
            )
            ->leftJoin("precise.oem_delivery_hd as hd", "dt.oem_delivery_hd_id", "=", "hd.oem_delivery_hd_id")
            ->leftJoin("precise.oem_order_hd as ohd", "hd.oem_order_hd_id", "=", "ohd.oem_order_hd_id")
            ->leftJoin("precise.warehouse as wh", "hd.warehouse_id", "=", "wh.warehouse_id")
            ->get();

        if (count($this->deliveryOrder) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);
        return response()->json(["status" => "ok", "data" => $this->deliveryOrder], 200);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'oem_delivery_date'     => 'required|date_format:Y-m-d',
            'oem_order_hd_id'       => 'required|exists:oem_order_hd,oem_order_hd_id',
            'customer_id'           => 'required|exists:customer,customer_id',
            'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
            'delivery_method_id'    => 'required|exists:delivery_method,delivery_method_id',
            'vehicle_id'            => 'nullable|exists:vehicle,vehicle_id',
            'driver_nik'            => 'nullable|exists:driver,driver_nik',
            'desc'                  => 'nullable',
            'delivery_status'       => 'required|exists:oem_delivery_status,oem_delivery_status_id',
            'created_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {

            $id = DB::table('precise.oem_delivery_hd')
                ->insertGetId([
                    'oem_delivery_date'     => $data['oem_delivery_date'],
                    'oem_order_hd_id'       => $data['oem_order_hd_id'],
                    'customer_id'           => $data['customer_id'],
                    'warehouse_id'          => $data['warehouse_id'],
                    'delivery_method_id'    => $data['delivery_method_id'],
                    'vehicle_id'            => $data['vehicle_id'],
                    'driver_nik'            => $data['driver_nik'],
                    'taker_name'            => $data['taker_name'],
                    'delivery_description'  => $data['desc'],
                    'delivery_status'       => $data['delivery_status'],
                    'created_by'            => $data['created_by']
                ]);

            if ($id == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "server error"], 500);
            }
            foreach ($data['detail'] as $d) {
                $validator = Validator::make($d, [
                    'oem_order_dt_id'       => 'required|exists:oem_order_dt,oem_order_dt_id',
                    'product_id'            => 'required|exists:product,product_id',
                    'delivery_qty'          => 'required|numeric',
                    'uom_code'              => 'required|exists:uom,uom_code',
                    'uom_code_received'     => 'required|exists:uom,uom_code',
                    'packaging_id'          => 'required|exists:packaging_hd,packaging_hd_id',
                    'packaging_qty'         => 'required|numeric',
                    'desc'                  => 'nullable',
                    'created_by'            => 'required'
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                }

                $dt[] = [
                    'oem_delivery_hd_id'    => $id,
                    'oem_order_dt_id'       => $d['oem_order_dt_id'],
                    'product_id'            => $d['product_id'],
                    'delivery_qty'          => $d['delivery_qty'],
                    'uom_code'              => $d['uom_code'],
                    'uom_code_received'     => $d['uom_code_received'],
                    'packaging_id'          => $d['packaging_id'],
                    'packaging_qty'         => $d['packaging_qty'],
                    'packaging_description' => $d['desc'],
                    'created_by'            => $d['created_by']
                ];
            }

            $check = DB::table('precise.oem_delivery_dt')
                ->insert($dt);

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "server error"], 500);
            }

            $trans = DB::table('precise.oem_delivery_hd')
                ->where('oem_delivery_hd_id', $id)
                ->select('oem_delivery_number')
                ->first();

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => $trans->oem_delivery_number], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'oem_delivery_date'     => 'required|date_format:Y-m-d',
            'oem_order_hd_id'       => 'required|exists:oem_order_hd,oem_order_hd_id',
            'customer_id'           => 'required|exists:customer,customer_id',
            'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
            'delivery_method_id'    => 'required|exists:delivery_method,delivery_method_id',
            'vehicle_id'            => 'nullable|exists:vehicle,vehicle_id',
            'driver_nik'            => 'nullable|exists:driver,driver_nik',
            'delivery_status'       => 'required|exists:oem_delivery_status,oem_delivery_status_id',
            'desc'                  => 'nullable',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update", $data);
            DB::table("precise.oem_delivery_hd")
                ->where("oem_delivery_hd_id", $data['oem_delivery_hd_id'])
                ->update([
                    'oem_delivery_date'     => $data['oem_delivery_date'],
                    'oem_order_hd_id'       => $data['oem_order_hd_id'],
                    'customer_id'           => $data['customer_id'],
                    'warehouse_id'          => $data['warehouse_id'],
                    'delivery_method_id'    => $data['delivery_method_id'],
                    'vehicle_id'            => $data['vehicle_id'],
                    'driver_nik'            => $data['driver_nik'],
                    'taker_name'            => $data['taker_name'],
                    'delivery_description'  => $data['desc'],
                    'updated_by'            => $data['updated_by']
                ]);

            if ($data['inserted'] != null) {
                foreach ($data['inserted'] as $d) {
                    $dt[] = [
                        'oem_delivery_hd_id'    => $d['oem_delivery_hd_id'],
                        'oem_order_dt_id'       => $d['oem_order_dt_id'],
                        'product_id'            => $d['product_id'],
                        'delivery_qty'          => $d['delivery_qty'],
                        'uom_code'              => $d['uom_code'],
                        'uom_code_received'     => $d['uom_code_received'],
                        'packaging_id'          => $d['packaging_id'],
                        'packaging_qty'         => $d['packaging_qty'],
                        'packaging_description' => $d['desc'],
                        'created_by'            => $d['created_by']
                    ];
                }

                $check = DB::table('precise.oem_delivery_dt')
                    ->insert($dt);

                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }
            }



            if ($data['updated'] != null) {
                foreach ($data['updated'] as $d) {
                    $check = DB::table("precise.oem_delivery_dt")
                        ->where("oem_delivery_dt_id", $d['oem_delivery_dt_id'])
                        ->update([
                            'delivery_qty'          => $d['delivery_qty'],
                            'packaging_id'          => $d['packaging_id'],
                            'packaging_qty'         => $d['packaging_qty'],
                            'packaging_description' => $d['desc'],
                            'updated_by'            => $d['updated_by']
                        ]);

                    if ($check == 0) {
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => "server error"], 500);
                    }
                }
            }

            if ($data['deleted'] != null) {
                $delete = array();
                foreach ($data['deleted'] as $del) {
                    $delete[] = $del['oem_delivery_dt_id'];
                }

                $check = DB::table('precise.oem_delivery_dt')
                    ->whereIn('oem_delivery_dt_id', $delete)
                    ->delete();

                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }
            }

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function receive(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'oem_delivery_hd_id'    => 'required|exists:oem_delivery_hd,oem_delivery_hd_id',
            'delivery_status'       => 'required|exists:oem_delivery_status,oem_delivery_status_id',
            'received_date'         => 'required',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update", $data);
            $this->deliveryOrder = DB::table('precise.oem_delivery_hd')
                ->where('oem_delivery_hd_id', $data['oem_delivery_hd_id'])
                ->update([
                    'delivery_status'   => $data['delivery_status'],
                    'received_date'     => $data['received_date'],
                    'updated_by'        => $data['updated_by']
                ]);

            if ($this->deliveryOrder > 0) {
                foreach ($data['updated'] as $detail) {
                    $check = $this->deliveryOrder = DB::table('precise.oem_delivery_dt')
                        ->where('oem_delivery_dt_id', $detail['oem_delivery_dt_id'])
                        ->update([
                            'received_qty'      => $detail['received_qty'],
                            'uom_code_received' => $detail['uom_code'],
                            'updated_by'        => $detail['updated_by']
                        ]);

                    if ($check == 0) {
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => "server error"], 500);
                    }
                }

                $material = DB::table('precise.oem_delivery_hd as hd')
                    ->where('hd.oem_delivery_hd_id', $data['oem_delivery_hd_id'])
                    ->whereNotNull('pcm.material_id')
                    ->select(
                        'oem_delivery_dt_id',
                        'pcm.material_id',
                        DB::raw(
                            "CASE delivery_status
                                WHEN 1 THEN  
                                    pcm.material_qty * delivery_qty
                                WHEN 4 THEN 	
                                    pcm.material_qty * received_qty
                                WHEN delivery_status IN (6, 8, 9) THEN 		
                                    0
                                END AS material_used,
                                'GR' as uom_code,
                                'oem_set_delivery_material_usage'"
                        )
                    )
                    ->join('precise.oem_delivery_dt as dt', 'hd.oem_delivery_hd_id', '=', 'dt.oem_delivery_hd_id')
                    ->leftJoin('precise.product_customer as pc', function ($query) {
                        $query->on('hd.customer_id', '=', 'pc.customer_id')
                            ->on('dt.product_id', '=', 'pc.product_id');
                    })
                    ->leftJoin('precise.product_customer_material as pcm', 'pc.product_customer_id', '=', 'pcm.product_customer_id')
                    ->get();

                foreach ($material as $materials) {
                    $check = DB::table('precise.oem_material_usage')
                        ->updateOrInsert([
                            'oem_delivery_dt_id'    => $materials->oem_delivery_dt_id,
                            'material_id'           => $materials->material_id
                        ], [
                            'material_qty'          => $materials->material_used,
                            'uom_code'              => $materials->uom_code,
                            'updated_by'            => $data['updated_by']
                        ]);

                    if (!$check) {
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => "server error"], 500);
                    }
                }

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'oem_delivery_hd_id'    => 'required|exists:oem_delivery_hd,oem_delivery_hd_id',
            'delivery_status'       => 'required|exists:oem_delivery_status,oem_delivery_status_id',
            'received_qty'          => 'required|numeric',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $check = DB::table('precise.oem_delivery_hd')
                ->where('oem_delivery_hd_id', $request->oem_delivery_hd_id)
                ->update([
                    'delivery_status'   => $request->delivery_status,
                    'updated_by'        => $request->updated_by
                ]);

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "server error"], 500);
            }

            $check = DB::table('precise.oem_delivery_dt')
                ->where('oem_delivery_hd_id', $request->oem_delivery_hd_id)
                ->update([
                    'received_qty' => $request->received_qty,
                    'updated_by'   => $request->updated_by
                ]);

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "server error"], 500);
            }

            $materials = DB::table('precise.oem_delivery_hd as hd')
                ->where('hd.oem_delivery_hd_id', $request->oem_delivery_hd_id)
                ->whereNotNull('pcm.material_id')
                ->select(
                    'oem_delivery_dt_id',
                    'pcm.material_id',
                    DB::raw(
                        "CASE delivery_status
                                WHEN 1 THEN  
                                    pcm.material_qty * delivery_qty
                                WHEN 4 THEN 	
                                    pcm.material_qty * received_qty
                                WHEN delivery_status IN (6, 8, 9) THEN 		
                                    0
                                END AS material_used,
                                'GR' as uom_code,
                                'oem_set_delivery_material_usage'"
                    )
                )
                ->join('precise.oem_delivery_dt as dt', 'hd.oem_delivery_hd_id', '=', 'dt.oem_delivery_hd_id')
                ->leftJoin('precise.product_customer as pc', function ($query) {
                    $query->on('hd.customer_id', '=', 'pc.customer_id')
                        ->on('dt.product_id', '=', 'pc.product_id');
                })
                ->leftJoin('precise.product_customer_material as pcm', 'pc.product_customer_id', '=', 'pcm.product_customer_id')
                ->get();

            foreach ($materials as $material) {
                $check = DB::table('precise.oem_material_usage')
                    ->updateOrInsert([
                        'oem_delivery_dt_id' => $material->oem_delivery_dt_id,
                        'material_id'   => $material->material_id
                    ], [
                        'material_qty'  => $material->material_used,
                        'uom_code'      => $material->uom_code,
                        'updated_by'    => $request->updated_by
                    ]);

                if (!$check) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function printCounter(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'oem_delivery_hd_id'    => 'required',
            'delivery_status'       => 'required',
            'updated_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $id = explode("-", $request->oem_delivery_hd_id);

        $this->deliveryOrder = DB::table("precise.oem_delivery_hd")
            ->whereIn("oem_delivery_hd_id", $id)
            ->update([
                "print_count"       => DB::raw("ifnull(print_count,0) + 1"),
                "delivery_status"   => $request->delivery_status
            ]);

        if ($this->deliveryOrder == 0) {
            return response()->json(['status' => 'error', 'message' => "server error"], 500);
        }

        return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'oem_delivery_hd_id'    => 'required|exists:oem_delivery_hd,oem_delivery_hd_id',
            'reason'                => 'required',
            'deleted_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $check = DB::table('precise.oem_delivery_dt')
                ->where('oem_delivery_hd_id', $request->oem_delivery_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "server error"], 500);
            }

            $check = DB::table('precise.oem_delivery_hd')
                ->where('oem_delivery_hd_id', $request->oem_delivery_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "server error"], 500);
            }

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success delete data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
