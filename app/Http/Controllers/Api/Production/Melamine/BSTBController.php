<?php

namespace App\Http\Controllers\Api\Production\Melamine;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BSTBController extends Controller
{
    private $bstb;
    public function showMachineByDateShift($date, $shift)
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
            return response()->json(['status' => 'error', 'data' => 'not found'], 404);

        return response()->json(['status' => 'ok', 'data' => $this->bstb], 200);
    }

    public function showMachineByDateShiftSetter(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'bstb_date'         =>  'required|date_format:Y-m-d',
                'bstb_shift'        =>  'required',
                'bstb_setter_id'    =>  'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        $this->bstb = DB::table("precise.bstb")
            ->where('bstb_date', $request->bstb_date)
            ->where('bstb_shift', $request->bstb_shift)
            ->where('bstb_setter_id', $request->bstb_setter_id)
            ->where('bstb_kw_status', 1)
            ->select(
                'bstb_mmac_loc',
                DB::raw('COUNT(*) as count_bstb')
            )
            ->groupBy('bstb_mmac_loc')
            ->get();

        if (count($this->bstb) == 0)
            return response()->json(['status' => 'error', 'data' => 'not found'], 404);

        return response()->json(['status' => 'ok', 'data' => $this->bstb], 200);
    }

    public function showBSTBByDateShiftMachine(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'bstb_date'         =>  'required|date_format:Y-m-d',
                'bstb_shift'        =>  'required',
                'bstb_mmac_loc'     =>  'required',
                'bstb_setter_id'    =>  'nullable'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        if ($request->bstb_setter_id == null || empty($request->bstb_setter_id))

            $this->bstb = DB::table('precise.bstb as bs')
                ->where('bstb_date', $request->bstb_date)
                ->where('bstb_shift', $request->bstb_shift)
                ->where('bstb_mmac_loc', $request->bstb_mmac_loc)
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
                ->where('bstb_date', $request->bstb_date)
                ->where('bstb_shift', $request->bstb_shift)
                ->where('bstb_mmac_loc', $request->bstb_mmac_loc)
                ->where('bstb_setter_id', $request->bstb_setter_id)
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
            return response()->json(['status' => 'error', 'data' => 'not found'], 404);

        return response()->json(['status' => 'ok', 'data' => $this->bstb], 200);
    }

    public function showByBSTBNumber($number)
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
                'bs.bstb_ppd_id',
                'bs.bstb_ppd_name',
                'bs.bstb_op_qty',
                'bs.bstb_ins_date',
                'bs.bstb_ins_by'
            )
            ->leftJoin("dbhrd.newdatakar as dtk", "bs.bstb_opr_id", "=", "dtk.NIP")
            ->leftJoin("dbhrd.newdatakar as dtk2", "bs.bstb_setter_id", "=", "dtk2.NIP")
            ->get();

        if (count($this->bstb) == 0) {
            return response()->json(["status" => "error", "message" => "not found"], 404);
        }

        return response()->json(["status" => "ok", "data" => $this->bstb], 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'bstb_date'                 =>  'required|date_format:Y-m-d',
                'bstb_opr_id'               =>  'required',
                'bstb_shift'                =>  'required',
                'bstb_setter_id'            =>  'required',
                'bstb_mmac_loc'             =>  'required',
                'bstb_cav_num'              =>  'required',
                'bstb_item'                 =>  'required',
                'bstb_design'               =>  'required',
                'bstb_material_lot_number'  =>  'required',
                'created_by'                =>  'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
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
            DB::table("precise.bstb")
                ->insert($values);

            $check = DB::statement("CALL precise.system_increment_transaction_counter(7, :bstb_date)", array(':bstb_date' => $request->bstb_date));

            if (!$check) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "error insert data"], 500);
            }

            $number = DB::select(DB::raw("select @bstbNumber as number"));
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => $number], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateMaterial(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'bstb_num'                  =>  'required',
                'bstb_opr_id'               =>  'required',
                'bstb_setter_id'            =>  'required',
                'bstb_material_lot_number'  =>  'required',
                'bstb_upd_by'               =>  'required'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
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
                return response()->json(['status' => 'error', 'message' => "error update data"], 500);
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
                return response()->json(['status' => 'error', 'message' => "error update data"], 500);
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
                return response()->json(['status' => 'error', 'message' => "error update data"], 500);
            }

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => "update data success"], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
