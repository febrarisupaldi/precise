<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockDiagramController extends Controller
{
    private $stock;
    public function index($id): JsonResponse
    {
        $this->stock = DB::select('call precise.warehouse_get_stock_diagram(?)', [$id]);
        return response()->json(['status' => 'ok', 'data' => $this->stock], 200);
    }

    public function getStockDiagramByWarehouseAndProduct($warehouse, $product)
    {
        $this->stock = DB::select("call precise.warehouse_get_stock_diagram_by_product(?,?)", [$warehouse, $product]);
        if (count($this->stock) == 0)
            return response()->json(['status' => 'error', 'data' => "not found"], 404);
        return response()->json(['status' => 'ok', 'data' => $this->stock], 200);
    }

    public function getStockDiagram2($id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $temporary = DB::select('call precise.warehouse_get_stock_diagram_2(?)', [$id]);
            if (empty($temporary)) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'data' => "server error"], 500);
            }

            $this->stock = DB::table("precise.tmp_warehouse_stock_diagram as d")
                ->select(
                    'd.product_id',
                    'd.warehouse_id',
                    'd.product_code',
                    'pi.item_code',
                    'pds.design_code',
                    'pds.appearance_id',
                    'pa.appearance_name',
                    'pd.packing_qty',
                    'd.avg_so_qty',
                    'd.move_level',
                    'd.product_name',
                    'd.uom_code',
                    'd.warehouse',
                    'd.current_stock',
                    'd.outstanding_so',
                    'd.unprocessed_do',
                    'd.free_stock_estimation',
                    'd.total_booking',
                    'd.free_to_book'
                )
                ->leftJoin("precise.product_dictionary as pd", "d.product_id", "=", "pd.product_id")
                ->leftJoin("precise.product_item as pi", "pd.item_id", "=", "pi.item_id")
                ->leftJoin("precise.product_design as pds", "pd.design_id", "=", "pds.design_id")
                ->leftJoin("precise.product_appearance as pa", "pds.appearance_id", "=", "pa.appearance_id")
                ->get();

            DB::commit();
            return response()->json(['status' => 'ok', 'data' => $this->stock], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getStockDiagram2ByItemCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'wh_id'         => 'required|exists:warehouse,warehouse_id',
            'item_code'     => 'required|exists:product_item,item_code',
            'design_code'   => 'nullable|exists:product_design,design_code',
            'uom_code'      => 'required|exists:uom,uom_code',
            'appearance_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $appearance = explode("-", $request->appearance_id);
            DB::beginTransaction();

            $designCode = $request->design_code ?: null;
            DB::select('call precise.warehouse_get_stock_diagram_2(?)', [$request->wh_id]);

            $this->stock = DB::table("precise.tmp_warehouse_stock_diagram as d")->select(
                'd.product_id',
                'd.warehouse_id',
                'd.product_code',
                'pi.item_code',
                'pds.design_code',
                'pds.appearance_id',
                'pa.appearance_name',
                'pd.packing_qty',
                'd.avg_so_qty',
                'd.move_level',
                'd.product_name',
                'd.uom_code',
                'd.warehouse',
                'd.current_stock',
                'd.outstanding_so',
                'd.unprocessed_do',
                'd.free_stock_estimation',
                'd.total_booking',
                'd.free_to_book'
            )->leftJoin("precise.product_dictionary as pd", "d.product_id", "=", "pd.product_id")
                ->leftJoin("precise.product_item as pi", "pd.item_id", "=", "pi.item_id")
                ->leftJoin("precise.product_design as pds", "pd.design_id", "=", "pds.design_id")
                ->leftJoin("precise.product_appearance as pa", "pds.appearance_id", "=", "pa.appearance_id")
                ->where('pi.item_code', $request->item_code)
                ->where('design_code', 'like', '%' . $designCode . '%')
                ->where('uom_code', $request->uom_code)
                ->whereIn('pds.appearance_id', $appearance)
                ->get();

            DB::commit();

            return response()->json(['status' => 'ok', 'data' => $this->stock], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getOutstandingSO($warehouseID, $productID): JsonResponse
    {
        $query = DB::table('precise.sales_order_hd as hd')
            ->where('hd.warehouse_id', $warehouseID)
            ->whereNotIn('hd.sales_order_status', ['C', 'X'])
            ->where('product_id', $productID)
            ->select(
                'dt.sales_order_number',
                'sales_order_seq',
                'sales_order_date',
                'product_id',
                'warehouse_id',
                'sales_order_qty'
            )
            ->join('precise.sales_order_dt as dt', 'hd.sales_order_hd_id', '=', 'dt.sales_order_hd_id');

        $query = DB::table(DB::raw('(' . $query->toSql() . ') as SO'))
            ->mergeBindings($query)
            ->select(
                'SO.sales_order_number',
                'SO.sales_order_seq',
                'SO.product_id',
                'warehouse_id',
                DB::raw(
                    "
                        cast(avg(sales_order_qty) as decimal(15,4)) as so_qty,
                        sum(ifnull(dt.delivery_order_qty, 0)) as do_qty,
                        cast(avg(sales_order_qty) as decimal(15,4)) - sum(ifnull(dt.delivery_order_qty, 0)) as outstanding_so
                    "
                )
            )
            ->leftJoin('precise.delivery_order_dt as dt', function ($join) {
                $join->on('SO.sales_order_number', '=', 'dt.sales_order_number');
                $join->on('SO.sales_order_seq', '=', 'dt.sales_order_seq');
            })
            ->groupBy('SO.sales_order_number', 'SO.sales_order_seq', 'SO.product_id', 'SO.warehouse_id');

        $this->stock = DB::table(DB::raw('(' . $query->toSql() . ') as outstanding'))
            ->where('outstanding_so', '!=', 0)
            ->mergeBindings($query)
            ->select(
                'outstanding.sales_order_number',
                'outstanding.sales_order_seq',
                'hd.sales_order_date',
                'hd.cancel_date',
                'hd.sales_order_status',
                'so_qty',
                'do_qty',
                'outstanding_so',
                'c.customer_code',
                'c.customer_name'
            )
            ->leftJoin('precise.sales_order_hd as hd', 'outstanding.sales_order_number', '=', 'hd.sales_order_number')
            ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
            ->get();

        return response()->json(['status' => 'ok', 'data' => $this->stock], 200);
    }

    public function getDOInProcess($warehouseID, $productID): JsonResponse
    {
        $this->stock = DB::table('precise.delivery_order_hd as hd')
            ->where('hd.warehouse_id', $warehouseID)
            ->whereNotIn('hd.delivery_order_status', ['C', 'X'])
            ->where('dt.product_id', $productID)
            ->select(
                'hd.delivery_order_number',
                'dt.delivery_order_seq',
                'dt.delivery_order_qty',
                'hd.delivery_order_date',
                'hd.delivery_order_status',
                'dt.sales_order_number',
                'dt.sales_order_seq',
                'c.customer_code',
                'c.customer_name'
            )
            ->join('precise.delivery_order_dt as dt', 'hd.delivery_order_hd_id', '=', 'dt.delivery_order_hd_id')
            ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
            ->get();

        return response()->json(['status' => 'ok', 'data' => $this->stock], 200);
    }

    public function getSOHistory(Request $request, $warehouseID, $productID): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            $this->stock = DB::table('precise.sales_order_hd as hd')
                ->where('hd.warehouse_id', $warehouseID)
                ->whereBetween('hd.sales_order_date', [$start, $end])
                ->where('product_id', $productID)
                ->select(
                    'sales_order_date',
                    DB::raw(
                        "precise.get_friendly_date(sales_order_date) as so_friendly_date"
                    ),
                    'customer_code',
                    'customer_name',
                    DB::raw(
                        "concat(customer_code, ' - ', customer_name) as customer"
                    ),
                    'sales_order_qty'
                )
                ->join('precise.sales_order_dt as dt', 'hd.sales_order_hd_id', '=', 'dt.sales_order_hd_id')
                ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
                ->get();

            return response()->json(['status' => 'ok', 'data' => $this->stock], 200);
        }
    }
}
