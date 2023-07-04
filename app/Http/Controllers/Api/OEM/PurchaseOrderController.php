<?php

namespace App\Http\Controllers\Api\OEM;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Master\HelperController;
use App\Http\Controllers\Api\Helpers\QueryController;
use Illuminate\Http\JsonResponse;

class PurchaseOrderController extends Controller
{
    private $purchaseOrder;
    public function index(Request $request): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $wh = $request->get('warehouse_id');
        $cust = $request->get('customer_id');

        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start',
            'warehouse_id'  => 'required',
            'customer_id'   => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $warehouse = explode('-', $wh);
        $customer = explode('-', $cust);
        $this->purchaseOrder = DB::table('precise.oem_order_hd as hd')
            ->whereBetween('hd.oem_order_date', [$start, $end])
            ->whereIn('hd.warehouse_id', $warehouse)
            ->whereIn('hd.customer_id', $customer)
            ->select(
                'oem_order_hd_id',
                'oem_order_number',
                'oem_so_number',
                'oem_order_date',
                'hd.customer_id',
                'customer_code',
                'customer_name',
                'hd.warehouse_id',
                'wh.warehouse_code',
                'wh.warehouse_name',
                'hd.shipping_address_id',
                'ca.address',
                'oem_order_description',
                'hd.oem_order_type_id',
                'oem_order_type_name',
                'hd.ppn_type',
                DB::raw("
                    case hd.ppn_type
                    when 'I' then 'Include' 
                    when 'E' then 'Exclude' 
                    when 'N' then 'Non PPN' else 'Unknown'		
                end as ppn_type, 
                case oem_order_status 
                    when 'A' then 'Aktif'
                    when 'X' then 'Close'
                    when 'F' then 'Freeze'
                    when 'P' then 'Pending'
                    when 'H' then 'Hold' else 'Unknown'
                end as oem_order_status
                "),
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
            ->leftJoin('precise.oem_order_type as ot', 'hd.oem_order_type_id', '=', 'ot.oem_order_type_id')
            ->leftJoin('precise.warehouse as wh', 'hd.warehouse_id', '=', 'wh.warehouse_id')
            ->leftJoin('precise.customer_address as ca', 'hd.shipping_address_id', '=', 'ca.customer_address_id')
            ->get();

        if (count($this->purchaseOrder) == 0)
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->purchaseOrder], 200);
    }

    public function show($id): JsonResponse
    {
        try {
            $master = DB::table('precise.oem_order_hd as hd')
                ->where('hd.oem_order_hd_id', $id)
                ->select(
                    'oem_order_hd_id',
                    'oem_order_number',
                    'oem_order_date',
                    'hd.customer_id',
                    'customer_code',
                    'customer_name',
                    'oem_order_description',
                    'hd.oem_order_type_id',
                    'oem_order_type_name',
                    'oem_order_status',
                    'hd.warehouse_id',
                    'warehouse_code',
                    'warehouse_name',
                    'hd.shipping_address_id',
                    'ca.address',
                    'hd.ppn_type',
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
                ->leftJoin('precise.oem_order_type as ot', 'hd.oem_order_type_id', '=', 'ot.oem_order_type_id')
                ->leftJoin('precise.warehouse as wh', 'hd.warehouse_id', '=', 'wh.warehouse_id')
                ->leftJoin('precise.customer_address as ca', 'hd.shipping_address_id', '=', 'ca.customer_address_id')
                ->first();

            if (empty($master))
                return response()->json("not found", 404);

            $detail = DB::table('precise.oem_order_hd as hd')
                ->where('hd.oem_order_hd_id', $master->oem_order_hd_id)
                ->select(
                    'hd.oem_order_hd_id',
                    'dt.oem_order_dt_id',
                    'dt.oem_order_dt_seq',
                    'dt.product_customer_id',
                    'pc.is_order_qty_include_reject',
                    DB::raw("
                        count(odd.oem_delivery_dt_id) as delivery_count
                    "),
                    'pc.product_id',
                    'p.product_code',
                    'p.product_name',
                    'hd.customer_id',
                    'customer_code',
                    'customer_name',
                    'dt.oem_order_qty',
                    'v.sum_delivery_qty as total_delivery_qty',
                    'v.sum_on_going_qty as total_on_going_qty',
                    'v.sum_received_qty as total_received_qty',
                    'v.outstanding_qty',
                    'p.uom_code',
                    'dt.due_date',
                    'dt.loss_tolerance',
                    'dt.created_on',
                    'dt.created_by',
                    'dt.updated_on',
                    'dt.updated_by'
                )
                ->join('precise.oem_order_dt as dt', 'hd.oem_order_hd_id', '=', 'dt.oem_order_hd_id')
                ->leftJoin('precise.oem_delivery_dt as odd', 'dt.oem_order_dt_id', '=', 'odd.oem_order_dt_id')
                ->leftJoin('precise.product_customer as pc', 'dt.product_customer_id', '=', 'pc.product_customer_id')
                ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
                ->leftJoin('precise.product as p', 'pc.product_id', '=', 'p.product_id')
                ->leftJoin('precise.warehouse as w', 'hd.warehouse_id', '=', 'w.warehouse_id')
                ->leftJoin('precise.view_oem_outstanding_po as v', function ($join) {
                    $join->on('hd.oem_order_hd_id', '=', 'v.oem_order_hd_id')
                        ->on('dt.oem_order_dt_id', '=', 'v.oem_order_dt_id');
                })
                ->groupBy('hd.oem_order_hd_id', 'dt.oem_order_dt_id')
                ->get();

            $this->purchaseOrder = array_merge_recursive(
                (array)$master,
                array("detail" => $detail)
            );
            return response()->json($this->purchaseOrder, 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        $validator = Validator::make($data, [
            'oem_order_number'      => 'required',
            'oem_order_date'        => 'required|date_format:Y-m-d',
            'customer_id'           => 'required|exists:customer,customer_id',
            'oem_order_type_id'     => 'required|exists:oem_order_type,oem_order_type_id',
            'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
            'shipping_address_id'   => 'required|exists:customer_address,customer_address_id',
            'desc'                  => 'nullable',
            'ppn_type'              => 'nullable',
            'created_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            $id = DB::table('precise.oem_order_hd')
                ->insertGetId([
                    'oem_order_number'      => $data['oem_order_number'],
                    'oem_order_date'        => $data['oem_order_date'],
                    'customer_id'           => $data['customer_id'],
                    'oem_order_description' => $data['desc'],
                    'oem_order_type'        => $data['oem_order_type_id'],
                    'oem_order_status'      => $data['oem_order_status'],
                    'warehouse_id'          => $data['warehouse_id'],
                    'shipping_address_id'   => $data['shipping_address_id'],
                    'ppn_type'              => $data['ppn_type'],
                    'created_by'            => $data['created_by']
                ]);

            foreach ($data['detail'] as $d) {
                $validator = Validator::make($d, [
                    'oem_order_dt_seq'      => 'required|numeric',
                    'product_customer_id'   => 'required|exists:product_customer,product_customer_id',
                    'oem_order_qty'         => 'required|numeric',
                    'due_date'              => 'required|date_format:Y-m-d',
                    'loss_tolerance'        => 'required|numeric',
                    'created_by'            => 'required'
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                }
                $dt[] = [
                    'oem_order_hd_id'       => $id,
                    'oem_order_dt_seq'      => $d['oem_order_dt_seq'],
                    'product_customer_id'   => $d['product_customer_id'],
                    'oem_order_qty'         => $d['oem_order_qty'],
                    'due_date'              => $d['due_date'],
                    'loss_tolerance'        => $d['loss_tolerance'],
                    'created_by'            => $d['created_by']
                ];
            }

            $check = DB::table('precise.oem_order_dt')
                ->insert($dt);

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "server error"], 500);
            }

            $trans = DB::table('precise.oem_order_hd')
                ->where('oem_order_hd_id', $id)
                ->select('oem_order_number')
                ->first();

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => $trans->oem_order_number], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        $validator = Validator::make($data, [
            'oem_order_number'      => 'required',
            'oem_order_date'        => 'required|date_format:Y-m-d',
            'customer_id'           => 'required|exists:customer,customer_id',
            'oem_order_type_id'     => 'required|exists:oem_order_type,oem_order_type_id',
            'warehouse_id'          => 'required|exists:warehouse, warehouse_id',
            'shipping_address_id'   => 'required|exists:customer_address, customer_address_id',
            'desc'                  => 'nullable',
            'ppn_type'              => 'nullable',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update", $data);
            DB::table('precise.oem_order_hd')
                ->where('oem_order_hd_id', $data['oem_order_hd_id'])
                ->update([
                    'oem_order_number'      => $data['oem_order_number'],
                    'oem_order_date'        => $data['oem_order_date'],
                    'customer_id'           => $data['customer_id'],
                    'oem_order_description' => $data['oem_order_description'],
                    'oem_order_type_id'     => $data['oem_order_type_id'],
                    'warehouse_id'          => $data['warehouse_id'],
                    'shipping_address_id'   => $data['shipping_address_id'],
                    'ppn_type'              => $data['ppn_type'],
                    'created_by'            => $data['created_by']
                ]);

            if ($data['inserted'] != null) {
                foreach ($data['inserted'] as $d) {
                    $dt[] = [
                        'oem_order_hd_id'       => $d['oem_order_hd_id'],
                        'oem_order_dt_seq'      => $d['oem_order_dt_seq'],
                        'product_customer_id'   => $d['product_customer_id'],
                        'oem_order_qty'         => $d['oem_order_qty'],
                        'due_date'              => $d['due_date'],
                        'loss_tolerance'        => $d['loss_tolerance'],
                        'created_by'            => $d['created_by']
                    ];
                }
                $check = DB::table('precise.oem_order_dt')
                    ->insert($dt);

                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }
            }

            if ($data['updated'] != null) {
                foreach ($data['updated'] as $d) {
                    $check = DB::table('precise.oem_order_dt')
                        ->where('oem_order_dt_id', $d['oem_order_dt_id'])
                        ->update([
                            'oem_order_hd_id'       => $d['oem_order_hd_id'],
                            'product_customer_id'   => $d['product_customer_id'],
                            'oem_order_qty'         => $d['oem_order_qty'],
                            'due_date'              => $d['due_date'],
                            'loss_tolerance'        => $d['loss_tolerance'],
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
                    $delete[] = $del['oem_order_dt_id'];
                }

                $check = DB::table('precise.oem_order_dt')
                    ->whereIn('oem_order_dt_id', $delete)
                    ->delete();

                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'oem_order_hd_id'   => 'required|exists:oem_order_hd,oem_order_hd_id',
            'reason'            => 'required',
            'deleted_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $check = DB::table('precise.oem_order_dt')
                ->where('oem_order_hd_id', $request->oem_order_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "server error"], 500);
            }

            $check = DB::table('precise.oem_order_hd')
                ->where('oem_order_hd_id', $request->oem_order_hd_id)
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

    public function destroyPurchaseOrder(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'oem_order_number'  => 'required|exists:oem_order_hd,oem_order_number',
                'customer_id'       => 'required|exists:customer,customer_id',
                'warehouse_id'      => 'required|exists:warehouse,warehouse_id',
                'deleted_by'        => 'required',
                'reason'            => 'required'
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            DB::beginTransaction();
            try {
                DBController::reason($request, "delete");

                $check = DB::table('precise.oem_order_dt as dt')
                    ->where('oem_order_number', $request->oem_order_number)
                    ->where('customer_id', $request->customer_id)
                    ->where('warehouse_id', $request->warehouse_id)
                    ->join('precise.oem_order_hd as hd', 'hd.oem_order_hd_id', '=', 'dt.oem_order_hd_id')
                    ->delete();

                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }

                $check = DB::table('precise.oem_order_hd')
                    ->where('oem_order_number', $request->oem_order_number)
                    ->where('customer_id', $request->customer_id)
                    ->where('warehouse_id', $request->warehouse_id)
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

    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'oem_order_number'  => 'required',
                'customer_id'       => 'required',
                'warehouse_id'      => 'required'
            ]
        );
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->purchaseOrder = DB::table('precise.oem_order_hd')
                ->where('oem_order_number', $request->oem_order_number)
                ->where('customer_id', $request->customer_id)
                ->where('warehouse_id', $request->warehouse_id)
                ->count();
            if ($this->purchaseOrder == 0)
                return response()->json(['status' => 'error', 'message' => $this->purchaseOrder], 404);
            return response()->json(['status' => 'ok', 'message' => $this->purchaseOrder], 200);
        }
    }

    public function joined(Request $request): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $wh = $request->get('warehouse_id');
        $cust = $request->get('customer_id');

        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start',
            'warehouse_id'  => 'required',
            'customer_id'   => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $warehouse = explode('-', $wh);
            $customer = explode('-', $cust);

            $this->purchaseOrder =
                DB::table('precise.oem_order_hd as hd')
                ->whereBetween('hd.oem_order_date', [$start, $end])
                ->whereIn('hd.customer_id', $customer)
                ->whereIn('hd.warehouse_id', $warehouse)
                ->select(
                    'hd.oem_order_hd_id',
                    'hd.oem_order_number',
                    'hd.oem_order_date',
                    'hd.oem_so_number',
                    'hd.customer_id',
                    'customer_code',
                    'customer_name',
                    'hd.warehouse_id',
                    'wh.warehouse_code',
                    'wh.warehouse_name',
                    'hd.oem_order_description',
                    'oem_order_type_name',
                    DB::raw("case hd.ppn_type
                                when 'I' then 'Include' 
                                when 'E' then 'Exclude' 
                                when 'N' then 'Non PPN' else 'Unknown'		
                            end ppn_type, 
                            case hd.oem_order_status 
                                when 'A' then 'Aktif'
                                when 'X' then 'Close'
                                when 'F' then 'Freeze'
                            end oem_order_status
                            "),
                    'dt.oem_order_dt_seq',
                    'prod.product_code',
                    'prod.product_name',
                    'dt.oem_order_dt_id',
                    'dt.oem_order_qty',
                    'v.sum_delivery_qty',
                    'v.sum_on_going_qty',
                    'v.sum_received_qty',
                    'v.outstanding_qty',
                    'dt.due_date',
                    DB::raw("datediff(dt.due_date, date(now())) as days_left"),
                    'dt.loss_tolerance',
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->leftJoin('precise.oem_order_dt as dt', 'hd.oem_order_hd_id', '=', 'dt.oem_order_hd_id')
                ->leftJoin('precise.customer as cust', 'hd.customer_id', '=', 'cust.customer_id')
                ->leftJoin('precise.oem_order_type as ot', 'hd.oem_order_type_id', '=', 'ot.oem_order_type_id')
                ->leftJoin('precise.product_customer as pc', 'dt.product_customer_id', '=', 'pc.product_customer_id')
                ->leftJoin('precise.product as prod', 'pc.product_id', '=', 'prod.product_id')
                ->leftJoin('precise.warehouse as wh', 'hd.warehouse_id', '=', 'wh.warehouse_id')
                ->leftJoin('precise.view_oem_outstanding_po as v', function ($join) {
                    $join->on('hd.oem_order_hd_id', '=', 'v.oem_order_hd_id')
                        ->on('dt.oem_order_dt_id', '=', 'v.oem_order_dt_id')
                        ->on('hd.warehouse_id', '=', 'v.warehouse_id');
                })
                ->get();
            if (count($this->purchaseOrder) == 0)
                return response()->json(['status' => 'error', 'data' => "not found"], 404);
            return response()->json(['status' => 'ok', 'data' => $this->purchaseOrder], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function remaining($id): JsonResponse
    {
        DB::statement(DB::raw('SET @startDate := precise.get_beginning_date_from_date(sysdate())'));
        DB::statement(DB::raw('SET @endDate := date(sysdate())'));

        $sub = DB::query()
            ->select(
                DB::raw('@startDate as startDate'),
                DB::raw('@endDate as endDate')
            );

        $this->purchaseOrder =
            DB::table(DB::raw('(' . $sub->toSql() . ') as p'))
            ->where('v.oem_order_hd_id', $id)
            ->select(
                'v.oem_order_dt_id',
                'v.product_id',
                'v.product_code',
                'v.product_name',
                'pc.oem_material_supply_type',
                'pldt.price_idr',
                'v.oem_order_qty',
                'v.due_date',
                'v.oem_order_dt_seq',
                'v.total_on_going_qty',
                'v.total_received_qty',
                'v.outstanding_qty',
                DB::raw('ifnull(min(psq), 0) as PSQ'),
                DB::raw('0 as delivery_qty'),
                'v.uom_code',
                DB::raw('0 as packaging_id'),
                DB::raw('null as packaging_code'),
                DB::raw('null as packaging_name'),
                DB::raw('0 as packaging_qty'),
                DB::raw('null as packaging_uom_code'),
                DB::raw('null as packaging_description')
            )
            ->join('precise.view_oem_permitted_shipping_qty as v', 'v.oem_order_hd_id', '=', 'v.oem_order_hd_id')
            ->leftJoin('precise.customer as c', 'v.customer_id', '=', 'c.customer_id')
            ->leftJoin('precise.price_list_hd as plhd', function ($join) {
                $join
                    ->on('plhd.price_group_code', '=', 'c.price_group_code')
                    ->where('plhd.price_status', '=', 'A');
            })
            ->leftJoin('precise.price_list_dt as pldt', function ($join) {
                $join->on('plhd.price_group_code', '=', 'pldt.price_group_code')
                    ->on('pldt.price_group_seq', '=', 'plhd.price_seq')
                    ->on('v.product_code', '=', 'pldt.product_code');
            })
            ->leftJoin('precise.product_customer as pc', 'v.product_customer_id', '=', 'pc.product_customer_id')
            ->mergeBindings($sub)
            ->groupBy('v.oem_order_dt_id', 'pldt.price_idr')
            ->get();
        if (count($this->purchaseOrder) == 0)
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->purchaseOrder], 200);
    }

    public function outstandingValidation(Request $request): JsonResponse
    {
        $product = $request->get('product_id');
        $customer = $request->get('customer_id');

        $validator = Validator::make($request->all(), [
            'product_id'    => 'required|exists:product,product_id',
            'customer_id'   => 'required|exists:customer,customer_id'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->purchaseOrder = DB::table("precise.oem_order_hd as hd")
            ->where('hd.oem_order_status', '!=', 'X')
            ->where('hd.customer_id', $customer)
            ->where('pc.product_id', $product)
            ->groupBy('hd.oem_order_hd_id', 'hd.oem_order_number', 'hd.customer_id', 'dt.product_customer_id', 'dt.oem_order_qty')
            ->having('outstanding', '!=', 0)
            ->having('dt.oem_order_qty', '!=', 'outstanding')
            ->select(
                'hd.oem_order_hd_id',
                'hd.oem_order_number',
                'hd.customer_id',
                'pc.product_id',
                'dt.product_customer_id',
                'dt.oem_order_qty',
                DB::raw("
                SUM(IF(ods.is_delivery = 1, odd.delivery_qty, 0)) AS sum_delivery_qty,
                SUM(IF(ods.is_on_going = 1, odd.delivery_qty, 0)) AS sum_on_going_qty,
                SUM(IF(ods.is_received = 1, odd.received_qty, 0)) AS sum_received_qty,
                dt.oem_order_qty - SUM(IF(ods.is_on_going = 1, odd.delivery_qty, 0)) - SUM(IF(ods.is_received = 1, odd.received_qty, 0)) outstanding
                ")
            )
            ->leftJoin('precise.oem_order_dt as dt', 'hd.oem_order_hd_id', '=', 'dt.oem_order_hd_id')
            ->leftJoin('precise.product_customer as pc', 'dt.product_customer_id', '=', 'pc.product_customer_id')
            ->leftJoin('precise.oem_delivery_dt as odd', 'dt.oem_order_dt_id', '=', 'odd.oem_order_dt_id')
            ->leftJoin('precise.oem_delivery_hd as odh', 'odd.oem_delivery_hd_id', '=', 'odh.oem_delivery_hd_id')
            ->leftJoin('precise.oem_delivery_status as ods', 'odh.delivery_status', '=', 'ods.oem_delivery_status_id')
            ->get();

        if (count($this->purchaseOrder) == 0)
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->purchaseOrder], 200);
    }

    public function outstandingLookup(Request $request): JsonResponse
    {
        $warehouse = $request->get('warehouse_id');
        $customer = $request->get('customer_id');
        $id = $request->get('id');

        $validator = Validator::make($request->all(), [
            'id'            => 'required|exists:oem_order_hd,oem_order_hd_id',
            'warehouse_id'  => 'required|exists:warehouse,warehouse_id',
            'customer_id'   => 'required|exists:customer,customer_id'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->purchaseOrder = DB::table('precise.view_oem_outstanding_po as v')
            ->where('v.customer_id', $customer)
            ->where('v.warehouse_id', $warehouse)
            ->where('outstanding_qty', '>', 0)
            ->orWhere('v.oem_order_hd_id', $id)
            ->select(
                'v.oem_order_hd_id',
                'v.oem_order_number',
                'ooh.oem_so_number',
                'v.oem_order_date',
                DB::raw("datediff(due_date, date(now())) as days_left"),
                'pc.product_id',
                'product_code',
                'product_name',
                'oem_order_qty',
                'v.sum_on_going_qty as total_on_going_qty',
                'v.sum_received_qty as total_received_qty',
                'v.outstanding_qty as outstanding_qty',
                'due_date',
                'v.oem_order_description',
                'ca.address'
            )
            ->leftJoin('precise.product_customer as pc', 'v.product_customer_id', '=', 'pc.product_customer_id')
            ->leftJoin('precise.product as p', 'pc.product_id', '=', 'p.product_id')
            ->leftJoin('precise.oem_order_hd as ooh', 'v.oem_order_hd_id', '=', 'ooh.oem_order_hd_id')
            ->leftJoin('precise.customer_address as ca', 'ooh.shipping_address_id', '=', 'ca.customer_address_id')
            ->get();
        if (count($this->purchaseOrder) == 0)
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->purchaseOrder], 200);
    }

    public function outstandingSchedule(Request $request): JsonResponse
    {
        $customer = $request->get('customer_id');
        $warehouse = $request->get('warehouse_id');
        $order_date = $request->get('order_date');
        $product_customer = $request->get('product_customer_id');

        $validator = Validator::make($request->all(), [
            'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
            'customer_id'           => 'required|exists:customer,customer_id',
            'order_date'            => 'required|date_format:Y-m-d',
            'product_customer_id'   => 'required|exists:product_customer,product_customer_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $po = DB::table('precise.oem_order_hd as hd')
            ->where('hd.customer_id', $customer)
            ->where('hd.warehouse_id', $warehouse)
            ->where('hd.oem_order_date', $order_date)
            ->where('dt.product_customer_id', $product_customer)
            ->select(
                'hd.oem_order_hd_id',
                'hd.oem_order_number',
                'hd.oem_order_date',
                'dt.due_date',
                'hd.oem_order_status',
                'dt.oem_order_dt_id',
                'dt.oem_order_qty'
            )
            ->join('precise.oem_order_dt as dt', 'hd.oem_order_hd_id', '=', 'dt.oem_order_hd_id');

        $delivery = DB::table('precise.oem_order_hd as hd')
            ->where('hd.customer_id', $customer)
            ->where('hd.warehouse_id', $warehouse)
            ->where('hd.oem_order_date', $order_date)
            ->where('odh.oem_delivery_date', $order_date)
            ->where('dt.product_customer_id', $product_customer)
            ->select(
                'hd.oem_order_hd_id',
                'dt.oem_order_dt_id',
                'dt.oem_order_qty',
                DB::raw("
                        sum(if(ods.is_delivery = 1, odd.`delivery_qty`, 0)) as sum_delivery_qty,
                        sum(if(ods.is_on_going = 1, odd.delivery_qty, 0)) as sum_on_going_qty,
                        sum(if(ods.is_received = 1, odd.received_qty, 0)) as sum_received_qty		     
                    ")
            )
            ->join('precise.oem_order_dt as dt', 'hd.oem_order_hd_id', '=', 'dt.oem_order_hd_id')
            ->leftJoin('precise.oem_delivery_dt as odd', 'dt.oem_order_dt_id', '=', 'odd.oem_order_dt_id')
            ->join('precise.oem_delivery_hd as odh', 'odd.oem_delivery_hd_id', '=', 'odh.oem_delivery_hd_id')
            ->leftJoin('precise.oem_delivery_status as ods', 'odh.delivery_status', '=', 'ods.oem_delivery_status_id')
            ->groupBy('hd.oem_order_hd_id', 'dt.oem_order_dt_id');

        $this->purchaseOrder = DB::table(DB::raw("({$po->toSql()})po"))
            ->mergeBindings($po)
            ->mergeBindings($delivery)
            ->where(DB::raw("po.`oem_order_qty` - ifnull(sum_on_going_qty, 0) - ifnull(sum_received_qty, 0)"), '>', 0)
            ->select(
                'po.oem_order_hd_id',
                'po.oem_order_number',
                'po.oem_order_date',
                'po.due_date',
                'po.oem_order_status',
                'po.oem_order_dt_id',
                'po.oem_order_qty',
                DB::raw("
                    ifnull(sum_delivery_qty, 0) total_delivery_qty,
                    ifnull(sum_on_going_qty, 0) total_on_going_qty,
                    ifnull(sum_received_qty,0) total_received_qty,
                    po.`oem_order_qty` - ifnull(sum_on_going_qty, 0) - ifnull(sum_received_qty, 0) as outstanding_qty
                ")
            )
            ->leftJoin(DB::raw("({$delivery->toSql()})delivery"), function ($join) {
                $join->on('po.oem_order_hd_id', '=', 'delivery.oem_order_hd_id')
                    ->on('po.oem_order_dt_id', '=', 'delivery.oem_order_dt_id');
            })
            ->get();

        if (count($this->purchaseOrder) == 0)
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->purchaseOrder], 200);
    }
}
