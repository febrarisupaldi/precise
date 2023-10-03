<?php

namespace App\Http\Controllers\Api\Stok;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
{
    private $purchaseOrder;

    public function show($id): JsonResponse
    {
        $this->purchaseOrder = DB::table("dbstok.po_hd", "hd")
            ->select(
                'dt.po_dt_id',
                'dt.po_dt_seq',
                'hd.po_hd_id',
                'hd.po_tgl',
                'hd.po_number',
                'hd.customer_id',
                'c.customer_code',
                'c.customer_name',
                'p.product_id',
                'p.product_code',
                'p.product_name',
                'dt.qty_produk',
                'p.uom_code',
                'hd.po_note'
            )->leftJoin("dbstok.po_dt as dt", "hd.po_hd_id", "=", "dt.po_hd_id")
            ->leftJoin("precise.customer as c", "hd.customer_id", "=", "c.customer_id")
            ->leftJoin("precise.product as p", "dt.id_produk", "=", "p.product_id")
            ->where('hd.po_hd_id', $id)
            ->orderBy('dt.po_dt_seq')
            ->get();
        if (empty($this->purchaseOrder))
            return response()->json("not found", 404);
        return response()->json($this->purchaseOrder, 200);
    }

    public function getCummulativeByDateAndSalesman($sales, $poYear): JsonResponse
    {
        $this->purchaseOrder = DB::table("dbstok.po_hd")
            ->where("salesman_id", "$sales")
            ->where("po_tgl", 'like', "$poYear" . '%')
            ->select(
                "salesman_id",
                DB::raw("
                    DATE_FORMAT(po_tgl, '%Y-%m-%d') 'po_date',
                    COUNT(IF(po_status = 'A', 1, NULL)) 'count_active',
                    COUNT(IF(po_status = 'R', 1, NULL)) 'count_read',
                    COUNT(IF(po_status = 'D', 1, NULL)) 'count_download',
                    COUNT(IF(po_status = 'X', 1, NULL)) 'count_inactive',
                    COUNT(po_status) 'count_total'
                ")
            )
            ->groupBy('po_tgl', 'salesman_id')
            ->get();

        if (count($this->purchaseOrder) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);
        return response()->json(["status" => "ok", "data" => $this->purchaseOrder], 200);
    }

    public function getBySalesmanAndStatus($sales, $status = null): JsonResponse
    {
        $this->purchaseOrder = DB::table("dbstok.po_hd as hd")
            ->select(
                'hd.po_hd_id',
                'hd.po_tgl',
                'hd.po_number',
                'hd.customer_id',
                'c.customer_code',
                'c.customer_name',
                'hd.customer_wilayah',
                'hd.po_note',
                'hd.po_status',
                DB::raw("
                CASE 
                    WHEN hd.po_status = 'A' THEN 'Active'
                    WHEN hd.po_status = 'R' THEN 'Read'
                    WHEN hd.po_status = 'D' THEN 'Download'
                    WHEN hd.po_status = 'X' THEN 'Inactive'
                    ELSE ''
                END as 'status_description'
                "),
                'ad.nik',
                'ad.nama_lengkap'
            )->leftJoin("precise.customer as c", "c.customer_id", "=", "hd.customer_id")
            ->leftJoin("dbstok.admins as ad", "hd.created_by", "=", "ad.nama_lengkap")
            ->where('ad.username', $sales);

        if ($status == null) {
            $this->purchaseOrder = $this->purchaseOrder->orderByDesc('hd.po_hd_id')
                ->get();
        } else {
            $this->purchaseOrder = $this->purchaseOrder->where('hd.po_status', $status)->orderByDesc('hd.po_hd_id')
                ->get();
        }
        if (count($this->purchaseOrder) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);
        return response()->json(["status" => "ok", "data" => $this->purchaseOrder], 200);
    }

    public function createHeader(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'po_tgl'        => 'required|date_format:Y-m-d',
            'customer_id'   => 'required',
            'salesman_id'   => 'required',
            'po_note'       => 'nullable',
            'created_by'    => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->purchaseOrder = DB::table('dbstok.po_hd')
                ->insert([
                    "po_tgl"        => $request->po_tgl,
                    "customer_id"   => $request->customer_id,
                    "salesman_id"   => $request->salesman_id,
                    "po_note"       => $request->po_note,
                    "created_by"    => $request->created_by,
                ]);

            if ($this->purchaseOrder == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
            }

            return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
        }
    }

    public function createDetail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'po_hd_id'      => 'required|exists:po_hd,po_hd_id',
            'id_produk'     => 'required|exists:product,product_id',
            'qty_produk'    => 'required',
            'uom_produk'    => 'required',
            'created_by'    => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            $this->purchase = DB::table('dbstok.po_hd')
                ->insert([
                    'po_hd_id'      => $request->po_hd_id,
                    'id_produk'     => $request->id_produk,
                    'qty_produk'    => $request->qty_produk,
                    'uom_produk'    => $request->uom_produk,
                    'created_by'    => $request->created_by
                ]);

            if ($this->purchase == 0) {
                return response()->json(['status' => 'error', 'message' => 'failed input data'], 500);
            }
            return response()->json(['status' => 'ok', 'message' => 'success input data'], 200);
        }
    }

    public function getLastNumber($number): JsonResponse
    {
        $this->purchase = DB::table("dbstok.po_hd as a")
            ->select(
                DB::raw('MAX(a.po_number) AS no_akhir'),
                'po_hd_id'
            )
            ->leftJoin('dbstok.admins as s', 'a.created_by', '=', 's.nama_lengkap')
            ->where('a.po_number', 'like', $number . '%')
            ->groupBy('po_hd_id')
            ->first();

        if (empty($this->purchase))
            return response()->json("not found", 404);
        return response()->json($this->purchase, 200);
    }
}
