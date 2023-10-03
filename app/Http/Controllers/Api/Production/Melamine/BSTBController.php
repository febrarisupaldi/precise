<?php

namespace App\Http\Controllers\Api\Production\Melamine;

use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BSTBController extends Controller
{
    private $bstb;
    public function showMachineByDateShift($date, $shift): JsonResponse
    {
        $this->bstb = DB::table("precise.bstb")
            ->where('bstb_date', $date)
            ->where('bstb_shift', $shift)
            ->where('bstb_kw_status', 1)
            ->select(
                'bstb_mmac_loc',
                DB::raw('COUNT(*) as count_bstb')
            )
            ->groupBy('bstb_mmac_loc')
            ->get();

        if (count($this->bstb) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->bstb, code: 200);
    }

    public function showMachineByDateShiftSetter($date, $shift, $setter): JsonResponse
    {
        $this->bstb = DB::table("precise.bstb")
            ->where('bstb_date', $date)
            ->where('bstb_shift', $shift)
            ->where('bstb_setter_id', $setter)
            ->where('bstb_kw_status', 1)
            ->select(
                'bstb_mmac_loc',
                DB::raw('COUNT(*) as count_bstb')
            )
            ->groupBy('bstb_mmac_loc')
            ->get();

        if (count($this->bstb) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->bstb, code: 200);
    }

    public function showBSTBByDateShiftMachine($date, $shift, $machine, $setter): JsonResponse
    {

        if ($setter == null || empty($setter))

            $this->bstb = DB::table('precise.bstb as bs')
                ->where('bstb_date', $date)
                ->where('bstb_shift', $shift)
                ->where('bstb_mmac_loc', $machine)
                ->where('bstb_kw_status', 1)
                ->select(
                    'bs.bstb_num',
                    'bs.bstb_item',
                    'bs.bstb_design',
                    'bs.bstb_pprh_id',
                    'bs.bstb_ppd_id',
                    'bs.bstb_ppd_name',
                    'bs.bstb_cav_num',
                    'bs.bstb_material_lot_number',
                    'bs.bstb_opr_id',
                    'dtk.NAMA AS bstb_opr_name',
                    'bs.bstb_setter_id',
                    'dtk2.NAMA AS bstb_setter_name',
                    'bs.bstb_ins_date',
                    'bs.bstb_ins_by'
                )
                ->leftJoin("dbhrd.newdatakar as dtk", "bs.bstb_opr_id", "=", "dtk.NIP")
                ->leftJoin("dbhrd.newdatakar as dtk2", "bs.bstb_setter_id", "=", "dtk2.NIP")
                ->get();
        else
            $this->bstb = DB::table('precise.bstb as bs')
                ->where('bstb_date', $date)
                ->where('bstb_shift', $shift)
                ->where('bstb_mmac_loc', $machine)
                ->where('bstb_setter_id', $setter)
                ->where('bstb_kw_status', 1)
                ->select(
                    'bs.bstb_num',
                    'bs.bstb_item',
                    'bs.bstb_design',
                    'bs.bstb_pprh_id',
                    'bs.bstb_ppd_id',
                    'bs.bstb_ppd_name',
                    'bs.bstb_cav_num',
                    'bs.bstb_material_lot_number',
                    'bs.bstb_opr_id',
                    'dtk.NAMA AS bstb_opr_name',
                    'bs.bstb_setter_id',
                    'dtk2.NAMA AS bstb_setter_name',
                    'bs.bstb_ins_date',
                    'bs.bstb_ins_by'
                )
                ->leftJoin("dbhrd.newdatakar as dtk", "bs.bstb_opr_id", "=", "dtk.NIP")
                ->leftJoin("dbhrd.newdatakar as dtk2", "bs.bstb_setter_id", "=", "dtk2.NIP")
                ->get();

        if (count($this->bstb) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->bstb, code: 200);
    }

    public function showByBSTBNumber($number): JsonResponse
    {
        $this->bstb = DB::table("precise.bstb as bs")
            ->where("bs.bstb_num", $number)
            ->select(
                'bs.bstb_id',
                'bs.bstb_num',
                'bs.bstb_date',
                'bs.bstb_shift',
                'bs.bstb_status',
                'bs.bstb_kw_status',
                'bs.bstb_pressing_status',
                'bs.bstb_item',
                'bs.bstb_design',
                'bs.bstb_mmac_loc',
                'bs.bstb_cav_num',
                'bstb_material_lot_number',
                'bs.bstb_opr_id',
                'dtk.NAMA AS bstb_opr_name',
                'bs.bstb_setter_id',
                'dtk2.NAMA AS bstb_setter_name',
                'bs.bstb_pprh_id',
                'p1.product_id as product_id_gip',
                'bs.bstb_ppd_id',
                'bs.bstb_ppd_name',
                'bs.bstb_op_qty',
                'p2.product_id as product_id_fg',
                'bs.bstb_packing_ppd_id',
                'bs.bstb_packing_ppd_name',
                'bs.bstb_packing_qty',
                'bs.bstb_packing_std',
                'bs.bstb_retail_num',
                'bs.bstb_retail_date',
                'bs.bstb_retail_pprh_id',
                'bs.bstb_retail_qty',
                'bs.bstb_ins_date',
                'bs.bstb_ins_by'
            )
            ->leftJoin("dbhrd.newdatakar as dtk", "bs.bstb_opr_id", "=", "dtk.NIP")
            ->leftJoin("dbhrd.newdatakar as dtk2", "bs.bstb_setter_id", "=", "dtk2.NIP")
            ->leftJoin("precise.product as p1", "bs.bstb_ppd_id", "=", "p1.product_code")
            ->leftJoin("precise.product as p2", "bs.bstb_packing_ppd_id", "=", "p2.product_code")
            ->get();

        if (count($this->bstb) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->bstb, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'bstb_date'                 =>  'required|date_format:Y-m-d',
                'bstb_opr_id'               =>  'required',
                'bstb_shift'                =>  'required|numeric',
                'bstb_setter_id'            =>  'required|numeric',
                'bstb_mmac_loc'             =>  'required',
                'bstb_cav_num'              =>  'required',
                'bstb_item'                 =>  'required',
                'bstb_design'               =>  'required',
                'bstb_material_lot_number'  =>  'required|numeric',
                'created_by'                =>  'required'
            ]
        );

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DB::statement("SET @bstbNumber = precise.get_transaction_number(7, :bstb_date)", array(':bstb_date' => $request->bstb_date));
            $values = [
                [
                    'bstb_num'                  => DB::raw("concat(@bstbNumber, 'A')"),
                    'bstb_date'                 => $request->bstb_date,
                    'bstb_shift'                => $request->bstb_shift,
                    'bstb_setter_id'            => $request->bstb_setter_id,
                    'bstb_opr_id'               => $request->bstb_opr_id,
                    'bstb_mmac_loc'             => $request->bstb_mmac_loc,
                    'bstb_cav_num'              => $request->bstb_cav_num,
                    'bstb_item'                 => $request->bstb_item,
                    'bstb_design'               => $request->bstb_design,
                    'bstb_material_lot_number'  => $request->bstb_material_lot_number,
                    'bstb_ins_by'               => $request->created_by
                ], [
                    'bstb_num'                  => DB::raw("concat(@bstbNumber, 'B')"),
                    'bstb_date'                 => $request->bstb_date,
                    'bstb_shift'                => $request->bstb_shift,
                    'bstb_setter_id'            => $request->bstb_setter_id,
                    'bstb_opr_id'               => $request->bstb_opr_id,
                    'bstb_mmac_loc'             => $request->bstb_mmac_loc,
                    'bstb_cav_num'              => $request->bstb_cav_num,
                    'bstb_item'                 => $request->bstb_item,
                    'bstb_design'               => $request->bstb_design,
                    'bstb_material_lot_number'  => $request->bstb_material_lot_number,
                    'bstb_ins_by'               => $request->created_by
                ], [
                    'bstb_num'                  => DB::raw("concat(@bstbNumber, 'C')"),
                    'bstb_date'                 => $request->bstb_date,
                    'bstb_shift'                => $request->bstb_shift,
                    'bstb_setter_id'            => $request->bstb_setter_id,
                    'bstb_opr_id'               => $request->bstb_opr_id,
                    'bstb_mmac_loc'             => $request->bstb_mmac_loc,
                    'bstb_cav_num'              => $request->bstb_cav_num,
                    'bstb_item'                 => $request->bstb_item,
                    'bstb_design'               => $request->bstb_design,
                    'bstb_material_lot_number'  => $request->bstb_material_lot_number,
                    'bstb_ins_by'               => $request->created_by
                ]
            ];
            $check = DB::table("precise.bstb")
                ->insert($values);

            if ($check < 3) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error input data", code: 500);
            }

            $check = DB::statement("CALL precise.system_increment_transaction_counter(7, :bstb_date)", array(':bstb_date' => $request->bstb_date));

            if (!$check) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error input data", code: 500);
            }

            $number = DB::select(DB::raw("select @bstbNumber as number"));
            DB::commit();
            return ResponseController::json(status: "ok", message: "success input data", id: $number['number'], code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function updateMaterial(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'bstb_num'                  =>  'required',
                'bstb_opr_id'               =>  'required|numeric',
                'bstb_setter_id'            =>  'required|numeric',
                'bstb_material_lot_number'  =>  'required|numeric',
                'bstb_upd_by'               =>  'required'
            ]
        );

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            $this->bstb = DB::table("precise.bstb")
                ->where("bstb_num", DB::raw("CONCAT(LEFT(?,9),'A')"))
                ->setBindings([$request->bstb_num])
                ->update([
                    'bstb_setter_id'            => $request->bstb_setter_id,
                    'bstb_opr_id'               => $request->bstb_opr_id,
                    'bstb_material_lot_number'  => $request->bstb_material_lot_number,
                    'bstb_upd_by'               => $request->bstb_upd_by
                ]);

            if ($this->bstb == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error update data", code: 500);
            }

            $this->bstb = DB::table("precise.bstb")
                ->where("bstb_num", DB::raw("CONCAT(LEFT(?,9),'B')"))
                ->setBindings([$request->bstb_num])
                ->update([
                    'bstb_setter_id'            => $request->bstb_setter_id,
                    'bstb_opr_id'               => $request->bstb_opr_id,
                    'bstb_material_lot_number'  => $request->bstb_material_lot_number,
                    'bstb_upd_by'               => $request->bstb_upd_by
                ]);

            if ($this->bstb == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error update data", code: 500);
            }

            $this->bstb = DB::table("precise.bstb")
                ->where("bstb_num", DB::raw("CONCAT(LEFT(?,9),'C')"))
                ->setBindings([$request->bstb_num])
                ->update([
                    'bstb_setter_id'            => $request->bstb_setter_id,
                    'bstb_opr_id'               => $request->bstb_opr_id,
                    'bstb_material_lot_number'  => $request->bstb_material_lot_number,
                    'bstb_upd_by'               => $request->bstb_upd_by
                ]);

            if ($this->bstb == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "error update data", code: 500);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success update data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
