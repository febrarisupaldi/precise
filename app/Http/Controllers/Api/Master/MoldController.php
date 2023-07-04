<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\QueryController;
use Symfony\Component\HttpFoundation\JsonResponse;

class MoldController extends Controller
{
    private $mold;
    public function index($id): JsonResponse
    {
        try {
            $workcenterIDs = explode('-', $id);

            $this->mold = DB::table("precise.mold_hd as hd")
                ->whereIn('hd.workcenter_id', $workcenterIDs)
                ->select(
                    'hd.mold_hd_id',
                    'hd.mold_number',
                    'hd.mold_name',
                    'hd.workcenter_id',
                    'workcenter_code',
                    'workcenter_name',
                    'hd.is_family_mold',
                    'hd.customer_id',
                    'customer_code',
                    'customer_name',
                    'hd.status_code',
                    'status_description',
                    'hd.remake_from',
                    'hd2.mold_number as remake_from_number',
                    'hd2.mold_name as remake_from_name',
                    'hd.production_year',
                    'hd.tonnage_std',
                    'hd.tonnage_min',
                    'hd.tonnage_max',
                    'hd.steel_type_id',
                    'st.steel_type_name',
                    'hd.estimation_number',
                    'hd.mold_maker',
                    'hd.cooling_method_id',
                    'cm.cooling_method_name',
                    'hd.mold_description',
                    'hd.length',
                    'hd.width',
                    'hd.height',
                    'hd.dimension_uom',
                    'hd.weight',
                    'hd.weight_uom',
                    'hd.plate_size_length',
                    'hd.plate_size_width',
                    'hd.plate_size_uom',
                    'hd.is_active',
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->leftJoin('precise.workcenter as w', 'hd.workcenter_id', '=', 'w.workcenter_id')
                ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
                ->leftJoin('precise.mold_status as ms', 'hd.status_code', '=', 'ms.status_code')
                ->leftJoin('precise.mold_hd as hd2', 'hd.remake_from', '=', 'hd2.mold_hd_id')
                ->leftJoin('precise.steel_type as st', 'hd.steel_type_id', '=', 'st.steel_type_id')
                ->leftJoin('precise.cooling_method as cm', 'hd.cooling_method_id', '=', 'cm.cooling_method_id')
                ->get();

            return response()->json(["status" => "ok", "data" => $this->mold], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $all = array();
        $cavity = array();

        $master = DB::table("precise.mold_hd as hd")
            ->where('hd.mold_hd_id', $id)
            ->select(
                'hd.mold_hd_id',
                'hd.mold_number',
                'hd.mold_name',
                'hd.workcenter_id',
                'workcenter_code',
                'workcenter_name',
                'hd.is_family_mold',
                'hd.customer_id',
                'customer_code',
                'customer_name',
                'hd.status_code',
                'status_description',
                'hd.remake_from',
                'hd.mold_description',
                'hd2.mold_number as old_mold_number',
                'hd2.mold_name as old_mold_name',
                'hd.length',
                'hd.width',
                'hd.height',
                'hd.dimension_uom',
                'hd.weight',
                'hd.weight_uom',
                'hd.plate_size_length',
                'hd.plate_size_width',
                'hd.plate_size_uom',
                'hd.tonnage_std',
                'hd.tonnage_min',
                'hd.tonnage_max',
                'hd.production_year',
                'hd.steel_type_id',
                'hd.estimation_number',
                'hd.mold_maker',
                'hd.cooling_method_id',
                'hd.is_active'
            )
            ->leftJoin('precise.workcenter as w', 'hd.workcenter_id', '=', 'w.workcenter_id')
            ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
            ->leftJoin('precise.mold_status as ms', 'hd.status_code', '=', 'ms.status_code')
            ->leftJoin('precise.mold_hd as hd2', 'hd.remake_from', '=', 'hd2.mold_hd_id')
            ->first();

        if (empty($this->mold)) {
            return response()->json($this->mold, 404);
        }

        $detail = DB::table("precise.mold_dt as dt")
            ->where('mold_hd_id', $id)
            ->select(
                'mold_dt_id',
                'mold_hd_id',
                'dt.item_id',
                'item_code',
                'item_name'
            )
            ->leftJoin("precise.product_item as pi", "dt.item_id", "=", "pi.item_id")
            ->get();

        foreach ($detail as $details) {
            $cavity = DB::table('precise.mold_cavity as mc')
                ->where('mc.mold_dt_id', $details->mold_dt_id)
                ->select(
                    'mold_cavity_id',
                    'mc.mold_dt_id',
                    'cavity_number',
                    'product_weight',
                    'product_weight_uom',
                    'is_active'
                )
                ->leftJoin('precise.mold_dt as md', 'mc.mold_dt_id', '=', 'md.mold_dt_id')
                ->get();

            $all[] = array(
                "mold_dt_id"        => $details->mold_dt_id,
                "mold_hd_id"        => $details->mold_hd_id,
                "item_id"           => $details->item_id,
                "item_code"         => $details->item_code,
                "item_name"         => $details->item_name,
                "cavity_detail"     => $cavity
            );
        }

        $this->mold = array(
            'mold_hd_id'            => $master->mold_hd_id,
            'mold_number'           => $master->mold_number,
            'mold_name'             => $master->mold_name,
            'workcenter_id'         => $master->workcenter_id,
            'workcenter_code'       => $master->workcenter_code,
            'workcenter_name'       => $master->workcenter_name,
            'is_family_mold'        => $master->is_family_mold,
            'customer_id'           => $master->customer_id,
            'customer_code'         => $master->customer_code,
            'customer_name'         => $master->customer_name,
            'status_code'           => $master->status_code,
            'status_description'    => $master->status_description,
            'remake_from'           => $master->remake_from,
            'mold_description'      => $master->mold_description,
            'old_mold_number'       => $master->old_mold_number,
            'old_mold_name'         => $master->old_mold_name,
            'length'                => $master->length,
            'width'                 => $master->width,
            'height'                => $master->height,
            'dimension_uom'         => $master->dimension_uom,
            'weight'                => $master->weight,
            'weight_uom'            => $master->weight_uom,
            'plate_size_length'     => $master->plate_size_length,
            'plate_size_width'      => $master->plate_size_width,
            'plate_size_uom'        => $master->plate_size_uom,
            'tonnage_std'           => $master->tonnage_std,
            'tonnage_min'           => $master->tonnage_min,
            'tonnage_max'           => $master->tonnage_max,
            'production_year'       => $master->production_year,
            'steel_type_id'         => $master->steel_type_id,
            'estimation_number'     => $master->estimation_number,
            'mold_maker'            => $master->mold_maker,
            'cooling_method_id'     => $master->cooling_method_id,
            'is_active'             => $master->is_active,
            'detail'                => $all
        );
        return response()->json($this->mold, 200);
    }

    public function joined($id): JsonResponse
    {
        try {
            $workcenter = explode("-", $id);
            $this->mold = DB::table('precise.mold_hd as hd')
                ->whereIn('hd.workcenter_id', $workcenter)
                ->select(
                    'hd.mold_hd_id',
                    'hd.mold_number',
                    'hd.mold_name',
                    'hd.workcenter_id',
                    'workcenter_code',
                    'workcenter_name',
                    'hd.is_family_mold',
                    'hd.customer_id',
                    'customer_code',
                    'customer_name',
                    'hd.status_code',
                    'status_description',
                    'hd.remake_from',
                    'hd2.mold_number as old_mold_number',
                    'hd2.mold_name as old_mold_name',
                    'hd.production_year',
                    'hd.tonnage_std',
                    'hd.tonnage_min',
                    'hd.tonnage_max',
                    'hd.steel_type_id',
                    'st.steel_type_name',
                    'hd.estimation_number',
                    'hd.mold_maker',
                    'hd.cooling_method_id',
                    'cm.cooling_method_name',
                    'hd.mold_description',
                    'hd.length',
                    'hd.width',
                    'hd.height',
                    'hd.dimension_uom',
                    'hd.weight',
                    'hd.weight_uom',
                    'hd.plate_size_length',
                    'hd.plate_size_width',
                    'hd.plate_size_uom',
                    'hd.is_active',
                    'dt.item_id',
                    'pi.item_code',
                    'pi.item_name',
                    'cavity_number',
                    'product_weight',
                    'product_weight_uom',
                    'ca.is_active',
                    'ca.created_on',
                    'ca.created_by',
                    'ca.updated_on',
                    'ca.updated_by'
                )
                ->leftJoin('precise.mold_hd as hd2', 'hd.remake_from', '=', 'hd2.mold_hd_id')
                ->leftJoin('precise.mold_dt as dt', 'hd.mold_hd_id', '=', 'dt.mold_hd_id')
                ->leftJoin('precise.mold_cavity as ca', 'dt.mold_dt_id', '=', 'ca.mold_dt_id')
                ->leftJoin('precise.workcenter as w', 'hd.workcenter_id', '=', 'w.workcenter_id')
                ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
                ->leftJoin('precise.mold_status as ms', 'hd.status_code', '=', 'ms.status_code')
                ->leftJoin('precise.product_item as pi', 'dt.item_id', '=', 'pi.item_id')
                ->leftJoin('precise.steel_type as st', 'hd.steel_type_id', '=', 'st.steel_type_id')
                ->leftJoin('precise.cooling_method as cm', 'hd.cooling_method_id', '=', 'cm.cooling_method_id')
                ->get();

            return response()->json(["status" => "ok", "data" => $this->mold], 200);
        } catch (\Exception $e) {
            return response()->json(["status" => "error", "message" => $e->getMessage()], 500);
        }
    }

    public function showByWorkcenter($id): JsonResponse
    {
        $this->mold = DB::table("precise.mold_hd as hd")
            ->where('hd.workcenter_id', $id)
            ->select(
                "mold_hd_id",
                "mold_number",
                "mold_name",
                "mold_descrption"
            )
            ->get();

        return response()->json(["data" => $this->mold]);
    }

    public function showByProductItem($id)
    {
        $this->mold = DB::table("precise.mold_hd as hd")
            ->where('dt.product_item_id', $id)
            ->select(
                "hd.mold_hd_id",
                "mold_number",
                "mold_name",
                "mold_descrption"
            )
            ->leftJoin("precise.mold_dt as dt", "hd.mold_hd_id", "=", "dt.mold_hd_id")
            ->get();

        return response()->json(["data" => $this->mold]);
    }

    public function create(Request $request)
    {
        $data = $request->json()->all();
        //$detail = array();
        $validator = Validator::make(json_decode(json_encode($data), true), [
            'mold_number'       => 'required|unique:mold_hd,mold_number',
            'mold_name'         => 'required|unique:mold_hd,mold_name',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'is_family_mold'    => 'required|boolean',
            //'customer_id'       => 'required|exists:customer,customer_id',
            'remake_from'       => 'nullable|exists:mold_hd,mold_hd_id',
            //'length'            => 'required|numeric',
            //'width'             => 'required|numeric',
            //'height'            => 'required|numeric',
            //'dimension_uom'     => 'required|exists:uom,uom_code',
            //'weight'            => 'required|numeric',
            //'weight_uom'        => 'required|exists:uom,uom_code',
            //'plate_size_length' => 'required|numeric',
            //'plate_size_width'  => 'required|numeric',
            //'plate_size_uom'    => 'required|exists:uom,uom_code',
            'tonnage_std'       => 'required|exists:machine_tonnage,tonnage_group',
            'created_by'        => 'required',
            'detail'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            try {
                DB::beginTransaction();
                $id_hd = DB::table('precise.mold_hd')
                    ->insertGetId([
                        'mold_number'       => $data['mold_number'],
                        'mold_name'         => $data['mold_name'],
                        'workcenter_id'     => $data['workcenter_id'],
                        'is_family_mold'    => $data['is_family_mold'],
                        'customer_id'       => $data['customer_id'],
                        'remake_from'       => $data['remake_from'],
                        'mold_description'  => $data['mold_description'],
                        'length'            => $data['length'],
                        'width'             => $data['width'],
                        'height'            => $data['height'],
                        'dimension_uom'     => $data['dimension_uom'],
                        'weight'            => $data['weight'],
                        'weight_uom'        => $data['weight_uom'],

                        'tonnage_std'        => $data['tonnage_std'],
                        'tonnage_min'        => $data['tonnage_min'],
                        'tonnage_max'        => $data['tonnage_max'],
                        'production_year'        => $data['production_year'],
                        'steel_type_id'        => $data['steel_type_id'],
                        'estimation_number'        => $data['estimation_number'],
                        'mold_maker'        => $data['mold_maker'],
                        'cooling_method_id'        => $data['cooling_method_id'],

                        'plate_size_length' => $data['plate_size_length'],
                        'plate_size_width'  => $data['plate_size_length'],
                        'plate_size_uom'    => $data['plate_size_uom'],
                        'created_by'        => $data['created_by']
                    ]);

                foreach ($data['detail'] as $d) {
                    $validator = Validator::make(json_decode(json_encode($d), true), [
                        'item_id'   => 'required|exists:product_item,item_id',
                        'created_by'        => 'required',
                        'cavity_detail'     => 'required'
                    ]);

                    if ($validator->fails()) {
                        return response()->json(['status' => 'error', 'message' => $validator->errors()]);
                    } else {
                        $id_dt = DB::table('precise.mold_dt')->insertGetId([
                            'mold_hd_id'        => $id_hd,
                            'item_id'           => $d['item_id'],
                            'created_by'        => $d['created_by']
                        ]);
                        foreach ($d['cavity_detail'] as $dc) {
                            $validator = Validator::make(json_decode(json_encode($dc), true), [
                                'cavity_number'     => 'required',
                                'product_weight'    => 'required|numeric',
                                'product_weight_uom' => 'required|exists:uom,uom_code',
                                'is_active'         => 'required|boolean',
                                'created_by'        => 'required'
                            ]);

                            if ($validator->fails()) {
                                return response()->json(['status' => 'error', 'message' => $validator->errors()]);
                            } else {
                                $detail = [
                                    'mold_dt_id'        => $id_dt,
                                    'cavity_number'     => $dc['cavity_number'],
                                    'product_weight'    => $dc['product_weight'],
                                    'product_weight_uom' => $dc['product_weight_uom'],
                                    'is_active'         => $dc['is_active'],
                                    'created_by'        => $dc['created_by']
                                ];

                                DB::table('precise.mold_cavity')
                                    ->insert($detail);
                            }
                        }
                    }
                }

                $trans = DB::table('precise.mold_hd')
                    ->where('mold_hd_id', $id_hd)
                    ->select('mold_number')
                    ->first();

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => $trans->mold_number]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function update(Request $request)
    {
        $data = $request->json()->all();
        $validator = Validator::make(json_decode(json_encode($data), true), [
            'mold_hd_id'        => 'required|exists:mold_hd,mold_hd_id',
            'mold_number'       => 'required',
            'mold_name'         => 'required',
            'workcenter_id'     => 'required|exists:workcenter,workcenter_id',
            'is_family_mold'    => 'required|boolean',
            //'customer_id'       => 'required|exists:customer,customer_id',
            //status_code'       => 'required|exists:mold_status,status_code',
            'remake_from'       => 'nullable|exists:mold_hd,mold_hd_id',
            //'length'            => 'required|numeric',
            //'width'             => 'required|numeric',
            //'height'            => 'required|numeric',
            //'dimension_uom'     => 'required|exists:uom,uom_code',
            //'weight'            => 'required|numeric',
            //'weight_uom'        => 'required|exists:uom,uom_code',
            //'plate_size_length' => 'required|numeric',
            //'plate_size_width'  => 'required|numeric',
            //'plate_size_uom'    => 'required|exists:uom,uom_code',
            'tonnage_std'        => 'required|exists:machine_tonnage,tonnage_group',
            'updated_by'        => 'required',
            //'detail'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            DB::beginTransaction();
            try {
                QueryController::reason($data);
                $this->mold = DB::table('precise.mold_hd')
                    ->where('mold_hd_id', $data['mold_hd_id'])
                    ->update([
                        'mold_number'       => $data['mold_number'],
                        'mold_name'         => $data['mold_name'],
                        'workcenter_id'     => $data['workcenter_id'],
                        'is_family_mold'    => $data['is_family_mold'],
                        'customer_id'       => $data['customer_id'],
                        'status_code'       => $data['status_code'],
                        'remake_from'       => $data['remake_from'],
                        'mold_description'  => $data['mold_description'],
                        'length'            => $data['length'],
                        'width'             => $data['width'],
                        'height'            => $data['height'],
                        'dimension_uom'     => $data['dimension_uom'],
                        'weight'            => $data['weight'],
                        'weight_uom'        => $data['weight_uom'],
                        'plate_size_length' => $data['plate_size_length'],
                        'plate_size_width'  => $data['plate_size_length'],
                        'plate_size_uom'    => $data['plate_size_uom'],
                        'tonnage_std'       => $data['tonnage_std'],
                        'tonnage_min'       => $data['tonnage_min'],
                        'tonnage_max'       => $data['tonnage_max'],
                        'production_year'   => $data['production_year'],
                        'steel_type_id'     => $data['steel_type_id'],
                        'estimation_number' => $data['estimation_number'],
                        'mold_maker'        => $data['mold_maker'],
                        'cooling_method_id' => $data['cooling_method_id'],
                        'updated_by'        => $data['updated_by']
                    ]);

                if ($data['inserted'] != null) {
                    foreach ($data['inserted'] as $d) {
                        $validator = Validator::make(json_decode(json_encode($d), true), [
                            'item_id'       => 'required|exists:product_item,item_id',
                            'created_by'    => 'required',
                            'cavity_added'  => 'required'
                        ]);
                        if ($validator->fails()) {
                            return response()->json(['status' => 'error1', 'message' => $validator->errors()]);
                        } else {
                            $id_dt = DB::table('precise.mold_dt')->insertGetId([
                                'mold_hd_id'        => $data['mold_hd_id'],
                                'item_id'           => $d['item_id'],
                                'created_by'        => $d['created_by']
                            ]);

                            foreach ($d['cavity_added'] as $dc) {
                                $validator = Validator::make(json_decode(json_encode($dc), true), [
                                    'cavity_number'     => 'required',
                                    'product_weight'    => 'required|numeric',
                                    'product_weight_uom' => 'required|exists:uom,uom_code',
                                    'is_active'         => 'required|boolean',
                                    'created_by'        => 'required'
                                ]);

                                if ($validator->fails()) {
                                    return response()->json(['status' => 'error2', 'message' => $validator->errors()]);
                                } else {
                                    $detail_cavity = [
                                        'mold_dt_id'        => $id_dt,
                                        'cavity_number'     => $dc['cavity_number'],
                                        'product_weight'    => $dc['product_weight'],
                                        'product_weight_uom' => $dc['product_weight_uom'],
                                        'is_active'         => $dc['is_active'],
                                        'created_by'        => $dc['created_by']
                                    ];

                                    DB::table('precise.mold_cavity')
                                        ->insert($detail_cavity);
                                }
                            }
                        }
                    }
                }

                if ($data['updated'] != null) {
                    foreach ($data['updated'] as $d) {
                        $validator = Validator::make(json_decode(json_encode($d), true), [
                            'mold_dt_id'        => 'required|exists:mold_dt,mold_dt_id',
                            'mold_hd_id'        => 'required|exists:mold_hd,mold_hd_id',
                            'item_id'           => 'required|exists:product_item,item_id',
                            'updated_by'        => 'required'
                        ]);

                        if ($validator->fails()) {
                            return response()->json(['status' => 'error3', 'message' => $validator->errors()]);
                        } else {
                            DB::table('precise.mold_dt')
                                ->where('mold_dt_id', $d['mold_dt_id'])
                                ->update([
                                    'mold_hd_id'    => $d['mold_hd_id'],
                                    'item_id'       => $d['item_id'],
                                    'updated_by'    => $d['updated_by']
                                ]);

                            if ($d['cavity_inserted'] != null) {
                                foreach ($d['cavity_inserted'] as $dc) {
                                    $validator = Validator::make(json_decode(json_encode($dc), true), [
                                        'mold_dt_id'        => 'required|exists:mold_dt,mold_dt_id',
                                        'cavity_number'     => 'required',
                                        'product_weight'    => 'required|numeric',
                                        'product_weight_uom' => 'required|exists:uom,uom_code',
                                        'is_active'         => 'required|boolean',
                                        'created_by'        => 'required'
                                    ]);

                                    if ($validator->fails()) {
                                        return response()->json(['status' => 'error4', 'message' => $validator->errors()]);
                                    } else {
                                        $detail = [
                                            'mold_dt_id'        => $d['mold_dt_id'],
                                            'cavity_number'     => $dc['cavity_number'],
                                            'product_weight'    => $dc['product_weight'],
                                            'product_weight_uom' => $dc['product_weight_uom'],
                                            'is_active'         => $dc['is_active'],
                                            'created_by'        => $dc['created_by']
                                        ];

                                        DB::table('precise.mold_cavity')
                                            ->insert($detail);
                                    }
                                }
                            }

                            if ($d['cavity_updated'] != null) {
                                foreach ($d['cavity_updated'] as $dc) {
                                    $validator = Validator::make(json_decode(json_encode($dc), true), [
                                        'mold_cavity_id'    => 'required|exists:mold_cavity,mold_cavity_id',
                                        'mold_dt_id'        => 'required|exists:mold_dt,mold_dt_id',
                                        'cavity_number'     => 'required',
                                        'product_weight'    => 'required|numeric',
                                        'product_weight_uom' => 'required|exists:uom,uom_code',
                                        'is_active'         => 'required|boolean',
                                        'updated_by'        => 'required'
                                    ]);

                                    if ($validator->fails()) {
                                        return response()->json(['status' => 'error5', 'message' => $validator->errors()]);
                                    } else {
                                        DB::table('precise.mold_cavity')
                                            ->where('mold_cavity_id', $dc['mold_cavity_id'])
                                            ->update([
                                                'mold_dt_id'        => $dc['mold_dt_id'],
                                                'cavity_number'     => $dc['cavity_number'],
                                                'product_weight'    => $dc['product_weight'],
                                                'product_weight_uom' => $dc['product_weight_uom'],
                                                'is_active'         => $dc['is_active'],
                                                'updated_by'        => $dc['updated_by']
                                            ]);
                                    }
                                }
                            }
                        }
                    }
                }
                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'Mold have been updated']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
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
            if ($type == "number") {
                $this->mold = DB::table('mold_hd')
                    ->where('mold_number', $value)
                    ->count();
            } else if ($type == "name") {
                $this->mold = DB::table('mold_hd')
                    ->where('mold_name', $value)
                    ->count();
            }

            return response()->json(['status' => 'ok', 'message' => $this->mold]);
        }
    }
}
