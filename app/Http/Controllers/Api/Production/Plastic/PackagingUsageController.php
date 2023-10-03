<?php

namespace App\Http\Controllers\Api\Production\Plastic;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PackagingUsageController extends Controller
{
    private $packaging = "";
    public function showCurrentProductStatusByNumber($number)
    {
        try {
            $query = DB::table('precise.packaging_numbering as pn')
                ->where('pn.packaging_number', $number)
                ->select(
                    'pn.packaging_numbering_id',
                    'pn.packaging_id',
                    'pn.packaging_number',
                    'pn.status_code',
                    'pn.created_on',
                    'pn.created_by',
                    'pn.updated_on',
                    'pn.updated_by',
                    DB::raw("max(puh.usage_counter) as max_counter")
                )
                ->leftJoin('precise.packaging_usage_hd as puh', 'pn.packaging_numbering_id', '=', 'puh.packaging_numbering_id')
                ->groupBy('pn.packaging_numbering_id', 'pn.packaging_id', 'pn.packaging_number', 'pn.status_code');

            $this->packaging = DB::table(DB::raw("({$query->toSql()}) as base"))
                ->mergeBindings($query)
                ->select(
                    'base.packaging_numbering_id',
                    'base.packaging_id',
                    'base.packaging_number',
                    'ph.packaging_alias',
                    'base.status_code',
                    'ps.status_description',
                    'base.created_on',
                    'base.created_by',
                    'base.updated_on',
                    'base.updated_by',
                    'puh.packaging_usage_hd_id',
                    'puh.used_by',
                    'puh.used_on',
                    'pud.work_order_hd_id',
                    'wo.work_order_number',
                    'pud.product_id',
                    'p.product_code',
                    'p.product_name',
                    'pud.product_qty'
                )
                ->leftJoin('precise.packaging_usage_hd as puh', function ($join) {
                    $join->on('base.packaging_numbering_id', '=', 'puh.packaging_numbering_id');
                    $join->on('base.max_counter', '=', 'puh.usage_counter');
                })
                ->leftJoin('precise.packaging_usage_dt as pud', 'puh.packaging_usage_hd_id', '=', 'pud.packaging_usage_hd_id')
                ->leftJoin('precise.packaging_hd as ph', 'base.packaging_id', '=', 'ph.packaging_id')
                ->leftJoin('precise.product as p', 'pud.product_id', '=', 'p.product_id')
                ->leftJoin('precise.work_order as wo', 'pud.work_order_hd_id', '=', 'wo.work_order_hd_id')
                ->leftJoin('precise.packaging_status as ps', 'base.status_code', '=', 'ps.status_code')
                ->get();

            if (count($this->packaging) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->packaging, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", data: $e->getMessage(), code: 500);
        }
    }
    public function showHistoryProductStatusByNumber($number)
    {
        try {
            $this->packaging = DB::table('precise.packaging_numbering as pn')
                ->where('pn.packaging_number', $number)
                ->select(
                    'pn.packaging_numbering_id',
                    'pn.packaging_id',
                    'pn.packaging_number',
                    'ph.packaging_alias',
                    'pn.status_code',
                    'ps.status_description',
                    'pn.created_on',
                    'pn.created_by',
                    'pn.updated_on',
                    'pn.updated_by',
                    'puh.packaging_usage_hd_id',
                    'puh.packaging_numbering_id',
                    'puh.usage_counter',
                    'puh.used_on',
                    'puh.used_by',
                    'pud.packaging_usage_dt_id',
                    'pud.packaging_usage_hd_id',
                    'pud.work_order_hd_id',
                    'wo.work_order_number',
                    'pud.product_id',
                    'p.product_code',
                    'p.product_name',
                    'pud.product_qty'
                )
                ->leftJoin('precise.packaging_usage_hd as puh', 'pn.packaging_numbering_id', '=', 'puh.packaging_numbering_id')
                ->leftJoin('precise.packaging_usage_dt as pud', 'puh.packaging_usage_hd_id', '=', 'pud.packaging_usage_hd_id')
                ->leftJoin('precise.packaging_hd as ph', 'pn.packaging_id', '=', 'ph.packaging_alias')
                ->leftJoin('precise.product as p', 'pud.product_id', '=', 'p.product_id')
                ->leftJoin('precise.work_order as wo', 'pud.work_order_hd_id', '=', 'wo.work_order_hd_id')
                ->leftJoin('precise.packaging_status as ps', 'pn.status_code', '=', 'ps.status_code')
                ->get();

            if (count($this->packaging) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->packaging, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", data: $e->getMessage(), code: 500);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'packaging_numbering_id'    =>  'required|exists:packaging_numbering,packaging_numbering_id',
            'packaging_number'          =>  'required|exists:packaging_numbering,packaging_number',
            'work_order_hd_id'          =>  'required|exists:work_order,work_order_hd_id',
            'product_id'                =>  'required|exists:product,product_id',
            'product_qty'               =>  'required|numeric',
            'created_by'                =>  'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            $select = DB::table('precise.packaging_numbering as pn')
                ->where('packaging_number', $request->packaging_number)
                ->select('packaging_numbering_id');

            DB::table('precise.packaging_usage_hd')
                ->insertUsing(
                    ['packaging_numbering_id', 'usage_counter', 'used_by'],
                    DB::table(
                        DB::raw("({$select->toSql()}) as base")
                    )->mergeBindings($select)
                        ->select(
                            'base.packaging_numbering_id',
                            DB::raw(
                                "ifnull(max(usage_counter), 0) + 1",
                            ),
                            DB::raw("
                                    '$request->created_by'
                                ")
                        )
                        ->leftJoin('precise.packaging_usage_hd as puh', 'base.packaging_numbering_id', '=', 'puh.packaging_numbering_id')
                        ->groupBy('base.packaging_numbering_id')
                );

            $id = DB::getPdo()->lastInsertId();

            if (!$id) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            $detail = DB::table("precise.packaging_usage_dt")
                ->insert([
                    "packaging_usage_hd_id" => $id,
                    "work_order_hd_id"      => $request->work_order_hd_id,
                    "product_id"            => $request->product_id,
                    "product_qty"           => $request->product_qty
                ]);

            if ($detail == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            $update = DB::table("precise.packaging_numbering")
                ->where("packaging_numbering_id", $request->packaging_numbering_id)
                ->update([
                    "status_code"   => "U"
                ]);

            if ($update == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "sucess input data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
