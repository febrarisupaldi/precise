<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingStockController extends Controller
{
    private $stock;

    public function getBySalesmanCustomerAndStatus($sales)
    {
        $customer = request('customer');
        $status = request('status');

        $this->stock = DB::table('precise.stock_booking as sb')
            ->select(
                'sb.stock_booking_id',
                'sb.salesman_id',
                'e.employee_name',
                'sb.product_id',
                'p.product_code',
                'p.product_name',
                'p.uom_code',
                'sb.booking_qty',
                'sb.booking_type_code',
                'sbt.booking_type_description',
                'sb.customer_id',
                'c.customer_code',
                'c.customer_name',
                DB::raw('TIMESTAMP(sb.booking_date, sb.booking_time) AS booking_datetime'),
                'sb.expiry_date',
                'sb.booking_status_code',
                'sbs.status_description',
                'sb.created_by',
                'sb.sales_order_hd_id',
                'sohd.sales_order_number',
                'sohd.sales_order_date',
                'sodt.sales_order_qty'
            )
            ->leftJoin("precise.employee as e", "sb.salesman_id", "=", "e.employee_nik")
            ->leftJoin("precise.product as p", "sb.product_id", "=", "p.product_id")
            ->leftJoin("precise.customer as c", "sb.customer_id", "=", "c.customer_id")
            ->leftJoin("precise.stock_booking_status as sbs", "sb.booking_status_code", "=", "sbs.booking_status_code")
            ->leftJoin("precise.stock_booking_type as sbt", "sb.booking_type_code", "=", "sbt.booking_type_code")
            ->leftJoin("precise.sales_order_hd as sohd", "sb.sales_order_hd_id", "=", "sohd.sales_order_hd_id")
            ->leftJoin("precise.sales_order_dt as sodt", "sb.sales_order_dt_id", "=", "sodt.sales_order_dt_id")
            ->where('sb.salesman_id', $sales);

        if ($customer != null) {
            $this->stock = $this->stock
                ->where('sb.customer_id', $customer);
        }

        if ($status != null) {
            $this->stock = $this->stock
                ->where('sb.booking_status_code', $status);
        }

        $this->stock = $this->stock->orderBy('sb.stock_booking_id', 'desc')
            ->get();

        if (count($this->stock) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 500);

        return response()->json(["status" => "ok", "data" => $this->stock], 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'salesman_id'           => 'required|exists:users,user_id',
            'product_id'            => 'required|exists:product,product_id',
            'booking_qty'           => 'required',
            'booking_type_code'     => 'required|exists:stock_booking_type,booking_type_code',
            'customer_id'           => 'required|exists:customer,customer_id',
            'booking_date'          => 'required',
            'booking_time'          => 'required',
            'expiry_date'           => 'required',
            'booking_status_code'   => 'required|exists:stock_booking_status,booking_status_code',
            'created_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->stock = DB::table('precise.stock_booking')
            ->insert([
                'salesman_id'           => $request->salesman_id,
                'product_id'            => $request->product_id,
                'booking_qty'           => $request->booking_qty,
                'booking_type_code'     => $request->booking_type_code,
                'customer_id'           => $request->customer_id,
                'booking_date'          => $request->booking_date,
                'booking_time'          => $request->booking_time,
                'expiry_date'           => $request->expiry_date,
                'booking_status_code'   => $request->booking_status_code,
                'created_by'            => $request->created_by
            ]);

        if ($this->stock == 0) {
            return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
        }
        return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
    }
}
