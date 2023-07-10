<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use App\Http\Controllers\Api\Helpers\ResponseController;
use Symfony\Component\HttpFoundation\JsonResponse;

class MoldInjectionController extends Controller
{
    private $mold;

    public function index(): JsonResponse
    {
        $this->mold = DB::table("precise.mold_injection_hd as hd")
            ->select(
                'hd.mold_injection_hd_id',
                'hd.mold_number',
                'dt.mold_group',
                'dt.item_code',
                'hd.mold_name',
                'hd.is_family_mold',
                'hd.customer_id',
                'cs.customer_code',
                'cs.customer_name',
                'hd.status_code',
                'ms.status_description',
                'hd.remake_from',
                'hd.production_date',
                'hd.tonnage_std',
                'hd.tonnage_min',
                'hd.tonnage_max',
                'hd.steel_type_id',
                'st.steel_type_name',
                'mm.estimation_number',
                'hd.mold_maker',
                'hd.cooling_method_id',
                'cm.cooling_method_name',
                'hd.mold_description',
                'hd.length',
                'hd.width',
                'hd.height',
                'hd.dimension_uom',
                'hd.plate_size_length',
                'hd.plate_size_width',
                'hd.plate_size_uom',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin("precise.mold_injection_dt as dt", "hd.mold_injection_hd_id", "=", "dt.mold_injection_hd_id")
            ->leftJoin("precise.customer as cs", "hd.customer_id", "=", "cs.customer_id")
            ->leftJoin("precise.mold_status as ms", "hd.status_code", "=", "ms.status_code")
            ->leftJoin("precise.steel_type as st", "hd.steel_type_id", "=", "st.steel_type_id")
            ->leftJoin("precise.mold_making as mm", "hd.mold_making_id", "=", "mm.mold_making_id")
            ->leftJoin("precise.cooling_method as cm", "hd.cooling_method_id", "=", "cm.cooling_method_id")
            ->get();

        if (count($this->mold) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->mold, code: 200);
    }

    public function detail(): JsonResponse
    {
        $this->mold = DB::table("precise.mold_injection_hd as hd")
            ->select(
                'hd.mold_injection_hd_id',
                'dt.mold_injection_dt_id',
                'cv.mold_injection_cavity_id',
                'hd.mold_number',
                'dt.mold_group',
                'dt.item_code',
                'dt.item_description',
                'hd.mold_name',
                'hd.is_family_mold',
                'hd.customer_id',
                'cs.customer_code',
                'cs.customer_name',
                'cv.cavity_number',
                'cv.product_weight',
                'cv.product_weight_uom',
                'cv.is_active',
                'hd.status_code',
                'ms.status_description',
                'hd.remake_from',
                'hd.production_date',
                'hd.tonnage_std',
                'hd.tonnage_min',
                'hd.tonnage_max',
                'hd.steel_type_id',
                'st.steel_type_name',
                'hd.mold_making_id',
                'mm.estimation_number',
                'hd.mold_maker',
                'hd.cooling_method_id',
                'cm.cooling_method_name',
                'hd.mold_description',
                'hd.length',
                'hd.width',
                'hd.height',
                'hd.dimension_uom',
                'hd.plate_size_length',
                'hd.plate_size_width',
                'hd.plate_size_uom',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin("precise.mold_injection_dt as dt", "hd.mold_injection_hd_id", "=", "dt.mold_injection_hd_id")
            ->leftJoin("precise.mold_injection_cavity as cv", "dt.mold_injection_dt_id", "=", "cv.mold_injection_dt_id")
            ->leftJoin("precise.customer as cs", "hd.customer_id", "=", "cs.customer_id")
            ->leftJoin("precise.mold_status as ms", "hd.status_code", "=", "ms.status_code")
            ->leftJoin("precise.steel_type as st", "hd.steel_type_id", "=", "st.steel_type_id")
            ->leftJoin("precise.mold_making as mm", "hd.mold_making_id", "=", "mm.mold_making_id")
            ->leftJoin("precise.cooling_method as cm", "hd.cooling_method_id", "=", "cm.cooling_method_id")
            ->get();

        if (count($this->mold) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->mold, code: 200);
    }

    public function showByNumber($number): JsonResponse
    {
        $this->mold = DB::table('precise.mold_injection_hd as hd')
            ->select(
                'hd.mold_injection_hd_id',
                'hd.mold_number',
                'dt.mold_group',
                'dt.item_code',
                'hd.mold_name',
                'hd.is_family_mold',
                'hd.customer_id',
                'cs.customer_code',
                'cs.customer_name',
                'dt.mold_injection_dt_id',
                'hd.tonnage_std',
                'hd.production_date',
                'hd.mold_maker',
                'hd.status_code',
                'ms.status_description',
                'hd.mold_description',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin('precise.mold_injection_dt as dt', 'hd.mold_injection_hd_id', '=', 'dt.mold_injection_hd_id')
            ->leftJoin('precise.customer as cs', 'hd.customer_id', '=', 'cs.customer_id')
            ->leftJoin('precise.mold_status as ms', 'hd.status_code', '=', 'ms.status_code')
            ->where('hd.mold_number', $number)
            ->get();
        if (count($this->mold) == 0)
            return ResponseController::json(status: "error", data: "not found", code: 404);
        return ResponseController::json(status: "ok", data: $this->mold, code: 200);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'mold_number'       => 'required|unique:mold_hd,mold_number',
            'mold_name'         => 'required|unique:mold_hd,mold_name',
            'is_family_mold'    => 'required|boolean',
            'status_code'       => 'required|exists:mold_status,status_code',
            'customer_id'       => 'nullable|exists:customer,customer_id',
            'remake_from'       => 'nullable|exists:mold_hd,mold_hd_id',
            'production_date'   => 'nullable|date_format:Y-m-d',
            'tonnage_std'       => 'nullable|numeric',
            'tonnage_min'       => 'nullable|numeric',
            'tonnage_max'       => 'nullable|numeric',
            'steel_type_id'     => 'nullable|exists:steel_type,steel_type_id',
            'mold_making_id'    => 'nullable|exists:mold_making,mlod_making_id',
            'mold_maker'        => 'nullable',
            'cooling_method_id' => 'nullable|exists:cooling_method,cooling_method_id',
            'length'            => 'nullable|numeric',
            'width'             => 'nullable|numeric',
            'height'            => 'nullable|numeric',
            'dimension_uom'     => 'nullable|exists:uom,uom_code',
            'plate_size_length' => 'nullable|numeric',
            'plate_size_width'  => 'nullable|numeric',
            'plate_size_uom'    => 'nullable|exists:uom,uom_code',
            'created_by'        => 'required',
            'detail'            => 'required'
        ]);
        if ($validator->fails()) {
            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
        }
        try {
            DB::beginTransaction();
            $id_hd = DB::table('precise.mold_injection_hd')
                ->insertGetId([
                    'mold_number'       => $data['mold_number'],
                    'mold_name'         => $data['mold_name'],
                    'is_family_mold'    => $data['is_family_mold'],
                    'status_code'       => $data['status_code'],
                    'customer_id'       => $data['customer_id'],
                    'remake_from'       => $data['remake_from'],
                    'production_date'   => $data['production_date'],
                    'tonnage_std'       => $data['tonnage_std'],
                    'tonnage_min'       => $data['tonnage_min'],
                    'tonnage_max'       => $data['tonnage_max'],
                    'steel_type_id'     => $data['steel_type_id'],
                    'mold_making_id'    => $data['mold_making_id'],
                    'mold_maker'        => $data['mold_maker'],
                    'cooling_method_id' => $data['cooling_method_id'],
                    'mold_description'  => $data['desc'],
                    'length'            => $data['length'],
                    'width'             => $data['width'],
                    'height'            => $data['height'],
                    'dimension_uom'     => $data['dimension_uom'],
                    'plate_size_length' => $data['plate_size_length'],
                    'plate_size_width'  => $data['plate_size_width'],
                    'plate_size_uom'    => $data['plate_size_uom'],
                    'created_by'        => $data['created_by']
                ]);

            foreach ($data['detail'] as $d) {
                $validator = Validator::make($d, [
                    'mold_group'        => 'required',
                    'item_code'         => 'required|exists:product_item,product_code',
                    'created_by'        => 'required',
                    'desc'              => 'nullable',
                    'cavity'            => 'required'
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                } else {
                    $id_dt = DB::table('precise.mold_injection_dt')
                        ->insertGetId([
                            'mold_injection_hd_id'      => $id_hd,
                            'mold_group'                => $d['mold_group'],
                            'item_code'                 => $d['item_code'],
                            'item_description'          => $d['desc'],
                            'created_by'                => $d['created_by']
                        ]);
                    foreach ($d['cavity'] as $dc) {
                        $validator = Validator::make($dc, [
                            'cavity_number'         => 'required',
                            'product_weight'        => 'required|numeric',
                            'product_weight_uom'    => 'required|exists:uom,uom_code',
                            'is_active'             => 'required|boolean',
                            'created_by'            => 'required'
                        ]);

                        if ($validator->fails()) {
                            DB::rollBack();
                            return ResponseController::json(status: "error", message: $validator->errors(), code: 400);
                        } else {
                            $detail = [
                                'mold_injection_dt_id'          => $id_dt,
                                'cavity_number'                 => $dc['cavity_number'],
                                'product_weight'                => $dc['product_weight'],
                                'product_weight_uom'            => $dc['product_weight_uom'],
                                'is_active'                     => $dc['is_active'],
                                'created_by'                    => $dc['created_by']
                            ];

                            DB::table('precise.mold_injection_cavity')
                                ->insert($detail);
                        }
                    }
                }
            }

            $trans = DB::table('precise.mold_injection_hd')
                ->where('mold_injection_hd_id', $id_hd)
                ->value("mold_number");

            if (empty($trans)) {
                DB::rollBack();
                return ResponseController::json(status: "error", message: "failed input data",  code: 500);
            }
            DB::commit();
            return ResponseController::json(status: "ok", message: "success input data", id: $trans, code: 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseController::json(status: "error", message: $e->getMessage(), code: 500);
        }
    }
}
