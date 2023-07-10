<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class PackagingController extends Controller
{
    private $packaging;

    public function index(Request $request): JsonResponse
    {
        try {
            $status = $request->get('status');
            $this->packaging = DB::table('precise.packaging_hd as hd')
                ->select(
                    'hd.packaging_id',
                    'phd.product_code as packaging_code',
                    'phd.product_name as packaging_name',
                    'packaging_alias',
                    'length',
                    'width',
                    'height',
                    'dimension_uom_code',
                    'weight',
                    'weight_uom_code',
                    DB::raw("case hd.is_active 
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif'
                end as is_active"),
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->leftJoin('precise.product as phd', 'hd.packaging_id', '=', 'phd.product_id');
            if ($status == 1)
                $this->packaging = $this->packaging->where('hd.is_active', $status)->get();
            else
                $this->packaging = $this->packaging->get();

            if (count($this->packaging) == 0)
                return ResponseController::json(status: "error", data: "not found", code: 404);
            return ResponseController::json(status: "ok", data: $this->packaging, code: 200);
        } catch (\Exception $e) {
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function show($id): JsonResponse
    {
        $this->packaging = DB::table('precise.packaging_hd as hd')
            ->where('hd.packaging_id', $id)
            ->select(
                'hd.packaging_id',
                'phd.product_code as packaging_code',
                'phd.product_name as packaging_name',
                'length',
                'width',
                'height',
                'dimension_uom_code',
                'weight',
                'weight_uom_code',
                'hd.is_active',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->join('precise.`packaging_dt` as dt', 'hd.packaging_id', '=', 'dt.packaging_id')
            ->leftJoin('precise.product as phd', 'hd.packaging_id', '=', 'phd.product_id')
            ->first();

        if (empty($this->packaging)) {
            return response()->json("not found", 404);
        }
        return response()->json($this->packaging, 200);
    }

    public function detail(): JsonResponse
    {
        $this->packaging = DB::table('precise.packaging_hd as hd')
            ->select(
                'hd.packaging_id',
                'phd.product_code as packaging_code',
                'phd.product_name as packaging_name',
                'length',
                'width',
                'height',
                'dimension_uom_code',
                'weight',
                'weight_uom_code',
                DB::raw("case hd.is_active 
                    when 0 then 'Tidak aktif'
                    when 1 then 'Aktif' 
                end as is_active"),
                'dt.product_id',
                'pdt.product_code',
                'pdt.product_name',
                'dt.product_qty',
                'dt.created_on',
                'dt.created_by',
                'dt.updated_on',
                'dt.updated_by'
            )
            ->leftJoin('precise.packaging_dt as dt', 'hd.packaging_id', '=', 'dt.packaging_id')
            ->leftJoin('precise.product as phd', 'hd.packaging_id', '=', 'phd.product_id')
            ->leftJoin('precise.product as pdt', 'dt.packaging_id', '=', 'pdt.product_id')
            ->get();

        if (count($this->packaging) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->packaging, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make(
            $data,
            [
                'packaging_id'          => 'required|exists:product,product_id',
                'length'                => 'required',
                'width'                 => 'required',
                'height'                => 'required',
                'dimension_uom_code'    => 'required|exists:uom,uom_code',
                'weight'                => 'required',
                'weight_uom_code'       => 'required|exists:uom,uom_code',
                'is_active'             => 'nullable',
                'created_by'            => 'required'
            ]
        );
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DB::table('precise.packaging_hd')
                ->insertGetId([
                    'packaging_id'      => $data['packaging_id'],
                    'length'            => $data['length'],
                    'width'             => $data['width'],
                    'height'            => $data['height'],
                    'dimension_uom_code' => $data['dimension_uom_code'],
                    'weight'            => $data['weight'],
                    'weight_uom_code'   => $data['weight_uom_code'],
                    'created_by'        => $data['created_by']
                ]);

            foreach ($data['detail'] as $detail) {
                $validator = Validator::make(
                    $detail,
                    [
                        'product_id'    => 'required|exists:product,product_id',
                        'product_qty'   => 'required|numeric',
                        'created_by'    => 'required'
                    ]
                );
                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                } else {
                    $values[] = [
                        'packaging_id'  => $data['packaging_id'],
                        'product_id'    => $detail['product_id'],
                        'product_qty'   => $detail['product_qty'],
                        'created_by'    => $detail['created_by']
                    ];
                }
            }

            $check = DB::table('precise.packaging_dt')
                ->insert($values);

            if ($check < 1) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "failed input data", code: 500);
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success input data", code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }

    public function update(Request $request)
    {
        $data = $request->json()->all();
        $validator = Validator::make(
            $data,
            [
                'packaging_id'      => 'required|exists:packaging_hd,packaging_id',
                'length'            => 'required',
                'width'             => 'required',
                'height'            => 'required',
                'dimension_uom_code' => 'required|exists:uom,uom_code',
                'weight'            => 'required',
                'weight_uom_code'   => 'required|exists:uom,uom_code',
                'is_active'         => 'required',
                'reason'            => 'required',
                'updated_by'        => 'required'
            ]
        );
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            DB::beginTransaction();
            DBController::reason($request, "update", $data);

            $check = DB::table('precise.packaging_hd')
                ->where('packaging_id', $data['packaging_id'])
                ->update([
                    'length'            => $data['length'],
                    'width'             => $data['width'],
                    'height'            => $data['height'],
                    'dimension_uom_code' => $data['dimension_uom_code'],
                    'weight'            => $data['weight'],
                    'weight_uom_code'   => $data['weight_uom_code'],
                    'is_active'         => $data['is_active'],
                    'updated_by'        => $data['updated_by']
                ]);

            if ($data['inserted'] != null) {
                foreach ($data['inserted'] as $insert) {
                    $validator = Validator::make(
                        $insert,
                        [
                            'product_id'    => 'required|exists:product,product_id',
                            'product_qty'   => 'required|numeric',
                            'created_by'    => 'required'
                        ]
                    );
                    if ($validator->fails()) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                    }

                    $i[] = [
                        'packaging_id'  => $data['packaging_id'],
                        'product_id'    => $insert['product_id'],
                        'product_qty'   => $insert['product_qty'],
                        'created_by'    => $insert['created_by']
                    ];
                }

                $check = DB::table('precise.packaging_dt')
                    ->insert($i);

                if ($check < 1) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: "failed update data",  code: 500);
                }
            }

            if ($data['updated'] != null) {
                foreach ($data['updated'] as $update) {
                    $validator = Validator::make(
                        $update,
                        [
                            'packaging_dt_id'   => 'required|exists:packaging_dt,packaging_dt_id',
                            'product_id'        => 'required|exists:product,product_id',
                            'product_qty'       => 'required|numeric',
                            'updated_by'        => 'required'
                        ]
                    );
                    if ($validator->fails()) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                    }
                    $check = DB::table('precise.packaging_dt')
                        ->where('packaging_dt_id', $update['packaging_dt_id'])
                        ->update([
                            'packaging_id'  => $update['packaging_id'],
                            'product_id'    => $update['product_id'],
                            'product_qty'   => $update['product_qty'],
                            'updated_by'    => $update['updated_by']
                        ]);

                    if ($check < 1) {
                        DB::rollBack();
                        return ResponseController::json(status: "error", message: "failed update data",  code: 500);
                    }
                }
            }

            if ($data['deleted'] != null) {
                foreach ($data['deleted'] as $delete) {
                    $values[] = $delete['packaging_dt_id'];
                }

                $check = DB::table('precise.packaging_dt')
                    ->whereIn('packaging_dt_id', $values)
                    ->delete();

                if ($check < 1) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: "failed update data",  code: 500);
                }
            }

            DB::commit();
            return ResponseController::json(status: "ok", message: "success update data",  code: 200);
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
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        } else {
            if ($type == 'id') {
                $this->packaging = DB::table('precise.packaging_hd')->where('packaging_id', $value)->count();
            }
            if ($this->packaging == 0)
                return ResponseController::json(status: "error", message: $this->packaging, code: 404);

            return ResponseController::json(status: "ok", message: $this->packaging, code: 200);
        }
    }
}
