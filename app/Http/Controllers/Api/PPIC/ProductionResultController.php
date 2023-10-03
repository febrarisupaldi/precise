<?php

namespace App\Http\Controllers\Api\PPIC;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\JsonResponse;

class ProductionResultController extends Controller
{
    private $productionResult;


    public function index($workcenter, Request $request): JsonResponse
    {
        $start = $request->get('start', date("Y-m-d"));
        $end = $request->get('end', date("Y-m-d"));
        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            $workcenter = explode("-", $workcenter);
            $this->productionResult = DB::table('precise.production_result_hd as pr')
                ->whereIn('wo.workcenter_id', $workcenter)
                ->whereBetween('pr.result_date', [$start, $end])
                ->select(
                    'pr.result_hd_id',
                    'pr.result_date',
                    'pr.result_shift',
                    'w.workcenter_code',
                    'w.workcenter_name',
                    'pr.work_order_hd_id',
                    'pr.PrdNumber',
                    'pr.ResultSeq',
                    'prd.InvtNmbr',
                    'wht.trans_description',
                    'pr.created_on',
                    'pr.created_by',
                    'pr.updated_on',
                    'pr.updated_by'
                )
                ->leftJoin('precise.production_result_dt as prd', 'pr.result_hd_id', '=', 'prd.result_hd_id')
                ->leftJoin('precise.work_order as wo', 'pr.work_order_hd_id', '=', 'wo.work_order_hd_id')
                ->leftJoin('precise.workcenter as w', 'wo.workcenter_id', '=', 'w.workcenter_id')
                ->leftJoin('precise.warehouse_trans_hd as wht', 'prd.trans_hd_id', '=', 'wht.trans_hd_id')
                ->get();
            if (count($this->productionResult) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->productionResult, code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function show($id): JsonResponse
    {
        $master = DB::table("precise.production_result_hd as pr")
            ->where("pr.result_hd_id", $id)
            ->select(
                'pr.result_hd_id',
                'pr.result_date',
                'pr.result_shift',
                'pr.work_order_hd_id',
                'pr.PrdNumber',
                'wo.workcenter_id',
                'w.workcenter_code',
                'w.workcenter_name',
                'pr.ResultSeq',
                'wh.warehouse_id',
                'wh.warehouse_code',
                'wh.warehouse_name',
                'wht.trans_description',
                'pr.created_on',
                'pr.created_by',
                'pr.updated_on',
                'pr.updated_by'
            )
            ->leftJoin('precise.production_result_dt as prd', 'pr.result_hd_id', '=', 'prd.result_hd_id')
            ->leftJoin('precise.warehouse as wh', 'prd.result_warehouse', '=', 'wh.warehouse_id')
            ->leftJoin('precise.work_order as wo', 'pr.work_order_hd_id', '=', 'wo.work_order_hd_id')
            ->leftJoin('precise.workcenter as w', 'wo.workcenter_id', '=', 'w.workcenter_id')
            ->leftJoin('precise.warehouse_trans_hd as wht', 'prd.trans_hd_id', '=', 'wht.trans_hd_id')
            ->first();
        if (empty($master))
            return response()->json("not found", 404);

        $detail = DB::table('precise.production_result_dt as dt')
            ->where('dt.result_hd_id', $master->result_hd_id)
            ->select(
                'dt.result_dt_id',
                'dt.result_hd_id',
                'dt.PrdNumber',
                'dt.ResultSeq',
                'dt.ProductCode',
                'p.product_code',
                'p.product_name',
                'dt.ProductCode AS old_product_code',
                'dt.product_id',
                'dt.result_qty',
                'dt.result_warehouse',
                'dt.InvtNmbr',
                'dt.InvtType',
                'dt.trans_hd_id',
                'p.uom_code',
                'dt.bstb_id',
                'b.bstb_num as bstb_number',
                'dt.created_on',
                'dt.created_by',
                'dt.updated_on',
                'dt.updated_by'
            )
            ->leftJoin('precise.product as p', 'dt.product_id', '=', 'p.product_id')
            ->leftJoin('precise.bstb as b', 'dt.bstb_id', '=', 'b.bstb_id')
            ->get();
        if (count($detail) == 0)
            return response()->json(["status" => "error", "data" => "not found"], 404);

        $downtime = DB::table('precise.production_downtime as dwt')
            ->where('dwt.result_hd_id', $master->result_hd_id)
            ->select(
                'dwt.production_downtime_id',
                'dwt.result_hd_id',
                'dwt.downtime_id',
                'd.downtime_code',
                'd.downtime_name',
                'dwt.start_time',
                'dwt.end_time',
                'dwt.std_duration',
                'dwt.downtime_note',
                'dwt.approval_status',
                'dwt.approval_note',
                'dwt.created_on',
                'dwt.created_by',
                'dwt.updated_on',
                'dwt.updated_by'
            )
            ->leftJoin('precise.downtime as d', 'dwt.downtime_id', '=', 'd.downtime_id')
            ->get();
        if (count($downtime) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        $reject = DB::table('precise.production_reject as rjt')
            ->where('rjt.result_hd_id', $master->result_hd_id)
            ->select(
                'rjt.production_reject_id',
                'rjt.result_hd_id',
                'rjt.reject_id',
                'r.reject_code',
                'r.reject_name',
                'rjt.reject_qty',
                'rjt.created_on',
                'rjt.created_by',
                'rjt.updated_on',
                'rjt.updated_by'
            )
            ->leftJoin('precise.reject as r', 'rjt.reject_id', '=', 'r.reject_id')
            ->get();
        if (count($reject) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        // $this->productionResult =
        //     array(
        //         "result_hd_id"        => $master->result_hd_id,
        //         "result_date"         => $master->result_date,
        //         "result_shift"        => $master->result_shift,
        //         "work_order_hd_id"    => $master->work_order_hd_id,
        //         "warehouse_id"        => $master->warehouse_id,
        //         "warehouse_code"      => $master->warehouse_code,
        //         "warehouse_name"      => $master->warehouse_name,
        //         "PrdNumber"           => $master->PrdNumber,
        //         "workcenter_id"       => $master->workcenter_id,
        //         "workcenter_code"     => $master->workcenter_code,
        //         "workcenter_name"     => $master->workcenter_name,
        //         "ResultSeq"           => $master->ResultSeq,
        //         "trans_description"   => $master->trans_description,
        //         "created_on"          => $master->created_on,
        //         "created_by"          => $master->created_by,
        //         "updated_on"          => $master->updated_on,
        //         "updated_by"          => $master->updated_by,
        //         "detail"              => $detail,
        //         "downtime"            => $downtime,
        //         "reject"              => $reject
        //     );

        $this->productionResult = array_merge_recursive(
            (array)$master,
            array(
                "detail" => $detail,
                "downtime" => $downtime,
                "reject" => $reject
            )
        );

        return response()->json($this->productionResult, 200);
    }

    public function showProductionClone(): JsonResponse
    {
        $this->productionResult = DB::table('precise.production_result_clone as a')
            ->select(
                'a.result_hd_id',
                'a.result_dt_id',
                'prh.PrdNumber',
                'prh.result_date as before_result_date',
                'a.result_date as after_result_date',
                'prh.result_shift as before_result_shift',
                'a.result_shift as after_result_shift',
                'wht.trans_description as before_trans_description',
                'a.trans_description as after_trans_description',
                'p.product_code',
                'p.product_name',
                'prd.result_qty as before_result_qty',
                'a.result_qty as after_result_qty',
                DB::raw("
                    case a.is_active 
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'is_active'
                "),
                'prd.InvtNmbr',
                'a.updated_on',
                'a.updated_by'
            )
            ->leftJoin('precise.production_result_hd as prh', 'a.result_hd_id', '=', 'prh.result_hd_id')
            ->leftJoin('precise.production_result_dt as prd', 'a.result_dt_id', '=', 'prd.result_dt_id')
            ->leftJoin('precise.warehouse_trans_hd as wht', 'prd.trans_hd_id', '=', 'wht.trans_hd_id')
            ->leftJoin('precise.product as p', 'prd.product_id', '=', 'p.product_id')
            ->get();

        if (count($this->productionResult) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->productionResult, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'result_date'           => 'required|date_format:Y-m-d',
            'result_shift'          => 'required',
            'work_order_hd_id'      => 'required|exists:work_order,work_order_hd_id',
            'PrdNumber'             => 'required',
            'created_by'            => 'required',
            'workcenter_id'         => 'required|exists:workcenter,workcenter_id'
        ]);


        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $transactionTypeID = 0;
        if ($data['workcenter_id'] == 1) {
            $transactionTypeID = 13;
        } elseif ($data['workcenter_id'] == 2) {
            $transactionTypeID = 14;
        } elseif ($data['workcenter_id'] == 3) {
            $transactionTypeID = 15;
        } elseif ($data['workcenter_id'] == 4) {
            $transactionTypeID = 16;
        } elseif ($data['workcenter_id'] == 6) {
            $transactionTypeID = 8;
        } elseif ($data['workcenter_id'] == 7) {
            $transactionTypeID = 9;
        } elseif ($data['workcenter_id'] == 8) {
            $transactionTypeID = 10;
        } elseif ($data['workcenter_id'] == 9) {
            $transactionTypeID = 11;
        } elseif ($data['workcenter_id'] == 10) {
            $transactionTypeID = 12;
        }
        DB::beginTransaction();
        try {
            $transNum = DB::select('SELECT precise.get_transaction_number(:transTypeID, :rDate) AS transNumber', ['transTypeID' => $transactionTypeID, 'rDate' => $data['result_date']]);

            $number = $transNum[0]->transNumber;

            $id = DB::table('precise.warehouse_trans_hd')
                ->insertGetId([
                    'trans_number'       => $number,
                    'trans_type'         => $data['trans_type_id'],
                    'trans_date'         => $data['result_date'],
                    'trans_from'         => $data['warehouse_id'],
                    'work_order_id'      => $data['work_order_hd_id'],
                    'work_order_number'  => $data['PrdNumber'],
                    'trans_description'  => $data['trans_description'],
                    'created_by'         => $data['created_by']
                ]);

            if ($id == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            $validator = Validator::make($data, [
                'detail.*.product_id'    => 'required|exists:product,product_id',
                'detail.*.result_qty'    => 'required|numeric',
                'detail.*.uom_code'      => 'required|exists:uom,uom_code',
                'detail.*.created_by'    => 'required'
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
            }

            foreach ($data['detail'] as $transdt) {
                $whDt[] = [
                    'trans_hd_id'           => $id,
                    'trans_number'          => $number,
                    'trans_type'            => $data['trans_type_id'],
                    'trans_seq'             => 1,
                    'product_id'            => $transdt['product_id'],
                    'trans_in_qty'          => $transdt['result_qty'],
                    'trans_out_qty'         => 0.0000,
                    'trans_uom'             => $transdt['uom_code'],
                    'trans_in_qty_t'        => $transdt['result_qty'],
                    'trans_out_qty_t'       => 0.0000,
                    'trans_uom_t'           => $transdt['uom_code'],
                    'trans_price'           => 0.0000,
                    'trans_qty_price'       => 0.0000,
                    'trans_ppn_percent'     => 0.0000,
                    'trans_ppn_amount'      => 0.0000,
                    'created_by'            => $data['created_by']
                ];
            }

            $check = DB::table('precise.warehouse_trans_dt')
                ->insert($whDt);

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }
            $resSeq = 0;
            $resultSeq = DB::table('precise.production_result_hd')
                ->where('work_order_hd_id', $data['work_order_hd_id'])
                ->select(
                    'ResultSeq'
                )
                ->orderBy('ResultSeq', 'DESC')
                ->first();

            if ($resultSeq != null) {
                $resSeq = $resultSeq->ResultSeq + 1;
            } else {
                $resSeq = 1;
            }


            $id = DB::table('precise.production_result_hd')
                ->insertGetId([
                    'result_date'       => $data['result_date'],
                    'result_shift'      => $data['result_shift'],
                    'work_order_hd_id'  => $data['work_order_hd_id'],
                    'PrdNumber'         => $data['PrdNumber'],
                    'ResultSeq'         => $resSeq,
                    'created_by'        => $data['created_by']
                ]);

            if ($id == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            $validator = Validator::make($data, [
                'detail.*.product_id'       => 'required|exists:product,product_id',
                'detail.*.product_code'     => 'required|exists:product,product_code',
                'detail.*.PrdNumber'        => 'required',
                'detail.*.result_qty'       => 'required|numeric',
                'detail.*.result_warehouse' => 'required|numeric',
                'detail.*.bstb_id'          => 'required|exists:bstb,bstb_id'
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
            }

            foreach ($data['detail'] as $d) {
                $dt[] = [
                    'result_hd_id'          => $id,
                    'PrdNumber'             => $d['PrdNumber'],
                    'ResultSeq'             => $resSeq,
                    'ProductCode'           => $d['product_code'],
                    'product_id'            => $d['product_id'],
                    'result_qty'            => $d['result_qty'],
                    'result_warehouse'      => $d['result_warehouse'],
                    'InvtNmbr'              => $number,
                    'InvtType'              => $data['trans_type_code'],
                    'trans_hd_id'           => $id,
                    'bstb_id'               => $d['bstb_id'],
                    'created_by'            => $data['created_by']
                ];
            }

            $check = DB::table('precise.production_result_dt')
                ->insert($dt);

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            if ($data['downtime'] != null) {
                $validator = Validator::make($data, [
                    'downtime.*.downtime_id'        => 'required|exists:downtime,downtime_id',
                    'downtime.*.start_time'         => 'required',
                    'downtime.*.end_time'           => 'required',
                    'downtime.*.std_duration'       => 'required',
                    'downtime.*.downtime_note'      => 'required',
                    'downtime.*.approval_status'    => 'required',
                    'downtime.*.approval_note'      => 'required',
                    'downtime.*.created_by'         => 'required'
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                }

                foreach ($data['downtime'] as $downtime) {
                    $dwt[] = [
                        'result_hd_id'              => $id,
                        'downtime_id'               => $downtime['downtime_id'],
                        'start_time'                => $downtime['start_time'],
                        'end_time'                  => $downtime['end_time'],
                        'std_duration'              => $downtime['std_duration'],
                        'downtime_note'             => $downtime['downtime_note'],
                        'approval_status'           => $downtime['approval_status'],
                        'approval_note'             => $downtime['approval_note'],
                        'created_by'                => $data['created_by']
                    ];
                }
                $check = DB::table('precise.production_downtime')
                    ->insert($dwt);

                if ($check == 0) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: "server error", code: 500);
                }
            }

            if ($data['reject'] != null) {

                $validator = Validator::make($data, [
                    'reject.*.reject_id'    => 'required|exists:reject,reject_id',
                    'reject.*.reject_qty'   => 'required|numeric'
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                }

                foreach ($data['reject'] as $reject) {
                    $rjt[] = [
                        'result_hd_id'              => $id,
                        'reject_id'                 => $reject['reject_id'],
                        'reject_qty'                => $reject['reject_qty'],
                        'created_by'                => $data['created_by']
                    ];
                }
                $check = DB::table('precise.production_reject')
                    ->insert($rjt);

                if ($check == 0) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: "server error", code: 500);
                }
            }


            if ($data['bstb_number'] != null || $data['bstb_number'] != '') {
                $check = DB::table('precise.bstb')
                    ->where('bstb_num', $data['bstb_number'])
                    ->update([
                        'bstb_status'    => 'X'
                    ]);

                if ($check == 0) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: "server error", code: 500);
                }
            }

            $trans = DB::table('precise.production_result_hd')
                ->where('result_hd_id', $id)
                ->value('PrdNumber');

            DB::raw(DB::select('call precise.`system_increment_transaction_counter`(:transTypeID, :rDate)', ['transTypeID' => $transactionTypeID, 'rDate' => $data['result_date']]));
            DB::commit();
            return ResponseController::json(status: "ok", message: "success input data", id: $trans, code: 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'result_hd_id'          => 'required|exists:production_result_hd,result_hd_id',
            'result_date'           => 'required|date_format:Y-m-d',
            'result_shift'          => 'required',
            'work_order_hd_id'      => 'required|exists:work_order,work_order_hd_id',
            'PrdNumber'             => 'required',
            'ResultSeq'             => 'required',
            'updated_by'            => 'required',
            'reason'                => 'required',
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update", $data);

            $mode = "";
            $checkPB = DB::table('precise.material_usage')
                ->where('work_order_hd_id', $data['work_order_hd_id'])
                ->select(
                    'work_order_hd_id'
                )
                ->first();

            if ($checkPB != null) {
                $mode = "clone";
                $validator = Validator::make($data, [
                    'detail.*.result_dt_id' => 'required|exists:production_result_dt,result_dt_id',
                    'detail.*.result_qty'   => 'required|date_format:Y-m-d'
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                }
                foreach ($data['detail'] as $detailresult) {
                    $this->productionResult = DB::table("precise.production_result_clone")
                        ->insert([
                            'result_hd_id'          => $data['result_hd_id'],
                            'result_dt_id'          => $detailresult['result_dt_id'],
                            'result_date'           => $data['result_date'],
                            'result_shift'          => $data['result_shift'],
                            'result_qty'            => $detailresult['result_qty'],
                            'trans_description'     => $data['trans_description'],
                            'is_active'             => 1,
                            'updated_by'            => $data['updated_by']
                        ]);
                }
            } else {
                $mode = "update";

                $validator = Validator::make($data, [
                    'detail.*.result_hd_id' => 'required|exists:production_result_hd,result_hd_id',
                    'detail.*.product_id'   => 'required|exists:product,product_id',
                    'detail.*.result_qty'   => 'required|numeric',
                    'detail.*.uom_code'     => 'required|exists:uom,uom_code'
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                }

                foreach ($data['detail'] as $transdt) {
                    DB::table('precise.warehouse_trans_hd')
                        ->where('trans_hd_id', $transdt['trans_hd_id'])
                        ->update([
                            'trans_date'         => $data['result_date'],
                            'trans_from'         => $data['warehouse_id'],
                            'work_order_id'      => $data['work_order_hd_id'],
                            'work_order_number'  => $data['PrdNumber'],
                            'trans_description'  => $data['trans_description'],
                            'updated_by'         => $data['updated_by']
                        ]);

                    DB::table('precise.warehouse_trans_dt')
                        ->where('trans_hd_id', $transdt['trans_hd_id'])
                        ->update([
                            'product_id'            => $transdt['product_id'],
                            'trans_in_qty'          => $transdt['result_qty'],
                            'trans_uom'             => $transdt['uom_code'],
                            'trans_in_qty_t'        => $transdt['result_qty'],
                            'trans_uom_t'           => $transdt['uom_code'],
                            'updated_by'            => $data['updated_by']
                        ]);
                }

                $resSeq = 0;
                if ($data['work_order_hd_id'] != $data['old_work_order_hd_id']) {
                    $resultSeq = DB::table('precise.production_result_hd')
                        ->where('work_order_hd_id', $data['work_order_hd_id'])
                        ->select(
                            'ResultSeq'
                        )
                        ->orderBy('ResultSeq', 'DESC')
                        ->first();

                    if ($resultSeq != null) {
                        $resSeq = $resultSeq->ResultSeq + 1;
                    } else {
                        $resSeq = 1;
                    }
                } else {
                    $resSeq = $data['ResultSeq'];
                }

                DB::table('precise.production_result_hd')
                    ->where('result_hd_id', $data['result_hd_id'])
                    ->update([
                        'result_date'         => $data['result_date'],
                        'result_shift'        => $data['result_shift'],
                        'work_order_hd_id'    => $data['work_order_hd_id'],
                        'PrdNumber'           => $data['PrdNumber'],
                        'ResultSeq'           => $resSeq,
                        'updated_by'          => $data['updated_by']
                    ]);

                $validator = Validator::make($data, [
                    'detail.*.result_dt_id'     => 'required|exists:production_result_dt,result_dt_id',
                    'detail.*.result_hd_id'     => 'required|exists:production_result_hd,result_hd_id',
                    'detail.*.product_id'       => 'required|exists:product,product_id',
                    'detail.*.product_code'     => 'required|exists:product,product_code',
                    'detail.*.PrdNumber'        => 'required',
                    'detail.*.InvtNmbr'         => 'required',
                    'detail.*.InvtType'         => 'required',
                    'detail.*.result_qty'       => 'required|numeric',
                    'detail.*.result_warehouse' => 'required|numeric',
                    'detail.*.bstb'             => 'required',
                    'detail.*.trans_hd_id'      => 'required|exists:warehouse_trans_hd,trans_hd_id'
                ]);
                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                }

                foreach ($data['detail'] as $d) {
                    DB::table('precise.production_result_dt')
                        ->where('result_dt_id', $d['result_dt_id'])
                        ->update([
                            'result_hd_id'          => $d['result_hd_id'],
                            'PrdNumber'             => $d['PrdNumber'],
                            'ResultSeq'             => $resSeq,
                            'ProductCode'           => $d['product_code'],
                            'product_id'            => $d['product_id'],
                            'result_qty'            => $d['result_qty'],
                            'result_warehouse'      => $d['result_warehouse'],
                            'InvtNmbr'              => $d['InvtNmbr'],
                            'InvtType'              => $d['InvtType'],
                            'trans_hd_id'           => $d['trans_hd_id'],
                            'bstb_id'               => $d['bstb_id'],
                            'updated_by'            => $data['updated_by']
                        ]);
                }

                DB::table('precise.production_downtime')
                    ->where('result_hd_id', $data['result_hd_id'])
                    ->delete();

                DB::table('precise.production_reject')
                    ->where('result_hd_id', $data['result_hd_id'])
                    ->delete();

                if ($data['downtime'] != null) {
                    $validator = Validator::make($data, [
                        'downtime.*.result_hd_id'       => 'required|exists:production_result_hd,result_hd_id',
                        'downtime.*.downtime_id'        => 'required|exists:downtime,downtime_id',
                        'downtime.*.start_time'         => 'required',
                        'downtime.*.end_time'           => 'required',
                        'downtime.*.std_duration'       => 'required',
                        'downtime.*.downtime_note'      => 'required',
                        'downtime.*.approval_status'    => 'required',
                        'downtime.*.approval_note'      => 'required',
                        'downtime.*.created_by'         => 'required'
                    ]);

                    if ($validator->fails()) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                    }

                    foreach ($data['downtime'] as $downtime) {
                        $dwt[] = [
                            'result_hd_id'              => $data['result_hd_id'],
                            'downtime_id'               => $downtime['downtime_id'],
                            'start_time'                => $downtime['start_time'],
                            'end_time'                  => $downtime['end_time'],
                            'std_duration'              => $downtime['std_duration'],
                            'downtime_note'             => $downtime['downtime_note'],
                            'approval_status'           => $downtime['approval_status'],
                            'approval_note'             => $downtime['approval_note'],
                            'created_by'                => $data['created_by']
                        ];
                    }
                    $check = DB::table('precise.production_downtime')
                        ->insert($dwt);

                    if ($check == 0) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: "server error", code: 500);
                    }
                }

                if ($data['reject'] != null) {
                    $validator = Validator::make($data, [
                        'reject.*.reject_id'    => 'required|exists:reject,reject_id',
                        'reject.*.reject_qty'   => 'required|numeric'
                    ]);

                    if ($validator->fails()) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                    }

                    foreach ($data['reject'] as $reject) {
                        $rjt[] = [
                            'result_hd_id'              => $data['result_hd_id'],
                            'reject_id'                 => $reject['reject_id'],
                            'reject_qty'                => $reject['reject_qty'],
                            'created_by'                => $data['created_by']
                        ];
                    }
                    $check = DB::table('precise.production_reject')
                        ->insert($rjt);

                    if ($check == 0) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: "server error", code: 500);
                    }
                }
            }


            DB::commit();
            return ResponseController::json(status: "ok", message: $mode, code: 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'result_hd_id'      => 'required|exists:production_result_dt,result_hd_id',
            'reason'            => 'required',
            'deleted_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $transid = DB::table('precise.production_result_dt')
                ->where('result_hd_id', $request->result_hd_id)
                ->select(
                    'trans_hd_id'
                )
                ->first();

            $check = DB::table('precise.warehouse_trans_dt')
                ->where('trans_hd_id', $transid->trans_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            $check = DB::table('precise.warehouse_trans_hd')
                ->where('trans_hd_id', $transid->trans_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            $check = DB::table('precise.production_downtime')
                ->where('result_hd_id', $request->result_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            $check = DB::table('precise.production_reject')
                ->where('result_hd_id', $request->result_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            $check = DB::table('precise.production_result_dt')
                ->where('result_hd_id', $request->result_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            $check = DB::table('precise.production_result_hd')
                ->where('result_hd_id', $request->result_hd_id)
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success delete data", code: 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "material_usage") {
                $this->productionResult = DB::table('precise.material_usage')->where([
                    'work_order_hd_id' => $value
                ])->count();
            }
            if ($this->productionResult == 0)
                return ResponseController::json(status: "error", message: $this->productionResult, code: 404);
            return ResponseController::json(status: "ok", message: $this->productionResult, code: 200);
        }
    }

    public function updateApproval(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'result_hd_id'          => 'required|exists:production_result_hd,result_hd_id',
            'result_date'           => 'required|date:Y-m-d',
            'result_shift'          => 'required',
            'work_order_hd_id'      => 'required|exists:work_order,work_order_hd_id',
            'PrdNumber'             => 'required',
            'ResultSeq'             => 'required',
            'updated_by'            => 'required',
            'reason'                => 'required',
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update", $data);
            $validator = Validator::make($data, [
                'detail.*.trans_hd_id'  => 'required|exists:warehouse_trans_hd,trans_hd_id',
                'detail.*.product_id'   => 'required|exists:product,product_id',
                'detail.*.result_qty'   => 'required|numeric',
                'detail.*.uom_code'     => 'required|exists:uom,uom_code'
            ]);
            if ($validator->fails()) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
            }
            foreach ($data['detail'] as $transdt) {
                DB::table('precise.warehouse_trans_hd')
                    ->where('trans_hd_id', $transdt['trans_hd_id'])
                    ->update([
                        'trans_date'         => $data['result_date'],
                        'trans_from'         => $data['warehouse_id'],
                        'work_order_id'      => $data['work_order_hd_id'],
                        'work_order_number'  => $data['PrdNumber'],
                        'trans_description'  => $data['trans_description'],
                        'updated_by'         => $data['updated_by']
                    ]);

                DB::table('precise.warehouse_trans_dt')
                    ->where('trans_hd_id', $transdt['trans_hd_id'])
                    ->update([
                        'product_id'            => $transdt['product_id'],
                        'trans_in_qty'          => $transdt['result_qty'],
                        'trans_uom'             => $transdt['uom_code'],
                        'trans_in_qty_t'        => $transdt['result_qty'],
                        'trans_uom_t'           => $transdt['uom_code'],
                        'updated_by'            => $data['updated_by']
                    ]);
            }

            $resSeq = 0;
            if ($data['work_order_hd_id'] != $data['old_work_order_hd_id']) {
                $resultSeq = DB::table('precise.production_result_hd')
                    ->where('work_order_hd_id', $data['work_order_hd_id'])
                    ->select(
                        'ResultSeq'
                    )
                    ->orderBy('ResultSeq', 'DESC')
                    ->first();

                if ($resultSeq != null) {
                    $resSeq = $resultSeq->ResultSeq + 1;
                } else {
                    $resSeq = 1;
                }
            } else {
                $resSeq = $data['ResultSeq'];
            }

            DB::table('precise.production_result_clone')
                ->where('result_hd_id', $data['result_hd_id'])
                ->where('is_active', 1)
                ->update([
                    'is_active'    => 0
                ]);

            DB::table('precise.production_result_hd')
                ->where('result_hd_id', $data['result_hd_id'])
                ->update([
                    'result_date'         => $data['result_date'],
                    'result_shift'        => $data['result_shift'],
                    'work_order_hd_id'    => $data['work_order_hd_id'],
                    'PrdNumber'           => $data['PrdNumber'],
                    'ResultSeq'           => $resSeq,
                    'updated_by'          => $data['updated_by']
                ]);

            $validator = Validator::make($data, [
                'detail.*.result_dt_id'     => 'required|exists:production_result_dt,result_dt_id',
                'detail.*.result_hd_id'     => 'required|exists:production_result_hd,result_hd_id',
                'detail.*.product_id'       => 'required|exists:product,product_id',
                'detail.*.product_code'     => 'required|exists:product,product_code',
                'detail.*.PrdNumber'        => 'required',
                'detail.*.InvtNmbr'         => 'required',
                'detail.*.InvtType'         => 'required',
                'detail.*.result_qty'       => 'required|numeric',
                'detail.*.result_warehouse' => 'required|numeric',
                'detail.*.trans_hd_id'      => 'required|exists:warehouse_trans_hd,trans_hd_id'
            ]);
            if ($validator->fails()) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
            }
            foreach ($data['detail'] as $d) {
                DB::table('precise.production_result_dt')
                    ->where('result_dt_id', $d['result_dt_id'])
                    ->update([
                        'result_hd_id'          => $d['result_hd_id'],
                        'PrdNumber'             => $d['PrdNumber'],
                        'ResultSeq'             => $resSeq,
                        'ProductCode'           => $d['product_code'],
                        'product_id'            => $d['product_id'],
                        'result_qty'            => $d['result_qty'],
                        'result_warehouse'      => $d['result_warehouse'],
                        'InvtNmbr'              => $d['InvtNmbr'],
                        'InvtType'              => $d['InvtType'],
                        'trans_hd_id'           => $d['trans_hd_id'],
                        'updated_by'            => $data['updated_by']
                    ]);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success update data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
