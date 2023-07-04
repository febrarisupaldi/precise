<?php

namespace App\Http\Controllers\Api\Production\Melamine;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Boolean;

class BSTBInspectionController extends Controller
{
    private $bstbInspection;

    public function showByBSTBNumber($number)
    {
        $this->bstbInspection = DB::table("precise.bstb_inspection as bi")
            ->where('bstb_num', $number)
            ->select(
                'bi.bstb_inspection_id',
                'bi.bstb_id',
                'bs.bstb_num',
                'bs.bstb_pprh_id',
                'bs.bstb_ppd_id',
                'bs.bstb_ppd_name',
                'bs.bstb_opr_id',
                'dtk2.NAMA AS bstb_opr_name',
                'bi.inspection_date',
                'bi.inspector_id',
                'dtk.NAMA as inspector_name',
                'bi.check_a',
                'bi.check_b',
                'bi.check_c',
                'bi.check_d',
                'bi.check_e',
                'bi.check_f',
                'bi.check_g',
                'bi.lot_size',
                'bi.sample_size',
                'bi.cumm_size',
                'bi.qty_ng',
                'bi.test_flatness',
                'bi.test_drop',
                'bi.test_functional',
                'bi.test_result',
                'bi.check_bubble_sticker_kw2',
                'bi.check_bubble_sticker_kw3',
                'bi.check_bubble_powder_kw2',
                'bi.check_bubble_powder_kw3',
                'bi.check_bubble_material_kw2',
                'bi.check_bubble_material_kw3',
                'bi.check_sticker_wreck_kw2',
                'bi.check_sticker_wreck_kw3',
                'bi.check_sticker_slant_kw2',
                'bi.check_sticker_slant_kw3',
                'bi.check_sticker_folded_kw2',
                'bi.check_sticker_folded_kw3',
                'bi.check_dirty_sticker_kw2',
                'bi.check_dirty_sticker_kw3',
                'bi.check_dirty_material_kw2',
                'bi.check_dirty_material_kw3',
                'bi.check_dot_kw2',
                'bi.check_dot_kw3',
                'bi.check_stripe_kw2',
                'bi.check_stripe_kw3',
                'bi.check_flashing_kw2',
                'bi.check_flashing_kw3',
                'bi.check_material_bs_printing',
                'bi.check_material_bs_plain',
                'bi.check_other_kw2',
                'bi.check_other_kw3',
                'bi.check_other_bs_printing',
                'bi.check_other_bs_plain',
                'bi.inspection_note',
                'bi.cavity_act',
                'bi.product_weight_std',
                'bi.product_weight_act1',
                'bi.product_weight_act2',
                'bi.product_weight_act3',
                'bi.product_weight_act4',
                'bi.dimension_flashing_std',
                'bi.dimension_flashing_act',
                'bi.dimension_product_std',
                'bi.dimension_product_act'
            )
            ->leftJoin('precise.bstb as bs', 'bi.bstb_id', '=', 'bs.bstb_id')
            ->leftJoin('dbhrd.newdatakar as dtk', 'bi.inspector_id', '=', 'dtk.NIP')
            ->leftJoin('dbhrd.newdatakar as dtk2', 'bs.bstb_opr_id', '=', 'dtk2.NIP')
            ->get();

        if (count($this->bstbInspection) == 0)
            return response()->json(["status" => "error", "message" => "not found", "data" => null], 404);

        return response()->json(["status" => "ok", "message" => "ok", "data" => $this->bstbInspection], 200);
    }

    public function show($id)
    {
        $this->bstbInspection = DB::table("precise.bstb_inspection as bi")
            ->where('bi.bstb_inspection_id', $id)
            ->select(
                'bi.bstb_inspection_id',
                'bi.bstb_id',
                'bs.bstb_num',
                'bs.bstb_pprh_id',
                'bs.bstb_ppd_id',
                'bs.bstb_ppd_name',
                'bs.bstb_opr_id',
                'dtk2.NAMA AS bstb_opr_name',
                'bi.inspection_date',
                'bi.inspector_id',
                'dtk.NAMA as inspector_name',
                'bi.check_a',
                'bi.check_b',
                'bi.check_c',
                'bi.check_d',
                'bi.check_e',
                'bi.check_f',
                'bi.check_g',
                'bi.lot_size',
                'bi.sample_size',
                'bi.cumm_size',
                'bi.qty_ng',
                'bi.test_flatness',
                'bi.test_drop',
                'bi.test_functional',
                'bi.test_result',
                'bi.check_bubble_sticker_kw2',
                'bi.check_bubble_sticker_kw3',
                'bi.check_bubble_powder_kw2',
                'bi.check_bubble_powder_kw3',
                'bi.check_bubble_material_kw2',
                'bi.check_bubble_material_kw3',
                'bi.check_sticker_wreck_kw2',
                'bi.check_sticker_wreck_kw3',
                'bi.check_sticker_slant_kw2',
                'bi.check_sticker_slant_kw3',
                'bi.check_sticker_folded_kw2',
                'bi.check_sticker_folded_kw3',
                'bi.check_dirty_sticker_kw2',
                'bi.check_dirty_sticker_kw3',
                'bi.check_dirty_material_kw2',
                'bi.check_dirty_material_kw3',
                'bi.check_dot_kw2',
                'bi.check_dot_kw3',
                'bi.check_stripe_kw2',
                'bi.check_stripe_kw3',
                'bi.check_flashing_kw2',
                'bi.check_flashing_kw3',
                'bi.check_material_bs_printing',
                'bi.check_material_bs_plain',
                'bi.check_other_kw2',
                'bi.check_other_kw3',
                'bi.check_other_bs_printing',
                'bi.check_other_bs_plain',
                'bi.inspection_note',
                'bi.cavity_act',
                'bi.product_weight_std',
                'bi.product_weight_act1',
                'bi.product_weight_act2',
                'bi.product_weight_act3',
                'bi.product_weight_act4',
                'bi.dimension_flashing_std',
                'bi.dimension_flashing_act',
                'bi.dimension_product_std',
                'bi.dimension_product_act'
            )
            ->leftJoin('precise.bstb as bs', 'bi.bstb_id', '=', 'bs.bstb_id')
            ->leftJoin('dbhrd.newdatakar as dtk', 'bi.inspector_id', '=', 'dtk.NIP')
            ->leftJoin('dbhrd.newdatakar as dtk2', 'bs.bstb_opr_id', '=', 'dtk2.NIP')
            ->first();

        if (empty($this->bstbInspection))
            return response()->json(["status" => "error", "message" => "not found", "data" => null], 404);

        return response()->json($this->bstbInspection, 200);
    }

    public function showLotCummulativeByBSTBID($id)
    {
        $this->bstbInspection = DB::table("precise.bstb_inspection")
            ->where('bstb_id', $id)
            ->select(
                'bstb_id',
                DB::raw("IFNULL(SUM(lot_size),0) as cumm_size"),
                DB::raw("IFNULL(SUM(
                    check_bubble_sticker_kw2 + 
                    check_bubble_powder_kw2 + 
                    check_bubble_material_kw2 + 
                    check_sticker_wreck_kw2 + 
                    check_sticker_slant_kw2 + 
                    check_sticker_folded_kw2 + 
                    check_dirty_sticker_kw2 + 
                    check_dirty_material_kw2 + 
                    check_dot_kw2 + 
                    check_stripe_kw2 + 
                    check_flashing_kw2 + 
                    check_other_kw2),0)
                AS total_kw2"),
                DB::raw("
                IFNULL(SUM(
                    check_bubble_sticker_kw3 + 
                    check_bubble_powder_kw3 + 
                    check_bubble_material_kw3 + 
                    check_sticker_wreck_kw3 + 
                    check_sticker_slant_kw3 + 
                    check_sticker_folded_kw3 + 
                    check_dirty_sticker_kw3 + 
                    check_dirty_material_kw3 + 
                    check_dot_kw3 + 
                    check_stripe_kw3 + 
                    check_flashing_kw3 + 
                    check_other_kw3),0)
                AS total_kw3"),
                DB::raw("
                IFNULL(SUM(check_material_bs_printing + check_other_bs_printing),0) AS total_bs_printing"),
                DB::raw("
                IFNULL(SUM(check_material_bs_plain + check_other_bs_plain),0) AS total_bs_plain"),
            )
            ->groupBy("bstb_id")
            ->get();

        if (count($this->bstbInspection) == 0)
            return response()->json(["status" => "error", "message" => "not found", "data" => null], 404);

        return response()->json(["status" => "ok", "message" => "ok", "data" => $this->bstbInspection], 200);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bstb_inspection_id'            => 'required|exists:bstb_inspection,bstb_inspection_id',
            'check_bubble_sticker_kw2'      => 'required',
            'check_bubble_sticker_kw3'      => 'required',
            'check_bubble_powder_kw2'       => 'required',
            'check_bubble_powder_kw3'       => 'required',
            'check_bubble_material_kw2'     => 'required',
            'check_bubble_material_kw3'     => 'required',
            'check_sticker_wreck_kw2'       => 'required',
            'check_sticker_wreck_kw3'       => 'required',
            'check_sticker_slant_kw2'       => 'required',
            'check_sticker_slant_kw3'       => 'required',
            'check_sticker_folded_kw2'      => 'required',
            'check_sticker_folded_kw3'      => 'required',
            'check_dirty_sticker_kw2'       => 'required',
            'check_dirty_sticker_kw3'       => 'required',
            'check_dirty_material_kw2'      => 'required',
            'check_dirty_material_kw3'      => 'required',
            'check_dot_kw2'                 => 'required',
            'check_dot_kw3'                 => 'required',
            'check_stripe_kw2'              => 'required',
            'check_stripe_kw3'              => 'required',
            'check_flashing_kw2'            => 'required',
            'check_flashing_kw3'            => 'required',
            'check_material_bs_printing'    => 'required',
            'check_material_bs_plain'       => 'required',
            'check_other_kw2'               => 'required',
            'check_other_kw3'               => 'required',
            'check_other_bs_printing'       => 'required',
            'check_other_bs_plain'          => 'required',
            'inspection_note'               => 'nullable',
            'cavity_act'                    => 'required',
            'product_weight_std'            => 'required',
            'product_weight_act1'           => 'required',
            'product_weight_act2'           => 'required',
            'product_weight_act3'           => 'required',
            'product_weight_act4'           => 'required',
            'dimension_flashing_std'        => 'required',
            'dimension_flashing_act'        => 'required',
            'dimension_product_std'         => 'required',
            'dimension_product_act'         => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->bstbInspection = DB::table("precise.bstb_inspection")
            ->where("bstb_inspection_id", $request->bstb_inspection_id)
            ->update([
                'check_bubble_sticker_kw2'      => $request->check_bubble_sticker_kw2,
                'check_bubble_sticker_kw3'      => $request->check_bubble_sticker_kw3,
                'check_bubble_powder_kw2'       => $request->check_bubble_powder_kw2,
                'check_bubble_powder_kw3'       => $request->check_bubble_powder_kw3,
                'check_bubble_material_kw2'     => $request->check_bubble_material_kw2,
                'check_bubble_material_kw3'     => $request->check_bubble_material_kw3,
                'check_sticker_wreck_kw2'       => $request->check_sticker_wreck_kw2,
                'check_sticker_wreck_kw3'       => $request->check_sticker_wreck_kw3,
                'check_sticker_slant_kw2'       => $request->check_sticker_slant_kw2,
                'check_sticker_slant_kw3'       => $request->check_sticker_slant_kw3,
                'check_sticker_folded_kw2'      => $request->check_sticker_folded_kw2,
                'check_sticker_folded_kw3'      => $request->check_sticker_folded_kw3,
                'check_dirty_sticker_kw2'       => $request->check_dirty_sticker_kw2,
                'check_dirty_sticker_kw3'       => $request->check_dirty_sticker_kw3,
                'check_dirty_material_kw2'      => $request->check_dirty_material_kw2,
                'check_dirty_material_kw3'      => $request->check_dirty_material_kw3,
                'check_dot_kw2'                 => $request->check_dot_kw2,
                'check_dot_kw3'                 => $request->check_dot_kw3,
                'check_stripe_kw2'              => $request->check_stripe_kw2,
                'check_stripe_kw3'              => $request->check_stripe_kw3,
                'check_flashing_kw2'            => $request->check_flashing_kw2,
                'check_flashing_kw3'            => $request->check_flashing_kw3,
                'check_material_bs_printing'    => $request->check_material_bs_printing,
                'check_material_bs_plain'       => $request->check_material_bs_plain,
                'check_other_kw2'               => $request->check_other_kw2,
                'check_other_kw3'               => $request->check_other_kw3,
                'check_other_bs_printing'       => $request->check_other_bs_printing,
                'check_other_bs_plain'          => $request->check_other_bs_plain,
                'inspection_note'               => $request->inspection_note,
                'cavity_act'                    => $request->cavity_act,
                'product_weight_std'            => $request->product_weight_std,
                'product_weight_act1'           => $request->product_weight_act1,
                'product_weight_act2'           => $request->product_weight_act2,
                'product_weight_act3'           => $request->product_weight_act3,
                'product_weight_act4'           => $request->product_weight_act4,
                'dimension_flashing_std'        => $request->dimension_flashing_std,
                'dimension_flashing_act'        => $request->dimension_flashing_act,
                'dimension_product_std'         => $request->dimension_product_std,
                'dimension_product_act'         => $request->dimension_product_act
            ]);

        if ($this->bstbInspection == 0)
            return response()->json(["status" => "error", "message" => "server error"], 500);

        return response()->json(["status" => "ok", "message" => "success update data"], 200);
    }

    private function createNewData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bstb_id'                       => 'required|exists:bstb,bstb_id',
            'inspection_date'               => 'required',
            'inspector_id'                  => 'required',
            'check_a'                       => 'required',
            'check_b'                       => 'required',
            'check_c'                       => 'required',
            'check_d'                       => 'required',
            'check_e'                       => 'required',
            'check_f'                       => 'required',
            'check_g'                       => 'required',
            'lot_size'                      => 'required',
            'sample_size'                   => 'required',
            'cumm_size'                     => 'required',
            'qty_ng'                        => 'required',
            'test_flatness'                 => 'nullable',
            'test_drop'                     => 'nullable',
            'test_functional'               => 'nullable',
            'test_result'                   => 'required',
            'check_bubble_sticker_kw2'      => 'required',
            'check_bubble_sticker_kw3'      => 'required',
            'check_bubble_powder_kw2'       => 'required',
            'check_bubble_powder_kw3'       => 'required',
            'check_bubble_material_kw2'     => 'required',
            'check_bubble_material_kw3'     => 'required',
            'check_sticker_wreck_kw2'       => 'required',
            'check_sticker_wreck_kw3'       => 'required',
            'check_sticker_slant_kw2'       => 'required',
            'check_sticker_slant_kw3'       => 'required',
            'check_sticker_folded_kw2'      => 'required',
            'check_sticker_folded_kw3'      => 'required',
            'check_dirty_sticker_kw2'       => 'required',
            'check_dirty_sticker_kw3'       => 'required',
            'check_dirty_material_kw2'      => 'required',
            'check_dirty_material_kw3'      => 'required',
            'check_dot_kw2'                 => 'required',
            'check_dot_kw3'                 => 'required',
            'check_stripe_kw2'              => 'required',
            'check_stripe_kw3'              => 'required',
            'check_flashing_kw2'            => 'required',
            'check_flashing_kw3'            => 'required',
            'check_material_bs_printing'    => 'required',
            'check_material_bs_plain'       => 'required',
            'check_other_kw2'               => 'required',
            'check_other_kw3'               => 'required',
            'check_other_bs_printing'       => 'required',
            'check_other_bs_plain'          => 'required',
            'inspection_note'               => 'nullable',
            'cavity_act'                    => 'required',
            'product_weight_std'            => 'required',
            'product_weight_act1'           => 'required',
            'product_weight_act2'           => 'required',
            'product_weight_act3'           => 'required',
            'product_weight_act4'           => 'required',
            'dimension_flashing_std'        => 'required',
            'dimension_flashing_act'        => 'required',
            'dimension_product_std'         => 'required',
            'dimension_product_act'         => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->bstbInspection = DB::table("precise.bstb_inspection")
            ->insert([
                'bstb_id'                       => $request['bstb_id'],
                'inspection_date'               => $request['inspection_date'],
                'inspector_id'                  => $request['inspector_id'],
                'check_a'                       => $request['check_a'],
                'check_b'                       => $request['check_b'],
                'check_c'                       => $request['check_c'],
                'check_d'                       => $request['check_d'],
                'check_e'                       => $request['check_e'],
                'check_f'                       => $request['check_f'],
                'check_g'                       => $request['check_g'],
                'lot_size'                      => $request['lot_size'],
                'sample_size'                   => $request['sample_size'],
                'cumm_size'                     => $request['cumm_size'],
                'qty_ng'                        => $request['qty_ng'],
                'test_flatness'                 => $request['test_flatness'],
                'test_drop'                     => $request['test_drop'],
                'test_functional'               => $request['test_functional'],
                'test_result'                   => $request['test_result'],
                'check_bubble_sticker_kw2'      => $request['check_bubble_sticker_kw2'],
                'check_bubble_sticker_kw3'      => $request['check_bubble_sticker_kw3'],
                'check_bubble_powder_kw2'       => $request['check_bubble_powder_kw2'],
                'check_bubble_powder_kw3'       => $request['check_bubble_powder_kw3'],
                'check_bubble_material_kw2'     => $request['check_bubble_material_kw2'],
                'check_bubble_material_kw3'     => $request['check_bubble_material_kw3'],
                'check_sticker_wreck_kw2'       => $request['check_sticker_wreck_kw2'],
                'check_sticker_wreck_kw3'       => $request['check_sticker_wreck_kw3'],
                'check_sticker_slant_kw2'       => $request['check_sticker_slant_kw2'],
                'check_sticker_slant_kw3'       => $request['check_sticker_slant_kw3'],
                'check_sticker_folded_kw2'      => $request['check_sticker_folded_kw2'],
                'check_sticker_folded_kw3'      => $request['check_sticker_folded_kw3'],
                'check_dirty_sticker_kw2'       => $request['check_dirty_sticker_kw2'],
                'check_dirty_sticker_kw3'       => $request['check_dirty_sticker_kw3'],
                'check_dirty_material_kw2'      => $request['check_dirty_material_kw2'],
                'check_dirty_material_kw3'      => $request['check_dirty_material_kw3'],
                'check_dot_kw2'                 => $request['check_dot_kw2'],
                'check_dot_kw3'                 => $request['check_dot_kw3'],
                'check_stripe_kw2'              => $request['check_stripe_kw2'],
                'check_stripe_kw3'              => $request['check_stripe_kw3'],
                'check_flashing_kw2'            => $request['check_flashing_kw2'],
                'check_flashing_kw3'            => $request['check_flashing_kw3'],
                'check_material_bs_printing'    => $request['check_material_bs_printing'],
                'check_material_bs_plain'       => $request['check_material_bs_plain'],
                'check_other_kw2'               => $request['check_other_kw2'],
                'check_other_kw3'               => $request['check_other_kw3'],
                'check_other_bs_printing'       => $request['check_other_bs_printing'],
                'check_other_bs_plain'          => $request['check_other_bs_plain'],
                'inspection_note'               => $request['inspection_note'],
                'cavity_act'                    => $request['cavity_act'],
                'product_weight_std'            => $request['product_weight_std'],
                'product_weight_act1'           => $request['product_weight_act1'],
                'product_weight_act2'           => $request['product_weight_act2'],
                'product_weight_act3'           => $request['product_weight_act3'],
                'product_weight_act4'           => $request['product_weight_act4'],
                'dimension_flashing_std'        => $request['dimension_flashing_std'],
                'dimension_flashing_act'        => $request['dimension_flashing_act'],
                'dimension_product_std'         => $request['dimension_product_std'],
                'dimension_product_act'         => $request['dimension_product_act']
            ]);

        return $this->bstbInspection;
    }
    public function create(Request $request)
    {
        $this->bstbInspection = $this->createNewData($request);

        if ($this->bstbInspection == 0)
            return response()->json(["status" => "error", "message" => $this->bstbInspection], 500);

        return response()->json(["status" => "ok", "message" => "success insert data"], 200);
    }

    public function closedQCChecked(Request $request)
    {
        DB::beginTransaction();
        $this->bstbInspection = $this->createNewData($request);

        if ($this->bstbInspection == 0) {
            DB::rollBack();
            return response()->json(["status" => "error", "message" => "server error"], 500);
        }
        $this->bstbInspection = DB::table("precise.bstb")
            ->where("bstb_id", $request->bstb_id)
            ->update([
                'bstb_pressing_status' => 'X'
            ]);

        if ($this->bstbInspection == 0) {
            DB::rollBack();
            return response()->json(["status" => "error", "message" => "server error"], 500);
        }

        $validator = Validator::make($request->all(), [
            'bstb_id'               => 'exists:bstb_setting_and_result,bstb_id',
            'result_kw2'            => 'required',
            'result_kw3'            => 'required',
            'result_bs_printing'    => 'required',
            'result_bs_plain'       => 'required',
        ]);

        if ($validator->fails()) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }

        $this->bstbInspection = DB::table("precise.bstb_setting_and_result")
            ->where('bstb_id', $request->bstb_id)
            ->update([
                'result_kw2'            => $request->result_kw2,
                'result_kw3'            => $request->result_kw3,
                'result_bs_printing'    => $request->result_bs_printing,
                'result_bs_plain'       => $request->result_bs_plain,
            ]);

        if ($this->bstbInspection == 0) {
            DB::rollBack();
            return response()->json(["status" => "error", "message" => "server error"], 500);
        }
        DB::commit();
        return response()->json(["status" => "ok", "message" => "success update data"], 200);
    }
}
