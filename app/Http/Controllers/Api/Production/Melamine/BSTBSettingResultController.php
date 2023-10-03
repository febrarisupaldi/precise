<?php

namespace App\Http\Controllers\Api\Production\Melamine;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BSTBSettingResultController extends Controller
{
    private $bstbSettingResult;
    public function showByBSTBNumber($number): JsonResponse
    {
        $this->bstbSettingResult = DB::table("precise.bstb_setting_and_result as bsr")
            ->where("bs.bstb_num", $number)
            ->select(
                'bsr.bstb_setting_and_result_id',
                'bs.bstb_id',
                'bs.bstb_num',
                'bs.bstb_mmac_loc',
                'bs.bstb_pprh_id',
                'bs.bstb_opr_id',
                'dtk.NAMA AS bstb_opr_name',
                'bs.bstb_setter_id',
                'dtk.NAMA AS bstb_setter_name',
                'bs.bstb_item',
                'bs.bstb_design',
                'bs.bstb_cav_num',
                'bs.bstb_material_lot_number',
                'bsr.product_weight_std_1',
                'bsr.product_weight_std_2',
                'bsr.product_weight_act',
                'bsr.temp_upper',
                'bsr.temp_lower',
                'bsr.t16',
                'bsr.t17',
                'bsr.t18',
                'bsr.pressure_upper',
                'bsr.pressure_lower',
                'bsr.cycle_time',
                'bsr.result_kw1',
                'bsr.result_kw2',
                'bsr.result_kw3',
                'bsr.result_bs_printing',
                'bsr.result_bs_plain',
                'bsr.work_hour',
                'bsr.target_per_hour',
                'bsr.result_description',
                'bsr.approved_on',
                'bsr.approved_by',
                'bsr.created_on',
                'bsr.created_by',
                'bsr.updated_on',
                'bsr.updated_by'
            )
            ->leftJoin("precise.bstb as bs", "bsr.bstb_id", "=", "bs.bstb_id")
            ->leftJoin("dbhrd.newdatakar as dtk", "bs.bstb_opr_id", "=", "dtk.NIP")
            ->leftJoin("dbhrd.newdatakar as dtk2", "bs.bstb_setter_id", "=", "dtk2.NIP")
            ->get();

        if (count($this->bstbSettingResult) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->bstbSettingResult, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bstb_id'               => 'required|exists:bstb,bstb_id',
            'product_weight_std_1'  => 'required',
            'product_weight_std_2'  => 'required',
            'product_weight_act'    => 'required',
            'temp_upper'            => 'required',
            'temp_lower'            => 'required',
            't16'                   => 'nullable',
            't17'                   => 'nullable',
            't18'                   => 'nullable',
            'pressure_upper'        => 'nullable',
            'pressure_lower'        => 'nullable',
            'cycle_time'            => 'required',
            'desc'                  => 'nullable',
            'created_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        $this->bstbSettingResult = DB::table("precise.bstb_setting_and_result")
            ->upsert([
                'bstb_id'               => $request->bstb_id,
                'product_weight_std_1'  => $request->product_weight_std_1,
                'product_weight_std_2'  => $request->product_weight_std_2,
                'product_weight_act'    => $request->product_weight_act,
                'temp_upper'            => $request->temp_upper,
                'temp_lower'            => $request->temp_lower,
                't16'                   => $request->t16,
                't17'                   => $request->t17,
                't18'                   => $request->t18,
                'pressure_upper'        => $request->pressure_upper,
                'pressure_lower'        => $request->pressure_lower,
                'cycle_time'            => $request->cycle_time,
                'result_description'    => $request->desc,
                'created_by'            => $request->created_by
            ], ['bstb_setting_and_result_id']);

        if ($this->bstbSettingResult == 0)
            return ResponseController::json(status: "error", message: "error input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);

        // if ($this->bstbSettingResult == 0) {
        //     $check = DB::table("precise.bstb_setting_and_result")
        //         ->where("bstb_id", $request->bstb_id)
        //         ->count();
        // }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bstb_setting_and_result_id'    => 'required|exists:bstb_setting_and_result,bstb_setting_and_result_id',
            'bstb_id'                       => 'required|exists:bstb,bstb_id',
            'product_weight_std_1'          => 'required',
            'product_weight_std_2'          => 'required',
            'product_weight_act'            => 'required',
            'temp_upper'                    => 'required',
            'temp_lower'                    => 'required',
            't16'                           => 'nullable',
            't17'                           => 'nullable',
            't18'                           => 'nullable',
            'pressure_upper'                => 'nullable',
            'pressure_lower'                => 'nullable',
            'cycle_time'                    => 'required',
            'desc'                          => 'nullable',
            'updated_by'                    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->bstbSettingResult = DB::table("precise.bstb_setting_and_result")
                ->where('bstb_setting_and_result_id', $request->bstb_setting_and_result_id)
                ->update([
                    'bstb_id'               => $request->bstb_id,
                    'product_weight_std_1'  => $request->product_weight_std_1,
                    'product_weight_std_2'  => $request->product_weight_std_2,
                    'product_weight_act'    => $request->product_weight_act,
                    'temp_upper'            => $request->temp_upper,
                    'temp_lower'            => $request->temp_lower,
                    't16'                   => $request->t16,
                    't17'                   => $request->t17,
                    't18'                   => $request->t18,
                    'pressure_upper'        => $request->pressure_upper,
                    'pressure_lower'        => $request->pressure_lower,
                    'cycle_time'            => $request->cycle_time,
                    'result_description'    => $request->desc,
                    'updated_by'            => $request->updated_by
                ]);

            if ($this->bstbSettingResult == 0) {
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

    public function updateResultProduction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bstb_id'               => 'required|exists:bstb,bstb_id',
            'result_kw1'            => 'required',
            'result_kw2'            => 'required',
            'result_kw3'            => 'required',
            'result_bs_printing'    => 'required',
            'result_bs_plain'       => 'required',
            'work_hour'             => 'required',
            'target_per_hour'       => 'required',
            'desc'                  => 'nullable',
            'updated_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }

        DB::beginTransaction();
        try {
            $this->bstbSettingResult = DB::table("precise.bstb_setting_and_result")
                ->where("bstb_id", $request->bstb_id)
                ->update([
                    "result_kw1"            => $request->result_kw1,
                    "result_kw2"            => $request->result_kw2,
                    "result_kw3"            => $request->result_kw3,
                    "result_bs_printing"    => $request->result_bs_printing,
                    "result_bs_plain"       => $request->result_bs_plain,
                    "work_hour"             => $request->work_hour,
                    "target_per_hour"       => $request->target_per_hour,
                    "result_description"    => $request->desc,
                    "updated_by"            => $request->updated_by
                ]);

            if ($this->bstbSettingResult == 0) {
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

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bstb_setting_and_result_id'    => 'required|exists:bstb_setting_and_result,bstb_setting_and_result_id',
            'reason'                        => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->bstbSettingResult = DB::table('precise.bstb_setting_and_result')
                ->where('bstb_setting_and_result_id', $request->bstb_setting_and_result_id)
                ->delete();

            if ($this->bstbSettingResult == 0) {
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

    public function check(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type'  => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == "number") {
                $this->bstbSettingResult = DB::table('precise.bstb_setting_and_result as bsr')
                    ->leftJoin("precise.bstb as bs", "bsr.bstb_id", "=", "bs.bstb_id")
                    ->where('bstb_num', $value)
                    ->count();
            }
            if ($this->bstbSettingResult == 0)
                return ResponseController::json(status: "error", message: $this->bstbSettingResult, code: 404);

            return ResponseController::json(status: "ok", message: $this->bstbSettingResult, code: 200);
        }
    }
}
