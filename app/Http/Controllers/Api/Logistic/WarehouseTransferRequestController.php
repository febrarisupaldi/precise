<?php

namespace App\Http\Controllers\Api\Logistic;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WarehouseTransferRequestController extends Controller
{
    private $warehouseTransferRequest;
    public function show($id): JsonResponse
    {
        $header = DB::table("precise.warehouse_trans_request_hd as a")
            ->where("a.wh_trans_req_hd_id", $id)
            ->select(
                'a.wh_trans_req_hd_id',
                'a.trans_req_number',
                'a.request_date',
                'a.request_wh_from',
                'b.warehouse_code AS warehouse_code_from',
                'b.warehouse_name AS warehouse_name_from',
                'a.request_wh_to',
                'c.warehouse_code AS warehouse_code_to',
                'c.warehouse_name AS warehouse_name_to',
                'a.request_by',
                'd.NAMA AS request_by_name',
                'a.request_approve_by',
                'e.NAMA AS request_approve_by_name',
                'a.request_accepted_by',
                'f.NAMA AS request_accepted_by_name',
                'a.request_accepted_appr_by',
                'g.NAMA AS request_accepted_appr_by_name',
                'a.request_accepted_on',
                'a.request_accepted_appr_on',
                'a.warehouse_trans_number_out',
                'a.warehouse_trans_number_in',
                'a.is_handed',
                'a.request_description',
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin("precise.warehouse as b", "a.request_wh_from", "=", "b.warehouse_id")
            ->leftJoin("precise.warehouse as c", "a.request_wh_to", "=", "c.warehouse_id")
            ->leftJoin("dbhrd.newdatakar as d", "a.request_by", "=", "d.NIP")
            ->leftJoin("dbhrd.newdatakar as e", "a.request_approve_by", "=", "e.NIP")
            ->leftJoin("dbhrd.newdatakar as f", "a.request_accepted_by", "=", "f.NIP")
            ->leftJoin("dbhrd.newdatakar as g", "a.request_accepted_appr_by", "=", "g.NIP")
            ->first();

        if (empty($header)) {
            return response()->json("not found", 404);
        }

        $detail = DB::table("precise.warehouse_trans_request_dt as dt")
            ->where("dt.wh_trans_req_hd_id", $id)
            ->select(
                'dt.wh_trans_req_hd_id',
                'dt.wh_trans_req_dt_id',
                'dt.product_id',
                'p.product_code',
                'p.product_name',
                'dt.request_qty',
                'dt.approved_qty',
                'dt.product_uom',
                'dt.created_on',
                'dt.created_by',
                'dt.updated_on',
                'dt.updated_by'
            )
            ->leftJoin("precise.product as p", "dt.product_id", "=", "p.product_id")
            ->get();

        $this->warehouseTransferRequest = array_merge_recursive(
            (array)$header,
            array("detail" => $detail)
        );

        return response()->json($this->warehouseTransferRequest, 200);
    }

    public function showDetailRequestOut($warehouse, Request $request): JsonResponse
    {
        $wh = explode("-", $warehouse);
        $start = $request->start;
        $end = $request->end;

        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        try {
            $this->warehouseTransferRequest = DB::table("precise.warehouse_trans_request_hd as a")
                ->whereIn("a.request_wh_to", $wh)
                ->whereBetween("a.request_date", [$start, $end])
                ->select(
                    'a.wh_trans_req_hd_id',
                    'a.trans_req_number',
                    'a.request_date',
                    'a.request_wh_from',
                    'b.warehouse_code AS warehouse_code_from',
                    'b.warehouse_name AS warehouse_name_from',
                    'a.request_wh_to',
                    'c.warehouse_code AS warehouse_code_to',
                    'c.warehouse_name AS warehouse_name_to',
                    'a.request_by',
                    'd.NAMA AS request_by_name',
                    'a.request_approve_by',
                    'e.NAMA AS request_approve_by_name',
                    'a.request_accepted_by',
                    'f.NAMA AS request_accepted_by_name',
                    'a.request_accepted_appr_by',
                    'g.NAMA AS request_accepted_appr_by_name',
                    'dt.wh_trans_req_dt_id',
                    'dt.product_id',
                    'p.product_code',
                    'p.product_name',
                    'dt.request_qty',
                    'dt.approved_qty',
                    'dt.product_uom',
                    'a.is_handed',
                    'a.warehouse_trans_number_in',
                    'a.warehouse_trans_number_out',
                    'a.request_description',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin("precise.warehouse_trans_request_dt as dt", "a.wh_trans_req_hd_id", "=", "dt.wh_trans_req_hd_id")
                ->leftJoin("precise.warehouse as b", "a.request_wh_from", "=", "b.warehouse_id")
                ->leftJoin("precise.warehouse as c", "a.request_wh_to", "=", "c.warehouse_id")
                ->leftJoin("dbhrd.newdatakar as d", "a.request_by", "=", "d.NIP")
                ->leftJoin("dbhrd.newdatakar as e", "a.request_approve_by", "=", "e.NIP")
                ->leftJoin("dbhrd.newdatakar as f", "a.request_accepted_by", "=", "f.NIP")
                ->leftJoin("dbhrd.newdatakar as g", "a.request_accepted_appr_by", "=", "g.NIP")
                ->leftJoin("precise.product as p", "dt.product_id", "=", "p.product_id")
                ->get();

            if (count($this->warehouseTransferRequest) == 0)
                return response()->json(["status" => "error", "message" => "not found"], 404);
            return response()->json(["status" => "ok", "data" => $this->warehouseTransferRequest], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function showDetailRequestIn($warehouse, Request $request): JsonResponse
    {
        $wh = explode("-", $warehouse);
        $start = $request->start;
        $end = $request->end;

        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        try {
            $this->warehouseTransferRequest = DB::table("precise.warehouse_trans_request_hd as a")
                ->whereIn("a.request_wh_from", $wh)
                ->whereBetween("a.request_date", [$start, $end])
                ->select(
                    'a.wh_trans_req_hd_id',
                    'a.trans_req_number',
                    'a.request_date',
                    'a.request_wh_from',
                    'b.warehouse_code AS warehouse_code_from',
                    'b.warehouse_name AS warehouse_name_from',
                    'a.request_wh_to',
                    'c.warehouse_code AS warehouse_code_to',
                    'c.warehouse_name AS warehouse_name_to',
                    'a.request_by',
                    'd.NAMA AS request_by_name',
                    'a.request_approve_by',
                    'e.NAMA AS request_approve_by_name',
                    'a.request_accepted_by',
                    'f.NAMA AS request_accepted_by_name',
                    'a.request_accepted_appr_by',
                    'g.NAMA AS request_accepted_appr_by_name',
                    'dt.wh_trans_req_dt_id',
                    'dt.product_id',
                    'p.product_code',
                    'p.product_name',
                    'dt.request_qty',
                    'dt.approved_qty',
                    'dt.product_uom',
                    'a.is_handed',
                    'a.warehouse_trans_number_in',
                    'a.warehouse_trans_number_out',
                    'a.request_description',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin("precise.warehouse_trans_request_dt as dt", "a.wh_trans_req_hd_id", "=", "dt.wh_trans_req_hd_id")
                ->leftJoin("precise.warehouse as b", "a.request_wh_from", "=", "b.warehouse_id")
                ->leftJoin("precise.warehouse as c", "a.request_wh_to", "=", "c.warehouse_id")
                ->leftJoin("dbhrd.newdatakar as d", "a.request_by", "=", "d.NIP")
                ->leftJoin("dbhrd.newdatakar as e", "a.request_approve_by", "=", "e.NIP")
                ->leftJoin("dbhrd.newdatakar as f", "a.request_accepted_by", "=", "f.NIP")
                ->leftJoin("dbhrd.newdatakar as g", "a.request_accepted_appr_by", "=", "g.NIP")
                ->leftJoin("precise.product as p", "dt.product_id", "=", "p.product_id")
                ->get();

            if (count($this->warehouseTransferRequest) == 0)
                return response()->json(["status" => "error", "message" => "not found"], 404);
            return response()->json(["status" => "ok", "data" => $this->warehouseTransferRequest], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function showRequestOut($warehouse, Request $request)
    {
        $wh = explode("-", $warehouse);
        $start = $request->start;
        $start = $request->start;
        $end = $request->end;

        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $this->warehouseTransferRequest = DB::table("precise.warehouse_trans_request_hd as a")
                ->whereIn("a.request_wh_to", $wh)
                ->whereBetween("a.request_date", [$start, $end])
                ->select(
                    'a.wh_trans_req_hd_id',
                    'a.trans_req_number',
                    'a.request_date',
                    'a.request_wh_from',
                    'b.warehouse_code AS warehouse_code_from',
                    'b.warehouse_name AS warehouse_name_from',
                    'a.request_wh_to',
                    'c.warehouse_code AS warehouse_code_to',
                    'c.warehouse_name AS warehouse_name_to',
                    'a.request_by',
                    'd.NAMA AS request_by_name',
                    'a.request_approve_by',
                    'e.NAMA AS request_approve_by_name',
                    'a.request_accepted_by',
                    'f.NAMA AS request_accepted_by_name',
                    'a.request_accepted_appr_by',
                    'g.NAMA AS request_accepted_appr_by_name',
                    'a.is_handed',
                    'a.warehouse_trans_number_in',
                    'a.warehouse_trans_number_out',
                    'a.request_description',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin("precise.warehouse as b", "a.request_wh_from", "=", "b.warehouse_id")
                ->leftJoin("precise.warehouse as c", "a.request_wh_to", "=", "c.warehouse_id")
                ->leftJoin("dbhrd.newdatakar as d", "a.request_by", "=", "d.NIP")
                ->leftJoin("dbhrd.newdatakar as e", "a.request_approve_by", "=", "e.NIP")
                ->leftJoin("dbhrd.newdatakar as f", "a.request_accepted_by", "=", "f.NIP")
                ->leftJoin("dbhrd.newdatakar as g", "a.request_accepted_appr_by", "=", "g.NIP")
                ->get();

            if (count($this->warehouseTransferRequest) == 0)
                return response()->json(["status" => "error", "message" => "not found"], 404);
            return response()->json(["status" => "ok", "data" => $this->warehouseTransferRequest], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function showRequestIn($warehouse, Request $request)
    {
        $wh = explode("-", $warehouse);
        $start = $request->start;
        $end = $request->end;

        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $this->warehouseTransferRequest = DB::table("precise.warehouse_trans_request_hd as a")
                ->whereIn("a.request_wh_from", $wh)
                ->whereBetween("a.request_date", [$start, $end])
                ->select(
                    'a.wh_trans_req_hd_id',
                    'a.trans_req_number',
                    'a.request_date',
                    'a.request_wh_from',
                    'b.warehouse_code AS warehouse_code_from',
                    'b.warehouse_name AS warehouse_name_from',
                    'a.request_wh_to',
                    'c.warehouse_code AS warehouse_code_to',
                    'c.warehouse_name AS warehouse_name_to',
                    'a.request_by',
                    'd.NAMA AS request_by_name',
                    'a.request_approve_by',
                    'e.NAMA AS request_approve_by_name',
                    'a.request_accepted_by',
                    'f.NAMA AS request_accepted_by_name',
                    'a.request_accepted_appr_by',
                    'g.NAMA AS request_accepted_appr_by_name',
                    'a.is_handed',
                    'a.warehouse_trans_number_in',
                    'a.warehouse_trans_number_out',
                    'a.request_description',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin("precise.warehouse as b", "a.request_wh_from", "=", "b.warehouse_id")
                ->leftJoin("precise.warehouse as c", "a.request_wh_to", "=", "c.warehouse_id")
                ->leftJoin("dbhrd.newdatakar as d", "a.request_by", "=", "d.NIP")
                ->leftJoin("dbhrd.newdatakar as e", "a.request_approve_by", "=", "e.NIP")
                ->leftJoin("dbhrd.newdatakar as f", "a.request_accepted_by", "=", "f.NIP")
                ->leftJoin("dbhrd.newdatakar as g", "a.request_accepted_appr_by", "=", "g.NIP")
                ->get();

            if (count($this->warehouseTransferRequest) == 0)
                return response()->json(["status" => "error", "message" => "not found"], 404);
            return response()->json(["status" => "ok", "data" => $this->warehouseTransferRequest], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function showRequestOutForIncomingRequest($warehouse, Request $request)
    {
        $wh = explode("-", $warehouse);
        $start = $request->start;
        $end = $request->end;

        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $this->warehouseTransferRequest = DB::table("precise.warehouse_trans_request_hd as a")
                ->where('a.is_handed', 0)
                ->whereIn("a.request_wh_to", $wh)
                ->whereNotNull("a.request_approve_by")
                ->whereNull("a.request_accepted_by")
                ->whereBetween("a.request_date", [$start, $end])
                ->select(
                    'a.wh_trans_req_hd_id',
                    'a.trans_req_number',
                    'a.request_date',
                    'a.request_wh_from',
                    'b.warehouse_code AS warehouse_code_from',
                    'b.warehouse_name AS warehouse_name_from',
                    'a.request_wh_to',
                    'c.warehouse_code AS warehouse_code_to',
                    'c.warehouse_name AS warehouse_name_to',
                    'a.request_by',
                    'd.NAMA AS request_by_name',
                    'a.request_approve_by',
                    'e.NAMA AS request_approve_by_name',
                    'a.request_accepted_by',
                    'f.NAMA AS request_accepted_by_name',
                    'a.request_accepted_appr_by',
                    'g.NAMA AS request_accepted_appr_by_name',
                    'a.is_handed',
                    'a.warehouse_trans_number_in',
                    'a.warehouse_trans_number_out',
                    'a.request_description',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin("precise.warehouse as b", "a.request_wh_from", "=", "b.warehouse_id")
                ->leftJoin("precise.warehouse as c", "a.request_wh_to", "=", "c.warehouse_id")
                ->leftJoin("dbhrd.newdatakar as d", "a.request_by", "=", "d.NIP")
                ->leftJoin("dbhrd.newdatakar as e", "a.request_approve_by", "=", "e.NIP")
                ->leftJoin("dbhrd.newdatakar as f", "a.request_accepted_by", "=", "f.NIP")
                ->leftJoin("dbhrd.newdatakar as g", "a.request_accepted_appr_by", "=", "g.NIP")
                ->get();

            if (count($this->warehouseTransferRequest) == 0)
                return response()->json(["status" => "error", "message" => "not found"], 404);
            return response()->json(["status" => "ok", "data" => $this->warehouseTransferRequest], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function showRequestOutForHanded($warehouse, Request $request)
    {
        $wh = explode("-", $warehouse);
        $start = $request->start;
        $end = $request->end;

        $validator = Validator::make($request->all(), [
            'start'     => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'       => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $this->warehouseTransferRequest = DB::table("precise.warehouse_trans_request_hd as a")
                ->whereIn("a.request_wh_to", $wh)
                ->whereNotNull("a.request_accepted_appr_by")
                ->where("a.is_handed", 0)
                ->whereBetween("a.request_date", [$start, $end])
                ->select(
                    'a.wh_trans_req_hd_id',
                    'a.trans_req_number',
                    'a.request_date',
                    'a.request_wh_from',
                    'b.warehouse_code AS warehouse_code_from',
                    'b.warehouse_name AS warehouse_name_from',
                    'a.request_wh_to',
                    'c.warehouse_code AS warehouse_code_to',
                    'c.warehouse_name AS warehouse_name_to',
                    'a.request_by',
                    'd.NAMA AS request_by_name',
                    'a.request_approve_by',
                    'e.NAMA AS request_approve_by_name',
                    'a.request_accepted_by',
                    'f.NAMA AS request_accepted_by_name',
                    'a.request_accepted_appr_by',
                    'g.NAMA AS request_accepted_appr_by_name',
                    'a.is_handed',
                    'a.warehouse_trans_number_in',
                    'a.warehouse_trans_number_out',
                    'a.request_description',
                    'a.created_on',
                    'a.created_by',
                    'a.updated_on',
                    'a.updated_by'
                )
                ->leftJoin("precise.warehouse as b", "a.request_wh_from", "=", "b.warehouse_id")
                ->leftJoin("precise.warehouse as c", "a.request_wh_to", "=", "c.warehouse_id")
                ->leftJoin("dbhrd.newdatakar as d", "a.request_by", "=", "d.NIP")
                ->leftJoin("dbhrd.newdatakar as e", "a.request_approve_by", "=", "e.NIP")
                ->leftJoin("dbhrd.newdatakar as f", "a.request_accepted_by", "=", "f.NIP")
                ->leftJoin("dbhrd.newdatakar as g", "a.request_accepted_appr_by", "=", "g.NIP")
                ->get();

            if (count($this->warehouseTransferRequest) == 0)
                return response()->json(["status" => "error", "message" => "not found"], 404);
            return response()->json(["status" => "ok", "data" => $this->warehouseTransferRequest], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function currentStock($product, $warehouse)
    {
        $data = DB::select("select precise.warehouse_get_current_stock(?,?) as current_stock", [$product, $warehouse]);
        return response()->json(["current_stock" => $data[0]->current_stock]);
    }

    public function create(Request $request)
    {
        $data = $request->json()->all();
        $validator = Validator::make(
            $data,
            [
                'request_date'              => 'required',
                'request_wh_from'           => 'required|exists:warehouse,warehouse_id',
                'request_wh_to'             => 'required|exists:warehouse,warehouse_id',
                'request_by'                => 'required|exists:users,user_id',
                'request_approve_by'        => 'nullable',
                'request_accepted_by'       => 'nullable',
                'request_accepted_appr_by'  => 'nullable',
                'request_accepted_appr_by'  => 'nullable',
                'desc'                      => 'nullable',
                'created_by'                => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();

            $trans = DB::select("select precise.get_transaction_number(:type,:date) as number", [":type" => 22, ":date" => $request->request_date]);

            if (empty($trans)) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "error insert data"], 500);
            }

            $id = DB::table("precise.warehouse_trans_request_hd")
                ->insertGetId([
                    'trans_req_number'          => $trans[0]->number,
                    'request_date'              => $data['request_date'],
                    'request_wh_from'           => $data['request_wh_from'],
                    'request_wh_to'             => $data['request_wh_to'],
                    'request_by'                => $data['request_by'],
                    'request_approve_by'        => $data['request_approve_by'],
                    'request_accepted_by'       => $data['request_accepted_by'],
                    'request_accepted_appr_by'  => $data['request_accepted_appr_by'],
                    'request_description'       => $data['desc'],
                    'created_by'                => $data['created_by'],
                ]);

            if (empty($id)) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "fatal error"], 500);
            }

            foreach ($data['detail'] as $detail) {
                $validator = Validator::make(
                    $detail,
                    [
                        'product_id'    => 'required|exists:product,product_id',
                        'request_qty'   => 'required|numeric',
                        'approved_qty'  => 'required|numeric',
                        'product_uom'   => 'required|exists:uom,uom_code',
                        'created_by'    => 'required'
                    ]
                );

                if ($validator->fails()) {
                    return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                }

                $values[] = [
                    'wh_trans_req_hd_id'    => $id,
                    'product_id'            => $detail['product_id'],
                    'request_qty'           => $detail['request_qty'],
                    'approved_qty'          => $detail['approved_qty'],
                    'product_uom'           => $detail['product_uom'],
                    'created_by'            => $detail['created_by']
                ];
            }

            $insertDetail = DB::table("precise.warehouse_trans_request_dt")
                ->insert($values);

            if ($insertDetail == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "fatal error"], 500);
            }

            $check = DB::statement("CALL precise.system_increment_transaction_counter(22, :request_date)", array(':request_date' => $request->request_date));
            if (!$check) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "error insert data"], 500);
            }

            DB::commit();
            return response()->json(["status" => "ok", "id" => $id, "message" => "success insert data"], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        $data = $request->json()->all();
        $validator = Validator::make(
            $data,
            [
                'wh_trans_req_hd_id'            => 'required|exists:warehouse_trans_request_hd,wh_trans_req_hd_id',
                'request_date'                  => 'required',
                'request_wh_from'               => 'required|exists:warehouse,warehouse_id',
                'request_wh_to'                 => 'required|exists:warehouse,warehouse_id',
                'request_by'                    => 'required|exists:users,user_id',
                'request_approve_by'            => 'nullable|exists:users,user_id',
                'request_approve_on'            => 'nullable',
                'request_accepted_by'           => 'nullable|exists:users,user_id',
                'request_accepted_on'           => 'nullable',
                'request_accepted_appr_by'      => 'nullable',
                'request_accepted_appr_on'      => 'nullable',
                'is_handed'                     => 'required',
                'warehouse_trans_number_out'    => 'nullable',
                'warehouse_trans_number_in'     => 'nullable',
                'desc'                          => 'nullable',
                'reason'                        => 'required',
                'updated_on'                    => 'required',
                'updated_by'                    => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "update", $data);
            $trans_out = '';
            $trans_in = '';
            if ($data['is_handed'] == 1 && $data['warehouse_trans_number_out'] == null) {
                $trans_out = DB::select("select precise.get_transaction_number_warehouse_transfer(:to,:type,:date) as number", [":to" => $data["request_wh_to"], ":type" => 19, ":date" => $data['updated_on']])[0];
                DB::statement("CALL precise.system_increment_transaction_counter_warehouse_transfer(:to,:type,:date)", array(':to' => $data["request_wh_to"], ":type" => 19, ":date" => $data['updated_on']));
                $trans_in = DB::select("select precise.get_transaction_number_warehouse_transfer(:from,:type,:date) as number", [":from" => $data["request_wh_from"], ":type" => 4, ":date" => $data['updated_on']])[0];
                DB::statement("CALL precise.system_increment_transaction_counter_warehouse_transfer(:from,:type,:date)", array(':from' => $data["request_wh_from"], ":type" => 4, ":date" => $data['updated_on']));
                DB::table("precise.warehouse_trans_request_hd")
                    ->where("wh_trans_req_hd_id", $data['wh_trans_req_hd_id'])
                    ->update([
                        "request_date"                  =>  $data["request_date"],
                        "request_wh_from"               =>  $data["request_wh_from"],
                        "request_wh_to"                 =>  $data["request_wh_to"],
                        "request_by"                    =>  $data["request_by"],
                        "request_approve_by"            =>  $data["request_approve_by"],
                        "request_approve_on"            =>  $data["request_approve_on"],
                        "request_accepted_by"           =>  $data["request_accepted_by"],
                        "request_accepted_on"           =>  $data["request_accepted_on"],
                        "request_accepted_appr_by"      =>  $data["request_accepted_appr_by"],
                        "request_accepted_appr_on"      =>  $data["request_accepted_appr_on"],
                        'is_handed'                     =>  $data["is_handed"],
                        'warehouse_trans_number_in'     =>  $trans_in->number,
                        'warehouse_trans_number_out'    =>  $trans_out->number,
                        'request_description'           =>  $data['desc'],
                        'updated_by'                    =>  $data['updated_by'],
                    ]);
            }

            DB::table("precise.warehouse_trans_request_hd")
                ->where("wh_trans_req_hd_id", $data['wh_trans_req_hd_id'])
                ->update([
                    "request_date"                  =>  $data["request_date"],
                    "request_wh_from"               =>  $data["request_wh_from"],
                    "request_wh_to"                 =>  $data["request_wh_to"],
                    "request_by"                    =>  $data["request_by"],
                    "request_approve_by"            =>  $data["request_approve_by"],
                    "request_approve_on"            =>  $data["request_approve_on"],
                    "request_accepted_by"           =>  $data["request_accepted_by"],
                    "request_accepted_on"           =>  $data["request_accepted_on"],
                    "request_accepted_appr_by"      =>  $data["request_accepted_appr_by"],
                    "request_accepted_appr_on"      =>  $data["request_accepted_appr_on"],
                    'is_handed'                     =>  $data["is_handed"],
                    'request_description'           =>  $data['desc'],
                    'updated_by'                    =>  $data['updated_by'],
                ]);

            if ($data["inserted"] != null) {
                foreach ($data["inserted"] as $insert) {
                    $validator = Validator::make(
                        $insert,
                        [
                            'wh_trans_req_hd_id'    => 'required|exists:warehouse_trans_request_hd,wh_trans_req_hd_id',
                            'product_id'            => 'required|exists:product,product_id',
                            'request_qty'           => 'required|numeric',
                            'approved_qty'          => 'required|numeric',
                            'product_uom'           => 'required|exists:uom,uom_code',
                            'created_by'            => 'required'
                        ]
                    );

                    if ($validator->fails()) {
                        return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                    }

                    $values[] = [
                        'wh_trans_req_hd_id'    => $insert['wh_trans_req_hd_id'],
                        'product_id'            => $insert['product_id'],
                        'request_qty'           => $insert['request_qty'],
                        'approved_qty'          => $insert['approved_qty'],
                        'product_uom'           => $insert['product_uom'],
                        'created_by'            => $insert['created_by']
                    ];
                }

                $check = DB::table("precise.warehouse_trans_request_dt")
                    ->insert($values);

                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "fatal error"], 500);
                }
            }

            if ($data["updated"] != null) {
                foreach ($data["updated"] as $update) {
                    $check = DB::table("precise.warehouse_trans_request_dt")
                        ->where("wh_trans_req_dt_id", $update['wh_trans_req_dt_id'])
                        ->update([
                            'wh_trans_req_hd_id'    => $update['wh_trans_req_hd_id'],
                            'product_id'            => $update['product_id'],
                            'request_qty'           => $update['request_qty'],
                            'approved_qty'          => $update['approved_qty'],
                            'product_uom'           => $update['product_uom'],
                            'updated_by'            => $update['updated_by']
                        ]);

                    if ($check == 0) {
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => "fatal error"], 500);
                    }
                }
            }

            if ($data["deleted"] != null) {
                foreach ($data['deleted'] as $delete) {
                    $del[] = $delete["wh_trans_req_dt_id"];
                }

                $check = DB::table("precise.warehouse_trans_request_dt")
                    ->whereIn("wh_trans_req_dt_id", $del)
                    ->delete();

                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "fatal error"], 500);
                }
            }

            DB::commit();
            return response()->json(["status" => "ok", "message" => "success update data"], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'wh_trans_req_hd_id'        => 'required|exists:warehouse_trans_request_hd,wh_trans_req_hd_id',
                'reason'                    => 'required',
                'deleted_by'                => 'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $check = DB::table("precise.warehouse_trans_request_dt")
                ->where("wh_trans_req_hd_id", $request->wh_trans_req_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "fatal error"], 500);
            }

            $check = DB::table("precise.warehouse_trans_request_hd")
                ->where("wh_trans_req_hd_id", $request->wh_trans_req_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "fatal error"], 500);
            }

            DB::commit();
            return response()->json(["status" => "ok", "message" => "success delete data"], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function check(Request $request)
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } else {
            if ($type == "number") {
                $this->warehouseTransferRequest = DB::table('precise.warehouse_trans_request_hd')
                    ->where('trans_req_number', $value)
                    ->count();
            }
            if ($this->warehouseTransferRequest == 0)
                return response()->json(['status' => 'ok', 'message' => $this->warehouseTransferRequest], 404);
            return response()->json(['status' => 'ok', 'message' => $this->warehouseTransferRequest], 200);
        }
    }
}
