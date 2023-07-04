<?php

namespace App\Http\Controllers\Api\OEM;

use App\Http\Controllers\Api\Helpers\DBController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class MaterialIncomingController extends Controller
{
    private $materialIncoming;
    public function index(Request $request): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $wh = $request->get('warehouse_id');
        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start',
            'warehouse_id'  => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $warehouse = explode('-', $wh);
            $this->materialIncoming = DB::table('precise.oem_material_trans_hd as hd')
                ->whereBetween('hd.trans_date', [$start, $end])
                ->whereIn('hd.warehouse_id', $warehouse)
                ->select(
                    'material_trans_hd_id',
                    'trans_number',
                    'trans_date',
                    DB::raw("
                        concat(c.customer_code, ' - ', c.customer_name) as 'Customer',
                        concat(w.warehouse_code, ' - ', w.warehouse_name) as 'Gudang'
                    "),
                    'trans_description',
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
                ->leftJoin('precise.warehouse as w', 'hd.warehouse_id', '=', 'w.warehouse_id')
                ->get();

            if (count($this->materialIncoming) == 0)
                return response()->json(['status' => 'error', 'data' => 'not found'], 404);
            return response()->json(['status' => 'ok', 'data' => $this->materialIncoming], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id): JsonResponse
    {
        $master = DB::table('precise.oem_material_trans_hd as hd')
            ->where('material_trans_hd_id', $id)
            ->select(
                'material_trans_hd_id',
                'trans_number',
                'trans_date',
                'trans_type_id',
                'hd.customer_id',
                'c.customer_code',
                'c.customer_name',
                'hd.warehouse_id',
                'w.warehouse_code',
                'w.warehouse_name',
                'trans_description',
                'hd.created_on',
                'hd.created_by',
                'hd.updated_on',
                'hd.updated_by'
            )
            ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
            ->leftJoin('precise.warehouse as w', 'hd.warehouse_id', '=', 'w.warehouse_id')
            ->first();

        if (empty($master))
            return response()->json("not found", 404);

        $detail = DB::table('precise.oem_material_trans_dt as dt')
            ->where('material_trans_hd_id', $master->material_trans_hd_id)
            ->select(
                'material_trans_dt_id',
                'material_trans_hd_id',
                'dt.material_customer_hd_id',
                'mch.material_id',
                'product_code as material_code',
                'product_name as material_name',
                'mch.customer_id',
                'c.customer_code',
                'c.customer_name',
                'material_qty',
                'material_uom',
                'dt.created_on',
                'dt.created_by',
                'dt.updated_on',
                'dt.updated_by'
            )
            ->leftJoin('precise.material_customer_hd as mch', 'dt.material_customer_hd_id', '=', 'mch.material_customer_hd_id')
            ->leftJoin('precise.product as p', 'mch.material_id', '=', 'p.product_id')
            ->leftJoin('precise.customer as c', 'mch.customer_id', '=', 'c.customer_id')
            ->get();

        foreach ($detail as $dataDetail) {
            $purchaseOrder = DB::table("precise.oem_material_allocation", "oma")
                ->where("omtdt.material_trans_hd_id", $master->material_trans_hd_id)
                ->select(
                    "oma.material_allocation_id",
                    "oma.material_trans_dt_id",
                    "omtdt.material_trans_hd_id",
                    "oma.oem_order_dt_id",
                    "oohd.oem_order_number",
                    "pc.product_id",
                    "p.product_code",
                    "p.product_name",
                    "oodt.oem_order_qty",
                    "p.uom_code",
                    "oma.allocation_qty",
                    "oma.allocation_uom",
                    "oma.created_on",
                    "oma.created_by",
                    "oma.updated_on",
                    "oma.updated_by"
                )
                ->leftJoin("precise.oem_material_trans_dt as omtdt", "oma.material_trans_dt_id", "=", "omtdt.material_trans_dt_id")
                ->leftJoin("precise.oem_order_dt as oodt", "oma.oem_order_dt_id", "=", "oodt.oem_order_dt_id")
                ->leftJoin("precise.oem_order_hd as oohd", "oodt.oem_order_hd_id", "=", "oohd.oem_order_hd_id")
                ->leftJoin("precise.product_customer as pc", "oodt.product_customer_id", "=", "pc.product_customer_id")
                ->leftJoin("precise.product as p", "pc.product_id", "=", "p.product_id")
                ->get();

            $detail2[] = array_merge((array)$dataDetail, array("purchaseorder_detail" => $purchaseOrder));
        }

        $this->materialIncoming = array_merge_recursive(
            (array)$master,
            array("detail" => $detail2)
        );

        return response()->json($this->materialIncoming, 200);
    }

    public function getMaterialIncommingUnallocated()
    {
        $query = DB::table("precise.oem_material_trans_hd", "hd")
            ->select(
                "hd.material_trans_hd_id",
                "hd.customer_id",
                "dt.material_customer_hd_id",
                "dt.material_qty",
                "dt.material_uom",
                DB::raw("
                    SUM(IFNULL(ma.allocation_qty, 0)) sumOfAllocation
                ")
            )
            ->join("precise.oem_material_trans_dt as dt", "hd.material_trans_hd_id", "=", "dt.material_trans_hd_id")
            ->leftJoin("precise.oem_material_allocation as ma", "dt.material_trans_dt_id", "=", "ma.material_trans_dt_id")
            ->groupBy("hd.material_trans_hd_id", "hd.customer_id", "dt.material_customer_hd_id", "dt.material_qty", "dt.material_uom");

        $query2 = DB::table(DB::raw("({$query->toSql()})as source"))
            ->select(
                "source.material_trans_hd_id",
                "hd.trans_number",
                "source.customer_id",
                "c.customer_code",
                "c.customer_name",
                "hd.trans_date",
                "source.material_customer_hd_id",
                "mch.material_id",
                "p.product_code",
                "p.product_name",
                "source.material_qty",
                "source.material_uom",
                "sumOfAllocation"
            )
            ->leftJoin("precise.oem_material_trans_hd as hd", "source.material_trans_hd_id", "=", "hd.material_trans_hd_id")
            ->leftJoin("precise.customer as c", "hd.customer_id", "=", "c.customer_id")
            ->leftJoin("precise.material_customer_hd as mch", "source.material_customer_hd_id", "=", "mch.material_customer_hd_id")
            ->leftJoin("precise.product as p", "mch.material_id", "=", "p.product_id");

        $this->materialIncoming = DB::table(DB::raw("({$query2->toSql()})as source"))
            ->where("sumOfAllocation", "<", "material_qty")
            ->get();

        if (count($this->materialIncoming) == 0)
            return response()->json(['status' => 'error', 'data' => 'not found'], 404);
        return response()->json(['status' => 'ok', 'data' => $this->materialIncoming], 200);
    }

    public function joined(Request $request): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $wh = $request->get('warehouse_id');
        $validator = Validator::make($request->all(), [
            'start'         => 'required|date_format:Y-m-d|before_or_equal:end',
            'end'           => 'required|date_format:Y-m-d|after_or_equal:start',
            'warehouse_id'  => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        try {
            $warehouse = explode('-', $wh);
            $this->materialIncoming = DB::table('precise.oem_material_trans_hd as hd')
                ->whereBetween('hd.trans_date', [$start, $end])
                ->whereIn('hd.warehouse_id', $warehouse)
                ->select(
                    'hd.material_trans_hd_id',
                    'trans_number',
                    'trans_date',
                    DB::raw("
                    concat(c.customer_code, ' - ', c.customer_name) as 'Customer',
                    concat(w.warehouse_code, ' - ', w.warehouse_name) as 'Gudang'
                "),
                    'trans_description',
                    'p.product_code',
                    'p.product_name',
                    'dt.material_qty',
                    'dt.material_uom',
                    'hd.created_on',
                    'hd.created_by',
                    'hd.updated_on',
                    'hd.updated_by'
                )
                ->leftJoin('precise.oem_material_trans_dt as dt', 'hd.material_trans_hd_id', '=', 'dt.material_trans_hd_id')
                ->leftJoin('precise.customer as c', 'hd.customer_id', '=', 'c.customer_id')
                ->leftJoin('precise.warehouse as w', 'hd.warehouse_id', '=', 'w.warehouse_id')
                ->leftJoin('precise.material_customer_hd as mch', 'dt.material_customer_hd_id', '=', 'mch.material_customer_hd_id')
                ->leftJoin('precise.product as p', 'mch.material_id', '=', 'p.product_id')
                ->get();

            if (count($this->materialIncoming) == 0)
                return response()->json(['status' => 'error', 'data' => 'not found'], 404);
            return response()->json(['status' => 'ok', 'data' => $this->materialIncoming], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function create(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        $validator = Validator::make($data, [
            'trans_date'    => 'required|date_format:Y-m-d',
            'trans_type_id' => 'required|exists:warehouse_trans_type,warehouse_trans_type_id',
            'customer_id'   => 'required|exists:customer,customer_id',
            'warehouse_id'  => 'required|exists:warehouse,warehouse_id',
            'desc'          => 'nullable',
            'created_by'    => 'required',
            'detail'        => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            $id = DB::table('precise.oem_material_trans_hd')
                ->insertGetId([
                    'trans_date'        => $data['trans_date'],
                    'trans_type_id'     => $data['trans_type_id'],
                    'customer_id'       => $data['customer_id'],
                    'warehouse_id'      => $data['warehouse_id'],
                    'trans_description' => $data['desc'],
                    'created_by'        => $data['created_by']
                ]);

            if ($id == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => "server error"], 500);
            }
            foreach ($data['detail'] as $d) {
                $validator = Validator::make($d, [
                    'material_customer_hd_id'   => 'required|exists:material_customer_hd,material_customer_hd_id',
                    'material_qty'              => 'required|numeric',
                    'material_uom'              => 'required|exists:uom,uom_code',
                    'created_by'                => 'required',
                    'purchaseorder_detail'      => 'required'
                ]);

                if ($validator->fails()) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                }

                $id = DB::table('precise.oem_material_trans_dt')
                    ->insertGetId([
                        'material_trans_hd_id'    => $id,
                        'material_customer_hd_id' => $d['material_customer_hd_id'],
                        'material_qty'            => $d['material_qty'],
                        'material_uom'            => $d['material_uom'],
                        'created_by'              => $d['created_by']
                    ]);

                if ($id == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }
                $values = [];
                foreach ($d["purchaseorder_detail"] as $po) {
                    $validator = Validator::make($po, [
                        'oem_order_dt_id'   => 'required|exists:oem_order_dt,oem_order_dt_id',
                        'allocation_qty'    => 'required|numeric',
                        'allocation_uom'    => 'required|exists:uom,uom_code',
                        'created_by'        => 'required'
                    ]);

                    if ($validator->fails()) {
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
                    }

                    $values[] = [
                        'material_trans_dt_id'  => $id,
                        'oem_order_dt_id'       => $po['oem_order_dt_id'],
                        'allocation_qty'        => $po['allocation_qty'],
                        'allocation_uom'        => $po['allocation_uom'],
                        'created_by'            => $po['created_by']
                    ];
                }

                $check = DB::table("precise.oem_material_allocation")
                    ->insert($values);

                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }
            }

            $trans = DB::table('precise.oem_material_trans_hd')
                ->where('material_trans_hd_id', $id)
                ->select('trans_number')
                ->first();

            DB::commit();
            return response()->json(['status' => 'ok', 'message' => $trans->trans_number], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->json()->all();

        $validator = Validator::make($data, [
            'material_trans_hd_id'  => 'required|exists:oem_material_trans_hd,oem_material_trans_hd_id',
            'trans_date'            => 'required|date_format:Y-m-d',
            'customer_id'           => 'required|exists:customer,customer_id',
            'warehouse_id'          => 'required|exists:warehouse,warehouse_id',
            'desc'                  => 'nullable',
            'reason'                => 'required',
            'updated_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "update", $data);
            DB::table('precise.oem_material_trans_hd')
                ->where('material_trans_hd_id', $data['material_trans_hd_id'])
                ->update([
                    'trans_date'        => $data['trans_date'],
                    'customer_id'       => $data['customer_id'],
                    'warehouse_id'      => $data['warehouse_id'],
                    'trans_description' => $data['desc'],
                    'updated_by'        => $data['updated_by']
                ]);

            if ($data['inserted'] != null) {
                foreach ($data['inserted'] as $d) {
                    $dt[] = [
                        'material_trans_hd_id'    => $d['material_trans_hd_id'],
                        'material_customer_hd_id' => $d['material_customer_hd_id'],
                        'material_qty'            => $d['material_qty'],
                        'material_uom'            => $d['material_uom'],
                        'created_by'              => $d['created_by']
                    ];
                }
                $check = DB::table('precise.oem_material_trans_dt')
                    ->insert($dt);
                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }
            }

            if ($data['updated'] != null) {
                foreach ($data['updated'] as $d) {
                    $check = DB::table('precise.oem_material_trans_dt')
                        ->where('material_trans_dt_id', $d['material_trans_dt_id'])
                        ->update([
                            'material_customer_hd_id' => $d['material_customer_hd_id'],
                            'material_qty'            => $d['material_qty'],
                            'material_uom'            => $d['material_uom'],
                            'updated_by'              => $d['updated_by']
                        ]);

                    if ($check == 0) {
                        DB::rollBack();
                        return response()->json(['status' => 'error', 'message' => "server error"], 500);
                    }
                }
            }

            if ($data['deleted'] != null) {
                $delete = array();
                foreach ($data['deleted'] as $del) {
                    $delete[] = $del['material_trans_dt_id'];
                }

                $check = DB::table('precise.oem_material_trans_dt')
                    ->whereIn('material_trans_dt_id', $delete)
                    ->delete();

                if ($check == 0) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => "server error"], 500);
                }
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success update data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'material_trans_hd_id'  => 'required|exists:material_trans_hd,material_trans_hd_id',
            'reason'                => 'required',
            'deleted_by'            => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        }
        DB::beginTransaction();
        try {
            DBController::reason($request, "delete");

            $check = DB::table('precise.oem_material_trans_dt as dt')
                ->join('precise.oem_material_trans_hd as hd', 'dt.material_trans_hd_id', '=', 'hd.material_trans_hd_id')
                ->whereRaw("dt.material_trans_hd_id = if('" . $request->material_trans_hd_id . "' regexp '^-?[0-9]+$' = 1,'" . $request->material_trans_hd_id . "', 0)")
                ->orWhereRaw("trans_number = cast('" . $request->material_trans_hd_id . "' as char)")
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'server error'], 500);
            }

            $check = DB::table('precise.oem_material_trans_hd')
                ->whereRaw("material_trans_hd_id = if('" . $request->material_trans_hd_id . "' regexp '^-?[0-9]+$' = 1,'" . $request->material_trans_hd_id . "', 0)")
                ->orWhereRaw("trans_number = cast('" . $request->material_trans_hd_id . "' as char)")
                ->delete();

            if ($check == 0) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'server error'], 500);
            }
            DB::commit();
            return response()->json(['status' => 'ok', 'message' => 'success delete data'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
