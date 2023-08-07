<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class TechnicalStandardController extends Controller
{
    private $technical;
    public function index($kind): JsonResponse
    {
        $code = explode("-", $kind);
        $this->technical =  DB::table('precise.technical_std_hd as hd')
            ->whereIn('pi.kind_code', $code)
            ->select(
                'technical_std_hd_id',
                'hd.item_id',
                'pi.item_code',
                'pi.item_name',
                'pi.item_alias',
                DB::raw("CONCAT(pi.kind_code, ' - ', pk.product_kind_name) as product_code_and_name"),
                'default_tonnage',
                'int_weight_def',
                'ext_weight_def',
                'int_cycle_time_def',
                'ext_cycle_time_def',
                'int_runner_weight_def',
                'ext_runner_weight_def',
                'int_material_weight_def',
                'ext_material_weight_def',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin('precise.product_item as pi', 'hd.item_id', '=', 'pi.item_id')
            ->leftJoin('precise.product_kind as pk', 'pi.kind_code', '=', 'pk.product_kind_code')
            ->get();
        if (count($this->technical) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->technical, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->technical = DB::table('precise.technical_std_hd as hd')
            ->where('hd.technical_std_hd_id', $id)
            ->select(
                'technical_std_hd_id',
                'hd.item_id',
                'pi.kind_code',
                DB::raw("CONCAT(pi.kind_code, ' - ', pk.product_kind_name) as product_code_and_name"),
                'pi.item_code',
                'pi.item_name',
                'pi.item_alias',
                'default_tonnage',
                'int_weight_def',
                'ext_weight_def',
                'int_cycle_time_def',
                'ext_cycle_time_def',
                'int_runner_weight_def',
                'ext_runner_weight_def',
                'int_material_weight_def',
                'ext_material_weight_def',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin('precise.product_item as pi', 'hd.item_id', '=', 'pi.item_id')
            ->leftJoin('precise.product_kind as pk', 'pi.kind_code', '=', 'pk.product_kind_code')
            ->first();
        if (empty($this->technical)) {
            return response()->json($this->technical, 404);
        }
        return response()->json($this->technical, 200);
    }

    public function detail($kind)
    {
        try {
            $code = explode("-", $kind);
            $this->technical = DB::table("precise.technical_std_hd as hd")
                ->whereIn('pi.kind_code', $code)
                ->select(
                    'hd.technical_std_hd_id',
                    'hd.item_id',
                    'pi.item_code',
                    'pi.item_name',
                    'pi.item_alias',
                    'default_tonnage',
                    'technical_std_code',
                    'dt.process_type_id',
                    'ppt.process_code',
                    'ppt.process_description',
                    'technical_std_description',
                    'dt.mold_hd_id',
                    'mold_number',
                    'mold_name',
                    'dt.machine_id',
                    'machine_code',
                    'machine_name',
                    'int_weight_def',
                    'dt.int_weight_std',
                    'dt.int_weight_min',
                    'dt.int_weight_max',
                    'ext_weight_def',
                    'dt.ext_weight_std',
                    'dt.ext_weight_min',
                    'dt.ext_weight_max',
                    'int_cycle_time_def',
                    'dt.int_cycle_time_std',
                    'dt.int_cycle_time_min',
                    'dt.int_cycle_time_max',
                    'ext_cycle_time_def',
                    'dt.ext_cycle_time_std',
                    'dt.ext_cycle_time_min',
                    'dt.ext_cycle_time_max',
                    'int_runner_weight_def',
                    'dt.int_runner_weight_std',
                    'dt.int_runner_weight_min',
                    'dt.int_runner_weight_max',
                    'ext_runner_weight_def',
                    'dt.ext_runner_weight_std',
                    'dt.ext_runner_weight_min',
                    'dt.ext_runner_weight_max',
                    'int_material_weight_def',
                    'dt.int_material_weight_std',
                    'dt.int_material_weight_min',
                    'dt.int_material_weight_max',
                    'ext_material_weight_def',
                    'dt.ext_material_weight_std',
                    'dt.ext_material_weight_min',
                    'dt.ext_material_weight_max',
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->join('precise.technical_std_dt as dt', 'hd.technical_std_hd_id', '=', 'dt.technical_std_hd_id')
                ->leftJoin('precise.product_item as pi', 'hd.item_id', '=', 'pi.item_id')
                ->leftJoin('precise.production_process_type as ppt', 'dt.process_type_id', '=', 'ppt.process_type_id')
                ->leftJoin('precise.mold_hd as mh', 'dt.mold_hd_id', '=', 'mh.mold_hd_id')
                ->leftJoin('precise.machine as m', 'dt.machine_id', '=', 'm.machine_id')
                ->get();
            if (count($this->technical) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);

            return ResponseController::json(status: "ok", data: $this->technical, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: "server error", code: 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'item_id'                   => 'required|exists:product_item,item_id',
            'default_tonnage'           => 'required|numeric',
            'int_weight_def'            => 'required|numeric',
            'ext_weight_def'            => 'required|numeric',
            'int_cycle_time_def'        => 'required|numeric',
            'ext_cycle_time_def'        => 'required|numeric',
            'int_runner_weight_def'     => 'required|numeric',
            'ext_runner_weight_def'     => 'required|numeric',
            'int_material_weight_def'   => 'required|numeric',
            'ext_material_weight_def'   => 'required|numeric',
            'created_by'                => 'required',
            'detail'                    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            $header = DB::table('precise.technical_std_hd')
                ->insertGetId([
                    'item_id'                   =>  $data['item_id'],
                    'default_tonnage'           =>  $data['default_tonnage'],
                    'int_weight_def'            =>  $data['int_weight_def'],
                    'ext_weight_def'            =>  $data['ext_weight_def'],
                    'int_cycle_time_def'        =>  $data['int_cycle_time_def'],
                    'ext_cycle_time_def'        =>  $data['ext_cycle_time_def'],
                    'int_runner_weight_def'     =>  $data['int_runner_weight_def'],
                    'ext_runner_weight_def'     =>  $data['ext_runner_weight_def'],
                    'int_material_weight_def'   =>  $data['int_material_weight_def'],
                    'ext_material_weight_def'   =>  $data['ext_material_weight_def'],
                    'created_by'                =>  $data['created_by']
                ]);

            if ($header < 1) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            foreach ($data['detail'] as $detail) {
                $validator = Validator::make($detail, [
                    'technical_std_code'        => 'required',
                    'process_type_id'           => 'required',
                    'technical_std_description' => 'required',
                    'mold_hd_id'                => 'required',
                    'machine_id'                => 'required',
                    'manpower_std'              => 'required',
                    'int_weight_std'            => 'required',
                    'int_weight_min'            => 'required',
                    'int_weight_max'            => 'required',
                    'ext_weight_std'            => 'required',
                    'ext_weight_min'            => 'required',
                    'ext_weight_max'            => 'required',
                    'int_cycle_time_std'        => 'required',
                    'int_cycle_time_min'        => 'required',
                    'int_cycle_time_max'        => 'required',
                    'ext_cycle_time_std'        => 'required',
                    'ext_cycle_time_min'        => 'required',
                    'ext_cycle_time_max'        => 'required',
                    'int_runner_weight_std'     => 'required',
                    'int_runner_weight_min'     => 'required',
                    'int_runner_weight_max'     => 'required',
                    'ext_runner_weight_std'     => 'required',
                    'ext_runner_weight_min'     => 'required',
                    'ext_runner_weight_max'     => 'required',
                    'int_material_weight_std'   => 'required',
                    'int_material_weight_min'   => 'required',
                    'int_material_weight_max'   => 'required',
                    'ext_material_weight_std'   => 'required',
                    'ext_material_weight_min'   => 'required',
                    'ext_material_weight_max'   => 'required',
                    'int_lg_weight_std'         => 'required',
                    'int_lg_weight_min'         => 'required',
                    'int_lg_weight_max'         => 'required',
                    'ext_lg_weight_std'         => 'required',
                    'ext_lg_weight_min'         => 'required',
                    'ext_lg_weight_max'         => 'required',
                    'created_by'                => 'required'
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                } else {
                    $values[] = [
                        'technical_std_hd_id'       => $header,
                        'technical_std_code'        => $detail['technical_std_code'],
                        'process_type_id'           => $detail['process_type_id'],
                        'technical_std_description' => $detail['technical_std_description'],
                        'mold_hd_id'                => $detail['mold_hd_id'],
                        'machine_id'                => $detail['machine_id'],
                        'manpower_std'              => $detail['manpower_std'],
                        'int_weight_std'            => $detail['int_weight_std'],
                        'int_weight_min'            => $detail['int_weight_min'],
                        'int_weight_max'            => $detail['int_weight_max'],
                        'ext_weight_std'            => $detail['ext_weight_std'],
                        'ext_weight_min'            => $detail['ext_weight_min'],
                        'ext_weight_max'            => $detail['ext_weight_max'],
                        'int_cycle_time_std'        => $detail['int_cycle_time_std'],
                        'int_cycle_time_min'        => $detail['int_cycle_time_min'],
                        'int_cycle_time_max'        => $detail['int_cycle_time_max'],
                        'ext_cycle_time_std'        => $detail['ext_cycle_time_std'],
                        'ext_cycle_time_min'        => $detail['ext_cycle_time_min'],
                        'ext_cycle_time_max'        => $detail['ext_cycle_time_max'],
                        'int_runner_weight_std'     => $detail['int_runner_weight_std'],
                        'int_runner_weight_min'     => $detail['int_runner_weight_min'],
                        'int_runner_weight_max'     => $detail['int_runner_weight_max'],
                        'ext_runner_weight_std'     => $detail['ext_runner_weight_std'],
                        'ext_runner_weight_min'     => $detail['ext_runner_weight_min'],
                        'ext_runner_weight_max'     => $detail['ext_runner_weight_max'],
                        'int_material_weight_std'   => $detail['int_material_weight_std'],
                        'int_material_weight_min'   => $detail['int_material_weight_min'],
                        'int_material_weight_max'   => $detail['int_material_weight_max'],
                        'ext_material_weight_std'   => $detail['ext_material_weight_std'],
                        'ext_material_weight_min'   => $detail['ext_material_weight_min'],
                        'ext_material_weight_max'   => $detail['ext_material_weight_max'],
                        'int_lg_weight_std'         => $detail['int_lg_weight_std'],
                        'int_lg_weight_min'         => $detail['int_lg_weight_min'],
                        'int_lg_weight_max'         => $detail['int_lg_weight_max'],
                        'ext_lg_weight_std'         => $detail['ext_lg_weight_std'],
                        'ext_lg_weight_min'         => $detail['ext_lg_weight_min'],
                        'ext_lg_weight_max'         => $detail['ext_lg_weight_max'],
                        'created_by'                => $detail['created_by']
                    ];
                }
            }

            $check = DB::table('precise.technical_std_dt')->insert($values);
            if ($check < 1) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $request)
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'technical_std_hd_id'       => 'required|exists:technical_std_hd,technical_std_hd_id',
            'item_id'                   => 'required|exists:product_item,item_id',
            'default_tonnage'           => 'required|numeric',
            'int_weight_def'            => 'required|numeric',
            'ext_weight_def'            => 'required|numeric',
            'int_cycle_time_def'        => 'required|numeric',
            'ext_cycle_time_def'        => 'required|numeric',
            'int_runner_weight_def'     => 'required|numeric',
            'ext_runner_weight_def'     => 'required|numeric',
            'int_material_weight_def'   => 'required|numeric',
            'ext_material_weight_def'   => 'required|numeric',
            'reason'                    => 'required',
            'updated_by'                => 'required',
            'detail'                    => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            DB::beginTransaction();
            DBController::reason($request, "update", $data);
            DB::table('precise.technical_std_hd')
                ->where('technical_std_hd_id', $data['technical_std_hd_id'])
                ->update([
                    'item_id'                   =>  $data['item_id'],
                    'default_tonnage'           =>  $data['default_tonnage'],
                    'int_weight_def'            =>  $data['int_weight_def'],
                    'ext_weight_def'            =>  $data['ext_weight_def'],
                    'int_cycle_time_def'        =>  $data['int_cycle_time_def'],
                    'ext_cycle_time_def'        =>  $data['ext_cycle_time_def'],
                    'int_runner_weight_def'     =>  $data['int_runner_weight_def'],
                    'ext_runner_weight_def'     =>  $data['ext_runner_weight_def'],
                    'int_material_weight_def'   =>  $data['int_material_weight_def'],
                    'ext_material_weight_def'   =>  $data['ext_material_weight_def'],
                    'updated_by'                =>  $data['updated_by']
                ]);

            foreach ($data['inserted'] as $insert) {
                $validator = Validator::make($insert, [
                    'technical_std_code'        => 'required',
                    'process_type_id'           => 'required',
                    'technical_std_description' => 'required',
                    'mold_hd_id'                => 'required',
                    'machine_id'                => 'required',
                    'manpower_std'              => 'required',
                    'int_weight_std'            => 'required',
                    'int_weight_min'            => 'required',
                    'int_weight_max'            => 'required',
                    'ext_weight_std'            => 'required',
                    'ext_weight_min'            => 'required',
                    'ext_weight_max'            => 'required',
                    'int_cycle_time_std'        => 'required',
                    'int_cycle_time_min'        => 'required',
                    'int_cycle_time_max'        => 'required',
                    'ext_cycle_time_std'        => 'required',
                    'ext_cycle_time_min'        => 'required',
                    'ext_cycle_time_max'        => 'required',
                    'int_runner_weight_std'     => 'required',
                    'int_runner_weight_min'     => 'required',
                    'int_runner_weight_max'     => 'required',
                    'ext_runner_weight_std'     => 'required',
                    'ext_runner_weight_min'     => 'required',
                    'ext_runner_weight_max'     => 'required',
                    'int_material_weight_std'   => 'required',
                    'int_material_weight_min'   => 'required',
                    'int_material_weight_max'   => 'required',
                    'ext_material_weight_std'   => 'required',
                    'ext_material_weight_min'   => 'required',
                    'ext_material_weight_max'   => 'required',
                    'int_lg_weight_std'         => 'required',
                    'int_lg_weight_min'         => 'required',
                    'int_lg_weight_max'         => 'required',
                    'ext_lg_weight_std'         => 'required',
                    'ext_lg_weight_min'         => 'required',
                    'ext_lg_weight_max'         => 'required',
                    'created_by'                => 'required'
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                }
                $values[] = [
                    'technical_std_hd_id'       => $insert['technical_std_hd_id'],
                    'technical_std_code'        => $insert['technical_std_code'],
                    'process_type_id'           => $insert['process_type_id'],
                    'technical_std_description' => $insert['technical_std_description'],
                    'mold_hd_id'                => $insert['mold_hd_id'],
                    'machine_id'                => $insert['machine_id'],
                    'manpower_std'              => $insert['manpower_std'],
                    'int_weight_std'            => $insert['int_weight_std'],
                    'int_weight_min'            => $insert['int_weight_min'],
                    'int_weight_max'            => $insert['int_weight_max'],
                    'ext_weight_std'            => $insert['ext_weight_std'],
                    'ext_weight_min'            => $insert['ext_weight_min'],
                    'ext_weight_max'            => $insert['ext_weight_max'],
                    'int_cycle_time_std'        => $insert['int_cycle_time_std'],
                    'int_cycle_time_min'        => $insert['int_cycle_time_min'],
                    'int_cycle_time_max'        => $insert['int_cycle_time_max'],
                    'ext_cycle_time_std'        => $insert['ext_cycle_time_std'],
                    'ext_cycle_time_min'        => $insert['ext_cycle_time_min'],
                    'ext_cycle_time_max'        => $insert['ext_cycle_time_max'],
                    'int_runner_weight_std'     => $insert['int_runner_weight_std'],
                    'int_runner_weight_min'     => $insert['int_runner_weight_min'],
                    'int_runner_weight_max'     => $insert['int_runner_weight_max'],
                    'ext_runner_weight_std'     => $insert['ext_runner_weight_std'],
                    'ext_runner_weight_min'     => $insert['ext_runner_weight_min'],
                    'ext_runner_weight_max'     => $insert['ext_runner_weight_max'],
                    'int_material_weight_std'   => $insert['int_material_weight_std'],
                    'int_material_weight_min'   => $insert['int_material_weight_min'],
                    'int_material_weight_max'   => $insert['int_material_weight_max'],
                    'ext_material_weight_std'   => $insert['ext_material_weight_std'],
                    'ext_material_weight_min'   => $insert['ext_material_weight_min'],
                    'ext_material_weight_max'   => $insert['ext_material_weight_max'],
                    'int_lg_weight_std'         => $insert['int_lg_weight_std'],
                    'int_lg_weight_min'         => $insert['int_lg_weight_min'],
                    'int_lg_weight_max'         => $insert['int_lg_weight_max'],
                    'ext_lg_weight_std'         => $insert['ext_lg_weight_std'],
                    'ext_lg_weight_min'         => $insert['ext_lg_weight_min'],
                    'ext_lg_weight_max'         => $insert['ext_lg_weight_max'],
                    'created_by'                => $insert['created_by']
                ];
            }

            $check = DB::table('precise.technical_std_dt')->insert($values);
            if ($check < 1) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "server error", code: 500);
            }

            if ($data['updated'] != null) {
                foreach ($data['updated'] as $update) {
                    $validator = Validator::make($update, [
                        'technical_std_dt_id'       => 'required|exists:technical_std_dt,technical_std_dt_id',
                        'technical_std_hd_id'       => 'required|exists:technical_std_hd,technical_std_hd_id',
                        'technical_std_code'        => 'required',
                        'process_type_id'           => 'required|exists:production_process_type,process_type_id',
                        'technical_std_description' => 'required',
                        'mold_hd_id'                => 'required|exists:mold_hd,mold_hd_id',
                        'machine_id'                => 'required|exists:machine,machine_id',
                        'manpower_std'              => 'required|numeric',
                        'int_weight_std'            => 'required|numeric',
                        'int_weight_min'            => 'required|numeric',
                        'int_weight_max'            => 'required|numeric',
                        'ext_weight_std'            => 'required|numeric',
                        'ext_weight_min'            => 'required|numeric',
                        'ext_weight_max'            => 'required|numeric',
                        'int_cycle_time_std'        => 'required|numeric',
                        'int_cycle_time_min'        => 'required|numeric',
                        'int_cycle_time_max'        => 'required|numeric',
                        'ext_cycle_time_std'        => 'required|numeric',
                        'ext_cycle_time_min'        => 'required|numeric',
                        'ext_cycle_time_max'        => 'required|numeric',
                        'int_runner_weight_std'     => 'required|numeric',
                        'int_runner_weight_min'     => 'required|numeric',
                        'int_runner_weight_max'     => 'required|numeric',
                        'ext_runner_weight_std'     => 'required|numeric',
                        'ext_runner_weight_min'     => 'required|numeric',
                        'ext_runner_weight_max'     => 'required|numeric',
                        'int_material_weight_std'   => 'required|numeric',
                        'int_material_weight_min'   => 'required|numeric',
                        'int_material_weight_max'   => 'required|numeric',
                        'ext_material_weight_std'   => 'required|numeric',
                        'ext_material_weight_min'   => 'required|numeric',
                        'ext_material_weight_max'   => 'required|numeric',
                        'int_lg_weight_std'         => 'required|numeric',
                        'int_lg_weight_min'         => 'required|numeric',
                        'int_lg_weight_max'         => 'required|numeric',
                        'ext_lg_weight_std'         => 'required|numeric',
                        'ext_lg_weight_min'         => 'required|numeric',
                        'ext_lg_weight_max'         => 'required|numeric',
                        'updated_by'                => 'required'
                    ]);

                    if ($validator->fails()) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                    }
                    $check = DB::table('precise.technical_std_dt')
                        ->where('technical_std_dt_id', $update['technical_std_dt_id'])
                        ->update([
                            'technical_std_hd_id'       => $update['technical_std_hd_id'],
                            'technical_std_code'        => $update['technical_std_code'],
                            'process_type_id'           => $update['process_type_id'],
                            'technical_std_description' => $update['technical_std_description'],
                            'mold_hd_id'                => $update['mold_hd_id'],
                            'machine_id'                => $update['machine_id'],
                            'manpower_std'              => $update['manpower_std'],
                            'int_weight_std'            => $update['int_weight_std'],
                            'int_weight_min'            => $update['int_weight_min'],
                            'int_weight_max'            => $update['int_weight_max'],
                            'ext_weight_std'            => $update['ext_weight_std'],
                            'ext_weight_min'            => $update['ext_weight_min'],
                            'ext_weight_max'            => $update['ext_weight_max'],
                            'int_cycle_time_std'        => $update['int_cycle_time_std'],
                            'int_cycle_time_min'        => $update['int_cycle_time_min'],
                            'int_cycle_time_max'        => $update['int_cycle_time_max'],
                            'ext_cycle_time_std'        => $update['ext_cycle_time_std'],
                            'ext_cycle_time_min'        => $update['ext_cycle_time_min'],
                            'ext_cycle_time_max'        => $update['ext_cycle_time_max'],
                            'int_runner_weight_std'     => $update['int_runner_weight_std'],
                            'int_runner_weight_min'     => $update['int_runner_weight_min'],
                            'int_runner_weight_max'     => $update['int_runner_weight_max'],
                            'ext_runner_weight_std'     => $update['ext_runner_weight_std'],
                            'ext_runner_weight_min'     => $update['ext_runner_weight_min'],
                            'ext_runner_weight_max'     => $update['ext_runner_weight_max'],
                            'int_material_weight_std'   => $update['int_material_weight_std'],
                            'int_material_weight_min'   => $update['int_material_weight_min'],
                            'int_material_weight_max'   => $update['int_material_weight_max'],
                            'ext_material_weight_std'   => $update['ext_material_weight_std'],
                            'ext_material_weight_min'   => $update['ext_material_weight_min'],
                            'ext_material_weight_max'   => $update['ext_material_weight_max'],
                            'int_lg_weight_std'         => $update['int_lg_weight_std'],
                            'int_lg_weight_min'         => $update['int_lg_weight_min'],
                            'int_lg_weight_max'         => $update['int_lg_weight_max'],
                            'ext_lg_weight_std'         => $update['ext_lg_weight_std'],
                            'ext_lg_weight_min'         => $update['ext_lg_weight_min'],
                            'ext_lg_weight_max'         => $update['ext_lg_weight_max'],
                            'updated_by'                => $update['updated_by']
                        ]);

                    if ($check < 1) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: "server error", code: 500);
                    }
                }
            }

            if ($data['deleted'] != null) {
                foreach ($data['deleted'] as $delete) {
                    $values[] = $delete['technical_std_dt_id'];
                }

                $check = DB::table('precise.technical_std_dt')
                    ->whereIn('technical_std_dt_id', $values)
                    ->delete();

                if (!$check) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: "server error", code: 500);
                }
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success update data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function check(Request $request)
    {
        $type = $request->get('type');
        $value = $request->get('value');
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if ($type == "code")
                $this->technical = DB::table('precise.technical_std_dt')
                    ->where('technical_std_code', $value)
                    ->count();
            else if ($type == "item")
                $this->technical = DB::table('precise.technical_std_hd')
                    ->where('item_id', $value)
                    ->count();
            if ($this->technical == 0)
                return ResponseController::json(status: "error", message: $this->technical, code: 404);

            return ResponseController::json(status: "ok", message: $this->technical, code: 200);
        }
    }
}
