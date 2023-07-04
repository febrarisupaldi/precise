<?php

namespace App\Http\Controllers\Api\Master;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\Helpers\DBController;
use Symfony\Component\HttpFoundation\JsonResponse;

class MoldPressingController extends Controller
{
    private $mold;
    public function index(): JsonResponse
    {
        $this->mold = DB::table("precise.mold_pressing_hd as mp")
            ->select(
                'mp.mold_pressing_hd_id',
                'mp.mold_number',
                'mp.mold_code',
                'mp.mold_group',
                'mp.item_code',
                'mp.default_tonnage',
                'mp.mold_description',
                'mp.mold_status_code',
                'ms.status_description',
                'mp.mold_parent_id',
                'mp.production_date',
                'mp.mold_making_id',
                'mp.mold_maker',
                'mp.created_on',
                'mp.created_by',
                'mp.updated_on',
                'mp.updated_by'
            )
            ->join("precise.mold_status as ms", "mp.mold_status_code", "=", "ms.status_code")
            ->get();

        return response()->json(["data" => $this->mold], 200);
    }

    public function show($id): JsonResponse
    {
        $master = DB::table("precise.mold_pressing_hd")
            ->where("mold_pressing_hd_id", $id)
            ->select(
                "mold_pressing_hd_id",
                "mold_number",
                "mold_code",
                "mold_group",
                "item_code",
                "default_tonnage",
                "mold_description",
                "mold_status_code",
                "mold_parent_id",
                "production_date",
                "mold_making_id",
                "mold_maker",
                "created_on",
                "created_by",
                "updated_on",
                "updated_by"
            )->first();
        if ($master != null) {

            $detail = DB::table("precise.mold_pressing_dt")
                ->where("mold_pressing_hd_id", $id)
                ->select(
                    "mold_pressing_dt_id",
                    "mold_pressing_hd_id",
                    "cavity_number",
                    "product_weight",
                    "product_weight_uom",
                    "is_active",
                    "created_on",
                    "created_by",
                    "updated_on",
                    "updated_by"
                )->get();

            $this->mold = array(
                "mold_pressing_hd_id"   => $master->mold_pressing_hd_id,
                "mold_number"           => $master->mold_number,
                "mold_code"             => $master->mold_code,
                "mold_group"            => $master->mold_group,
                "item_code"             => $master->item_code,
                "default_tonnage"       => $master->default_tonnage,
                "mold_description"      => $master->mold_description,
                "mold_status_code"      => $master->mold_status_code,
                "mold_parent_id"        => $master->mold_parent_id,
                "production_date"       => $master->production_date,
                "mold_making_id"        => $master->mold_making_id,
                "mold_maker"            => $master->mold_maker,
                "created_on"            => $master->created_on,
                "created_by"            => $master->created_by,
                "updated_on"            => $master->updated_on,
                "updated_by"            => $master->updated_by,
                "detail"                => $detail
            );
        }

        return response()->json($this->mold, 200);
    }

    public function showByNumber($number): JsonResponse
    {
        $master = DB::table("precise.mold_pressing_hd")
            ->where("mold_number", $number)
            ->select(
                "mold_pressing_hd_id",
                "mold_number",
                "mold_code",
                "mold_group",
                "item_code",
                "default_tonnage",
                "mold_description",
                "mold_status_code",
                "mold_parent_id",
                "production_date",
                "mold_making_id",
                "mold_maker",
                "created_on",
                "created_by",
                "updated_on",
                "updated_by"
            )->first();
        if ($master != null) {

            $detail = DB::table("precise.mold_pressing_dt as dt")
                ->where("hd.mold_number", $number)
                ->select(
                    "dt.mold_pressing_dt_id",
                    "dt.mold_pressing_hd_id",
                    "dt.cavity_number",
                    "dt.product_weight",
                    "dt.product_weight_uom",
                    "dt.is_active",
                    "dt.created_on",
                    "dt.created_by",
                    "dt.updated_on",
                    "dt.updated_by"
                )
                ->join("precise.mold_pressing_hd as hd", "dt.mold_pressing_hd_id", "=", "hd.mold_pressing_hd_id")
                ->get();

            $this->mold = array(
                "mold_pressing_hd_id"   => $master->mold_pressing_hd_id,
                "mold_number"           => $master->mold_number,
                "mold_code"             => $master->mold_code,
                "mold_group"            => $master->mold_group,
                "item_code"             => $master->item_code,
                "default_tonnage"       => $master->default_tonnage,
                "mold_description"      => $master->mold_description,
                "mold_status_code"      => $master->mold_status_code,
                "mold_parent_id"        => $master->mold_parent_id,
                "production_date"       => $master->production_date,
                "mold_making_id"        => $master->mold_making_id,
                "mold_maker"            => $master->mold_maker,
                "created_on"            => $master->created_on,
                "created_by"            => $master->created_by,
                "updated_on"            => $master->updated_on,
                "updated_by"            => $master->updated_by,
                "detail"                => $detail
            );
        }

        return response()->json($this->mold, 200);
    }

    public function showMoldCavityByCode($code): JsonResponse
    {
        $this->mold = DB::table('precise.mold_pressing_hd as hd')
            ->where("hd.mold_code", $code)
            ->where("hd.mold_status_code", '!=', 'X')
            ->select(

                'mold_number',
                DB::raw("
                    MIN(hd.mold_code) AS mold_code,mold_group, 
                    GROUP_CONCAT(dt.`cavity_number` ORDER BY cavity_number SEPARATOR ',') as cavities
                ")
            )
            ->leftJoin("precise.mold_pressing_dt as dt", "hd.mold_pressing_hd_id", '=', 'dt.mold_pressing_hd_id')
            ->groupBy('mold_number', 'mold_group')
            ->get();

        return response()->json(["data" => $this->mold]);
    }

    public function showMoldCavityByNumber($number): JsonResponse
    {
        $this->mold = DB::table('precise.mold_pressing_hd as hd')
            ->where("hd.mold_number", $number)
            ->select(
                'mold_pressing_hd_id',
                'mold_number',
                DB::raw("
                    MIN(hd.mold_code) AS mold_code,mold_group, 
                    GROUP_CONCAT(dt.`cavity_number` ORDER BY cavity_number SEPARATOR ',') as cavities
                ")
            )
            ->leftJoin("precise.mold_pressing_dt as dt", "hd.mold_pressing_hd_id", '=', 'dt.mold_pressing_hd_id')
            ->groupBy('mold_number', 'mold_group')
            ->get();

        return response()->json(["data" => $this->mold]);
    }

    public function full(): JsonResponse
    {
        $this->mold = DB::table("precise.mold_pressing_hd as hd")
            ->select(
                "hd.mold_pressing_hd_id",
                "hd.mold_number",
                "hd.mold_code",
                "hd.mold_group",
                "hd.item_code",
                "hd.default_tonnage",
                "hd.mold_description",
                "hd.mold_status_code",
                "ms.status_description",
                "hd.mold_parent_id",
                "hd.production_date",
                "hd.mold_making_id",
                "hd.mold_maker",
                "dt.mold_pressing_dt_id",
                "dt.cavity_number",
                "dt.product_weight",
                "dt.product_weight_uom",
                "dt.is_active",
                "hd.created_on",
                "hd.created_by",
                "hd.updated_on",
                "hd.updated_by"
            )
            ->join("precise.mold_pressing_dt as dt", "hd.mold_pressing_hd_id", "=", "dt.mold_pressing_hd_id")
            ->join("precise.mold_status as ms", "hd.mold_status_code", "=", "ms.status_code")
            ->get();

        return response()->json(["data" => $this->mold], 200);
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'mold_number'       => 'required',
            'mold_code'         => 'required',
            'mold_group'        => 'required',
            'item_code'         => 'required',
            'default_tonnage'   => 'required',
            'desc'              => 'required',
            'mold_status_code'  => 'required|exists:mold_status,status_code',
            'mold_parent_id'    => 'required|exists:mold_pressing_hd,mold_pressing_hd_id',
            'production_date'   => 'required',
            'mold_making_id'    => 'required|exists:mold_making,mold_making_id',
            'mold_maker'        => 'required',
            'created_by'        => 'required',
            'detail'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            try {
                DB::beginTransaction();
                $header = DB::table("precise.mold_pressing_hd")
                    ->insertGetId([
                        'mold_number'       => $data['mold_number'],
                        'mold_code'         => $data['mold_code'],
                        'mold_group'        => $data['mold_group'],
                        'item_code'         => $data['item_code'],
                        'default_tonnage'   => $data['default_tonnage'],
                        'mold_description'  => $data['desc'],
                        'mold_status_code'  => $data['mold_status_code'],
                        'mold_parent_id'    => $data['mold_parent_id'],
                        'production_date'   => $data['production_date'],
                        'mold_making_id'    => $data['mold_making_id'],
                        'mold_maker'        => $data['mold_maker'],
                        'created_by'        => $data['created_by']
                    ]);

                foreach ($data['detail'] as $detail) {
                    $validator = Validator::make($detail, [
                        'cavity_number'     => 'required',
                        'created_by'        => 'required',
                        'product_weight'    => 'required',
                        'product_weight_uom' => 'required|exists:uom,uom_code'
                    ]);
                    if ($validator->fails()) {
                        return response()->json(['status' => 'error', 'message' => $validator->errors()]);
                    } else {
                        $d[] = [
                            'mold_pressing_hd_id'   => $header,
                            'cavity_number'         => $detail['cavity_number'],
                            'created_by'            => $detail['created_by'],
                            'product_weight'        => $detail['product_weight'],
                            'product_weight_uom'    => $detail['product_weight_uom']
                        ];
                    }
                }
                $this->mold = DB::table("precise.mold_pressing_dt")
                    ->insert($d);

                if ($this->mold == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'Failed to insert new mold pressing, Contact your administrator']);
                } else {
                    DB::commit();
                    return response()->json(['status' => 'ok', 'message' => 'Mold pressing has been inserted']);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $validator = Validator::make($data, [
            'mold_pressing_hd_id'   => 'required|exists:mold_pressing_hd,mold_pressing_hd_id',
            'mold_number'       => 'required',
            'mold_code'         => 'required',
            'mold_group'        => 'required',
            'item_code'         => 'required',
            'default_tonnage'   => 'required',
            'desc'              => 'required',
            'mold_status_code'  => 'required|exists:mold_status,status_code',
            'mold_parent_id'    => 'required|exists:mold_pressing_hd,mold_pressing_hd_id',
            'production_date'   => 'required',
            'mold_making_id'    => 'required|exists:mold_making,mold_making_id',
            'mold_maker'        => 'required',
            'updated_by'        => 'required',
            'reason'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            try {
                DB::beginTransaction();
                DBController::reason($request, "update");
                $header = DB::table("precise.mold_pressing_hd")
                    ->where('mold_pressing_hd_id', $data['mold_pressing_hd_id'])
                    ->update([
                        'mold_number'       => $data['mold_number'],
                        'mold_code'         => $data['mold_code'],
                        'mold_group'        => $data['mold_group'],
                        'item_code'         => $data['item_code'],
                        'default_tonnage'   => $data['default_tonnage'],
                        'mold_description'  => $data['desc'],
                        'mold_status_code'  => $data['mold_status_code'],
                        'mold_parent_id'    => $data['mold_parent_id'],
                        'production_date'   => $data['production_date'],
                        'mold_making_id'    => $data['mold_making_id'],
                        'mold_maker'        => $data['mold_maker'],
                        'updated_by'        => $data['updated_by']
                    ]);

                if ($data['inserted'] != null) {
                    foreach ($data['inserted'] as $ins) {
                        $validator = Validator::make($ins, [
                            'mold_pressing_hd_id'   => 'required|exists:mold_pressing_hd,mold_pressing_hd_id',
                            'cavity_number'     => 'required',
                            'product_weight'    => 'required',
                            'product_weight_uom' => 'required|exists:uom,uom_code',
                            'created_by'        => 'required'
                        ]);
                        if ($validator->fails()) {
                            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
                        } else {
                            $ins_dt[] = [
                                "mold_pressing_hd_id"   => $ins['mold_pressing_hd_id'],
                                "cavity_number"         => $ins["cavity_number"],
                                "product_weight"        => $ins["product_weight"],
                                "product_weight_uom"    => $ins["product_weight_uom"],
                                "created_by"            => $ins["created_by"]
                            ];
                        }
                    }

                    DB::table("precise.mold_pressing_dt")
                        ->insert($ins_dt);
                }

                if ($data['updated'] != null) {
                    foreach ($data['updated'] as $upd) {
                        $validator = Validator::make($upd, [
                            'mold_pressing_dt_id'   => 'required|exists:mold_pressing_dt,mold_pressing_dt_id',
                            'mold_pressing_hd_id'   => 'required|exists:mold_pressing_hd,mold_pressing_hd_id',
                            'cavity_number'         => 'required',
                            'is_active'             => 'required|boolean',
                            'product_weight'        => 'required',
                            'product_weight_uom'    => 'required|exists:uom,uom_code',
                            'updated_by'            => 'required'
                        ]);
                        if ($validator->fails()) {
                            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
                        } else {
                            DB::table("precise.mold_pressing_dt")
                                ->where("mold_pressing_dt_id", $upd["mold_pressing_dt_id"])
                                ->update([
                                    "mold_pressing_hd_id"   => $upd['mold_pressing_hd_id'],
                                    "cavity_number"         => $upd["cavity_number"],
                                    "is_active"             => $upd["is_active"],
                                    "product_weight"        => $upd["product_weight"],
                                    "product_weight_uom"    => $upd["product_weight_uom"],
                                    "updated_by"            => $upd["updated_by"]
                                ]);
                        }
                    }
                }

                if ($data['deleted'] != null) {
                    foreach ($data['deleted'] as $del) {
                        $delete[] = $del['mold_pressing_dt_id'];
                    }

                    DB::table("precise.mold_pressing_dt")
                        ->where("mold_pressing_dt_id", $delete);
                }

                DB::commit();
                return response()->json(['status' => 'ok', 'message' => 'Mold pressing has been updated']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mold_pressing_hd_id'   => 'required|exists:mold_pressing_hd,mold_pressing_hd_id',
            'reason'                => 'required',
            'deleted_by'            => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            try {
                DB::beginTransaction();
                DBController::reason($request, "delete");
                $this->mold = DB::table("precise.mold_pressing_dt")
                    ->where("mold_pressing_hd_id", $request->mold_pressing_hd_id)
                    ->delete();

                if ($this->mold == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'Failed to delete mold pressing, Contact your administrator']);
                } else {
                    $this->mold = DB::table("precise.mold_pressing_hd")
                        ->where("mold_pressing_hd_id", $request->mold_pressing_hd_id)
                        ->delete();

                    if ($this->mold == 0) {
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => 'Failed to delete mold pressing, Contact your administrator']);
                    } else {
                        DB::commit();
                        return response()->json(['status' => 'ok', 'message' => 'Mold pressing has been deleted']);
                    }
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
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
            return response()->json(['status' => 'error', 'message' => $validator->errors()]);
        } else {
            if ($type == "number") {
                $this->mold = DB::table('precise.mold_pressing_hd')->where('mold_number', $value)->count();
            }
            return response()->json(['status' => 'ok', 'message' => $this->mold]);
        }
    }
}
