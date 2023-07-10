<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductDesignController extends Controller
{
    private $productDesign;
    public function index(): JsonResponse
    {
        $this->productDesign = DB::table('precise.product_design as a')
            ->select(
                'design_id',
                'design_code',
                'design_name',
                'design_description',
                'a.appearance_id',
                'appearance_name',
                'a.license_type_id',
                'license_type_name',
                'a.color_type_id',
                DB::raw("concat(d.color_type_code, ' - ', d.color_type_name) as 'color_type',
                    case a.is_active_sell
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'is_active_sell'
                    , case a.is_active_production
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'is_active_production'
		            , case a.is_active
                        when 0 then 'Tidak aktif'
                        when 1 then 'Aktif' 
                    end as 'is_active'"),
                'a.created_on',
                'a.created_by',
                'a.updated_on',
                'a.updated_by'
            )->leftJoin('precise.product_appearance as b', 'a.appearance_id', '=', 'b.appearance_id')
            ->leftJoin('precise.product_license_type as c', 'a.license_type_id', '=', 'c.license_type_id')
            ->leftJoin('precise.color_type as d', 'a.color_type_id', '=', 'd.color_type_id')
            ->get();
        if (count($this->productDesign) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);

        return ResponseController::json(status: "ok", data: $this->productDesign, code: 200);
    }

    public function show($id): JsonResponse
    {
        $this->productDesign = DB::table('precise.product_design as a')
            ->where('design_id', $id)
            ->select(
                'design_id',
                'design_code',
                'design_name',
                'design_description',
                'appearance_id',
                'license_type_id',
                'color_type_id',
                'is_active_sell',
                'is_active_production',
                'is_active'
            )
            ->first();
        if (empty($this->productDesign))
            return response()->json("not found", 404);

        return response()->json($this->productDesign, 200);
    }

    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'design_code'       => 'required|unique:product_design,design_code',
            'appearance_id'     => 'required|exists:product_appearance,appearance_id',
            'license_type_id'   => 'required|exists:product_license_type,license_type_id',
            'color_type_id'     => 'required|exists:color_type,color_type_id',
            'desc'              => 'nullable',
            'created_by'        => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        $this->productDesign = DB::table('precise.product_design')
            ->insert([
                'design_code'           => $request->design_code,
                'design_name'           => $request->design_name,
                'design_description'    => $request->desc,
                'appearance_id'         => $request->appearance_id,
                'license_type_id'       => $request->license_type_id,
                'color_type_id'         => $request->color_type_id,
                'created_by'            => $request->created_by
            ]);

        if ($this->productDesign == 0)
            return ResponseController::json(status: "error", message: "error input data", code: 500);

        return ResponseController::json(status: "ok", message: "success input data", code: 200);
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'design_id'             => 'required|exists:product_design,design_id',
            'design_code'           => 'required',
            'appearance_id'         => 'required|exists:product_appearance,appearance_id',
            'license_type_id'       => 'required|exists:product_license_type,license_type_id',
            'color_type_id'         => 'required|exists:color_type,color_type_id',
            'desc'                  => 'nullable',
            'is_active_sell'        => 'required|boolean',
            'is_active_production'  => 'required|boolean',
            'is_active'             => 'required|boolean',
            'updated_by'            => 'required',
            'reason'                => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update");
            $this->productDesign = DB::table('precise.product_design')
                ->where('design_id', $request->design_id)
                ->update([
                    'design_code'           => $request->design_code,
                    'design_name'           => $request->design_name,
                    'design_description'    => $request->desc,
                    'appearance_id'         => $request->appearance_id,
                    'license_type_id'       => $request->license_type_id,
                    'color_type_id'         => $request->color_type_id,
                    'is_active_sell'        => $request->is_active_sell,
                    'is_active_production'  => $request->is_active_production,
                    'is_active'             => $request->is_active,
                    'updated_by'            => $request->updated_by
                ]);

            if ($this->productDesign == 0) {
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
            'product_design_id' => 'required|exists:product_design,design_id',
            'deleted_by'        => 'required',
            'reason'            => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");
            $this->productDesign = DB::table('precise.product_design')
                ->where('design_id', $request->product_design_id)
                ->delete();

            if ($this->productDesign == 0) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "failed delete data", code: 500);
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
            if ($type == "code") {
                $this->productDesign = DB::table('precise.product_design')
                    ->where([
                        'design_code' => $value
                    ])->count();
            } else if ($type == "name") {
                $this->productDesign = DB::table('precise.product_design')
                    ->where([
                        'design_name' => $value
                    ])->count();
            }
            if ($this->productDesign == 0)
                return ResponseController::json(status: "error", message: $this->productDesign, code: 404);

            return ResponseController::json(status: "ok", message: $this->productDesign, code: 200);
        }
    }
}
